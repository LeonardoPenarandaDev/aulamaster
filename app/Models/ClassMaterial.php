<?php

namespace App\Models;

use Database\Factories\ClassMaterialFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * Material de una clase: video de YouTube, enlace, PDF o imagen (parte 12
 * del plan de mejoras). Los archivos van al disco privado y se borran junto
 * con el material.
 */
#[Fillable([
    'class_session_id',
    'type',
    'title',
    'url',
    'file_path',
    'file_name',
    'file_size',
    'mime_type',
    'description',
    'created_by_id',
])]
class ClassMaterial extends Model
{
    /** @use HasFactory<ClassMaterialFactory> */
    use HasFactory;

    /**
     * @var array<string, string>
     */
    public const TYPES = [
        'youtube' => 'Video de YouTube',
        'enlace' => 'Enlace',
        'pdf' => 'PDF',
        'imagen' => 'Imagen',
    ];

    protected static function booted(): void
    {
        static::deleted(function (ClassMaterial $material) {
            if ($material->file_path) {
                Storage::disk('local')->delete($material->file_path);
            }
        });
    }

    public function classSession(): BelongsTo
    {
        return $this->belongsTo(ClassSession::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function isFile(): bool
    {
        return in_array($this->type, ['pdf', 'imagen'], true);
    }

    /**
     * Id del video para incrustarlo con youtube-nocookie.com.
     */
    public static function youtubeIdFrom(?string $url): ?string
    {
        if (! $url) {
            return null;
        }

        $pattern = '~(?:youtube\.com/(?:watch\?(?:.*&)?v=|embed/|shorts/|live/)|youtu\.be/)([A-Za-z0-9_-]{11})~';

        return preg_match($pattern, $url, $matches) ? $matches[1] : null;
    }

    /**
     * Datos para mostrar el material en el portal.
     *
     * @return array<string, mixed>
     */
    public function toPortalArray(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'title' => $this->title,
            'description' => $this->description,
            'url' => $this->isFile() ? null : $this->url,
            'youtube_id' => $this->type === 'youtube' ? self::youtubeIdFrom($this->url) : null,
            'file_url' => $this->isFile() ? route('class-materials.file', $this) : null,
            'file_name' => $this->file_name,
            'file_size' => $this->file_size,
        ];
    }
}
