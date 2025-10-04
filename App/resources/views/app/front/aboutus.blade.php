@extends('layouts.app')

@section('title', 'About Us')
@section('bodyClasses', 'antialiased bg-gradient-to-br from-white to-slate-100 dark:from-slate-950 dark:to-slate-900 min-h-screen')

@section('content')

<div class="relative flex min-h-screen flex-col items-center justify-center px-6">
  <!-- Logo/Icon link to Home -->
  <a href="{{ url('/') }}" class="absolute top-8 flex flex-col items-center">
    <div class="mx-auto h-14 w-14 rounded-2xl bg-gradient-to-br from-rose-500 to-orange-400 shadow-lg"></div>
  </a>

  <!-- Card -->
  <div class="w-full max-w-3xl rounded-xl border border-slate-200 p-8 shadow-lg dark:border-slate-800 dark:bg-slate-900">
    <h1 class="mb-6 text-center text-3xl font-semibold text-slate-900 dark:text-white">About Us</h1>

    <p class="mb-4 text-slate-700 dark:text-slate-300 text-lg leading-relaxed">
      Welcome to Our Company! We are dedicated to providing the best services and solutions to our customers.
    </p>

    <p class="mb-4 text-slate-700 dark:text-slate-300 text-lg leading-relaxed">
      Our mission is to empower businesses and individuals through innovative technology, exceptional support, and a commitment to quality.
    </p>

    <p class="mb-4 text-slate-700 dark:text-slate-300 text-lg leading-relaxed">
      Founded in [Year], we have grown from a small team into a global organization, delivering reliable solutions across multiple industries.
    </p>

    <p class="mb-4 text-slate-700 dark:text-slate-300 text-lg leading-relaxed">
      Our core values include integrity, innovation, collaboration, and customer-centricity. We strive to create long-lasting relationships with our clients by exceeding expectations.
    </p>

    <div class="mt-6 text-center">
      <a href="{{ url('/') }}" class="inline-block rounded-lg bg-gradient-to-br from-rose-500 to-orange-400 px-6 py-2 text-white shadow-lg hover:opacity-90 transition">
        Back to Home
      </a>
    </div>
  </div>
</div>

@endsection