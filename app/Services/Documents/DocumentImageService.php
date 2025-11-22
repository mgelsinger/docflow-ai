<?php

namespace App\Services\Documents;

use App\Models\Document;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class DocumentImageService
{
    /**
     * Path to Ghostscript on this Windows machine.
     */
    private const GHOSTSCRIPT_PATH = 'C:\\Program Files\\gs\\gs10.06.0\\bin\\gswin64c.exe';

    /**
     * Convert the given Document to a PNG binary (first page for PDFs).
     */
    public function toPngBinary(Document $document): string
    {
        $mime        = $document->mime_type;
        $storagePath = $document->storage_path;
        $absolute    = Storage::disk('local')->path($storagePath);

        if (! file_exists($absolute)) {
            throw new RuntimeException("File not found at path: {$absolute}");
        }

        // Case 1: PDF – render first page via Ghostscript
        if ($mime === 'application/pdf') {
            return $this->renderPdfFirstPageToPng($absolute);
        }

        // Case 2: existing images – just return the bytes
        if (str_starts_with($mime, 'image/')) {
            $bytes = Storage::disk('local')->get($storagePath);

            if ($bytes === '' || $bytes === false) {
                throw new RuntimeException("Failed to read image bytes from storage for {$storagePath}");
            }

            return $bytes;
        }

        throw new RuntimeException("Unsupported MIME type for image conversion: {$mime}");
    }

    /**
     * Convert Document to base64-encoded PNG (no data URI prefix).
     */
    public function toBase64Image(Document $document): string
    {
        $binary = $this->toPngBinary($document);

        return base64_encode($binary);
    }

    /**
     * Use Ghostscript directly to render the first page of a PDF to PNG.
     *
     * This does NOT depend on PATH; it uses the full gswin64c.exe path above.
     */
    private function renderPdfFirstPageToPng(string $pdfPath): string
    {
        $gs = self::GHOSTSCRIPT_PATH;

        if (! file_exists($gs)) {
            throw new RuntimeException("Ghostscript binary not found at: {$gs}");
        }

        $tmpBase = tempnam(sys_get_temp_dir(), 'docflow_pdf_');
        if ($tmpBase === false) {
            throw new RuntimeException('Failed to create temporary filename for PDF rendering.');
        }

        // Ghostscript will append page numbers; we only care about the first PNG it creates
        $outputPattern = $tmpBase . '-%d.png';

        // Build a Windows-friendly command with explicit quotes
        $cmd = '"' . $gs . '" '
            . '-dSAFER -dBATCH -dNOPAUSE '
            . '-sDEVICE=pngalpha -r150 '
            . '-sOutputFile="' . $outputPattern . '" '
            . '"' . $pdfPath . '"';

        $descriptors = [
            1 => ['pipe', 'w'], // stdout
            2 => ['pipe', 'w'], // stderr
        ];

        $process = proc_open($cmd, $descriptors, $pipes);

        if (! is_resource($process)) {
            @unlink($tmpBase);
            throw new RuntimeException('Failed to start Ghostscript process.');
        }

        $stdout = stream_get_contents($pipes[1]) ?: '';
        $stderr = stream_get_contents($pipes[2]) ?: '';

        foreach ($pipes as $pipe) {
            fclose($pipe);
        }

        $exitCode = proc_close($process);

        // Look for ANY PNGs matching our temp base (Ghostscript usually creates ...-1.png)
        $pattern = $tmpBase . '-*.png';
        $files   = glob($pattern) ?: [];

        // Some configurations might output without the "-n" suffix
        if (empty($files)) {
            $plain = $tmpBase . '.png';
            if (file_exists($plain)) {
                $files = [$plain];
            }
        }

        // If we have no output file, treat as failure (regardless of exit code)
        if (empty($files)) {
            @unlink($tmpBase);
            throw new RuntimeException(
                "Ghostscript produced no PNG output. Exit code {$exitCode}. STDERR: {$stderr}"
            );
        }

        // Use the first PNG as "page 1"
        $firstPage = $files[0];

        $png = file_get_contents($firstPage);

        // Cleanup temporary files
        @unlink($tmpBase);
        foreach ($files as $file) {
            @unlink($file);
        }

        if ($png === false || $png === '') {
            throw new RuntimeException('Failed to read PNG output from Ghostscript.');
        }

        return $png;
    }
}
