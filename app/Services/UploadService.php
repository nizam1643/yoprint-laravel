<?php

namespace App\Services;

use App\Interfaces\UploadRepositoryInterface;
use App\Jobs\ProcessUploadCsv;
use App\Models\Upload;

class UploadService
{
    public function __construct(protected UploadRepositoryInterface $repo) {}

    public function handleUpload($file): Upload
    {
        $contents = file_get_contents($file->getRealPath());
        $checksum = hash('sha256', $contents);
        $size = $file->getSize();

        $disk = 'local';
        $folder = 'uploads';
        $ext = $file->getClientOriginalExtension();
        $storedName = "{$checksum}.{$ext}";
        $path = $file->storeAs($folder, $storedName, $disk);

        $upload = $this->repo->findOrCreate($checksum, $size, [
            'original_name' => $file->getClientOriginalName(),
            'disk' => $disk,
            'path' => $path,
        ]);

        if (! in_array($upload->status, ['completed', 'failed'])) {
            ProcessUploadCsv::dispatch($upload->id)->onQueue('default');
        }

        return $upload;
    }
}
