<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Http;
use Exception;

class Whatsapp extends Model
{
    use HasFactory;

    public static function send($number_phone, $message) {
        try {
            Http::post('http://localhost:8080/send-text', [
                'number' => $number_phone . '@s.whatsapp.net',
                'text' => $message,
            ]);
        } catch (Exception $e) {
            throw new Exception("WHATSAPP_SEND_FAIL");
        }
    }
}
