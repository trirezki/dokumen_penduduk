<?php

namespace App\Http\Controllers;

use App\Http\Resources\ResidentCollection;
use App\Models\Resident;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ResidentController extends Controller
{
    public function search(Request $request)
    {
        $residents = Resident::byUserId(auth()->id())->where(function ($q) use ($request) {
            $q->where('nik', 'LIKE', '%' . $request->search . '%')
            ->orWhere('name', 'LIKE', '%' . $request->search . '%');
        });
        if ($request->has('gender')) {
            $residents = $residents->where('gender', $request->gender);
        }
        $residents = $residents->paginate(5);
        return new ResidentCollection($residents);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "nik" => "required|string",
            "name" => "required|string",
            "gender" => "required|string|in:laki-laki,perempuan",
            "place_of_birth" => "required|string",
            "date_of_birth" => "required|date",
            "profession" => "required|string",
            "religion" => "required|in:Islam,Kristen,Katolik,Hindu,Budha,Konghucu",
            "marital_status" => "required|string|in:jejaka,perawan,kawin,cerai_hidup,cerai_mati",
            "address" => "required|string",
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();
            return response()->json($errors, 500);
        }

        try {
            $payload = $request->all();
            $payload["user_id"] = auth()->id();
            $resident = Resident::create($payload);
            $response = [
                "message" => "Berhasil menambah data penduduk",
                "data" => $resident,
            ];

            return response()->json($response, 201);
        } catch (Exception $e) {
            return response()->json($e, 500);
        }
    }
}
