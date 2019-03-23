@extends('layouts.app')
@section('title',$product->title)

@section('content')
    <div class="row">
        <div class="col-lg-10 offset-lg-1">
            <div class="card">
                <div class="card-body product-info">
                    <div class="row">
                        <div class="col-5">
                            <img src="{{ $product->image_url }}" alt="" class="cover">
                        </div>

                        <div class="col-7">
                            <div class="title">{{ $product->long_title ?: $product->title }}</div>
                            {{-- 众筹商品 --}}
                            @if($product->type === \App\Models\Product::TYPE_CROWDFUNDING)
                                <div class="crowdfunding-info">
                                    <div class="have-text">已筹到</div>
                                    <div class="total-amount"><span
                                            class="symbol">￥</span>{{ $product->crowdfunding->total_amount }}</div>
                                    <div class="progress">
                                        <div class="progress-bar progress-bar-success progress-bar-striped"
                                             role="progressbar" aria-valuenow="{{ $product->crowdfunding->percent }}"
                                             aria-valuemin="0" aria-valuemax="100"
                                             style="min-width: 1em;width: {{ min($product->crowdfunding->percent,100) }}%"
                                        >
                                        </div>
                                    </div>
                                    <div class="progress-info">
                                        <span
                                            class="current-progress">当前进度：{{ $product->crowdfunding->percent }}%</span>
                                        <span class="float-right user-count">{{ $product->crowdfunding->user_count }}名支持者</span>
                                    </div>
                                    @if($product->crowdfunding->status === \App\Models\CrowdfundingProduct::STATUS_FUNDING)
                                        <div>此项目必须在
                                            <span
                                                class="text-red">{{ $product->crowdfunding->end_at->format('Y-m-d H:i:s') }}</span>
                                             前得到
                                            <span class="text-red">￥{{ $product->crowdfunding->target_amount }}</span>的支持才可成功，筹款将在
                                            <span
                                                class="text-red">{{ $product->crowdfunding->end_at->diffForHumans(now()) }}</span>结束！
                                        </div>
                                    @endif
                                </div>
                            @else
                                {{-- 普通商品 --}}
                                <div class="price"><label>价格</label><em>￥</em><span>{{ $product->price }}</span></div>
                                <div class="sales_and_reviews">
                                    <div class="sold_count">累计销量 <span class="count">{{ $product->sold_count }}</span>
                                    </div>
                                    <div class="review_count">累计评价 <span
                                            class="count">{{ $product->review_count }}</span>
                                    </div>
                                    <div class="rating" title="评分 {{ $product->rating }}">评分 <span
                                            class="count">{{ str_repeat('★',floor($product->rating)) }}{{ str_repeat('☆',5-floor($product->rating)) }}</span>
                                    </div>
                                </div>
                            @endif
                            <div class="skus">
                                <label>选择</label>
                                <div class="btn-group btn-group-toggle" data-toggle="buttons">
                                    @foreach($product->skus as $sku)
                                        <label title="{{ $sku->description }}" class="btn sku-btn"
                                               data-price="{{ $sku->price }}"
                                               data-stock="{{ $sku->stock }}" data-toogle="tooltip"
                                               data-placement="bottom">
                                            <input type="radio" name="skus" autocomplete="off"
                                                   value="{{ $sku->id }}">{{ $sku->title }}
                                        </label>
                                    @endforeach
                                </div>
                            </div>

                            <div class="cart_amount">
                                <label>数量</label>
                                <input type="text" class="form-control-sm form-control" value="1">
                                <span>件</span>
                                <span class="stock"></span>
                            </div>

                            <div class="buttons">
                                @if($favored)
                                    <button class="btn btn-danger btn-disfavor">取消收藏</button>
                                @else
                                    <button class="btn btn-success btn-favor">❤ 收藏</button>
                                @endif
                                @if($product->type === \App\Models\Product::TYPE_CROWDFUNDING)
                                    @if(Auth::check())
                                        @if($product->crowdfunding->status === \App\Models\CrowdfundingProduct::STATUS_FUNDING)
                                            <button class="btn btn-primary btn-crowdfunding">参与众筹</button>
                                        @else
                                            <button class="btn btn-primary disabled">
                                                {{ \App\Models\CrowdfundingProduct::$statusMap[$product->crowdfunding->status] }}
                                            </button>
                                        @endif
                                    @else
                                        <a href="{{ route('login') }}" class="btn btn-primary">请先登录</a>
                                    @endif
                                @else
                                    <button class="btn btn-primary btn-add-to-cart">加入购物车</button>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="product-detail">
                        <ul class="nav nav-tabs" role="tablist">
                            <li class="nav-item">
                                <a href="#product-detail-tab" class="nav-link active" aria-controls="product-detail-tab"
                                   role="tab" data-toggle="tab" aria-selected="true">商品详情</a>
                            </li>
                            <li class="nav-item">
                                <a href="#product-reviews-tab" class="nav-link" aria-controls="product-reviews-tab"
                                   role="tab" data-toggle="true" aria-selected="false">用户评价</a>
                            </li>
                        </ul>
                        <div class="tab-content">
                            <div class="tab-pane active" id="product-detail-tab"
                                 role="tabpanel">
                                {{-- 产品属性 --}}
                                <div class="properties-list">
                                    <div class="properties-list-title">产品参数：</div>
                                    <ul class="properties-list-body">
                                        @foreach($product->grouped_properties as $name => $values)
                                            <li>{{ $name }} : {{ join(' ',$values) }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                                {{-- 产品属性 --}}
                                <div class="product-description">
                                    {!! $product->decsription !!}
                                </div>
                            </div>
                            <div class="tab-pane" id="product-reviews-tab" role="tabpanel">
                                <table class="table table-bordered table-striped">
                                    <thead>
                                    <tr>
                                        <td>用户</td>
                                        <td>商品</td>
                                        <td>评分</td>
                                        <td>评价</td>
                                        <td>时间</td>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @foreach($reviews as $review)
                                        <tr>
                                            <td>{{ $review->order->user->name }}</td>
                                            <td>{{ $review->productSku->title }}</td>
                                            <td>str_repeat('★', $review->rating)
                                                }}{{ str_repeat('☆', 5 - $review->rating) }}</td>
                                            <td>{{ $review->review }}</td>
                                            <td>{{ $review->reviewed_at->format('Y-m-d H:i') }}</td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@stop

@section('scriptsAfterJs')
    <script>
        $(function () {
            $('[data-toogle="tooltip"]').tooltip({trigger: 'hover'});
            $('.sku-btn').click(function () {
                $('.product-info .price span').text($(this).data('price'));
                $('.product-info .stock').text('库存：' + $(this).data('stock') + '件');
            });

            $('.btn-favor').click(function () {
                axios.post('{{ route('products.favor',['product' => $product->id]) }}')
                    .then(function () {
                        swal('操作成功', '', 'success');
                    }).then(function () {
                    localtion.reload();
                }).catch(function (error) {
                    if (error.response && error.response.status === 401) {
                        swal('请先登录', '', 'error');
                    } else if (error.response && error.response.data.msg) {
                        swal(error.response.data.msg, '', 'error');
                    } else {
                        swal('系统错误', '', 'error');
                    }
                })
            });

            $('.btn-disfavor').click(function () {
                axios.delete('{{ route('products.disfavor',['product' => $product->id]) }}')
                    .then(function () {
                        swal('操作成功', '', 'success')
                    }).then(function () {
                    location.reload();
                })
            });

            $('.btn-add-to-cart').click(function () {
                axios.post('{{ route('cart.add') }}', {
                    sku_id: $('label.active input[name=skus]').val(),
                    amount: $('.cart_amount input').val(),
                }).then(function () {
                    swal('加入购物车', '', 'success').then(function () {
                        location.href = '{{ route('cart.index') }}';
                    })
                }).catch(function (error) {
                    if (error.response.status === 401) {
                        swal('请先登录', '', 'error');
                    } else if (error.response.status === 422) {
                        let html = '<div>';
                        _.each(error.response.data.errors, function (errors) {
                            _.each(errors, function (error) {
                                html += error + '<br>';
                            })
                        });
                        html += '</div>';
                        swal({content: $(html)[0], icon: 'error'});
                    } else {
                        swal('系统错误', '', 'error');
                    }
                })
            });

            $('.btn-crowdfunding').click(function () {
                if (!$('label.active input[name=skus]').val()) {
                    swal('请先选择商品');
                    return;
                }

                let addresses = {!! json_encode(Auth::check() ? Auth::user()->addresses() : []) !!};

                const $form = $('<form></form>');

                $form.append('<div class="form-group row">' + '<label class="col-form-label col-sm-3">请选择地址</label>' + '<div class="col-sm-9">' + '<select class="custom-select" name="address_id"></select>' + '</div></div>');
                addresses.forEach(function (address) {
                    $form.find('select[name=address_id]')
                        .append("<option value='" + address.id + "'>" + address.full_address + ' ' + address.contact_name + ' ' + address.contact_phone + '</option>');
                });

                $form.append('<div class="form-group row">' + '<label class="col-form-label col-sm-3">购买数量</label>' + '<div class="col-sm-9"><input class="form-control" name="amount">' + '</div></div>');

                swal({
                    text:'参与众筹',
                    content:$form[0],
                    buttons:['取消','确定']
                }).then(result => {
                    if (!result) return;

                    let req = {
                        address_id:$form.find('select[name=address_id]').val(),
                        amount:$form.find('input[name=amount]').val(),
                        sku_id:$('label.active input[name=skus]').val()
                    };

                    axios.post('{{ route('crowdfunding_orders.store') }}',req)
                        .then(response => {
                            swal('订单提交成功','','success')
                                .then(() => {
                                    location.href = '/orders/' + response.data.id;
                                })
                        }).catch(error => {
                          if (error.response.status === 422) {
                              let html = '<div>';
                              _.each(error.response.data.errors,function (errors) {
                                  _.each(errors,function (error) {
                                      html += error + '<br>';
                                  })
                              });

                              html += '</div>';
                              swal({content:$(html)[0],icon:'error'})
                          } else if(error.response.status === 403) {
                              swal(error.response.data.msg,'','error')
                          } else {
                              swal('系统错误','','error')
                          }
                    })
                })
            })

        })
    </script>
@stop
