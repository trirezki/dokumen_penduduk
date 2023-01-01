<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AccountController as AdminAccountController;
use App\Http\Controllers\AuthenticationController;
use App\Http\Controllers\InstitutionController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\DamiuLetterController;
use App\Http\Controllers\DispensasiNikahLetterController;
use App\Http\Controllers\IumkLetterController;
use App\Http\Controllers\OfficialController;
use App\Http\Controllers\ReferenceNumberController;
use App\Http\Controllers\ResidentController;
use App\Http\Controllers\SktmSekolahLetterController;
use App\Http\Controllers\SktmDtksLetterController;
use App\Http\Controllers\BiodataPendudukWniLetterController;
use App\Http\Controllers\SkpwniLetterController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/secret-page/register-admin', function () {
    return view('register');
})->name('register');

Route::post('/secret-page/register-admin-action', [AdminAccountController::class, 'create_admin_account'])->name('register.action');

Route::middleware('guest')->group(function () {
    Route::get('/login', function () {
        return view('login');
    })->name('login');

    Route::post('/login', [AuthenticationController::class, 'login'])->name('login.action');
});

Route::middleware('auth')->group(function () {
    Route::get('/me', [AuthenticationController::class, 'me'])->name('me');
    Route::post('/logout', [AuthenticationController::class, 'logout'])->name('logout.action');
    Route::prefix('/backend')->group(function () {
        Route::get('/analytics', [AnalyticsController::class, 'index']);
        Route::get('/institution', [InstitutionController::class, 'index']);
        // Route::get('/kop_surat', [InstitutionController::class, 'preview_kop']);
        Route::post('/institution', [InstitutionController::class, 'update']);
        Route::post('/institution/head-of-institution', [InstitutionController::class, 'update_head_of_institution']);

        Route::get('/account/village', [AccountController::class, 'village']);
        Route::get('/account/village/{id}', [AccountController::class, 'showVillage']);
        Route::post('/account/village', [AccountController::class, 'create_village']);
        Route::post('/account/village/{id}', [AccountController::class, 'update_village']);

        Route::get('/account/sub_district', [AccountController::class, 'sub_district']);
        Route::get('/account/sub_district/{id}', [AccountController::class, 'showSubDistrict']);
        Route::post('/account/sub_district', [AccountController::class, 'create_sub_district']);
        Route::post('/account/sub_district/{id}', [AccountController::class, 'update_sub_district']);

        Route::resource('/officials', OfficialController::class);
        Route::post('/residents', [ResidentController::class, 'store']);

        Route::get('/reference-number', [ReferenceNumberController::class, 'index']);
        Route::post('/reference-number', [ReferenceNumberController::class, 'save']);

        // Routing Surat IUMK
        Route::prefix('/iumk-letters')->group(function () {
            Route::get('/reference-number', [IumkLetterController::class, 'reference_number']);
            Route::get('/download/{id}', [IumkLetterController::class, 'download']);
            Route::post('/verification/{id}', [IumkLetterController::class, 'verification']);
            Route::post('/penandatangan/{id}', [IumkLetterController::class, 'penandatangan']);
            Route::post('/{id}', [IumkLetterController::class, 'update']);
        });
        Route::resource('/iumk-letters', IumkLetterController::class);

        //Routing Surat DAMIU
        Route::prefix('/damiu-letters')->group(function () {
            Route::get('/reference-number', [DamiuLetterController::class, 'reference_number']);
            Route::get('/download/{id}', [DamiuLetterController::class, 'download']);
            Route::post('/verification/{id}', [DamiuLetterController::class, 'verification']);
            Route::post('/penandatangan/{id}', [DamiuLetterController::class, 'penandatangan']);
            Route::post('/{id}', [DamiuLetterController::class, 'update']);
        });
        Route::resource('/damiu-letters', DamiuLetterController::class);

        //Routing Surat DISPENSAI_NIKAH
        Route::prefix('/dispensasi-nikah-letters')->group(function () {
            Route::get('/reference-number', [DispensasiNikahLetterController::class, 'reference_number']);
            Route::get('/download/{id}', [DispensasiNikahLetterController::class, 'download']);
            Route::post('/verification/{id}', [DispensasiNikahLetterController::class, 'verification']);
            Route::post('/penandatangan/{id}', [DispensasiNikahLetterController::class, 'penandatangan']);
            Route::post('/{id}', [DispensasiNikahLetterController::class, 'update']);
            
        });
        Route::resource('/dispensasi-nikah-letters', DispensasiNikahLetterController::class);

        //Routing Surat SKTM_SEKOLAH
        Route::prefix('/sktm-sekolah-letters')->group(function () {
            Route::get('/reference-number', [SktmSekolahLetterController::class, 'reference_number']);
            Route::get('/download/{id}', [SktmSekolahLetterController::class, 'download']);
            Route::post('/verification/{id}', [SktmSekolahLetterController::class, 'verification']);
            Route::post('/penandatangan/{id}', [SktmSekolahLetterController::class, 'penandatangan']);
            Route::post('/upload-file-arsip/{id}', [SktmSekolahLetterController::class, 'upload_file_arsip']);
            Route::post('/{id}', [SktmSekolahLetterController::class, 'update']);
        });
        Route::resource('/sktm-sekolah-letters', SktmSekolahLetterController::class);

        //Routing Surat SKTM_DTKS
        Route::prefix('/sktm-dtks-letters')->group(function () {
            Route::get('/reference-number', [SktmDtksLetterController::class, 'reference_number']);
            Route::get('/download/{id}', [SktmDtksLetterController::class, 'download']);
            Route::post('/verification/{id}', [SktmDtksLetterController::class, 'verification']);
            Route::post('/penandatangan/{id}', [SktmDtksLetterController::class, 'penandatangan']);
            Route::post('/upload-file-arsip/{id}', [SktmDtksLetterController::class, 'upload_file_arsip']);
            Route::post('/{id}', [SktmDtksLetterController::class, 'update']);
        });
        Route::resource('/sktm-dtks-letters', SktmDtksLetterController::class);

        //Routing Surat BIODATA_PENDUDUK_WNI
        Route::prefix('/biodata-penduduk-wni-letters')->group(function () {
            Route::get('/reference-number', [BiodataPendudukWniLetterController::class, 'reference_number']);
            Route::get('/download/{id}', [BiodataPendudukWniLetterController::class, 'download']);
            Route::post('/verification/{id}', [BiodataPendudukWniLetterController::class, 'verification']);
            Route::post('/penandatangan/{id}', [BiodataPendudukWniLetterController::class, 'penandatangan']);
            Route::post('/{id}', [BiodataPendudukWniLetterController::class, 'update']);
        });
        Route::resource('/biodata-penduduk-wni-letters', BiodataPendudukWniLetterController::class);

        //Routing Surat SKPWNI
        Route::prefix('/skpwni-letters')->group(function () {
            Route::get('/reference-number', [SkpwniLetterController::class, 'reference_number']);
            Route::get('/download/{id}', [SkpwniLetterController::class, 'download']);
            Route::post('/verification/{id}', [SkpwniLetterController::class, 'verification']);
            Route::post('/penandatangan/{id}', [SkpwniLetterController::class, 'penandatangan']);
            Route::post('/{id}', [SkpwniLetterController::class, 'update']);
        });
        Route::resource('/skpwni-letters', SkpwniLetterController::class);

        Route::get('/resident-search', [ResidentController::class, 'search']);
    });

    Route::get('/{any}', function () {
        return view('welcome');
    })->where('any', '.*')->name('root');
});
