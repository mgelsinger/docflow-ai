<?php

namespace Database\Seeders;

use App\Enums\DocumentCategory;
use App\Enums\DocumentStatus;
use App\Models\Contract;
use App\Models\Document;
use App\Models\Invoice;
use App\Models\InvoiceLine;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create a test user
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        // Seed sample documents
        $this->seedGeneralDocument($user);
        $this->seedInvoiceDocument($user);
        $this->seedContractDocument($user);
    }

    /**
     * Seed a general document
     */
    private function seedGeneralDocument(User $user): void
    {
        Document::create([
            'user_id' => $user->id,
            'category' => DocumentCategory::GENERAL,
            'filename' => 'company_memo.pdf',
            'storage_path' => 'documents/2025/01/company_memo.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => 245678,
            'status' => DocumentStatus::EXTRACTED,
            'ocr_text' => 'COMPANY MEMORANDUM\n\nDate: January 15, 2025\nFrom: Management\nTo: All Staff\n\nSubject: Q1 2025 Updates\n\nPlease be advised of the following changes...',
            'llm_json' => [
                'document_type' => 'memorandum',
                'date' => '2025-01-15',
                'sender' => 'Management',
                'recipient' => 'All Staff',
                'subject' => 'Q1 2025 Updates',
            ],
        ]);
    }

    /**
     * Seed an invoice document with line items
     */
    private function seedInvoiceDocument(User $user): void
    {
        $document = Document::create([
            'user_id' => $user->id,
            'category' => DocumentCategory::INVOICE,
            'filename' => 'acme_invoice_2025_001.pdf',
            'storage_path' => 'documents/2025/01/acme_invoice_2025_001.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => 156432,
            'status' => DocumentStatus::EXTRACTED,
            'ocr_text' => 'INVOICE\n\nAcme Corporation\n123 Business St, Tech City, CA 94000\n\nInvoice #: INV-2025-001\nDate: January 10, 2025\nDue: February 10, 2025\n\nItems:\n1. Web Development Services - $2,500.00\n2. Cloud Hosting (3 months) - $450.00\n3. SSL Certificate - $50.00\n\nSubtotal: $3,000.00\nTax (8.5%): $255.00\nTotal: $3,255.00',
            'llm_json' => [
                'vendor' => 'Acme Corporation',
                'invoice_number' => 'INV-2025-001',
                'date' => '2025-01-10',
                'items' => [
                    ['description' => 'Web Development Services', 'amount' => 2500],
                    ['description' => 'Cloud Hosting (3 months)', 'amount' => 450],
                    ['description' => 'SSL Certificate', 'amount' => 50],
                ],
            ],
        ]);

        $invoice = Invoice::create([
            'document_id' => $document->id,
            'vendor_name' => 'Acme Corporation',
            'vendor_address' => '123 Business St, Tech City, CA 94000',
            'invoice_number' => 'INV-2025-001',
            'invoice_date' => '2025-01-10',
            'due_date' => '2025-02-10',
            'subtotal' => 3000.00,
            'tax' => 255.00,
            'total' => 3255.00,
            'currency' => 'USD',
        ]);

        InvoiceLine::create([
            'invoice_id' => $invoice->id,
            'description' => 'Web Development Services',
            'quantity' => 1,
            'unit_price' => 2500.00,
            'line_total' => 2500.00,
        ]);

        InvoiceLine::create([
            'invoice_id' => $invoice->id,
            'description' => 'Cloud Hosting (3 months)',
            'quantity' => 3,
            'unit_price' => 150.00,
            'line_total' => 450.00,
        ]);

        InvoiceLine::create([
            'invoice_id' => $invoice->id,
            'description' => 'SSL Certificate',
            'quantity' => 1,
            'unit_price' => 50.00,
            'line_total' => 50.00,
        ]);
    }

    /**
     * Seed a contract document
     */
    private function seedContractDocument(User $user): void
    {
        $document = Document::create([
            'user_id' => $user->id,
            'category' => DocumentCategory::CONTRACT,
            'filename' => 'software_license_agreement.pdf',
            'storage_path' => 'documents/2025/01/software_license_agreement.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => 387945,
            'status' => DocumentStatus::EXTRACTED,
            'ocr_text' => 'SOFTWARE LICENSE AGREEMENT\n\nThis agreement is made between:\n\nParty A: TechStart Inc.\nParty B: Enterprise Solutions LLC\n\nEffective Date: January 1, 2025\nExpiration Date: December 31, 2026\n\nThis agreement grants Party B the right to use the software product...',
            'llm_json' => [
                'contract_type' => 'Software License Agreement',
                'party_a' => 'TechStart Inc.',
                'party_b' => 'Enterprise Solutions LLC',
                'effective_date' => '2025-01-01',
                'expiration_date' => '2026-12-31',
            ],
        ]);

        Contract::create([
            'document_id' => $document->id,
            'party_a' => 'TechStart Inc.',
            'party_b' => 'Enterprise Solutions LLC',
            'effective_date' => '2025-01-01',
            'expiration_date' => '2026-12-31',
            'contract_summary' => 'This is a two-year software license agreement between TechStart Inc. (licensor) and Enterprise Solutions LLC (licensee). The agreement grants the licensee the right to use the software product for internal business purposes. Key terms include annual licensing fees, support and maintenance provisions, data security requirements, and termination clauses. The licensee is restricted from sublicensing or distributing the software to third parties.',
        ]);
    }
}
