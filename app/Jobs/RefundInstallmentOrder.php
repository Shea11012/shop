<?php

namespace App\Jobs;

use App\Exceptions\InternalException;
use App\Models\Installment;
use App\Models\InstallmentItem;
use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class RefundInstallmentOrder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // 不是分期付款，订单未支付，订单退款状态不是退款中
        if ($this->order->payment_method !== 'installment' || !$this->order->paid_at || $this->order->refund_status !== Order::REFUND_STATUS_PROCESSING) {
            return;
        }

        if (!$installment = Installment::query()->where('order_id', $this->order->id)->first()) {
            return;
        }

        foreach ($installment->items as $item) {
            // 如果还款计划未支付，或者退款状态为退款成功或退款中，则跳过
            if (!$item->paid_at || in_array($item->refund_status, [InstallmentItem::REFUND_STATUS_SUCCESS, InstallmentItem::REFUND_STATUS_PROCESSING])) {
                continue;
            }
            try {
                $this->refundInstallmentItems($item);
            } catch (\Exception $e) {
                \Log::warning('分期退款失败：' . $e->getMessage(), [
                    'installment_item_id' => $item->id,
                ]);
                // 假如某个还款计划退款报错，暂时跳过，继续处理下一个还款计划的退款
                continue;
            }
        }
        $installment->refreshRefundStatus();
    }

    protected function refundInstallmentItem(InstallmentItem $item)
    {
        $refundNo = $this->order->refund_no . '_' . $item->sequence;

        switch ($item->payment_method) {
            case 'wechat':
                app('wechat_pay')->refund([
                    'transaction_id' => $item->payment_no,
                    'total_fee'      => $item->total * 100,
                    'refund_fee'     => $item->base * 100,
                    'out_refund_no'  => $refundNo,
                    'notify_url'     => route('installments.wechat.refund_notify'),
                ]);
                $item->update([
                    'refund_status' => InstallmentItem::REFUND_STATUS_PROCESSING,
                ]);
                break;
            case 'alipay':
                $result = app('alipay')->refund([
                    'trade_no'       => $item->payment_no,
                    'refund_amount'  => $item->base,
                    'out_request_no' => $refundNo,
                ]);

                if ($result->sub_code) {
                    $item->update([
                        'refund_status' => InstallmentItem::REFUND_STATUS_FAILED,
                    ]);
                } else {
                    $item->update([
                        'refund_status' => InstallmentItem::REFUND_STATUS_SUCCESS,
                    ]);
                }
                break;
            default:
                throw new InternalException('未知订单支付方式：'.$item->payment_method);
                break;
        }
    }
}
