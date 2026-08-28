<?php

use App\Http\Controllers\ChatController;
use Illuminate\Support\Facades\Route;

Route::get('/login', [ChatController::class, 'showLogin'])->name('login');
Route::post('/login', [ChatController::class, 'login'])->name('login.attempt');
Route::post('/logout', [ChatController::class, 'logout'])->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/', [ChatController::class, 'index'])->name('chat');
    Route::get('/messages', [ChatController::class, 'fetch'])->name('messages.fetch');
    Route::post('/messages', [ChatController::class, 'store'])->name('messages.store');
    Route::post('/messages/{message}/summarize', [ChatController::class, 'summarize'])->name('messages.summarize');
    Route::get('/messages/{message}/download', [ChatController::class, 'download'])->name('messages.download');
});
