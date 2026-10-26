<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PhotoService
{
    public function store(UploadedFile $file, int $storeId): string
    {
        $invalid = fn () => ValidationException::withMessages(['photo' => 'Usa una foto JPEG, PNG o WebP de hasta 5 MiB y 4096 × 4096 píxeles.']);
        if (! $file->isValid() || $file->getSize() > 5 * 1024 * 1024
            || $file->getClientOriginalPath() !== $file->getClientOriginalName()
            || ! preg_match('/^[^.\/\\\\]+\.(jpe?g|png|webp)$/iD', $file->getClientOriginalName())) {
            throw $invalid();
        }
        $info = @getimagesize($file->getRealPath());
        if (! $info || ! in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP], true)
            || $info[0] > 4096 || $info[1] > 4096 || $info[0] < 1 || $info[1] < 1) {
            throw $invalid();
        }
        $image = @imagecreatefromstring(file_get_contents($file->getRealPath()));
        if (! $image) { throw $invalid(); }
        if ($info[2] === IMAGETYPE_JPEG) {
            $exif = @exif_read_data($file->getRealPath(), 'IFD0', true);
            $orientation = (int) ($exif['IFD0']['Orientation'] ?? 1);
            $angle = match ($orientation) { 3 => 180, 5, 6, 7 => -90, 8 => 90, default => 0 };
            if ($angle !== 0) {
                $rotated = imagerotate($image, $angle, 0);
                imagedestroy($image);
                if (! $rotated) { throw $invalid(); }
                $image = $rotated;
            }
            if (in_array($orientation, [2, 4, 5, 7], true)) {
                imageflip($image, in_array($orientation, [2, 5], true) ? IMG_FLIP_HORIZONTAL : IMG_FLIP_VERTICAL);
            }
        }
        // Una imagen nueva elimina EXIF, comentarios y datos anexos; siempre WebP.
        $clean = imagecreatetruecolor(imagesx($image), imagesy($image));
        imagealphablending($clean, false);
        imagesavealpha($clean, true);
        imagecopy($clean, $image, 0, 0, 0, 0, imagesx($image), imagesy($image));
        ob_start();
        try {
            if (! imagewebp($clean, null, 85)) { throw $invalid(); }
            $bytes = ob_get_contents();
        } finally {
            ob_end_clean();
            imagedestroy($clean);
            imagedestroy($image);
        }
        $path = $storeId.'/'.Str::uuid().'.webp';
        if (! Storage::disk('catalog')->put($path, $bytes)) {
            Storage::disk('catalog')->delete($path);
            throw ValidationException::withMessages(['photo' => 'No se pudo guardar la foto. El producto anterior se conserva.']);
        }
        return $path;
    }
}
