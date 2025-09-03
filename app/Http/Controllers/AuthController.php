<?php

namespace App\Http\Controllers;

use App\Models\Invitation;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use SebastianBergmann\CodeUnit\FunctionUnit;
use Illuminate\Support\Facades\DB;

use Illuminate\Validation\ValidationException; 
use Illuminate\Support\Facades\Storage;

use Illuminate\Support\Facades\Log;
class AuthController extends Controller
{

    //login section
    public function login(){
        // Cek apakah pengguna sudah login
        if (Auth::check()) {
            return redirect()->route('panel.dashboard'); // Arahkan ke dashboard jika sudah login
        }
        return view('page.auth.login');

        
    }
    public function logout(){
        Auth::logout();
        Session::flush();
        return redirect()->route('front.index');
    }

    public function authenticate(Request $request)
    {
        // 1. Validasi input seperti sebelumnya
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|min:8',
        ]);
    
        // 2. Coba untuk mengautentikasi pengguna
        // Auth::attempt akan otomatis melakukan hashing pada password dan membandingkannya
        if (Auth::attempt($credentials)) {
            
            // 3. Cek status pengguna SETELAH kredensialnya valid
            $user = Auth::user();
            if ($user->status === 'deleted') {
                // Jika statusnya 'deleted', logout kembali dan kirim pesan error
                Auth::logout();
                
                // Menggunakan ValidationException untuk mengirim pesan error kembali ke form
                throw ValidationException::withMessages([
                    'email' => 'Akun Anda tidak aktif atau telah dihapus.',
                ]);
            }
    
            // 4. Regenerasi session untuk keamanan dan redirect ke dashboard
            $request->session()->regenerate();
            session()->put('role',$user->role);
            
            return redirect()->route('panel.dashboard'); // Menggunakan intended() lebih baik
        }
    
        // 5. Jika autentikasi gagal (email atau password salah)
        // Kirim pesan error yang lebih umum untuk keamanan
        throw ValidationException::withMessages([
            'email' => 'Kredensial yang Anda masukkan tidak cocok dengan data kami.',
        ]);
    }

    public function showRegistrationForm(Request $request)
    {
        // 1. Validasi awal: Pastikan 'code' ada dan tidak kosong di dalam request.
        if (!$request->has('code') || !$request->code) {
            // Redirect dengan pesan error jika parameter kode tidak ada di URL.
            return redirect()->route('auth.login')
                ->with('error', 'Link registrasi tidak valid atau tidak lengkap.');
        }
    
        // 2. Cari kode undangan di database berdasarkan kode dari request.
        $invitation = Invitation::where('code', $request->code)->first();
    
        // 3. Jika kode tidak ditemukan di database, redirect kembali dengan pesan error.
        if (!$invitation) {
            // Metode yang benar adalah menyertakan pesan flash LANGSUNG ke redirect.
            return redirect()->route('auth.login')
                   ->with('error', 'Kode Undangan tidak valid atau sudah digunakan.');
        }
    
        // 4. Jika kode valid dan ditemukan, tampilkan halaman registrasi
        //    dan kirimkan kode undangan ke view tersebut.
        return view('page.auth.register', ['invitation_code' => $request->code]);
    }

    public function register(Request $request)
    {
        $validatedData = $request->validate([
            'invitation_code' => 'required|string|exists:invitations,code',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string',
            'institution' => 'required|string|max:255',
            'institution_type' => 'required|string',
            'address' => 'nullable|string|max:255',
            'reference' => 'nullable|string|max:255',
            // [BARU] Validasi untuk gambar dari cropper (base64)
            'profile_image' => [
                'nullable',
                'string',
                function ($attribute, $value, $fail) {
                    if (!preg_match('/^data:image\/(jpeg|png|jpg);base64,/', $value)) {
                        $fail('Format gambar dari cropper tidak valid.'); return;
                    }
                    $decodedData = base64_decode(last(explode(',', $value)));
                    if ((strlen($decodedData) / 1024) > 2048) { // Cek ukuran 2MB
                        $fail('Ukuran gambar tidak boleh lebih dari 2048 KB.');
                    }
                },
            ],
        ]);

        DB::beginTransaction();
        try {
            // Langkah 1: Buat User
            $user = User::create([
                'email' => $validatedData['email'],
                'password' => Hash::make($validatedData['password']),
                'confirm_password' => Hash::make($validatedData['password']),
                'role' => 'user',
                'status' => 'active',
            ]);

            // [BARU] Logika Pemrosesan Gambar
            $imagePath = null;
            if ($request->filled('profile_image') && is_string($request->input('profile_image'))) {
                $imageData = $request->input('profile_image');
                @list($type, $imageData) = explode(';', $imageData);
                @list(, $imageData) = explode(',', $imageData);
                $imageData = base64_decode($imageData);
                
                $imageName = 'profile_images/' . Str::random(40) . '.jpg';
                Storage::disk('public')->put($imageName, $imageData);
                $imagePath = $imageName;
            }

            // Langkah 2: Buat Profil dengan path gambar
            Profile::create([
                'user_id'        => $user->id,
                'name'           => $validatedData['name'],
                'phone'          => $validatedData['phone'],
                'institution'    => $validatedData['institution'],
                'institution_type' => $validatedData['institution_type'],
                'address'        => $validatedData['address'],
                'reference'      => $validatedData['reference'] ?? null,
                'image'          => $imagePath, // Simpan path gambar ke database
            ]);

            // Langkah 3: Hapus kode undangan
            Invitation::where('code', $validatedData['invitation_code'])->delete();
            
            DB::commit();

            Auth::login($user);
            Session::put('role', $user->role);
            return redirect()->route('panel.dashboard')->with('success', 'Pendaftaran berhasil! Selamat datang.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Pendaftaran gagal: ' . $e->getMessage());
            return redirect()->back()->withInput()->with('error', 'Terjadi kesalahan saat pendaftaran.');
        }
    }
}
