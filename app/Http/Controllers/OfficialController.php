<?php

namespace App\Http\Controllers;

use App\Http\Resources\OfficialCollection;
use App\Models\Official;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class OfficialController extends Controller
{
    public function index(Request $request)
    {
        $officials = Official::byUserId(auth()->id())
            ->where(function ($q) use ($request) {
                $q->where('nip', 'like', '%' . $request->search . '%')
                    ->orWhere('name', 'like', '%' . $request->search . '%')
                    ->orWhere('position', 'like', '%' . $request->search . '%')
                    ->orWhere('rank', 'like', '%' . $request->search . '%');
            })
            ->paginate(5);
        return new OfficialCollection($officials);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "nip" => "nullable|string",
            "name" => "required|string",
            "position" => "required|string",
            "rank" => "nullable|string",
            "signature" => "required|image",
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();
            return response()->json($errors, 500);
        }

        $request["user_id"] = auth()->id();

        try {
            $signature = $request->file('signature')->storePublicly('signature', 'public');
            $data = $request->all();
            $data['signature'] = $signature;
            $response_data = Official::create($data);
            $response = [
                "message" => "Pejabat berhasil ditambahkan",
                "data" => $response_data
            ];

            return response()->json($response, 201);
        } catch (Exception $e) {
            return response()->json($e, 500);
        }
    }

    public function update(Request $request, $id)
    {
        $official = Official::byUserId(auth()->id())->where('id', $id)->first();
        if (!$official || !$official->can_modified()) {
            return response()->json("Gagal memperbarui data. Pejabat tidak ditemukan.", 404);
        }

        $validator = Validator::make($request->all(), [
            "nip" => "string",
            "name" => "required|string",
            "position" => "required|string",
            "rank" => "required|string",
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();
            return response()->json($errors, 500);
        }

        $request["user_id"] = auth()->id();

        try {
            Storage::disk('public')->delete($official->signature);
            $signature = $request->file('signature')->storePublicly('signature', 'public');
            $request['signature'] = $signature;
            $official->update($request->all());
            $response = [
                "message" => "Pejabat berhasil diperbaru",
                "data" => $official
            ];

            return response()->json($response, 200);
        } catch (Exception $e) {
            return response()->json("Pejabat gagal diperbarui", 500);
        }
    }

    public function destroy($id)
    {
        $official = Official::byUserId(auth()->id())->where('id', $id)->first();
        if (!$official || !$official->can_modified()) {
            return response()->json("Gagal menghapus data. Pejabat tidak ditemukan.", 404);
        }

        DB::beginTransaction();
        try {
            $official->delete();
            if (Storage::disk('public')->exists($official->signature)) {
                Storage::disk('public')->delete($official->signature);
            }

            DB::commit();

            $response = [
                "message" => "Pejabat berhasil dihapus",
                "data" => null,
            ];
            return response()->json($response, 200);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json("Pejabat gagal dihapus", 500);
        }
    }
}
