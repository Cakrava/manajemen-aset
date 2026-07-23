<!DOCTYPE html>
<html lang="id">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Laporan {{ $reportData['title'] ?? 'Umum' }}</title>
    <style>
        /* [Global Styles] */
        body { font-family: 'Times New Roman', Times, serif; font-size: 10pt; line-height: 1.3; color: #333; }
        .header { text-align: center; margin-bottom: 20px; position: relative; padding-bottom: 10px; border-bottom: 2px solid #333; }
        .header .logo { position: absolute; left: 0px; top: 0px; width: 70px; height: auto; }
        .header .header-text { margin-left: 80px; text-align: center; }
        .header .header-text h3 { margin: 0; font-size: 13pt; }
        .header .header-text p { margin: 3px 0 0 0; font-size: 9pt; }
        
        /* [Title Styles] */
        .report-main-title { text-align: center; font-size: 14pt; font-weight: bold; margin-top: 20px; margin-bottom: 5px; text-decoration: underline; }
        .report-sub-title { text-align: center; font-size: 12pt; margin-top: 0; margin-bottom: 15px; }
        .period-info { text-align: center; font-size: 9pt; color: #6c757d; margin-bottom: 20px; }

        /* ================================================================== */
        /* [PERUBAHAN UTAMA] GAYA TABEL AGAR IDENTIK DENGAN GAMBAR PRATINJAU */
        /* ================================================================== */
        .report-table {
            width: 100%;
            border-collapse: collapse; /* Penting untuk border yang rapi */
            font-size: 8pt;
        }

        /* Gaya untuk semua sel, baik header maupun data */
        .report-table th, .report-table td {
            border: 1px solid #DEE2E6; /* Warna border abu-abu muda, seperti di pratinjau */
            padding: 8px;
            vertical-align: middle; /* Membuat teks rata tengah secara vertikal */
        }

        /* Gaya khusus untuk baris header (thead) */
        .report-table thead th {
            background-color: #F8F9FA; /* Latar belakang abu-abu sangat muda, standar pratinjau */
            font-weight: bold;
            color: #212529; /* Warna teks hitam pekat */
            text-align: center; /* Teks header selalu rata tengah */
        }

        /* Gaya untuk sel data (tbody) */
        .report-table tbody td {
            text-align: left; /* Teks data rata kiri secara default */
        }
        
        
        .text-center { text-align: center !important; } /* Class helper untuk meratakan tengah */
        .no-data { padding: 40px; text-align: center; font-size: 11pt; color: #777; }

        /* [Status Badge Styles] */
        .status-badge {
            border: 1px solid #ccc; padding: 2px 8px; border-radius: 10px; background-color: #ffffff;
            display: inline-block; min-width: 80px; text-align: center; font-size: 0.9em; font-weight: bold;
        }
        .status-in { border-color: #28a745; color: #28a745; }
        .status-out { border-color: #dc3545; color: #dc3545; }
        .status-intake { border-color: #6c757d; color: #6c757d; }
        .status-deployed { border-color: #007bff; color: #007bff; }
        .status-pending { border-color: #ffc107; color: #ffc107; }
        .status-revoked { border-color: #dc3545; color: #dc3545; }
        .status-hybrid { border-color: #6f42c1; color: #6f42c1; }

    </style>
</head>
<body>
    @php
        // Helper PHP tidak berubah, sudah bagus.
        $currentReportType = $reportTypeToExport ?? 'unknown';
        $currentFilters = $filters ?? [];
        function formatTypeName($typeString) {
            if (!$typeString) return '-';
            return ucwords(str_replace('_', ' ', $typeString));
        }
        function renderStatusBadge($status) {
            if (!$status) return '-';
            $statusText = $status; $class = 'status-badge'; $normalizedStatus = strtolower($status);
            switch ($normalizedStatus) {
                case 'in': case 'masuk': $statusText = 'Masuk'; $class .= ' status-in'; break;
                case 'out': case 'keluar': $statusText = 'Keluar'; $class .= ' status-out'; break;
                case 'hybrid': case 'in & out': $statusText = 'In & Out'; $class .= ' status-hybrid'; break;
                case 'intake': $statusText = 'Intake'; $class .= ' status-intake'; break;
                case 'deployed': $statusText = 'Deployed'; $class .= ' status-deployed'; break;
                case 'deployed & intake': $statusText = 'Deployed & Intake'; $class .= ' status-deployed'; break;
                case 'pending': $statusText = 'Pending'; $class .= ' status-pending'; break;
                case 'revoked': $statusText = 'Revoked'; $class .= ' status-revoked'; break;
            }
            return '<span class="' . $class . '">' . $statusText . '</span>';
        }
        function renderActivityFlowBadge(array $activityInfo) {
            $map = [
                'in' => ['label' => 'In', 'class' => 'status-in'],
                'out' => ['label' => 'Out', 'class' => 'status-out'],
                'hybrid' => ['label' => 'In & Out', 'class' => 'status-hybrid'],
            ];
            $activity = $activityInfo['activity'] ?? 'out';
            $entry = $map[$activity] ?? ['label' => $activityInfo['activity_label'] ?? '-', 'class' => ''];
            return '<span class="status-badge ' . $entry['class'] . '">' . $entry['label'] . '</span>';
        }
    @endphp

    <div class="header">
        <img src="{{ public_path('asset/image/icon_title.png') }}" alt="Logo" class="logo">
        <div class="header-text">
            <h3>DINAS KOMUNIKASI DAN INFORMASI KOTA PARIAMAN</h3>
            <p>Jl. Jend. Sudirman 25-31, Pd. II, Kec. Pariaman Tengah,<br>Kota Pariaman, Sumatera Barat 25513</p>
        </div>
    </div>

    <div class="content">
        @if ($currentReportType === 'letter' && !empty($reportData['data'][0]))
            <div class="report-main-title">BERITA ACARA SERAH TERIMA BARANG</div>
            <div class="report-sub-title">Tanggal Cetak: {{ \Carbon\Carbon::now()->locale('id')->isoFormat('D MMMM YYYY') }}</div>
        @else
            <div class="report-main-title">LAPORAN {{ strtoupper($reportData['title'] ?? 'UMUM') }}</div>
            <div class="report-sub-title">Tanggal Cetak: {{ \Carbon\Carbon::now()->locale('id')->isoFormat('D MMMM YYYY') }}</div>
        @endif

        @if (!empty($currentFilters['startDate']) || !empty($currentFilters['endDate']))
            <div class="period-info">
                Periode: {{ $currentFilters['startDate'] ?? 'Awal' }} s/d {{ $currentFilters['endDate'] ?? 'Akhir' }}
            </div>
        @endif

        @if (!empty($reportData['data']))
            <table class="report-table">
                <thead>
                    @if ($currentReportType === 'inventory') <tr><th>No</th><th>Perangkat</th><th>Tipe</th><th>Model</th><th>Stok</th><th>Kondisi</th></tr>
                    @elseif ($currentReportType === 'instansi') <tr><th>No</th><th>Nama Instansi</th><th>Tipe</th><th>Kontak</th><th>Alamat</th></tr>
                    @elseif ($currentReportType === 'other_profile') <tr><th>No</th><th>Nama</th><th>Instansi</th><th>Tipe</th><th>Kontak</th></tr>
                    @elseif ($currentReportType === 'flow_transaction') <tr><th>No</th><th>ID Transaksi</th><th>Flow</th><th>Klien/Sumber</th><th>Perangkat</th><th>Status</th><th>Tanggal</th></tr>
                    @elseif ($currentReportType === 'letter') <tr><th>No</th><th>No. Surat</th><th>Perihal</th><th>Klien</th><th>Tanggal</th></tr>
                    @elseif ($currentReportType === 'deployed_device') <tr><th>No</th><th>Penerima</th><th>Instansi</th><th>Tipe</th><th>Perangkat</th><th>Tanggal Deploy</th><th>Status</th></tr>
                    @endif
                </thead>
                <tbody>
                    @foreach ($reportData['data'] as $index => $item)
                        <tr>
                            <td class="text-center">{{ $index + 1 }}</td>
                            @if ($currentReportType === 'inventory')
                                <td>{{ $item['device']['brand'] ?? '-' }}</td>
                                <td>{{ formatTypeName($item['device']['type'] ?? null) }}</td>
                                <td>{{ $item['device']['model'] ?? '-' }}</td>
                                <td class="text-center">{{ $item['stock'] }}</td>
                                <td>{{ $item['condition'] }}</td>
                            @elseif ($currentReportType === 'instansi')
                                <td>{{ $item['institution'] }}</td>
                                <td>{{ formatTypeName($item['institution_type'] ?? null) }}</td>
                                <td>{{ $item['phone'] ?? '-' }}</td>
                                <td>{{ $item['address'] ?? '-' }}</td>
                            @elseif ($currentReportType === 'other_profile')
                                <td>{{ $item['name'] }}</td>
                                <td>{{ $item['institution'] ?? '-' }}</td>
                                <td>{{ formatTypeName($item['institution_type'] ?? null) }}</td>
                                <td>{{ $item['phone'] ?? '-' }}</td>
                            @elseif ($currentReportType === 'flow_transaction')
                                @php $activityInfo = resolve_transaction_activity($item); @endphp
                                <td>{{ $item['transaction_number'] }}</td>
                                <td class="text-center">{!! renderActivityFlowBadge($activityInfo) !!}</td>
                                <td>{{ $item['client']['profile']['name'] ?? $item['other_source_profile']['name'] ?? '-' }}</td>
                                <td>
                                    @if (!empty($item['details']))
    <ul style="list-style-type: none; padding-left: 0;">
        @foreach ($item['details'] as $detail)
            @php
                $detailStatus = resolve_transaction_detail_status($item, (int) ($detail['stored_device_id'] ?? 0));
                $badge = '';
                if ($detailStatus === 1) {
                    $badge = ' <span style="font-size:8pt;color:#dc3545;">(Tarik)</span>';
                } elseif ($detailStatus === 0) {
                    $badge = ' <span style="font-size:8pt;color:#0d6efd;">(Serah)</span>';
                }
            @endphp
            <li>- {{ $detail['stored_device']['device']['brand'] ?? '-' }}{!! $badge !!}</li>
        @endforeach
    </ul>
@else 
    - 
@endif
                                </td>
                                <td class="text-center">{!! renderStatusBadge($activityInfo['status_display']) !!}</td>
                                <td class="text-center">{{ \Carbon\Carbon::parse($item['created_at'])->format('d/m/Y') }}</td>
                            @elseif ($currentReportType === 'deployed_device')
                                <td>{{ $item['client']['profile']['name'] ?? $item['other_source_profile']['name'] ?? '-' }}</td>
                                <td>{{ $item['client']['profile']['institution'] ?? '-' }}</td>
                                <td>{{ formatTypeName($item['client']['profile']['institution_type'] ?? null) }}</td>
                                <td>
                                    @if (!empty($item['details']))
                                        <ul>@foreach ($item['details'] as $detail)<li>{{ $detail['stored_device']['device']['brand'] ?? '-' }}</li>@endforeach</ul>
                                    @else - @endif
                                </td>
                                <td class="text-center">{{ \Carbon\Carbon::parse($item['created_at'])->format('d/m/Y') }}</td>
                                <td class="text-center">{!! renderStatusBadge($item['instalation_status'] ?? $item['status'] ?? null) !!}</td>
                            @elseif ($currentReportType === 'letter')
                                <td>{{ $item['letter_number'] ?? '-' }}</td>
                                <td>{{ $item['subject'] ?? '-' }}</td>
                                <td>{{ $item['client']['profile']['name'] ?? '-' }}</td>
                                <td class="text-center">{{ \Carbon\Carbon::parse($item['created_at'])->format('d/m/Y') }}</td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <div class="no-data">Tidak ada data ditemukan untuk filter yang dipilih.</div>
        @endif
    </div>

    {{-- Script untuk memberitahu halaman utama bahwa PDF sudah siap, JANGAN DIHAPUS --}}
    <script>
        window.onload = function() {
            window.parent.postMessage('pdf-is-ready-to-print', '*');
        };
    </script>
</body>
</html>