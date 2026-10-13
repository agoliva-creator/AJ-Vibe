<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ImageService
{
    public function store(UploadedFile $file, string $directory): string
    {
        return $file->store($directory, config('commerce.image_disk'));
    }

    public function delete(?string $path): void
    {
        if ($path) {
            Storage::disk(config('commerce.image_disk'))->delete($path);
        }
    }

    public function url(?string $path): ?string
    {
        return $path ? Storage::disk(config('commerce.image_disk'))->url($path) : null;
    }
}
