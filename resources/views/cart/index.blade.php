@extends('layouts.app')
@section('title','购物车')

@section('content')
    <div class="row">
        <div class="col-lg-10 offset-lg-1">
            <div class="card">
                <div class="card-header">我的购物车</div>
                <div class="card-body">
                    <table class="table table-striped">
                        <thead>
                        <tr>
                            <th><input type="checkbox" id="select-all"></th>
                            <th>商品信息</th>
                            <th>单价</th>
                            <th>数量</th>
                            <th>操作</th>
                        </tr>
                        </thead>
                        <tbody class="product_list">
                        @foreach($cartItems as $item)
                            <tr data-id="{{ $item->productSku->id }}">
                                <td>
                                    <input type="checkbox" name="select"
                                           value="{{ $item->productSku->id }}" {{ $item->productSku->product->on_sale ? 'checked' : 'disabled' }}>
                                </td>
                                <td class="product_info">
                                    <div class="preview">
                                        <a href="{{ route('products.show',[$item->productSku->product_id]) }}"
                                           target="_blank">
                                            <img src="{{ $item->productSku->product->image_url }}" alt="">
                                        </a>
                                    </div>
                                    <div @if(!$item->productSku->product->on_sale) class="not_on_sale" @endif>
                                        <span class="product_title">
                                            <a href="{{ route('products.show',[$item->productSku->product_id]) }}"
                                               target="_blank">{{ $item->productSku->product->title }}</a>
                                        </span>
                                        <span class="sku_title">{{ $item->productSku->title }}</span>
                                        @if(!$item->productSku->product->on_sale)
                                            <span class="warning">该商品已下架</span>
                                        @endif
                                    </div>
                                </td>
                                <td><span class="price">￥{{ $item->productSku->price }}</span></td>
                                <td>
                                    <input type="text" class="form-control form-control-sm amount"
                                           @if(!$item->productSku->product->on_sale) disabled @endif name="amount"
                                           value="{{ $item->amount }}">
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-danger btn-remove">移除</button>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>

                    <div>
                        <form class="form-horizontal" role="form" id="order-form">
                            <div class="form-group row">
                                <label class="col-form-label col-sm-3 text-md-right">选择收货地址</label>
                                <div class="col-sm-9 col-md-7">
                                    <select name="address" class="form-control">
                                        @foreach($addresses as $address)
                                            <option
                                                value="{{ $address->id }}">{{ $address->full_address }} {{ $address->contact_name }} {{ $address->contact_phone }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="form-group row">
                                <label class="col-form-label col-sm-3 text-md-right">备注</label>
                                <div class="col-sm-9 col-md-7">
                                    <textarea name="remark" class="form-control" rows="3"></textarea>
                                </div>
                            </div>
                            
                            <div class="form-group row">
                                <label class="col-form-label col-sm-3 text-md-right">优惠码</label>
                                <div class="col-sm-4">
                                    <input type="text" class="form-control" name="coupon_code">
                                    <span class="form-text text-muted" id="coupon_desc"></span>
                                </div>
                                <div class="col-sm-3">
                                    <button class="btn btn-success" id="btn-check-coupon" type="button">检查</button>
                                    <button type="button" class="btn btn-danger" id="btn-cancel-coupon" style="display: none;">取消</button>
                                </div>
                            </div>

                            <div class="form-group">
                                <div class="offset-sm-3 col-sm-3">
                                    <button type="button" class="btn btn-primary btn-create-order">提交订单</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@stop

@section('scriptsAfterJs')
    <script>
        $(function () {
            $('.btn-remove').click(function () {
                const id = $(this).closest('tr').data('id');

                swal({
                    title: '确认要将该商品移除？',
                    icon: 'warning',
                    button: ['取消', '确定'],
                    dangerMode: true,
                }).then(function (willDelete) {
                    if (!willDelete) {
                        return;
                    }
                    axios.delete('/cart/' + id)
                        .then(function () {
                            location.reload();
                        })
                })
            });

            $('#select-all').change(function () {
                let checked = $(this).prop('checked');

                $('input[name=select][type=checkbox]:not([disabled])').each(function () {
                    $(this).prop('checked', checked);
                })
            });

            $('.btn-create-order').click(function () {
                let orderForm = $('#order-form');
                let req = {
                    address_id: orderForm.find('select[name=address]').val(),
                    items: [],
                    remark: orderForm.find('textarea[name=remark]').val(),
                    coupon_code: $('input[name=coupon_code]').val(),
                };

                $('table tr[data-id]').each(function () {
                    let $checkbox = $(this).find('input[name=select][type=checkbox]');
                    if ($checkbox.prop('disabled') || !$checkbox.prop('checked')) {
                        return;
                    }

                    let $input = $(this).find('input[name=amount]');
                    if ($input.val() == 0 || isNaN($input.val())) {
                        return;
                    }

                    req.items.push({
                        sku_id: $(this).data('id'),
                        amount: $input.val()
                    })
                });

                axios.post('{{ route('orders.store') }}', req)
                    .then(response => {
                        swal('订单提交成功', '', 'success')
                            .then(() => {
                                location.href = '/orders/' + response.data.id;
                            });
                    }).catch(error => {
                    if (error.response.status === 422) {
                        let html = '<div>';
                        _.each(error.response.data.errors, function (errors) {
                            _.each(errors, function (error) {
                                html += error + '<br>';
                            })
                        });
                        html += '</div>';
                        swal({content: $(html)[0], icon: 'error'})
                    } else {
                        swal('系统错误', '', 'error');
                    }
                })
            });

            $('#btn-check-coupon').click(function () {
                let code = $('input[name=coupon_code]').val();

                if (!code) {
                    swal('请输入优惠码','','warning');
                    return;
                }

                axios.get('/coupon_codes/' + encodeURIComponent(code))
                    .then(response => {
                        $('#coupon_desc').text(response.data.description);
                        $('input[name=coupon_code]').prop('readonly',true);
                        $('#btn-cancel-coupon').show();
                        $('#btn-check-coupon').hide();
                    }).catch(error => {
                        if (error.response.status === 404) {
                            swal('优惠码不存在','','error');
                        } else if (error.response.status === 403) {
                            swal(error.response.data.msg,'','error');
                        } else {
                            swal('系统内部错误','','error')
                        }
                })
            });

            $('#btn-cancel-coupon').click(function () {
                $('#coupon_desc').text('');
                $('input[name=coupon_code]').prop('readonly',false);
                $('#btn-cancel-coupon').hide();
                $('#btn-check-coupon').show();
            });
        })
    </script>
@stop
