<?php

namespace Modules\Content\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Content\Models\Category;
use Modules\Content\Models\Post;
use Illuminate\Http\Request;

class PostController extends Controller
{
    public function index(Request $request)
    {
        $categorySlug = $request->query('category');

        $query = Post::where('is_published', true)
            ->whereHas('category', fn($q) => $q->where('slug', '!=', 'events'));

        if ($categorySlug) {
            $query->whereHas('category', fn($q) => $q->where('slug', $categorySlug));
        }

        $posts = $query->latest('published_at')->paginate(9);

        $categories = Category::where('slug', '!=', 'events')
            ->withCount(['posts' => fn($q) => $q->where('is_published', true)])
            ->get();

        return view('posts.index', [
            'posts' => $posts,
            'categories' => $categories,
            'activeCategory' => $categorySlug,
        ]);
    }

    public function show(Post $post)
    {
        // Abort with a 404 error if the post is not published
        // You might want to allow admins to preview unpublished posts later
        if (!$post->is_published) {
            abort(404);
        }

        // Load the post with its category and approved comments
        $post->load(['category', 'comments' => function ($query) {
            $query->where('is_approved', true)->latest();
        }]);

        // Get some related posts (e.g., from the same category)
        $relatedPosts = Post::where('is_published', true)
            ->where('category_id', $post->category_id)
            ->where('id', '!=', $post->id) // Exclude the current post
            ->latest()
            ->take(3)
            ->get();

        return view('posts.show', [
            'post' => $post,
            'relatedPosts' => $relatedPosts,
        ]);
    }
}
