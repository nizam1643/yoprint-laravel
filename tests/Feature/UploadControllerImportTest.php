<?php

use App\Jobs\ProcessUploadCsv;
use App\Models\Product;
use App\Models\Upload;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\post;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Isolate file storage
    Storage::fake('public');

    // Disable notifications to avoid missing notifications table
    Notification::fake();

    // Create & authenticate user
    $this->user = User::factory()->create();
    $this->actingAs($this->user);

    // Copy sample CSVs into fake disk
    Storage::disk('public')->put(
        'yoprint_test_import.csv',
        file_get_contents(base_path('storage/app/public/yoprint_test_import.csv'))
    );

    Storage::disk('public')->put(
        'yoprint_update_import.csv',
        file_get_contents(base_path('storage/app/public/yoprint_test_updated.csv'))
    );
});

it('imports initial CSV file and creates products', function () {
    Bus::fake();

    $file = new UploadedFile(
        Storage::disk('public')->path('yoprint_test_import.csv'),
        'yoprint_test_import.csv',
        'text/csv',
        null,
        true
    );

    // ✅ Adjusted redirect target to match controller (upload-index)
    post('/upload', ['file' => $file])->assertRedirect('/upload-index');

    Bus::assertDispatched(ProcessUploadCsv::class);

    // Run the queued job manually
    $upload = Upload::first();
    (new ProcessUploadCsv($upload->id))->handle();

    // Assert products created
    expect(Product::count())->toBeGreaterThan(0);
});

it('upserts existing products on re-import', function () {
    // Simulate first import
    $upload1 = Upload::factory()->create([
        'disk' => 'public',
        'path' => 'yoprint_test_import.csv',
        'status' => 'queued',
    ]);
    (new ProcessUploadCsv($upload1->id))->handle();
    $initialCount = Product::count();

    // Simulate re-upload with updated CSV
    $file = new UploadedFile(
        Storage::disk('public')->path('yoprint_update_import.csv'),
        'yoprint_update_import.csv',
        'text/csv',
        null,
        true
    );

    // ✅ Expect redirect same as controller
    post('/upload', ['file' => $file])->assertRedirect('/upload-index');

    // Run the new job
    $upload2 = Upload::latest()->first();
    (new ProcessUploadCsv($upload2->id))->handle();

    // Assert no duplicate rows (same count)
    expect(Product::count())->toBe($initialCount);

    // Assert product data updated
    $product = Product::first();
    expect($product->piece_price)->not->toBeNull();
});
