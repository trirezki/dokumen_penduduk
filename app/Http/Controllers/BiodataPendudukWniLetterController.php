<?php

namespace App\Http\Controllers;

use App\Http\Resources\BiodataPendudukWniLetterCollection as ThisCollection;
use App\Models\BiodataPendudukWniLetter as ThisLetter;
use App\Models\LetterOfficial;
use App\Models\Official;
use App\Models\ReferenceNumber;
use App\Models\Resident;
use App\Models\FamilyMember;
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

class BiodataPendudukWniLetterController extends Controller
{
    private $type;

    public function __construct()
    {
        $this->type = ThisLetter::$type;
    }

    public function reference_number()
    {
        $format = ReferenceNumber::get_reference_number($this->type, auth()->user()->parent->id);

        if (!$format) {
            return response()->json("Format surat tidak ditemukan", 404);
        }
        return response()->json($format, 200);
    }

    public function filter_file_name($name)
    {
        $filter = ["/", "."];
        $replace = str_replace($filter, "-", $name);
        return $replace;
    }

    public function index(Request $request)
    {
        $letters = ThisLetter::with(['penandatangan_kecamatan', 'penandatangan_desa', 'data_keluarga', 'data_keluarga.resident'])->byUserId(auth()->id())
            ->where(function ($q) use ($request) {
                $q->where(DB::raw("CONCAT(prefix, biodata_penduduk_wni_letters.order, suffix)"), 'like', '%' . $request->search . '%')
                    ->orWhereHas('kepala_keluarga.resident', function ($query) use ($request) {
                        return $query->where('nik', 'like', '%' . $request->search . '%')
                            ->orWhere('name', 'like', '%' . $request->search . '%');
                    });
            })->paginate(5);
        return new ThisCollection($letters);
    }

    public function download($id)
    {
        $letter = ThisLetter::byUserId(auth()->id())->with(['user', 'penandatangan_kecamatan', 'penandatangan_desa', 'kepala_keluarga', 'data_keluarga.resident'])->where('id', $id)->first();

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

        $templateProcessor = new TemplateProcessor(storage_path("app/template_word/BIODATA_PENDUDUK_WNI.docx"));

        // Kebutuhan data kepala keluarga
        $templateProcessor->setValue('nama_kepala_keluarga', $letter->kepala_keluarga->resident->name);
        $templateProcessor->setValue('alamat_kepala_keluarga', $letter->kepala_keluarga->resident->address);
        $templateProcessor->setValue('zip_code', $letter->zip_code ? $letter->zip_code : "-     ");
        $templateProcessor->setValue('phone', $letter->phone);
        $templateProcessor->setValue('rt', $letter->rt);
        $templateProcessor->setValue('rw', $letter->rw);
        $templateProcessor->setValue('jml', count($letter->data_keluarga) + 1 . " Orang");
        $templateProcessor->setValue('province', $letter->province);
        $templateProcessor->setValue('district', $letter->district);
        $templateProcessor->setValue('sub_district', $letter->sub_district);
        $templateProcessor->setValue('village', $letter->village);
        $templateProcessor->setValue('dusun', $letter->dusun);

        // Kebutuhan data keluarga
        $data_keluarga = $letter->data_keluarga;
        $kepala_keluarga = $letter->kepala_keluarga;
        $data_keluarga->prepend($kepala_keluarga);
        // $templateProcessor->setValue('ttl', $letter->resident_sktm_dtks_letters[0]->resident->place_of_birth . ", " . Carbon::parse($letter->resident_sktm_dtks_letters[0]->resident->date_of_birth)->translatedFormat('j - F - Y'));
        $templateProcessor->cloneRow('no_1', $data_keluarga->count());
        $templateProcessor->cloneRow('no_2', $data_keluarga->count());
        $data_keluarga->map(function ($q, $key) use ($templateProcessor) {
            $templateProcessor->setValue("no_1#" . $key + 1, $key + 1);
            $templateProcessor->setValue("name#" . $key + 1, $q->resident->name);
            $templateProcessor->setValue("nik#" . $key + 1, $q->resident->nik);
            $templateProcessor->setValue("address#" . $key + 1, $q->resident->address);
            $templateProcessor->setValue("paspor_number#" . $key + 1, $q->paspor_number);
            $templateProcessor->setValue("paspor_due_date#" . $key + 1, $q->paspor_due_date ? Carbon::parse($q->paspor_due_date)->format('d/m/Y') : '');
            $gender = $q->resident->gender == "laki-laki" ? "LAKI-LAKI" : "PEREMPUAN";
            $templateProcessor->setValue("gender#" . $key + 1, $gender);
            $templateProcessor->setValue("place_of_birth#" . $key + 1, $q->resident->place_of_birth);
            $templateProcessor->setValue("date_of_birth#" . $key + 1, $q->resident->date_of_birth ? Carbon::parse($q->resident->date_of_birth)->format('d/m/Y') : "");
            $templateProcessor->setValue("age#" . $key + 1, $q->resident->date_of_birth ? Carbon::parse($q->resident->date_of_birth)->age : "");
            $templateProcessor->setValue("have_birth_certificate#" . $key + 1 . ":15:15", "");
            if ($q->birth_certificate_number && $q->birth_certificate_number != "") {
                $templateProcessor->setImageValue("have_birth_certificate#" . $key + 1, [
                    'path' => storage_path('app/helper_icon/check.png'),
                    'ratio' => true,
                ]);
            }
            $templateProcessor->setValue("birth_certificate_number#" . $key + 1, $q->birth_certificate_number);
            $templateProcessor->setValue("blood_type#" . $key + 1, $q->blood_type);

            $templateProcessor->setValue("no_2#" . $key + 1, $key + 1);
            $templateProcessor->setValue("religion#" . $key + 1, $q->resident->religion);
            $marital_status = $q->resident->marital_status == "jejaka" || $q->resident->marital_status == "perawan" ? "Belum Kawin" : $q->resident->marital_status;
            $templateProcessor->setValue("marital_status#" . $key + 1, $marital_status);
            $templateProcessor->setValue("have_marriage_certificate#" . $key + 1 . ":15:15", "");
            if ($q->marriage_certificate_number && $q->marriage_certificate_number != "") {
                $templateProcessor->setImageValue("have_marriage_certificate#" . $key + 1, [
                    'path' => storage_path('app/helper_icon/check.png'),
                    'ratio' => true,
                ]);
            }
            $templateProcessor->setValue("marriage_certificate_number#" . $key + 1, $q->marriage_certificate_number);
            $templateProcessor->setValue("marriage_date#" . $key + 1, $q->marriage_date ? Carbon::parse($q->marriage_date)->format('d/m/Y') : "");
            $templateProcessor->setValue("have_divorce_certificate#" . $key + 1 . ":15:15", "");
            if ($q->divorce_certificate_number && $q->divorce_certificate_number != "") {
                $templateProcessor->setImageValue("have_divorce_certificate#" . $key + 1, [
                    'path' => storage_path('app/helper_icon/check.png'),
                    'ratio' => true,
                ]);
            }
            $templateProcessor->setValue("divorce_certificate_number#" . $key + 1, $q->divorce_certificate_number);
            $templateProcessor->setValue("divorce_date#" . $key + 1, $q->divorce_date ? Carbon::parse($q->divorce_date)->format('d/m/Y') : "");
            $templateProcessor->setValue("family_status#" . $key + 1, $q->family_status);
            $templateProcessor->setValue("have_physical_mental_disorders#" . $key + 1 . ":15:15", "");
            if ($q->disabilities && $q->disabilities != "") {
                $templateProcessor->setImageValue("have_physical_mental_disorders#" . $key + 1, [
                    'path' => storage_path('app/helper_icon/check.png'),
                    'ratio' => true,
                ]);
            }
            $templateProcessor->setValue("disabilities#" . $key + 1, $q->disabilities);
            $templateProcessor->setValue("last_study#" . $key + 1, $q->last_study);
            $templateProcessor->setValue("profession#" . $key + 1, $q->resident->profession);
            $templateProcessor->setValue("mother_nik#" . $key + 1, $q->mother_nik);
            $templateProcessor->setValue("mother_name#" . $key + 1, $q->mother_name);
            $templateProcessor->setValue("father_nik#" . $key + 1, $q->father_nik);
            $templateProcessor->setValue("father_name#" . $key + 1, $q->father_name);
        });

        // Kebutuhan data yang terkait dengan data surat
        $templateProcessor->setValue('nomor_surat', $letter->get_reference_number());
        $templateProcessor->setValue('tanggal_surat', Carbon::parse($letter->updated_at)->translatedFormat('j F Y'));
        $templateProcessor->setValue('rt_name', $letter->rt_name);
        $templateProcessor->setValue('rw_name', $letter->rw_name);

        // Kebutuhan data yang terkait dengan pejabat yang menandatangan
        $templateProcessor->setValue('penandatangan_kecamatan_nip', 'NIP. ' . $letter->penandatangan_kecamatan->nip);
        $templateProcessor->setValue('penandatangan_kecamatan_name', $letter->penandatangan_kecamatan->name);
        $templateProcessor->setImageValue('signature_kecamatan', [
            'path' => storage_path('app/signature/' . $letter->penandatangan_kecamatan->signature),
            'width' => 100,
            'height' => 100,
            'ratio' => true,
        ]);

        $templateProcessor->setValue('jabatan_penandatangan_desa', $letter->penandatangan_desa->position);
        $templateProcessor->setValue('penandatangan_desa_nip', 'NIP. ' . $letter->penandatangan_desa->nip);
        $templateProcessor->setValue('penandatangan_desa_name', $letter->penandatangan_desa->name);
        $templateProcessor->setImageValue('signature_desa', [
            'path' => storage_path('app/signature/' . $letter->penandatangan_desa->signature),
            'width' => 100,
            'height' => 100,
            'ratio' => true,
        ]);

        $fileNameServer = 'app/tmp/' . auth()->user()->id . '-' . md5(auth()->user()->id . uniqid() . time() . microtime()) . '.docx';
        $templateProcessor->saveAs(storage_path($fileNameServer));

        $filename = Str::replace("/", "-", $letter->get_reference_number()) . '.docx';
        return response()->download(storage_path($fileNameServer), $filename)->deleteFileAfterSend(true);
    }

    public function store(Request $request)
    {
        $request->merge([
            'kepala_keluarga' => json_decode($request->kepala_keluarga, true),
            'data_keluarga' => json_decode($request->data_keluarga, true),
        ]);
        $validator = Validator::make($request->all(), [
            "kepala_keluarga" => "required",
            "kepala_keluarga.id" => "required",
            "data_keluarga" => "present|nullable|array",
            "file_pendukung" => "required|mimes:zip,rar,jpg,jpeg,png,pdf",
            "penandatangan_desa_id" => "required",
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();
            return response()->json($errors, 500);
        }


        DB::beginTransaction();

        $file_pendukung = null;
        $signature_desa = null;

        try {

            // Cek data penduduk sebagai kepala keluarga
            if (!Resident::where('id', $request->kepala_keluarga["id"])->first()) {
                throw new Exception("kepala_keluarga_not_found");
            }

            // SET ID agar tidak auto increment
            $latest = ThisLetter::whereHas('user', function (Builder $query) {
                $query->where('parent_id', auth()->user()->parent->id);
            })->latest('order')->first();
            $latest_id = ($latest ? $latest->order : 0) + 1;

            // SET no surat berdasarkan format surat
            $format = ReferenceNumber::get_reference_number($this->type, auth()->id());

            $prefix = ReferenceNumber::parse_reference_number($format["prefix"]);
            $suffix = ReferenceNumber::parse_reference_number($format["suffix"]);

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

            $penandatangan_desa_id = $new_penandatangan_desa->id;

            // buat surat baru
            $request_surat_baru = $request->all();
            $request_surat_baru["order"] = $latest_id;
            $request_surat_baru["prefix"] = $prefix;
            $request_surat_baru["suffix"] = $suffix;
            $request_surat_baru["penandatangan_desa_id"] = $penandatangan_desa_id;
            $file_pendukung = $request->file('file_pendukung')->storePubliclyAs('file_pendukung', 'file_pendukung_biodata_penduduk_wni_' . $this->filter_file_name($prefix . $latest_id . $suffix) . "." . $request->file('file_pendukung')->extension(), 'public');
            $request_surat_baru["file_pendukung"] = $file_pendukung;
            $request_surat_baru["user_id"] = auth()->id();
            $new_letter = ThisLetter::create($request_surat_baru);

            // Simpan kepala keluarga
            $kepala_keluarga = $request->kepala_keluarga;
            $kepala_keluarga['resident_id'] = $kepala_keluarga["id"];
            $kepala_keluarga['head_of_family'] = true;
            $kepala_keluarga['family_status'] = "Kepala Keluarga";
            $kepala_keluarga['biodata_penduduk_wni_letter_id'] = $new_letter->id;
            FamilyMember::create($kepala_keluarga);

            // Simpan data keluarga
            if (is_array($request->data_keluarga)) {
                $data_keluarga = $request->data_keluarga;
                for ($i = 0; $i < count($data_keluarga); $i++) {
                    $data_keluarga[$i]['resident_id'] = $data_keluarga[$i]["id"];
                    $data_keluarga[$i]['biodata_penduduk_wni_letter_id'] = $new_letter->id;
                    FamilyMember::create($data_keluarga[$i]);
                };
            }

            DB::commit();

            $host = $_SERVER['SERVER_NAME'];
            if ($host == "127.0.0.1") {
                $host = $host . ":" . $_SERVER['SERVER_PORT'];
            }
            $message = "Notification \n-----------------------\n" . "Meminta Verifikasi Biodata Penduduk WNI\nNama Instansi : " . auth()->user()->name . "\nNomor Surat : " . $new_letter->get_reference_number() . "\nLink Surat : http://" . $host . "/biodata-penduduk-wni?search=" . $new_letter->get_reference_number();

            Whatsapp::send(auth()->user()->parent->whatsapp_number, $message);

            return response()->json("Biodata Penduduk WNI berhasil dibuat", 201);
        } catch (Exception $e) {
            DB::rollBack();
            if ($e->getMessage() != "WHATSAPP_SEND_FAIL") {
                if (Storage::disk('public')->exists($file_pendukung)) {
                    Storage::disk('public')->delete($file_pendukung);
                }
                if (Storage::exists('signature/' . $signature_desa)) {
                    Storage::delete('signature/' . $signature_desa);
                }
            }
            if ($e->getMessage() == 'kepala_keluarga_not_found') {
                return response()->json("Kepala keluarga tidak ditemukan", 404);
            } elseif ($e->getMessage() == 'official_not_found') {
                return response()->json("Pejabat penandatangan tidak ditemukan", 404);
            } elseif ($e->getMessage() == 'WHATSAPP_SEND_FAIL') {
                return response()->json("Biodata Penduduk WNI berhasil dibuat, tapi notifikasi whatsapp gagal dikirim", 201);
            } else {
                return response()->json($e->getMessage() . " In line " . $e->getLine(), 500);
            }
        }
    }

    public function update(Request $request, $id)
    {
        $request->merge([
            'kepala_keluarga' => json_decode($request->kepala_keluarga, true),
            'data_keluarga' => json_decode($request->data_keluarga, true),
        ]);
        $validator = Validator::make($request->all(), [
            "kepala_keluarga" => "required",
            "kepala_keluarga.id" => "required",
            "data_keluarga" => "present|nullable|array",
            "file_pendukung" => "present|nullable",
            "penandatangan_desa_id" => "present|nullable",
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();
            return response()->json($errors, 500);
        }

        if ($request->file_pendukung) {
            $validator = Validator::make($request->all(), [
                "file_pendukung" => "mimes:zip,rar,jpg,jpeg,png,pdf",
            ]);

            if ($validator->fails()) {
                $errors = $validator->errors();
                return response()->json($errors, 500);
            }
        }

        DB::beginTransaction();

        $file_pendukung = null;
        $signature_desa_old = null;
        $signature_desa = null;

        try {

            //cek apakah surat ada
            $letter = ThisLetter::byUserId(auth()->id())->where('id', $id)->first();
            if (!$letter || !$letter->can_modified()) {
                return response()->json("Gagal memperbarui data. Biodata Penduduk WNI tidak ditemukan.", 404);
            }

            // Cek data penduduk sebagai kepala keluarga
            if (!Resident::where('id', $request->kepala_keluarga["id"])->first()) {
                throw new Exception("kepala_keluarga_not_found");
            }

            // edit surat
            $update_letter = $letter;
            $request_edit_surat = $request->only([
                "rt", "rw", "rt_name", "rw_name", "zip_code", "phone", "province", "district", "sub_district", "village", "dusun"
            ]);
            if ($request->file_pendukung) {
                $file_pendukung = $request->file('file_pendukung')->storePubliclyAs('file_pendukung', 'file_pendukung_sktm_dtks_' . $this->filter_file_name($update_letter->get_reference_number()) . "." . $request->file('file_pendukung')->extension(), 'public');
                $request_edit_surat["file_pendukung"] = $file_pendukung;
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
                $signature_desa = 'penandatangan_desa_biodata_penduduk_wni' . Str::uuid() . '.png';
                Storage::copy('public/' . $official->signature, 'signature/' . $signature_desa);
                $penandatangan_desa->signature = $signature_desa;
                $penandatangan_desa->save();

                $request_edit_surat["penandatangan_desa_id"] = $penandatangan_desa->id;
            }

            $update_letter->update($request_edit_surat);

            // Simpan kepala keluarga
            $kepala_keluarga = collect($request->kepala_keluarga)->only([
                "id", "mother_nik", "mother_name", "father_nik", "father_name", "blood_type", "family_status", "last_study", "disabilities", "paspor_number", "paspor_due_date", "birth_certificate_number", "marriage_certificate_number", "marriage_date", "divorce_certificate_number", "divorce_date",
            ]);
            $kepala_keluarga['resident_id'] = $kepala_keluarga["id"];
            $kepala_keluarga['biodata_penduduk_wni_letter_id'] = $letter->id;
            FamilyMember::where('id', FamilyMember::where('biodata_penduduk_wni_letter_id', $letter->id)->where('head_of_family', 1)->first()->id)->update($kepala_keluarga->except('id')->toArray());

            // Simpan data keluarga
            FamilyMember::where('biodata_penduduk_wni_letter_id', $letter->id)->where('head_of_family', 0)->delete();
            if (is_array($request->data_keluarga)) {
                $data_keluarga = $request->data_keluarga;
                for ($i = 0; $i < count($data_keluarga); $i++) {
                    $data_keluarga[$i]['resident_id'] = $data_keluarga[$i]["id"];
                    $data_keluarga[$i]['biodata_penduduk_wni_letter_id'] = $letter->id;
                    FamilyMember::create($data_keluarga[$i]);
                };
            }

            if (!$update_letter->verified_file) {
                $update_letter->verified_file = null;
                $update_letter->save();
                if (Storage::exists($update_letter->verified_file)) {
                    Storage::delete($update_letter->verified_file);
                }
            }

            DB::commit();

            //hapus file-file sebelumnya jika telah diganti (variable dibawah tidak null atau kosong)
            if (Storage::exists($signature_desa_old)) {
                Storage::delete($signature_desa_old);
            }

            $host = $_SERVER['SERVER_NAME'];
            if ($host == "127.0.0.1") {
                $host = $host . ":" . $_SERVER['SERVER_PORT'];
            }
            $message = "Notification \n-----------------------\n" . "Meminta Verifikasi Biodata Penduduk WNI\nNama Instansi : " . auth()->user()->name . "\nNomor Surat : " . $update_letter->get_reference_number() . "\nLink Surat : http://" . $host . "/biodata-penduduk-wni?search=" . $update_letter->get_reference_number();

            Whatsapp::send(auth()->user()->parent->whatsapp_number, $message);

            return response()->json("Biodata Penduduk WNI berhasil diubah", 200);
        } catch (Exception $e) {
            //Kembalikan segala transaction ke database
            DB::rollBack();

            if ($e->getMessage() != "WHATSAPP_SEND_FAIL") {
                //Hapus file yang telah diupload jika ada
                if (Storage::disk('public')->exists($file_pendukung)) {
                    Storage::disk('public')->delete($file_pendukung);
                }
                if (Storage::exists($signature_desa)) {
                    Storage::delete($signature_desa);
                }
            }

            if ($e->getMessage() == 'kepala_keluarga_not_found') {
                return response()->json("Kepala keluarga tidak ditemukan", 404);
            } elseif ($e->getMessage() == 'official_not_found') {
                return response()->json("Pejabat penandatangan tidak ditemukan", 404);
            } elseif ($e->getMessage() == 'WHATSAPP_SEND_FAIL') {
                return response()->json("Biodata Penduduk WNI berhasil diubah, tapi notifikasi whatsapp gagal dikirim", 200);
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
                return response()->json("Gagal memperbarui data. Biodata Penduduk WNI tidak ditemukan.", 404);
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
                $signature_kecamatan = 'penandatangan_kecamatan_sktm_sekolah_' . Str::uuid() . '.png';
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
            return response()->json("Biodata Penduduk WNI tidak ditemukan", 404);
        }

        if ($letter->is_verified()) {
            return response()->json("Biodata Penduduk WNI telah di verifikasi", 200);
        }

        $verified_file = $request->file('verified_file')->store('verified_file');

        $letter->verified_file = $verified_file;

        $letter->save();

        $host = $_SERVER['SERVER_NAME'];
        if ($host == "127.0.0.1") {
            $host = $host . ":" . $_SERVER['SERVER_PORT'];
        }
        $message = "Notification \n-----------------------\n" . "Biodata Penduduk WNI Telah Di Verifikasi Oleh\nNama Instansi : " . auth()->user()->name . "\nNomor Surat : " . $letter->get_reference_number() . "\nLink Surat : http://" . $host . "/biodata-penduduk-wni?search=" . $letter->get_reference_number();

        Whatsapp::send($letter->user->whatsapp_number, $message);

        return response()->json("Biodata Penduduk WNI berhasil di verifikasi", 200);
    }

    public function destroy($id)
    {
        $letter = ThisLetter::byUserId(auth()->id())->where('id', $id)->first();
        if (!$letter || !$letter->can_modified()) {
            return response()->json("Gagal menghapus data. Biodata Penduduk WNI tidak ditemukan.", 404);
        }

        DB::beginTransaction();
        try {
            FamilyMember::where('biodata_penduduk_wni_letter_id', $letter->id)->delete();


            $letter->delete();

            if (Storage::disk('public')->exists($letter->file_pendukung)) {
                Storage::disk('public')->delete($letter->file_pendukung);
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

            DB::commit();
            return response()->json("Biodata Penduduk WNI berhasil dihapus", 200);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json("Biodata Penduduk WNI gagal dihapus " . $e->getMessage() . " " . $e->getLine(), 500);
        }
    }
}
