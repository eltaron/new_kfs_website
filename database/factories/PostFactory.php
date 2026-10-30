<?php

namespace Database\Factories;

use Modules\Content\Models\Category;
use Modules\Content\Models\Post;
use Database\Seeders\Support\DemoData;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Post> — خبر عربي تجريبي بصورة مولدة محلياً (بدون Faker). */
class PostFactory extends Factory
{
    protected $model = Post::class;

    public function definition(): array
    {
        $center = DemoData::pick(DemoData::centerNames());
        $title = DemoData::pick(['جولة ميدانية لمتابعة المشروعات الخدمية بمركز', 'افتتاح مشروعات جديدة بمدينة', 'قافلة خدمية شاملة لأهالي']) . ' ' . $center;

        return [
            'category_id' => fn () => Category::query()->value('id') ?? Category::create(['name' => 'أخبار المحافظة', 'slug' => 'news'])->id,
            'title' => $title,
            'slug' => 'post-' . Str::lower(Str::random(10)),
            'thumbnail' => DemoData::image('posts', 'factory-' . mt_rand(1, 8)),
            'content' => '<p>' . $title . '، ضمن خطة المحافظة لتحسين جودة الخدمات المقدمة للمواطنين.</p>',
            'is_published' => true,
            'published_at' => now(),
            'allow_comments' => true,
        ];
    }
}
