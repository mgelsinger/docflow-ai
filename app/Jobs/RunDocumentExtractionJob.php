<?php

namespace App\Jobs;

use App\Enums\DocumentCategory;
use App\Enums\DocumentStatus;
use App\Models\Contract;
use App\Models\Document;
use App\Models\Invoice;
use App\Models\InvoiceLine;
use App\Services\Ollama\OllamaQwenClient;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RunDocumentExtractionJob implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    protected int $documentId;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 300;

    /**
     * Create a new job instance.
     */
    public function __construct(Document $document)
    {
        $this->documentId = $document->id;
    }

    /**
     * Execute the job.
     */
    public function handle(OllamaQwenClient $ollamaClient): void
    {
        try {
            // Reload document from database to avoid stale state
            $document = Document::findOrFail($this->documentId);

            Log::info('Starting document extraction', [
                'document_id' => $document->id,
                'filename' => $document->filename,
                'current_category' => $document->category?->value,
            ]);

            // Set status to processing
            $document->update(['status' => DocumentStatus::PROCESSING]);

            // Step 1: Classify if category is null or general
            if ($document->category === null || $document->category === DocumentCategory::GENERAL) {
                $classificationResult = $ollamaClient->classify($document);

                if (isset($classificationResult['category'])) {
                    $categoryValue = $classificationResult['category'];
                    $document->category = DocumentCategory::from($categoryValue);
                    $document->save();

                    Log::info('Document classified', [
                        'document_id' => $document->id,
                        'category' => $categoryValue,
                    ]);
                }
            }

            // Step 2: Extract data based on category
            match ($document->category) {
                DocumentCategory::INVOICE => $this->processInvoice($document, $ollamaClient),
                DocumentCategory::CONTRACT => $this->processContract($document, $ollamaClient),
                DocumentCategory::GENERAL => $this->processGeneral($document, $ollamaClient),
            };

            Log::info('Document extraction completed', [
                'document_id' => $document->id,
                'category' => $document->category->value,
            ]);
        } catch (\Exception $e) {
            Log::error('Document extraction failed', [
                'document_id' => $this->documentId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Update document status to failed
            Document::where('id', $this->documentId)->update([
                'status' => DocumentStatus::FAILED,
                'error_message' => $e->getMessage(),
            ]);

            // Re-throw to mark job as failed
            throw $e;
        }
    }

    /**
     * Process an invoice document.
     *
     * @param Document $document
     * @param OllamaQwenClient $ollamaClient
     * @return void
     */
    protected function processInvoice(Document $document, OllamaQwenClient $ollamaClient): void
    {
        $extractedData = $ollamaClient->extractInvoice($document);

        DB::transaction(function () use ($document, $extractedData) {
            // Save raw JSON to document
            $document->llm_json = $extractedData;

            // Validate invoice totals
            $validationWarnings = $this->validateInvoiceTotals($extractedData);
            if (!empty($validationWarnings)) {
                $document->error_message = implode('; ', $validationWarnings);
            }

            $document->status = DocumentStatus::EXTRACTED;
            $document->save();

            // Upsert invoice record
            $invoice = Invoice::updateOrCreate(
                ['document_id' => $document->id],
                [
                    'vendor_name' => $extractedData['vendor_name'] ?? null,
                    'vendor_address' => $extractedData['vendor_address'] ?? null,
                    'invoice_number' => $extractedData['invoice_number'] ?? null,
                    'invoice_date' => $this->parseDate($extractedData['invoice_date'] ?? null),
                    'due_date' => $this->parseDate($extractedData['due_date'] ?? null),
                    'subtotal' => $extractedData['subtotal'] ?? null,
                    'tax' => $extractedData['tax'] ?? null,
                    'total' => $extractedData['total'] ?? null,
                    'currency' => $extractedData['currency'] ?? 'USD',
                ]
            );

            // Delete existing line items and create new ones
            InvoiceLine::where('invoice_id', $invoice->id)->delete();

            if (isset($extractedData['lines']) && is_array($extractedData['lines'])) {
                foreach ($extractedData['lines'] as $lineData) {
                    InvoiceLine::create([
                        'invoice_id' => $invoice->id,
                        'description' => $lineData['description'] ?? null,
                        'quantity' => $lineData['quantity'] ?? 1,
                        'unit_price' => $lineData['unit_price'] ?? 0,
                        'line_total' => $lineData['line_total'] ?? 0,
                    ]);
                }
            }

            Log::info('Invoice processed', [
                'document_id' => $document->id,
                'invoice_id' => $invoice->id,
                'line_count' => count($extractedData['lines'] ?? []),
            ]);
        });
    }

    /**
     * Process a contract document.
     *
     * @param Document $document
     * @param OllamaQwenClient $ollamaClient
     * @return void
     */
    protected function processContract(Document $document, OllamaQwenClient $ollamaClient): void
    {
        $extractedData = $ollamaClient->summarizeContract($document);

        DB::transaction(function () use ($document, $extractedData) {
            // Save raw JSON to document
            $document->llm_json = $extractedData;
            $document->status = DocumentStatus::EXTRACTED;
            $document->save();

            // Upsert contract record
            $contract = Contract::updateOrCreate(
                ['document_id' => $document->id],
                [
                    'party_a' => $extractedData['party_a'] ?? null,
                    'party_b' => $extractedData['party_b'] ?? null,
                    'effective_date' => $this->parseDate($extractedData['effective_date'] ?? null),
                    'expiration_date' => $this->parseDate($extractedData['expiration_date'] ?? null),
                    'contract_summary' => $extractedData['summary'] ?? null,
                ]
            );

            Log::info('Contract processed', [
                'document_id' => $document->id,
                'contract_id' => $contract->id,
                'risk_score' => $extractedData['risk_score'] ?? null,
            ]);
        });
    }

    /**
     * Process a general document.
     *
     * @param Document $document
     * @param OllamaQwenClient $ollamaClient
     * @return void
     */
    protected function processGeneral(Document $document, OllamaQwenClient $ollamaClient): void
    {
        // For general documents, we just mark as extracted
        // You could optionally extract basic metadata here
        $document->status = DocumentStatus::EXTRACTED;
        $document->llm_json = [
            'category' => 'general',
            'processed_at' => now()->toIso8601String(),
        ];
        $document->save();

        Log::info('General document processed', [
            'document_id' => $document->id,
        ]);
    }

    /**
     * Validate invoice totals for consistency.
     *
     * @param array $data
     * @return array List of validation warnings
     */
    protected function validateInvoiceTotals(array $data): array
    {
        $warnings = [];
        $tolerance = 0.02; // Allow 2 cent rounding differences

        if (
            isset($data['subtotal']) && is_numeric($data['subtotal']) &&
            isset($data['tax']) && is_numeric($data['tax']) &&
            isset($data['total']) && is_numeric($data['total'])
        ) {
            $expectedTotal = (float) $data['subtotal'] + (float) $data['tax'];
            $actualTotal = (float) $data['total'];
            $difference = abs($expectedTotal - $actualTotal);

            if ($difference > $tolerance) {
                $warnings[] = sprintf(
                    'Total validation warning: subtotal (%.2f) + tax (%.2f) = %.2f, but total is %.2f (diff: %.2f)',
                    $data['subtotal'],
                    $data['tax'],
                    $expectedTotal,
                    $actualTotal,
                    $difference
                );
            }
        }

        // Validate line items sum to subtotal
        if (isset($data['lines']) && is_array($data['lines']) && isset($data['subtotal'])) {
            $linesTotal = 0;
            foreach ($data['lines'] as $line) {
                if (isset($line['line_total']) && is_numeric($line['line_total'])) {
                    $linesTotal += (float) $line['line_total'];
                }
            }

            $difference = abs($linesTotal - (float) $data['subtotal']);
            if ($difference > $tolerance) {
                $warnings[] = sprintf(
                    'Line items total (%.2f) does not match subtotal (%.2f) (diff: %.2f)',
                    $linesTotal,
                    $data['subtotal'],
                    $difference
                );
            }
        }

        return $warnings;
    }

    /**
     * Parse a date string, returning null if invalid.
     *
     * @param mixed $dateString
     * @return string|null
     */
    protected function parseDate(mixed $dateString): ?string
    {
        if (!$dateString || !is_string($dateString)) {
            return null;
        }

        try {
            $date = new \DateTime($dateString);
            return $date->format('Y-m-d');
        } catch (\Exception $e) {
            Log::warning('Failed to parse date', ['date' => $dateString]);
            return null;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('RunDocumentExtractionJob failed permanently', [
            'document_id' => $this->documentId,
            'error' => $exception->getMessage(),
        ]);

        Document::where('id', $this->documentId)->update([
            'status' => DocumentStatus::FAILED,
            'error_message' => 'Job failed after ' . $this->tries . ' attempts: ' . $exception->getMessage(),
        ]);
    }
}
