<?php

namespace App\Http\Controllers;

use App\Http\Resources\SkpwniLetterCollection as ThisCollection;
use App\Models\SkpwniLetter as ThisLetter;
use App\Models\LetterOfficial;
use App\Models\Official;
use App\Models\ReferenceNumber;
use App\Models\Resident;
use App\Models\FamilySkpwniLetter;
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

class SkpwniLetterController extends Controller
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
                $q->where(DB::raw("CONCAT(prefix, skpwni_letters.order, suffix)"), 'like', '%' . $request->search . '%')
                    ->orWhere(DB::raw("CONCAT(prefix_desa, skpwni_letters.order_desa, suffix_desa)"), 'like', '%' . $request->search . '%')
                    ->orWhereHas('kepala_keluarga', function ($query) use ($request) {
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

        $templateProcessor = new TemplateProcessor(storage_path("app/template_word/SKPWNI.docx"));

        // Kebutuhan data kepala keluarga
        $templateProcessor->setValue('kepala_keluarga_name', $letter->kepala_keluarga->name);
        $templateProcessor->setValue('kepala_keluarga_address', $letter->kepala_keluarga->address);

        // Kebutuhan data keluarga
        $data_keluarga = $letter->data_keluarga;
        $templateProcessor->cloneRow('no', $data_keluarga->count());
        $data_keluarga->map(function ($q, $key) use ($templateProcessor) {
            $templateProcessor->setValue("no#" . $key + 1, $key + 1);
            $templateProcessor->setValue("name#" . $key + 1, $q->resident->name);
            $templateProcessor->setValue("nik#" . $key + 1, $q->resident->nik);
            $templateProcessor->setValue("shdk#" . $key + 1, $q->shdk);
        });

        // Kebutuhan data yang terkait dengan data surat
        $templateProcessor->setValue('family_card_number', $letter->family_card_number);
        $templateProcessor->setValue('rt', $letter->rt);
        $templateProcessor->setValue('rw', $letter->rw);
        $templateProcessor->setValue('village', $letter->village);
        $templateProcessor->setValue('sub_district', $letter->sub_district);
        $templateProcessor->setValue('district', $letter->district);
        $templateProcessor->setValue('province', $letter->province);
        $templateProcessor->setValue('zip_code', $letter->zip_code);
        $templateProcessor->setValue('phone', $letter->phone);
        $templateProcessor->setValue('reason_to_move', $letter->reason_to_move);
        $templateProcessor->setValue('moving_destination', $letter->moving_destination);
        $templateProcessor->setValue('moving_destination_rt', $letter->moving_destination_rt);
        $templateProcessor->setValue('moving_destination_rw', $letter->moving_destination_rw);
        $templateProcessor->setValue('moving_destination_village', $letter->moving_destination_village);
        $templateProcessor->setValue('moving_destination_sub_district', $letter->moving_destination_sub_district);
        $templateProcessor->setValue('moving_destination_district', $letter->moving_destination_district);
        $templateProcessor->setValue('moving_destination_province', $letter->moving_destination_province);
        $templateProcessor->setValue('moving_destination_zip_code', $letter->moving_destination_zip_code);
        $templateProcessor->setValue('move_classification', $letter->move_classification);
        $templateProcessor->setValue('type_of_move', $letter->type_of_move);
        $templateProcessor->setValue('status_not_move', $letter->status_not_move);
        $templateProcessor->setValue('status_move', $letter->status_move);
        $templateProcessor->setValue('moving_date_plan', $letter->moving_date_plan);
        $templateProcessor->setValue('nomor_surat_kecamatan', $letter->get_reference_number());
        $templateProcessor->setValue('tanggal_surat', Carbon::parse($letter->updated_at)->translatedFormat('j F Y'));
        $templateProcessor->setValue('nomor_surat', $letter->get_reference_number_desa());
        $templateProcessor->setValue('pimpinan_kecamatan', "Camat " . $letter->user->parent->sub_district);
        $templateProcessor->setValue('pimpinan_desa', $letter->user->leader() . " " . $letter->user->village);

        // Kebutuhan data yang terkait dengan pejabat yang menandatangan
        $templateProcessor->setValue('penandatangan_kecamatan_nip', 'NIP. ' . $letter->penandatangan_kecamatan->nip);
        $templateProcessor->setValue('penandatangan_kecamatan_name', $letter->penandatangan_kecamatan->name);
        $templateProcessor->setImageValue('signature_kecamatan', [
            'path' => storage_path('app/signature/' . $letter->penandatangan_kecamatan->signature),
            'width' => 100,
            'height' => 100,
            'ratio' => true,
        ]);

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

            // SET ORDER agar tidak auto increment
            $latest = ThisLetter::whereHas('user', function (Builder $query) {
                $query->where('parent_id', auth()->user()->parent->id);
            })->latest('order')->first();
            $order = ($latest ? $latest->order : 0) + 1;

            $latest_desa = ThisLetter::whereHas('user', function (Builder $query) {
                $query->where('id', auth()->id());
            })->latest('order_desa')->first();
            $order_desa = ($latest_desa ? $latest_desa->order_desa : 0) + 1;

            // SET no surat berdasarkan format surat
            $format = ReferenceNumber::get_reference_number($this->type, auth()->user()->parent_id);

            $prefix = ReferenceNumber::parse_reference_number($format["prefix"]);
            $suffix = ReferenceNumber::parse_reference_number($format["suffix"]);

            $format_desa = ReferenceNumber::get_reference_number($this->type, auth()->id());

            $prefix_desa = ReferenceNumber::parse_reference_number($format_desa["prefix"]);
            $suffix_desa = ReferenceNumber::parse_reference_number($format_desa["suffix"]);

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
            $request_surat_baru["order"] = $order;
            $request_surat_baru["order_desa"] = $order_desa;
            $request_surat_baru["prefix"] = $prefix;
            $request_surat_baru["suffix"] = $suffix;
            $request_surat_baru["prefix_desa"] = $prefix_desa;
            $request_surat_baru["suffix_desa"] = $suffix_desa;
            $request_surat_baru["kepala_keluarga_id"] = $request->kepala_keluarga["id"];
            $file_pendukung = $request->file('file_pendukung')->storePubliclyAs('file_pendukung', 'file_pendukung_biodata_penduduk_wni_' . $this->filter_file_name($prefix_desa . $order_desa . $suffix_desa) . "." . $request->file('file_pendukung')->extension(), 'public');
            $request_surat_baru["file_pendukung"] = $file_pendukung;
            $request_surat_baru["penandatangan_desa_id"] = $penandatangan_desa_id;
            $request_surat_baru["user_id"] = auth()->id();
            $new_letter = ThisLetter::create($request_surat_baru);

            // Simpan data keluarga
            if (is_array($request->data_keluarga)) {
                $data_keluarga = $request->data_keluarga;
                for ($i = 0; $i < count($data_keluarga); $i++) {
                    $data_keluarga[$i]['resident_id'] = $data_keluarga[$i]["id"];
                    $data_keluarga[$i]['skpwni_letter_id'] = $new_letter->id;
                    FamilySkpwniLetter::create($data_keluarga[$i]);
                };
            }

            DB::commit();

            $host = $_SERVER['SERVER_NAME'];
            if ($host == "127.0.0.1") {
                $host = $host . ":" . $_SERVER['SERVER_PORT'];
            }
            $message = "Notification \n-----------------------\n" . "Meminta Verifikasi Surat Keterangan Pindah\nNama Instansi : " . auth()->user()->name . "\nNomor Surat : " . $new_letter->get_reference_number_desa() . "\nLink Surat : http://" . $host . "/skpwni?search=" . $new_letter->get_reference_number_desa();

            Whatsapp::send(auth()->user()->parent->whatsapp_number, $message);

            return response()->json("SKPWNI berhasil dibuat", 201);
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
                return response()->json("SKPWNI berhasil dibuat, tapi notifikasi whatsapp gagal dikirim", 201);
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
                return response()->json("Gagal memperbarui data. SKPWNI tidak ditemukan.", 404);
            }

            // Cek data penduduk sebagai kepala keluarga
            if (!Resident::where('id', $request->kepala_keluarga["id"])->first()) {
                throw new Exception("kepala_keluarga_not_found");
            }

            // edit surat
            $update_letter = $letter;
            $request_edit_surat = $request->only([
                "family_card_number", "rt", "rw", "village", "sub_district", "district", "province", "zip_code",
                "phone", "reason_to_move", "moving_destination", "moving_destination_rt", "moving_destination_rw",
                "moving_destination_village", "moving_destination_sub_district", "moving_destination_district",
                "moving_destination_province", "moving_destination_zip_code", "moving_destination_phone",
                "move_classification", "type_of_move", "status_not_move", "status_move", "moving_date_plan",
            ]);
            if ($request->file_pendukung) {
                $file_pendukung = $request->file('file_pendukung')->storePubliclyAs('file_pendukung', 'file_pendukung_sktm_dtks_' . $this->filter_file_name($update_letter->get_reference_number()) . "." . $request->file('file_pendukung')->extension(), 'public');
                $request_edit_surat["file_pendukung"] = $file_pendukung;
            }
            $request_edit_surat["kepala_keluarga_id"] = $request->kepala_keluarga["id"];

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

            // Simpan data keluarga
            FamilySkpwniLetter::where('skpwni_letter_id', $letter->id)->delete();
            if (is_array($request->data_keluarga)) {
                $data_keluarga = $request->data_keluarga;
                for ($i = 0; $i < count($data_keluarga); $i++) {
                    $data_keluarga[$i]['resident_id'] = $data_keluarga[$i]["id"];
                    $data_keluarga[$i]['skpwni_letter_id'] = $letter->id;
                    FamilySkpwniLetter::create($data_keluarga[$i]);
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
            $message = "Notification \n-----------------------\n" . "Meminta Verifikasi Surat Keterangan Pindah\nNama Instansi : " . auth()->user()->name . "\nNomor Surat : " . $update_letter->get_reference_number_desa() . "\nLink Surat : http://" . $host . "/skpwni?search=" . $update_letter->get_reference_number_desa();

            Whatsapp::send(auth()->user()->parent->whatsapp_number, $message);

            return response()->json("SKPWNI berhasil diubah", 200);
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
                return response()->json("SKPWNI berhasil diubah, tapi notifikasi whatsapp gagal dikirim", 200);
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
                return response()->json("Gagal memperbarui data. Surat Keterangan Pindah WNI tidak ditemukan.", 404);
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
            return response()->json("SKPWNI tidak ditemukan", 404);
        }

        if ($letter->is_verified()) {
            return response()->json("SKPWNI telah di verifikasi", 200);
        }

        $verified_file = $request->file('verified_file')->store('verified_file');

        $letter->verified_file = $verified_file;

        $letter->save();

        $host = $_SERVER['SERVER_NAME'];
        if ($host == "127.0.0.1") {
            $host = $host . ":" . $_SERVER['SERVER_PORT'];
        }
        $message = "Notification \n-----------------------\n" . "Surat Keterangan Pindah Telah Di Verifikasi Oleh\nNama Instansi : " . auth()->user()->name . "\nNomor Surat : " . $letter->get_reference_number_desa() . "\nLink Surat : http://" . $host . "/skpwni?search=" . $letter->get_reference_number_desa();

        Whatsapp::send($letter->user->whatsapp_number, $message);

        return response()->json("SKPWNI berhasil di verifikasi", 200);
    }

    public function destroy($id)
    {
        $letter = ThisLetter::byUserId(auth()->id())->where('id', $id)->first();
        if (!$letter || !$letter->can_modified()) {
            return response()->json("Gagal menghapus data. SKPWNI tidak ditemukan.", 404);
        }

        DB::beginTransaction();
        try {
            FamilySkpwniLetter::where('skpwni_letter_id', $letter->id)->delete();

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
            return response()->json("SKPWNI berhasil dihapus", 200);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json($e->getMessage() . " In line " . $e->getLine(), 500);
            return response()->json("SKPWNI gagal dihapus", 500);
        }
    }
}
