<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ChannelController;
use App\Http\Controllers\ChecklistController;
use App\Http\Controllers\ContentController;
use App\Http\Controllers\MetricController;
use App\Http\Controllers\ReviewController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/contents');

Route::middleware('guest')->group(function () {
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Channels
    Route::get('/channels', [ChannelController::class, 'index'])->name('channels.index');
    Route::post('/channels', [ChannelController::class, 'store'])->name('channels.store');
    Route::delete('/channels/{channel}', [ChannelController::class, 'destroy'])->name('channels.destroy');

    // Contents (CRUD + transisi status)
    Route::resource('contents', ContentController::class);
    Route::patch('/contents/{content}/status', [ContentController::class, 'transition'])->name('contents.transition');

    // Review editor (setujui / minta revisi pada tahap editing)
    Route::post('/contents/{content}/review', [ReviewController::class, 'store'])->name('contents.review');

    // Checklist
    Route::post('/contents/{content}/checklists', [ChecklistController::class, 'store'])->name('contents.checklists.store');
    Route::patch('/checklists/{checklist}/toggle', [ChecklistController::class, 'toggle'])->name('checklists.toggle');
    Route::delete('/checklists/{checklist}', [ChecklistController::class, 'destroy'])->name('checklists.destroy');

    // Metrik performa
    Route::post('/contents/{content}/metrics', [MetricController::class, 'store'])->name('contents.metrics.store');
});
