<?php

namespace App\Models;

use Database\Factories\CourseFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'description', 'status'])]
class Course extends Model
{
    /** @use HasFactory<CourseFactory> */
    use HasFactory;

    public function levels(): HasMany
    {
        return $this->hasMany(Level::class);
    }

    public function promotions(): HasMany
    {
        return $this->hasMany(Promotion::class);
    }
}
