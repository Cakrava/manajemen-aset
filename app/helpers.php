<?php

if (!function_exists('flexible_asset')) {
    function flexible_asset($path)
    {
        return env('APP_SECURE', false) ? secure_asset($path) : asset($path);
    }
}

if (!function_exists('resolve_transaction_activity')) {
    /**
     * Menentukan aktivitas transaksi (In / Out / Hybrid) selaras dengan asset-flow.
     *
     * @param  \App\Models\Transaction|array  $transaction
     * @return array{activity: string, activity_label: string, is_hybrid: bool, has_serah: bool, has_tarik: bool, status_display: string}
     */
    function resolve_transaction_activity($transaction): array
    {
        $transactionType = is_array($transaction)
            ? ($transaction['transaction_type'] ?? 'out')
            : $transaction->transaction_type;

        $instalationStatus = is_array($transaction)
            ? ($transaction['instalation_status'] ?? '')
            : $transaction->instalation_status;

        $letterDetails = [];
        if (is_array($transaction)) {
            $letterDetails = $transaction['letter']['details'] ?? [];
        } elseif ($transaction->relationLoaded('letter') && $transaction->letter) {
            $letterDetails = $transaction->letter->details;
        }

        $statuses = collect($letterDetails)->pluck('status');
        $hasSerah = $statuses->contains(0) || $statuses->contains('0');
        $hasTarik = $statuses->contains(1) || $statuses->contains('1');
        $isHybrid = $hasSerah && $hasTarik;

        if ($isHybrid) {
            $activity = 'hybrid';
            $activityLabel = 'In & Out';
        } elseif ($transactionType === 'in') {
            $activity = 'in';
            $activityLabel = 'In';
        } else {
            $activity = 'out';
            $activityLabel = 'Out';
        }

        $statusDisplay = ucwords(trim((string) $instalationStatus));
        if ($isHybrid && strtolower(trim((string) $instalationStatus)) === 'deployed') {
            $statusDisplay = 'Deployed & Intake';
        }

        return [
            'activity' => $activity,
            'activity_label' => $activityLabel,
            'is_hybrid' => $isHybrid,
            'has_serah' => $hasSerah,
            'has_tarik' => $hasTarik,
            'status_display' => $statusDisplay,
        ];
    }
}

if (!function_exists('resolve_transaction_detail_status')) {
    /**
     * Status item surat: 0 = Serah, 1 = Tarik, null = tidak ada surat.
     *
     * @param  \App\Models\Transaction|array  $transaction
     */
    function resolve_transaction_detail_status($transaction, int $storedDeviceId): ?int
    {
        $letterDetails = [];
        if (is_array($transaction)) {
            $letterDetails = $transaction['letter']['details'] ?? [];
        } elseif ($transaction->relationLoaded('letter') && $transaction->letter) {
            $letterDetails = $transaction->letter->details;
        }

        foreach ($letterDetails as $detail) {
            $detailStoredId = is_array($detail)
                ? (int) ($detail['stored_device_id'] ?? 0)
                : (int) $detail->stored_device_id;

            if ($detailStoredId === $storedDeviceId) {
                $status = is_array($detail) ? ($detail['status'] ?? 0) : $detail->status;

                return (int) $status;
            }
        }

        return null;
    }
}