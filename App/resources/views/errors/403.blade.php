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

        {{-- Exception Details (if any) --}}
        @isset($exception)
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 p-6">
                {{-- Exception Class --}}
                <h3 class="font-mono font-semibold text-lg mb-2 text-gray-900 dark:text-gray-100">
                    {{ get_class($exception) }}
                </h3>

                {{-- Message --}}
                <p class="text-red-600 dark:text-red-400 font-medium mb-4">
                    {{ $exception->getMessage() }}
                </p>

                {{-- File + Line --}}
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-6">
                    In <span class="font-mono">{{ $exception->getActualFile() }}</span> at line 
                    <span class="font-mono">{{ $exception->getActualLine() }}</span>
                </p>

                {{-- Stack Trace --}}
                <h4 class="font-semibold text-md mb-3 text-gray-900 dark:text-gray-100">Stack Trace:</h4>
                <div class="overflow-x-auto">
                    <pre class="whitespace-pre-wrap break-words text-xs leading-relaxed bg-gray-100 dark:bg-gray-900 text-gray-700 dark:text-gray-300 p-4 rounded-lg">
{{ $exception->getTraceAsString() }}
                    </pre>
                </div>
            </div>
        @endisset
    </div>
</div>
@endsection