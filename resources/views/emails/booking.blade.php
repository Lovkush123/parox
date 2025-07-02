<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Booking Confirmation</title>
  <style>
    body {
      font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
      background-color: #f7f7f7;
      margin: 0;
      padding: 0;
    }
    .email-wrapper {
      max-width: 600px;
      margin: 40px auto;
      background: #ffffff;
      padding: 30px;
      border-radius: 10px;
      box-shadow: 0 0 10px rgba(0,0,0,0.05);
    }
    .header {
      text-align: center;
      padding-bottom: 20px;
    }
    .header .icon {
      font-size: 60px;
      color: #28a745;
    }
    .header h1 {
      margin: 10px 0 0;
      color: #2c3e50;
    }
    .content {
      font-size: 16px;
      color: #555555;
      line-height: 1.6;
    }
    .booking-details {
      margin: 20px 0;
      padding: 15px;
      background-color: #f2f2f2;
      border-radius: 8px;
    }
    .booking-details strong {
      color: #333333;
    }
    .footer {
      text-align: center;
      font-size: 13px;
      color: #999999;
      padding-top: 20px;
    }
    .btn {
      display: inline-block;
      margin-top: 20px;
      padding: 12px 25px;
      background-color: #007bff;
      color: #ffffff;
      text-decoration: none;
      border-radius: 5px;
      font-weight: bold;
    }
  </style>
</head>
<body>
  <div class="email-wrapper">
    <div class="header">
      <div class="icon">✅</div>
      <h1>Booking Confirmed!</h1>
    </div>
    <div class="content">
     <p>Hi <strong>{{ $booking->user->name ?? 'Customer' }}</strong>,</p>

<p>Thank you for your booking! We're excited to confirm your reservation. Below are your booking details:</p>

<div class="booking-details">
    <p><strong>Booking ID:</strong> #{{ $booking->unique_order_id }}</p>
    <p><strong>Date:</strong> {{ \Carbon\Carbon::parse($booking->created_at)->format('F j, Y') }}</p>
    <p><strong>Time:</strong> {{ \Carbon\Carbon::parse($booking->created_at)->format('g:i A') }}</p>
    <p><strong>Delivery Address:</strong>
        {{ $booking->address->address_one ?? '' }},
        {{ $booking->address->address_two ?? '' }},
        {{ $booking->address->city ?? '' }},
        {{ $booking->address->state ?? '' }} - {{ $booking->address->pincode ?? '' }}
    </p>
</div>

@if(!empty($booking->invoice_link))
    <p><strong>Download Invoice:</strong> <a href="{{ $booking->invoice_link }}" target="_blank">Click here</a></p>
@endif

      <p>If you have any questions or need to make changes to your booking, feel free to contact us.</p>

      <p>We look forward to seeing you!</p>
      <p>Best regards,<br><strong>Peraux Luxries</strong></p>
    </div>

    <div class="footer">
      © 2025 Peraux Luxries Private Limited. All rights reserved.<br>
    </div>
  </div>
</body>
</html>
