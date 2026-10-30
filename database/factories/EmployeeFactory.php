<?php

namespace Database\Factories;

use Modules\Content\Models\Employee;
use Database\Seeders\Support\DemoData;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Employee> */
class EmployeeFactory extends Factory
{
    protected $model = Employee::class;

    public function definition(): array
    {
        return ['name' => DemoData::name(), 'national_id' => DemoData::nationalId()];
    }
}
