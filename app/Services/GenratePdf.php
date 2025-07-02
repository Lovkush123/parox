<?php
namespace App\Services;

use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;

class GenratePdf {
   public static function generateInvoice($booking)
{
    $directory = storage_path('app/invoices');

    // 1. Create the directory if it doesn't exist
    if (!file_exists($directory)) {
        mkdir($directory, 0755, true);
        \Log::info('Invoice directory created: ' . $directory);
    }

    $fileName = $booking->unique_order_id . '.pdf';
    $filePath = $directory . '/' . $fileName;

    try {
        // 2. Generate PDF
        $pdf = \PDF::loadView('pdf.invoice', ['booking' => $booking]);
        $pdf->save($filePath);

        \Log::info('Invoice PDF generated: ' . $filePath);

        // 3. Move PDF to public storage (optional, for URL access)
        $publicStoragePath = 'invoices/' . $fileName;
        \Storage::disk('public')->put($publicStoragePath, file_get_contents($filePath));

        // 4. Generate public invoice URL
        $invoiceUrl = asset('storage/' . $publicStoragePath);

        // 5. Save URL to invoice_link column
        $booking->invoice_link = $invoiceUrl;
        $booking->save();

        \Log::info('Invoice URL saved: ' . $invoiceUrl);

        return $filePath;

    } catch (\Exception $e) {
        \Log::error('Failed to generate invoice: ' . $e->getMessage());
        throw $e; // Let controller handle the failure
    }
}



}
