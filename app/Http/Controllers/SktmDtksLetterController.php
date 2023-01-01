<?php

namespace App\Http\Controllers;

use App\Http\Resources\SktmDtksLetterCollection as ThisCollection;
use App\Models\SktmDtksLetter as ThisLetter;
use App\Models\LetterOfficial;
use App\Models\Official;
use App\Models\ReferenceNumber;
use App\Models\Resident;
use App\Models\ResidentSktmDtksLetter;
use App\Models\Whatsapp;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use PhpOffice\PhpWord\TemplateProcessor;
use Illuminate\Support\Str;

class SktmDtksLetterController extends Controller
{
    private $type;

    public function __construct()
    {
        $this->type = ThisLetter::$type;
    }

    public function reference_number()
    {
        $format = ReferenceNumber::get_reference_number($this->type, auth()->id());

        if (!$format) {
            return response()->json("Format surat tidak ditemukan", 404);
        }
        return response()->json($format, 200);
    }

    public function index(Request $request)
    {
        $letters = ThisLetter::with(['penandatangan_kecamatan', 'penandatangan_desa'])->byUserId(auth()->id())
            ->where(function ($q) use ($request) {
                $q->where(DB::raw("CONCAT(prefix, sktm_dtks_letters.order, suffix)"), 'like', '%' . $request->search . '%')
                    ->orWhere(DB::raw("CONCAT(prefix_desa, sktm_dtks_letters.order_desa, suffix_desa)"), 'like', '%' . $request->search . '%')
                    ->orWhereHas('resident_sktm_dtks_letters.resident', function ($query) use ($request) {
                        return $query->where('nik', 'like', '%' . $request->search . '%')
                            ->orWhere('name', 'like', '%' . $request->search . '%');
                    });
            })->paginate(5);
        return new ThisCollection($letters);
    }

    public function download($id)
    {
        $letter = ThisLetter::byUserId(auth()->id())->with(['user', 'penandatangan_kecamatan', 'penandatangan_desa', 'resident_sktm_dtks_letters.resident'])->where('id', $id)->first();

        if (!$letter) {
            return response()->json("Surat tidak ditemukan", 404);
        }

        if ($letter->is_verified()) {
            return response()->download(storage_path("app/" . $letter->verified_file), str_replace("/", "_", $letter->get_reference_number()) . ".pdf");
        }

        // Cek apakah user mempunyai akses untuk memverifikasi surat ini
        if (!$letter->can_verified()) {
            return response()->json("Surat tidak ditemukan", 404);
        }

        $templateProcessor = new TemplateProcessor(storage_path("app/template_word/SKTM_DTKS.docx"));

        // Kebutuhan data dari table user dan instansi
        $templateProcessor->setValue('email', $letter->user->email);
        $templateProcessor->setValue('provinsi', $letter->user->province);
        $templateProcessor->setValue('kabupaten_upper', Str::upper($letter->user->district));
        $templateProcessor->setValue('desa_upper', Str::upper($letter->user->kop_village()));
        $templateProcessor->setValue('kecamatan_upper', Str::upper($letter->user->sub_district));
        $templateProcessor->setValue('kabupaten', $letter->user->district);
        $templateProcessor->setValue('kecamatan', $letter->user->sub_district);
        $templateProcessor->setValue('desa_kop', $letter->user->kop_village());
        $templateProcessor->setValue('desa', $letter->user->village);
        $templateProcessor->setValue('jalan', $letter->user->street);
        $templateProcessor->setValue('kode_pos', $letter->user->zip_code);

        // Kebutuhan data yang terkait dengan pemohon atau pembuat surat
        $templateProcessor->setValue('nik', $letter->resident_sktm_dtks_letters[0]->resident->nik);
        $templateProcessor->setValue('nama', $letter->resident_sktm_dtks_letters[0]->resident->name);
        $templateProcessor->setValue('ttl', $letter->resident_sktm_dtks_letters[0]->resident->place_of_birth . ", " . Carbon::parse($letter->resident_sktm_dtks_letters[0]->resident->date_of_birth)->translatedFormat('j F Y'));
        $templateProcessor->setValue('jenis_kelamin', $letter->resident_sktm_dtks_letters[0]->resident->gender);
        $templateProcessor->setValue('agama', $letter->resident_sktm_dtks_letters[0]->resident->religion);
        $templateProcessor->setValue('pekerjaan', $letter->resident_sktm_dtks_letters[0]->resident->profession);
        $templateProcessor->setValue('alamat', $letter->resident_sktm_dtks_letters[0]->resident->address);

        // Kebutuhan data terkait anggota keluarga
        $letter->resident_sktm_dtks_letters->splice(0, 1);
        $values = $letter->resident_sktm_dtks_letters->map(function ($q, $key) {
            $result = $q->resident;
            return [
                "no" => $key + 1,
                "nama_2" => $result->name,
                "nik_2" => $result->nik,
                "ttl_2" => $result->place_of_birth . ", " . Carbon::parse($result->date_of_birth)->translatedFormat('j F Y'),
            ];
        });
        $templateProcessor->cloneRowAndSetValues('no', $values);

        // Kebutuhan data yang terkait dengan data surat
        $templateProcessor->setValue('nomor_surat_kecamatan', $letter->get_reference_number());
        $templateProcessor->setValue('nomor_surat', $letter->get_reference_number_desa());
        $templateProcessor->setValue('tanggal_surat', Carbon::parse($letter->updated_at)->translatedFormat('j F Y'));
        $templateProcessor->setValue('dasar_1', $letter->dasar_1);
        $templateProcessor->setValue('dasar_2', $letter->dasar_2);
        $templateProcessor->setValue('digunakan_untuk', $letter->used_as);
        $templateProcessor->setValue('pimpinan_desa', $letter->user->leader() . " " . $letter->user->village);

        // Kebutuhan data yang terkait dengan pejabat yang menandatangan
        $templateProcessor->setValue('nip_penandatangan_kecamatan', 'NIP. ' . $letter->penandatangan_kecamatan->nip);
        $templateProcessor->setValue('nama_penandatangan_kecamatan', $letter->penandatangan_kecamatan->name);
        $templateProcessor->setValue('jabatan_penandatangan_kecamatan', $letter->penandatangan_kecamatan->position);
        $templateProcessor->setValue('pangkat_penandatangan_kecamatan', $letter->penandatangan_kecamatan->rank);
        $templateProcessor->setImageValue('signature_kecamatan', [
            'path' => storage_path('app/signature/' . $letter->penandatangan_kecamatan->signature),
            'width' => 100,
            'height' => 100,
            'ratio' => true,
        ]);

        $templateProcessor->setValue('nip_penandatangan_desa', 'NIP. ' . $letter->penandatangan_desa->nip);
        $templateProcessor->setValue('nama_penandatangan_desa', $letter->penandatangan_desa->name);
        $templateProcessor->setValue('jabatan_penandatangan_desa', $letter->penandatangan_desa->position);
        $templateProcessor->setValue('pangkat_penandatangan_desa', $letter->penandatangan_desa->rank);

        $fileNameServer = 'app/tmp/' . auth()->user()->id . '-' . md5(auth()->user()->id . uniqid() . time() . microtime()) . '.docx';
        $templateProcessor->saveAs(storage_path($fileNameServer));

        $filename = Str::replace("/", "-", $letter->get_reference_number_desa()) . '.docx';
        return response()->download(storage_path($fileNameServer), $filename)->deleteFileAfterSend(true);
    }

    public function store(Request $request)
    {
        $request->residents = json_decode($request->residents);
        $validator = Validator::make($request->all(), [
            "residents" => "required",
            "dasar_1" => "required|string",
            "dasar_2" => "required|string",
            "surat_pengantar" => "required|mimes:pdf,jpg,jpeg,png",
            "kartu_keluarga" => "required|mimes:pdf,jpg,jpeg,png",
            "used_as" => "required|string",
            "penandatangan_desa_id" => "required",
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();
            return response()->json($errors, 500);
        }

        DB::beginTransaction();

        $kartu_keluarga = null;
        $surat_pengantar = null;
        $signature_desa = null;

        try {
            // buat surat baru
            $new_letter = new ThisLetter();

            // SET ORDER agar tidak auto increment
            $latest = ThisLetter::whereHas('user', function (Builder $query) {
                $query->where('parent_id', auth()->user()->parent->id);
            })->latest('order')->first();
            $new_letter->order = ($latest ? $latest->order : 0) + 1;

            $latest_desa = ThisLetter::whereHas('user', function (Builder $query) {
                $query->where('id', auth()->id());
            })->latest('order_desa')->first();
            $new_letter->order_desa = ($latest_desa ? $latest_desa->order_desa : 0) + 1;


            // SET no surat berdasarkan format surat
            $format = ReferenceNumber::get_reference_number($this->type, auth()->user()->parent_id);

            $new_letter->prefix = ReferenceNumber::parse_reference_number($format["prefix"]);
            $new_letter->suffix = ReferenceNumber::parse_reference_number($format["suffix"]);

            $format_desa = ReferenceNumber::get_reference_number($this->type, auth()->id());

            $new_letter->prefix_desa = ReferenceNumber::parse_reference_number($format_desa["prefix"]);
            $new_letter->suffix_desa = ReferenceNumber::parse_reference_number($format_desa["suffix"]);

            $new_letter->dasar_1 = $request->dasar_1;
            $new_letter->dasar_2 = $request->dasar_2;
            $surat_pengantar = $request->file('surat_pengantar')->storePublicly('surat_pengantar', 'public');
            $new_letter->surat_pengantar = $surat_pengantar;
            $kartu_keluarga = $request->file('kartu_keluarga')->storePublicly('kartu_keluarga', 'public');
            $new_letter->kartu_keluarga = $kartu_keluarga;
            $new_letter->used_as = $request->used_as;
            $new_letter->user_id = auth()->id();

            // Cek data penduduk sebagai pemohon surat

            $residents_exist = Resident::whereIn('id', $request->residents)->count();

            if ($residents_exist < count($request->residents)) {
                throw new Exception("resident_not_found");
            }

            //cek penandatangan desa apakah terdaftar atau tidak
            $official = Official::find($request->penandatangan_desa_id);
            if (!$official) {
                throw new Exception("official_not_found");
            }

            //penandatangan
            $penandatangan_desa = Official::find($request->penandatangan_desa_id);
            $new_penandatangan_desa = new LetterOfficial();
            $new_penandatangan_desa->nip = $penandatangan_desa->nip;
            $new_penandatangan_desa->name = $penandatangan_desa->name;
            $new_penandatangan_desa->position = $penandatangan_desa->position;
            $new_penandatangan_desa->rank = $penandatangan_desa->rank;
            $signature_desa = 'penandatangan_desa_sktm_dtks' . Str::uuid() . '.png';
            Storage::copy('public/' . $penandatangan_desa->signature, 'signature/' . $signature_desa);
            $new_penandatangan_desa->signature = $signature_desa;
            $new_penandatangan_desa->save();

            $new_letter->penandatangan_desa_id = $new_penandatangan_desa->id;

            // Simpan surat baru
            $new_letter->save();

            // Simpan data pemohon dan keluarga terkait
            foreach ($request->residents as $resident) {
                $new_resident_sktm_dtks_letter = new ResidentSktmDtksLetter;
                $new_resident_sktm_dtks_letter->resident_id = $resident;
                $new_resident_sktm_dtks_letter->sktm_dtks_letter_id = $new_letter->id;
                $new_resident_sktm_dtks_letter->save();
            }

            DB::commit();

            $host = $_SERVER['SERVER_NAME'];
            if ($host == "127.0.0.1") {
                $host = $host . ":" . $_SERVER['SERVER_PORT'];
            }
            $message = "Notification \n-----------------------\n" . "Meminta Verifikasi Surat Keterangan Tidak Mampu Untuk DTKS\nNama Instansi : " . auth()->user()->name . "\nNomor Surat : " . $new_letter->get_reference_number_desa() . "\nLink Surat : http://" . $host . "/sktm-dtks?search=" . $new_letter->get_reference_number_desa();

            Whatsapp::send(auth()->user()->parent->whatsapp_number, $message);

            return response()->json("Surat SKTM DTKS berhasil dibuat", 201);
        } catch (Exception $e) {
            DB::rollBack();
            if ($e->getMessage() != "WHATSAPP_SEND_FAIL") {
                if (Storage::disk('public')->exists($kartu_keluarga)) {
                    Storage::disk('public')->delete($kartu_keluarga);
                }
                if (Storage::disk('public')->exists($surat_pengantar)) {
                    Storage::disk('public')->delete($surat_pengantar);
                }
                if (Storage::exists('signature/' . $signature_desa)) {
                    Storage::delete('signature/' . $signature_desa);
                }
            }
            if ($e->getMessage() == 'resident_not_found') {
                return response()->json("Satu atau lebih pemohon tidak ditemukan", 404);
            } elseif ($e->getMessage() == 'official_not_found') {
                return response()->json("Pejabat penandatangan tidak ditemukan", 404);
            } elseif ($e->getMessage() == 'WHATSAPP_SEND_FAIL') {
                return response()->json("Surat SKTM DTKS berhasil dibuat, tapi notifikasi whatsapp gagal dikirim", 201);
            } else {
                return response()->json($e->getMessage() . " In line " . $e->getLine(), 500);
            }
        }
    }

    public function update(Request $request, $id)
    {
        $request->residents = json_decode($request->residents);
        $request->penandatangan_kecamatan = json_decode($request->penandatangan_kecamatan);
        $request->penandatangan_desa = json_decode($request->penandatangan_desa);

        $validator = Validator::make($request->all(), [
            "residents" => "required",
            "dasar_1" => "required|string",
            "dasar_2" => "required|string",
            "surat_pengantar" => "present|nullable",
            "kartu_keluarga" => "present|nullable",
            "used_as" => "required|string",
            "penandatangan_desa_id" => "present|nullable",
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();
            return response()->json($errors, 500);
        }

        if ($request->surat_pengantar) {
            $validator = Validator::make($request->all(), [
                "surat_pengantar" => "mimes:pdf,jpg,jpeg,png",
            ]);

            if ($validator->fails()) {
                $errors = $validator->errors();
                return response()->json($errors, 500);
            }
        }

        if ($request->kartu_keluarga) {
            $validator = Validator::make($request->all(), [
                "kartu_keluarga" => "mimes:pdf,jpg,jpeg,png",
            ]);

            if ($validator->fails()) {
                $errors = $validator->errors();
                return response()->json($errors, 500);
            }
        }

        $surat_pengantar = null;
        $kartu_keluarga = null;
        $signature_desa_old = null;
        $signature_desa = null;

        DB::beginTransaction();
        try {

            //cek apakah surat ada
            $letter = ThisLetter::byUserId(auth()->id())->where('id', $id)->first();
            if (!$letter || !$letter->can_modified()) {
                return response()->json("Gagal memperbarui data. Surat SKTM DTKS tidak ditemukan.", 404);
            }
            // update surat
            $update_letter = $letter;
            $update_letter->dasar_1 = $request->dasar_1;
            $update_letter->dasar_2 = $request->dasar_2;
            $surat_pengantar_old = null;
            $kartu_keluarga_old = null;
            if ($request->surat_pengantar) {
                $surat_pengantar_old = $update_letter->surat_pengantar;
                $surat_pengantar = $request->file('surat_pengantar')->storePublicly('surat_pengantar', 'public');
                $update_letter->surat_pengantar = $surat_pengantar;
            }
            if ($request->kartu_keluarga) {
                $kartu_keluarga_old = $update_letter->kartu_keluarga;
                $kartu_keluarga = $request->file('kartu_keluarga')->storePublicly('kartu_keluarga', 'public');
                $update_letter->kartu_keluarga = $kartu_keluarga;
            }
            $update_letter->used_as = $request->used_as;

            // Cek data penduduk sebagai pemohon surat

            $residents_exist = Resident::whereIn('id', $request->residents)->count();

            if ($residents_exist < count($request->residents)) {
                throw new Exception("resident_not_found");
            }

            //penandatangan
            if ($request->penandatangan_desa_id) {
                $official = Official::find($request->penandatangan_desa_id);
                if (!$official) {
                    throw new Exception("official_not_found");
                }

                $penandatangan_desa = $update_letter->penandatangan_desa;
                $signature_desa_old = 'signature/' . $penandatangan_desa->signature;
                $penandatangan_desa->nip = $official->nip;
                $penandatangan_desa->name = $official->name;
                $penandatangan_desa->position = $official->position;
                $penandatangan_desa->rank = $official->rank;
                $signature_desa = 'penandatangan_desa_sktm_dtks' . Str::uuid() . '.png';
                Storage::copy('public/' . $official->signature, 'signature/' . $signature_desa);
                $penandatangan_desa->signature = $signature_desa;
                $penandatangan_desa->save();

                $update_letter->penandatangan_desa_id = $penandatangan_desa->id;
            }

            if ($update_letter->isDirty() && !$update_letter->verified_file) {
                if (Storage::exists($update_letter->verified_file)) {
                    Storage::delete($update_letter->verified_file);
                }
                $update_letter->verified_file = null;
            }

            // Simpan data pemohon dan keluarga terkait
            ResidentSktmDtksLetter::where('sktm_dtks_letter_id', $update_letter->id)->delete();
            foreach ($request->residents as $resident) {
                $new_resident_sktm_dtks_letter = new ResidentSktmDtksLetter;
                $new_resident_sktm_dtks_letter->resident_id = $resident;
                $new_resident_sktm_dtks_letter->sktm_dtks_letter_id = $update_letter->id;
                $new_resident_sktm_dtks_letter->save();
            }

            // Simpan surat baru
            $update_letter->save();

            DB::commit();

            //hapus file-file sebelumnya jika telah diganti (variable dibawah tidak null atau kosong)
            if (Storage::disk('public')->exists($surat_pengantar_old)) {
                Storage::disk('public')->delete($surat_pengantar_old);
            }
            if (Storage::disk('public')->exists($kartu_keluarga_old)) {
                Storage::disk('public')->delete($kartu_keluarga_old);
            }
            if (Storage::exists($signature_desa_old)) {
                Storage::delete($signature_desa_old);
            }

            $host = $_SERVER['SERVER_NAME'];
            if ($host == "127.0.0.1") {
                $host = $host . ":" . $_SERVER['SERVER_PORT'];
            }
            $message = "Notification \n-----------------------\n" . "Meminta Verifikasi Surat Keterangan Tidak Mampu Untuk DTKS\nNama Instansi : " . auth()->user()->name . "\nNomor Surat : " . $update_letter->get_reference_number_desa() . "\nLink Surat : http://" . $host . "/sktm-dtks?search=" . $update_letter->get_reference_number_desa();

            Whatsapp::send(auth()->user()->parent->whatsapp_number, $message);

            return response()->json("Surat SKTM DTKS berhasil diubah", 200);
        } catch (Exception $e) {
            //Kembalikan segala transaction ke database
            DB::rollBack();

            if ($e->getMessage() != "WHATSAPP_SEND_FAIL") {
                //Hapus file yang telah diupload jika ada
                if (Storage::disk('public')->exists($kartu_keluarga)) {
                    Storage::disk('public')->delete($kartu_keluarga);
                }
                if (Storage::disk('public')->exists($surat_pengantar)) {
                    Storage::disk('public')->delete($surat_pengantar);
                }
                if (Storage::exists($signature_desa)) {
                    Storage::delete($signature_desa);
                }
            }

            if ($e->getMessage() == 'resident_not_found') {
                return response()->json("Satu atau lebih pemohon tidak ditemukan", 404);
            } elseif ($e->getMessage() == 'official_not_found') {
                return response()->json("Pejabat penandatangan tidak ditemukan", 404);
            } elseif ($e->getMessage() == 'WHATSAPP_SEND_FAIL') {
                return response()->json("Surat SKTM DTKS berhasil diubah, tapi notifikasi whatsapp gagal dikirim", 200);
            } else {
                return response()->json($e->getMessage() . " In line " . $e->getLine(), 500);
            }
        }
    }

    // hanya bisa diakses kecamatan
    public function penandatangan(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            "penandatangan_kecamatan_id" => "present|nullable",
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();
            return response()->json($errors, 500);
        }

        $signature_kecamatan_old = null;
        $signature_kecamatan = null;

        DB::beginTransaction();
        try {

            //cek apakah surat ada
            $letter = ThisLetter::with('penandatangan_kecamatan')->byUserId(auth()->id())->where('id', $id)->first();
            if (!$letter || !$letter->can_verified()) {
                return response()->json("Gagal memperbarui data. Surat SKTM DTKS tidak ditemukan.", 404);
            }

            if ($request->penandatangan_kecamatan_id) {

                // update surat
                $update_letter = $letter;

                // penandatangan
                $official = Official::find($request->penandatangan_kecamatan_id);
                if (!$official) {
                    throw new Exception("official_not_found");
                }

                if ($update_letter->penandatangan_kecamatan_id == null) {
                    $letter_official = new LetterOfficial();
                } else {
                    $letter_official = $update_letter->penandatangan_kecamatan;
                    $signature_kecamatan_old = $letter_official->signature;
                }

                $letter_official->nip = $official->nip;
                $letter_official->name = $official->name;
                $letter_official->position = $official->position;
                $letter_official->rank = $official->rank;
                $signature_kecamatan = 'penandatangan_kecamatan_sktm_dtks_' . Str::uuid() . '.png';
                Storage::copy('public/' . $official->signature, 'signature/' . $signature_kecamatan);
                $letter_official->signature = $signature_kecamatan;
                $letter_official->save();

                $update_letter->penandatangan_kecamatan_id = $letter_official->id;

                $update_letter->save();

                DB::commit();
                if (Storage::exists('signature/' . $signature_kecamatan_old)) {
                    Storage::delete('signature/' . $signature_kecamatan_old);
                }
            }

            return response()->json($letter_official, 200);
        } catch (Exception $e) {
            DB::rollBack();
            if (Storage::exists('signature/' . $signature_kecamatan)) {
                Storage::delete('signature/' . $signature_kecamatan);
            }
            if ($e->getMessage() == 'official_not_found') {
                return response()->json("Pejabat penandatangan tidak ditemukan", 404);
            } else {
                return response()->json($e->getMessage() . " In line " . $e->getLine(), 500);
            }
        }
    }

    public function verification(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            "verified_file" => "required|mimes:pdf",
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 500);
        }

        $letter = ThisLetter::find($id);
        if (!$letter || !$letter->can_verified()) {
            return response()->json("Surat SKTM DTKS tidak ditemukan", 404);
        }

        if ($letter->is_verified()) {
            return response()->json("Surat SKTM DTKS telah di verifikasi", 200);
        }

        $verified_file = $request->file('verified_file')->store('verified_file');

        $letter->verified_file = $verified_file;

        $letter->save();

        $host = $_SERVER['SERVER_NAME'];
        if ($host == "127.0.0.1") {
            $host = $host . ":" . $_SERVER['SERVER_PORT'];
        }
        $message = "Notification \n-----------------------\n" . "Surat Keterangan Tidak Mampu Untuk DTKS Telah Di Verifikasi Oleh\nNama Instansi : " . auth()->user()->name . "\nNomor Surat : " . $letter->get_reference_number_desa() . "\nLink Surat : http://" . $host . "/sktm-dtks?search=" . $letter->get_reference_number_desa();

        Whatsapp::send($letter->user->whatsapp_number, $message);

        return response()->json("Surat SKTM DTKS berhasil di verifikasi", 200);
    }

    public function upload_file_arsip(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            "file_arsip" => "present|nullable",
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();
            return response()->json($errors, 500);
        }

        if ($request->file_arsip) {
            $validator = Validator::make($request->all(), [
                "file_arsip" => "mimes:zip,rar,jpg,jpeg,png,pdf",
            ]);

            if ($validator->fails()) {
                $errors = $validator->errors();
                return response()->json($errors, 500);
            }
        }

        $file_arsip = null;

        DB::beginTransaction();
        try {

            //cek apakah surat ada
            $letter = ThisLetter::byUserId(auth()->id())->where('id', $id)->first();
            if (!$letter || !$letter->can_modified() || !$letter->is_verified()) {
                return response()->json("Gagal upload file arsip data. Surat SKTM DTKS tidak ditemukan.", 404);
            }
            // update surat
            $update_letter = $letter;
            $file_arsip_old = null;
            if ($request->file_arsip) {
                $file_arsip_old = $update_letter->file_arsip;
                $file_arsip = $request->file('file_arsip')->storePublicly('file_arsip', 'public');
                $update_letter->file_arsip = $file_arsip;
            }

            // Simpan surat baru
            $update_letter->save();

            DB::commit();

            //hapus file-file sebelumnya jika telah diganti (variable dibawah tidak null atau kosong)
            if (Storage::disk('public')->exists($file_arsip_old)) {
                Storage::disk('public')->delete($file_arsip_old);
            }
            return response()->json("File arsip surat SKTM DTKS berhasil diupload", 200);
        } catch (Exception $e) {
            //Kembalikan segala transaction ke database
            DB::rollBack();

            //Hapus file yang telah diupload jika ada
            if (Storage::disk('public')->exists($file_arsip)) {
                Storage::disk('public')->delete($file_arsip);
            }
            return response()->json($e->getMessage() . " In line " . $e->getLine(), 500);
        }
    }

    public function destroy($id)
    {
        $letter = ThisLetter::byUserId(auth()->id())->where('id', $id)->first();
        if (!$letter || !$letter->can_modified()) {
            return response()->json("Gagal menghapus data. Surat SKTM DTKS tidak ditemukan.", 404);
        }

        try {
            $letter->delete();

            if (Storage::disk('public')->exists($letter->kartu_keluarga)) {
                Storage::disk('public')->delete($letter->kartu_keluarga);
            }
            if (Storage::disk('public')->exists($letter->surat_pengantar)) {
                Storage::disk('public')->delete($letter->surat_pengantar);
            }

            $penandatangan_kecamatan = LetterOfficial::find($letter->penandatangan_kecamatan_id);
            if ($penandatangan_kecamatan) {
                if (Storage::exists('signature/' . $penandatangan_kecamatan->signature)) {
                    Storage::delete('signature/' . $penandatangan_kecamatan->signature);
                }
                $penandatangan_kecamatan->delete();
            }

            $penandatangan_desa = LetterOfficial::find($letter->penandatangan_desa_id);
            if ($penandatangan_desa) {
                if (Storage::exists('signature/' . $penandatangan_desa->signature)) {
                    Storage::delete('signature/' . $penandatangan_desa->signature);
                }
                $penandatangan_desa->delete();
            }

            return response()->json("Surat SKTM DTKS berhasil dihapus", 200);
        } catch (Exception $e) {
            return response()->json("Surat SKTM DTKS gagal dihapus", 500);
        }
    }
}
