<?php

namespace App\Services\Ollama;

use App\Models\Document;
use App\Services\Documents\DocumentImageService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class OllamaQwenClient
{
    protected string $baseUrl;
    protected string $model;
    protected int $timeout;
    protected DocumentImageService $imageService;

    public function __construct(DocumentImageService $imageService)
    {
        $this->baseUrl = config('ollama.base_url', 'http://127.0.0.1:11434');
        $this->model = config('ollama.model', 'qwen3-vl:8b');
        $this->timeout = config('ollama.timeout', 120);
        $this->imageService = $imageService;
    }

    /**
     * Classify a document as invoice, contract, or general.
     *
     * @param Document $document
     * @return array ['category' => 'invoice'|'contract'|'general']
     * @throws RuntimeException
     */
    public function classify(Document $document): array
    {
        $prompt = <<<'PROMPT'
You are a document classification AI. Analyze the provided document image and classify it into one of these categories:
- "invoice" - for invoices, bills, receipts, or payment documents
- "contract" - for contracts, agreements, terms of service, or legal documents
- "general" - for any other type of document

Return ONLY valid JSON with this exact structure, no markdown formatting or additional text:
{
  "category": "invoice"
}

Replace "invoice" with the appropriate category. Return ONLY the JSON object.
PROMPT;

        $response = $this->sendChatRequest($document, $prompt);

        $result = $this->decodeJson($response);

        if (!isset($result['category']) || !in_array($result['category'], ['invoice', 'contract', 'general'])) {
            Log::warning('Invalid classification response', ['response' => $result]);
            return ['category' => 'general'];
        }

        return $result;
    }

    /**
     * Extract structured invoice data from a document.
     *
     * @param Document $document
     * @return array Structured invoice data
     * @throws RuntimeException
     */
    public function extractInvoice(Document $document): array
    {
        $prompt = <<<'PROMPT'
You are an invoice data extraction AI. Extract all relevant information from this invoice image.

Return ONLY valid JSON with this exact structure (no markdown, no code blocks, just raw JSON):
{
  "vendor_name": "string or null",
  "vendor_address": "string or null",
  "invoice_number": "string or null",
  "invoice_date": "YYYY-MM-DD or null",
  "due_date": "YYYY-MM-DD or null",
  "currency": "USD or null",
  "subtotal": 0.00,
  "tax": 0.00,
  "total": 0.00,
  "lines": [
    {
      "description": "Item description",
      "quantity": 1.0,
      "unit_price": 0.00,
      "line_total": 0.00
    }
  ],
  "confidence": 0.85
}

Rules:
- Use null for missing values
- Use numbers (not strings) for numeric fields
- Use ISO date format (YYYY-MM-DD) for dates
- confidence should be a decimal between 0.0 and 1.0
- Extract all line items into the "lines" array
- If you cannot find a field, set it to null
- Return ONLY the JSON object, no other text
PROMPT;

        $response = $this->sendChatRequest($document, $prompt);

        return $this->decodeJson($response);
    }

    /**
     * Extract and summarize contract data from a document.
     *
     * @param Document $document
     * @return array Structured contract data
     * @throws RuntimeException
     */
    public function summarizeContract(Document $document): array
    {
        $prompt = <<<'PROMPT'
You are a contract analysis AI. Extract key information from this contract and provide a summary.

Return ONLY valid JSON with this exact structure (no markdown, no code blocks, just raw JSON):
{
  "party_a": "First party name or null",
  "party_b": "Second party name or null",
  "effective_date": "YYYY-MM-DD or null",
  "expiration_date": "YYYY-MM-DD or null",
  "summary": "A brief 2-3 sentence summary of the contract's purpose and key terms",
  "risk_score": 0,
  "risk_notes": "Any concerning clauses or risk factors identified"
}

Rules:
- Use null for missing values
- Use ISO date format (YYYY-MM-DD) for dates
- risk_score is an integer from 0 to 100 (0 = no risk, 100 = high risk)
- Identify party_a as the first party mentioned (often the provider/licensor)
- Identify party_b as the second party (often the client/licensee)
- In the summary, mention the contract type and main obligations
- In risk_notes, highlight any unfavorable terms, auto-renewal clauses, liability limitations, etc.
- Return ONLY the JSON object, no other text
PROMPT;

        $response = $this->sendChatRequest($document, $prompt);

        return $this->decodeJson($response);
    }

    /**
     * Send a chat request to Ollama with an image.
     *
     * @param Document $document
     * @param string $userPrompt
     * @return string Raw response content
     * @throws RuntimeException
     */
    protected function sendChatRequest(Document $document, string $userPrompt): string
    {
        try {
            $base64Image = $this->imageService->toBase64Image($document);

            $payload = [
                'model' => $this->model,
                'prompt' => $userPrompt,
                'images' => [$base64Image],
                'stream' => false,
            ];

            Log::info('Sending request to Ollama', [
                'model' => $this->model,
                'document_id' => $document->id,
                'endpoint' => $this->baseUrl . '/api/generate',
            ]);

            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                ])
                ->post($this->baseUrl . '/api/generate', $payload);

            if (!$response->successful()) {
                throw new RuntimeException(
                    "Ollama API request failed with status {$response->status()}: {$response->body()}"
                );
            }

            $data = $response->json();

            if (!isset($data['response'])) {
                throw new RuntimeException('Invalid response format from Ollama API');
            }

            return $data['response'];
        } catch (\Exception $e) {
            Log::error('Ollama request failed', [
                'document_id' => $document->id,
                'error' => $e->getMessage(),
            ]);

            throw new RuntimeException("Failed to communicate with Ollama: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * Decode JSON from Ollama response, handling common issues.
     *
     * @param string $raw Raw response content
     * @return array Decoded JSON data
     * @throws RuntimeException
     */
    protected function decodeJson(string $raw): array
    {
        // Trim whitespace
        $raw = trim($raw);

        // Remove markdown code blocks if present
        $raw = preg_replace('/^```json\s*/m', '', $raw);
        $raw = preg_replace('/^```\s*/m', '', $raw);
        $raw = trim($raw);

        // Try to extract JSON if there's extra text
        if (!str_starts_with($raw, '{') && !str_starts_with($raw, '[')) {
            if (preg_match('/\{(?:[^{}]|(?R))*\}/s', $raw, $matches)) {
                $raw = $matches[0];
            }
        }

        // Attempt to decode
        try {
            $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);

            if (!is_array($decoded)) {
                throw new RuntimeException('Decoded JSON is not an array');
            }

            return $decoded;
        } catch (\JsonException $e) {
            Log::error('JSON decode failed', [
                'raw_response' => $raw,
                'error' => $e->getMessage(),
            ]);

            // Return error structure
            return [
                '_error' => 'Failed to decode JSON response',
                '_raw' => $raw,
                '_exception' => $e->getMessage(),
            ];
        }
    }

    /**
     * Test connection to Ollama server.
     *
     * @return bool True if connection successful
     */
    public function testConnection(): bool
    {
        try {
            $response = Http::timeout(5)->get($this->baseUrl . '/api/tags');
            return $response->successful();
        } catch (\Exception $e) {
            Log::error('Ollama connection test failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Get available models from Ollama.
     *
     * @return array List of available models
     */
    public function getAvailableModels(): array
    {
        try {
            $response = Http::timeout(5)->get($this->baseUrl . '/api/tags');

            if (!$response->successful()) {
                return [];
            }

            $data = $response->json();
            return $data['models'] ?? [];
        } catch (\Exception $e) {
            Log::error('Failed to get Ollama models', ['error' => $e->getMessage()]);
            return [];
        }
    }
}
