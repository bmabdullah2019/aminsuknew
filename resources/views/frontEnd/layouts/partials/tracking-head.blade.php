@php
    $storefrontPixelCodes = collect($activePixelCodes ?? []);
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


@foreach($storefrontGtmCodes as $gtmCode)
<!-- Google tag (gtag.js) -->
<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
})(window,document,'script','dataLayer','GTM-' + @json($gtmCode));</script>
<!-- End Google Tag Manager -->
@endforeach
