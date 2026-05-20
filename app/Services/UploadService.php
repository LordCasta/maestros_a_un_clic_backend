<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class UploadService
{
    protected string $disk = 'public';

    public function __construct()
    {
        // Use the public disk for profile and content uploads by default.
        $this->disk = 'public';
    }

    /**
     * Store uploaded file under given folder and return stored path
     */
    public function store(UploadedFile $file, string $folder): string
    {
        $path = $file->store($folder, $this->disk);
        return $path;
    }

    public function url(string $path): string
    {
        return Storage::disk($this->disk)->url($path);
    }
}


