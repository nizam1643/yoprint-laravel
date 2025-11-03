<?php

namespace App\Notifications;

use App\Models\Upload;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class UploadProcessed extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Upload $upload) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        return [
            'upload_id' => $this->upload->id,
            'original_name' => $this->upload->original_name,
            'status' => $this->upload->status,
            'upserted_count' => $this->upload->upserted_count,
        ];
    }
}
