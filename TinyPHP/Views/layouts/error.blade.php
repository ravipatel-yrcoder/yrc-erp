<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>@hasSection('title')TinyPHP | @yield('title') @else TinyPHP @endif</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <meta name="color-scheme" content="light dark" />
    <style>
        :root { --ring: 0 0% 0%; }
        @media (prefers-color-scheme: dark) {
        :root { --ring: 0 0% 100%; }
        }
    </style>
    {!! $styleSheets !!}
    {!! $headerScripts !!}
</head>
<body class="">
    @yield('content')
    {!! $footerScripts !!}
</body>
</html>