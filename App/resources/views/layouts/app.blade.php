<!doctype html>
<html lang="en" class="layout-navbar-fixed layout-compact layout-menu-100vh layout-menu-collapsed layout-menu-fixed" dir="ltr" data-skin="default" data-assets-path="{{asset('/assets/')}}" data-template="vertical-menu-template-no-customizer" data-bs-theme="light">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    <title>@hasSection('title'){{config('app.name')}} | @yield('title') @else {{config('app.name')}} @endif</title>
    <meta name="description" content="" />
    <meta name="csrf-token" content="{{TinyPHP_Session::generateCSRFToken()}}" />

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="{{asset('/assets/img/favicon/favicon.ico')}}" />

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&display=swap"
      rel="stylesheet" />

    <link rel="stylesheet" href="{{asset('/assets/vendor/fonts/iconify-icons.css')}}" />

    <!-- Core CSS -->
    <!-- build:css assets/vendor/css/theme.css  -->

    <link rel="stylesheet" href="{{asset('/assets/vendor/css/core.css')}}" />
    <link rel="stylesheet" href="{{asset('/assets/css/demo.css')}}" />

    <!-- Vendors CSS -->

    <link rel="stylesheet" href="{{asset('/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css')}}" />

    <link rel="stylesheet" href="{{asset('/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css')}}" />
    <link rel="stylesheet" href="{{asset('/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css')}}" />

    <!-- endbuild -->

    <!-- Vendor -->
    <link rel="stylesheet" href="{{asset('/assets/vendor/libs/@form-validation/form-validation.css')}}" />
    <link rel="stylesheet" href="{{asset('/assets/vendor/libs/select2/select2.css')}}" />
    <link rel="stylesheet" href="{{asset('/assets/vendor/libs/dropzone/dropzone.css')}}" />
    <link rel="stylesheet" href="{{asset('/assets/vendor/libs/notyf/notyf.css')}}" />
    <link rel="stylesheet" href="{{asset('/assets/vendor/libs/sweetalert2/sweetalert2.css')}}" />
    <link rel="stylesheet" href="{{asset('/assets/vendor/libs/tagify/tagify.css')}}" />

    <!-- Page CSS -->
    <!-- Page -->
    <link rel="stylesheet" href="{{asset('/assets/vendor/css/pages/page-auth.css')}}" />

    <!-- Custom CSS -->
    <link rel="stylesheet" href="{{asset('/assets/css/custom.css')}}" />

    <script src="{{asset('/assets/vendor/js/helpers.js')}}"></script>
    <script src="{{asset('/assets/js/config.js')}}"></script>
    
  </head>
<body class="">
    
    @if (auth()->check())
      <div class="layout-wrapper layout-content-navbar">
        <div class="layout-container">
          @include('partial.app.sidebar')
            <div class="layout-page">
              
              @include('partial.app.nav')
              
              <div class="content-wrapper">
                @yield('content')
              </div>

              @include('partial.app.footer')

              <div class="content-backdrop fade"></div>

            </div>
        </div>
        <!-- Overlay -->
        <div class="layout-overlay layout-menu-toggle"></div>
        
        <!-- Drag Target Area To SlideIn Menu On Small Screens -->
        <div class="drag-target"></div>
      </div>
    @else
      @yield('content')
    @endif    
    
    @include('partial.common.preloader')

    <!-- Core JS -->
    <script src="{{asset('/assets/vendor/libs/jquery/jquery.js')}}"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <script src="{{asset('/assets/js/app-axios.js')}}"></script>    
    <script src="{{asset('/assets/vendor/libs/popper/popper.js')}}"></script>
    <script src="{{asset('/assets/vendor/js/bootstrap.js')}}"></script>
    <script src="{{asset('/assets/vendor/libs/@algolia/autocomplete-js.js')}}"></script>
    <script src="{{asset('/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js')}}"></script>
    <script src="{{asset('/assets/vendor/libs/hammer/hammer.js')}}"></script>
    <script src="{{asset('/assets/vendor/libs/i18n/i18n.js')}}"></script>
    <script src="{{asset('/assets/vendor/js/menu.js')}}"></script>
    <script src="{{asset('/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js')}}"></script>
    
    <!-- Vendors JS -->
    <script src="{{asset('/assets/vendor/libs/@form-validation/popular.js')}}"></script>
    <script src="{{asset('/assets/vendor/libs/@form-validation/bootstrap5.js')}}"></script>
    <script src="{{asset('/assets/vendor/libs/@form-validation/auto-focus.js')}}"></script>
    <script src="{{asset('/assets/vendor/libs/select2/select2.js')}}"></script>
    <script src="{{asset('/assets/vendor/libs/dropzone/dropzone.js')}}"></script>
    <script src="{{asset('/assets/vendor/libs/notyf/notyf.js')}}"></script>
    <script src="{{asset('/assets/vendor/libs/sweetalert2/sweetalert2.js')}}"></script>
    <script src="{{asset('/assets/vendor/libs/tagify/tagify.js')}}"></script>
    
    <!-- Main JS -->
    <script src="{{asset('/assets/js/main.js')}}"></script>
    <script src="{{asset('/assets/js/app-lib-common.js')}}"></script>
    <script src="{{asset('/assets/js/app-datatable.js')}}"></script>
    <script src="{{asset('/assets/js/app-custom.js')}}"></script>

     <!-- View-specific JS stack -->
    @stack('scripts')

</body>
</html>