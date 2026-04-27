@extends('layouts.error')

@section('title', 'Access Forbidden')

@section('content')
<div class="min-h-screen flex flex-col items-center justify-center bg-gray-100 dark:bg-gray-900 text-gray-800 dark:text-gray-200">
    <div class="w-full max-w-6xl px-6 py-10">
        {{-- Error Code & Title --}}
        <div class="text-center mb-8">
            <h1 class="text-9xl font-extrabold tracking-tight mb-4 text-red-600">403</h1>
            <h2 class="text-3xl md:text-4xl font-semibold mb-2">Access Forbidden</h2>
            <p class="text-lg text-gray-500 dark:text-gray-400">You don't have permission to access this resource.</p>
        </div>        
    </div>
</div>
@endsection