@props(['order'])

@php
    if (!$order) {
        return;
    }

    $providerStatus = is_array($order->tracking_provider_status ?? null)
        ? $order->tracking_provider_status
        : [];

    // Browser-side GTM and server-side GA4 are tracked independently.
    // The browser event is rendered on the customer success page until this
    // specific provider flag is recorded by the fallback endpoint.
    $shouldRender = ($providerStatus['gtm_browser_purchase'] ?? null) !== 'success';
@endphp

@if($shouldRender)
    @php
        $pixelPurchaseCurrency = strtoupper((string) ($order->currency ?: 'BDT'));
        $pixelPurchaseValue = number_format((float) $order->amount, 2, '.', '');
        $pixelPurchaseEventId = 'order-' . (int) $order->id;
        $pixelPurchaseTransactionId = (string) ($order->invoice_id ?: $order->id);

        $gtmItems = $order->orderdetails->map(function ($item) {
            return [
                'item_id' => (string) $item->product_id,
                'item_name' => $item->product_name,
                'price' => (float) $item->sale_price,
                'quantity' => (int) $item->qty,
                'item_variant' => trim(($item->product_color ? $item->product_color . ' ' : '') . ($item->product_size ?: ''))
            ];
        })->values()->all();
    @endphp

    <script src="{{ asset('js/tracking-fallback.js') }}"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        dataLayer.push({ ecommerce: null }); // Clear previous ecommerce data
        dataLayer.push({
            event: "purchase",
            event_id: "{{ $pixelPurchaseEventId }}",
            ecommerce: {
                currency: "{{ $pixelPurchaseCurrency }}",
                value: {{ $pixelPurchaseValue }},
                transaction_id: "{{ $pixelPurchaseTransactionId }}",
                event_id: "{{ $pixelPurchaseEventId }}",
                items: {!! json_encode($gtmItems) !!}
            }
        });

        // Record only browser-side GTM success; server-side GA4 remains independent.
        window.reportTrackingFallback(
            {{ (int) $order->id }},
            "{{ $order->invoice_id }}",
            "{{ csrf_token() }}",
            "{{ route('tracking.fallback') }}"
        );
    </script>
@endif
