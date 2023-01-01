<?php

namespace App\Http\Controllers;

use App\Http\Resources\DamiuLetterCollection as ThisCollection;
use App\Models\DamiuLetter as ThisLetter;
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

class DamiuLetterController extends Controller
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
        $letters = ThisLetter::with(['resident', 'official'])->byUserId(auth()->id())
            ->where(function ($q) use ($request) {
                $q->where(DB::raw("CONCAT(prefix, damiu_letters.order, suffix)"), 'like', '%' . $request->search . '%')
                    ->orWhereHas('resident', function ($query) use ($request) {
                        return $query->where('nik', 'like', '%' . $request->search . '%')
                            ->orWhere('name', 'like', '%' . $request->search . '%');
                    })
                    ->orWhere('damiu_name', 'like', '%' . $request->search . '%')
                    ->orWhere('business', 'like', '%' . $request->search . '%');
            })->paginate(5);
        return new ThisCollection($letters);
    }

    //     private function convertDocToPDF($file){
    //         $domPdfPath = base_path('vendor/dompdf/dompdf');
    //         \PhpOffice\PhpWord\Settings::setPdfRendererPath($domPdfPath);
    //         \PhpOffice\PhpWord\Settings::setPdfRendererName('DomPDF'); 
    //         $Content = \PhpOffice\PhpWord\IOFactory::load(storage_path($file . ".docx")); 
    //         $PDFWriter = \PhpOffice\PhpWord\IOFactory::createWriter($Content,'PDF');
    //         $PDFWriter->save(storage_path($file . '.pdf'));
    //         Storage::delete(storage_path($file . ".docx"));
    //    }

    public function download($id)
    {
        $letter = ThisLetter::byUserId(auth()->id())->with(['resident', 'user', 'official'])->where('id', $id)->first();

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

        $templateProcessor = new TemplateProcessor(storage_path("app/template_word/DAMIU.docx"));

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

        // Kebutuhan data yang terkait dengan pemohon atau pembuat surat
        $templateProcessor->setValue('nik_pemohon', $letter->resident->nik);
        $templateProcessor->setValue('nama_pemohon', $letter->resident->name);
        $templateProcessor->setValue('alamat_pemohon', $letter->resident->address);

        // Kebutuhan data yang terkait dengan data surat
        $templateProcessor->setValue('nomor_surat', $letter->get_reference_number());
        $templateProcessor->setValue('tanggal_surat', Carbon::parse($letter->updated_at)->translatedFormat('j F Y'));
        $templateProcessor->setValue('nama_damiu', $letter->damiu_name);
        $templateProcessor->setValue('alamat_damiu', $letter->damiu_address);
        $templateProcessor->setValue('jenis_usaha', $letter->business);
        $templateProcessor->setValue('awal_berlaku', Carbon::parse($letter->updated_at)->translatedFormat('j F Y'));
        $templateProcessor->setValue('akhir_berlaku', Carbon::parse($letter->updated_at)->addYears($letter->validity_period)->translatedFormat('j F Y'));
        $templateProcessor->setValue('masa_berlaku', $letter->validity_period . " Tahun");

        // Kebutuhan data yang terkait dengan pejabat yang menandatangan
        $templateProcessor->setValue('nip_penandatangan', 'NIP. ' . $letter->official->nip);
        $templateProcessor->setValue('nama_penandatangan', $letter->official->name);
        $templateProcessor->setValue('jabatan_penandatangan', $letter->official->position);
        $templateProcessor->setValue('pangkat_penandatangan', $letter->official->rank);
        $templateProcessor->setImageValue('signature', [
            'path' => storage_path('app/signature/' . $letter->official->signature),
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
            "damiu_name" => "required|string",
            "damiu_address" => "required|string",
            "business" => "required|string",
            "resident_id" => "required",
            "validity_period" => "required|integer",
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

            $new_letter->damiu_name = $request->damiu_name;
            $new_letter->damiu_address = $request->damiu_address;
            $new_letter->business = $request->business;
            $new_letter->validity_period = $request->validity_period;
            $file_pendukung = $request->file('file_pendukung')->storePubliclyAs('file_pendukung', 'file_pendukung_iumk_' . $this->filter_file_name($new_letter->get_reference_number()) . "." . $request->file('file_pendukung')->extension(), 'public');
            $new_letter->file_pendukung = $file_pendukung;
            $new_letter->user_id = auth()->id();

            // Cek data penduduk sebagai pemohon surat
            $resident = Resident::find($request->resident_id);

            if (!$resident) {
                throw new Exception("resident_not_found");
            }

            $new_letter->resident_id = $resident->id;


            // Simpan surat baru
            $new_letter->save();

            DB::commit();

            $host = $_SERVER['SERVER_NAME'];
            if ($host == "127.0.0.1") {
                $host = $host . ":" . $_SERVER['SERVER_PORT'];
            }
            $message = "Notification \n-----------------------\n" . "Meminta Verifikasi Surat DAMIU\nNama Instansi : " . auth()->user()->name . "\nNomor Surat : " . $new_letter->get_reference_number() . "\nLink Surat : http://" . $host . "/damiu?search=" . $new_letter->get_reference_number();

            Whatsapp::send(auth()->user()->parent->whatsapp_number, $message);

            return response()->json("Surat DAMIU berhasil dibuat", 201);
        } catch (Exception $e) {
            DB::rollBack();
            if ($e->getMessage() != "WHATSAPP_SEND_FAIL") {
                if (Storage::disk('public')->exists($file_pendukung)) {
                    Storage::disk('public')->delete($file_pendukung);
                }
            }
            if ($e->getMessage() == 'resident_not_found') {
                return response()->json("Penduduk tidak ditemukan", 404);
            } elseif ($e->getMessage() == 'WHATSAPP_SEND_FAIL') {
                return response()->json("Surat DAMIU berhasil dibuat, tapi notifikasi whatsapp gagal dikirim", 200);
            } else {
                return response()->json($e->getMessage() . " In line " . $e->getLine(), 500);
            }
        }
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            "damiu_name" => "required|string",
            "damiu_address" => "required|string",
            "business" => "required|string",
            "resident_id" => "required",
            "validity_period" => "required|integer",
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
                return response()->json("Gagal memperbarui data. Surat DAMIU tidak ditemukan.", 404);
            }

            // Cek data penduduk sebagai pemohon surat
            $resident = Resident::find($request->resident_id);

            if (!$resident) {
                throw new Exception("resident_not_found");
            }

            // update surat
            $update_letter = $letter;
            $update_letter->damiu_name = $request->damiu_name;
            $update_letter->damiu_address = $request->damiu_address;
            $update_letter->business = $request->business;
            $update_letter->validity_period = $request->validity_period;
            $update_letter->resident_id = $resident->id;
            if ($request->file_pendukung) {
                $file_pendukung = $request->file('file_pendukung')->storePubliclyAs('file_pendukung', 'file_pendukung_iumk_' . $this->filter_file_name($update_letter->get_reference_number()) . "." . $request->file('file_pendukung')->extension(), 'public');
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
            $message = "Notification \n-----------------------\n" . "Meminta Verifikasi Surat DAMIU\nNama Instansi : " . auth()->user()->name . "\nNomor Surat : " . $update_letter->get_reference_number() . "\nLink Surat : http://" . $host . "/damiu?search=" . $update_letter->get_reference_number();

            Whatsapp::send(auth()->user()->parent->whatsapp_number, $message);

            return response()->json("Surat DAMIU berhasil diubah", 200);
        } catch (Exception $e) {
            DB::rollBack();
            if ($e->getMessage() != "WHATSAPP_SEND_FAIL") {
                if (Storage::disk('public')->exists($file_pendukung)) {
                    Storage::disk('public')->delete($file_pendukung);
                }
            }
            if ($e->getMessage() == 'resident_not_found') {
                return response()->json("Penduduk tidak ditemukan", 404);
            } elseif ($e->getMessage() == 'WHATSAPP_SEND_FAIL') {
                return response()->json("Surat DAMIU berhasil diubah, tapi notifikasi whatsapp gagal dikirim", 200);
            } else {
                return response()->json($e->getMessage() . " In line " . $e->getLine(), 500);
            }
        }
    }

    // hanya bisa diakses kecamatan
    public function penandatangan($id)
    {
        DB::beginTransaction();
        try {

            //cek apakah surat ada
            $letter = ThisLetter::with('official')->byUserId(auth()->id())->where('id', $id)->first();
            if (!$letter || !$letter->can_verified()) {
                return response()->json("Gagal memperbarui data. Surat DAMIU tidak ditemukan.", 404);
            }

            //cek apakah akun kecamatan telah memilih camat
            if (!auth()->user()->head_of_institution_id) {
                return response()->json("Institusi belum memilih camat", 422);
            }

            // update surat
            $update_letter = $letter;

            // penandatangan
            $official = Official::find(auth()->user()->head_of_institution_id);
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
            return response()->json("Surat DAMIU tidak ditemukan", 404);
        }

        if ($letter->is_verified()) {
            return response()->json("Surat DAMIU telah di verifikasi", 404);
        }

        $verified_file = $request->file('verified_file')->store('verified_file');

        $letter->verified_file = $verified_file;

        $letter->save();

        $host = $_SERVER['SERVER_NAME'];
        if ($host == "127.0.0.1") {
            $host = $host . ":" . $_SERVER['SERVER_PORT'];
        }
        $message = "Notification \n-----------------------\n" . "Surat DAMIU Telah Di Verifikasi Oleh\nNama Instansi : " . auth()->user()->name . "\nNomor Surat : " . $letter->get_reference_number() . "\nLink Surat : http://" . $host . "/damiu?search=" . $letter->get_reference_number();

        Whatsapp::send($letter->user->whatsapp_number, $message);

        return response()->json("Surat DAMIU berhasil di verifikasi", 200);
    }

    public function destroy($id)
    {
        $letter = ThisLetter::byUserId(auth()->id())->where('id', $id)->first();
        if (!$letter || !$letter->can_modified()) {
            return response()->json("Gagal menghapus data. Surat DAMIU tidak ditemukan.", 404);
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

            return response()->json("Surat DAMIU berhasil dihapus", 200);
        } catch (Exception $e) {
            return response()->json("Surat DAMIU gagal dihapus", 500);
        }
    }
}
