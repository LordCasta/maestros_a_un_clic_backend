<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * Único punto de guardado de archivos.
 *
 * - Público (`public`): avatares y portafolio. Se sirven por URL directa.
 * - Privado (`local`): documentos KYC y adjuntos del chat. Solo por URL firmada y temporal.
 */
class UploadService
{
    private const PUBLIC_DISK = 'public';

    private const PRIVATE_DISK = 'local';

    public function storePublic(UploadedFile $file, string $folder): string
    {
        return $file->store($folder, self::PUBLIC_DISK);
    }

    public function storePrivate(UploadedFile $file, string $folder): string
    {
        return $file->store($folder, self::PRIVATE_DISK);
    }

    public function publicUrl(string $path): string
    {
        return Storage::disk(self::PUBLIC_DISK)->url($path);
    }

    public function temporaryPrivateUrl(string $path, int $minutes = 10): string
    {
        return Storage::disk(self::PRIVATE_DISK)->temporaryUrl($path, Carbon::now()->addMinutes($minutes));
    }

    public function deletePublic(string ...$paths): void
    {
        Storage::disk(self::PUBLIC_DISK)->delete($paths);
    }

    public function deletePrivate(string ...$paths): void
    {
        Storage::disk(self::PRIVATE_DISK)->delete($paths);
    }
}
