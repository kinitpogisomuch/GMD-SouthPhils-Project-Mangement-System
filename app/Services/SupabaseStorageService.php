<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SupabaseStorageService
{
    protected ?string $url;
    protected ?string $serviceKey;
    protected string $bucket;

    public function __construct()
    {
        $this->url        = config('services.supabase.url');
        $this->serviceKey = config('services.supabase.service_key');
        $this->bucket     = 'project-photos';
    }

    public function upload(UploadedFile $file, string $folder = 'updates'): string|null
    {
        if (!$this->url || !$this->serviceKey) {
            return $this->uploadLocal($file, $folder);
        }

        try {
            $extension = $file->getClientOriginalExtension();
            $filename  = $folder . '/' . Str::uuid() . '.' . $extension;
            $contents  = file_get_contents($file->getRealPath());
            $mimeType  = $file->getMimeType();

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->serviceKey,
                'Content-Type'  => $mimeType,
                'x-upsert'      => 'true',
            ])->withBody($contents, $mimeType)
              ->post("{$this->url}/storage/v1/object/{$this->bucket}/{$filename}");

            if ($response->successful()) {
                return "{$this->url}/storage/v1/object/public/{$this->bucket}/{$filename}";
            }
        } catch (\Exception $e) {
            // Network unreachable (DNS failure, timeout, etc.) — fall through to local storage
        }

        return $this->uploadLocal($file, $folder);
    }

    public function uploadMultiple(array $files, string $folder = 'updates'): array
    {
        $urls = [];
        foreach ($files as $file) {
            $url = $this->upload($file, $folder);
            if ($url) {
                $urls[] = $url;
            }
        }
        return $urls;
    }

    protected function uploadLocal(UploadedFile $file, string $folder): string|null
    {
        $extension = $file->getClientOriginalExtension();
        $filename  = $folder . '/' . Str::uuid() . '.' . $extension;

        $stored = Storage::disk('public')->put($filename, file_get_contents($file->getRealPath()));

        if ($stored) {
            return asset('storage/' . $filename);
        }

        return null;
    }
}
