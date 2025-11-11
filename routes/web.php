<?php

use Illuminate\Support\Facades\{Route, Auth};
use Livewire\Volt\Volt;

// disable register, reset password
Auth::routes(['register' => false, 'reset' => false]);

// jika ke /, redirect ke /login
Route::get('/', function () {
    return redirect()->route('admin.dashboard');
});


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
    Volt::route('fuel', 'fuel')->name('admin.fuel');
    Volt::route('breakdown', 'breakdown')->name('admin.unit.breakdown');
    Volt::route('standby', 'standby')->name('admin.unit.standby');
    Volt::route('barging', 'barging')->name('admin.barging');

    // perusda: retase,
    Volt::route('hauling-panjang-perusda', 'perusda.retase')->name('admin.perusda.retase');
    Volt::route('hauling-pendek-perusda', 'perusda.hauling-pendek')->name('admin.perusda.hauling-pendek');

    // pms: retase
    Volt::route('hauling-panjang-pms', 'pms.retase')->name('admin.pms.retase');
});