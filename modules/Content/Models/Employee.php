<?php

namespace Modules\Content\Models;

use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    protected $fillable = ['name', 'national_id'];

    /** The factory stays in database/factories (legacy location, see docs/architecture.md). */
    protected static function newFactory()
    {
        return \Database\Factories\EmployeeFactory::new();
    }
}
