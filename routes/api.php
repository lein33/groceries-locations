<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RolController;
use App\Http\Controllers\UsuarioController;

use App\Http\Controllers\AuthController;

Route::post('/login', [AuthController::class, 'login']);
Route::delete('/usuarios/{id}', [AuthController::class, 'deleteUser']);
Route::put('/usuario/{id}', [AuthController::class, 'updateUser']);

Route::post('/email/verify/{id}/{hash}', [AuthController::class, 'emailVerify'])->name('verification.verify');

//Route::post('/resend-email-verify', [AuthController::class, 'resendEmailVerificationMail'])->middleware('auth:sanctum');
Route::post('/resend-email-verify', [AuthController::class, 'resendEmailVerificationMail']);

Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.reset');


Route::apiResource('roles', RolController::class);
Route::put('rol/{id}',[RolController::class,'update'] );

Route::apiResource('usuarios', UsuarioController::class);
Route::get('usuarios/ruc/{ruc}', [UsuarioController::class, 'byRuc']);

Route::post('/registrar', [AuthController::class, 'register']);

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');
