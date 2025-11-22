# DocFlow AI - Document Extraction Pipeline Setup

This guide covers the setup and testing of the Ollama-powered document extraction pipeline.

## What Was Implemented

### Core Services

1. **DocumentImageService** ([app/Services/Documents/DocumentImageService.php](app/Services/Documents/DocumentImageService.php))
   - Converts PDF first page to PNG using Imagick
   - Handles image files (PNG, JPG, JPEG)
   - Resizes images to max 1600px width
   - Provides base64 encoding for Ollama

2. **OllamaQwenClient** ([app/Services/Ollama/OllamaQwenClient.php](app/Services/Ollama/OllamaQwenClient.php))
   - Connects to local Ollama instance
   - Three main methods:
     - `classify()` - Determines document category (invoice/contract/general)
     - `extractInvoice()` - Extracts structured invoice data
     - `summarizeContract()` - Extracts contract metadata and summary
   - Robust JSON parsing with error handling

3. **RunDocumentExtractionJob** ([app/Jobs/RunDocumentExtractionJob.php](app/Jobs/RunDocumentExtractionJob.php))
   - Queued job for async processing
   - Automatic classification if category is unknown
   - Creates/updates Invoice and InvoiceLine records
   - Creates/updates Contract records
   - Validates invoice totals
   - Comprehensive error handling and logging

4. **DocumentController** ([app/Http/Controllers/DocumentController.php](app/Http/Controllers/DocumentController.php))
   - RESTful endpoints for document management
   - File upload with validation
   - Download, retry, and deletion capabilities

### Configuration

**File:** [config/ollama.php](config/ollama.php)

Configuration options:
- `base_url` - Ollama server URL
- `model` - Vision model name
- `timeout` - Request timeout in seconds
- `max_image_width` - Maximum image width for processing

## Environment Configuration

Add these variables to your `.env` file:

```env
# Ollama Configuration
OLLAMA_BASE_URL=http://127.0.0.1:11434
OLLAMA_MODEL=qwen3-vl:8b
OLLAMA_TIMEOUT=120
OLLAMA_MAX_IMAGE_WIDTH=1600

# Queue Configuration (if not already set)
QUEUE_CONNECTION=database
```

## Prerequisites

### 1. Install Imagick PHP Extension

**Windows:**
- Download the appropriate DLL from [PECL](https://pecl.php.net/package/imagick)
- Place it in your PHP extensions directory
- Add `extension=imagick` to `php.ini`
- Restart your web server

**Linux/Mac:**
```bash
# Ubuntu/Debian
sudo apt-get install php-imagick

# Mac
brew install imagemagick
pecl install imagick
```

Verify installation:
```bash
php -m | grep imagick
```

### 2. Install and Configure Ollama

**Download Ollama:**
- Visit [https://ollama.com](https://ollama.com)
- Download and install for your platform

**Pull the qwen3-vl model:**
```bash
ollama pull qwen3-vl:8b
```

**Verify Ollama is running:**
```bash
# Should return a list of models
curl http://127.0.0.1:11434/api/tags
```

### 3. Configure Queue Worker

For database queue driver, ensure the jobs table exists:

```bash
php artisan queue:table
php artisan migrate
```

## Testing the Pipeline

### Step 1: Run the Queue Worker

Open a terminal and start the queue worker:

```bash
php artisan queue:work
```

Keep this terminal open. You should see output like:
```
INFO  Processing jobs from the [default] queue.
```

### Step 2: Start the Development Server

Open another terminal:

```bash
php artisan serve
```

The application will be available at [http://127.0.0.1:8000](http://127.0.0.1:8000)

### Step 3: Upload a Test Document

#### Option A: Using cURL (API)

```bash
# Upload an invoice PDF
curl -X POST http://127.0.0.1:8000/documents \
  -H "Accept: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -F "document=@/path/to/invoice.pdf" \
  -F "category=invoice"

# Upload without specifying category (will auto-classify)
curl -X POST http://127.0.0.1:8000/documents \
  -H "Accept: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -F "document=@/path/to/document.pdf"
```

#### Option B: Using Artisan Tinker

```bash
php artisan tinker
```

Then in the tinker shell:

```php
use App\Models\Document;
use App\Enums\DocumentCategory;
use App\Enums\DocumentStatus;
use App\Jobs\RunDocumentExtractionJob;

// Create a test document entry
$document = Document::create([
    'user_id' => 1,
    'category' => DocumentCategory::INVOICE,
    'filename' => 'test_invoice.pdf',
    'storage_path' => 'path/to/your/uploaded/file.pdf',
    'mime_type' => 'application/pdf',
    'size_bytes' => 123456,
    'status' => DocumentStatus::PENDING,
]);

// Dispatch the extraction job
RunDocumentExtractionJob::dispatch($document);
```

### Step 4: Monitor the Queue Worker

Watch the queue worker terminal. You should see:

```
[INFO] Processing: App\Jobs\RunDocumentExtractionJob
[INFO] Processed:  App\Jobs\RunDocumentExtractionJob
```

### Step 5: Check the Results

#### Using Tinker:

```bash
php artisan tinker
```

```php
use App\Models\Document;

// Get the document
$doc = Document::with(['invoice.lines', 'contract'])->find(1);

// Check status
echo $doc->status->value; // Should be 'extracted'

// View extracted data
print_r($doc->llm_json);

// If it's an invoice
if ($doc->invoice) {
    echo "Vendor: " . $doc->invoice->vendor_name . "\n";
    echo "Total: " . $doc->invoice->total . "\n";
    echo "Line items: " . $doc->invoice->lines->count() . "\n";
}

// If it's a contract
if ($doc->contract) {
    echo "Party A: " . $doc->contract->party_a . "\n";
    echo "Party B: " . $doc->contract->party_b . "\n";
    echo "Summary: " . $doc->contract->contract_summary . "\n";
}
```

#### Using Database:

```bash
php artisan db
```

```sql
-- Check document status
SELECT id, filename, category, status FROM documents;

-- Check extracted invoices
SELECT d.filename, i.vendor_name, i.invoice_number, i.total
FROM documents d
JOIN invoices i ON d.id = i.document_id;

-- Check invoice line items
SELECT d.filename, il.description, il.quantity, il.unit_price, il.line_total
FROM documents d
JOIN invoices i ON d.id = i.document_id
JOIN invoice_lines il ON i.id = il.invoice_id;

-- Check contracts
SELECT d.filename, c.party_a, c.party_b, c.effective_date, c.expiration_date
FROM documents d
JOIN contracts c ON d.id = c.document_id;
```

## API Endpoints

All endpoints require authentication.

### Upload Document
```
POST /documents
Content-Type: multipart/form-data

Fields:
- document: File (required, pdf|png|jpg|jpeg, max 10MB)
- category: String (optional, invoice|contract|general)
```

### List Documents
```
GET /documents?category=invoice&status=extracted
```

### Get Document Details
```
GET /documents/{id}
```

### Download Original File
```
GET /documents/{id}/download
```

### Retry Failed Extraction
```
POST /documents/{id}/retry
```

### Delete Document
```
DELETE /documents/{id}
```

## Troubleshooting

### Document Status is "failed"

Check the error message:
```php
$doc = Document::find(1);
echo $doc->error_message;
```

Common issues:
- Ollama not running
- Wrong model name
- File not found in storage
- Imagick not installed

### Queue Jobs Not Processing

1. Ensure queue worker is running:
   ```bash
   php artisan queue:work
   ```

2. Check failed jobs:
   ```bash
   php artisan queue:failed
   ```

3. Retry failed jobs:
   ```bash
   php artisan queue:retry all
   ```

### Ollama Connection Issues

Test Ollama connection:
```php
use App\Services\Ollama\OllamaQwenClient;
use App\Services\Documents\DocumentImageService;

$client = new OllamaQwenClient(new DocumentImageService());

// Test connection
var_dump($client->testConnection()); // Should return true

// List available models
print_r($client->getAvailableModels());
```

### Imagick Not Found

Verify Imagick is installed:
```bash
php -i | grep imagick
```

If not found, install it (see Prerequisites section).

### Large Files Timing Out

Increase timeout in [config/ollama.php](config/ollama.php):
```php
'timeout' => env('OLLAMA_TIMEOUT', 300), // 5 minutes
```

## Logging

All extraction activities are logged. Check logs:

```bash
tail -f storage/logs/laravel.log
```

Key log messages:
- `Starting document extraction`
- `Document classified`
- `Invoice processed`
- `Contract processed`
- `Document extraction failed`

## Next Steps

1. **Frontend Integration**: Build Vue components for document upload and viewing
2. **Webhooks**: Add real-time status updates via websockets
3. **Batch Processing**: Process multiple documents at once
4. **OCR Enhancement**: Save OCR text for searchability
5. **Validation Rules**: Add custom validation for extracted data
6. **Export Features**: Export invoices/contracts to CSV/Excel
7. **Advanced Analytics**: Dashboard with extraction statistics

## Sample Test Documents

For testing, use:
- **Invoices**: Any PDF invoice from vendors
- **Contracts**: Service agreements, NDAs, license agreements
- **Images**: Photos of receipts, scanned documents

The system will automatically classify and extract relevant data based on the document type.
