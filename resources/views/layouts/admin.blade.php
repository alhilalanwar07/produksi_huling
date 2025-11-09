<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>
        @yield('title', config('app.name'))
    </title>
    <meta content="width=device-width, initial-scale=1.0, shrink-to-fit=no" name="viewport" />
    <link rel="icon" href="{{ url('/') }}/assets/img/logo/favicon.ico" type="image/x-icon" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
    <script src="{{ url('/') }}/assets/js/plugin/webfont/webfont.min.js" data-navigate-track></script>
    <link rel="stylesheet" href="{{ url('/') }}/assets/css/fonts.min.css" />
    <link rel="stylesheet" href="{{ url('/') }}/assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="{{ url('/') }}/assets/css/plugins.min.css" />
    <link rel="stylesheet" href="{{ url('/') }}/assets/css/kaiadmin.min.css" />
    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="icon" type="image/png" href="{{ url('/') }}/favicon-96x96.png" sizes="96x96" />
    <link rel="icon" type="image/svg+xml" href="{{ url('/') }}/favicon.svg" />
    <link rel="shortcut icon" href="{{ url('/') }}/favicon.ico" />
    <link rel="apple-touch-icon" sizes="180x180" href="{{ url('/') }}/apple-touch-icon.png" />
    <link rel="manifest" href="{{ url('/') }}/site.webmanifest" />
    @livewireStyles
    <style>
        :root {
            --ui-font: "Poppins", sans-serif;
        }

        html,
        body,
        button,
        input,
        select,
        textarea {
            font-family: var(--ui-font) !important;
        }

        body {
            font-weight: 400;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        /* Headings and brand use slightly heavier weight */
        h1,
        h2,
        h3,
        h4,
        h5,
        h6,
        .navbar-brand,
        .logo,
        .main-header-logo .logo {
            font-weight: 600;
        }

        /* Smaller / muted text */
        small,
        .text-muted {
            font-weight: 300;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <livewire:layout.admin-navigation />

        <div class="main-panel">
            <div class="main-header">
                <div class="main-header-logo">
                    <!-- Logo Header -->
                    <div class="logo-header" data-background-color="white">
                        <a href="#" class="logo">
                            <img src="{{ url('/') }}/assets/img/logo/logo_light.svg" alt="navbar brand" class="navbar-brand" height="20" />
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
                <!-- Navbar Header -->
                <livewire:layout.admin-header />
                <!-- End Navbar -->
            </div>

            <div class="container">
                {{ $slot }}
            </div>

            <footer class="footer">
                <div class="container-fluid d-flex justify-content-between">
                    <div class="copyright">
                        {{ date('Y') }}, made with <i class="fa fa-heart heart text-danger"></i> by
                        <a href="#">YOU</a>
                    </div>
                </div>
            </footer>
        </div>
    </div>
    <!--   Core JS Files   -->
    <script src="{{ url('/') }}/assets/js/core/jquery-3.7.1.min.js" data-navigate-track></script>
    <script src="{{ url('/') }}/assets/js/core/popper.min.js" data-navigate-track></script>
    <script src="{{ url('/') }}/assets/js/core/bootstrap.min.js" data-navigate-track></script>
    <script src="{{ url('/') }}/assets/js/plugin/jquery-scrollbar/jquery.scrollbar.min.js" data-navigate-track></script>
    <script src="{{ url('/') }}/assets/js/plugin/chart.js/chart.min.js" data-navigate-track></script>
    <script src="{{ url('/') }}/assets/js/plugin/jquery.sparkline/jquery.sparkline.min.js" data-navigate-track></script>
    <script src="{{ url('/') }}/assets/js/plugin/chart-circle/circles.min.js" data-navigate-track></script>
    <script src="{{ url('/') }}/assets/js/plugin/datatables/datatables.min.js" data-navigate-track></script>
    <script src="{{ url('/') }}/assets/js/plugin/bootstrap-notify/bootstrap-notify.min.js" data-navigate-track></script>
    <script src="{{ url('/') }}/assets/js/plugin/jsvectormap/jsvectormap.min.js" data-navigate-track></script>
    <script src="{{ url('/') }}/assets/js/plugin/jsvectormap/world.js" data-navigate-track></script>
    <script src="{{ url('/') }}/assets/js/plugin/sweetalert/sweetalert.min.js" data-navigate-track></script>
    <script src="{{ url('/') }}/assets/js/kaiadmin.min.js" data-navigate-track></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js" data-navigate-track></script>
    <!-- Select2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    @stack('script')
    @stack('scripts')
    @livewireScripts
</body>
</html>