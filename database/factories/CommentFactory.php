<?php

namespace Database\Factories;

use Modules\Content\Models\Comment;
use Modules\Content\Models\Post;
use Database\Seeders\Support\DemoData;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Comment> */
class CommentFactory extends Factory
{
    protected $model = Comment::class;

    public function definition(): array
    {
        return [
            'post_id' => fn () => Post::query()->inRandomOrder()->value('id') ?? Post::factory(),
            'author_name' => DemoData::name(),
            'author_email' => 'reader' . mt_rand(100, 999) . '@example.com',
            'content' => DemoData::pick(['جهود مشكورة.', 'نتمنى تعميم التجربة على باقي القرى.', 'خطوة ممتازة لخدمة الأهالي.']),
            'is_approved' => DemoData::chance(80),
        ];
    }
}
