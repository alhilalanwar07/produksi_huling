<?php

use App\Livewire\Actions\Logout;
use Livewire\Volt\Component;

new class extends Component {

    public function logout(Logout $logout): void
    {
        $logout();

        $this->redirect('/', navigate: true);
    }
}; ?>

<div>
    <div class="sidebar" data-background-color="white">
        <div class="sidebar-logo">
            <!-- Logo Header -->
            <div class="logo-header" data-background-color="dark">
                <a href="#" class="logo text-white">
                    MyApp
                </a>
                <div class="nav-toggle">
                    <button class="btn btn-toggle toggle-sidebar">
                        <i class="gg-menu-right"></i>
                    </button>
                    <button class="btn btn-toggle sidenav-toggler">
                        <i class="gg-menu-left"></i>
                    </button>
                </div>
                <button class="topbar-toggler more">
                    <i class="gg-more-vertical-alt"></i>
                </button>
            </div>
            <!-- End Logo Header -->
        </div>
        <div class="sidebar-wrapper scrollbar scrollbar-inner">
            <div class="sidebar-content">
                <ul class="nav nav-secondary">
                    <li class="nav-item {{ Route::is('admin.dashboard') ? 'active text-info' : '' }}">
                        <a class="nav-link" href="{{ route('admin.dashboard') }}" >
                            <i class="fas fa-home"></i>
                            <p>Dashboard</p>
                        </a>
                    </li>
                    <li class="nav-section">
                        <span class="sidebar-mini-icon">
                            <i class="fa fa-ellipsis-h"></i>
                        </span>
                        <h4 class="text-section">Masters</h4>
                    </li>
                    <li class="nav-item {{ Route::is('admin.karyawan') ? 'active text-info' : '' }}">
                        <a class="nav-link" href="{{ route('admin.karyawan') }}" >
                            <i class="fas fa-address-book"></i>
                            <p>Data Karyawan</p>
                        </a>
                    </li>
                    <!-- data unit, data mitra, data lokasi, data tongkang, type unit -->
                    <li class="nav-item {{ Route::is('admin.unit') ? 'active text-info' : '' }}">
                        <a class="nav-link" href="{{ route('admin.unit') }}" >
                            <i class="fas fa-truck"></i>
                            <p>Data Unit</p>
                        </a>
                    </li>
                    <li class="nav-item {{ Route::is('admin.mitra') ? 'active text-info' : '' }}">
                        <a class="nav-link" href="{{ route('admin.mitra') }}" >
                            <i class="fas fa-handshake"></i>
                            <p>Data Mitra</p>
                        </a>
                    </li>
                    <li class="nav-item {{ Route::is('admin.lokasi') ? 'active text-info' : '' }}">
                        <a class="nav-link" href="{{ route('admin.lokasi') }}" >
                            <i class="fas fa-map-marker-alt"></i>
                            <p>Data Lokasi</p>
                        </a>
                    </li>
                    <li class="nav-item {{ Route::is('admin.tongkang') ? 'active text-info' : '' }}">
                        <a class="nav-link" href="{{ route('admin.tongkang') }}" >  
                            <i class="fas fa-ship"></i>
                            <p>Data Tongkang</p>
                        </a>
                    </li>
                    <li class="nav-item {{ Route::is('admin.type-unit') ? 'active text-info' : '' }}">
                        <a class="nav-link" href="{{ route('admin.type-unit') }}" >     
                            <i class="fas fa-cogs"></i>
                            <p>Type Unit</p>
                        </a>
                    </li>
                    <li class="nav-section">
                        <span class="sidebar-mini-icon">
                            <i class="fa fa-ellipsis-h"></i>
                        </span>
                        <h4 class="text-section">DATA PRODUKSI</h4>
                    </li>
                    <!-- PMS dropdown -->
                    <li class="nav-item {{ Route::is('admin.pms.*') ? 'active submenu' : '' }}">
                        <a data-bs-toggle="collapse" href="#pmsSubmenu" class="nav-link" aria-expanded="{{ Route::is('admin.pms.*') ? 'true' : 'false' }}">
                            <i class="fas fa-file-contract"></i>
                            <p>PMS</p>
                            <span class="caret"></span>
                        </a>
                        <div class="collapse {{ Route::is('admin.pms.*') ? 'show' : '' }}" id="pmsSubmenu">
                            <ul class="nav nav-collapse">
                                <li class="{{ Route::is('admin.pms.retase') ? 'active text-info' : '' }}">
                                    <a class="nav-link" href="{{ route('admin.pms.retase') }}" >
                                        <span class="sub-item">Hauling Panjang</span>
                                    </a>
                                </li>
                                <li>
                                    <a class="nav-link" href="#">
                                        <span class="sub-item">Hauling Pendek</span>
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </li>
                    <!-- PERUSDA dropdown -->
                    <li class="nav-item {{ Route::is('admin.perusda.*') ? 'active submenu' : '' }}">
                        <a data-bs-toggle="collapse" href="#perusdaSubmenu" class="nav-link" aria-expanded="{{ Route::is('admin.perusda.*') ? 'true' : 'false' }}">
                            <i class="fas fa-file-alt"></i>
                            <p>PERUSDA</p>
                            <span class="caret"></span>
                        </a>
                        <div class="collapse {{ Route::is('admin.perusda.*') ? 'show' : '' }}" id="perusdaSubmenu">
                            <ul class="nav nav-collapse">
                                <li class="{{ Route::is('admin.perusda.retase') ? 'active text-info' : '' }}">
                                    <a class="nav-link" href="{{ route('admin.perusda.retase') }}">
                                        <span class="sub-item">Hauling Panjang</span>
                                    </a>
                                </li>
                                <li class="{{ Route::is('admin.perusda.hauling-pendek') ? 'active text-info' : '' }}">
                                    <a class="nav-link" href="{{ route('admin.perusda.hauling-pendek') }}">
                                        <span class="sub-item">Hauling Pendek</span>
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </li>
                    <li class="nav-item {{ Route::is('admin.barging') ? 'active text-info' : '' }}">
                        <a class="nav-link" href="{{ route('admin.barging') }}" >
                            <i class="fas fa-ship"></i>
                            <p>Barging</p>
                        </a>
                    </li>
                    <li class="nav-item {{ Route::is('admin.time-sheat') ? 'active text-info' : '' }}">
                        <a class="nav-link" href="{{ route('admin.time-sheat') }}" >
                            <i class="fas fa-clock"></i>
                            <p>Time Sheat</p>
                        </a>
                    </li>
                    <li class="nav-item {{ Route::is('admin.fuel') ? 'active text-info' : '' }}">
                        <a class="nav-link" href="{{ route('admin.fuel') }}" >
                            <i class="fas fa-gas-pump"></i>
                            <p>Fuel</p>
                        </a>
                    </li>
                    <li class="nav-section">
                        <span class="sidebar-mini-icon">
                            <i class="fa fa-ellipsis-h"></i>
                        </span>
                        <h4 class="text-section">DATA UNIT</h4>
                    </li>
                    <li class="nav-item {{ Route::is('admin.unit.*') ? 'active submenu' : '' }}">
                        <a data-bs-toggle="collapse" href="#unitSubmenu" class="nav-link" aria-expanded="{{ Route::is('admin.unit.*') ? 'true' : 'false' }}">
                            <i class="fas fa-truck"></i>
                            <p>Unit</p>
                            <span class="caret"></span>
                        </a>
                        <div class="collapse {{ Route::is('admin.unit.*') ? 'show' : '' }}" id="unitSubmenu">
                            <ul class="nav nav-collapse">
                                <li class="{{ Route::is('admin.unit.breakdown') ? 'active text-info' : '' }}">
                                    <a class="nav-link" href="{{ route('admin.unit.breakdown') }}">
                                        <span class="sub-item">Breakdown</span>
                                    </a>
                                </li>
                                <li class="{{ Route::is('admin.unit.standby') ? 'active text-info' : '' }}">
                                    <a class="nav-link" href="{{ route('admin.unit.standby') }}">
                                        <span class="sub-item">Standby</span>
                                    </a>
                                </li>
                                <li class="">
                                    <a class="nav-link" href="#">
                                        <span class="sub-item">Performance</span>
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </li>
                    <li class="nav-section">
                        <span class="sidebar-mini-icon">
                            <i class="fa fa-ellipsis-h"></i>
                        </span>
                        <h4 class="text-section">SETTINGS</h4>
                    </li>
                    @if(auth()->user()->role == 'admin')
                    <li class="nav-item {{ Route::is('admin.manajemen-user') ? 'active text-info' : '' }}">
                        <a class="nav-link" href="{{ route('admin.manajemen-user') }}" >
                            <i class="fas fa-users"></i>
                            <p>Manajemen User</p>
                        </a>
                    </li>
                    @endif
                    <li class="nav-item {{ Route::is('admin.profil') ? 'active text-info' : '' }}">
                        <a class="nav-link" href="{{ route('admin.profil') }}" >
                            <i class="fas fa-user"></i>
                            <p>Profil</p>
                        </a>
                    </li>

                    <br>
                    <div class="px-4">
                        <li class="nav-item" style="padding: 0px !important;">
                            <a href="#" wire:click="logout" class=" text-center btn btn-sm btn-danger w-100 btn-block d-flex justify-content-center align-items-center" style="padding: 0px !important;">
                                <i class="fas fa-sign-out-alt fa-lg m-2 p-1"></i> &nbsp;
                                <p style="padding: 0px !important; margin: 5px !important">Keluar</p>
                            </a>
                        </li>
                    </div>
                </ul>
            </div>
        </div>
    </div>
</div>
