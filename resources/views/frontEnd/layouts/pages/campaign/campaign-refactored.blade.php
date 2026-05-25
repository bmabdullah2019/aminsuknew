@extends('backEnd.layouts.master')
@section('title','Campaign Landing Page')
@section('content')

<!--========== CSS Styles ===========-->
<style>
    .button-3d {
        position: relative;
        overflow: hidden;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .button-3d:hover {
        transform: scale(1.05);
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
    }

    .button-animated-border {
        position: relative;
        overflow: hidden;
        border: 3px solid white;
        border-radius: 10px;
        transition: color 0.3s ease;
        animation: border-animation 3s linear infinite;
    }

    @keyframes border-animation {
        0% { border-color: white; }
        25% { border-color: yellow; }
        50% { border-color: white; }
        75% { border-color: yellow; }
        100% { border-color: white; }
    }

    .button-animated-border {
        will-change: border-color;
    }

    .animated-heading {
        animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        will-change: opacity;
    }

    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: .8; }
    }

    .product-card {
        transition: all 0.3s ease;
    }

    .product-card.selected {
        border-color: #28a745 !importa
            let rowId = $(this).data('id');
            let product_size = $('.cart-size-selector[data-id="' + rowId + '"]').val();
            let product_color = $('.cart-color-selector[data-id="' + rowId + '"]').val();
            
            $("#loading").show();
            $.ajax({
                type: "GET",
                data: { id: rowId, product_size: product_size, product_color: product_color, context: 'campaign' },
                url: "{{ route('cart.update') }}",
                success: function (response) {
                    $(".cartlist").html(response);
                    syncCampaignSelectionFromCart();
                    $("#loading").hide();
                }
            });
        });

        // Shipping area change
        $("#area").on("change", function () {
            let id = $(this).val();
            $.ajax({
                type: "GET",
                data: { id: id, context: 'campaign' },
                url: "{{ route('shipping.charge') }}",
                dataType: "html",
                success: function(response) {
                    $('.cartlist').html(response);
                    syncCampaignSelectionFromCart();
                }
            });
        });

        $(document).on('click', '.cam_order_now', function(event) {
            const target = document.getElementById('order_form');
            if (!target) {
                return;
            }

            event.preventDefault();
            target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
    </script>

    {!! $generalsetting->footer_code ?? '' !!}
</body>
</html>

