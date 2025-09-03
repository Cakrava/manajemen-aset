<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>Verifikasi Kode Undangan</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet" />
    <style>
        body { font-family: 'Roboto', sans-serif; }
    </style>
</head>

<body class="bg-white md:bg-gray-100 flex items-center justify-center min-h-screen py-8 md:py-0">
    <div class="flex flex-col md:flex-row max-w-4xl w-full mx-auto md:bg-white md:rounded-lg md:shadow-lg md:overflow-hidden">

        <!-- Gambar -->
        <div class="w-full md:w-1/2 order-first md:order-last bg-transparent md:bg-transparent rounded-t-lg md:rounded-t-none">
            <img alt="Office discussion scene" class="w-full h-64 md:h-full object-cover rounded-t-lg md:rounded-t-none md:rounded-r-lg" src="{{ asset('asset/image/login.png') }}" />
        </div>

        <div class="w-full md:w-1/2 p-8 order-last md:order-first bg-transparent md:bg-transparent rounded-b-lg md:rounded-b-none md:rounded-l-lg">
            <!-- Form Verifikasi Kode -->
            <div>
                <h2 class="text-2xl font-bold mb-4 text-gray-800">Verifikasi Undangan</h2>
                <p style="color: gray">Silakan masukkan kode undangan yang Anda terima untuk melanjutkan pendaftaran.</p>
                <br>
                <form method="GET" action="{{ route('register.invitation') }}">
                    {{-- @csrf tidak diperlukan untuk form GET --}}

                    {{-- Menampilkan pesan error dari redirect --}}
                    @if (session('error'))
                        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                            <strong class="font-bold">Error!</strong>
                            <p class="block sm:inline">{{ session('error') }}</p>
                        </div>
                    @endif
                    
                    <div class="mb-4 relative">
                        <label class="block text-gray-700">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3">
                                <i class="fas fa-ticket-alt text-gray-500"></i>
                            </span>
                            <input id="code" name="code" type="text" required autofocus
                                class="w-full pl-10 px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-600 uppercase"
                                placeholder="Masukkan Kode Undangan" value="{{ old('code') }}" />
                        </label>
                    </div>

                    <div class="mb-4">
                        <button type="submit"
                            class="w-full bg-[#0EA2BC] text-white py-2 rounded-lg hover:bg-blue-400 focus:outline-none focus:ring-2 focus:ring-[#0EA2BC]">
                            VERIFIKASI KODE
                        </button>
                    </div>

                    <div class="text-center mt-6">
                        <p class="text-sm text-gray-600">Sudah punya akun? <a href="{{ route('auth.login') }}" class="text-blue-600 hover:underline">Login di sini</a></p>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>

</html>