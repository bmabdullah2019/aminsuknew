<div class="modal-view quick-product">
    <button class="close-modal">x</button>
    <div class="quick-product-img">
        <img src="{{ asset($data->display_image) }}" alt="">
    </div>
    <div class="quick-product-content">
        <div class="product-details-cart">
            <p class="name">{{ $data->name }}</p>
            <p style="display: none;" class="product_star"><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i> ({{ $data->reviews_count }} customer review)</p>
            <p class="details-price">৳{{ $data->new_price }} @if($data->old_price)<del>৳{{ $data->old_price }}</del>@endif</p>
            @php
                $isOutOfStock = (float) ($data->available_stock ?? 0) <= 0;
            @endphp
            @if($isOutOfStock)
            <p class="text-danger fw-bold mb-2">Stock Out</p>
            @endif
            <div class="details_short">
                {!! $data->short_description !!}
            </div>
            <form action="{{ route('cart.store') }}" method="POST">
                @csrf
                <input type="hidden" name="id" value="{{ $data->id }}">

                <div class="qty-cart">
                    <div class="quantity">
                        <span class="minus">-</span>
                        <input type="text" name="qty" value="1" @if($isOutOfStock) disabled @endif />
                        <span class="plus">+</span>
                    </div>
                    <button type="submit" class="add-to-cart cart_store @if($isOutOfStock) out-of-stock @endif" data-id="{{ $data->id }}" data-stock="{{ (float) ($data->available_stock ?? 0) }}" @if($isOutOfStock) disabled @endif>@if($isOutOfStock) stock out @else add to cart @endif</button>
                </div>
            </form>
            <a href="{{ route('product', ['id' => $data->slug]) }}" style="display: none;" class="details-wishlist">Go To Details</a>
            <div class="col-12 mt-3 delivery_details">
                <table class="table">
                    <tbody>
                        <tr>
                            <td class="potro_font">
                                Category: {{ $data->category->name }}
                            </td>
                        </tr>
                        <tr>
                            <td class="potro_font">
                                Brand: {{ $data->brand ? $data->brand->name : '' }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<script>
    $('.close-modal').on('click', function() {
        $("#custom-modal").hide();
        $("#page-overlay").hide();
    });
</script>
<script>
    $(document).ready(function() {
        $('.minus').click(function() {
            var $input = $(this).parent().find('input');
            var count = parseInt($input.val()) - 1;
            count = count < 1 ? 1 : count;
            $input.val(count);
            $input.change();
            return false;
        });
        $('.plus').click(function() {
            var $input = $(this).parent().find('input');
            $input.val(parseInt($input.val()) + 1);
            $input.change();
            return false;
        });
    });
</script>
<!-- cart js start -->
