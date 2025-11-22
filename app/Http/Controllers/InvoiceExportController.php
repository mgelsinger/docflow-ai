<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InvoiceExportController extends Controller
{
    /**
     * Export all invoices as CSV.
     *
     * @param Request $request
     * @return StreamedResponse
     */
    public function exportCsv(Request $request): StreamedResponse
    {
        $filename = 'invoices-' . now()->format('Y-m-d') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ];

        return response()->stream(function () {
            $handle = fopen('php://output', 'w');

            // Write CSV headers
            fputcsv($handle, [
                'document_id',
                'filename',
                'vendor_name',
                'vendor_address',
                'invoice_number',
                'invoice_date',
                'due_date',
                'currency',
                'subtotal',
                'tax',
                'total',
                'created_at',
            ]);

            // Query invoices with document relationship
            Invoice::with('document')
                ->chunk(100, function ($invoices) use ($handle) {
                    foreach ($invoices as $invoice) {
                        fputcsv($handle, [
                            $invoice->document_id,
                            $invoice->document?->filename ?? '',
                            $invoice->vendor_name ?? '',
                            $invoice->vendor_address ?? '',
                            $invoice->invoice_number ?? '',
                            $invoice->invoice_date?->format('Y-m-d') ?? '',
                            $invoice->due_date?->format('Y-m-d') ?? '',
                            $invoice->currency ?? 'USD',
                            $invoice->subtotal ?? 0,
                            $invoice->tax ?? 0,
                            $invoice->total ?? 0,
                            $invoice->created_at->format('Y-m-d H:i:s'),
                        ]);
                    }
                });

            fclose($handle);
        }, 200, $headers);
    }
}
