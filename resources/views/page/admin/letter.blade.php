@extends('layout.sidebar')
@section('content')

    {{-- Token CSRF untuk request AJAX --}}
    <meta name="csrf-token" content="{{ csrf_token() }}">
    {{-- SweetAlert2 CDN & PDF.js --}}
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.worker.min.js"></script>

    <!-- [Page specific CSS] start -->
    <link rel="stylesheet" href="{{ asset('assets/css/plugins/dataTables.bootstrap5.min.css') }}">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />
    <!-- [Page specific CSS] end -->
    <style>
        .filter-v1 .filter-btn{background:0 0;border:none;padding:8px 15px;color:#495057;cursor:pointer;position:relative}
        .filter-v1 .filter-btn:hover{color:#0ea2bc}
        .filter-v1 .filter-btn.active{color:#0ea2bc;font-weight:700}
        .filter-v1 .filter-btn.active::after{content:'';position:absolute;bottom:-2px;left:8px;right:8px;height:2px;background-color:#0ea2bc}

        /* STYLING UNTUK TOMBOL FLOATING DELETED */
        .float-deleted-btn {
            position: fixed;
            width: auto; /* Lebar otomatis */
            padding: 10px 20px;
            height: 48px;
            bottom: 40px;
            right: 40px;
            background-color: #dc3545; /* Merah */
            color: #fff;
            border-radius: 10px;
            text-align: center;
            font-size: 14px;
            font-weight: 500;
            box-shadow: 2px 2px 8px rgba(0, 0, 0, 0.25);
            z-index: 1040;
            cursor: pointer;
            transition: all 0.3s ease-in-out;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .float-deleted-btn:hover {
            background-color: #bb2d3b;
            color: #fff;
        }
    </style>

    <div class="pc-container">
        <div class="pc-content">
            {{-- Header dan Breadcrumb --}}
            <div class="page-header">
                <div class="page-block">
                    <div class="row align-items-center">
                        <div class="col-md-12">
                            <ul class="breadcrumb">
                                <li class="breadcrumb-item"><a href="{{ route('panel.dashboard') }}">Dashboard</a></li>
                                <li class="breadcrumb-item">Letters</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Card Utama --}}
            <div class="col-sm-12">
                <div class="card">
                    <div class="card-header" style="margin-bottom : -15px">
                        <h5>Data Surat</h5>
                        <small class="text-muted">Data ini berisi daftar surat aktif yang tercatat dalam sistem.</small>

                        <div class="mt-3">
                            @auth
                                @if (Auth::user()->role === 'admin')
                                    @if (!session()->has('profile_incomplete'))
                                        <button type="button" class="btn btn-primary" style="margin-bottom: -10px" id="btn-new-letter-process">
                                            <i class="ti ti-plus"></i> New Letter
                                        </button>
                                    @endif
                                @endif
                            @endauth
                            
                            @if (session()->has('profile_incomplete'))
                                <div class="alert alert-primary" style="margin-top: 20px; margin-bottom : -20px">{!! session('profile_incomplete') !!}</div>
                            @endif
                            {{-- Notifikasi Sukses dan Error --}}
                            @if (session()->has('success'))
                                <div class="alert alert-success alert-dismissible fade show" style="margin-top: 20px; margin-bottom : -20px">
                                    {!! session('success') !!}
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            @endif
                            @if (session()->has('error'))
                                <div class="alert alert-danger alert-dismissible fade show" style="margin-top: 20px; margin-bottom : -20px">
                                    {{ session('error') }}
                                     <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            @endif
                            <div style="clear: both;"></div>
                        </div>
                    </div>

                    <div class="card-body">
                        {{-- Filter Status (Tanpa "Deleted") --}}
                        <div class="filter-navigation filter-v1 mb-4">
                            <button type="button" class="filter-btn active" data-filter="">All</button>
                            <button type="button" class="filter-btn" data-filter="Open">Open</button>
                            <button type="button" class="filter-btn" data-filter="Needed">Needed</button>
                            <button type="button" class="filter-btn" data-filter="Closed">Closed</button>
                        </div>
                        
                        {{-- Tabel Data Surat Aktif --}}
                        <div class="dt-responsive">
                            <table id="dom-jqry" class="table table-striped table-bordered nowrap">
                                <thead>
                                    <tr>
                                        <th>Letter Number</th>
                                        <th>Name</th>
                                        <th>Institution</th>
                                        <th>Status</th>
                                        <th>Publish Date</th>
                                        <th>Since</th>
                                        <th class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($letters as $letter)
                                        <tr>
                                            <td>{{ $letter->letter_number }}</td>
                                            <td>{{ $letter->client?->profile?->name ?? 'N/A' }}</td>
                                            <td>{{ $letter->client?->profile?->institution ?? 'N/A' }}</td>
                                            <td>
                                                @php
                                                    $status = $letter->status; 
                                                    $statusStyles = [
                                                        'Open'    => 'background-color: #28a745; color: #fff;',
                                                        'Needed'  => 'background-color: #ffc107; color: #212529;',
                                                        'Closed'  => 'background-color: #6c757d; color: #fff;',
                                                        'default' => 'background-color: #e0e0e0; color: #555;'
                                                    ];
                                                    $style = $statusStyles[$status] ?? $statusStyles['default'];
                                                @endphp
                                                <span style="{{ $style }} padding: 6px 14px; border-radius: 12px; font-size: 0.70rem; font-weight: 500;">
                                                    {{ $status }}
                                                </span>
                                            </td>
                                            <td>{{ $letter->created_at ? $letter->created_at->format('d-m-Y') : '-' }}</td>
                                            <td>{{ $letter->created_at ? $letter->created_at->diffForHumans(['locale' => 'id']) : '-' }}</td>
                                            
                                            <td class="text-center">
                                                <div class="d-flex justify-content-center gap-2">
                                                    @php
                                                        $isViewDisabled = !$letter->pdf_path;
                                                        $isDownloadDisabled = in_array($letter->status, ['Deleted', 'Closed']) || !$letter->pdf_path;
                                                    @endphp

                                                    <button type="button" class="btn btn-sm btn-info view-pdf-btn" 
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#pdfPreviewModal"
                                                            data-letter-status="{{ $letter->status }}"
                                                            data-pdf-url="{{ $letter->pdf_path ? route('panel.letter.view_archive', $letter->id) : '' }}"
                                                            data-signed-pdf-url="{{ $letter->sign_pdf_path ? route('panel.letter.view_signed_archive', $letter->id) : '' }}"
                                                            title="Lihat Arsip"
                                                            {{ $isViewDisabled ? 'disabled' : '' }}>
                                                        <i class="ti ti-eye"></i>
                                                    </button>

                                                    <a href="{{ $isDownloadDisabled ? '#' : route('panel.letter.download_archive', $letter->id) }}" 
                                                       class="btn btn-sm btn-secondary {{ $isDownloadDisabled ? 'disabled' : '' }}" 
                                                       title="Unduh Arsip PDF">
                                                        <i class="ti ti-download"></i>
                                                    </a>

                                                    @if (auth()->user()->role === 'admin' && in_array($letter->status, ['Open', 'Needed']))
                                                        <button type="button" class="btn btn-sm btn-danger btn-delete-letter" 
                                                                data-letter-id="{{ $letter->id }}"
                                                                data-letter-number="{{ $letter->letter_number }}"
                                                                title="Hapus Surat">
                                                            <i class="ti ti-trash"></i>
                                                        </button>
                                                    @endif
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
    
    {{-- TOMBOL FLOATING UNTUK SURAT YANG DIHAPUS --}}
    @if(isset($deletedLetters) && $deletedLetters->count() > 0)
        <div class="float-deleted-btn" data-bs-toggle="offcanvas" data-bs-target="#deletedLettersSidebar" aria-controls="deletedLettersSidebar">
            <i class="ti ti-trash-x"></i>
            <span>Deleted</span>
            <span class="badge bg-light text-danger rounded-pill">{{ $deletedLetters->count() }}</span>
        </div>
    @endif

    {{-- SIDEBAR (OFFCANVAS) UNTUK SURAT YANG DIHAPUS --}}
    <div class="offcanvas offcanvas-end" tabindex="-1" id="deletedLettersSidebar" aria-labelledby="deletedLettersSidebarLabel" style="width: 60%;">
        <div class="offcanvas-header">
            <h5 id="deletedLettersSidebarLabel"><i class="ti ti-trash-x me-2 text-danger"></i>Riwayat Surat Dihapus</h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body">
            <p class="text-muted">Berikut adalah daftar surat yang telah dihapus atau dibatalkan dari sistem.</p>
            <div class="dt-responsive">
                <table id="deleted-letter-table" class="table table-striped table-bordered nowrap" style="width:100%">
                    <thead>
                        <tr>
                            <th>Nomor Surat</th>
                            <th>Nama Klien</th>
                            <th>Instansi</th>
                            <th>Tgl Dihapus</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if(isset($deletedLetters))
                            @foreach($deletedLetters as $letter)
                                <tr>
                                    <td>{{ $letter->letter_number }}</td>
                                    <td>{{ $letter->client?->profile?->name ?? 'N/A' }}</td>
                                    <td>{{ $letter->client?->profile?->institution ?? 'N/A' }}</td>
                                    <td>{{ $letter->updated_at->format('d M Y') }}</td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-sm btn-info view-pdf-btn" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#pdfPreviewModal"
                                                data-letter-status="{{ $letter->status }}"
                                                data-pdf-url="{{ $letter->pdf_path ? route('panel.letter.view_archive', $letter->id) : '' }}"
                                                data-signed-pdf-url="{{ $letter->sign_pdf_path ? route('panel.letter.view_signed_archive', $letter->id) : '' }}"
                                                title="Lihat Arsip"
                                                {{ !$letter->pdf_path ? 'disabled' : '' }}>
                                            <i class="ti ti-eye"></i>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Modal Preview PDF --}}
    <div class="modal fade" id="pdfPreviewModal" tabindex="-1" aria-labelledby="pdfPreviewModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header py-2 px-3">
                    <h5 class="modal-title fs-6" id="pdfPreviewModalLabel">Pratinjau Dokumen</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="pdfModalBody" style="max-height: 65vh; background-color: #f5f5f5; padding: 10px;">
                    
                    <div id="single-preview-container" style="display: none; height: 100%; max-height: 60vh; overflow-y: auto;">
                        <p class="loading-message text-center text-muted p-4">Memuat dokumen...</p>
                        <div id="single-pdf-content"></div>
                    </div>

                    <div id="tabbed-preview-container" style="display: none;">
                        <ul class="nav nav-tabs" id="documentTab" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active py-1 px-3" id="draf-tab" data-bs-toggle="tab" data-bs-target="#draf-pane" type="button" role="tab" aria-controls="draf-pane" aria-selected="true">Draf Surat</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link py-1 px-3" id="tertanda-tab" data-bs-toggle="tab" data-bs-target="#tertanda-pane" type="button" role="tab" aria-controls="tertanda-pane" aria-selected="false">Surat Tertanda</button>
                            </li>
                        </ul>
                        <div class="tab-content pt-2" id="documentTabContent">
                            <div class="tab-pane fade show active" id="draf-pane" role="tabpanel" aria-labelledby="draf-tab" tabindex="0" style="max-height: 55vh; overflow-y: auto;">
                                <p class="loading-message text-center text-muted p-4">Memuat draf...</p>
                                <div id="draf-pdf-content"></div>
                            </div>
                            <div class="tab-pane fade" id="tertanda-pane" role="tabpanel" aria-labelledby="tertanda-tab" tabindex="0" style="max-height: 55vh; overflow-y: auto;">
                                <p class="loading-message text-center text-muted p-4">Memuat surat tertanda...</p>
                                <div id="tertanda-pdf-content"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer py-2 px-3">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Tutup</button>
                    <button type="button" class="btn btn-sm btn-primary" id="printPdfButton" style="display: none;">
                        <i class="ti ti-printer"></i> Cetak Dokumen
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal untuk Proses Surat Baru --}}
    <div class="modal fade" id="newLetterProcessModal" tabindex="-1" aria-labelledby="newLetterProcessModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="newLetterProcessModalLabel">Create New Letter</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-8">
                            <div id="clientSelectionView">
                                 <h6>Langkah 1: Pilih Klien</h6>
                                 <input type="text" id="clientSearchInput" class="form-control mb-3" placeholder="Cari nama atau email klien...">
                                 <div id="clientListContainer" style="max-height: 55vh; overflow-y: auto;">
                                     <div class="list-group">
                                        @forelse($clients as $client)
                                            <a href="#" class="list-group-item list-group-item-action d-flex align-items-center client-list-item" data-client-id="{{ $client->id }}" data-client-name="{{ $client->profile->name ?? $client->name }}" data-client-institution="{{ $client->profile->institution ?? 'N/A' }}" data-client-address="{{ $client->profile->address ?? 'N/A' }}">
                                                <img src="{{ $client->profile && $client->profile->image ? asset('storage/' . $client->profile->image) : asset('assets/images/user/avatar-default.png') }}" alt="{{ $client->profile->name ?? $client->name }}" class="rounded-circle me-3" width="45" height="45" style="object-fit: cover;">
                                                <div>
                                                    <h6 class="mb-0 client-name">{{ $client->profile->name ?? $client->name }}</h6>
                                                    <small class="text-muted client-email">{{ $client->email }}</small>
                                                </div>
                                            </a>
                                        @empty
                                            <p class="text-muted">Tidak ada data klien ditemukan.</p>
                                        @endforelse
                                     </div>
                                 </div>
                            </div>
                            <div id="equipmentSelectionView" style="display: none;">
                                <h6>Langkah 2: Pilih Perangkat</h6>
                                <div class="alert alert-warning alert-dismissible fade show" style="margin-top: 20px; margin-bottom : 20px; display: none;" id="warning-perangkat">
                                    <p>Pilih setidaknya 1 perangkat</p>
                                     <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                                <input type="text" id="equipmentSearchInput" class="form-control mb-3" placeholder="Cari berdasarkan Merek, Model, Tipe...">
                                
                                {{-- TAB UNTUK PENYERAHAN & PENARIKAN --}}
                                <ul class="nav nav-tabs mb-2" id="equipmentFlowTab" role="tablist">
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link active py-1 px-3" id="handover-devices-tab" data-bs-toggle="tab" data-bs-target="#handover-devices-pane" type="button" role="tab">Penyerahan</button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link py-1 px-3" id="withdrawal-devices-tab" data-bs-toggle="tab" data-bs-target="#withdrawal-devices-pane" type="button" role="tab">Penarikan</button>
                                    </li>
                                </ul>

                                <div class="tab-content" id="equipmentFlowTabContent">
                                    <div class="tab-pane fade show active" id="handover-devices-pane" role="tabpanel">
                                        <div id="inventoryListContainer" style="max-height: 45vh; overflow-y: auto; border: 1px solid #eee; padding: 10px;">
                                             <p class="text-muted" id="inventoryListPlaceholder">Ketik untuk mencari inventaris...</p>
                                        </div>
                                    </div>
                                    <div class="tab-pane fade" id="withdrawal-devices-pane" role="tabpanel">
                                        <div id="deployedListContainer" style="max-height: 45vh; overflow-y: auto; border: 1px solid #eee; padding: 10px;">
                                             <p class="text-muted" id="deployedListPlaceholder">Memuat perangkat terpasang pada klien...</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div id="sstDocumentView" style="display: none; font-family: 'Times New Roman', Times, serif; font-size: 12pt; padding: 20px; border: 1px solid #ccc; background-color: #f9f9f9; height: 100%;">
                                <p class="text-center">Memuat dokumen...</p>
                            </div>
                        </div>
                        <div class="col-md-4 border-start">
                            <div class="p-2">
                                 <h5>Ringkasan</h5>
                                 <hr>
                                 <div>
                                     <strong>Klien:</strong>
                                     <p id="selectedClientName" class="text-primary fw-bold">Belum dipilih</p>
                                 </div>
                                 
                                 <div class="mt-3">
                                    <strong>Perangkat Dipilih:</strong>
                                    <div id="selectedEquipmentDisplay" class="mt-2" style="max-height: 40vh; overflow-y: auto;">
                                        <ul id="selectedEquipmentList" class="list-group">
                                            <li class="list-group-item text-muted" id="noEquipmentSelected">Belum ada perangkat yang dipilih.</li>
                                        </ul>
                                    </div>
                                 </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <div id="clientSelectionFooter">
                        <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Batal</button>
                        <button type="button" class="btn btn-primary" id="goToEquipmentSelectionBtn" disabled>Lanjutkan</button>
                    </div>
                    <div id="equipmentSelectionFooter" style="display: none;">
                        <button type="button" class="btn btn-secondary me-2" id="backToClientSelectionBtn">Kembali</button>
                        <button type="button" class="btn btn-primary" id="confirmAndShowSstBtn" disabled>Lanjutkan ke Pratinjau SST</button>
                    </div>
                    <div id="sstDocumentFooter" style="display: none;">
                        <button type="button" class="btn btn-secondary me-2" id="backToEquipmentSelectionBtn">Kembali</button>
                        <button type="button" class="btn btn-success" id="processAndSaveBtn">Proses & Simpan</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- [Page Specific JS] start -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="{{ asset('asset/dist/assets/js/plugins/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('asset/dist/assets/js/plugins/dataTables.bootstrap5.min.js') }}"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        // --- BLOK 1: LOGIKA UNTUK PDF PREVIEW MODAL ---
        pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.worker.min.js';

        const pdfPreviewModal = document.getElementById('pdfPreviewModal');
        const printPdfButton = document.getElementById('printPdfButton');
        const singlePreviewContainer = document.getElementById('single-preview-container');
        const tabbedPreviewContainer = document.getElementById('tabbed-preview-container');
        const singlePdfContent = document.getElementById('single-pdf-content');
        const drafPdfContent = document.getElementById('draf-pdf-content');
        const tertandaPdfContent = document.getElementById('tertanda-pdf-content');
        const modalBody = document.getElementById('pdfModalBody');

        let currentPrintUrl = null;
        const RENDER_SCALE_FACTOR = 0.9;

        async function renderPdfToContainer(pdfUrl, container, loadingElement) {
            if (!pdfUrl) {
                loadingElement.textContent = 'Dokumen tidak ditemukan atau tidak tersedia.';
                loadingElement.style.display = 'block';
                return;
            }
            try {
                loadingElement.style.display = 'block';
                loadingElement.textContent = 'Memuat dokumen...';
                container.innerHTML = '';

                const loadingTask = pdfjsLib.getDocument(pdfUrl);
                const pdfDoc = await loadingTask.promise;
                const parentWidth = modalBody.clientWidth;
                const containerWidth = parentWidth > 0 ? parentWidth - 20 : 750;

                for (let pageNum = 1; pageNum <= pdfDoc.numPages; pageNum++) {
                    const page = await pdfDoc.getPage(pageNum);
                    const viewport = page.getViewport({ scale: 1 });
                    const devicePixelRatio = window.devicePixelRatio || 1;
                    const targetCssWidth = containerWidth * RENDER_SCALE_FACTOR;
                    const renderScale = (targetCssWidth / viewport.width) * devicePixelRatio;
                    const scaledViewport = page.getViewport({ scale: renderScale });
                    const canvas = document.createElement('canvas');
                    const ctx = canvas.getContext('2d');
                    canvas.height = scaledViewport.height;
                    canvas.width = scaledViewport.width;
                    canvas.style.width = `${targetCssWidth}px`;
                    canvas.style.height = `${scaledViewport.height / devicePixelRatio}px`;
                    canvas.style.display = 'block';
                    canvas.style.margin = '0 auto 15px auto';
                    canvas.style.boxShadow = '0 0 10px rgba(0,0,0,0.1)';
                    canvas.style.backgroundColor = '#fff';
                    const renderContext = { canvasContext: ctx, viewport: scaledViewport };
                    await page.render(renderContext).promise;
                    container.appendChild(canvas);
                }
                loadingElement.style.display = 'none';
            } catch (error) {
                console.error('Error loading PDF:', error);
                loadingElement.textContent = 'Gagal memuat dokumen. Detail: ' + error.message;
            }
        }

        pdfPreviewModal.addEventListener('show.bs.modal', async function (event) {
            const button = event.relatedTarget;
            const status = button.getAttribute('data-letter-status');
            const drafUrl = button.getAttribute('data-pdf-url');
            const signedUrl = button.getAttribute('data-signed-pdf-url');

            if (status === 'Closed') {
                singlePreviewContainer.style.display = 'none';
                tabbedPreviewContainer.style.display = 'block';
                printPdfButton.style.display = 'none';
                bootstrap.Tab.getOrCreateInstance(document.getElementById('draf-tab')).show();
                renderPdfToContainer(drafUrl, drafPdfContent, document.querySelector('#draf-pane .loading-message'));
                renderPdfToContainer(signedUrl, tertandaPdfContent, document.querySelector('#tertanda-pane .loading-message'));
            } else {
                singlePreviewContainer.style.display = 'block';
                tabbedPreviewContainer.style.display = 'none';
                printPdfButton.style.display = 'block';
                currentPrintUrl = drafUrl;
                renderPdfToContainer(drafUrl, singlePdfContent, document.querySelector('#single-preview-container .loading-message'));
            }
        });

        printPdfButton.addEventListener('click', function () {
            if (!currentPrintUrl) {
                Swal.fire('Info', 'Tidak ada dokumen yang dimuat untuk dicetak.', 'info');
                return;
            }
            const iframe = document.createElement('iframe');
            iframe.style.display = 'none';
            document.body.appendChild(iframe);
            iframe.onload = function() {
                try {
                    iframe.contentWindow.focus();
                    iframe.contentWindow.print();
                } catch (e) {
                    console.error('Gagal mencetak dari iframe:', e);
                    Swal.fire('Gagal Mencetak', 'Browser mungkin memblokir tindakan ini. Coba unduh dokumen dan cetak secara manual.', 'error');
                } finally {
                    setTimeout(() => { document.body.removeChild(iframe); }, 1000);
                }
            };
            iframe.src = currentPrintUrl;
        });
    
        pdfPreviewModal.addEventListener('hidden.bs.modal', function () {
            singlePdfContent.innerHTML = '';
            drafPdfContent.innerHTML = '';
            tertandaPdfContent.innerHTML = '';
            currentPrintUrl = null;
            document.querySelectorAll('.loading-message').forEach(el => {
                el.textContent = 'Memuat dokumen...';
                el.style.display = 'block';
            });
        });

        // --- BLOK 2: LOGIKA UNTUK DATATABLE, FILTER, DAN LAINNYA ---
        
        var table = $('#dom-jqry').DataTable({
            "dom": '<"row justify-content-between"<"col-md-6"l><"col-md-6 text-end"f>>rt<"row"<"col-md-6"i><"col-md-6 text-end"p>>',
            "columnDefs": [ { "targets": 6, "orderable": false, "searchable": false } ] 
        });

        var deletedLetterTable;
        $('#deletedLettersSidebar').on('shown.bs.offcanvas', function () {
            if (!$.fn.DataTable.isDataTable('#deleted-letter-table')) {
                deletedLetterTable = $('#deleted-letter-table').DataTable({
                    "responsive": true,
                    "dom": '<"row justify-content-between"<"col-md-6"l><"col-md-6 text-end"f>>rt<"row"<"col-md-6"i><"col-md-6 text-end"p>>'
                });
            }
        });

        $('.filter-btn').on('click', function() {
            $('.filter-btn').removeClass('active');
            $(this).addClass('active');
            var filterValue = $(this).data('filter');
            table.column(3).search(filterValue).draw(); 
        });

        const allInventories = @json($inventories ?? []);
        const allClients = @json($clients ?? []);
        
        let selectedClientData = null;
        let selectedEquipments = [];      // Array untuk Serah Terima (Handover)
        let withdrawnEquipments = [];     // Array untuk Penarikan (Withdrawal)
        let clientDeployedUnits = [];     // Data unit terpasang milik klien aktif

        const newLetterModalEl = document.getElementById('newLetterProcessModal');
        const newLetterModal = new bootstrap.Modal(newLetterModalEl);
        const modalTitle = document.getElementById('newLetterProcessModalLabel');
        const clientSelectionView = document.getElementById('clientSelectionView');
        const equipmentSelectionView = document.getElementById('equipmentSelectionView');
        const sstDocumentView = document.getElementById('sstDocumentView');
        const clientSelectionFooter = document.getElementById('clientSelectionFooter');
        const equipmentSelectionFooter = document.getElementById('equipmentSelectionFooter');
        const sstDocumentFooter = document.getElementById('sstDocumentFooter');
        const btnNewLetter = document.getElementById('btn-new-letter-process');
        const goToEquipmentBtn = document.getElementById('goToEquipmentSelectionBtn');
        const backToClientBtn = document.getElementById('backToClientSelectionBtn');
        const confirmAndShowSstBtn = document.getElementById('confirmAndShowSstBtn');
        const backToEquipmentBtn = document.getElementById('backToEquipmentSelectionBtn');
        const processAndSaveBtn = document.getElementById('processAndSaveBtn');
        const clientSearchInput = document.getElementById('clientSearchInput');
        const clientListContainer = document.getElementById('clientListContainer');
        const equipmentSearchInput = document.getElementById('equipmentSearchInput');
        const inventoryListContainer = document.getElementById('inventoryListContainer');
        const deployedListContainer = document.getElementById('deployedListContainer');
        const selectedEquipmentList = document.getElementById('selectedEquipmentList');
        const selectedClientNameEl = document.getElementById('selectedClientName');

        function showView(viewToShow) {
            [clientSelectionView, equipmentSelectionView, sstDocumentView].forEach(v => v.style.display = 'none');
            [clientSelectionFooter, equipmentSelectionFooter, sstDocumentFooter].forEach(f => f.style.display = 'none');
            if (viewToShow === 'client') {
                modalTitle.textContent = 'Buat Surat Baru - Langkah 1: Pilih Klien';
                clientSelectionView.style.display = 'block';
                clientSelectionFooter.style.display = 'flex';
            } else if (viewToShow === 'equipment') {
                modalTitle.textContent = 'Buat Surat Baru - Langkah 2: Pilih Perangkat';
                equipmentSelectionView.style.display = 'block';
                equipmentSelectionFooter.style.display = 'flex';
                bootstrap.Tab.getOrCreateInstance(document.getElementById('handover-devices-tab')).show();
            } else if (viewToShow === 'sst') {
                modalTitle.textContent = 'Buat Surat Baru - Langkah 3: Pratinjau Dokumen';
                sstDocumentView.style.display = 'block';
                sstDocumentFooter.style.display = 'flex';
                generateSstDocument();
            }
        }

        function resetModal() {
            selectedClientData = null;
            selectedEquipments = [];
            withdrawnEquipments = [];
            clientDeployedUnits = [];
            clientSearchInput.value = '';
            equipmentSearchInput.value = '';
            filterClients('');
            document.querySelectorAll('.client-list-item.active').forEach(item => item.classList.remove('active', 'bg-light'));
            selectedClientNameEl.textContent = 'Belum dipilih';
            renderSelectedEquipments();
            renderInventoryList('');
            deployedListContainer.innerHTML = `<p class="text-muted" id="deployedListPlaceholder">Memuat perangkat terpasang pada klien...</p>`;
            goToEquipmentBtn.disabled = true;
            confirmAndShowSstBtn.disabled = true; 
            processAndSaveBtn.disabled = false; 
            processAndSaveBtn.innerHTML = 'Proses & Simpan';
            showView('client');
        }

        btnNewLetter.addEventListener('click', () => newLetterModal.show());
        newLetterModalEl.addEventListener('hidden.bs.modal', resetModal);
        clientSearchInput.addEventListener('input', function() { filterClients(this.value); });

        function filterClients(searchTerm) {
            const lower = searchTerm.toLowerCase();
            document.querySelectorAll('.client-list-item').forEach(item => {
                const name = item.querySelector('.client-name').textContent.toLowerCase();
                const email = item.querySelector('.client-email').textContent.toLowerCase();
                item.style.display = (name.includes(lower) || email.includes(lower)) ? 'flex' : 'none';
            });
        }

        clientListContainer.addEventListener('click', function(event) {
            const target = event.target.closest('.client-list-item');
            if (!target) return;
            event.preventDefault();
            document.querySelectorAll('.client-list-item.active').forEach(item => item.classList.remove('active', 'bg-light'));
            target.classList.add('active', 'bg-light');
            selectedClientData = { id: target.dataset.clientId, name: target.dataset.clientName, institution: target.dataset.clientInstitution, address: target.dataset.clientAddress };
            selectedClientNameEl.textContent = selectedClientData.name;
            goToEquipmentBtn.disabled = false;

            fetchDeployedUnits(selectedClientData.id);
        });

        function fetchDeployedUnits(userId) {
            clientDeployedUnits = [];
            deployedListContainer.innerHTML = '<p class="text-muted"><span class="spinner-border spinner-border-sm me-2"></span>Mencari data terpasang...</p>';
            
            $.ajax({
                url: `{{ url('/api/get-deployed-devices') }}/${userId}`,
                type: 'GET',
                success: function(response) {
                    if (response.devices && response.devices.length > 0) {
                        clientDeployedUnits = response.devices;
                        renderDeployedList('');
                    } else {
                        deployedListContainer.innerHTML = '<p class="text-muted text-center p-3">Klien tidak memiliki riwayat unit terpasang.</p>';
                    }
                },
                error: function() {
                    deployedListContainer.innerHTML = '<p class="text-danger text-center p-3">Gagal memuat unit terpasang.</p>';
                }
            });
        }

        goToEquipmentBtn.addEventListener('click', () => { if(selectedClientData) showView('equipment'); });
        backToClientBtn.addEventListener('click', () => showView('client'));
        confirmAndShowSstBtn.addEventListener('click', () => {
             if (selectedEquipments.length === 0 && withdrawnEquipments.length === 0) { 
                 document.getElementById('warning-perangkat').style.display = 'block'; 
                 return; 
             }
             showView('sst');
        });

        equipmentSearchInput.addEventListener('input', function() { 
            renderInventoryList(this.value); 
            renderDeployedList(this.value);
        });

        // --- RENDER & LOGIKA PENYERAHAN (HANDOVER) ---
        function renderInventoryList(searchTerm) {
            inventoryListContainer.innerHTML = '';
            const lower = searchTerm.toLowerCase();
            const filtered = allInventories.filter(inv => !inv.device ? false : [inv.device.brand, inv.device.model, inv.device.type].some(val => (val || '').toString().toLowerCase().includes(lower)));
            if (filtered.length === 0) {
                inventoryListContainer.innerHTML = `<p class="text-muted">${searchTerm ? 'Inventaris tidak ditemukan.' : 'Ketik untuk mencari inventaris...'}</p>`;
                return;
            }
            const ul = document.createElement('ul');
            ul.className = 'list-group';
            filtered.forEach(inv => {
                const li = document.createElement('li');
                li.className = 'list-group-item';
                const isSelected = selectedEquipments.some(eq => eq.inventory.id === inv.id);
                li.innerHTML = `<div class="d-flex justify-content-between align-items-center"><div><strong>${inv.device.brand || ''} ${inv.device.model || ''}</strong> (${inv.device.type || ''})<br><small class="text-muted">Stok: ${inv.stock} | Kondisi: ${inv.condition}</small></div><div class="input-group input-group-sm" style="width: 150px;"><input type="number" class="form-control quantity-input" value="1" min="1" max="${inv.stock}" ${isSelected ? 'disabled' : ''}><button class="btn ${isSelected ? 'btn-success' : 'btn-outline-primary'} add-item-btn" data-inventory-id="${inv.id}" ${isSelected ? 'disabled' : ''}>${isSelected ? '✓ Ditambahkan' : 'Add'}</button></div></div>`;
                ul.appendChild(li);
            });
            inventoryListContainer.appendChild(ul);
        }
        
        inventoryListContainer.addEventListener('click', function(event) {
            const button = event.target.closest('.add-item-btn');
            if (!button || button.disabled) return;
            const invId = parseInt(button.dataset.inventoryId, 10);
            const invData = allInventories.find(i => i.id === invId);
            const qtyInput = button.previousElementSibling;
            const qty = parseInt(qtyInput.value, 10);
            if (qty > 0 && qty <= invData.stock) {
                selectedEquipments.push({ inventory: invData, quantity: qty });
                renderSelectedEquipments();
                button.textContent = '✓ Ditambahkan';
                button.disabled = true;
                button.classList.replace('btn-outline-primary', 'btn-success');
                qtyInput.disabled = true;
                validateNextStepBtn();
            }
        });

        // --- RENDER & LOGIKA PENARIKAN (WITHDRAWAL) ---
        function renderDeployedList(searchTerm) {
            deployedListContainer.innerHTML = '';
            const lower = searchTerm.toLowerCase();
            const filtered = clientDeployedUnits.filter(unit => (unit.name || '').toLowerCase().includes(lower));
            
            if (filtered.length === 0) {
                deployedListContainer.innerHTML = `<p class="text-muted">${searchTerm ? 'Unit terpasang tidak ditemukan.' : 'Tidak ada perangkat terpasang.'}</p>`;
                return;
            }

            const ul = document.createElement('ul');
            ul.className = 'list-group';
            filtered.forEach(unit => {
                const isSelected = withdrawnEquipments.some(we => we.stored_device_id === unit.stored_device_id);
                const li = document.createElement('li');
                li.className = 'list-group-item';
                li.innerHTML = `
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <strong>${unit.name}</strong><br>
                            <small class="text-muted">Kondisi Saat Ini: ${unit.condition} | Terpasang (Stok): <b>${unit.quantity}</b> unit</small>
                        </div>
                        <div class="input-group input-group-sm" style="width: 220px;">
                            <select class="form-select condition-select" ${isSelected ? 'disabled' : ''}>
                                <option value="Bekas">Bekas</option>
                                <option value="Rusak">Rusak</option>
                            </select>
                            <input type="number" class="form-control quantity-withdraw" value="1" min="1" max="${unit.quantity}" ${isSelected ? 'disabled' : ''}>
                            <button class="btn ${isSelected ? 'btn-success' : 'btn-outline-danger'} add-withdraw-btn" data-stored-id="${unit.stored_device_id}" ${isSelected ? 'disabled' : ''}>
                                ${isSelected ? '✓' : 'Tarik'}
                            </button>
                        </div>
                    </div>
                `;
                ul.appendChild(li);
            });
            deployedListContainer.appendChild(ul);
        }

        deployedListContainer.addEventListener('click', function(event) {
            const button = event.target.closest('.add-withdraw-btn');
            if (!button || button.disabled) return;
            const storedId = parseInt(button.dataset.storedId, 10);
            const unitData = clientDeployedUnits.find(u => u.stored_device_id === storedId);
            const qtyInput = button.previousElementSibling;
            const condSelect = qtyInput.previousElementSibling;
            const qty = parseInt(qtyInput.value, 10);
            const condition = condSelect.value;

            if (qty > 0 && qty <= unitData.quantity) {
                withdrawnEquipments.push({ 
                    stored_device_id: storedId, 
                    name: unitData.name, 
                    quantity: qty, 
                    condition: condition 
                });
                renderSelectedEquipments();
                button.textContent = '✓';
                button.disabled = true;
                button.classList.replace('btn-outline-danger', 'btn-success');
                qtyInput.disabled = true;
                condSelect.disabled = true;
                validateNextStepBtn();
            }
        });

        // --- PENYATUAN DAN RENDER SUMMARY KANAN ---
        function renderSelectedEquipments() {
            selectedEquipmentList.innerHTML = '';
            
            if (selectedEquipments.length === 0 && withdrawnEquipments.length === 0) {
                selectedEquipmentList.innerHTML = '<li class="list-group-item text-muted" id="noEquipmentSelected">Belum ada perangkat yang dipilih.</li>';
                return;
            }

            selectedEquipments.forEach((item, index) => {
                const li = document.createElement('li');
                li.className = 'list-group-item d-flex justify-content-between align-items-center mb-1 border-primary-subtle';
                li.innerHTML = `
                    <span>
                        <span class="badge bg-light-primary text-primary mb-1">Serah Terima</span><br>
                        <strong>${item.inventory.device.brand} ${item.inventory.device.model}</strong>
                        <small class="text-muted d-block">Qty: ${item.quantity}</small>
                    </span>
                    <button class="btn btn-sm btn-outline-danger remove-selected-equipment-btn" data-index="${index}" data-inventory-id="${item.inventory.id}">×</button>
                `;
                selectedEquipmentList.appendChild(li);
            });

            withdrawnEquipments.forEach((item, index) => {
                const li = document.createElement('li');
                li.className = 'list-group-item d-flex justify-content-between align-items-center mb-1 border-danger-subtle';
                li.innerHTML = `
                    <span>
                        <span class="badge bg-light-danger text-danger mb-1">Penarikan (Kondisi: ${item.condition})</span><br>
                        <strong>${item.name}</strong>
                        <small class="text-muted d-block">Qty: ${item.quantity}</small>
                    </span>
                    <button class="btn btn-sm btn-outline-danger remove-withdrawn-equipment-btn" data-index="${index}" data-stored-id="${item.stored_device_id}">×</button>
                `;
                selectedEquipmentList.appendChild(li);
            });
        }

        selectedEquipmentList.addEventListener('click', function(event) {
            const handoverBtn = event.target.closest('.remove-selected-equipment-btn');
            const withdrawBtn = event.target.closest('.remove-withdrawn-equipment-btn');

            if (handoverBtn) {
                const index = parseInt(handoverBtn.dataset.index, 10);
                const invId = parseInt(handoverBtn.dataset.inventoryId, 10);
                selectedEquipments.splice(index, 1);
                renderSelectedEquipments();
                const originalBtn = inventoryListContainer.querySelector(`.add-item-btn[data-inventory-id="${invId}"]`);
                if (originalBtn) {
                    originalBtn.textContent = 'Add';
                    originalBtn.disabled = false;
                    originalBtn.classList.replace('btn-success', 'btn-outline-primary');
                    originalBtn.previousElementSibling.disabled = false;
                }
                validateNextStepBtn();
            }

            if (withdrawBtn) {
                const index = parseInt(withdrawBtn.dataset.index, 10);
                const storedId = parseInt(withdrawBtn.dataset.storedId, 10);
                withdrawnEquipments.splice(index, 1);
                renderSelectedEquipments();
                const originalBtn = deployedListContainer.querySelector(`.add-withdraw-btn[data-stored-id="${storedId}"]`);
                if (originalBtn) {
                    originalBtn.textContent = 'Tarik';
                    originalBtn.disabled = false;
                    originalBtn.classList.replace('btn-success', 'btn-outline-danger');
                    originalBtn.previousElementSibling.disabled = false;
                    originalBtn.previousElementSibling.previousElementSibling.disabled = false;
                }
                validateNextStepBtn();
            }
        });

        function validateNextStepBtn() {
            if (selectedEquipments.length > 0 || withdrawnEquipments.length > 0) {
                confirmAndShowSstBtn.disabled = false;
                document.getElementById('warning-perangkat').style.display = 'none';
            } else {
                confirmAndShowSstBtn.disabled = true;
            }
        }

        backToEquipmentBtn.addEventListener('click', () => showView('equipment'));
        
        // --- PRATINJAU DUA ARAH (SST DOCUMENT) ---
        function generateSstDocument() {
            if (!selectedClientData) return;
            const today = new Date();
            const localDate = today.toLocaleDateString('id-ID', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
            const romawi = ['I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'];
            const nomor = `.../SST/DISKOMINFO/${romawi[today.getMonth()]}/${today.getFullYear()}`;
            
            let handoverSectionHtml = '';
            if (selectedEquipments.length > 0) {
                const listHtml = selectedEquipments.map((item, i) => `<li>${item.inventory.device.brand} ${item.inventory.device.model} (${item.inventory.condition}) - Jumlah: ${item.quantity} unit</li>`).join('');
                handoverSectionHtml = `
                    <p style="margin-bottom: 5px;"><strong>A. PERANGKAT YANG DISERAHKAN:</strong></p>
                    <ol style="padding-left: 20px; margin-bottom: 15px;">${listHtml}</ol>
                `;
            }

            let withdrawalSectionHtml = '';
            if (withdrawnEquipments.length > 0) {
                const listHtml = withdrawnEquipments.map((item, i) => `<li>${item.name} (Kondisi Tarik: ${item.condition}) - Jumlah: ${item.quantity} unit</li>`).join('');
                withdrawalSectionHtml = `
                    <p style="margin-bottom: 5px;"><strong>B. PERANGKAT YANG DITARIK KEMBALI:</strong></p>
                    <ol style="padding-left: 20px; margin-bottom: 15px;">${listHtml}</ol>
                `;
            }

            sstDocumentView.innerHTML = `
                <div style="text-align: center; margin-bottom: 20px;">
                    <h4 style="text-decoration: underline; font-weight: bold; margin-bottom: 5px;">SURAT SERAH TERIMA</h4>
                    <p style="margin-top: 0;">Nomor: ${nomor} (Otomatis)</p>
                </div>
                <p>Pada hari ini, ${localDate}, telah dilakukan serah terima perangkat antara:</p>
                <table style="width: 100%; margin-bottom: 15px;">
                    <tr><td style="width: 150px;"><strong>Pihak Pertama:</strong></td><td>(Perwakilan DISKOMINFO Pariaman)</td></tr>
                    <tr><td><strong>Pihak Kedua:</strong></td><td></td></tr>
                    <tr><td>Nama</td><td>: ${selectedClientData.name || ''}</td></tr>
                    <tr><td>Instansi</td><td>: ${selectedClientData.institution || ''}</td></tr>
                    <tr><td>Alamat</td><td>: ${selectedClientData.address || ''}</td></tr>
                </table>
                
                ${handoverSectionHtml}
                ${withdrawalSectionHtml}
                
                <p>Dengan ini, pihak kedua menyatakan telah menerima perangkat dalam keadaan baik dan mengembalikan unit lama sesuai rincian di atas.</p>
                <table style="width: 100%; margin-top: 40px; text-align: center;">
                    <tr><td>Pihak Pertama,</td><td>Pihak Kedua,</td></tr>
                    <tr><td style="padding-top: 60px;">(__________________)</td><td style="padding-top: 60px;">(${selectedClientData.name || '__________________'})</td></tr>
                </table>
            `;
        }

        // --- SUBMIT DATA KE SERVER ---
        processAndSaveBtn.addEventListener('click', async function () {
            this.disabled = true; 
            this.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Memproses...';
            
            const payload = { 
                client_id: selectedClientData.id, 
                equipments: selectedEquipments.map(item => ({ id: item.inventory.id, quantity: item.quantity })),
                withdrawals: withdrawnEquipments.map(item => ({ stored_device_id: item.stored_device_id, quantity: item.quantity, condition: item.condition }))
            };

            try {
                const response = await fetch("{{ route('panel.letter.storeWithDevices') }}", {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') },
                    body: JSON.stringify(payload)
                });
                const result = await response.json();
                newLetterModal.hide();
                if (response.ok) {
                    Swal.fire({ icon: 'success', title: 'Berhasil!', text: result.message || 'Surat berhasil dibuat.', customClass: { confirmButton: 'btn btn-primary' }, buttonsStyling: false }).then(() => { window.location.reload(); });
                } else {
                    Swal.fire({ icon: (response.status === 409 ? 'warning' : 'error'), title: (response.status === 409 ? 'Peringatan!' : 'Gagal!'), text: result.message || 'Gagal memproses.', customClass: { confirmButton: 'btn btn-primary' }, buttonsStyling: false });
                }
            } catch (error) {
                newLetterModal.hide(); console.error('Error:', error);
                Swal.fire({ icon: 'error', title: 'Error Koneksi', text: 'Gagal terhubung ke server.', customClass: { confirmButton: 'btn btn-primary' }, buttonsStyling: false });
            } finally {
                this.disabled = false; this.innerHTML = 'Proses & Simpan';
            }
        });

        document.body.addEventListener('click', function(event) {
            const deleteButton = event.target.closest('.btn-delete-letter');
            if (!deleteButton) return;
            const letterId = deleteButton.dataset.letterId;
            const letterNumber = deleteButton.dataset.letterNumber;
            const url = `{{ url('/panel/letters') }}/${letterId}/delete`;
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            Swal.fire({
                title: 'Anda yakin?',
                html: `Surat dengan nomor <b>${letterNumber}</b> akan dibatalkan. Transaksi terkait akan ditarik dari proses distribusi.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Ya, hapus!',
                cancelButtonText: 'Batal',
                customClass: { confirmButton: 'btn btn-danger', cancelButton: 'btn btn-secondary ms-2' },
                buttonsStyling: false,
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch(url, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' } })
                    .then(response => response.json())
                    .then(data => {
                        if (data.type === 'success') {
                            Swal.fire({ title: 'Berhasil!', text: data.message, icon: 'success' }).then(() => { window.location.reload(); });
                        } else {
                            Swal.fire({ title: 'Gagal!', text: data.message || 'Error.', icon: 'error' });
                        }
                    })
                    .catch(error => {
                        console.error('Fetch Error:', error);
                        Swal.fire('Error Jaringan', 'Tidak dapat terhubung ke server.', 'error');
                    });
                }
            });
        });
        
        renderInventoryList('');
    });
    </script>
@endsection