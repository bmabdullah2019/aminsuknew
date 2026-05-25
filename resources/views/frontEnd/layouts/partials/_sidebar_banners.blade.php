@php
    $sidebarBanners = $sidebarBanners ?? collect();
@endphp

@if ($sidebarBanners->isNotEmpty())
    <div class="sidebar_item wraper__item wc-sidebar-banners">
        @foreach ($sidebarBanners as $sidebarBanner)
            <a href="{{ $sidebarBanner->link ?: '#' }}" class="wc-sidebar-banner-link">
                <img src="{{ asset($sidebarBanner->image) }}" alt="Sidebar Banner {{ $loop->iteration }}"
                    loading="lazy" />
            </a>
        @endforeach
    </div>
@endif
