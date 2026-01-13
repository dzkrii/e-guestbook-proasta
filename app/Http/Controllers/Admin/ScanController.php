<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Registration;
use App\Models\Event;
use Carbon\Carbon; // Manipulasi tanggal
use Illuminate\Support\Str; // Helper String (untuk Slug nama file)

/**
 * ScanController
 * * Controller ini menangani operasional utama saat hari-H acara.
 * * Fitur: Scan QR Code (API), Dashboard Statistik, Manajemen Peserta, & Export Laporan.
 */
class ScanController extends Controller
{
    /**
     * 1. HALAMAN SCANNER
     * Menampilkan halaman kamera scanner.
     * Hanya memunculkan acara yang berlangsung HARI INI agar admin tidak salah pilih acara.
     */
    public function index()
    {
        // Filter: whereDate memastikan kita membandingkan tanggal saja (abaikan jam)
        // Carbon::today() mengambil tanggal hari ini pukul 00:00:00
        $events = Event::whereDate('event_date', Carbon::today())->get();

        return view('admin.scan', compact('events'));
    }

    /**
     * 2. PROSES SCAN (API ENDPOINT)
     * * Method ini dipanggil oleh Javascript (AJAX) saat kamera mendeteksi QR Code.
     * * Mengembalikan respon JSON (bukan View) karena halaman tidak boleh reload.
     */
    public function store(Request $request)
    {
        // Validasi input dari scanner
        $request->validate([
            'qr_code_token' => 'required',
            'event_id' => 'required|exists:events,id'
        ]);

        // A. Cari Tiket di Database berdasarkan Token Unik
        $registration = Registration::with('event')->where('qr_code_token', $request->qr_code_token)->first();

        // B. Cek 1: Apakah Tiket Valid? (Ada di DB?)
        if (!$registration) {
            return response()->json([
                'status' => 'error',
                'message' => 'QR Code tidak dikenali / Tidak terdaftar!'
            ]);
        }

        // C. Cek 2: Security Check (Salah Acara?)
        // Mencegah tiket acara A dipakai masuk ke acara B
        if ($registration->event_id != $request->event_id) {
            return response()->json([
                'status' => 'error',
                'message' => 'SALAH ACARA! Tiket ini untuk: ' . $registration->event->title
            ]);
        }

        // D. Cek 3: Double Entry (Sudah Masuk?)
        // Mencegah satu tiket dipakai berulang kali oleh orang berbeda
        if ($registration->is_attended) {
            return response()->json([
                'status' => 'error',
                'message' => 'Peserta ' . $registration->name . ' SUDAH MASUK sebelumnya pada jam ' . Carbon::parse($registration->attended_at)->format('H:i')
            ]);
        }

        // Hitung urutan kedatangan
        $arrivalOrder = Registration::where('event_id', $registration->event_id)
            ->where('is_attended', true)
            ->max('arrival_order') + 1; // Pakai max + 1 lebih aman daripada count + 1 menghindari duplikat jika ada yang dihapus

        // E. SUKSES: Update Status Kehadiran
        $registration->update([
            'is_attended' => true,
            'attended_at' => now(), // Catat waktu scan
            'arrival_order' => $arrivalOrder
        ]);

        // Kirim respon sukses ke Frontend biar layar jadi Hijau
        return response()->json([
            'status' => 'success',
            'message' => 'Berhasil! ' . $registration->name . ' (Data Terverifikasi)'
        ]);
    }

    // --- BAGIAN DASHBOARD & LAPORAN ---

    /**
     * 3. DASHBOARD ADMIN
     * Menampilkan ringkasan statistik (Total Pendaftar vs Total Hadir).
     */
    public function dashboard()
    {
        // withCount: Menghitung jumlah relasi tanpa meload semua datanya (Efisien Memory)
        // Kita hitung total pendaftar DAN total yang sudah scan (attended)
        $events = Event::withCount([
            'registrations as total_registrants',
            'registrations as total_attended' => function ($query) {
                $query->where('is_attended', true);
            }
        ])->orderBy('event_date', 'desc')->get();

        return view('dashboard', compact('events'));
    }

    /**
     * 4. DAFTAR PESERTA (MANAJEMEN DATA)
     * Menampilkan tabel semua peserta dengan fitur PENCARIAN & PAGINATION.
     */
    public function participants(Request $request)
    {
        $search = $request->input('search');

        // Query Kompleks: Mengambil Event beserta Pesertanya, tapi Pesertanya difilter dulu
        $events = Event::with([
            'registrations' => function ($query) use ($search) {
                // Jika admin mengetik di kolom cari (search), filter nama/email peserta
                if ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                }
                $query->orderByRaw('arrival_order IS NULL ASC, arrival_order ASC');
            }
        ])
            ->orderBy('event_date', 'desc')
            ->paginate(10); // Batasi 10 Acara per halaman (Angka 1 tadi kekecilan bro hehe)

        return view('admin.participants', compact('events'));
    }

    /**
     * 5. EXPORT LAPORAN PER ACARA (CSV)
     * * Menggunakan method 'Stream' agar server tidak crash jika download ribuan data.
     */
    public function exportByEvent($eventId)
    {
        $event = Event::findOrFail($eventId);

        // Buat nama file rapi (contoh: Laporan-Seminar-IT-20-10-2023.csv)
        $filename = "Laporan-" . Str::slug($event->title) . "-" . date('d-m-Y') . ".csv";

        $headers = [
            "Content-Type" => "text/csv",
            "Content-Disposition" => "attachment; filename=\"$filename\"",
        ];

        $columns = ['Nama Peserta', 'Email', 'WhatsApp', 'Status Kehadiran', 'Waktu Hadir'];

        // Callback Stream: Menulis data baris per baris ke file output
        $callback = function () use ($columns, $event) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns); // Tulis Header CSV

            foreach ($event->registrations as $reg) {
                fputcsv($file, [
                    $reg->name,
                    $reg->email,
                    "'" . $reg->whatsapp, // TRIK: Tambah tanda kutip (') biar Excel baca WA sebagai Text (bukan angka ilmiah 8.12E+10)
                    $reg->is_attended ? 'HADIR' : 'BELUM HADIR',
                    $reg->attended_at ? $reg->attended_at->format('d/m/Y H:i') : '-'
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * 6. EXPORT SEMUA DATA (GLOBAL)
     * Versi export gabungan semua event.
     */
    public function export()
    {
        $filename = "laporan-kehadiran-total-" . date('d-m-Y') . ".csv";
        $headers = [
            "Content-Type" => "text/csv",
            "Content-Disposition" => "attachment; filename=\"$filename\"",
            "Pragma" => "no-cache",
            "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
            "Expires" => "0"
        ];
        $columns = ['Nama Peserta', 'Email', 'WhatsApp', 'Acara', 'Status', 'Waktu Hadir'];

        $callback = function () use ($columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            // Ambil semua data pendaftaran urut terbaru
            $registrations = Registration::with('event')->orderBy('created_at', 'desc')->get();

            foreach ($registrations as $reg) {
                fputcsv($file, [
                    $reg->name,
                    $reg->email,
                    "'" . $reg->whatsapp,
                    $reg->event ? $reg->event->title : 'Tanpa Acara',
                    $reg->is_attended ? 'HADIR' : 'BELUM',
                    $reg->attended_at ? $reg->attended_at->format('d M Y H:i') : '-'
                ]);
            }
            fclose($file);
        };
        return response()->stream($callback, 200, $headers);
    }

    // === FITUR EDIT & HAPUS PESERTA (MANUAL) ===

    // Tampilkan Form Edit
    public function editParticipant($id)
    {
        $registration = Registration::findOrFail($id);
        return view('admin.participants.edit', compact('registration'));
    }

    // Simpan Perubahan
    public function updateParticipant(Request $request, $id)
    {
        $registration = Registration::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'whatsapp' => 'required',
        ]);

        // Logika penanganan status kehadiran & arrival_order
        $isAttended = $request->has('is_attended') ? true : false;
        $updateData = [
            'name' => $request->name,
            'email' => $request->email,
            'whatsapp' => $request->whatsapp,
            'is_attended' => $isAttended,
        ];

        // Jika status berubah jadi Hadir (dan sebelumnya belum)
        if ($isAttended && !$registration->is_attended) {
            $updateData['attended_at'] = now();
            $updateData['arrival_order'] = Registration::where('event_id', $registration->event_id)
                ->where('is_attended', true)
                ->max('arrival_order') + 1;
        }
        // Bunda status berubah jadi Tidak Hadir (dan sebelumnya hadir)
        elseif (!$isAttended && $registration->is_attended) {
            $updateData['attended_at'] = null;
            $updateData['arrival_order'] = null;
        }

        $registration->update($updateData);

        return redirect()->route('admin.participants')->with('success', 'Data peserta berhasil diperbarui.');
    }

    // Hapus Peserta
    public function destroyParticipant($id)
    {
        $registration = Registration::findOrFail($id);
        $name = $registration->name; // Simpan nama dulu buat pesan sukses

        $registration->delete();

        return redirect()->back()->with('success', "Peserta atas nama $name berhasil dihapus.");
    }
}
