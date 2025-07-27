<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Jobs\GenerateArticleSlug;
use App\Jobs\GenerateArticleSummary;

class ArticleController extends Controller
{
    public function index(Request $request)
    {
        $query = Article::with(['author', 'categories'])
            ->when($request->user()->hasRole('author'), function ($query) use ($request) {
                return $query->where('user_id', $request->user()->id);
            });

        // Filter by category
        if ($request->has('category')) {
            $query->whereHas('categories', function ($q) use ($request) {
                $q->where('slug', $request->category);
            });
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by date range
        if ($request->has('from_date')) {
            $query->whereDate('published_date', '>=', $request->from_date);
        }
        if ($request->has('to_date')) {
            $query->whereDate('published_date', '<=', $request->to_date);
        }

        return response()->json($query->paginate(10));
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'categories' => 'required|array',
            'categories.*' => 'exists:categories,id',
            'status' => 'required|in:draft,published,archived',
            'published_date' => 'nullable|date',
        ]);

        $article = Article::create([
            'title' => $request->title,
            'content' => $request->content,
            'status' => $request->status,
            'published_date' => $request->status === 'published' ? ($request->published_date ?? now()) : null,
            'user_id' => Auth::id(),
        ]);

        $article->categories()->sync($request->categories);

        // Dispatch jobs to generate slug and summary asynchronously
        GenerateArticleSlug::dispatch($article);
        GenerateArticleSummary::dispatch($article);

        return response()->json($article->load('categories'), 201);
    }

    public function show(Article $article)
    {
        $this->authorize('view', $article);
        return response()->json($article->load(['author', 'categories']));
    }

    public function update(Request $request, Article $article)
    {
        $this->authorize('update', $article);

        $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'content' => 'sometimes|required|string',
            'categories' => 'sometimes|required|array',
            'categories.*' => 'exists:categories,id',
            'status' => 'sometimes|required|in:draft,published,archived',
            'published_date' => 'nullable|date',
        ]);

        $article->update([
            'title' => $request->title ?? $article->title,
            'content' => $request->content ?? $article->content,
            'status' => $request->status ?? $article->status,
            'published_date' => $request->has('status') && $request->status === 'published'
                ? ($request->published_date ?? now())
                : ($request->published_date ?? $article->published_date),
        ]);

        if ($request->has('categories')) {
            $article->categories()->sync($request->categories);
        }

        // If title or content changed, regenerate slug and summary
        if ($request->has('title') || $request->has('content')) {
            GenerateArticleSlug::dispatch($article);
            GenerateArticleSummary::dispatch($article);
        }

        return response()->json($article->load(['author', 'categories']));
    }

    public function destroy(Article $article)
    {
        $this->authorize('delete', $article);
        $article->delete();
        return response()->json(null, 204);
    }
}
