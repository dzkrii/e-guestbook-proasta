<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Buku Tamu Digital</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-50 font-sans antialiased">

    <nav class="bg-white shadow-sm sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16 items-center">
                <div class="flex-shrink-0 flex items-center gap-2">
                    <div class="bg-indigo-600 p-2 rounded-lg">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253">
                            </path>
                        </svg>
                    </div>
                    <span class="font-bold text-xl text-gray-800 tracking-tight">E-GuestBook</span>
                </div>
                <!-- <div class="flex items-center gap-4">
                    @auth
                        <a href="{{ url('/dashboard') }}"
                            class="text-sm font-medium text-gray-600 hover:text-indigo-600 transition">Dashboard Admin</a>
                    @else
                        <a href="{{ route('login') }}"
                            class="text-sm font-medium text-gray-600 hover:text-indigo-600 transition">Admin Login</a>
                    @endauth
                </div> -->
            </div>
        </div>
    </nav>

    <header class="bg-white border-b">
        <div class="max-w-7xl mx-auto py-16 px-4 sm:px-6 lg:px-8 text-center">
            <h1 class="text-4xl font-extrabold text-gray-900 sm:text-5xl sm:tracking-tight lg:text-6xl">
                Temukan & Ikuti <span class="text-indigo-600">Event Seru</span>
            </h1>
            <p class="mt-5 max-w-xl mx-auto text-xl text-gray-500">
                Pendaftaran cepat, tiket digital instan, dan verifikasi kehadiran tanpa ribet.
            </p>
        </div>
    </header>

    <main class="max-w-7xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between mb-8">
            <h2 class="text-2xl font-bold text-gray-800">Acara Mendatang</h2>
            <span
                class="bg-indigo-100 text-indigo-700 text-xs font-bold px-3 py-1 rounded-full uppercase tracking-wider">
                {{ $events->count() }} Tersedia
            </span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            @forelse($events as $event)
                @php
                    // Hitung sisa kuota di sini biar rapi
                    $terdaftar = \App\Models\Registration::where('event_id', $event->id)->count();
                    $isPenuh = $terdaftar >= $event->quota;
                @endphp

                <div
                    class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-xl transition-shadow duration-300">
                    <div class="p-6">
                        <div class="flex items-center gap-2 mb-4">
                            <span
                                class="bg-gray-100 text-gray-600 text-[10px] font-bold px-2 py-0.5 rounded uppercase tracking-widest">
                                {{ $event->category->name }}
                            </span>
                            @if ($isPenuh)
                                <span
                                    class="bg-red-100 text-red-600 text-[10px] font-bold px-2 py-0.5 rounded uppercase tracking-widest">Penuh</span>
                            @endif
                        </div>

                        <h3 class="text-xl font-bold text-gray-900 mb-2">{{ $event->title }}</h3>

                        <div class="space-y-3 mb-6">
                            <div class="flex items-center text-gray-500 text-sm">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z">
                                    </path>
                                </svg>
                                {{ \Illuminate\Support\Carbon::parse($event->event_date)->isoFormat('D MMMM Y') }}
                            </div>
                            <div class="flex items-center text-gray-500 text-sm">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z">
                                    </path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                </svg>
                                {{ $event->location }}
                            </div>
                        </div>

                        @if ($isPenuh)
                            <button disabled
                                class="block w-full py-3 px-4 rounded-xl font-bold text-center bg-gray-200 text-gray-400 cursor-not-allowed uppercase text-sm">
                                Kuota Penuh
                            </button>
                        @else
                            <a href="{{ route('registration.form', $event->id) }}"
                                class="block w-full py-3 px-4 rounded-xl font-bold text-center bg-indigo-600 text-white hover:bg-indigo-700 shadow-md hover:shadow-indigo-200 transition uppercase text-sm">
                                Daftar Sekarang
                            </a>
                        @endif
                    </div>
                </div>
            @empty
                <div class="col-span-full text-center py-20">
                    <div class="bg-gray-100 inline-block p-4 rounded-full mb-4">
                        <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10l4 4v10a2 2 0 01-2 2zM14 4v4h4"></path>
                        </svg>
                    </div>
                    <p class="text-gray-500 italic">Belum ada acara mendatang yang tersedia.</p>
                </div>
            @endforelse
        </div>
    </main>

    <footer class="bg-gray-900 text-gray-400 py-12 mt-12">
        <div class="max-w-7xl mx-auto px-4 text-center">
            <p>&copy; {{ date('Y') }} Sistem Buku Tamu Digital. Dibuat dengan penuh semangat.</p>
        </div>
    </footer>

</body>

</html>
