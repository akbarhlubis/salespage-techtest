<?php
// File: app/Services/ClaudeService.php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ClaudeService
{
    private string $apiKey;
    private string $model = 'claude-sonnet-4-20250514';
    private string $baseUrl = 'https://api.anthropic.com/v1/messages';

    public function __construct()
    {
        $this->apiKey = config('services.anthropic.key');
    }

    /**
     * Generate a complete sales page from product data.
     * Returns structured JSON with all sections.
     */
    public function generateSalesPage(array $productData): array
    {
        $prompt = $this->buildPrompt($productData);

        $response = Http::withHeaders([
            'x-api-key'         => $this->apiKey,
            'anthropic-version' => '2023-06-01',
            'content-type'      => 'application/json',
        ])->timeout(60)->post($this->baseUrl, [
            'model'      => $this->model,
            'max_tokens' => 4000,
            'messages'   => [
                ['role' => 'user', 'content' => $prompt]
            ],
        ]);

        if ($response->failed()) {
            Log::error('Claude API error', ['status' => $response->status(), 'body' => $response->body()]);
            throw new \Exception('AI generation failed. Please try again.');
        }

        $content = $response->json('content.0.text');

        // Extract JSON from response
        $jsonStart = strpos($content, '{');
        $jsonEnd   = strrpos($content, '}');

        if ($jsonStart === false || $jsonEnd === false) {
            throw new \Exception('Invalid AI response format.');
        }

        $jsonStr = substr($content, $jsonStart, $jsonEnd - $jsonStart + 1);
        $data    = json_decode($jsonStr, true);

        if (!$data) {
            throw new \Exception('Could not parse AI response.');
        }

        return $data;
    }

    /**
     * Regenerate a specific section only (bonus feature).
     */
    public function regenerateSection(array $productData, string $section, array $existingData): string
    {
        $sectionLabels = [
            'headline'    => 'main headline and sub-headline',
            'description' => 'product description paragraph',
            'benefits'    => 'benefits section (3-5 bullet points)',
            'cta'         => 'call-to-action text and button label',
        ];

        $label = $sectionLabels[$section] ?? $section;

        $prompt = "You are a conversion copywriter. Rewrite ONLY the {$label} for this product.\n\n"
            . "Product: {$productData['product_name']}\n"
            . "Description: {$productData['description']}\n"
            . "Target Audience: {$productData['target_audience']}\n"
            . "Price: {$productData['price']}\n\n"
            . "Return ONLY the new text for that section, nothing else.";

        $response = Http::withHeaders([
            'x-api-key'         => $this->apiKey,
            'anthropic-version' => '2023-06-01',
            'content-type'      => 'application/json',
        ])->timeout(30)->post($this->baseUrl, [
            'model'      => $this->model,
            'max_tokens' => 800,
            'messages'   => [['role' => 'user', 'content' => $prompt]],
        ]);

        return $response->json('content.0.text', '');
    }

    private function buildPrompt(array $d): string
    {
        $features = is_array($d['features'])
            ? implode(', ', $d['features'])
            : $d['features'];

        return <<<PROMPT
You are an expert conversion copywriter. Generate a complete sales page for this product.

PRODUCT DATA:
- Name: {$d['product_name']}
- Description: {$d['description']}
- Key Features: {$features}
- Target Audience: {$d['target_audience']}
- Price: {$d['price']}
- Unique Selling Points: {$d['unique_selling_points']}

Return ONLY a valid JSON object with this exact structure (no markdown, no backticks):
{
  "headline": "compelling main headline (max 10 words)",
  "sub_headline": "supporting sub-headline that clarifies the value (max 20 words)",
  "product_description": "2-3 sentence engaging product description",
  "benefits": [
    {"icon": "emoji", "title": "benefit title", "description": "1 sentence description"},
    {"icon": "emoji", "title": "benefit title", "description": "1 sentence description"},
    {"icon": "emoji", "title": "benefit title", "description": "1 sentence description"},
    {"icon": "emoji", "title": "benefit title", "description": "1 sentence description"}
  ],
  "features": [
    {"name": "feature name", "detail": "brief detail"},
    {"name": "feature name", "detail": "brief detail"},
    {"name": "feature name", "detail": "brief detail"}
  ],
  "social_proof": [
    {"name": "Customer Name", "role": "Job Title / Company", "quote": "realistic testimonial quote"},
    {"name": "Customer Name", "role": "Job Title / Company", "quote": "realistic testimonial quote"},
    {"name": "Customer Name", "role": "Job Title / Company", "quote": "realistic testimonial quote"}
  ],
  "pricing": {
    "price": "{$d['price']}",
    "note": "short pricing note (e.g. one-time payment, per month, etc.)",
    "includes": ["what's included 1", "what's included 2", "what's included 3"]
  },
  "cta": {
    "primary_text": "Get [Product Name] Now",
    "secondary_text": "short urgency/trust line below button"
  },
  "seo_meta_description": "160 char meta description for this product"
}
PROMPT;
    }
}
