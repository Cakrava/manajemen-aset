<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>Registrasi Akun Baru</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.13/css/intlTelInput.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css">
    <style>
        body { font-family: 'Roboto', sans-serif; }
        .iti { width: 100%; }
        .iti__country-list { z-index: 50; }
        .step-indicator .step-circle { transition: background-color 0.3s, color 0.3s; }
        .step-indicator .step-line { transition: background-color 0.3s; }
        .step-indicator.active .step-circle { background-color: #0EA2BC; color: white; }
        .step-indicator.active .step-line { background-color: #0EA2BC; }
        .step-indicator.completed .step-circle { background-color: #10B981; color: white; }
        .step-indicator.completed .step-line { background-color: #10B981; }
        .image-picker-container { position: relative; cursor: pointer; display: inline-block; }
        .image-picker-overlay { position: absolute; top: 0; left: 0; width: 100%; height: 100%; border-radius: 50%; background-color: rgba(0, 0, 0, 0.4); opacity: 0; transition: opacity 0.3s ease; display:flex; justify-content:center; align-items:center; }
        .image-picker-container:hover .image-picker-overlay { opacity: 1; }
        .modal { z-index: 1050; }
        .modal-backdrop { z-index: 1040; }
        .validation-error { border-color: #EF4444 !important; }
        .error-message { color: #EF4444; font-size: 0.875rem; margin-top: 0.25rem; }
    </style>
</head>

<body class="bg-white md:bg-gray-100 flex items-center justify-center min-h-screen py-8 md:py-0">
    <div class="flex flex-col md:flex-row max-w-4xl w-full mx-auto md:bg-white md:rounded-lg md:shadow-lg md:overflow-hidden">
        
        <div class="w-full md:w-1/2 order-first md:order-last">
            <img alt="Welcoming illustration" class="w-full h-64 md:h-full object-cover rounded-t-lg md:rounded-t-none md:rounded-r-lg" src="{{ asset('asset/image/login.png') }}" />
        </div>

        <div class="w-full md:w-1/2 p-6 md:p-8 order-last md:order-first overflow-y-auto" style="max-height: 90vh;">
            <h2 class="text-2xl font-bold mb-2 text-gray-800">Registrasi Akun Baru</h2>
            <p class="text-gray-500 mb-6">Selamat datang! Silakan ikuti langkah-langkah berikut.</p>
            
            <div class="flex items-center mb-8">
                <div id="step-indicator-1" class="step-indicator w-1/4"><div class="step-circle w-8 h-8 mx-auto bg-gray-300 rounded-full text-lg text-white flex items-center justify-center">1</div></div>
                <div class="step-line w-1/4 bg-gray-300 h-1"></div>
                <div id="step-indicator-2" class="step-indicator w-1/4"><div class="step-circle w-8 h-8 mx-auto bg-gray-300 rounded-full text-lg text-white flex items-center justify-center">2</div></div>
                <div class="step-line w-1/4 bg-gray-300 h-1"></div>
                <div id="step-indicator-3" class="step-indicator w-1/4"><div class="step-circle w-8 h-8 mx-auto bg-gray-300 rounded-full text-lg text-white flex items-center justify-center">3</div></div>
                <div class="step-line w-1/4 bg-gray-300 h-1"></div>
                <div id="step-indicator-4" class="step-indicator w-1/4"><div class="step-circle w-8 h-8 mx-auto bg-gray-300 rounded-full text-lg text-white flex items-center justify-center"><i class="fas fa-check"></i></div></div>
            </div>

             @if ($errors->any())
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <strong class="font-bold">Terjadi Kesalahan!</strong>
                    <ul class="mt-2 list-disc list-inside text-sm">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                </div>
            @endif

            <form method="POST" action="{{ route('register.invitation') }}" id="wizardForm">
                @csrf
                <input type="hidden" name="invitation_code" value="{{ $invitation_code }}">
                <input type="hidden" id="reference" name="reference">
                <input type="hidden" name="profile_image" id="profileImageInput">

                <div id="step-1" class="step-content">
                    <p class="font-semibold text-gray-700 mb-4">Langkah 1: Informasi Akun</p>
                    <div class="mb-4"><input id="email" name="email" type="email" required class="w-full px-4 py-2 border rounded-lg" placeholder="Alamat Email Anda" value="{{ old('email') }}" /></div>
                    <div class="mb-4 relative"><input id="password" name="password" type="password" required class="w-full px-4 py-2 border rounded-lg" placeholder="Buat Password Baru (min. 8 karakter)" /><span class="absolute inset-y-0 right-0 flex items-center pr-3 cursor-pointer"><i id="toggle_password" class="fas fa-eye text-gray-400"></i></span></div>
                    <div class="mb-6"><input id="password_confirmation" name="password_confirmation" type="password" required class="w-full px-4 py-2 border rounded-lg" placeholder="Konfirmasi Password" /></div>
                </div>

                <div id="step-2" class="step-content hidden">
                    <p class="font-semibold text-gray-700 mb-4">Langkah 2: Informasi Profil Anda</p>
                    <div class="mb-4"><input id="name" name="name" type="text" required class="w-full px-4 py-2 border rounded-lg" placeholder="Nama Lengkap Anda" value="{{ old('name') }}" /></div>
                    <div class="mb-4"><input id="phone" name="phone" type="tel" class="w-full border rounded-lg" /></div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4"><input id="institution" name="institution" type="text" required class="w-full px-4 py-2 border rounded-lg" placeholder="Nama Institusi" value="{{ old('institution') }}" /><select id="institution_type" name="institution_type" required class="w-full px-4 py-2 border rounded-lg bg-white"><option value="" selected disabled>Tipe Institusi</option><option value="government">Pemerintahan</option><option value="private">Swasta</option><option value="non_profit">Nirlaba</option><option value="education">Pendidikan</option><option value="health">Kesehatan</option><option value="finance">Keuangan</option><option value="technology">Teknologi</option><option value="other">Lainnya</option></select></div>
                    <div class="mb-6"><textarea id="address" name="address" rows="3" class="w-full px-4 py-2 border rounded-lg" placeholder="Mengambil lokasi Anda..." readonly>{{ old('address') }}</textarea></div>
                </div>

                <div id="step-3" class="step-content hidden text-center">
                    <p class="font-semibold text-gray-700 mb-4">Langkah 3: Foto Profil (Opsional)</p>
                    <div class="image-picker-container" onclick="document.getElementById('imageInput').click();">
                        <img id="imagePreview" src="{{ asset('asset/image/profile.png') }}" class="w-40 h-40 mx-auto rounded-full object-cover border-4 border-gray-200" alt="Profile Preview">
                        <div class="image-picker-overlay"><i class="fas fa-camera text-white text-3xl"></i></div>
                    </div>
                    <input type="file" id="imageInput" accept="image/*" class="hidden">
                    <p class="text-sm text-gray-500 mt-2">Ketuk gambar untuk memilih & mengedit foto.</p>
                </div>

                <div id="step-4" class="step-content hidden">
                    <p class="font-semibold text-gray-700 mb-4">Langkah 4: Konfirmasi Data Anda</p>
                    <div class="space-y-3 text-sm text-gray-800 border p-4 rounded-lg bg-gray-50"><div class="flex justify-center mb-4"><img id="preview_image" class="w-24 h-24 rounded-full object-cover" alt="Preview"></div><p><strong>Email:</strong> <span id="preview_email"></span></p><p><strong>Nama:</strong> <span id="preview_name"></span></p><p><strong>Telepon:</strong> <span id="preview_phone"></span></p><p><strong>Institusi:</strong> <span id="preview_institution"></span></p><p><strong>Alamat:</strong> <span id="preview_address"></span></p></div>
                    <p class="text-xs text-center text-gray-500 mt-4">Pastikan semua data sudah benar.</p>
                </div>

                <div class="mt-8 flex justify-between">
                    <button type="button" id="prevBtn" class="bg-gray-300 text-gray-800 py-2 px-6 rounded-lg hidden">Kembali</button>
                    <button type="button" id="nextBtn" class="w-full md:w-auto ml-auto bg-[#0EA2BC] text-white py-2 px-6 rounded-lg">Lanjutkan</button>
                    <button type="submit" id="submitBtn" class="w-full md:w-auto ml-auto bg-green-500 text-white py-2 px-6 rounded-lg hidden">Buat Akun Saya</button>
                </div>
            </form>
        </div>
    </div>
    
    <div id="cropImageModal" class="fixed inset-0 bg-gray-900 bg-opacity-75 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-2xl"><div class="p-2 border-b flex justify-between items-center"><span class="font-semibold">Edit Foto Profil</span><button id="closeCropModal" class="text-gray-500 hover:text-gray-800">×</button></div><div class="p-4"><div class="img-container mb-4" style="height: 400px;"><img id="imageToCrop"></div></div><div class="p-4 border-t flex justify-end gap-2"><button type="button" class="bg-gray-200 text-gray-800 px-4 py-2 rounded-lg" id="cancelCrop">Batal</button><button type="button" class="bg-blue-500 text-white px-4 py-2 rounded-lg" id="cropAndSaveButton">Terapkan</button></div></div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.13/js/intlTelInput.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.13/js/utils.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>
    
    <script>
        // Gunakan event listener DOMContentLoaded untuk memastikan semua elemen siap
        document.addEventListener('DOMContentLoaded', function () {
            
            // --- Variabel & Inisialisasi ---
            let currentStep = 1;
            const totalSteps = 4;
            const wizardForm = document.getElementById('wizardForm');
            const prevBtn = document.getElementById('prevBtn');
            const nextBtn = document.getElementById('nextBtn');
            const submitBtn = document.getElementById('submitBtn');
            const phoneInput = document.querySelector("#phone");
            const iti = window.intlTelInput(phoneInput, { initialCountry: "id", separateDialCode: true, utilsScript: "https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.13/js/utils.js"});

            // --- Fungsi Navigasi Wizard ---
            const showStep = (stepNumber) => {
                document.querySelectorAll('.step-content').forEach(el => el.classList.add('hidden'));
                document.getElementById(`step-${stepNumber}`).classList.remove('hidden');

                prevBtn.classList.toggle('hidden', stepNumber === 1);
                nextBtn.classList.toggle('hidden', stepNumber === totalSteps);
                submitBtn.classList.toggle('hidden', stepNumber !== totalSteps);

                document.querySelectorAll('.step-indicator').forEach((indicator, index) => {
                    indicator.classList.remove('active', 'completed');
                    if (index + 1 < stepNumber) indicator.classList.add('completed');
                    else if (index + 1 === stepNumber) indicator.classList.add('active');
                });
            };

            // --- Fungsi Validasi ---
            const validateStep = (stepNumber) => {
                let isValid = true;
                const stepDiv = document.getElementById(`step-${stepNumber}`);
                stepDiv.querySelectorAll('.error-message').forEach(el => el.remove());

                const inputs = stepDiv.querySelectorAll('input[required], select[required]');
                inputs.forEach(input => {
                    input.classList.remove('validation-error');
                    if (!input.value.trim()) {
                        isValid = false;
                        input.classList.add('validation-error');
                        const errorMsg = document.createElement('p');
                        errorMsg.className = 'error-message';
                        errorMsg.textContent = 'Kolom ini tidak boleh kosong.';
                        input.parentNode.appendChild(errorMsg);
                    }
                });

                if (stepNumber === 1) {
                    const email = document.getElementById('email');
                    const password = document.getElementById('password');
                    const confirmPassword = document.getElementById('password_confirmation');
                    if (!/^\S+@\S+\.\S+$/.test(email.value)) {
                        isValid = false; email.classList.add('validation-error');
                    }
                    if (password.value.length < 8) {
                        isValid = false; password.classList.add('validation-error');
                    }
                    if (password.value !== confirmPassword.value) {
                        isValid = false; confirmPassword.classList.add('validation-error');
                    }
                }
                return isValid;
            };

            // --- Fungsi Update Pratinjau ---
            const updatePreview = () => {
                $('#preview_email').text($('#email').val());
                $('#preview_name').text($('#name').val());
                $('#preview_phone').text(iti.getNumber() || '-');
                $('#preview_institution').text(`${$('#institution').val()} (${$('#institution_type option:selected').text()})`);
                $('#preview_address').text($('#address').val() || '-');
                $('#preview_image').attr('src', $('#imagePreview').attr('src'));
            };

            // --- Event Listeners untuk Tombol ---
            nextBtn.addEventListener('click', () => {
                if (validateStep(currentStep)) {
                    currentStep++;
                    if (currentStep === totalSteps) {
                        updatePreview();
                    }
                    showStep(currentStep);
                }
            });

            prevBtn.addEventListener('click', () => {
                currentStep--;
                showStep(currentStep);
            });

            // --- Logika Cropping Gambar ---
            const cropModal = document.getElementById('cropImageModal');
            const imageToCrop = document.getElementById('imageToCrop');
            const imageInput = document.getElementById('imageInput');
            const hiddenImageInput = document.getElementById('profileImageInput');
            let cropper;

            imageInput.addEventListener('change', (e) => {
                if (e.target.files && e.target.files[0]) {
                    const reader = new FileReader();
                    reader.onload = (event) => {
                        imageToCrop.src = event.target.result;
                        cropModal.classList.remove('hidden');
                        if(cropper) cropper.destroy();
                        cropper = new Cropper(imageToCrop, { aspectRatio: 1, viewMode: 1, background: false });
                    };
                    reader.readAsDataURL(e.target.files[0]);
                }
            });
            
            const closeCropModal = () => {
                cropModal.classList.add('hidden');
                if(cropper) cropper.destroy();
            };

            document.getElementById('cropAndSaveButton').addEventListener('click', () => {
                if (cropper) {
                    const canvas = cropper.getCroppedCanvas({ width: 512, height: 512 });
                    const croppedDataUrl = canvas.toDataURL('image/jpeg', 0.9);
                    $('#imagePreview').attr('src', croppedDataUrl);
                    hiddenImageInput.value = croppedDataUrl;
                    closeCropModal();
                }
            });
            
            document.getElementById('cancelCrop').addEventListener('click', closeCropModal);
            document.getElementById('closeCropModal').addEventListener('click', closeCropModal);

            // --- Logika Lain-lain ---
            wizardForm.addEventListener('submit', () => { if (phoneInput.value.trim()) phoneInput.value = iti.getNumber(); });
            
            const passwordInput = document.getElementById('password');
            const togglePassword = document.getElementById('toggle_password');
            togglePassword.addEventListener('click', () => { passwordInput.type = passwordInput.type === 'password' ? 'text' : 'password'; togglePassword.classList.toggle('fa-eye-slash'); });

            const addressTextarea = document.getElementById('address');
            const referenceInput = document.getElementById('reference');
            const apiKey = "{{ env('API_GEOCODE') }}";
            if ('geolocation' in navigator) {
                navigator.geolocation.getCurrentPosition( async (position) => {
                    const { latitude, longitude } = position.coords;
                    referenceInput.value = `${latitude}, ${longitude}`;
                    if(apiKey) {
                        try {
                            const response = await fetch(`https://geocode.maps.co/reverse?lat=${latitude}&lon=${longitude}&api_key=${apiKey}`);
                            const data = await response.json();
                            addressTextarea.value = data.display_name || '';
                        } catch (e) { console.error('Geocoding failed'); }
                    }
                }, () => { addressTextarea.readOnly = false; addressTextarea.placeholder="Gagal dapatkan lokasi. Isi manual."; });
            } else { addressTextarea.readOnly = false; addressTextarea.placeholder="Browser tidak mendukung Geolocation. Isi manual."; }

            // Inisialisasi Tampilan Awal
            showStep(currentStep);
        });
    </script>
</body>
</html>