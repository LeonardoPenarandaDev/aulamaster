<?php

namespace App\Actions\Students;

use App\Models\Student;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class StoreStudentPhoto
{
    /**
     * Lado máximo de la foto guardada, en píxeles.
     */
    private const MAX_SIZE = 600;

    /**
     * Guarda la foto de perfil del estudiante en el disco privado, girada
     * según la cámara y reducida a 600 px para que pese poco. Reemplaza la
     * foto anterior.
     */
    public function handle(Student $student, UploadedFile $file): string
    {
        $path = "students/photos/{$student->id}-".Str::random(12).'.jpg';

        Storage::disk('local')->put($path, $this->resize($file));

        $this->delete($student);
        $student->forceFill(['photo_path' => $path])->save();

        return $path;
    }

    public function delete(Student $student): void
    {
        if ($student->photo_path) {
            Storage::disk('local')->delete($student->photo_path);
            $student->forceFill(['photo_path' => null])->save();
        }
    }

    private function resize(UploadedFile $file): string
    {
        $image = imagecreatefromstring($file->get());
        $image = $this->orient($image, $file);

        $width = imagesx($image);
        $height = imagesy($image);
        $scale = min(1, self::MAX_SIZE / max($width, $height));
        $resized = imagescale($image, (int) round($width * $scale), (int) round($height * $scale));

        ob_start();
        imagejpeg($resized, null, 85);

        return (string) ob_get_clean();
    }

    /**
     * Las fotos del celular guardan la orientación en los datos EXIF.
     */
    private function orient(\GdImage $image, UploadedFile $file): \GdImage
    {
        if (! function_exists('exif_read_data') || $file->getMimeType() !== 'image/jpeg') {
            return $image;
        }

        $orientation = @exif_read_data($file->getRealPath())['Orientation'] ?? 1;

        return match ((int) $orientation) {
            3 => imagerotate($image, 180, 0),
            6 => imagerotate($image, -90, 0),
            8 => imagerotate($image, 90, 0),
            default => $image,
        };
    }
}
