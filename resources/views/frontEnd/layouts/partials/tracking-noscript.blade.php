@php
    $envGtmId = env('GTM_ID');
    if ($envGtmId) {
        $envGtmId = trim(preg_replace('/^GTM-/i', '', $envGtmId));
    }
    $storefrontGtmCodes = collect($activeGtmCodes ?? []);
    if (!empty($envGtmId) && preg_match('/^[A-Za-z0-9_-]+$/', $envGtmId)) {
        $storefrontGtmCodes->push($envGtmId);
    }
    $storefrontGtmCodes = $storefrontGtmCodes->unique()->values();
@endphp

<!-- Google Tag Manager (noscript) -->
@foreach($storefrontGtmCodes as $gtmCode)
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-{{ rawurlencode($gtmCode) }}"
height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
@endforeach
<!-- End Google Tag Manager (noscript) -->
