<?php

namespace App\Services;

use App\Models\Order;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class InvoiceService
{
    /**
     * Determine if AWS S3 disk is properly configured.
     */
    public function isS3Configured(): bool
    {
        $bucket = config('filesystems.disks.s3.bucket');
        $key = config('filesystems.disks.s3.key');
        $secret = config('filesystems.disks.s3.secret');

        return ! empty($bucket) && ! empty($key) && ! empty($secret);
    }

    /**
     * Generate standard A4 PDF invoice for an order and store it on S3 (or public disk fallback).
     * Saves invoice details (invoice_number, invoice_url, invoice_pdf_path, invoice_generated_at) in database.
     *
     * @return string The public URL of the stored PDF invoice
     */
    public function generateAndStorePdf(Order $order, bool $force = false): string
    {
        $order->loadMissing(['items.product', 'items.variant', 'user']);

        $path = $order->invoice_pdf_path ?: "invoices/INV-{$order->order_number}.pdf";

        // If already generated and valid, return existing URL unless forced
        if (! $force && ! empty($order->invoice_url)) {
            $exists = false;
            try {
                if ($this->isS3Configured() && Storage::disk('s3')->exists($path)) {
                    $exists = true;
                } elseif (Storage::disk('public')->exists($path)) {
                    $exists = true;
                }
            } catch (\Throwable $e) {
                // Ignore storage probe error
            }

            if ($exists) {
                return $order->invoice_url;
            }
        }

        // Render invoice blade template in PDF mode
        $html = view('invoices.gst-tax-invoice', [
            'order' => $order,
            'isPdf' => true,
        ])->render();

        // Configure Dompdf for standard A4 portrait
        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $options->set('isHtml5ParserEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('dpi', 96);

        $dompdf = new Dompdf($options);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->loadHtml($html);
        $dompdf->render();
        $pdfOutput = $dompdf->output();

        $invoiceUrl = null;
        $storedDisk = 'public';

        // Attempt S3 storage if configured
        if ($this->isS3Configured()) {
            try {
                Storage::disk('s3')->put($path, $pdfOutput, [
                    'visibility' => 'public',
                    'CacheControl' => 'no-cache, no-store, must-revalidate, max-age=0',
                ]);
                $storedDisk = 's3';
                $invoiceUrl = Storage::disk('s3')->url($path);
            } catch (\Throwable $e) {
                Log::warning('InvoiceService: S3 upload failed, using local public disk fallback.', [
                    'order' => $order->order_number,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Local public storage fallback
        if ($storedDisk !== 's3' || empty($invoiceUrl)) {
            Storage::disk('public')->put($path, $pdfOutput);
            $invoiceUrl = Storage::disk('public')->url($path);

            if (! str_starts_with($invoiceUrl, 'http://') && ! str_starts_with($invoiceUrl, 'https://')) {
                $invoiceUrl = url($invoiceUrl);
            }
        }

        $invoiceNumber = $order->invoice_number ?: 'INV-' . $order->order_number;

        // Save invoice details into database
        $order->update([
            'invoice_number' => $invoiceNumber,
            'invoice_url' => $invoiceUrl,
            'invoice_pdf_path' => $path,
            'invoice_generated_at' => now(),
        ]);

        Log::info('InvoiceService: Order invoice generated and stored successfully.', [
            'order' => $order->order_number,
            'disk' => $storedDisk,
            'url' => $invoiceUrl,
        ]);

        return $invoiceUrl;
    }

    /**
     * Return a binary PDF response for inline viewing (in-browser PDF reader) or file download.
     */
    public function getPdfResponse(Order $order, bool $download = false, bool $force = false): Response
    {
        $path = $order->invoice_pdf_path ?: "invoices/INV-{$order->order_number}.pdf";
        $pdfContent = null;

        if ($force) {
            $this->generateAndStorePdf($order, true);
        }

        try {
            if ($this->isS3Configured() && Storage::disk('s3')->exists($path)) {
                $pdfContent = Storage::disk('s3')->get($path);
            } elseif (Storage::disk('public')->exists($path)) {
                $pdfContent = Storage::disk('public')->get($path);
            }
        } catch (\Throwable $e) {
            Log::warning('InvoiceService: Failed reading stored PDF, regenerating.', [
                'order' => $order->order_number,
                'error' => $e->getMessage(),
            ]);
        }

        if (empty($pdfContent)) {
            $this->generateAndStorePdf($order, true);
            $path = $order->invoice_pdf_path ?: "invoices/INV-{$order->order_number}.pdf";

            if ($this->isS3Configured() && Storage::disk('s3')->exists($path)) {
                $pdfContent = Storage::disk('s3')->get($path);
            } else {
                $pdfContent = Storage::disk('public')->get($path);
            }
        }

        $filename = ($order->invoice_number ?: 'INV-' . $order->order_number) . '.pdf';
        $disposition = $download ? 'attachment' : 'inline';

        return response($pdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "{$disposition}; filename=\"{$filename}\"",
            'Content-Length' => strlen($pdfContent),
            'Cache-Control' => 'no-cache, no-store, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }
}
