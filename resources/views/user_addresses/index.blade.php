@extends('layouts.app')
@section('title','收货地址列表')

@section('content')
    <div class="row">
        <div class="col-md-10 offset-md-1">
            <div class="card panel-default">
                <div class="card-header">
                    收货地址列表
                    <a href="{{ route('user_addresses.create') }}" class="float-right">新增收货地址</a>
                </div>

                <div class="card-body">
                    <table class="table table-bordered table-striped">
                        <thead>
                        <tr>
                            <th>收货人</th>
                            <th>地址</th>
                            <th>邮编</th>
                            <th>电话</th>
                            <th>操作</th>
                        </tr>
                        </thead>

                        <tbody>
                        @foreach($addresses as $address)
                            <tr>
                                <td>{{ $address->contact_name }}</td>
                                <td>{{ $address->full_address }}</td>
                                <td>{{ $address->zip }}</td>
                                <td>{{ $address->contact_phone }}</td>
                                <td>
                                    <a href="{{ route('user_addresses.edit',['user_address' => $address->id]) }}" class="btn btn-primary">修改</a>
                                    <button class="btn btn-danger btn-del-address" type="button" data-id="{{ $address->id }}">删除</button>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@stop

@section('scriptsAfterJs')
    <script>
        $(function(){
            $('.btn-del-address').on('click',function () {
                let id = $(this).data('id');

                swal({
                    title:'确认删除该地址？',
                    icon:'warning',
                    buttons:['取消','确定'],
                    dangerMode:true
                })
                    .then(function (willDelete) { // 点击按钮触发，确定为 true，取消为 false
                        if (!willDelete){
                            return;
                        }

                        axios.delete('/user_addresses/' + id)
                            .then(function () {
                                location.reload();
                            })
                    })
            })
        })
    </script>
@stop
