<?php

/**
 * Inventory source-of-truth configuration.
 *
 * Variable product sellable quantity is derived from `inventories` + `warehouse_stock`
 * (per warehouse + product_variant_id), managed via Stock / Inventory admin screens — not the product form.
 */
return [
    'variant_stock_on_product_form' => false,
];
