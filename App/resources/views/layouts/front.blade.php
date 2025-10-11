<!doctype html>
<html lang="en" class="layout-navbar-fixed layout-wide" dir="ltr" data-skin="default" data-assets-path="{{asset('/assets/')}}" data-template="front-pages" data-bs-theme="dark">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    <title>@hasSection('title'){{config('app.name')}} | @yield('title') @else {{config('app.name')}} @endif</title>
    <meta name="description" content="" />
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="../../assets/img/favicon/favicon.ico" />
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="{{asset('/assets/vendor/fonts/iconify-icons.css')}}" />

    <!-- Core CSS -->
    <!-- build:css assets/vendor/css/theme.css  -->
    <link rel="stylesheet" href="{{asset('/assets/vendor/libs/pickr/pickr-themes.css')}}" />
    <link rel="stylesheet" href="{{asset('/assets/vendor/css/core.css')}}" />
    <link rel="stylesheet" href="{{asset('/assets/css/demo.css')}}" />
    <link rel="stylesheet" href="{{asset('/assets/vendor/css/pages/front-page.css')}}" />
    <!-- Vendors CSS -->
    <!-- endbuild -->
    <link rel="stylesheet" href="{{asset('/assets/vendor/libs/nouislider/nouislider.css')}}" />
    <link rel="stylesheet" href="{{asset('/assets/vendor/libs/swiper/swiper.css')}}" />
    <!-- Page CSS -->

    <link rel="stylesheet" href="{{asset('/assets/vendor/css/pages/front-page-landing.css')}}" />
    
    <!-- Helpers -->
    <script src="{{asset('/assets/vendor/js/helpers.js')}}"></script>
    <!--! Template customizer & Theme config files MUST be included after core stylesheets and helpers.js in the <head> section -->

    <!--? Template customizer: To hide customizer set displayCustomizer value false in config.js.  -->
    <script src="{{asset('/assets/vendor/js/template-customizer.js')}}"></script>

    <!--? Config:  Mandatory theme config file contain global vars & default theme options, Set your preferred theme option in this file.  -->

    <script src="{{asset('/assets/js/front-config.js')}}"></script>
  </head>
    <body class="">

        <script src="{{asset('/assets/vendor/js/dropdown-hover.js')}}"></script>
        <script src="{{asset('/assets/vendor/js/mega-dropdown.js')}}"></script>
        
        @include('partial.front.nav')

        @yield('content')    

        @include('partial.front.footer')
        <script src="{{asset('/assets/vendor/libs/popper/popper.js')}}"></script>
        <script src="{{asset('/assets/vendor/js/bootstrap.js')}}"></script>
        <script src="{{asset('/assets/vendor/libs/@algolia/autocomplete-js.js')}}"></script>

        <script src="{{asset('/assets/vendor/libs/pickr/pickr.js')}}"></script>

        <!-- endbuild -->

        <!-- Vendors JS -->
        <script src="{{asset('/assets/vendor/libs/nouislider/nouislider.js')}}"></script>
        <script src="{{asset('/assets/vendor/libs/swiper/swiper.js')}}"></script>

        <!-- Main JS -->

        <script src="{{asset('/assets/js/front-main.js')}}"></script>

        <!-- Page JS -->
        <script src="{{asset('/assets/js/front-page-landing.js')}}"></script>

        <!-- View-specific JS stack -->
        @stack('scripts')

    </body>
</html>