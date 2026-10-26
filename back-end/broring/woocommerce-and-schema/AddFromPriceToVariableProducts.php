<?php

/*
 * Project:  Broring (broring-radicle, WooCommerce)
 * Origineel: app/Hooks/Filters/AddFromPriceToVariableProducts.php
 * Filter op woocommerce_variable_price_html: toont 'Vanaf' + doorgestreepte prijs, beide uit dezelfde variatie.
 */

namespace App\Hooks\Filters;

use App\Hooks\Filter;

class AddFromPriceToVariableProducts extends Filter
{
    public function hook(): array|string
    {
        return 'woocommerce_variable_price_html';
    }

    public function parameterCount(): int
    {
        return 2;
    }

    public function filter($price, $product): string
    {
        if ($product->is_on_sale() && $product->is_type('variable')) {
            $prices = $product->get_variation_prices(true);
            $min_price_variation_id = array_key_first($prices['price']);
            $min_price = $prices['price'][$min_price_variation_id];
            $min_regular_price = $prices['regular_price'][$min_price_variation_id];

            if ($min_regular_price > $min_price) {
                $price = wc_format_sale_price(wc_price($min_regular_price), wc_price($min_price));

                return __('Vanaf ', 'webreact') . $price;
            }

            return __('Vanaf ', 'webreact') . wc_price($min_price);
        }

        $min_price = $product->get_variation_price('min', true);
        $price = wc_price($min_price);

        return __('Vanaf ', 'webreact') . $price;
    }
}
