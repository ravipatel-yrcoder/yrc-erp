@extends('layouts.error')

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

        @if(config('app.debug'))
            {{-- Exception Details --}}
            @isset($exception)
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 p-6">
                    {{-- Exception Class --}}
                    <h3 class="font-mono font-semibold text-lg mb-2 text-gray-900 dark:text-gray-100">
                        {{ get_class($exception) }}
                    </h3>

                    {{-- Message --}}
                    <p class="text-red-600 dark:text-red-400 font-medium mb-4">
                        {!! nl2br(e($exception->getMessage())) !!}
                    </p>

                    {{-- File + Line --}}
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-6">
                        In <span class="font-mono">{{ $exception->getFile() }}</span> at line 
                        <span class="font-mono">{{ $exception->getLine() }}</span>
                    </p>

                    {{-- Stack Trace --}}
                    <h4 class="font-semibold text-md mb-3 text-gray-900 dark:text-gray-100">Stack Trace:</h4>
                    <div class="overflow-x-auto">
                        <pre class="whitespace-pre-wrap break-words text-xs leading-relaxed bg-gray-100 dark:bg-gray-900 text-gray-700 dark:text-gray-300 p-4 rounded-lg">{{ $exception->getTraceAsString() }}</pre>
                    </div>
                </div>
            @endisset
        @endif
    </div>
</div>
@endsection