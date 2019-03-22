@extends('layouts.app')
@section('title','商品列表')

@section('content')
    <div class="row">
        <div class="col-lg-10 offset-lg-1">
            <div class="card">
                <div class="card-body">
                    {{-- 筛选 --}}
                    <form action="{{ route('products.index') }}" class="search-form">
                        <div class="form-row">
                            <div class="col-md-9">
                                <div class="form-row">
                                    {{-- 面包屑 --}}
                                    <div class="col-auto category-breadcrumb">
                                        <a href="{{ route('products.index') }}" class="all-products">全部</a>
                                        @if($category)
                                            @foreach($category->ancestors as $ancestor)
                                                <span class="category">
                                                    <a href="{{ route('products.index',['category_id' => $ancestor->id]) }}">{{ $ancestor->name }}</a>
                                                </span>
                                                <span>&gt;</span>
                                            @endforeach
                                            <span class="category">{{ $category->name }}</span><span></span>
                                            {{-- 当前类目ID 当用户调整排序方式时，可以保证 category_id 参数不丢失 --}}
                                                <input type="hidden" name="category_id" value="{{ $category->id }}">
                                        @endif
                                    </div>
                                    {{-- 面包屑结束 --}}
                                    <div class="col-auto">
                                        <input type="text" class="form-control form-control-sm" name="search"
                                               placeholder="搜索">
                                    </div>
                                    <div class="col-auto">
                                        <button class="btn btn-primary btn-sm">搜索</button>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <select name="order" class="form-control form-control-sm float-right">
                                    <option value="">排序方式</option>
                                    <option value="price_asc">价格从低到高</option>
                                    <option value="price_desc">价格从高到低</option>
                                    <option value="sold_count_desc">销量从高到低</option>
                                    <option value="sold_count_asc">销量从低到高</option>
                                    <option value="rating_desc">评价从高到低</option>
                                    <option value="rating_asc">评价从低到高</option>
                                </select>
                            </div>
                        </div>
                    </form>

                    {{-- 展示子类目 --}}
                    <div class="filters">
                        @if($category && $category->is_directory)
                            <div class="row">
                                <div class="col-3 filter-key">子类目：</div>
                                <div class="col-9 filter-values">
                                    @foreach($category->children as $child)
                                        <a href="{{ route('products.index',['category_id' => $child->id]) }}">{{ $child->name }}</a>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                    {{-- 筛选 end --}}
                    <div class="row products-list">
                        @foreach($products as $product)
                            <div class="col-3 product-item">
                                <div class="product-content">
                                    <div class="top">
                                        <div class="img">
                                            <a href="{{ route('products.show',['product' => $product->id]) }}">
                                                <img src="{{ $product->image_url }}" alt="">
                                            </a>
                                        </div>
                                        <div class="price"><b>￥</b>{{ $product->price }}</div>
                                        <div class="title"><a
                                                href="{{ route('products.show',['product' => $product->id]) }}">{{ $product->title }}</a>
                                        </div>
                                    </div>
                                    <div class="bottom">
                                        <div class="sold_count">销量 <span>{{ $product->sold_count }}笔</span></div>
                                        <div class="review_count">评价 <span>{{ $product->review_count }}</span></div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div class="float-right">{{ $products->appends($filters)->render() }}</div>
                </div>
            </div>
        </div>
    </div>
@stop

@section('scriptsAfterJs')
    <script>
        let filters = {!! json_encode($filters) !!};
        $(function () {
            $('.search-form input[name=search]').val(filters.search);
            const selectSort = $('.search-form select[name=order]');

            selectSort.val(filters.order);

            selectSort.on('change', function () {
                $('.search-form').submit();
            })
        })
    </script>
@stop
