<?php

use Illuminate\Support\Facades\{Route, Auth};
use Livewire\Volt\Volt;

// disable register, reset password
Auth::routes(['register' => false, 'reset' => false]);

// jika ke /, redirect ke /login
Route::redirect('/', '/login');


Route::middleware('auth')->prefix('admin')->group(function () {
    Route::view('dashboard', 'dashboard')->name('admin.dashboard');
    Route::view('manajemen-user', 'manajemen-user')->name('admin.manajemen-user');
    Route::view('profil', 'profil')->name('admin.profil');

    // route volt
    Volt::route('karyawan', 'karyawan')->name('admin.karyawan');
    Volt::route('unit', 'unit')->name('admin.unit');
    Volt::route('mitra', 'mitra')->name('admin.mitra');
    Volt::route('lokasi', 'lokasi')->name('admin.lokasi');
    Volt::route('tongkang', 'tongkang')->name('admin.tongkang');
    Volt::route('type-unit', 'type-unit')->name('admin.type-unit');
    Volt::route('time-sheat', 'time-sheat')->name('admin.time-sheat');
});