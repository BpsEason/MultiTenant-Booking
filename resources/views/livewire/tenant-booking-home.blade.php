<div class="min-h-screen bg-gray-50 dark:bg-gray-900">
    <style>
        :root {
            /* 1. 從 Tenant Model 的 JSON settings 抓取顏色，對齊資料庫 primary_color */
            --primary-color: {{ $tenant->settings['primary_color'] ?? '#4F46E5' }};

            /* 自動計算深色變體（用於漸層結尾與 Hover） */
            --primary-dark: color-mix(in srgb, var(--primary-color) 80%, black);
            /* 自動計算光暈色（用於陰影與半透明背景） */
            --primary-glow: color-mix(in srgb, var(--primary-color) 25%, transparent);
        }

        .text-tenant {
            color: var(--primary-color);
        }

        .bg-tenant {
            background-color: var(--primary-color);
        }

        .border-tenant {
            border-color: var(--primary-color);
        }
    </style>

    <nav
        class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-md shadow-sm sticky top-0 z-50 border-b border-gray-200 dark:border-gray-700">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <a href="/"
                class="text-xl sm:text-2xl font-bold text-tenant tracking-tight hover:opacity-80 transition">
                {{ $tenant->name }}
            </a>
            <div class="flex items-center gap-4">
                <button
                    class="px-6 py-2 rounded-full text-white font-medium bg-tenant hover:shadow-lg transition-all active:scale-95 shadow-[0_4px_14px_0_var(--primary-glow)]">
                    登入 / 註冊
                </button>
            </div>
        </div>
    </nav>

    <section id="services" class="max-w-7xl mx-auto px-5 sm:px-6 lg:px-8 py-20 lg:py-28">
        <div class="text-center mb-16 lg:mb-20">
            <h2
                class="text-4xl sm:text-5xl lg:text-6xl font-extrabold text-gray-900 dark:text-white tracking-tight bg-clip-text text-transparent bg-gradient-to-r from-[var(--primary-color)] to-[var(--primary-dark)] inline-block">
                我們的頂級服務
            </h2>
            <p class="mt-5 text-xl text-gray-600 dark:text-gray-300 max-w-3xl mx-auto font-light">
                {{ $tenant->settings['welcome_msg'] ?? '專為您打造的極致體驗 • 專業 • 高效 • 值得信賴' }}
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 lg:gap-10">
            {{-- 核心修正：Livewire Computed Property 必須使用 $this->services 存取 --}}
            @forelse($this->services as $service)
                <div
                    class="group relative bg-white dark:bg-gray-900 rounded-3xl shadow-xl overflow-hidden transition-all duration-500 hover:shadow-2xl hover:shadow-[var(--primary-glow)] hover:-translate-y-4 border border-gray-100/50 dark:border-gray-700/40">

                    <div class="relative h-64 overflow-hidden bg-gray-100 dark:bg-gray-800">
                        @if ($service->image_path)
                            <img src="{{ Storage::url($service->image_path) }}" alt="{{ $service->name }}"
                                class="w-full h-full object-cover transition-all duration-700 group-hover:scale-110 group-hover:rotate-1 brightness-90 group-hover:brightness-100"
                                loading="lazy">
                        @else
                            <div
                                class="absolute inset-0 bg-gradient-to-br from-[var(--primary-glow)] to-transparent flex items-center justify-center">
                                <svg class="w-28 h-28 text-tenant opacity-20 animate-pulse" fill="none"
                                    stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                        @endif

                        <div
                            class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-500">
                        </div>

                        @if ($service->is_popular ?? false)
                            <div
                                class="absolute top-5 right-5 px-4 py-1.5 bg-gradient-to-r from-rose-500 to-pink-600 text-white text-sm font-bold rounded-full shadow-lg transform group-hover:scale-110 transition-transform duration-300">
                                熱門首選
                            </div>
                        @endif
                    </div>

                    <div class="p-7 lg:p-8 flex flex-col relative">
                        <h3
                            class="text-2xl lg:text-3xl font-bold text-gray-900 dark:text-white mb-4 line-clamp-2 group-hover:text-tenant transition-colors duration-300">
                            {{ $service->name }}
                        </h3>

                        <p
                            class="text-gray-600 dark:text-gray-300 mb-8 line-clamp-3 min-h-[4.8rem] leading-relaxed text-base">
                            {{ $service->description ?? '頂級服務，細節見真章。立即體驗不一樣的專業。' }}
                        </p>

                        <div class="mt-auto flex items-end justify-between">
                            <div class="flex items-baseline gap-2">
                                <span class="text-4xl lg:text-5xl font-black text-tenant tracking-tight drop-shadow-sm">
                                    ${{ number_format($service->price) }}
                                </span>
                                <span class="text-lg text-gray-500 dark:text-gray-400 font-medium">起</span>
                            </div>

                            <a href="{{ route('tenant.booking', ['tenant' => $tenant->slug, 'service' => $service->id]) }}"
                                class="group/btn inline-flex items-center justify-center gap-2.5 px-6 py-3 
                                    bg-gradient-to-r from-[var(--primary-color)] to-[var(--primary-dark)] 
                                    text-white font-semibold text-base rounded-xl shadow-lg 
                                    hover:shadow-2xl hover:shadow-[var(--primary-glow)]
                                    transform hover:scale-105 active:scale-95 transition-all duration-300 
                                    focus:outline-none focus:ring-4 focus:ring-[var(--primary-glow)]/60
                                    min-w-[150px] relative overflow-hidden">
                                <span class="relative z-10">立即預約</span>
                                <svg class="w-5 h-5 relative z-10 transform group-hover/btn:translate-x-1 transition-transform"
                                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                        d="M9 5l7 7-7 7" />
                                </svg>
                                <span
                                    class="absolute inset-0 bg-white/15 opacity-0 group-hover/btn:opacity-100 transition-opacity duration-500 blur-lg"></span>
                            </a>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-span-full py-24 text-center">
                    <div
                        class="inline-block p-12 bg-white/80 dark:bg-gray-800/80 rounded-3xl backdrop-blur-md border border-gray-200/50 dark:border-gray-700/50 shadow-xl">
                        <p class="text-2xl text-gray-600 dark:text-gray-300 font-light italic">
                            目前尚無服務項目<br>但精彩即將登場，敬請期待！
                        </p>
                    </div>
                </div>
            @endforelse
        </div>
    </section>

    <footer class="bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 py-8 mt-auto">
        <div class="max-w-7xl mx-auto px-4 text-center text-gray-500 dark:text-gray-400 text-sm">
            © {{ date('Y') }} {{ $tenant->name }}. 保留所有權利。
        </div>
    </footer>
</div>
