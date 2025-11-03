<?php

namespace App\Interfaces;

use App\Models\Upload;

interface UploadRepositoryInterface
{
    public function findOrCreate(string $checksum, int $size, array $meta): Upload;

    public function latest(int $limit = 50);
}
