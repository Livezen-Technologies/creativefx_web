<?php

namespace Modules\Admin\Controllers;

/**
 * Discount codes.
 *
 * A fixed-amount coupon must name its currency. "500 off" is generous in USD
 * and meaningless in LKR, and the checkout refuses to apply a fixed coupon to a
 * cart in a different currency rather than converting it — which is the same
 * rule the whole pricing system follows.
 *
 * Percentage coupons leave the currency blank.
 */
class Coupons extends BaseCrudController
{
    protected string $modelClass = 'Modules\Commerce\Models\CouponModel';
    protected string $title      = 'Coupons';
    protected string $singular   = 'Coupon';
    protected string $route      = 'coupons';
    protected string $active     = 'coupons';
    protected string $orderBy    = 'id';
    protected string $orderDir   = 'DESC';

    protected array $listColumns = [
        ['name' => 'code', 'label' => 'Code'],
        ['name' => 'type', 'label' => 'Type'],
        ['name' => 'value', 'label' => 'Value'],
        ['name' => 'used_count', 'label' => 'Used'],
        ['name' => 'is_active', 'label' => 'Active', 'type' => 'badge'],
    ];

    protected array $fields = [
        ['name' => 'code', 'label' => 'Code', 'rules' => 'required|max_length[48]', 'help' => 'Upper case. This is what a buyer types.'],
        ['name' => 'type', 'label' => 'Type', 'type' => 'select', 'options' => ['percent' => 'Percentage off', 'fixed' => 'Fixed amount off']],
        ['name' => 'value', 'label' => 'Value', 'type' => 'number', 'help' => 'A percentage for "percent"; minor units — cents — for "fixed".'],
        ['name' => 'currency', 'label' => 'Currency', 'type' => 'select', 'options' => ['' => 'Any (percentage only)', 'USD' => 'USD', 'LKR' => 'LKR'], 'help' => 'Required for a fixed amount. A fixed coupon only applies to a cart in the same currency.'],
        ['name' => 'min_spend_cents', 'label' => 'Minimum spend', 'type' => 'number', 'help' => 'In minor units'],
        ['name' => 'max_uses', 'label' => 'Total uses allowed', 'type' => 'number'],
        ['name' => 'per_user_limit', 'label' => 'Uses per customer', 'type' => 'number'],
        ['name' => 'starts_at', 'label' => 'Valid from', 'help' => 'YYYY-MM-DD HH:MM:SS'],
        ['name' => 'ends_at', 'label' => 'Valid until', 'help' => 'YYYY-MM-DD HH:MM:SS'],
        ['name' => 'is_active', 'label' => 'Active', 'type' => 'checkbox'],
        ['name' => 'used_count', 'label' => 'Times used', 'type' => 'static'],
    ];
}
