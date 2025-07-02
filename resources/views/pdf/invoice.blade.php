<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Booking Invoice</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 12px;
            color: #333;
        }
        .container {
            width: 700px;
            margin: 0 auto;
        }
        .header {
            text-align: center;
        }
        .header img {
            height: 60px;
            margin-bottom: 10px;
        }
        .invoice-title {
            font-size: 24px;
            margin-bottom: 10px;
        }
        .section {
            margin-top: 20px;
        }
        .section-title {
            font-weight: bold;
            margin-bottom: 5px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        table th, table td {
            border: 1px solid #ccc;
            padding: 8px;
        }
        .totals td {
            font-weight: bold;
        }
        .text-right {
            text-align: right;
        }
        .small {
            font-size: 10px;
        }
    </style>
</head>
<body>
    <div class="container">

        <div class="header">
            <img src="{{ public_path('logo.png') }}" alt="Company Logo">
            <div class="invoice-title">Invoice</div>
        </div>

        <div class="section">
            <div class="section-title">Order Info</div>
            <table>
                <tr>
                    <td><strong>Invoice #:</strong> {{ $booking->unique_order_id }}</td>
                    <td><strong>Date:</strong> {{ $booking->created_at->format('d M Y') }}</td>
                    <td><strong>Payment:</strong> {{ ucfirst($booking->payment_type) }}</td>
                </tr>
            </table>
        </div>

        <div class="section">
            <div class="section-title">Customer Info</div>
            <table>
                <tr>
                    <td>
                        <strong>Name:</strong> {{ $booking->user->name }}<br>
                        <strong>Email:</strong> {{ $booking->user->email }}<br>
                        <strong>Mobile:</strong> {{ $booking->user->number }}
                    </td>
                    <td>
                        <strong>Shipping Address:</strong><br>
                        {{ $booking->address->address_one }}<br>
                        {{ $booking->address->address_two }}<br>
                        {{ $booking->address->city }}, {{ $booking->address->state }} - {{ $booking->address->pincode }}
                    </td>
                </tr>
            </table>
        </div>

        <div class="section">
            <div class="section-title">Order Items</div>
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Product</th>
                        <th>Size</th>
                        <th>Qty</th>
                        <th>Price</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($booking->products as $index => $item)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>{{ $item->product->name ?? 'N/A' }}</td>
                            <td>{{ $item->size->size ?? '-' }}</td>
                            <td>{{ $item->quantity }}</td>
                            <td>₹{{ number_format($item->price, 2) }}</td>
                            <td>₹{{ number_format($item->quantity * $item->price, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="section">
            <div class="section-title">Summary</div>
            <table class="totals">
                <tr>
                    <td colspan="5" class="text-right">Subtotal</td>
                    <td>₹{{ number_format($booking->subtotal, 2) }}</td>
                </tr>
                <tr>
                    <td colspan="5" class="text-right">Tax</td>
                    <td>₹{{ number_format($booking->tax, 2) }}</td>
                </tr>
                <tr>
                    <td colspan="5" class="text-right">Total</td>
                    <td>₹{{ number_format($booking->total, 2) }}</td>
                </tr>
            </table>
        </div>

        <div class="section small">
            <p>Thank you for shopping with us! This is a system-generated invoice.</p>
        </div>

    </div>
</body>
</html>
