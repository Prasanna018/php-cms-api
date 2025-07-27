<?php

namespace App\Jobs;

use App\Models\Article;
use App\Services\GeminiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateArticleSummary implements ShouldQueue
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
            $prompt = "Generate a concise 2-3 sentence summary of the following article content:\n\n" .
                      "Title: {$this->article->title}\n\n" .
                      "Content: {$this->article->content}\n\n" .
                      "The summary should capture the main points and be suitable for a preview. " .
                      "Return only the summary text, nothing else.";

            $summary = $gemini->generateText($prompt);

            // Clean up the response if needed
            $summary = trim($summary);

            $this->article->update(['summary' => $summary]);
        } catch (\Exception $e) {
            // Fallback to a simple summary if Gemini fails
            $summary = Str::limit($this->article->content, 200);
            $this->article->update(['summary' => $summary]);
        }
    }
}
