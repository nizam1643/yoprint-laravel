<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UploadResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'original_name' => $this->original_name,
            'status' => $this->status,
            'row_count' => $this->row_count,
            'processed_count' => $this->processed_count,
            'upserted_count' => $this->upserted_count,
            'error' => $this->error,
            'uploaded_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
