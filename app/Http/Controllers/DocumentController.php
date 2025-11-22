<?php

namespace App\Http\Controllers;

use App\Enums\DocumentCategory;
use App\Enums\DocumentStatus;
use App\Http\Controllers\Controller;
use App\Jobs\RunDocumentExtractionJob;
use App\Models\Document;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\File;
use Inertia\Inertia;
use Inertia\Response;

class DocumentController extends Controller
{
    /**
     * Show the document upload form.
     *
     * @return Response
     */
    public function create(): Response
    {
        return Inertia::render('Documents/Upload');
    }

    /**
     * Store a newly uploaded document and queue extraction.
     *
     * @param Request $request
     * @return RedirectResponse|\Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'file' => [
                'required',
                'file',
                'mimes:pdf,png,jpg,jpeg',
            ],
            'category' => ['nullable', 'string', 'in:general,invoice,contract'],
        ]);

        $file = $request->file('file');

        // Generate a unique filename
        $filename = $file->getClientOriginalName();
        $extension = $file->getClientOriginalExtension();
        $timestamp = now()->format('Y/m');
        $uniqueName = uniqid() . '_' . time() . '.' . $extension;

        // Store the file
        $storagePath = "documents/{$timestamp}/{$uniqueName}";
        Storage::put($storagePath, $file->get());

        // Determine initial category
        $category = isset($validated['category'])
            ? DocumentCategory::from($validated['category'])
            : DocumentCategory::GENERAL;

        // Create document record
        $document = Document::create([
            'user_id' => auth()->id(), // null if not authenticated
            'category' => $category,
            'filename' => $filename,
            'storage_path' => $storagePath,
            'mime_type' => $file->getMimeType(),
            'size_bytes' => $file->getSize(),
            'status' => DocumentStatus::PENDING,
        ]);

        // Dispatch extraction job
        RunDocumentExtractionJob::dispatch($document);

        // Return appropriate response
        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Document uploaded successfully and queued for processing',
                'document' => [
                    'id' => $document->id,
                    'filename' => $document->filename,
                    'status' => $document->status->value,
                    'category' => $document->category->value,
                ],
            ], 201);
        }

        return redirect()->route('documents.create')->with('success', 'Document uploaded successfully and queued for processing');
    }

    /**
     * Display the list of documents with filters.
     *
     * @param Request $request
     * @return Response
     */
    public function index(Request $request): Response
    {
        $query = Document::query();

        // Filter by authenticated user if logged in
        if (auth()->check()) {
            $query->where('user_id', auth()->id());
        }

        // Search by filename
        if ($request->filled('search')) {
            $query->where('filename', 'like', '%' . $request->input('search') . '%');
        }

        // Filter by category if provided
        if ($request->filled('category') && $request->input('category') !== 'all') {
            $query->where('category', $request->input('category'));
        }

        // Filter by status if provided
        if ($request->filled('status') && $request->input('status') !== 'all') {
            $query->where('status', $request->input('status'));
        }

        // Order by most recent first and paginate
        $documents = $query->with(['invoice', 'contract'])
            ->latest()
            ->paginate(15)
            ->withQueryString()
            ->through(fn ($document) => [
                'id' => $document->id,
                'filename' => $document->filename,
                'category' => $document->category->value,
                'status' => $document->status->value,
                'created_at' => $document->created_at->format('Y-m-d H:i:s'),
                'updated_at' => $document->updated_at->format('Y-m-d H:i:s'),
                'has_invoice' => $document->invoice !== null,
                'has_contract' => $document->contract !== null,
            ]);

        return Inertia::render('Documents/Index', [
            'documents' => $documents,
            'filters' => [
                'search' => $request->input('search', ''),
                'category' => $request->input('category', 'all'),
                'status' => $request->input('status', 'all'),
            ],
        ]);
    }

    /**
     * Display the specified document with extracted data.
     *
     * @param Document $document
     * @return Response
     */
    public function show(Document $document): Response
    {
        // Load relationships
        $document->load(['invoice.lines', 'contract', 'user']);

        return Inertia::render('Documents/Show', [
            'document' => [
                'id' => $document->id,
                'filename' => $document->filename,
                'category' => $document->category->value,
                'status' => $document->status->value,
                'size_bytes' => $document->size_bytes,
                'mime_type' => $document->mime_type,
                'created_at' => $document->created_at->format('Y-m-d H:i:s'),
                'updated_at' => $document->updated_at->format('Y-m-d H:i:s'),
                'error_message' => $document->error_message,
            ],
            'invoice' => $document->invoice ? [
                'id' => $document->invoice->id,
                'vendor_name' => $document->invoice->vendor_name,
                'vendor_address' => $document->invoice->vendor_address,
                'invoice_number' => $document->invoice->invoice_number,
                'invoice_date' => $document->invoice->invoice_date?->format('Y-m-d'),
                'due_date' => $document->invoice->due_date?->format('Y-m-d'),
                'subtotal' => $document->invoice->subtotal,
                'tax' => $document->invoice->tax,
                'total' => $document->invoice->total,
                'currency' => $document->invoice->currency,
                'lines' => $document->invoice->lines->map(fn ($line) => [
                    'id' => $line->id,
                    'description' => $line->description,
                    'quantity' => $line->quantity,
                    'unit_price' => $line->unit_price,
                    'line_total' => $line->line_total,
                ])->toArray(),
            ] : null,
            'contract' => $document->contract ? [
                'id' => $document->contract->id,
                'party_a' => $document->contract->party_a,
                'party_b' => $document->contract->party_b,
                'effective_date' => $document->contract->effective_date?->format('Y-m-d'),
                'expiration_date' => $document->contract->expiration_date?->format('Y-m-d'),
                'contract_summary' => $document->contract->contract_summary,
            ] : null,
            'llm_json' => $document->llm_json,
        ]);
    }

    /**
     * Delete the specified document.
     *
     * @param Document $document
     * @return RedirectResponse|\Illuminate\Http\JsonResponse
     */
    public function destroy(Document $document)
    {
        // Delete the file from storage
        if (Storage::exists($document->storage_path)) {
            Storage::delete($document->storage_path);
        }

        // Delete the document record (cascade will handle related records)
        $document->delete();

        if (request()->expectsJson()) {
            return response()->json([
                'message' => 'Document deleted successfully',
            ]);
        }

        return redirect()->back()->with('success', 'Document deleted successfully');
    }

    /**
     * Download the original document file.
     *
     * @param Document $document
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function download(Document $document)
    {
        if (!Storage::exists($document->storage_path)) {
            abort(404, 'Document file not found');
        }

        return Storage::download($document->storage_path, $document->filename);
    }

    /**
     * Retry extraction for a failed document.
     *
     * @param Document $document
     * @return RedirectResponse|\Illuminate\Http\JsonResponse
     */
    public function retry(Document $document)
    {
        // Reset status and error message
        $document->update([
            'status' => DocumentStatus::PENDING,
            'error_message' => null,
        ]);

        // Dispatch extraction job again
        RunDocumentExtractionJob::dispatch($document);

        if (request()->expectsJson()) {
            return response()->json([
                'message' => 'Document extraction requeued',
                'document' => [
                    'id' => $document->id,
                    'status' => $document->status->value,
                ],
            ]);
        }

        return redirect()->back()->with('success', 'Document extraction requeued');
    }

    /**
     * Export a single document as JSON.
     *
     * @param Document $document
     * @return \Illuminate\Http\JsonResponse
     */
    public function exportJson(Document $document)
    {
        // Load relationships
        $document->load(['invoice.lines', 'contract']);

        // Build structured export data
        $exportData = [
            'document' => [
                'id' => $document->id,
                'filename' => $document->filename,
                'category' => $document->category->value,
                'status' => $document->status->value,
                'mime_type' => $document->mime_type,
                'size_bytes' => $document->size_bytes,
                'created_at' => $document->created_at->toIso8601String(),
                'updated_at' => $document->updated_at->toIso8601String(),
                'error_message' => $document->error_message,
            ],
            'invoice' => $document->invoice ? [
                'vendor_name' => $document->invoice->vendor_name,
                'vendor_address' => $document->invoice->vendor_address,
                'invoice_number' => $document->invoice->invoice_number,
                'invoice_date' => $document->invoice->invoice_date?->format('Y-m-d'),
                'due_date' => $document->invoice->due_date?->format('Y-m-d'),
                'subtotal' => (float) $document->invoice->subtotal,
                'tax' => (float) $document->invoice->tax,
                'total' => (float) $document->invoice->total,
                'currency' => $document->invoice->currency,
                'lines' => $document->invoice->lines->map(fn ($line) => [
                    'description' => $line->description,
                    'quantity' => (float) $line->quantity,
                    'unit_price' => (float) $line->unit_price,
                    'line_total' => (float) $line->line_total,
                ])->toArray(),
            ] : null,
            'contract' => $document->contract ? [
                'party_a' => $document->contract->party_a,
                'party_b' => $document->contract->party_b,
                'effective_date' => $document->contract->effective_date?->format('Y-m-d'),
                'expiration_date' => $document->contract->expiration_date?->format('Y-m-d'),
                'contract_summary' => $document->contract->contract_summary,
            ] : null,
            'llm_json' => $document->llm_json,
        ];

        $filename = "document-{$document->id}.json";

        return response()->json($exportData, 200, [
            'Content-Type' => 'application/json',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ], JSON_PRETTY_PRINT);
    }
}
