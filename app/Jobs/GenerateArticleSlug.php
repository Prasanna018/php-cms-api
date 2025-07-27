<?php

namespace App\Jobs;

use App\Models\Article;
use App\Services\GeminiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

class GenerateArticleSlug implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $article;

    public function __construct(Article $article)
    {
        $this->article = $article;
    }

    public function handle(GeminiService $gemini)
    {
        try {
            $prompt = "Generate a unique, SEO-friendly slug for this article title: '{$this->article->title}'. " .
                      "The slug should be concise, use hyphens to separate words, and be based on the main keywords. " .
                      "Return only the slug, nothing else.";

            $slug = $gemini->generateText($prompt);

            // Clean up the response if needed
            $slug = Str::slug(trim($slug));

            // Ensure uniqueness
            $originalSlug = $slug;
            $count = 1;

            while (Article::where('slug', $slug)->where('id', '!=', $this->article->id)->exists()) {
                $slug = $originalSlug . '-' . $count++;
            }

            $this->article->update(['slug' => $slug]);
        } catch (\Exception $e) {
            // Fallback to Laravel's slug generator if Gemini fails
            $slug = Str::slug($this->article->title);
            $originalSlug = $slug;
            $count = 1;

            while (Article::where('slug', $slug)->where('id', '!=', $this->article->id)->exists()) {
                $slug = $originalSlug . '-' . $count++;
            }

            $this->article->update(['slug' => $slug]);
        }
    }
}
