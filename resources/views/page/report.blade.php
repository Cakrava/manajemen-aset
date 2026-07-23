@extends('layout.sidebar')

@section('content')

    <!-- [Page specific CSS] start -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/css/bootstrap-datepicker.min.css">
    <style>
        .report-container { display: flex; gap: 20px; }
        .report-sidebar { flex: 0 0 280px; background-color: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,.05); }
        .report-type-list { list-style: none; padding: 0; margin: 0; }
        .report-type-item { padding: 10px 15px; margin-bottom: 5px; background-color: #f8f9fa; border-radius: 5px; cursor: pointer; transition: all .2s ease-in-out; color: #343a40; font-weight: 500; }
        .report-type-item:hover { background-color: #e2e6ea; }
        .report-type-item.active { background-color: #0d6efd; color: #fff; box-shadow: 0 2px 5px rgba(0,0,0,.1); }
        .report-preview-wrapper { flex-grow: 1; background-color: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,.05); display: flex; flex-direction: column; height: calc(100vh - 180px); }
        .report-preview-header-container { background-color: #f8f9fa; border-radius: 8px; margin-bottom: 20px; padding: 15px 20px; display: flex; justify-content: space-between; align-items: center; gap: 10px; border: 1px solid #dee2e6; }
        .report-preview-content { flex-grow: 1; min-height: 0; background-color: #e0e0e0; border-radius: 8px; padding: 20px; overflow-y: auto; display: flex; flex-direction: column; align-items: center; gap: 15px; scroll-behavior: smooth; }
        .document-page { background-color: #fff; box-shadow: 0 0 10px rgba(0,0,0,.15); padding: 25mm; box-sizing: border-box; border: 1px solid #ccc; flex-shrink: 0; width: 794px;  height: 1123px; overflow: hidden; position: relative; }
        .document-page .report-page-header { display: flex; align-items: center; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid #333; }
        .document-page .report-page-header .logo { width: 70px; height: auto; margin-right: 20px; }
        .document-page .report-page-header .header-text { flex-grow: 1; text-align: center; }
        .document-page .report-page-header .header-text h3 { margin: 0; font-size: 1.2em; }
        .document-page .report-page-header .header-text p { margin: 0; font-size: .8em; color: #555; }
        .document-page .report-main-title { text-align: center; font-size: 1.4em; font-weight: bold; margin-top: 20px; margin-bottom: 5px; color: #555; }
        .document-page .report-sub-title { text-align: center; font-size: 1.1em; margin-bottom: 30px; color: #555; }
        .modal-body .input-group { margin-bottom: 1rem; }
        .report-table { width: 100%; border-collapse: collapse; background-color: transparent; font-size: 8pt; margin-top: 20px; }
        .report-table th, .report-table td { border: 1px solid #ccc; padding: 6px; text-align: left; vertical-align: top; }
        .report-table thead th { font-weight: bold; background-color: #f8f9fa; border-bottom: 2px solid #ccc; }
        .report-table ul { list-style: none; padding-left: 0; margin-bottom: 0; }
        .report-table ul li { padding: 2px 0; border-bottom: 1px dotted #eee; }
        .report-table ul li:last-child { border-bottom: none; }

        /* Gaya untuk Filter Toggle Multi-Pilihan */
        .dynamic-filter { display: flex; align-items: center; margin-bottom: 1rem; flex-wrap: wrap; border-bottom: 1px solid #dee2e6; padding-bottom: 0.5rem; }
        .dynamic-filter .input-group-text { border: none; background: transparent; color: #6c757d; }
        .filter-toggle-group { display: flex; gap: 10px; flex-grow: 1; padding: 8px 0; border: none; flex-wrap: wrap; }
        .filter-toggle-option { padding: 6px 18px; border-radius: 50px; background-color: transparent; color: #495057; font-size: 0.85em; cursor: pointer; border: 1px solid #ced4da; transition: all 0.2s ease-in-out; user-select: none; }
        .filter-toggle-option:hover { border-color: #0d6efd; color: #0d6efd; background-color: rgba(13, 110, 253, 0.05); }
        .filter-toggle-option.active { background-color: #0d6efd; color: #fff; border-color: #0d6efd; font-weight: 500; box-shadow: 0 3px 8px rgba(13, 110, 253, 0.3); }
    </style>
    <!-- [Page specific CSS] end -->

    <div class="pc-container">
        <div class="pc-content">
            <div class="page-header"><div class="page-block"><div class="row align-items-center"><div class="col-md-12"><ul class="breadcrumb"><li class="breadcrumb-item"><a href="{{ route('panel.dashboard') }}">Dashboard</a></li><li class="breadcrumb-item">Laporan</li></ul></div></div></div></div>
            @if (session()->has('profile_incomplete'))
                <div class="alert alert-primary" style="margin-top: 20px; margin-bottom : 10px">{!! session('profile_incomplete') !!}</div>
            @endif
            <div class="report-container">
                <div class="report-sidebar">
                    <h5 class="mb-4">Pilih Laporan</h5>
                    <div class="report-type-list">
                        <div class="report-type-item active" data-report-type="inventory">Inventory</div>
                        <div class="report-type-item" data-report-type="instansi">Instansi (Klien)</div>
                        <div class="report-type-item" data-report-type="other_profile">Other Profile</div>
                        <div class="report-type-item" data-report-type="flow_transaction">Flow Transaction</div>
                        <div class="report-type-item" data-report-type="letter">Letter</div>
                        <div class="report-type-item" data-report-type="deployed_device">Deployed Device</div>
                    </div>
                </div>

                <div class="report-preview-wrapper">
                    <div class="report-preview-header-container">
                        <div>
                            <button type="button" class="btn btn-outline-primary bg-white text-primary border-primary" data-bs-toggle="modal" data-bs-target="#filterModal">
                                <i class="ti ti-filter"></i> Filter Laporan
                            </button>
                            <button type="button" class="btn btn-outline-danger d-none" id="mainResetFilterBtn">
                                <i class="ti ti-x"></i> Reset Filter
                            </button>
                        </div>
                        @if (!session()->has('profile_incomplete'))
                            <div class="ms-auto d-flex gap-2">
                                <button type="button" class="btn btn-primary" id="printReportBtn"><i class="ti ti-printer"></i> Cetak</button>
                                <button type="button" class="btn btn-secondary" id="downloadPdfBtn"><i class="ti ti-file-text"></i> PDF</button>
                                <button type="button" class="btn btn-info" id="exportExcelBtn"><i class="ti ti-table"></i> Excel</button>
                            </div>
                        @endif
                    </div>
                    <div class="report-preview-content" id="reportPreviewContent"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Modal dengan Opsi yang Sudah Diperbarui -->
    <div class="modal fade" id="filterModal" tabindex="-1" aria-labelledby="filterModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="filterModalLabel">Filter Laporan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <style>
                        .custom-outline { position: relative; margin-top: 1.5rem; }
                        .custom-outline input { width: 100%; padding: 1rem 0.75rem 0.5rem; border: 1px solid #ced4da; border-radius: 0.375rem; outline: none; font-size: 1rem; background: transparent; }
                        .custom-outline input:focus { border-color: #3b82f6; }
                        .custom-outline label { position: absolute; top: -0.6rem; left: 0.75rem; background: white; padding: 0 0.25rem; font-size: 0.85rem; color: #6c757d; pointer-events: none; transition: 0.2s ease; }
                        .custom-outline input:focus + label,
                        .custom-outline input:not(:placeholder-shown) + label { color: #3b82f6; }
                    </style>
                      
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="custom-outline">
                            <input type="text" id="startDate" class="datepicker" placeholder=" " />
                            <label for="startDate">Dari (YYYY-MM-DD)</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="custom-outline">
                            <input type="text" id="endDate" class="datepicker" placeholder=" " />
                            <label for="endDate">Sampai (YYYY-MM-DD)</label>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div id="dynamicFilters">
                        <div class="dynamic-filter d-none" id="flowTransactionTypeFilter">
                            <span class="input-group-text">Tipe</span>
                            <div class="filter-toggle-group">
                                <span class="filter-toggle-option" data-value="in">Masuk</span>
                                <span class="filter-toggle-option" data-value="out">Keluar</span>
                                <span class="filter-toggle-option" data-value="hybrid">In & Out</span>
                            </div>
                        </div>
                        <div class="dynamic-filter d-none" id="flowTransactionStatusFilter">
                            <span class="input-group-text">Status</span>
                            <div class="filter-toggle-group">
                                <span class="filter-toggle-option" data-value="Intake">Intake</span>
                                <span class="filter-toggle-option" data-value="Pending">Pending</span>
                                <span class="filter-toggle-option" data-value="Deployed">Deployed</span>
                                <span class="filter-toggle-option" data-value="Revoked">Revoked</span>
                            </div>
                        </div>
                        <div class="dynamic-filter d-none" id="instansiTypeFilter">
                            <span class="input-group-text">Tipe</span>
                            <div class="filter-toggle-group">
                                <span class="filter-toggle-option" data-value="government">Pemerintahan</span>
                                <span class="filter-toggle-option" data-value="private">Swasta</span>
                                <span class="filter-toggle-option" data-value="non_profit">Nirlaba</span>
                                <span class="filter-toggle-option" data-value="education">Pendidikan</span>
                                <span class="filter-toggle-option" data-value="health">Kesehatan</span>
                                <span class="filter-toggle-option" data-value="finance">Keuangan</span>
                                <span class="filter-toggle-option" data-value="technology">Teknologi</span>
                                <span class="filter-toggle-option" data-value="other">Lainnya</span>
                            </div>
                        </div>
                        <div class="dynamic-filter d-none" id="inventoryConditionFilter">
                            <span class="input-group-text">Kondisi</span>
                            <div class="filter-toggle-group">
                                <span class="filter-toggle-option" data-value="Baru">Baru</span>
                                <span class="filter-toggle-option" data-value="Bekas">Bekas</span>
                            </div>
                        </div>
                        <div class="dynamic-filter d-none" id="inventoryDeviceTypeFilter">
                            <span class="input-group-text">Perangkat</span>
                            <div class="filter-toggle-group">
                                <span class="filter-toggle-option" data-value="router">Router</span>
                                <span class="filter-toggle-option" data-value="access_point">Access Point</span>
                                <span class="filter-toggle-option" data-value="repeater">Repeater</span>
                                <span class="filter-toggle-option" data-value="switch">Switch</span>
                                <span class="filter-toggle-option" data-value="modem">Modem</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-warning me-auto" id="modalResetFilterBtn">Reset</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                    <button type="button" class="btn btn-primary" id="applyFilterBtn">Terapkan Filter</button>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/js/bootstrap-datepicker.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/locales/bootstrap-datepicker.id.min.js"></script>
    <script>
        const logoUrl = "{{ asset('asset/image/icon_title.png') }}";
        
        function formatDeviceType(typeString) {
            if (!typeString) return '-';
            return typeString.replace(/_/g, ' ').split(' ').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' ');
        }
        function formatInstitutionType(typeString) {
            if (!typeString) return '-';
            return typeString.replace(/_/g, ' ').split(' ').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' ');
        }

        $(document).ready(function() {
            $('.datepicker').datepicker({ format: 'yyyy-mm-dd', autoclose: true, todayHighlight: true, language: 'id' });
            let currentSelectedReportType = 'inventory';

            // PERBAIKAN UTAMA: Logika pengambilan filter diubah untuk mendukung multi-pilihan.
            function getFilters() {
                // Fungsi ini sekarang akan mengumpulkan SEMUA tombol aktif menjadi sebuah array.
                const getActiveToggles = (filterId) => {
                    return $(`#${filterId} .filter-toggle-option.active`).map(function() {
                        return $(this).data('value');
                    }).get(); // .get() mengubah objek jQuery menjadi array biasa
                };

                return {
                    startDate: $('#startDate').val(),
                    endDate: $('#endDate').val(),
                    transactionType: getActiveToggles('flowTransactionTypeFilter'),
                    transactionStatus: getActiveToggles('flowTransactionStatusFilter'),
                    instansiType: getActiveToggles('instansiTypeFilter'),
                    inventoryCondition: getActiveToggles('inventoryConditionFilter'),
                    inventoryDeviceType: getActiveToggles('inventoryDeviceTypeFilter'),
                };
            }
            
            function isFilterActive() {
                const filters = getFilters();
                if (filters.startDate || filters.endDate) return true;
                // Cek apakah ada array filter yang tidak kosong
                return Object.values(filters).some(val => Array.isArray(val) && val.length > 0);
            }

            function checkFilterState() {
                $('#mainResetFilterBtn').toggleClass('d-none', !isFilterActive());
            }

            function resetAllFilters() {
                $('#startDate, #endDate').val('').datepicker('update');
                $('.filter-toggle-option').removeClass('active');
                checkFilterState();
            }

            function updateDynamicFiltersVisibility() {
                const type = currentSelectedReportType;
                $('.dynamic-filter').addClass('d-none');

                if (type === 'flow_transaction') {
                    $('#flowTransactionTypeFilter, #flowTransactionStatusFilter').removeClass('d-none');
                } else if (type === 'instansi') {
                    $('#instansiTypeFilter').removeClass('d-none');
                } else if (type === 'inventory') {
                    $('#inventoryConditionFilter, #inventoryDeviceTypeFilter').removeClass('d-none');
                } else if (type === 'deployed_device') {
                    $('#inventoryDeviceTypeFilter').removeClass('d-none');
                }
            }

            function generateReportPreview() {
                $('#reportPreviewContent').scrollTop(0); 
                const filters = getFilters();
                $('#reportPreviewContent').html(`<div class="document-page"><div class="text-center p-5"><span class="spinner-border spinner-border-sm"></span> Memuat Laporan...</div></div>`);
                
                $.ajax({
                    url: '{{ route('reports.generate') }}',
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    data: {
                        report_types: [currentSelectedReportType],
                        filters: filters
                    },
                    success: (response) => renderPaginatedReport(response),
                    error: (xhr) => $('#reportPreviewContent').html(`<div class="document-page"><div class="text-center p-5 text-danger">Gagal Memuat Laporan: ${xhr.responseJSON?.message || 'Error'}</div></div>`)
                });
                checkFilterState();
            }
 
            function formatTypeName(inputString) {
                if (!inputString || typeof inputString !== 'string') {
                    return '-';
                }
                const words = inputString.replace(/_/g, ' ').split(' ');
                const formattedWords = words.map(word => {
                    if (word.length === 0) return '';
                    return word.charAt(0).toUpperCase() + word.slice(1).toLowerCase();
                });
                return formattedWords.join(' ');
            }

            function formatActivityFlow(activity, activityLabel) {
                if (!activity) return '-';
                let statusText = activityLabel || activity;
                let style = '';
                const baseStyle = 'border: 1px solid; padding: 2px 8px; border-radius: 10px; background-color: #ffffff; display: inline-block; min-width: 85px; text-align: center; box-sizing: border-box; font-size: 0.9em; font-weight: 500;';

                switch (activity) {
                    case 'in':
                        statusText = 'In';
                        style = `${baseStyle} border-color: #28a745; color: #28a745;`;
                        break;
                    case 'out':
                        statusText = 'Out';
                        style = `${baseStyle} border-color: #dc3545; color: #dc3545;`;
                        break;
                    case 'hybrid':
                        statusText = 'In & Out';
                        style = `${baseStyle} border-color: #6f42c1; color: #6f42c1;`;
                        break;
                }

                if (style) {
                    return `<span style="${style}">${statusText} <i class="ti ti-${activity === 'hybrid' ? 'arrows-up-down' : (activity === 'in' ? 'arrow-down' : 'arrow-up')}"></i></span>`;
                }
                return statusText;
            }

            function resolveTransactionActivity(item) {
                const letterDetails = item.letter?.details || [];
                const statuses = letterDetails.map(d => d.status);
                const hasSerah = statuses.some(s => s == 0);
                const hasTarik = statuses.some(s => s == 1);
                const isHybrid = hasSerah && hasTarik;

                if (isHybrid) {
                    return { activity: 'hybrid', activityLabel: 'In & Out', isHybrid: true, statusDisplay: (item.instalation_status === 'Deployed' ? 'Deployed & Intake' : (item.instalation_status || '-')) };
                }
                if (item.transaction_type === 'in') {
                    return { activity: 'in', activityLabel: 'In', isHybrid: false, statusDisplay: item.instalation_status || '-' };
                }
                return { activity: 'out', activityLabel: 'Out', isHybrid: false, statusDisplay: item.instalation_status || '-' };
            }

            function resolveDetailStatus(item, storedDeviceId) {
                const letterDetails = item.letter?.details || [];
                const match = letterDetails.find(d => d.stored_device_id == storedDeviceId);
                return match != null ? match.status : null;
            }

            function formatStatus(status) {
                if (!status || typeof status !== 'string') {
                    return '-';
                }

                let statusText = status;
                let style = '';
                const normalizedStatus = status.toLowerCase();
                const baseStyle = 'border: 1px solid; padding: 2px 8px; border-radius: 10px; background-color: #ffffff; display: inline-block; min-width: 85px; text-align: center; box-sizing: border-box; font-size: 0.9em;';

                switch (normalizedStatus) {
                    case 'in': case 'masuk':
                        statusText = 'Masuk';
                        style = `${baseStyle} border-color: #28a745; color: #28a745;`;
                        break;
                    case 'out': case 'keluar':
                        statusText = 'Keluar';
                        style = `${baseStyle} border-color: #dc3545; color: #dc3545;`;
                        break;
                    case 'intake':
                        statusText = 'Intake';
                        style = `${baseStyle} border-color: #6c757d; color: #6c757d;`;
                        break;
                    case 'deployed':
                        statusText = 'Deployed';
                        style = `${baseStyle} border-color: #007bff; color: #007bff;`;
                        break;
                    case 'pending':
                        statusText = 'Pending';
                        style = `${baseStyle} border-color: #ffc107; color: #ffc107;`;
                        break;
                    case 'revoked':
                        statusText = 'Revoked';
                        style = `${baseStyle} border-color: #dc3545; color: #dc3545;`;
                        break;
                    case 'deployed & intake':
                        statusText = 'Deployed & Intake';
                        style = `${baseStyle} border-color: #198754; color: #198754;`;
                        break;
                }

                if (style) {
                    return `<span style="${style}">${statusText}</span>`;
                }
                return statusText;
            }

            function renderPaginatedReport(data) {
                const previewContent = $('#reportPreviewContent');
                previewContent.empty(); 
                const reportSection = data[currentSelectedReportType];
                const fontStyle = "font-family: 'Times New Roman', Times, serif;";

                if (!reportSection || !reportSection.data || !reportSection.data.length) {
                    previewContent.html(`<div class="document-page" style="${fontStyle}"><div class="text-center p-5">Tidak Ada Data Ditemukan</div></div>`);
                    return;
                }

                const filters = getFilters();
                let mainHeaderHtml = `<div class="report-page-header"><img src="${logoUrl}" alt="Logo" class="logo"><div class="header-text"><h3>DINAS KOMUNIKASI DAN INFORMASI KOTA PARIAMAN</h3><p>Jl. Jend. Sudirman 25-31, Pd. II, Kec. Pariaman Tengah,<br>Kota Pariaman, Sumatera Barat 25513</p></div></div>`;
                if (currentSelectedReportType === 'letter' && reportSection.data[0]) {
                    mainHeaderHtml += `<div class="report-main-title">BERITA ACARA SERAH TERIMA BARANG</div><div class="report-sub-title">Tanggal Cetak: ${new Date().toLocaleDateString('id-ID', { year: 'numeric', month: 'long', day: 'numeric' })}</div>`;
                } else {
                    mainHeaderHtml += `<div class="report-main-title">LAPORAN ${reportSection.title?.toUpperCase() || 'UMUM'}</div><div class="report-sub-title">Tanggal Cetak: ${new Date().toLocaleDateString('id-ID', { year: 'numeric', month: 'long', day: 'numeric' })}</div>`;
                }
                if(isFilterActive()) {
                    mainHeaderHtml += `<p style="font-size: smaller; color: #6c757d; text-align:center;">Periode: ${filters.startDate || 'Awal'} s/d ${filters.endDate || 'Akhir'}</p>`;
                }

                let tableHeaderHtml = '<thead>';
                if (currentSelectedReportType === 'inventory') tableHeaderHtml += '<tr><th>No</th><th>Perangkat</th><th>Tipe</th><th>Model</th><th>Stok</th><th>Kondisi</th></tr>';
                else if (currentSelectedReportType === 'instansi') tableHeaderHtml += '<tr><th>No</th><th>Nama Instansi</th><th>Tipe</th><th>Kontak</th><th>Alamat</th></tr>';
                else if (currentSelectedReportType === 'other_profile') tableHeaderHtml += '<tr><th>No</th><th>Nama</th><th>Instansi</th><th>Tipe</th><th>Kontak</th></tr>';
                else if (currentSelectedReportType === 'flow_transaction') tableHeaderHtml += '<tr><th>No</th><th>ID Transaksi</th><th>Flow</th><th>Klien/Sumber</th><th>Perangkat</th><th>Status</th><th>Tanggal</th></tr>';
                else if (currentSelectedReportType === 'letter') tableHeaderHtml += '<tr><th>No</th><th>No. Surat</th><th>Perihal</th><th>Klien</th><th>Tanggal</th></tr>';
                else if (currentSelectedReportType === 'deployed_device') tableHeaderHtml += '<tr><th>No</th><th>Penerima</th><th>Instansi</th><th>Tipe</th><th>Perangkat</th><th>Tanggal Deploy</th><th>Status</th></tr>';
                tableHeaderHtml += '</thead>';

                const tableRows = reportSection.data.map((item, index) => {
                    let rowHtml = `<tr><td>${index + 1}</td>`;
                    if (currentSelectedReportType === 'inventory') {
                        rowHtml += `<td>${item.device?.brand || '-'} </td><td>${formatTypeName(item.device?.type)}</td><td>${item.device?.model || '-'}</td><td>${item.stock}</td><td>${item.condition}</td>`;
                    } else if (currentSelectedReportType === 'instansi') {
                        rowHtml += `<td>${item.institution}</td><td>${formatTypeName(item.institution_type)}</td><td>${item.phone || '-'}</td><td>${item.address || '-'}</td>`;
                    } else if (currentSelectedReportType === 'other_profile') {
                        rowHtml += `<td>${item.name}</td><td>${item.institution || '-'}</td><td>${formatTypeName(item.institution_type || '-')}</td><td>${item.phone || '-'}</td>`;
                    } else if (currentSelectedReportType === 'flow_transaction') {
                        const activityInfo = resolveTransactionActivity(item);
                        rowHtml += `<td>${item.transaction_number}</td>`;
                        rowHtml += `<td>${formatActivityFlow(activityInfo.activity, activityInfo.activityLabel)}</td>`;
                        let clientName = item.client?.profile?.name || item.other_source_profile?.name || '-';
                        rowHtml += `<td>${clientName}</td>`;
                        let devicesList = '<ul>' + (item.details?.map(d => {
                            const brand = d.stored_device?.device?.brand || '-';
                            const detailStatus = resolveDetailStatus(item, d.stored_device_id);
                            let badge = '';
                            if (detailStatus === 1) {
                                badge = ' <span style="font-size:10px;padding:2px 6px;border-radius:4px;background:#fde8e8;color:#dc3545;">Tarik</span>';
                            } else if (detailStatus === 0) {
                                badge = ' <span style="font-size:10px;padding:2px 6px;border-radius:4px;background:#e8f0fe;color:#0d6efd;">Serah</span>';
                            }
                            return `<li>${brand}${badge}</li>`;
                        }).join('') || '<li>-</li>') + '</ul>';
                        rowHtml += `<td>${devicesList}</td>`;
                        rowHtml += `<td>${formatStatus(activityInfo.statusDisplay)}</td>`;
                        rowHtml += `<td>${new Date(item.created_at).toLocaleDateString('id-ID')}</td>`;
                    } else if (currentSelectedReportType === 'deployed_device') {
                        let clientName = item.client?.profile?.name || item.other_source_profile?.name || '-';
                        rowHtml += `<td>${clientName}</td>`;
                        rowHtml += `<td>${item.client?.profile?.institution || '-'}</td>`;
                        rowHtml += `<td>${formatTypeName(item.client?.profile?.institution_type || '-')}</td>`;
                        let devicesList = '<ul>' + (item.details?.map(d => `<li>${d.stored_device?.device?.brand || '-'}</li>`).join('') || '<li>-</li>') + '</ul>';
                        rowHtml += `<td>${devicesList}</td>`;
                        rowHtml += `<td>${new Date(item.created_at).toLocaleDateString('id-ID')}</td>`;
                        rowHtml += `<td>${formatStatus(item.instalation_status || item.status)}</td>`;
                    } else if (currentSelectedReportType === 'letter') {
                        rowHtml += `<td>${item.letter_number || '-'}</td><td>${item.subject || '-'}</td><td>${item.client?.profile?.name || '-'}</td><td>${new Date(item.created_at).toLocaleDateString('id-ID')}</td>`;
                    }
                    return rowHtml + '</tr>';
                });

                const MAX_HEIGHT = 1123, PADDING_TOP_BOTTOM = (25 * 3.78) * 2, CONTENT_MAX_HEIGHT = MAX_HEIGHT - PADDING_TOP_BOTTOM;
                let currentPage = 1;

                while(tableRows.length > 0) {
                    const $page = $(`<div class="document-page" style="${fontStyle}">`).appendTo(previewContent);
                    if (currentPage === 1) { $page.append(mainHeaderHtml); }
                    const $table = $(`<table class="report-table">${tableHeaderHtml}<tbody></tbody></table>`).appendTo($page);
                    const $tbody = $table.find('tbody');
                    while(tableRows.length > 0) {
                        const rowHtml = tableRows[0];
                        const $tempRow = $(rowHtml);
                        $tbody.append($tempRow);
                        let pageContentHeight = 0;
                        $page.children().each(function() { pageContentHeight += $(this).outerHeight(true); });
                        if (pageContentHeight > CONTENT_MAX_HEIGHT) {
                           $tempRow.remove();
                           break;
                        }
                        tableRows.shift();
                    }
                    currentPage++;
                }
            }
            
            // --- Event Listeners ---
            $('#dynamicFilters').on('click', '.filter-toggle-option', function() { $(this).toggleClass('active'); });
            
            $('.report-type-item').on('click', function() {
                resetAllFilters();
                $('.report-type-item').removeClass('active');
                $(this).addClass('active');
                currentSelectedReportType = $(this).data('report-type');
                updateDynamicFiltersVisibility();
                generateReportPreview();
            });

            $('#applyFilterBtn').on('click', function() { generateReportPreview(); $('#filterModal').modal('hide'); });

            $('#modalResetFilterBtn, #mainResetFilterBtn').on('click', function() {
                resetAllFilters();
                generateReportPreview();
                if ($('#filterModal').is(':visible')) { $('#filterModal').modal('hide'); }
            });
        
      // GANTI SELURUH BLOK EVENT LISTENER #printReportBtn ANDA DENGAN KODE LENGKAP INI

// GANTI SELURUH BLOK EVENT LISTENER #printReportBtn ANDA DENGAN KODE INI

// GANTI SELURUH BLOK EVENT LISTENER #printReportBtn ANDA DENGAN KODE LENGKAP INI

$('#printReportBtn').on('click', function() {
    const reportType = currentSelectedReportType;
    const filters = getFilters();
    const printButton = $(this);

    // Nonaktifkan tombol agar tidak diklik berulang kali
    printButton.prop('disabled', true);

    // Tampilkan SweetAlert pertama yang menandakan server sedang bekerja
    Swal.fire({
        title: 'Menyiapkan Laporan',
        html: 'Mohon tunggu, server sedang menyiapkan data...',
        icon: 'info', // <-- DITAMBAHKAN
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    $.ajax({
        url: '{{ route('reports.printPdf') }}',
        method: 'POST',
        data: {
            _token: '{{ csrf_token() }}',
            report_type: reportType,
            filters: filters
        },
        success: function(response) {
            if (response.success) {
                // Siapkan iframe di belakang layar
                let iframe = document.getElementById('print-iframe');
                if (!iframe) {
                    iframe = document.createElement('iframe');
                    iframe.id = 'print-iframe';
                    iframe.style.cssText = 'position:absolute;width:0;height:0;border:0;';
                    document.body.appendChild(iframe);
                }

                // Handler ini akan berjalan SETELAH iframe SELESAI MENGUNDUH data PDF
                iframe.onload = function() {
                    // Tutup alert loading pertama
                    Swal.close();

                    // Tampilkan SweetAlert kedua dengan simulasi progress bar
                    let timerInterval;
                    Swal.fire({
                        title: 'Memproses Pratinjau Cetak',
                        html: 'Browser sedang merender file, mohon tunggu sebentar...',
                        icon: 'info', // <-- DITAMBAHKAN
                        timer: 3000, 
                        timerProgressBar: true,
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        },
                        willClose: () => {
                            clearInterval(timerInterval);
                            
                            try {
                                iframe.contentWindow.focus();
                                iframe.contentWindow.print();
                            } catch (e) {
                                console.error('Gagal membuka dialog cetak:', e);
                                // Pesan error dengan ikon
                                Swal.fire({
                                    title: 'Error',
                                    text: 'Gagal membuka dialog cetak. Pastikan pop-up tidak diblokir.',
                                    icon: 'error'
                                });
                            } finally {
                                printButton.prop('disabled', false).html('<i class="ti ti-printer"></i> Cetak');
                            }
                        }
                    });
                };
                
                // Mulai proses unduh PDF ke dalam iframe
                iframe.src = '{{ route('reports.viewPrintable') }}' + '?t=' + new Date().getTime();

            } else {
                // Pesan error dari server dengan ikon
                Swal.fire({
                    title: 'Gagal',
                    text: 'Server gagal menyiapkan data laporan: ' + (response.message || 'Error tidak diketahui'),
                    icon: 'error'
                });
                printButton.prop('disabled', false).html('<i class="ti ti-printer"></i> Cetak');
            }
        },
        error: function(xhr) {
            // Pesan error koneksi dengan ikon
            Swal.fire({
                title: 'Error Koneksi',
                text: 'Tidak dapat terhubung ke server untuk menyiapkan data cetak.',
                icon: 'error'
            });
            printButton.prop('disabled', false).html('<i class="ti ti-printer"></i> Cetak');
        }
    });
});
$('#downloadPdfBtn, #exportExcelBtn').on('click', function() {
                const isExcel = $(this).is('#exportExcelBtn');
                const route = isExcel ? '{{ route('reports.exportExcel') }}' : '{{ route('reports.downloadPdf') }}';
                const filters = getFilters();
                const params = new URLSearchParams();
                params.append('report_type', currentSelectedReportType);
                const appendParam = (key, value) => { if (value) { if (Array.isArray(value) && value.length > 0) { value.forEach(item => params.append(`${key}[]`, item)); } else if (!Array.isArray(value)) { params.append(key, value); } } };
                appendParam('start_date', filters.startDate);
                appendParam('end_date', filters.endDate);
                appendParam('transaction_type', filters.transactionType);
                appendParam('transaction_status', filters.transactionStatus);
                appendParam('instansi_type', filters.instansiType);
                appendParam('inventory_condition', filters.inventoryCondition);
                appendParam('inventory_device_type', filters.inventoryDeviceType);
                window.location.href = route + '?' + params.toString();
            });

            // Pemuatan awal saat halaman dibuka
            updateDynamicFiltersVisibility();
            generateReportPreview();
        });
    </script>

@endsection