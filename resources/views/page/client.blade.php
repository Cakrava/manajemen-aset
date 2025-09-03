@extends('layout.sidebar')
@section('content')

    <!-- [Page specific CSS] start -->
    <link rel="stylesheet" href="{{ asset('assets/css/plugins/dataTables.bootstrap5.min.css') }}">
    {{-- CSS untuk intl-tel-input --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.13/css/intlTelInput.css">
    <!-- [Page specific CSS] end -->

    <div class="pc-container">
        <div class="pc-content">
            <!-- [ breadcrumb ] start -->
            <div class="page-header">
                <div class="page-block">
                    <div class="row align-items-center">
                        <div class="col-md-12">
                            <ul class="breadcrumb">
                                <li class="breadcrumb-item"><a href="{{ route('panel.dashboard') }}">Dashboard</a></li>
                                <li class="breadcrumb-item">Client</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            <!-- [ breadcrumb ] end -->

            <div class="col-sm-12">
                <div class="card">
                    <div class="card-header" style="margin-bottom : -15px">
                        <h5>Data Client</h5>
                        <small class="text-muted">Data ini berisi daftar client yang terdaftar di sistem. Saat ini</small>
                        <small class="" style="color: rgb(8, 0, 255)">
                            {{ count($clients) }}
                            client </small><small class="text-muted">telah tercatat.</small>

                        <div class="mt-3">
                            @if (!session()->has('profile_incomplete'))
                                {{-- [PERUBAHAN] Tombol ini sekarang membuka modal pilihan --}}
                                <button type="button" class="btn btn-primary" style="margin-bottom: -10px"
                                    data-bs-toggle="modal" data-bs-target="#addClientModal">
                                    <i class="ti ti-plus"></i> Add Client
                                </button>
                                <button type="button" class="btn btn-danger" style="margin-bottom: -10px" id="btn-bulk-delete" disabled>
                                    <i class="ti ti-trash"></i> Bulk Delete
                                </button>
                            @endif

                            @if (session()->has('warning'))
                                <div class="alert alert-warning" style="margin-top: 20px; position: relative;">
                                    <p>⚠️ <strong>Beberapa Data Client Terlihat Serupa!</strong></p>
                                    {!! session('warning') !!}
                                </div>
                            @endif
                            
                            @if (session()->has('profile_incomplete'))
                                <div class="alert alert-primary" style="margin-top: 20px; margin-bottom : -20px">
                                    {!! session('profile_incomplete') !!}
                                </div>
                            @endif
                            <div style="clear: both;"></div>
                        </div>
                    </div>

                    <div class="card-body">
                        <div class="dt-responsive">
                            <table id="dom-jqry" class="table table-striped table-bordered nowrap">
                                <thead>
                                    <tr>
                                        <th>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="selectAllCheckbox">
                                            </div>
                                        </th>
                                        <th>Name</th>
                                        <th>Phone</th>
                                        <th>Institution</th>
                                        <th>Type</th>
                                        <th>Address</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php $highlighted = false; @endphp
                                    @foreach($clients as $client)
                                        @php
                                            $isRecentUpdate = false;
                                            if (!$highlighted && $client->updated_at && $client->updated_at->diffInSeconds(\Carbon\Carbon::now()) <= 30) {
                                                $isRecentUpdate = true;
                                                $highlighted = true;
                                            }
                                        @endphp
                                        <tr class="{{ $isRecentUpdate ? 'highlight-row' : '' }}">
                                            <td>
                                                <div class="form-check">
                                                    <input class="form-check-input client-checkbox" type="checkbox" value="{{ $client->id }}">
                                                </div>
                                            </td>
                                            <td>{{ $client->name }}</td>
                                            <td>{{ $client->phone }}</td>
                                            <td>{{ $client->institution }}</td>
                                            <td>{{ $institutionTypeNames[$client->institution_type] ?? ucfirst($client->institution_type) }}</td>
                                            <td>{{ Str::limit($client->address, 10) }}</td>
                                            <td>
                                                <div class="d-flex gap-2">
                                                    <button type="button" class="btn btn-sm btn-success" style="border-radius: 5px;" data-reference="{{ $client->reference }}" onclick="openGoogleMapsRoute(this)">
                                                        <i class="ti ti-map"></i>
                                                    </button>
                                                    {{-- [PERUBAHAN] Tombol update sekarang membuka modal khusus update --}}
                                                    <button type="button" class="btn btn-sm btn-primary btn-update-client" style="border-radius: 5px;" data-bs-toggle="modal" data-bs-target="#updateClientModal" data-id="{{ $client->id }}">
                                                        <i class="ti ti-pencil"></i>
                                                    </button>
                                                    <form class="form-delete-client d-inline" action="{{ route('panel.client.destroy', $client->id) }}" method="POST">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="button" class="btn btn-sm btn-danger btn-delete-client" style="border-radius: 5px;">
                                                            <i class="ti ti-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ========================================================================================= -->
    <!-- [MODAL BARU] Untuk Menambah Client dengan Pilihan Alur Kerja -->
    <!-- ========================================================================================= -->
    <div class="modal fade" id="addClientModal" tabindex="-1" aria-labelledby="addClientModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addClientModalLabel">Add New Client</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    
                    <!-- Div untuk Pilihan Awal -->
                    <div id="choice-container">
                        <p class="text-muted">Pilih metode pendaftaran untuk client baru:</p>
                        <div class="d-grid gap-2">
                            <button type="button" class="btn btn-outline-primary" id="btn-generate-invitation">
                                <i class="ti ti-mail-forward me-1"></i> Generate Invitation Code
                                <small class="d-block">Untuk client yang mendaftar sendiri (mandiri).</small>
                            </button>
                            <button type="button" class="btn btn-primary" id="btn-register-directly">
                                <i class="ti ti-user-plus me-1"></i> Register Client Directly
                                <small class="d-block">Untuk client yang datang langsung (dibantu).</small>
                            </button>
                        </div>
                    </div>

                    <!-- Div untuk Menampilkan Kode Undangan -->
                    <div id="invitation-code-container" class="d-none text-center">
                        <p>Berikan info berikut kepada client Anda untuk registrasi:</p>
                        <div class="alert alert-success">
                            <label class="form-label d-block text-start">Kode Undangan:</label>
                            <h4 class="fw-bold" id="invitation-code-display" style="letter-spacing: 2px;"></h4>
                        </div>
                        <button type="button" class="btn btn-secondary" id="btn-copy-code">
                            <i class="ti ti-clipboard"></i> Copy Info
                        </button>
                        <small class="d-block mt-2 text-muted">Kode ini hanya dapat digunakan satu kali.</small>
                    </div>

                    <!-- Div untuk Form Pendaftaran Langsung oleh Admin -->
                    <div id="direct-registration-container" class="d-none">
                        <form id="directClientForm" action="{{ route('panel.client.store') }}" method="POST">
                            @csrf
                            <div class="row">
                                <div class="col-md-7 mb-3">
                                    <label for="email" class="form-label">Client Email</label>
                                    <input type="email" class="form-control form-control-sm" id="email" name="email" required>
                                </div>
                                <div class="col-md-5 mb-3">
                                    <label for="password" class="form-label">Initial Password</label>
                                    <input type="password" class="form-control form-control-sm" id="password" name="password" required>
                                </div>
                            </div>
                            <hr>
                            <div class="row">
                                <div class="col-7 mb-3">
                                    <label for="direct_name" class="form-label">Name</label>
                                    <input type="text" class="form-control form-control-sm" id="direct_name" name="name" required>
                                </div>
                                <div class="col-5 mb-3">
                                    <label for="direct_phone" class="form-label">Phone</label>
                                    <input type="tel" class="form-control form-control-sm w-100" id="direct_phone" name="phone">
                                    {{-- [MODIFIKASI] Pesan peringatan untuk nomor tidak valid --}}
                                    <div id="direct-phone-warning" class="text-danger small mt-1 d-none">Nomor telepon tidak valid.</div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-7 mb-3">
                                    <label for="direct_institution" class="form-label">Institution</label>
                                    <input type="text" class="form-control form-control-sm" id="direct_institution" name="institution" required>
                                </div>
                                <div class="col-5 mb-3">
                                    <label for="direct_institution_type" class="form-label">Type</label>
                                    <select class="form-select form-select-sm" id="direct_institution_type" name="institution_type" required>
                                        <option value="" selected disabled>Pilih</option>
                                        <option value="government">Pemerintahan</option>
                                        <option value="private">Swasta</option>
                                        <option value="non_profit">Nirlaba</option>
                                        <option value="education">Pendidikan</option>
                                        <option value="health">Kesehatan</option>
                                        <option value="finance">Keuangan</option>
                                        <option value="technology">Teknologi</option>
                                        <option value="other">Lainnya</option>
                                    </select>
                                </div>
                            </div>
                            <hr class="my-2">
                            <div class="mb-3">
                                <label for="direct_address" class="form-label">Address</label>
                                <textarea class="form-control form-control-sm" id="direct_address" name="address" readonly  rows="2"></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="direct_reference" class="form-label">Reference / Coordinates</label>
                                <div class="input-group input-group-sm">
                                    <input type="text" class="form-control form-control-sm" id="direct_reference" name="reference">
                                    <button class="btn btn-outline-secondary" type="button" onclick="window.open('https://www.google.com/maps?hl=id', '_blank');">
                                        <i class="ti ti-map-pin"></i> Buka Peta
                                    </button>
                                    <button class="btn btn-outline-secondary btn-refresh-geocode" type="button" title="Get Address from Coordinates">
                                        <i class="ti ti-refresh"></i>
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-light d-none me-auto" id="btn-back-to-choice">
                        <i class="ti ti-arrow-left"></i> Kembali
                    </button>
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    {{-- [MODIFIKASI] Tombol dibuat 'disabled' secara default --}}
                    <button type="button" class="btn btn-sm btn-primary d-none" id="saveDirectClientBtn" disabled>Register Client</button>
                </div>
            </div>
        </div>
    </div>

    <!-- ========================================================================================= -->
    <!-- [MODAL UPDATE] Modal ini khusus untuk meng-update data client yang sudah ada -->
    <!-- ========================================================================================= -->
    <div class="modal fade" id="updateClientModal" tabindex="-1" aria-labelledby="updateClientModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="updateClientModalLabel">Update Client</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="updateClientForm" action="{{ route('panel.client.update') }}" method="POST">
                        @csrf
                        <input type="hidden" name="client_id" id="update_client_id">

                        <div class="row">
                            <div class="col-7 mb-3">
                                <label for="update_name" class="form-label">Name</label>
                                <input type="text" class="form-control form-control-sm" id="update_name" name="name" required>
                            </div>
                            <div class="col-5 mb-3">
                                <label for="update_phone" class="form-label">Phone</label>
                                <input type="tel" class="form-control form-control-sm w-100" id="update_phone" name="phone">
                                {{-- [MODIFIKASI] Pesan peringatan untuk nomor tidak valid --}}
                                <div id="update-phone-warning" class="text-danger small mt-1 d-none">Nomor telepon tidak valid.</div>
                            </div>
                        </div>
                        <div class="row">
                             <div class="col-7 mb-3">
                                <label for="update_institution" class="form-label">Institution</label>
                                <input type="text" class="form-control form-control-sm" id="update_institution" name="institution" required>
                            </div>
                            <div class="col-5 mb-3">
                                <label for="update_institution_type" class="form-label">Type</label>
                                <select class="form-select form-select-sm" id="update_institution_type" name="institution_type" required>
                                    <option value="" disabled>Pilih</option>
                                    <option value="government">Pemerintahan</option>
                                    <option value="private">Swasta</option>
                                    <option value="non_profit">Nirlaba</option>
                                    <option value="education">Pendidikan</option>
                                    <option value="health">Kesehatan</option>
                                    <option value="finance">Keuangan</option>
                                    <option value="technology">Teknologi</option>
                                    <option value="other">Lainnya</option>
                                </select>
                            </div>
                        </div>
                        <hr class="my-2">
                        <div class="mb-3">
                            <label for="update_address" class="form-label">Address</label>
                            <textarea class="form-control form-control-sm" id="update_address" name="address" rows="2" placeholder="Alamat akan terisi otomatis dari koordinat..." readonly></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="update_reference" class="form-label">Reference / Coordinates</label>
                            <div class="input-group input-group-sm">
                                <input type="text" class="form-control form-control-sm" id="update_reference" name="reference">
                                <button class="btn btn-outline-secondary" type="button" onclick="window.open('https://www.google.com/maps?hl=id', '_blank');">
                                    <i class="ti ti-map-pin"></i> Buka Peta
                                </button>
                                <button class="btn btn-outline-secondary btn-refresh-geocode" type="button" title="Get Address from Coordinates">
                                    <i class="ti ti-refresh"></i>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    {{-- [MODIFIKASI] Tombol dibuat 'disabled' secara default --}}
                    <button type="button" class="btn btn-sm btn-primary" id="saveUpdateClientBtn" disabled>Save Changes</button>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function openGoogleMapsRoute(button) {
            let reference = button.getAttribute('data-reference');
            if (reference) {
                let url = `https://www.google.com/maps/dir/?api=1&destination=${reference}`;
                window.open(url, '_blank');
            }
        }
    </script>

    {{-- SweetAlert2 CDN --}}
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- [Page Specific JS] start -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="{{ asset('asset/dist/assets/js/plugins/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('asset/dist/assets/js/plugins/dataTables.bootstrap5.min.js') }}"></script>
    
    {{-- JS untuk intl-tel-input --}}
    <script src="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.13/js/intlTelInput.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.13/js/utils.js"></script>

<script>
    $(document).ready(function () {

        async function getAddressFromGeocode(referenceInput, addressTextarea) {
            const apiKey = "{{ env('API_GEOCODE') }}";
            const coordinates = referenceInput.val();
            
            addressTextarea.val('');
            addressTextarea.attr('placeholder', 'Mengambil data alamat...');
            
            if (!coordinates) {
                addressTextarea.attr('placeholder', 'Koordinat kosong!');
                return;
            }
            
            const [lat, lon] = coordinates.split(",").map(coord => coord.trim());

            if (!lat || !lon || isNaN(lat) || isNaN(lon)) {
                addressTextarea.attr('placeholder', 'Format koordinat tidak valid!');
                return;
            }

            const url = `https://geocode.maps.co/reverse?lat=${lat}&lon=${lon}&api_key=${apiKey}`;

            try {
                const response = await fetch(url);
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                const data = await response.json();

                if (data.error) {
                    addressTextarea.val(`Error: ${data.error}`);
                } else {
                    addressTextarea.val(data.display_name || "Alamat tidak ditemukan.");
                }
            } catch (error) {
                addressTextarea.val("Gagal mengambil data alamat. Cek koneksi atau API key.");
                console.error("Geocoding error:", error);
            }
        }

        $(document).on('click', '.btn-refresh-geocode', function() {
            const form = $(this).closest('form, .modal-body');
            const referenceInput = form.find('input[name="reference"]');
            const addressTextarea = form.find('textarea[name="address"]');

            if (referenceInput.length > 0 && addressTextarea.length > 0) {
                getAddressFromGeocode(referenceInput, addressTextarea);
            } else {
                console.error('Tidak dapat menemukan input reference atau textarea address.');
            }
        });

        var table = $('#dom-jqry').DataTable({
            "dom": '<"row justify-content-between"<"col-md-6"l><"col-md-6 text-end"f>>rt<"row"<"col-md-6"i><"col-md-6 text-end"p>>',
            "columnDefs": [{ "orderable": false, "targets": 0, "className": 'dt-body-center' }]
        });

        // --- Inisialisasi Plugin Telepon ---
        var itiDirect;
        const phoneInputDirect = document.querySelector("#direct_phone");
        if (phoneInputDirect) {
            itiDirect = window.intlTelInput(phoneInputDirect, {
                initialCountry: "id",
                separateDialCode: true,
                utilsScript: "https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.13/js/utils.js",
            });
        }
        var itiUpdate;
        const phoneInputUpdate = document.querySelector("#update_phone");
        if(phoneInputUpdate) {
            itiUpdate = window.intlTelInput(phoneInputUpdate, {
                initialCountry: "id",
                separateDialCode: true,
                utilsScript: "https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.13/js/utils.js",
            });
        }
        
        // =========================================================================================
        // [MODIFIKASI BARU] LOGIKA VALIDASI FORM & DISABLE TOMBOL SIMPAN
        // =========================================================================================
        function validateAndToggleButton(formId, saveButtonId, itiInstance, phoneWarningId) {
            const form = $(formId);
            const saveButton = $(saveButtonId);
            const phoneWarning = $(phoneWarningId);
            const phoneInput = form.find('input[type="tel"]');
            
            let isFormValid = true;
            let isPhoneValid = true;

            // 1. Validasi semua input yang 'required'
            form.find('input[required], select[required]').each(function() {
                if ($(this).val() === null || $(this).val().trim() === '') {
                    isFormValid = false;
                    return false; // Keluar dari loop .each
                }
            });

            // 2. Validasi nomor telepon jika diisi
            const phoneNumber = phoneInput.val().trim();
            if (phoneNumber !== '') {
                if (itiInstance && !itiInstance.isValidNumber()) {
                    isPhoneValid = false;
                    phoneWarning.removeClass('d-none'); // Tampilkan div peringatan
                } else {
                    phoneWarning.addClass('d-none'); // Sembunyikan div peringatan
                }
            } else {
                phoneWarning.addClass('d-none'); // Sembunyikan juga jika kolom telepon kosong
            }

            // 3. Tentukan status tombol simpan berdasarkan kedua validasi
            if (isFormValid && isPhoneValid) {
                saveButton.prop('disabled', false); // Aktifkan tombol
            } else {
                saveButton.prop('disabled', true); // Non-aktifkan tombol
            }
        }

        // Jalankan validasi setiap kali ada input di form ADD
        $('#directClientForm').on('keyup change', 'input, select', function() {
            validateAndToggleButton('#directClientForm', '#saveDirectClientBtn', itiDirect, '#direct-phone-warning');
        });

        // Jalankan validasi setiap kali ada input di form UPDATE
        $('#updateClientForm').on('keyup change', 'input, select', function() {
            validateAndToggleButton('#updateClientForm', '#saveUpdateClientBtn', itiUpdate, '#update-phone-warning');
        });

        // Saat modal UPDATE dibuka, jalankan validasi karena form sudah terisi data
        $('#updateClientModal').on('shown.bs.modal', function () {
            // Beri sedikit jeda agar plugin intl-tel-input selesai memuat nomor
            setTimeout(function() {
                validateAndToggleButton('#updateClientForm', '#saveUpdateClientBtn', itiUpdate, '#update-phone-warning');
            }, 200); 
        });

        // Saat modal ADD dibuka, pastikan tombol disable dan form bersih
        $('#addClientModal').on('shown.bs.modal', function () {
            $('#saveDirectClientBtn').prop('disabled', true);
            $('#direct-phone-warning').addClass('d-none'); // Pastikan peringatan tersembunyi
        });
        // =========================================================================================
        // AKHIR DARI MODIFIKASI BARU
        // =========================================================================================

        // --- LOGIKA UNTUK MODAL ADD CLIENT YANG BARU ---
        const addClientModalEl = document.getElementById('addClientModal');
        const choiceContainer = $('#choice-container');
        const invitationContainer = $('#invitation-code-container');
        const registrationContainer = $('#direct-registration-container');
        const btnBack = $('#btn-back-to-choice');
        const saveDirectBtn = $('#saveDirectClientBtn');

        function resetAddClientModal() {
            $('#addClientModalLabel').text('Add New Client');
            choiceContainer.removeClass('d-none');
            invitationContainer.addClass('d-none');
            registrationContainer.addClass('d-none');
            btnBack.addClass('d-none');
            saveDirectBtn.addClass('d-none');
            $('#directClientForm').trigger('reset');
            // Pastikan tombol simpan dinonaktifkan saat modal direset
            saveDirectBtn.prop('disabled', true);
            $('#direct-phone-warning').addClass('d-none');
        }

        addClientModalEl.addEventListener('show.bs.modal', function () {
            resetAddClientModal();
        });

        btnBack.on('click', function() {
            resetAddClientModal();
        });

        $('#btn-generate-invitation').on('click', function() {
            const btn = $(this);
            btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Generating...');

            $.ajax({
                url: '{{ route("panel.invitation.create") }}',
                type: 'POST',
                data: { _token: '{{ csrf_token() }}' },
                success: function(response) {
                    $('#invitation-code-display').text(response.code);
                    $('#addClientModalLabel').text('Invitation Code Generated');
                    choiceContainer.addClass('d-none');
                    invitationContainer.removeClass('d-none');
                    btnBack.removeClass('d-none');
                },
                error: function() { Swal.fire('Gagal!', 'Tidak dapat membuat kode undangan.', 'error'); },
                complete: function() { btn.prop('disabled', false).html('<i class="ti ti-mail-forward me-1"></i> Generate Invitation Code <small class="d-block">Untuk client yang mendaftar sendiri.</small>'); }
            });
        });

        $('#btn-copy-code').on('click', function() {
            const code = $('#invitation-code-display').text();
            // GANTI 'invitation.page' dengan nama route halaman gerbang Anda
            const registrationUrl = "{{-- route('invitation.page') --}}";
            const textToCopy = `Halo, silakan gunakan kode undangan berikut untuk mendaftar:\n\nKode: ${code}\n\nAnda bisa memasukkan kode ini di halaman pendaftaran.`;

            navigator.clipboard.writeText(textToCopy).then(() => {
                Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Info Disalin!', showConfirmButton: false, timer: 2000 });
            });
        });

        $('#btn-register-directly').on('click', function() {
            $('#addClientModalLabel').text('Register Client Directly');
            choiceContainer.addClass('d-none');
            registrationContainer.removeClass('d-none');
            btnBack.removeClass('d-none');
            saveDirectBtn.removeClass('d-none');
        });

        saveDirectBtn.on('click', function(e) {
            e.preventDefault();
            const form = $('#directClientForm');
            const btn = $(this);
            
            // Set nilai telepon dengan format internasional sebelum submit
            if (itiDirect && $('#direct_phone').val().trim() !== '') {
                // Validasi ulang terakhir sebelum submit
                if (itiDirect.isValidNumber()) {
                    $('#direct_phone').val(itiDirect.getNumber());
                } else {
                    // Seharusnya tidak terjadi karena tombol sudah dinonaktifkan, tapi sebagai pengaman
                    Swal.fire('Gagal!', 'Nomor telepon tidak valid.', 'error');
                    return;
                }
            }

            btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Saving...');

            $.ajax({
                url: form.attr('action'),
                type: 'POST',
                data: form.serialize(),
                success: function(response) {
                    $('#addClientModal').modal('hide');
                    Swal.fire({ title: 'Sukses!', text: response.message, icon: 'success', timer: 2000, showConfirmButton: false })
                        .then(() => { window.location.reload(); });
                },
                error: function(xhr) {
                    const errorMessage = xhr.responseJSON?.message || 'Gagal menyimpan data.';
                    Swal.fire('Gagal!', errorMessage, 'error');
                },
                complete: function() {
                    // Tombol akan tetap disabled setelah proses selesai, menunggu input user baru
                    btn.prop('disabled', true).text('Register Client');
                }
            });
        });

        // --- LOGIKA UNTUK MODAL UPDATE CLIENT ---
        $('.btn-update-client').on('click', function () {
            var clientId = $(this).data('id');
            $('#update_client_id').val(clientId);
            
            // Reset state tombol & peringatan saat modal dibuka
            $('#saveUpdateClientBtn').prop('disabled', true);
            $('#update-phone-warning').addClass('d-none');

            $.ajax({
                url: '/client/' + clientId,
                type: 'GET',
                success: function (response) {
                    $('#update_name').val(response.name);
                    if(response.phone && itiUpdate) {
                        itiUpdate.setNumber(response.phone);
                    } else if (itiUpdate) {
                        itiUpdate.setNumber("");
                    }
                    $('#update_institution').val(response.institution);
                    $('#update_institution_type').val(response.institution_type);
                    $('#update_address').val(response.address);
                    $('#update_reference').val(response.reference);

                    // Jalankan validasi setelah data dimuat
                    validateAndToggleButton('#updateClientForm', '#saveUpdateClientBtn', itiUpdate, '#update-phone-warning');
                },
                error: function (xhr) { Swal.fire('Gagal', 'Tidak dapat memuat data client.', 'error'); }
            });
        });

        $('#saveUpdateClientBtn').on('click', function (e) {
            e.preventDefault();
            const form = $('#updateClientForm');
            const btn = $(this);

            if (itiUpdate && $('#update_phone').val().trim() !== '') {
                if (itiUpdate.isValidNumber()) {
                    $('#update_phone').val(itiUpdate.getNumber());
                } else {
                    Swal.fire('Gagal!', 'Nomor telepon tidak valid.', 'error');
                    return;
                }
            }
            
            btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Saving...');

            $.ajax({
                url: form.attr('action'),
                type: 'POST',
                data: form.serialize(),
                success: function (response) {
                    $('#updateClientModal').modal('hide');

                    Swal.fire({ title: 'Sukses!', text: response.message, icon: 'success', timer: 2000, showConfirmButton: false })
                        .then(() => { window.location.reload(); });
                },
                error: function (xhr) {
                    const errorMessage = xhr.responseJSON?.message || 'Gagal menyimpan data.';
                    Swal.fire('Gagal!', errorMessage, 'error');
                },
                complete: function() {
                    btn.prop('disabled', true).text('Save Changes');
                }
            });
        });

        // --- LOGIKA DELETE & BULK DELETE (TETAP SAMA) ---
        $('.btn-delete-client').on('click', function (e) {
            e.preventDefault();
            var form = $(this).closest('form');
            Swal.fire({
                title: 'Peringatan!',
                html: "<p>Anda akan menghapus sebuah Data Master.<br><br>Menghapus data ini dapat menyebabkan inkonsistensi. Apakah Anda yakin?</p>",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                confirmButtonText: 'Ya, Hapus'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: form.attr('action'),
                        type: 'POST',
                        data: form.serialize(),
                        success: function(response) {
                            Swal.fire({ title: 'Terhapus!', text: response.message, icon: 'success', timer: 2000, showConfirmButton: false })
                                .then(() => { window.location.reload(); });
                        },
                        error: function(xhr) {
                            const msg = xhr.responseJSON?.message || 'Terjadi kesalahan.';
                            Swal.fire({ icon: 'error', title: 'Gagal!', text: msg });
                        }
                    });
                }
            });
        });

        function updateBulkDeleteButtonState() {
            var selectedCount = table.rows({ search: 'applied' }).nodes().to$().find('.client-checkbox:checked').length;
            $('#btn-bulk-delete').prop('disabled', selectedCount === 0);
        }

        $('#selectAllCheckbox').on('click', function() {
            var rows = table.rows({ search: 'applied' }).nodes();
            $('input.client-checkbox', rows).prop('checked', this.checked);
            updateBulkDeleteButtonState();
        });

        $('#dom-jqry tbody').on('change', '.client-checkbox', function() {
            updateBulkDeleteButtonState();
            var total = table.rows({ search: 'applied' }).nodes().to$().find('.client-checkbox').length;
            var checked = table.rows({ search: 'applied' }).nodes().to$().find('.client-checkbox:checked').length;
            $('#selectAllCheckbox').prop('checked', total > 0 && total === checked);
        });

        table.on('draw', function() {
            updateBulkDeleteButtonState();
            $('#selectAllCheckbox').prop('checked', false);
        });

        $('#btn-bulk-delete').on('click', function() {
            var selectedClientIds = table.rows({ search: 'applied' }).nodes().to$().find('.client-checkbox:checked').map(function() {
                return $(this).val();
            }).get();

            if (selectedClientIds.length > 0) {
                Swal.fire({
                    title: `Hapus ${selectedClientIds.length} Data Master?`,
                    text: "Tindakan ini tidak dapat diurungkan!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    confirmButtonText: 'Ya, Hapus Semua'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: '{{ route('panel.client.bulkDestroy') }}',
                            type: 'POST',
                            data: { _token: '{{ csrf_token() }}', ids: selectedClientIds },
                            success: function (response) {
                                Swal.fire({ title: 'Terhapus!', text: response.message, icon: 'success', timer: 2000, showConfirmButton: false })
                                    .then(() => { window.location.reload(); });
                            },
                            error: function (xhr) {
                                const msg = xhr.responseJSON?.message || 'Terjadi kesalahan.';
                                Swal.fire({ icon: 'error', title: 'Gagal!', text: msg });
                            }
                        });
                    }
                });
            }
        });

    });
</script>
    <style>
        .highlight-row {
            background-color: #00a2ff36;
            transition: background-color 0.5s ease-in-out;
        }
        .iti {
            width: 100%;
        }
        .iti__country-list {
            z-index: 1056; /* Pastikan dropdown muncul di atas modal */
        }
    </style>
@endsection