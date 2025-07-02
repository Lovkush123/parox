<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OtpService
{
  public static function sendOTPPhone($otp, $number, $template_name)
{
    try {
        $key = env("WHATS_APP_API_KEY");
        $url = "http://wa.iconicsolution.co.in/wapp/api/send/otptemplate";

        // 🔍 Log the request details
        Log::debug('Sending OTP via WhatsApp API', [
            'url'          => $url,
            'apikey'       => $key,
            'templatename' => $template_name,
            'mobile'       => '+91' . $number,
            'otp'          => $otp,
        ]);

        $response = Http::get($url, [
            'apikey'      => $key,
            'templatename'=> $template_name,
            'mobile'      => '+91' . $number,
            'otp'         => $otp,
        ]);

        // 📦 Log the raw response
        Log::debug('WhatsApp API Response', [
            'status' => $response->status(),
            'body'   => $response->body(),
        ]);

        return $response->json();
    } catch (\Exception $e) {
        // ❗ Log the exception
        Log::error('WhatsApp OTP send failed', [
            'error' => $e->getMessage(),
        ]);

        return response()->json([
            'message' => 'Failed to send OTP',
            'error' => $e->getMessage(),
        ], 500);
    }
}

public static function sendWhatsAppBookingConfirmation($order, $invoicePath)
{
    try {
        $token = env('WHATSAPP_API_KEY');
        $url = 'http://wa.iconicsolution.co.in/wapp/api/v2/send/bytemplate';

        $invoiceUrl = asset('storage/invoices/' . basename($invoicePath));

        $response = Http::get($url, [
            'apikey'     => $token,
            'templatename'=> "booking_confirm",
            'mobile'     => '+91' . $order->user->number,
            'dvariables' => ["1"=>$order->user->name, "2"=>$order->unique_order_id, "3"=>$order->created_at->format('d M Y'), "4"=>$order->total], 
            // 'message'    => "Hello {$order->user->name},\n\nYour booking has been confirmed ✅\n\n🧾 Order ID: {$order->unique_order_id}\n📅 Date: {$order->created_at->format('d M Y')}\n💰 Total: ₹{$order->total}\n\nYour invoice is attached.\n\nThank you for choosing Peraux Luxuries 💎",
            'media'  => $invoiceUrl,
            'file_name'  => 'invoice_' . $order->unique_order_id . '.pdf',
        ]);

        \Log::info('WhatsApp invoice sent', ['response' => $response->body()]);
        return true;
    } catch (\Exception $e) {
        \Log::error('Failed to send WhatsApp invoice', ['error' => $e->getMessage()]);
        return false;
    }
}

}
