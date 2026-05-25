@php
    $subtotal = Cart::instance('shopping')->subtotal();
    $subtotal=str_replace(',','',$subtotal);
    $subtotal=str_replace('.00', '',$subtotal);
    $shipping = Session::get('shipping')?Session::get('shipping'):0;
    $discount = Session::get('discount')?Session::get('discount'):0;
@endphp
<table class="cart_table table table-bordered table-striped text-center mb-0">
        <thead>
         <tr>
          <th style="width: 20%;">ডিলিট</th>
          <th style="width: 40%;">প্রোডাক্ট</th>
          <th style="width: 20%;">পরিমাণ</th>
          <th style="width: 20%;">মূল্য</th>
         </tr>
        </thead>

        <tbody>
         @foreach(Cart::instance('shopping')->content() as $value)
         <tr class="cart-item" data-product-id="{{ $value->id }}" data-rowid="{{ $value->rowId }}">
          <td>
           <a class="cart_remove" data-id="{{ $value->rowId }}"><i class="fas fa-trash text-danger"></i></a>
          </td>
          <td class="text-left">
           @php
                $cartImage = (string) ($value->options->image ?? 'public/frontEnd/images/no-image.jpg');
                if (\Illuminate\Support\Str::startsWith($cartImage, 'storage/')) {
                    $cartImage = 'public/' . $cartImage;
                } elseif (\Illuminate\Support\Str::startsWith($cartImage, 'uploads/')) {
                    $cartImage = 'public/' . $cartImage;
                }
           @endphp
           <a href="{{ route('product', $value->options->slug) }}"> <img src="{{ asset($cartImage) }}" />
            {{ Str::limit($value->name, 20) }}</a>
           @if ($value->options->product_size)
            <p>Size: {{ $value->options->product_size }}</p>
           @endif
           @if ($value->options->product_color)
            <p>Color: {{ $value->options->product_color }}</p>
           @endif
          </td>
          <td class="cart_qty">
           <div class="qty-cart vcart-qty">
            <div class="quantity">
             <button class="minus cart_decrement" data-id="{{ $value->rowId }}">-</button>
             <input type="text" value="{{ $value->qty }}" readonly data-qty="{{ $value->qty }}" />
             <button class="plus cart_increment" data-id="{{ $value->rowId }}">+</button>
            </div>
           </div>
          </td>
          <td><span class="alinur">৳ </span><strong>{{ $value->price }}</strong></td>
         </tr>
         @endforeach
        </tbody>
        <tfoot>
         <tr>
          <th colspan="3" class="text-end px-4">মোট</th>
          <td>
           <span id="net_total"><span class="alinur">৳ </span><strong>{{$subtotal}}</strong></span>
          </td>
         </tr>
         <tr>
          <th colspan="3" class="text-end px-4">ডেলিভারি চার্জ</th>
          <td>
           <span id="cart_shipping_cost"><span class="alinur">৳ </span><strong>{{$shipping}}</strong></span>
          </td>
         </tr>
         @if(Session::get('discount', 0) > 0)
         <tr>
            <th colspan="3" class="text-end px-4">কুপন ছাড়</th>
            <td>
                <span id="discount"><span class="alinur">৳ </span><strong>{{ Session::get('discount', 0) }}</strong></span>
            </td>
        </tr>
        @endif
         <tr>
          <th colspan="3" class="text-end px-4">সর্বমোট</th>
          <td>
           <span id="grand_total"><span class="alinur">৳ </span><strong>{{$subtotal+$shipping-Session::get('discount', 0)}}</strong></span>
          </td>
         </tr>
        </tfoot>
       </table>
