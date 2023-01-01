<?php

namespace App\Http\Controllers;

use App\Models\ReferenceNumber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ReferenceNumberController extends Controller
{
    public function index(Request $request)
    {
        $format = ReferenceNumber::get_reference_number($request->type, auth()->id());
        if (!$format) {
            return response()->json("Format surat tidak ditemukan", 404);
        }
        return response()->json($format, 200);
    }

    public function save(Request $request) {
        $validator = Validator::make($request->all(), [
            "type" => [
                "required",
                "string",
                Rule::in(array_keys(ReferenceNumber::$DEFAULT)),
            ],
            "prefix" => "required|string",
            "suffix" => "required|string",
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 402);
        }

        $request['user_id'] = auth()->id();

        ReferenceNumber::updateOrCreate(
            $request->only(["type", "user_id"]),
            $request->only(["prefix", "suffix"])
        );

        return response()->json("Berhasil menyimpan format surat " . $request->type, 200);
    }
}
