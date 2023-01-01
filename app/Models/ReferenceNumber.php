<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReferenceNumber extends Model
{
    use HasFactory;

    public static $DEFAULT = [
        "IUMK" => [
            "type" => "IUMK",
            "prefix" => "535/",
            "suffix" => "/PLSi",
        ],
        "DAMIU" => [
            "type" => "DAMIU",
            "prefix" => "540.2/",
            "suffix" => "/PLSi",
        ],
        "DISPENSASI_NIKAH" => [
            "type" => "DISPENSASI_NIKAH",
            "prefix" => "474.2/",
            "suffix" => "/PLSi",
        ],
        "SKTM_SEKOLAH" => [
            "type" => "SKTM_SEKOLAH",
            "prefix" => "476/",
            "suffix" => "/PLSi",
        ],
        "SKTM_DTKS" => [
            "type" => "SKTM_DTKS",
            "prefix" => "460/",
            "suffix" => "/PLSi",
        ],
        "BIODATA_PENDUDUK_WNI" => [
            "type" => "BIODATA_PENDUDUK_WNI",
            "prefix" => "F-1/",
            "suffix" => "",
        ],
        "SKPWNI" => [
            "type" => "SKPWNI",
            "prefix" => "F-1.{bln}.",
            "suffix" => "",
        ],
    ];

    protected $fillable = [
        "type",
        "prefix",
        "suffix",
        "user_id",
    ];

    public static function get_reference_number($type, $user_id) {
        $format = self::where([
            "user_id" => $user_id,
            "type" => $type,
        ])->first();

        if(!$format) {
            if (!array_key_exists($type, self::$DEFAULT)) {
                return null;
            }
            return self::$DEFAULT[$type];
        }

        return $format;
    }


    // Parse {tgl} {bln} {thn} ke tanggal, bulan, dam tahun saat ini

    public static function parse_reference_number($reference_number) {
        $reference_number = str_replace("{tgl}", sprintf("%02s", Carbon::now()->day), $reference_number);
        $reference_number = str_replace("{bln}", sprintf("%02s", Carbon::now()->month), $reference_number);
        $reference_number = str_replace("{thn}", Carbon::now()->year, $reference_number);
        return $reference_number;
    }
}
