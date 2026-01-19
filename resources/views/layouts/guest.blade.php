<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', '預約系統') }}</title>

    <!-- 字體（Figtree + 支援中文） -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+TC:wght@400;500;700&display=swap" rel="stylesheet">

    <!-- Tailwind + Vite -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @livewireStyles

    <!-- 自訂全局樣式 -->
    <style>
        body {
            font-family: 'Figtree', 'Noto Sans TC', system-ui, sans-serif;
        }
    </style>
</head>

<body
    class="antialiased bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-900 dark:to-gray-950 text-gray-900 dark:text-gray-100 min-h-screen flex flex-col">
    <!-- 主要內容 -->
    <div class="flex-grow">
        {{ $slot }}
    </div>

    <!-- Livewire Scripts -->
    @livewireScripts

    <!-- 可選：全域通知或 loading 效果 -->
    <script>
        // 簡單的暗黑模式偵測（可移除）
        if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia(
                '(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>
</body>

</html>
