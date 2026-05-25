<script src="{{ asset('public/frontEnd/js/jquery-ui.js') }}"></script>
<script>
    (function () {
        var $sortForm = $(".sort-form");
        var $attributeForm = $(".attribute-submit");
        var $priceRange = $("#price-range");
        var $minInput = $("#min_price");
        var $maxInput = $("#max_price");

        $(".sort").on("change", function () {
            if ($sortForm.length) {
                $sortForm.trigger("submit");
            }
        });

        $(".form-attribute").on("change", function () {
            if ($attributeForm.length) {
                $attributeForm.trigger("submit");
            }
        });

        if (!$priceRange.length || typeof $.fn.slider !== "function") {
            return;
        }

        var minPrice = Number(@json((float) ($min_price ?? 0)));
        var maxPrice = Number(@json((float) ($max_price ?? 0)));
        if (!Number.isFinite(minPrice) || !Number.isFinite(maxPrice)) {
            return;
        }
        if (maxPrice < minPrice) {
            maxPrice = minPrice;
        }

        var selectedMin = Number(@json(request()->filled('min_price') ? (float) request()->input('min_price') : (float) ($min_price ?? 0)));
        var selectedMax = Number(@json(request()->filled('max_price') ? (float) request()->input('max_price') : (float) ($max_price ?? 0)));

        if (!Number.isFinite(selectedMin)) {
            selectedMin = minPrice;
        }
        if (!Number.isFinite(selectedMax)) {
            selectedMax = maxPrice;
        }

        selectedMin = Math.max(minPrice, Math.min(selectedMin, maxPrice));
        selectedMax = Math.min(maxPrice, Math.max(selectedMax, selectedMin));

        $priceRange.slider({
            step: 1,
            range: true,
            min: minPrice,
            max: maxPrice,
            values: [selectedMin, selectedMax],
            slide: function (event, ui) {
                $minInput.val(ui.values[0]);
                $maxInput.val(ui.values[1]);
            },
            stop: function () {
                if ($attributeForm.length) {
                    $attributeForm.trigger("submit");
                }
            }
        });

        $minInput.val(selectedMin);
        $maxInput.val(selectedMax);
    })();
</script>
