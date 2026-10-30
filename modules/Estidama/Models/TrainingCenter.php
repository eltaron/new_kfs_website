<?php

namespace Modules\Estidama\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TrainingCenter extends Model
{
    use HasFactory;

    protected $guarded = [];

    /**
     * Get all of the trainingPrograms for the TrainingCenter.
     */
    public function trainingPrograms(): HasMany
    {
        return $this->hasMany(TrainingProgram::class);
    }
}
