<?php

namespace App\Http\Controllers;

use App\Models\BiodataPendudukWniLetter;
use App\Models\DamiuLetter;
use App\Models\DispensasiNikahLetter;
use App\Models\IumkLetter;
use App\Models\SkpwniLetter;
use App\Models\SktmDtksLetter;
use App\Models\SktmSekolahLetter;
use Illuminate\Http\Request;

class AnalyticsController extends Controller
{
    public function index()
    {
        $iumk = IumkLetter::byUserId(auth()->id())->count();
        $iumk_verification = IumkLetter::byUserId(auth()->id())->where('verified_file', '!=', null)->count();
        $damiu = DamiuLetter::byUserId(auth()->id())->count();
        $damiu_verification = DamiuLetter::byUserId(auth()->id())->where('verified_file', '!=', null)->count();
        $dispensasi_nikah = DispensasiNikahLetter::byUserId(auth()->id())->count();
        $dispensasi_nikah_verification = DispensasiNikahLetter::byUserId(auth()->id())->where('verified_file', '!=', null)->count();
        $sktm_sekolah = SktmSekolahLetter::byUserId(auth()->id())->count();
        $sktm_sekolah_verification = SktmSekolahLetter::byUserId(auth()->id())->where('verified_file', '!=', null)->count();
        $sktm_dtks = SktmDtksLetter::byUserId(auth()->id())->count();
        $sktm_dtks_verification = SktmDtksLetter::byUserId(auth()->id())->where('verified_file', '!=', null)->count();
        $biodata_penduduk_wni = BiodataPendudukWniLetter::byUserId(auth()->id())->count();
        $biodata_penduduk_wni_verification = BiodataPendudukWniLetter::byUserId(auth()->id())->where('verified_file', '!=', null)->count();
        $skpwni = SkpwniLetter::byUserId(auth()->id())->count();
        $skpwni_verification = SkpwniLetter::byUserId(auth()->id())->where('verified_file', '!=', null)->count();
        return response()->json([
            "iumk" => [
                "count" => $iumk, 
                "verified" => $iumk_verification
            ],
            "damiu" => [
                "count" => $damiu, 
                "verified" => $damiu_verification
            ],
            "dispensasi_nikah" => [
                "count" => $dispensasi_nikah, 
                "verified" => $dispensasi_nikah_verification
            ],
            "sktm_sekolah" => [
                "count" => $sktm_sekolah, 
                "verified" => $sktm_sekolah_verification
            ],
            "sktm_dtks" => [
                "count" => $sktm_dtks, 
                "verified" => $sktm_dtks_verification
            ],
            "biodata_penduduk_wni" => [
                "count" => $biodata_penduduk_wni, 
                "verified" => $biodata_penduduk_wni_verification
            ],
            "skpwni" => [
                "count" => $skpwni, 
                "verified" => $skpwni_verification
            ],
        ], 200);
    }
}
