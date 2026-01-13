<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Daftar Peserta
            </h2>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="flex justify-between items-center">
                <form action="{{ route('admin.participants') }}" method="GET" class="flex gap-2">
                    <input type="text" name="search" value="{{ request('search') }}"
                        placeholder="Cari nama atau email..."
                        class="rounded-lg border-gray-300 focus:ring-indigo-500 focus:border-indigo-500 shadow-sm text-sm w-64">
                    <button type="submit"
                        class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg font-bold text-sm transition">
                        Cari
                    </button>
                    @if (request('search'))
                        <a href="{{ route('admin.participants') }}"
                            class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg font-bold text-sm transition">
                            Reset
                        </a>
                    @endif
                </form>
            </div><br>
            @if ($events->isEmpty())
                <div class="bg-white p-6 rounded-lg text-center shadow">
                    <p class="text-gray-500">Belum ada data peserta karena belum ada acara.</p>
                </div>
            @endif

            <div class="space-y-6">
                @foreach ($events as $event)
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-gray-200">
                        <div
                            class="p-6 bg-gray-50 border-b border-gray-200 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                            <div>
                                <h3 class="text-lg font-bold text-gray-800">{{ $event->title }}</h3>
                                <p class="text-sm text-gray-500">
                                    {{ \Carbon\Carbon::parse($event->event_date)->format('d M Y') }} |
                                    <span class="font-semibold text-indigo-600">{{ $event->registrations->count() }}
                                        Pendaftar</span>
                                </p>
                            </div>

                            <a href="{{ route('admin.export.event', $event->id) }}"
                                class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 transition shadow-sm">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M4 16v1a2 2 0 002 2h12a2 2 0 002-2v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                                </svg>
                                Export CSV (.csv)
                            </a>
                        </div>

                        <div class="p-6 overflow-x-auto">
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="text-xs font-semibold text-gray-500 uppercase border-b bg-white">
                                        <th class="px-4 py-3">Nama Peserta</th>
                                        <th class="px-4 py-3">Kontak</th>
                                        <th class="px-4 py-3 text-center">Status</th>
                                        <th class="px-4 py-3 text-center">No. Urut</th>
                                        <th class="px-4 py-3">Waktu Hadir</th>
                                        <th class="px-4 py-3 text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @forelse($event->registrations as $reg)
                                        <tr class="hover:bg-gray-50 transition">
                                            <td class="px-4 py-4">
                                                <div class="font-bold text-gray-700">{{ $reg->name }}</div>
                                                <div class="text-xs text-gray-400">{{ $reg->email }}</div>
                                            </td>
                                            <td class="px-4 py-4 text-sm text-gray-600">
                                                {{ $reg->whatsapp }}
                                            </td>
                                            <td class="px-4 py-4 text-center">
                                                @if ($reg->is_attended)
                                                    <span
                                                        class="px-2 py-1 text-xs font-bold leading-tight text-green-700 bg-green-100 rounded-full">Hadir</span>
                                                @else
                                                    <span
                                                        class="px-2 py-1 text-xs font-bold leading-tight text-red-700 bg-red-100 rounded-full">Belum
                                                        Hadir</span>
                                                @endif
                                            </td>
                                            <td class="px-4 py-4 text-center text-sm font-bold text-gray-800">
                                                {{ $reg->arrival_order ? '#' . $reg->arrival_order : '-' }}
                                            </td>
                                            <td class="px-4 py-4 text-sm text-gray-500">
                                                {{ $reg->attended_at ? $reg->attended_at->format('H:i, d M') : '-' }}
                                            </td>

                                            <td class="px-4 py-4 text-center">
                                                <div class="flex item-center justify-center space-x-2">
                                                    <a href="{{ route('admin.participants.edit', $reg->id) }}"
                                                        class="text-gray-400 hover:text-amber-500 transition duration-200"
                                                        title="Edit">
                                                        <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                                            viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z">
                                                            </path>
                                                        </svg>
                                                    </a>

                                                    <form action="{{ route('admin.participants.destroy', $reg->id) }}"
                                                        method="POST"
                                                        onsubmit="return confirm('Yakin ingin menghapus peserta ini? Data tidak bisa dikembalikan.');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit"
                                                            class="btn-delete text-gray-400 hover:text-red-600 transition duration-200"
                                                            title="Hapus">
                                                            <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                                                viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="2"
                                                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                                                </path>
                                                            </svg>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endforeach
                <div class="mt-8">
                    {{ $events->appends(['search' => request('search')])->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
