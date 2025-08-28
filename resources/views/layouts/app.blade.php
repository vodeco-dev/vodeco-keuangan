<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8"/>
  <title>{{ $title ?? 'FinanceFly' }}</title>
  @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100 font-sans">
  <div class="relative flex size-full min-h-screen flex-col overflow-x-hidden">
    <div class="flex flex-1">
      {{-- Sidebar --}}
      @include('partials.sidebar')

      {{-- Main content --}}
      <main class="flex-1 p-8">
        @yield('content')
      </main>
    </div>
  </div>
</body>
</html>
