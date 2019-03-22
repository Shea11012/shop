@extends('layouts.app')
@section('title','查看订单')

@section('content')
    <div class="row">
        <div class="col-lg-10 offset-lg-1">
            <div class="card">
                <div class="card-header">
                    <h4>订单详情</h4>
                </div>
                <div class="card-body">
                    <table class="table">
                        <thead>
                        <tr>
                            <th>商品信息</th>
                            <th class="text-center">单价</th>
                            <th class="text-center">数量</th>
                            <th class="text-right item-amount">小计</th>
                        </tr>
                        </thead>
                        @foreach($order->items as $index => $item)
                            <tr>
                                <td class="product-info">
                                    <div class="preview">
                                        <a href="{{ route('products.show',[$item->product_id]) }}" target="_blank">
                                            <img src="{{ $item->product->image_url }}" alt="">
                                        </a>
                                    </div>
                                    <div>
                                        <span class="product-title">
                                            <a href="{{ route('products.show',[$item->product_id]) }}"
                                               target="_blank">{{ $item->product->title }}</a>
                                        </span>
                                        <span class="sku-title">{{ $item->productSku->title }}</span>
                                    </div>
                                </td>
                                <td class="sku-price text-center vertical-middle">￥{{ $item->price }}</td>
                                <td class="sku-amount text-center vertical-middle">￥{{ $item->amount }}</td>
                                <td class="item-amount text-right vertical-middle">
                                    ￥ {{ number_format($item->price * $item->amount,2,'.','') }}</td>
                            </tr>
                        @endforeach
                        <tr>
                            <td colspan="4"></td>
                        </tr>
                    </table>
                    <div class="order-bottom">
                        <div class="order-info">
                            <div class="line">
                                <div class="line-label">收货地址：</div>
                                <div class="line-value">{{ join(' ',$order->address) }}</div>
                            </div>
                            <div class="line">
                                <div class="line-label">订单备注：</div>
                                <div class="line-value">{{ $order->remark ?: '-' }}</div>
                            </div>
                            <div class="line">
                                <div class="line-label">订单编号：</div>
                                <div class="line-value">{{ $order->no }}</div>
                            </div>
                            <div class="line">
                                <div class="line-label">物流状态：</div>
                                <div
                                    class="line-value">{{ \App\Models\Order::$shipStatusMap[$order->ship_status] }}</div>
                            </div>
                            @if($order->ship_data)
                                <div class="line">
                                    <div class="line-label">物流信息：</div>
                                    <div
                                        class="line-value">{{ $order->ship_data['express_company'] }} {{ $order->ship_data['express_no'] }}</div>
                                </div>
                            @endif

                            @if($order->paid_at && $order->refund_status !== \App\Models\Order::REFUND_STATUS_PENDING)
                                <div class="line">
                                    <div class="line-label">退款状态：</div>
                                    <div
                                        class="line-value">{{ \App\Models\Order::$refundStatusMap[$order->refund_status] }}</div>
                                </div>
                                <div class="line">
                                    <div class="line-label">退款理由：</div>
                                    <div class="line-value">{{ $order->extra['refund_reason'] }}</div>
                                </div>
                            @endif
                        </div>

                        <div class="order-summary text-right">
                            <div class="total-amount">
                                <span>订单总价：</span>
                                <div class="value">￥{{ $order->total_amount }}</div>
                            </div>
                            <div>
                                <span>订单状态：</span>
                                <div class="value">
                                    @if($order->paid_at)
                                        @if($order->refund_status === \App\Models\Order::REFUND_STATUS_PENDING)
                                            已支付
                                        @else
                                            {{ \App\Models\Order::$refundStatusMap[$order->refund_status] }}
                                        @endif
                                    @elseif($order->closed)
                                            已关闭
                                    @else
                                            未支付
                                    @endif
                                </div>
                            </div>
                            @if(isset($order->extra['refund_disagree_reason']))
                                <div>
                                    <span>拒绝退款理由</span>
                                    <div class="value">{{ $order->extra['refund_disagree_reason'] }}</div>
                                </div>
                            @endif
                            @if(!$order->paid_at && !$order->closed)
                                <div class="payment-buttons">
                                    <a href="{{ route('payment.alipay',['order' => $order->id]) }}"
                                       class="btn btn-primary btn-sm">支付宝支付</a>
                                    <button class="btn btn-sm btn-success" id="btn-wechat">微信支付</button>
                                    @if($order->total_amount >= config('app.min_installment_amount'))
                                        <button class="btn btn-sm btn-danger" id="btn-installment">分期付款</button>
                                    @endif
                                </div>
                            @endif

                            @if($order->ship_status === \App\Models\Order::SHIP_STATUS_DELIVERED)
                                <div class="receive-button">
                                    <button type="button" id="btn-receive" class="btn btn-sm btn-success">确认收货</button>
                                </div>
                            @endif

                            @if($order->type !== \App\Models\Order::TYPE_CROWDFUNDING && $order->paid_at && $order->refund_status === \App\Models\Order::REFUND_STATUS_PENDING)
                                <div class="refund-button">
                                    <button class="btn btn-sm btn-danger" id="btn-apply-refund">申请退款</button>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    {{-- 分期弹框 --}}
    <div class="modal fade" id="installment-modal">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">选择分期期数</h5>
                    <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">x</span>
                    </button>
                </div>
                <div class="modal-body">
                    <table class="table table-bordered table-striped text-center">
                        <thead>
                        <tr>
                            <th class="text-center">期数</th>
                            <th class="text-center">费率</th>
                            <th class="text-center"></th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach(config('app.installment_fee_rate') as $count => $rate)
                            <tr>
                                <td>{{ $count }}期</td>
                                <td>{{ $rate }}%</td>
                                <td>
                                    <button class="btn btn-sm btn-primary btn-select-installment"
                                            data-count="{{ $count }}">选择
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-default" type="button" data-dismiss="modal">取消</button>
                </div>
            </div>
        </div>
    </div>
@stop

@section('scriptsAfterJs')
    <script>
        $(function () {
            $('#btn-wechat').click(function () {
                swal({
                    content: $('<img src="{{ route('payment.wechat',['order' => $order->id]) }}">')[0],
                    buttons: ['关闭', '已完成付款'],
                })
                    .then(result => {
                        if (result) {
                            location.reload();
                        }
                    })
            });

            $('#btn-receive').click(function () {
                swal({
                    title: '确认已经收到商品？',
                    icon: 'waring',
                    dangerMode: true,
                    buttons: ['取消', '确认收到'],
                }).then(res => {
                    if (!res) {
                        return;
                    }

                    axios.post('{{ route('orders.received',[$order->id]) }}')
                        .then(() => {
                            location.reload();
                        })
                })
            });

            $('#btn-apply-refund').click(function () {
                swal({
                    text: '请输入退款理由',
                    content: 'input',
                }).then(input => {
                    if (!input) {
                        swal('退款理由不可为空', '', 'error');
                        return;
                    }

                    axios.post('{{ route('orders.apply_refund',[$order->id]) }}', {reason: input})
                        .then(() => {
                            swal('申请退款成功', '', 'success')
                                .then(() => {
                                    location.reload();
                                })
                        })
                })
            });

            $('#btn-installment').click(function () {
                $('#installment-modal').modal();
            });

            $('btn-select-installment').click(function () {
                axios.post('{{ route('payment.installment',['order' => $order->id]) }}', {count: $(this).data('count')})
                    .then(response => {
                        location.href = '/installments/' + response.data.id;
                    })
            })
        })
    </script>
@stop
