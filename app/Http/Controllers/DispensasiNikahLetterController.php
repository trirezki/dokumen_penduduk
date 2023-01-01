<?php

namespace App\Http\Controllers;

use App\Http\Resources\DispensasiNikahLetterCollection as ThisCollection;
use App\Models\DispensasiNikahLetter as ThisLetter;
use App\Models\LetterOfficial;
use App\Models\Official;
use App\Models\ReferenceNumber;
use App\Models\Resident;
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

class DispensasiNikahLetterController extends Controller
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
        $letters = ThisLetter::with(['pemohon_laki_laki', 'pemohon_perempuan', 'official'])->byUserId(auth()->id())
            ->where(function ($q) use ($request) {
                $q->where(DB::raw("CONCAT(prefix, dispensasi_nikah_letters.order, suffix)"), 'like', '%' . $request->search . '%')
                    ->orWhereHas('pemohon_laki_laki', function ($query) use ($request) {
                        return $query->where('nik', 'like', '%' . $request->search . '%')
                            ->orWhere('name', 'like', '%' . $request->search . '%');
                    })
                    ->orWhereHas('pemohon_perempuan', function ($query) use ($request) {
                        return $query->where('nik', 'like', '%' . $request->search . '%')
                            ->orWhere('name', 'like', '%' . $request->search . '%');
                    });
            })->paginate(5);
        return new ThisCollection($letters);
    }

    public function download($id)
    {
        $letter = ThisLetter::byUserId(auth()->id())->with(['pemohon_laki_laki', 'pemohon_perempuan', 'user', 'official'])->where('id', $id)->first();
        if (!$letter) {
            return response()->json("Surat tidak ditemukan", 404);
        }

        if (!$letter->official_id) {
            return response()->json("Surat belum di tanda tangani !", 422);
        }

        if ($letter->is_verified()) {
            return response()->download(storage_path("app/" . $letter->verified_file), str_replace("/", "_", $letter->get_reference_number()) . ".pdf");
        }

        // Cek apakah user mempunyai akses untuk memverifikasi surat ini
        if (!$letter->can_verified()) {
            return response()->json("Surat tidak ditemukan", 404);
        }

        $templateProcessor = new TemplateProcessor(storage_path("app/template_word/DISPENSASI_NIKAH.docx"));

        // Kebutuhan data dari table user dan instansi
        $templateProcessor->setValue('nama_instansi', Str::upper($letter->user->parent->name));
        $templateProcessor->setValue('email', $letter->user->parent->email);
        $templateProcessor->setValue('provinsi', $letter->user->parent->province);
        $templateProcessor->setValue('kabupaten_upper', Str::upper($letter->user->parent->district));
        $templateProcessor->setValue('kecamatan_upper', Str::upper($letter->user->parent->sub_district));
        $templateProcessor->setValue('kabupaten', $letter->user->parent->district);
        $templateProcessor->setValue('kecamatan', $letter->user->parent->sub_district);
        $templateProcessor->setValue('desa_kop', $letter->user->parent->kop_village());
        $templateProcessor->setValue('desa', $letter->user->parent->village);
        $templateProcessor->setValue('jalan', $letter->user->parent->street);
        $templateProcessor->setValue('kode_pos', $letter->user->parent->zip_code);

        // Kebutuhan data yang terkait dengan pemohon laki laki atau pembuat surat  
        $templateProcessor->setValue('nama_pemohon_laki_laki', $letter->pemohon_laki_laki->name);
        $templateProcessor->setValue('ttl_pemohon_laki_laki', $letter->pemohon_laki_laki->place_of_birth . ", " . Carbon::parse($letter->pemohon_laki_laki->date_of_birth)->translatedFormat('j F Y'));
        $templateProcessor->setValue('pekerjaan_pemohon_laki_laki', $letter->pemohon_laki_laki->profession);
        $templateProcessor->setValue('status_pemohon_laki_laki', $letter->pemohon_laki_laki->marital_status);
        $templateProcessor->setValue('alamat_pemohon_laki_laki', $letter->pemohon_laki_laki->address);

        // Kebutuhan data yang terkait dengan pemohon perempuan atau pembuat surat
        $templateProcessor->setValue('nama_pemohon_perempuan', $letter->pemohon_perempuan->name);
        $templateProcessor->setValue('ttl_pemohon_perempuan', $letter->pemohon_perempuan->place_of_birth . ", " . Carbon::parse($letter->pemohon_perempuan->date_of_birth)->translatedFormat('j F Y'));
        $templateProcessor->setValue('pekerjaan_pemohon_perempuan', $letter->pemohon_perempuan->profession);
        $templateProcessor->setValue('status_pemohon_perempuan', $letter->pemohon_perempuan->marital_status);
        $templateProcessor->setValue('alamat_pemohon_perempuan', $letter->pemohon_perempuan->address);

        // Kebutuhan data yang terkait dengan data surat
        $templateProcessor->setValue('nomor_surat', $letter->get_reference_number());
        $templateProcessor->setValue('tanggal_surat', Carbon::parse($letter->updated_at)->translatedFormat('j F Y'));

        // Kebutuhan data yang terkait dengan pejabat yang menandatangan
        $templateProcessor->setValue('nip_penandatangan', 'NIP. ' . $letter->official->nip);
        $templateProcessor->setValue('nama_penandatangan', $letter->official->name);
        $templateProcessor->setValue('jabatan_penandatangan', $letter->official->position);
        $templateProcessor->setValue('pangkat_penandatangan', $letter->official->rank);
        $templateProcessor->setImageValue('signature', [
            'path' => storage_path('app/signature/' . $letter->official->signature),
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
        $validator = Validator::make($request->all(), [
            "pemohon_laki_laki_id" => "required",
            "pemohon_perempuan_id" => "required",
            "file_pendukung" => "required|mimes:zip,rar,jpg,jpeg,png,pdf",
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();
            return response()->json($errors, 500);
        }

        DB::beginTransaction();

        $file_pendukung = null;

        try {
            // buat surat baru
            $new_letter = new ThisLetter();

            // SET ID agar tidak auto increment
            $latest = ThisLetter::whereHas('user', function (Builder $query) {
                $query->where('parent_id', auth()->user()->parent->id);
            })->latest('order')->first();
            $new_letter->order = ($latest ? $latest->order : 0) + 1;

            // SET no surat berdasarkan format surat
            $format = ReferenceNumber::get_reference_number($this->type, auth()->user()->parent->id);

            $new_letter->prefix = ReferenceNumber::parse_reference_number($format["prefix"]);
            $new_letter->suffix = ReferenceNumber::parse_reference_number($format["suffix"]);

            $file_pendukung = $request->file('file_pendukung')->storePubliclyAs('file_pendukung', 'file_pendukung_dispensasi_nikah_' . $this->filter_file_name($new_letter->get_reference_number()) . "." . $request->file('file_pendukung')->extension(), 'public');
            $new_letter->file_pendukung = $file_pendukung;
            $new_letter->user_id = auth()->id();

            // Cek data penduduk sebagai pemohon surat
            $pemohon_laki_laki = Resident::find($request->pemohon_laki_laki_id);

            if (!$pemohon_laki_laki) {
                throw new Exception("pemohon_laki_laki_not_found");
            }

            // Cek data penduduk sebagai pemohon surat
            $pemohon_perempuan = Resident::find($request->pemohon_perempuan_id);

            if (!$pemohon_perempuan) {
                throw new Exception("pemohon_perempuan_not_found");
            }

            $new_letter->pemohon_laki_laki_id = $pemohon_laki_laki->id;
            $new_letter->pemohon_perempuan_id = $pemohon_perempuan->id;


            // Simpan surat baru
            $new_letter->save();

            DB::commit();

            $host = $_SERVER['SERVER_NAME'];
            if ($host == "127.0.0.1") {
                $host = $host . ":" . $_SERVER['SERVER_PORT'];
            }
            $message = "Notification \n-----------------------\n" . "Meminta Verifikasi Surat Dispensasi Nikah\nNama Instansi : " . auth()->user()->name . "\nNomor Surat : " . $new_letter->get_reference_number() . "\nLink Surat : http://" . $host . "/dispensasi-nikah?search=" . $new_letter->get_reference_number();

            Whatsapp::send(auth()->user()->parent->whatsapp_number, $message);

            return response()->json("Surat Dispensasi Nikah berhasil dibuat", 201);
        } catch (Exception $e) {
            DB::rollBack();
            if ($e->getMessage() != "WHATSAPP_SEND_FAIL") {
                if (Storage::disk('public')->exists($file_pendukung)) {
                    Storage::disk('public')->delete($file_pendukung);
                }
                return response()->json("Surat SKTM untuk sekolah berhasil dibuat, tapi notifikasi whatsapp gagal dikirim", 201);
            }
            if ($e->getMessage() == 'pemohon_laki_laki_not_found') {
                return response()->json("Pemohon laki-laki tidak ditemukan", 404);
            } else if ($e->getMessage() == 'pemohon_perempuan_not_found') {
                return response()->json("Pemohon perempuan tidak ditemukan", 404);
            } elseif ($e->getMessage() == 'WHATSAPP_SEND_FAIL') {
                return response()->json("Surat Dispensasi Nikah berhasil dibuat, tapi notifikasi whatsapp gagal dikirim", 201);
            } else {
                return response()->json($e->getMessage() . " In line " . $e->getLine(), 500);
            }
        }
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            "pemohon_laki_laki_id" => "required",
            "pemohon_perempuan_id" => "required",
            "file_pendukung" => "present|nullable",
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

        try {

            //cek apakah surat ada
            $letter = ThisLetter::byUserId(auth()->id())->where('id', $id)->first();
            if (!$letter || !$letter->can_modified()) {
                return response()->json("Gagal memperbarui data. Surat Dispensasi Nikah tidak ditemukan.", 404);
            }
            // update surat
            $update_letter = $letter;

            // Cek data penduduk sebagai pemohon surat
            $pemohon_laki_laki = Resident::find($request->pemohon_laki_laki_id);

            if (!$pemohon_laki_laki) {
                throw new Exception("pemohon_laki_laki_not_found");
            }

            // Cek data penduduk sebagai pemohon surat
            $pemohon_perempuan = Resident::find($request->pemohon_perempuan_id);

            if (!$pemohon_perempuan) {
                throw new Exception("pemohon_perempuan_not_found");
            }

            $update_letter->pemohon_laki_laki = $pemohon_laki_laki->id;
            $update_letter->pemohon_perempuan = $pemohon_perempuan->id;

            if ($request->file_pendukung) {
                $file_pendukung = $request->file('file_pendukung')->storePubliclyAs('file_pendukung', 'file_pendukung_dispensasi_nikah_' . $this->filter_file_name($update_letter->get_reference_number()) . "." . $request->file('file_pendukung')->extension(), 'public');
                $update_letter->file_pendukung = $file_pendukung;
            }

            if ($update_letter->isDirty() && !$update_letter->verified_file) {
                if (Storage::exists($update_letter->verified_file)) {
                    Storage::delete($update_letter->verified_file);
                }
                $update_letter->verified_file = null;
            }

            // Simpan surat baru
            $update_letter->save();

            DB::commit();

            $host = $_SERVER['SERVER_NAME'];
            if ($host == "127.0.0.1") {
                $host = $host . ":" . $_SERVER['SERVER_PORT'];
            }
            $message = "Notification \n-----------------------\n" . "Meminta Verifikasi Surat Dispensasi Nikah\nNama Instansi : " . auth()->user()->name . "\nNomor Surat : " . $update_letter->get_reference_number() . "\nLink Surat : http://" . $host . "/dispensasi-nikah?search=" . $update_letter->get_reference_number();

            Whatsapp::send(auth()->user()->parent->whatsapp_number, $message);

            return response()->json("Surat Dispensasi Nikah berhasil diubah", 200);
        } catch (Exception $e) {
            DB::rollBack();
            if ($e->getMessage() != "WHATSAPP_SEND_FAIL") {
                if (Storage::disk('public')->exists($file_pendukung)) {
                    Storage::disk('public')->delete($file_pendukung);
                }
            }
            if ($e->getMessage() == 'pemohon_laki_laki_not_found') {
                return response()->json("Pemohon laki-laki tidak ditemukan", 404);
            } else if ($e->getMessage() == 'pemohon_perempuan_not_found') {
                return response()->json("Pemohon perempuan tidak ditemukan", 404);
            } elseif ($e->getMessage() == 'WHATSAPP_SEND_FAIL') {
                return response()->json("Surat Dispensasi Nikah berhasil diubah, tapi notifikasi whatsapp gagal dikirim", 200);
            } else {
                return response()->json($e->getMessage() . " In line " . $e->getLine(), 500);
            }
        }
    }

    // hanya bisa diakses kecamatan
    public function penandatangan(Request $request, $id)
    {
        DB::beginTransaction();
        try {

            //cek apakah surat ada
            $letter = ThisLetter::with('official')->byUserId(auth()->id())->where('id', $id)->first();
            if (!$letter || !$letter->can_verified()) {
                return response()->json("Gagal memperbarui data. Surat DISPENSASI NIKAH tidak ditemukan.", 404);
            }

            $validator = Validator::make($request->all(), [
                "official_id" => "required",
            ]);

            if ($validator->fails()) {
                return response()->json($validator->errors(), 422);
            }

            // update surat
            $update_letter = $letter;

            // penandatangan
            $official = Official::find($request->official_id);
            if (!$official) {
                DB::rollBack();
                return response()->json("Pejabat penandatangan tidak ditemukan", 404);
            }

            if ($update_letter->official_id == null) {
                $letter_official = new LetterOfficial();
            } else {
                $letter_official = $update_letter->official;
                if ($letter_official && Storage::exists('signature/' . $letter_official->signature)) {
                    Storage::delete('signature/' . $letter_official->signature);
                }
            }

            $letter_official->nip = $official->nip;
            $letter_official->name = $official->name;
            $letter_official->position = $official->position;
            $letter_official->rank = $official->rank;
            $new_filename = Str::uuid() . '.png';
            Storage::copy('public/' . $official->signature, 'signature/' . $new_filename);
            $letter_official->signature = $new_filename;
            $letter_official->save();

            $update_letter->official_id = $letter_official->id;

            $update_letter->save();

            DB::commit();
            return response()->json($letter_official, 200);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json($e, 500);
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
            return response()->json("Surat Dispensasi Nikah tidak ditemukan", 404);
        }

        if ($letter->is_verified()) {
            return response()->json("Surat Dispensasi Nikah telah di verifikasi", 404);
        }

        $verified_file = $request->file('verified_file')->store('verified_file');

        $letter->verified_file = $verified_file;

        $letter->save();

        $host = $_SERVER['SERVER_NAME'];
        if ($host == "127.0.0.1") {
            $host = $host . ":" . $_SERVER['SERVER_PORT'];
        }
        $message = "Notification \n-----------------------\n" . "Surat Dispensasi Nikah Telah Di Verifikasi Oleh\nNama Instansi : " . auth()->user()->name . "\nNomor Surat : " . $letter->get_reference_number() . "\nLink Surat : http://" . $host . "/dispensasi-nikah?search=" . $letter->get_reference_number();

        Whatsapp::send($letter->user->whatsapp_number, $message);

        return response()->json("Surat Dispensasi Nikah berhasil di verifikasi", 200);
    }

    public function destroy($id)
    {
        $letter = ThisLetter::byUserId(auth()->id())->where('id', $id)->first();
        if (!$letter || !$letter->can_modified()) {
            return response()->json("Gagal menghapus data. Surat Dispensasi Nikah tidak ditemukan.", 404);
        }

        try {
            $letter->delete();

            if (Storage::disk('public')->exists($letter->file_pendukung)) {
                Storage::disk('public')->delete($letter->file_pendukung);
            }

            $official = LetterOfficial::find($letter->official_id);
            if ($official) {
                if (Storage::exists('signature/' . $official->signature)) {
                    Storage::delete('signature/' . $official->signature);
                }
                $official->delete();
            }

            return response()->json("Surat Dispensasi Nikah berhasil dihapus", 200);
        } catch (Exception $e) {
            return response()->json("Surat Dispensasi Nikah gagal dihapus", 500);
        }
    }
}
