<?php

namespace App\Services;

use Google\Cloud\AIPlatform\V1\PredictServiceClient;
use Google\Cloud\AIPlatform\V1\Content;
use Google\Cloud\AIPlatform\V1\Part;
use Google\Cloud\AIPlatform\V1\PredictResponse;

class GeminiService
{
    protected $client;
    protected $model;

    public function __construct()
    {
        $this->client = new PredictServiceClient([
            'credentials' => config('services.gemini.key'),
            'apiEndpoint' => 'us-central1-aiplatform.googleapis.com',
        ]);

        $this->model = 'projects/' . config('services.gemini.project_id') .
                       '/locations/us-central1/publishers/google/models/gemini-pro';
    }

    public function generateText(string $prompt): string
    {
        try {
            $content = (new Content())
                ->setRole('user')
                ->setParts([(new Part())->setText($prompt)]);

            $response = $this->client->predict($this->model, [$content]);

            if ($response instanceof PredictResponse) {
                $candidates = $response->getPredictions();
                if (count($candidates) > 0 && $candidates[0]->hasContent()) {
                    return $candidates[0]->getContent()->getParts()[0]->getText();
                }
            }

            throw new \Exception('No valid response from Gemini API');
        } catch (\Exception $e) {
            \Log::error('Gemini API error: ' . $e->getMessage());
            throw $e;
        }
    }
}
