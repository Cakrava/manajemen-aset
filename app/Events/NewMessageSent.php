<?php

namespace App\Events;

use App\Models\Message;
use App\Models\User; // Pastikan User diimport
use App\Models\Profile; // Import Profile jika belum
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

use Illuminate\Support\Facades\Log;

class NewMessageSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    // Properti public akan otomatis disertakan dalam payload event
    public Message $message;
    public array $sender_data; // Data pengirim (termasuk profil)

    public function __construct(Message $message)
    {
        // --- MODIFIKASI DI SINI ---
        // Eager load relasi:
        // - sender (hanya ID)
        // - sender.profile (user_id, name, DAN image) <-- Tambahkan 'image'
        // - ticket (jika ada)
        $this->message = $message->load([
            'sender:id,role',
            'sender.profile:user_id,name,image', // <--- TAMBAHKAN ',image'
            'ticket'
        ]);
        // --- AKHIR MODIFIKASI ---

        // Siapkan data sender untuk payload (terutama untuk channel admin)
        // Pastikan sender ada sebelum mengakses relasi profile
        if ($this->message->sender) {
             // Konversi sender (yang sudah di-load profile-nya) ke array
            $this->sender_data = $this->message->sender->toArray();
            // Pastikan relasi profile benar-benar ada di hasil toArray
            // Jika tidak, mungkin perlu akses manual:
            // $this->sender_data = [
            //     'id' => $this->message->sender->id,
            //     'profile' => $this->message->sender->profile ? $this->message->sender->profile->toArray() : null
            //     // Tambahkan field user lain jika perlu
            // ];
        } else {
            $this->sender_data = []; // Kosongkan jika sender tidak ada (seharusnya tidak terjadi)
        }
    }

    public function broadcastOn(): array
    {
        // Satu channel: chat.{user_id} — user adalah pihak non-admin
        // Baik user maupun admin yang sedang buka chat user ini akan menerima event
        if ($this->message->sender && $this->message->sender->role === 'user') {
            // Pesan dari user ke admin
            $userId = $this->message->sender_id;
        } else {
            // Pesan dari admin ke user
            $userId = $this->message->receiver_id;
        }

        return $userId ? [new PrivateChannel('chat.' . $userId)] : [];
    }

    public function broadcastAs(): string
    {
        return 'chat';
    }

    // Method broadcastWith() BISA DIHAPUS
    // karena properti public $message dan $sender_data sudah otomatis dikirim.
    // Jika Anda ingin kontrol penuh payload, uncomment dan sesuaikan:
    // public function broadcastWith(): array
    // {
    //     return [
    //         'message' => $this->message->toArray(),
    //         'sender_data' => $this->sender_data, // Pastikan $sender_data berisi profile image
    //         'formatted_time' => $this->message->created_at->format('h:i A')
    //     ];
    // }
}