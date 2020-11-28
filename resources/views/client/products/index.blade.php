@extends('layouts.client-app')

@section('page-title')
    <div class="row bg-title">
        <!-- .page title -->
        <div class="col-lg-3 col-md-4 col-sm-4 col-xs-12">
            <h4 class="page-title"><i class="{{ $pageIcon }}"></i> @lang($pageTitle)</h4>
        </div>
        <!-- /.page title -->
        <!-- .breadcrumb -->
        <div class="col-lg-9 col-sm-8 col-md-8 col-xs-12">
            <ol class="breadcrumb">
                <li><a href="{{ route('member.dashboard') }}">@lang('app.menu.home')</a></li>
                <li class="active">@lang($pageTitle)</li>
            </ol>
        </div>
        <!-- /.breadcrumb -->
    </div>
@endsection

@push('head-script')
    <link rel="stylesheet" href="{{ asset('css/datatables/dataTables.bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/datatables/responsive.bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/datatables/buttons.dataTables.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/datatables/buttons.dataTables.min.css') }}">
@endpush

@section('content')

    <div class="row">
        <div class="col-md-12">
            <div class="white-box">
                <div class="row">
                    <div class="col-sm-6">

                    </div>
                    <div class="col-sm-6">
                        <div class="form-group pull-right">
                            <a href="{{ route('client.products.create') }}" class="btn btn-outline btn-success btn-sm cartButton"><i class="fa fa-shopping-cart"></i> <span class="badge badge-info right productCounter">{{ sizeof($productDetails) }}</span></a>
                        </div>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover toggle-circle default footable-loaded footable" id="products-table">
                        <thead>
                        <tr>
                            <th>@lang('app.id')</th>
                            <th>@lang('app.name')</th>
                            <th>@lang('app.price') (@lang('app.inclusiveAllTaxes'))</th>
                            <th>@lang('app.action')</th>
                        </tr>
                        </thead>
                    </table>
                </div>
                <div class="row">
                    <div class="col-sm-6">

                    </div>
                    <div class="col-sm-6">
                        <div class="form-group pull-right">
                            <a href="{{ route('client.products.create') }}" class="btn btn-outline btn-info btn-sm cartButton"> @lang('app.goToCart') <span class="badge badge-info right productCounter"> {{ sizeof($productDetails) }}</span></a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- .row -->

@endsection

@push('footer-script')
<script src="{{ asset('plugins/bower_components/datatables/jquery.dataTables.min.js') }}"></script>
<script src="https://cdn.datatables.net/1.10.13/js/dataTables.bootstrap.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.1.1/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.1.1/js/responsive.bootstrap.min.js"></script>
<script>
    $(function() {
        var table = $('#products-table').dataTable({
            responsive: true,
            processing: true,
            serverSide: true,
            stateSave: true,
            ajax: '{!! route('client.products.data') !!}',
            language: {
                "url": "<?php echo __("app.datatable") ?>"
            },
            "fnDrawCallback": function( oSettings ) {
                $("body").tooltip({
                    selector: '[data-toggle="tooltip"]'
                });
            },
            columns: [
                { data: 'id', name: 'id' },
                { data: 'name', name: 'name' },
                { data: 'price', name: 'price' },
                { data: 'action', name: 'action' }
            ]
        });
    });

    $('body').on('click', '.add-product', function () {
        let cartItems = [];
        var $this = $(this);
        cartItems  = cartItems.push($this.data('product-id'));

        if(cartItems === 0){
            swal('@lang("modules.booking.addItemsToCart")');
            $('#cart-item-error').html('@lang("modules.booking.addItemsToCart")');
            return false;
        }
        else {
            let url = '{{route('client.products.add-cart-item')}}';

            $.easyAjax({
                url: url,
                container: '#products-table',
                type: "GET",
                data: {'productID': $this.data('product-id')},
                success: function (response) {
                    cartItems = response.productItems;
                    $('.productCounter').html(cartItems.length);
                }
            })
        }

    });



</script>
@endpush