<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Upload File') }}
        </h2>
    </x-slot>

    <div class="py-12 flex justify-center">
        <div class="w-full max-w-5xl px-4"
             x-data="{
                uploads: [],
                loading: false,
                message: '{{ session('status') }}',
                file: null,
                dragover: false,
                async fetchUploads() {
                    const res = await fetch('{{ route('uploads.list') }}');
                    return await res.json();
                },
                async uploadFile() {
                    if (!this.file) return;

                    this.loading = true;
                    const formData = new FormData();
                    formData.append('file', this.file);

                    const res = await fetch('{{ route('upload.store') }}', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: formData
                    });

                    if (res.ok) {
                        this.message = 'Upload successful!';
                        this.file = null;
                        this.fetchUploads().then(d => { this.uploads = d.data; });
                    } else {
                        this.message = 'Upload failed!';
                    }
                    this.loading = false;
                }
            }"
            x-init="
                loading = true;
                fetchUploads().then(d => { uploads = d.data; }).finally(() => loading = false);
                setInterval(async () => { uploads = (await fetchUploads()).data; }, 2500);
            "
        >
            <h1 class="text-2xl font-semibold mb-6 text-center">CSV Uploader</h1>

            <!-- Drag & Drop Upload Zone -->
            <div class="bg-white p-8 rounded-2xl shadow mb-10 text-center border-2 border-dashed"
                :class="dragover ? 'border-indigo-500 bg-indigo-50' : 'border-gray-300'"
                @dragover.prevent="dragover = true"
                @dragleave.prevent="dragover = false"
                @drop.prevent="
                    dragover = false;
                    file = $event.dataTransfer.files[0];
                    uploadFile();
                ">

                <template x-if="!file">
                    <div>
                        <p class="text-gray-600">Drag & drop your <strong>CSV file</strong> here, or</p>
                        <label class="mt-2 inline-block px-4 py-2 bg-indigo-600 text-white rounded-md cursor-pointer hover:bg-indigo-700 transition">
                            Browse
                            <input type="file" class="hidden" accept=".csv,text/csv"
                                   @change="file = $event.target.files[0]; uploadFile();" />
                        </label>
                    </div>
                </template>

                <template x-if="file">
                    <div>
                        <p class="text-gray-700">Uploading: <strong x-text="file.name"></strong></p>
                    </div>
                </template>

                <template x-if="message">
                    <p class="mt-3 text-green-700 font-medium" x-text="message"></p>
                </template>

                <template x-if="loading">
                    <p class="mt-3 text-gray-500 italic">Processing...</p>
                </template>
            </div>

            <!-- Recent Uploads Table -->
            <div class="bg-white rounded-2xl shadow">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-medium">Recent Uploads</h2>
                </div>
                <div class="p-6" x-show="!loading" x-cloak>
                    <table class="min-w-full text-sm table-auto">
                        <thead>
                            <tr class="text-left text-gray-500 border-b">
                                <th class="py-2">File</th>
                                <th class="py-2">Uploaded</th>
                                <th class="py-2">Status</th>
                                <th class="py-2">Rows</th>
                                <th class="py-2">Processed</th>
                                <th class="py-2">Upserted</th>
                                <th class="py-2">Error</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="u in uploads" :key="u.id">
                                <tr class="border-t hover:bg-gray-50">
                                    <td class="py-2" x-text="u.original_name"></td>
                                    <td class="py-2" x-text="u.uploaded_at"></td>
                                    <td class="py-2">
                                        <span x-text="u.status"
                                            :class="{
                                                'text-amber-600': u.status==='queued' || u.status==='processing',
                                                'text-green-700': u.status==='completed',
                                                'text-red-700': u.status==='failed'
                                            }"></span>
                                    </td>
                                    <td class="py-2" x-text="u.row_count"></td>
                                    <td class="py-2" x-text="u.processed_count"></td>
                                    <td class="py-2" x-text="u.upserted_count"></td>
                                    <td class="py-2 text-red-700 truncate max-w-xs" x-text="u.error"></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
                <div class="p-6 text-gray-500" x-show="loading && uploads.length === 0">Loading…</div>
            </div>
        </div>
    </div>
</x-app-layout>
