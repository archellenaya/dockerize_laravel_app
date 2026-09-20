<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>@yield('title', 'Latest News') &middot; {{ config('app.name', 'News Aggregator') }}</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @stack('head')
    </head>
    <body class="min-h-screen bg-gray-50 text-gray-900 antialiased">
        <header class="border-b border-gray-200 bg-white">
            <div class="mx-auto flex max-w-6xl items-center justify-between px-4 py-4 sm:px-6">
                <a href="{{ route('articles.index') }}" class="text-lg font-semibold text-gray-900">
                    {{ config('app.name', 'News Aggregator') }}
                </a>

                <nav class="flex items-center gap-4 text-sm">
                    @auth
                        <a href="{{ route('dashboard') }}" class="font-medium text-gray-700 hover:text-gray-900">
                            Dashboard
                        </a>
                        <span class="text-gray-400">{{ auth()->user()->name }}</span>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="font-medium text-gray-700 hover:text-gray-900">
                                Log out
                            </button>
                        </form>
                    @else
                        <a href="{{ route('login') }}" class="font-medium text-gray-700 hover:text-gray-900">
                            Log in
                        </a>
                        <a href="{{ route('register') }}" class="rounded-md bg-gray-900 px-3 py-1.5 font-medium text-white hover:bg-gray-700">
                            Register
                        </a>
                    @endauth
                </nav>
            </div>
        </header>

        <main class="mx-auto max-w-6xl px-4 py-8 sm:px-6">
            @if (session('status'))
                <div class="mb-6 rounded-md border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                    {{ session('status') }}
                </div>
            @endif

            @yield('content')
        </main>

        <footer class="mt-12 border-t border-gray-200 py-6 text-center text-sm text-gray-500">
            &copy; {{ now()->year }} {{ config('app.name', 'News Aggregator') }}
        </footer>

        @stack('scripts')
    </body>
</html>
