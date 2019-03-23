<?php
/**
 * User: shea
 * Date: 19-3-18
 * Time: 下午8:40
 */

namespace App\Services;

use App\Exceptions\CouponCodeUnavailableException;
use App\Exceptions\InternalException;
use App\Exceptions\InvalidRequestException;
use App\Jobs\CloseOrder;
use App\Jobs\RefundInstallmentOrder;
use App\Models\CouponCode;
use App\Models\Order;
use App\Models\ProductSku;
use App\Models\User;
use App\Models\UserAddress;
use Carbon\Carbon;
use function GuzzleHttp\Psr7\uri_for;

class OrderService
{
    public function store(User $user, UserAddress $userAddress, $remark, $items, CouponCode $couponCode)
    {
        $user = \Auth::user();
        // 如果传入优惠券，先检查是否可用
        if ($couponCode) {
            $couponCode->checkAvailable($user);
        }

        $order = \DB::transaction(function () use ($user, $userAddress, $remark, $items, $couponCode) {
            $userAddress->update(['last_update_at' => Carbon::now()]);

            $order = new Order([
                'address'      => [
                    'address'       => $userAddress->full_address,
                    'zip'           => $userAddress->zip,
                    'contact_name'  => $userAddress->contact_name,
                    'contact_phone' => $userAddress->contact_phone,
                ],
                'remark'       => $remark,
                'total_amount' => 0,
                'type'         => Order::TYPE_NORMAL,
            ]);

            $order->user()->associate($user);
            $order->save();

            $totalAmount = 0;

            foreach ($items as $data) {
                $sku = ProductSku::find($data['sku_id']);
                $item = $order->items()->make([
                    'amount' => $data['amount'],
                    'price'  => $sku->price,
                ]);
                $item->product()->associate($sku->product_id);
                $item->productSku()->associate($sku);
                $item->save();
                $totalAmount += $sku->price * $data['amount'];
                if ($sku->decreaseStock($data['amount']) <= 0) {
                    throw new InvalidRequestException('该商品库存不足');
                }
            }
            if ($couponCode) {
                // 计算出总金额后，判断是否符合优惠券规则
                $couponCode->checkAvailable($user, $totalAmount);
                // 将订单金额修改为优惠券后的金额
                $totalAmount = $couponCode->getAdjustedPrice($totalAmount);
                $order->couponCode()->associate($couponCode);

                if ($couponCode->changeUsed() <= 0) {
                    throw new CouponCodeUnavailableException('该优惠券已被兑完');
                }
            }
            $order->update(['total_amount' => $totalAmount]);
            $skuIds = collect($items)->pluck('sku_id')->all();
            app(CartService::class)->remove($skuIds);
            return $order;
        });

        dispatch(new CloseOrder($order, config('app.order_ttl')));

        return $order;
    }

    public function crowdfunding(User $user, UserAddress $userAddress, ProductSku $sku, $amount)
    {
        $order = \DB::transaction(function () use ($amount, $sku, $user, $userAddress) {
            $userAddress->update(['last_used_at' => Carbon::now()]);
            $order = new Order([
                'address'      => [
                    'address'       => $userAddress->full_address,
                    'zip'           => $userAddress->zip,
                    'contact_name'  => $userAddress->contact_name,
                    'contact_phone' => $userAddress->contact_phone,
                ],
                'remark'       => '',
                'total_amount' => $sku->price * $amount,
                'type'         => Order::TYPE_CROWDFUNDING,
            ]);

            $order->user()->associate($user);

            $order->save();

            $item = $order->items()->make([
                'amount' => $amount,
                'price'  => $sku->price,
            ]);

            $item->product()->associate($sku->product_id);
            $item->productSku()->associate($sku);
            $item->save();

            if ($sku->decreaseStock($amount) <= 0) {
                throw new InvalidRequestException('该商品库存不足');
            }

            return $order;
        });
        // 距离众筹结束时间还剩多久
        $crowdfundingTtl = $sku->product->crowdfunding->end_at->getTimestamp() - time();
        // 剩余秒数与默认订单关闭时间取较小值作为订单关闭时间
        dispatch(new CloseOrder($order, min(config('app.order_ttl'), $crowdfundingTtl)));

        return $order;
    }

    public function refundOrder(Order $order)
    {
        switch ($order->payment_method) {
            case 'wechat':
                $refundNo = Order::getAvailableRefundNo();
                app('wechat_pay')->refund([
                    'out_trade_no'  => $order->no,
                    'total_fee'     => $order->total_amount * 100,
                    'refund_fee'    => $order->total_amount * 100,
                    'out_refund_no' => $refundNo,
                    'notify_url'    => route('payment.wechat.refund_notify'),
                ]);
                $order->update([
                    'refund_no'     => $refundNo,
                    'refund_status' => Order::REFUND_STATUS_PROCESSING,
                ]);
                break;

            case 'alipay':
                $refundNo = Order::getAvailableRefundNo();
                $ret = app('alipay')->refund([
                    'out_trade_no'   => $order->no,
                    'refund_amount'  => $order->total_amount,
                    'out_request_no' => $refundNo,
                ]);

                if ($ret->sub_code) {
                    $extra = $order->extra;
                    $extra['refund_failed_code'] = $ret->sub_code;
                    $order->update([
                        'refund_no' => $refundNo,
                        'refund_status' => Order::REFUND_STATUS_FAILED,
                        'extra' => $extra,
                    ]);
                } else {
                    $order->update([
                        'refund_no' => $refundNo,
                        'refund_status' => Order::REFUND_STATUS_SUCCESS,
                    ]);
                }
                break;
            case 'installment':
                $order->update([
                    'refund_no' => Order::getAvailableRefundNo(),
                    'refund_status' => Order::REFUND_STATUS_PROCESSING,
                ]);
                dispatch(new RefundInstallmentOrder($order));
                break;
            default:
                throw new InternalException('未知订单支付方式：'.$order->payment_method);
                break;
        }
    }
}
