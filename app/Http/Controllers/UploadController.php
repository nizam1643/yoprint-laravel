<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUploadRequest;
use App\Http\Resources\UploadResource;
use App\Interfaces\UploadRepositoryInterface;
use App\Services\UploadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class UploadController extends Controller
{
    public function __construct(
        protected UploadService $service,
        protected UploadRepositoryInterface $repository
    ) {}

    public function index(): View
    {
        return view('upload.index');
    }

    public function store(StoreUploadRequest $request): RedirectResponse
    {
        $upload = $this->service->handleUpload($request->file('file'));

        return redirect()
            ->route('upload.index')
            ->with('status', 'File queued: '.$upload->original_name);
    }

    public function list()
    {
        $uploads = $this->repository->latest();

        return UploadResource::collection($uploads);
    }
}
