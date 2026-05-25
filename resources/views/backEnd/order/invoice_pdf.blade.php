<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $order->invoice_id }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #222; }
        .header { margin-bottom: 14px; }
        .title { font-size: 22px; font-weight: 700; margin: 0 0 4px 0; }
        .muted { color: #666; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ddd; padding: 7px; text-align: left; }
        th { background: #f5f5f5; }
        .text-right { text-align: right; }
        .summary { width: 45%; margin-left: auto; margin-top: 16px; }
    </style>
</head>
<body>
    <div class="header">
        <p class="title">Invoice</p>
        <p><strong>Invoice ID:</strong> {{ $order->invoice_id }}</p>
        <p><strong>Date:</strong> {{ optional($order->created_at)->format('Y-m-d H:i') }}</p>
        <p><strong>From:</strong> {{ $generalsetting->name ?? config('app.name') }}</p>
        <p><strong>To:</strong> {{ $order->shipping->name ?? '' }} ({{ $order->shipping->phone ?? '' }})</p>
        <p class="muted">{{ $order->shipping->address ?? '' }} {{ $order->shipping->area ? ', '.$order->shipping->area : '' }}</p>
        <p><strong>Payment Method:</strong> {{ strtoupper((string) ($order->payment->payment_method ?? 'N/A')) }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 8%;">SL</th>
                <th>Product</th>
                <th style="width: 14%;" class="text-right">Price</th>
                <th style="width: 10%;" class="text-right">Qty</th>
                <th style="width: 16%;" class="text-right">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->orderdetails as $detail)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>
                        {{ $detail->product_name }}
                        @if($detail->product_size || $detail->product_color)
                            <br>
                            <span class="muted">
                                @if($detail->product_size) Size: {{ $detail->product_size }} @endif
                                @if($detail->product_color) Color: {{ $detail->product_color }} @endif
                            </span>
                        @endif
                    </td>
                    <td class="text-right">{{ number_format((float) $detail->sale_price, 2) }}</td>
                    <td class="text-right">{{ number_format((float) $detail->qty, 2) }}</td>
                    <td class="text-right">{{ number_format((float) $detail->sale_price * (float) $detail->qty, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    @php
        $invoiceSubtotal = $order->orderdetails->sum(fn ($item) => (float) $item->sale_price * (float) $item->qty);
        $invoiceDiscount = (float) $order->discount;
        $invoiceDeliveryCharge = (float) $order->shipping_charge;
        if ((float) $order->amount > 0) {
            $invoiceDeliveryCharge = max(0, (float) $order->amount - $invoiceSubtotal + $invoiceDiscount);
        }
    @endphp

    <table class="summary">
        <tr>
            <th>Subtotal</th>
            <td class="text-right">{{ number_format($invoiceSubtotal, 2) }}</td>
        </tr>
        <tr>
            <th>Delivery Charge</th>
            <td class="text-right">{{ number_format($invoiceDeliveryCharge, 2) }}</td>
        </tr>
        <tr>
            <th>Discount</th>
            <td class="text-right">{{ number_format($invoiceDiscount, 2) }}</td>
        </tr>
        <tr>
            <th>Grand Total</th>
            <td class="text-right"><strong>{{ number_format((float) $order->amount, 2) }}</strong></td>
        </tr>
    </table>
</body>
</html>
