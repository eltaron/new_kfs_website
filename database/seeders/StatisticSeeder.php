<?php

namespace Database\Seeders;

use Modules\Content\Models\Statistic;
use Illuminate\Database\Seeder;

class StatisticSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            ['التعداد السكاني للمحافظة', 3783559, 'fas fa-users'],
            ['المساحة الكلية للمحافظة (كم²)', 3683, 'fas fa-map-marked-alt'],
            ['عدد المراكز والمدن بالمحافظة', 13, 'fas fa-building'],
            ['عدد القرى', 207, 'fas fa-home'],
            ['المدارس', 1450, 'fas fa-school'],
            ['المستشفيات والوحدات الصحية', 318, 'fas fa-hospital'],
            ['طول الطرق المرصوفة (كم)', 4120, 'fas fa-road'],
            ['المشروعات القومية الجارية', 86, 'fas fa-hard-hat'],
        ];
        foreach ($rows as $i => [$title, $number, $icon]) {
            Statistic::updateOrCreate(['title' => $title], ['number' => $number, 'icon_class' => $icon, 'order' => $i + 1]);
        }
    }
}
