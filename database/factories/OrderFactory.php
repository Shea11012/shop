<?php

use Faker\Generator as Faker;

$factory->define(App\Models\Order::class, function (Faker $faker) {
    $user = \App\Models\User::query()->inRandomOrder()->first();
    $address = $user->addresses()->inRandomOrder()->first();
    // 10% 的概率把订单标记为退款
    $refund = rand(0, 10) < 1;
    $ship = $faker->randomElement(array_keys(\App\Models\Order::$shipStatusMap));
    $coupon = null;
    // 30% 概率订单使用优惠券
    if (rand(0, 10) < 3) {
        // 此处为避免逻辑错误，只选择没有最低金额限制的优惠券
        $coupon = \App\Models\CouponCode::query()->where('min_amount', 0)->inRandomOrder()->first();
        $coupon->changeUsed();
    }
    return [
        'address'        => [
            'address'       => $address->full_address,
            'zip'           => $address->zip,
            'contact_name'  => $address->contact_name,
            'contact_phone' => $address->phone,
        ],
        'total_amount'   => 0,
        'remark'         => $faker->sentence,
        'paid_at'        => $faker->dateTimeBetween('-30 days'),
        'payment_method' => $faker->randomElement(['wechat', 'alipay']),
        'payment_no'     => $faker->uuid,
        'refund_status'  => $refund ? \App\Models\Order::REFUND_STATUS_SUCCESS : \App\Models\Order::REFUND_STATUS_PENDING,
        'refund_no'      => $refund ? \App\Models\Order::getAvailableRefundNo() : null,
        'closed'         => false,
        'reviewed'       => rand(0, 10) > 2,
        'ship_status'    => $ship,
        'ship_data'      => $ship === \App\Models\Order::SHIP_STATUS_PENDING ? null : [
            'express_company' => $faker->company,
            'express_no'      => $faker->uuid,
        ],
        'extra'          => $refund ? ['refund_reason' => $faker->sentence] : [],
        'user_id'        => $user->id,
        'coupon_code_id' => $coupon ? $coupon->id : null,
    ];
});
