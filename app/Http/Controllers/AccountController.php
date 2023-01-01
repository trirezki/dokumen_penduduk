<?php

namespace App\Http\Controllers;

use App\Http\Resources\VillageCollection;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class AccountController extends Controller
{
    public function index(Request $request, $type)
    {
        $users = User::where('type', $type);
        if ($type == 'desa') {
            $users->where('parent_id', auth()->id());
        }
        return new VillageCollection($users->paginate(9));
    }

    public function showVillage($id) {
        $user = User::where('type', 'desa')->find($id);
        if (!$user) {
            return response()->json("Akun desa tidak ditemukan !", 404);
        }
        return $user;
    }

    public function showSubDistrict($id) {
        $user = User::where('type', 'kecamatan')->find($id);
        if (!$user) {
            return response()->json("Akun kecamatan tidak ditemukan !", 404);
        }
        return $user;
    }

    public function store(Request $request, $type)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|unique:users',
            'password' => 'required',
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
        
        $user = new User;
        $user->name = $request->name;
        $user->email = $request->email;
        $user->password = bcrypt($request->password);
        $user->type = $type;
        $user->parent_id = auth()->id();
        if ($request->has('logo') && $request->logo != null) {
            $validator = Validator::make($request->only(['logo']), [
                'logo' => 'image',
            ]);
            if ($validator->fails()) {
                return response()->json($validator->errors(), 422);
            }
            $logo = $request->file('logo')->storePublicly('logo', 'public');
            $user->logo = $logo;
        }
        $user->province = $request->province;
        $user->district = $request->district;
        $user->sub_district = $request->sub_district;
        $user->type_village = $request->type_village;
        $user->village = $request->village;
        $user->street = $request->street;
        $user->zip_code = $request->zip_code;
        $user->whatsapp_number = $request->whatsapp_number;
        $user->save();
        $response = "Menyimpan akun " . $type . " sukses!";
        return response()->json($response, 200);
    }

    public function update(Request $request, $type)
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

        $user = User::where('type', $type)->find($request->id);

        if (!$user) {
            return response()->json("Akun " . $type ." tidak ditemukan !", 404);
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

        return response()->json("Berhasil mengubah akun " . $type . " !", 200);
        
    }

    public function village(Request $request)
    {
        return $this->index($request, 'desa');
    }

    public function create_village(Request $request)
    {
        return $this->store($request, 'desa');
    }

    public function sub_district(Request $request)
    {
        return $this->index($request, 'kecamatan');
    }

    public function create_sub_district(Request $request)
    {
        return $this->store($request, 'kecamatan');
    }

    public function update_village(Request $request)
    {
        return $this->update($request, 'desa');
        
    }

    public function update_sub_district(Request $request)
    {
        return $this->update($request, 'kecamatan');
        
    }


}
