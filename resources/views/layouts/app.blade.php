<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1"/>
  <link rel="shortcut icon" href="{{ asset('vodeco favicon.png') }}"/>
  <title>{{ $title ?? 'Vodeco Digital Mediatama' }}</title>
  @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100 font-sans">
  <div class="relative flex size-full min-h-screen flex-col overflow-x-hidden">
    <div class="flex flex-1">
      {{-- Sidebar --}}
      @include('partials.sidebar')

      {{-- Main content --}}
      <main class="flex-1 p-8">
        {{ $slot ?? '' }}
        @yield('content')
      </main>
    </div>
  </div>
</body>
</html>
