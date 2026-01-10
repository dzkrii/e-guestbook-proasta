<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Registration;
use App\Models\StudentDetail;
use Illuminate\Http\Request;
use Carbon\Carbon; // Library untuk manipulasi tanggal (misal: cek H-1)
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Barryvdh\DomPDF\Facade\Pdf; // Library untuk convert HTML ke PDF
use Illuminate\Support\Str; // Library helper string (untuk generate token acak)

/*
 * RegistrationController
 * * Controller ini menangani seluruh alur pendaftaran peserta dari sisi Frontend (Publik).
 * Mulai dari menampilkan form, validasi input, simpan ke database, sampai cetak tiket.
 */
class RegistrationController extends Controller
{
    /*
     * 1. MENAMPILKAN FORM PENDAFTARAN
     * Method ini bertugas mengecek ketersediaan acara sebelum menampilkan form.
     * Ada 2 pengecekan utama: Batas Waktu (H-1) dan Sisa Kuota.
     *
     * @param int $id ID Acara yang dipilih user
     */
    public function showForm($id)
    {
        // Ambil data acara beserta kategorinya
        // findOrFail berfungsi: jika ID tidak ketemu, otomatis error 404
        $event = Event::with('category')->findOrFail($id);

        // --- LOGIKA 1: CEK BATAS WAKTU ---
        // Pendaftaran ditutup HANYA jika acara sudah lewat / sudah mulai
        // Kita menghapus ->subDay() agar peserta bisa daftar sampai detik-detik acara dimulai
        if (now()->greaterThan(Carbon::parse($event->event_date))) {
            // Jika acara sudah lewat
            return view('registration.closed', [
                'title' => 'Pendaftaran Ditutup',
                'message' => 'Mohon maaf, pendaftaran untuk acara ini sudah ditutup karena acara sudah dimulai atau telah berakhir.'
            ]);
        }

        // --- LOGIKA 2: CEK SISA KUOTA ---
        // Menghitung berapa orang yang sudah mendaftar di event ini
        $totalPendaftar = Registration::where('event_id', $id)->count();

        // Jika jumlah pendaftar sudah sama atau melebihi kuota event
        if ($totalPendaftar >= $event->quota) {
            return view('registration.closed', [
                'title' => 'Kuota Penuh',
                'message' => 'Mohon maaf, kuota peserta untuk acara ini sudah terpenuhi. Silakan cek acara menarik lainnya.'
            ]);
        }

        // --- SUKSES ---
        // Jika lolos kedua cek di atas, tampilkan form pendaftaran
        return view('registration.form', compact('event'));
    }

    /*
     * 2. PROSES SIMPAN DATA (STORE)
     * * Menangani pengiriman form (POST). Melakukan validasi inputan,
     * membedakan logic antara Peserta Umum vs Mahasiswa, dan menyimpan ke DB.
     *
     * @param Request $request Data yang dikirim dari form
     */
    public function store(Request $request)
    {
        // Cek dulu ini acara kategori apa
        $event = Event::with('category')->findOrFail($request->event_id);

        // --- TAHAP VALIDASI ---

        // 1. Aturan Validasi Dasar (Wajib untuk semua peserta)
        $rules = [
            'event_id' => 'required',
            'name' => 'required|string|max:255',
            'email' => 'required|email', // Format harus email valid
            'whatsapp' => 'required|numeric', // Harus angka
        ];

        // 2. Aturan Validasi Khusus (Hanya jika kategorinya 'Mahasiswa')
        // Ini fitur dinamis: form minta data tambahan, backend juga validasi data tambahan
        if ($event->category->name == 'Mahasiswa') {
            $rules['nim'] = 'required';
            $rules['university'] = 'required';
            $rules['major'] = 'required';
            $rules['semester'] = 'required|numeric';
        }

        // Jalankan validasi. Jika gagal, otomatis kembali ke form dengan pesan error.
        $request->validate($rules);

        // --- TAHAP PENYIMPANAN KE DATABASE ---

        // 1. Simpan Data Utama ke tabel 'registrations'
        // Kita pakai Str::random(10) untuk membuat kode unik tiket (bukan pakai ID urut)
        // agar lebih aman dan sulit ditebak orang lain.
        $registration = Registration::create($request->only(['event_id', 'name', 'email', 'whatsapp']) + [
            'qr_code_token' => Str::random(10),
        ]);

        // 2. Simpan Data Tambahan ke tabel 'student_details' (Relasi One-to-One)
        // Hanya dijalankan jika kategori event adalah Mahasiswa
        if ($event->category->name == 'Mahasiswa') {
            StudentDetail::create([
                'registration_id' => $registration->id, // Foreign Key ke tabel registrations
                'nim' => $request->nim,
                'university' => $request->university,
                'major' => $request->major,
                'semester' => $request->semester,
            ]);
        }

        // Redirect ke halaman sukses membawa ID registrasi yang baru dibuat
        return redirect()->route('registration.success', $registration->id);
    }

    /*
     * 3. HALAMAN SUKSES
     * * Menampilkan pesan "Terima Kasih" dan ringkasan data pendaftar.
     */
    public function success($id)
    {
        // Ambil data pendaftar + data acaranya (Eager Loading 'event')
        $registration = Registration::with('event')->findOrFail($id);

        return view('registration.success', compact('registration'));
    }

    /*
     * 4. DOWNLOAD TIKET PDF
     * * Generate file PDF berisi tiket dan QR Code.
     * Menggunakan Token Unik ($token) sebagai parameter pencarian demi keamanan,
     * sehingga user tidak bisa mendownload tiket orang lain dengan menebak ID (1, 2, 3...).
     *
     * @param string $token Token unik dari tabel registrations
     */
    public function downloadTicket($token)
    {
        // Cari data berdasarkan kolom 'qr_code_token'
        $registration = Registration::with('event')->where('qr_code_token', $token)->firstOrFail();

        // Load view khusus PDF ('pdf.ticket') dan oper data registration
        $pdf = Pdf::loadView('pdf.ticket', compact('registration'));

        // Set ukuran kertas A5 Landscape (Ukuran standar tiket event/karcis)
        $pdf->setPaper('A5', 'landscape');

        // Force download file PDF dengan nama 'Tiket-[Nama Peserta].pdf'
        return $pdf->download('Tiket-' . $registration->name . '.pdf');
    }
}
