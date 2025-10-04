<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>@hasSection('title')TinyPHP | @yield('title') @else TinyPHP @endif</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <meta name="color-scheme" content="light dark" />
    <style>
        :root { --ring: 0 0% 0%; }
        @media (prefers-color-scheme: dark) {
        :root { --ring: 0 0% 100%; }
        }
    </style>
    
    <!-- Global JS -->
    <script src="{{ asset('js/app.js') }}"></script>

</head>
<body class="">
    @yield('content')
    
     <!-- View-specific JS stack -->
    @stack('scripts')

</body>
</html>