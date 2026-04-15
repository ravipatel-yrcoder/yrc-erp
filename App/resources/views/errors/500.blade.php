@extends('layouts.app')

@section('title', 'Server Error')

@section('content')
<div class="min-h-screen flex flex-col items-center justify-center bg-gray-100 dark:bg-gray-900 text-gray-800 dark:text-gray-200">
    <div class="w-full max-w-6xl px-6 py-10">
        {{-- Error Code & Title --}}
        <div class="text-center mb-8">
            <h1 class="text-6xl font-extrabold tracking-tight mb-8 text-red-600">500 | Internal Server Error</h1>
            @if(!config('app.debug'))
                <p class="text-lg text-gray-700">An unexpected error occurred. Please try again later</p>
            @endif
        </div>
    </div>
</div>
@endsection