<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Investment\Models\Investment;

class InvestmentSeeder extends Seeder
{
    public function run(): void
    {
        Investment::updateOrCreate(['title' => 'المناطق الصناعية'], ['thumbnail' => '/placeholders/investment1.jpg', 'order' => 1]);
        Investment::updateOrCreate(['title' => 'الفرص الاستثمارية'], ['thumbnail' => '/placeholders/investment2.jpg', 'order' => 2]);
    }
}
