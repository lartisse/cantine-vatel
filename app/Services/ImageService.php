<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Validation, recadrage carré et compression WebP (GD) des photos de profil et de plats.
 * Le recadrage est fait dans le navigateur (Cropper.js) ; le serveur re-vérifie le type réel,
 * recadre au carré centré par sécurité, redimensionne et ré-encode en WebP.
 */
class ImageService
{
    public const RULES = ['required', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120'];

    public const MESSAGES = [
        'photo.max' => 'Image trop lourde (5 Mo maximum)',
        'photo.mimes' => 'Format non accepté (JPG, PNG ou WebP uniquement)',
        'photo.uploaded' => 'Image trop lourde (5 Mo maximum)',
    ];

    public function store(UploadedFile $file, string $dir, int $size = 512, ?string $old = null): string
    {
        $info = @getimagesize($file->getRealPath());
        if (! $info || ! in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP], true)) {
            throw ValidationException::withMessages(['photo' => 'Image illisible ou format non accepté (JPG, PNG ou WebP uniquement)']);
        }
        $src = match ($info[2]) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($file->getRealPath()),
            IMAGETYPE_PNG => @imagecreatefrompng($file->getRealPath()),
            IMAGETYPE_WEBP => @imagecreatefromwebp($file->getRealPath()),
        };
        if (! $src) {
            throw ValidationException::withMessages(['photo' => 'Image illisible']);
        }
        [$w, $h] = [imagesx($src), imagesy($src)];
        $side = min($w, $h);
        $dst = imagecreatetruecolor($size, $size);
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        imagecopyresampled($dst, $src, 0, 0, (int) (($w - $side) / 2), (int) (($h - $side) / 2), $size, $size, $side, $side);

        ob_start();
        imagewebp($dst, null, 80);
        $data = ob_get_clean();
        imagedestroy($src);
        imagedestroy($dst);

        $path = trim($dir, '/').'/'.Str::uuid().'.webp';
        Storage::disk('public')->put($path, $data);
        $this->delete($old);

        return $path;
    }

    public function delete(?string $path): void
    {
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
