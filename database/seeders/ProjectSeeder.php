<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Investment\Models\Project;

class ProjectSeeder extends Seeder
{
    public function run(): void
    {
        Project::updateOrCreate(
            ['name' => 'المشروعات القومية'],
            [
                'type' => 'image',
                'thumbnail' => '/placeholders/project1.jpg',
                'is_highlighted' => false,
                'description' => 'وصف تفصيلي للمشروعات القومية.'
            ]
        );
        Project::updateOrCreate(
            ['name' => 'حياة كريمة'],
            [
                'type' => 'logo',
                'thumbnail' => '/placeholders/hayah-karima-logo.png',
                'is_highlighted' => true,
                'description' => 'وصف تفصيلي لمبادرة حياة كريمة.'
            ]
        );
        Project::updateOrCreate(
            ['name' => 'الخطة الاستثمارية'],
            [
                'type' => 'image',
                'thumbnail' => '/placeholders/project3.jpg',
                'is_highlighted' => false,
                'description' => 'وصف تفصيلي للخطة الاستثمارية.'
            ]
        );
    }
}
