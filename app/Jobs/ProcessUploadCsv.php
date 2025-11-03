<?php

namespace App\Jobs;

use App\Models\Product;
use App\Models\Upload;
use App\Notifications\UploadProcessed;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

class ProcessUploadCsv implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600;

    public function __construct(public int $uploadId) {}

    public function handle(): void
    {
        $upload = Upload::findOrFail($this->uploadId);
        $upload->update(['status' => 'processing', 'error' => null]);

        $stream = Storage::disk($upload->disk)->readStream($upload->path);
        if (! $stream) {
            throw new \RuntimeException('Unable to open upload stream.');
        }

        $csv = new \SplFileObject(Storage::disk($upload->disk)->path($upload->path));
        $csv->setFlags(\SplFileObject::READ_CSV | \SplFileObject::SKIP_EMPTY | \SplFileObject::DROP_NEW_LINE);
        $csv->setCsvControl(',');

        $header = null;
        $rows = [];
        $rowCount = 0;
        $upserted = 0;

        foreach ($csv as $index => $row) {
            if ($row === [null] || $row === false) {
                continue;
            }

            $row = array_map(fn ($v) => self::cleanUtf8((string) $v), $row);

            if ($header === null) {
                $header = $row;

                continue;
            }

            if (count($row) === 1 && $row[0] === '') {
                continue;
            }
            $rowCount++;

            $data = array_combine($header, array_pad($row, count($header), null));
            if (! $data) {
                continue;
            }

            // Map columns
            $mapped = [
                'unique_key' => $data['UNIQUE_KEY'] ?? null,
                'product_title' => $data['PRODUCT_TITLE'] ?? null,
                'product_description' => $data['PRODUCT_DESCRIPTION'] ?? null,
                'style_number' => $data['STYLE#'] ?? null,
                'sanmar_mainframe_color' => $data['SANMAR_MAINFRAME_COLOR'] ?? null,
                'size' => $data['SIZE'] ?? null,
                'color_name' => $data['COLOR_NAME'] ?? null,
                'piece_price' => is_numeric($data['PIECE_PRICE'] ?? null) ? (float) $data['PIECE_PRICE'] : null,
            ];

            if (! filled($mapped['unique_key'])) {
                continue;
            }
            $rows[] = $mapped;

            if (count($rows) >= 1000) {
                $upserted += $this->bulkUpsert($rows);
                $upload->increment('processed_count', count($rows));
                $rows = [];
            }
        }

        if (! empty($rows)) {
            $upserted += $this->bulkUpsert($rows);
            $upload->increment('processed_count', count($rows));
        }

        $upload->update([
            'status' => 'completed',
            'row_count' => $rowCount,
            'upserted_count' => $upserted,
        ]);

        if ($upload->user_id) {
            $upload->refresh();
            Notification::send(optional($upload->user)->only('id') ? [$upload->user] : [], new UploadProcessed($upload));
        }
    }

    private function bulkUpsert(array $rows): int
    {
        Product::upsert($rows, ['unique_key'], [
            'product_title', 'product_description', 'style_number', 'sanmar_mainframe_color', 'size', 'color_name', 'piece_price', 'updated_at',
        ]);

        return collect($rows)->pluck('unique_key')->unique()->count();
    }

    public function failed(\Throwable $e): void
    {
        if ($upload = Upload::find($this->uploadId)) {
            $upload->update(['status' => 'failed', 'error' => $e->getMessage()]);
        }
        Log::error('Upload processing failed', ['upload_id' => $this->uploadId, 'error' => $e->getMessage()]);
    }

    private static function cleanUtf8(?string $value): string
    {
        if ($value === null) {
            return '';
        }

        $v = @iconv('UTF-8', 'UTF-8//IGNORE', $value);
        if ($v === false) {
            $v = @iconv('ISO-8859-1', 'UTF-8//IGNORE', $value) ?: $value;
        }

        return trim($v);
    }
}
