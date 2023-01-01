<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class InstitutionController extends Controller
{
    public function index()
    {
        $user = User::with('head_of_institution')->find(auth()->id());
        return response()->json($user, 200);
    }

    // public function preview_kop(){
    //     $institution_id = auth()->user()->institution_id;
    //     $institution = Institution::find($institution_id);
    //     // return Storage::path($institution->logo);
    //     return $institution->logo;
    //     // $template = new \PhpOffice\PhpWord\TemplateProcessor(Storage::path('template_word/kop_surat.docx'));
    //     // $template->setImageValue('logo', Storage::path());
    // }

    public function update_head_of_institution(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "id" => "required|exists:officials"
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $user = auth()->user();

        if (!$user) {
            return response()->json("Instansi tidak ditemukan !", 404);
        }

        $user->head_of_institution_id = $request->id;
        
        if ($user->isDirty()) {
            $user->save();
        }

        $response = "Berhasil mengubah " . ($user->type == 'kecamatan' ? 'Camat' : 'Kepala Desa') . " !";
        return response()->json($response, 200);
        
    }

    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'name' => 'required',
            'province' => 'required',
            'district' => 'required',
            'sub_district' => 'required',
            'type_village' => 'required',
            'village' => 'required',
            'street' => 'required',
            'zip_code' => 'required',
            'whatsapp_number' => 'required',
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $user = auth()->user();

        if (!$user) {
            return response()->json("Instansi tidak ditemukan !", 404);
        }

        if ($request->email != $user->email) {
            $validator = Validator::make($request->only('email'), [
                "email" => "unique:users",
            ]);

            if ($validator->fails()) {
                return response()->json($validator->errors(), 422);
            }
        }

        if ($request->has('logo') && $request->logo != null) {
            $validator = Validator::make($request->only(['logo']), [
                'logo' => 'image',
            ]);
            if ($validator->fails()) {
                return response()->json($validator->errors(), 422);
            }
            Storage::delete('app/public/' . $user->logo);
            $logo = $request->file('logo')->storePublicly('logo', 'public');
            $user->logo = $logo;
        }

        $user->email = $request->email;
        if ($request->has('password') && $request->password != null && trim($request->password) != "") {
            $user->password = bcrypt($request->password);
        }
        $user->name = $request->name;
        $user->province = $request->province;
        $user->district = $request->district;
        $user->sub_district = $request->sub_district;
        $user->type_village = $request->type_village;
        $user->village = $request->village;
        $user->street = $request->street;
        $user->zip_code = $request->zip_code;
        $user->whatsapp_number = $request->whatsapp_number;

        if ($user->isDirty()) {
            $user->save();
        }

        return response()->json("Berhasil menyimpan data!", 200);
    }
}
