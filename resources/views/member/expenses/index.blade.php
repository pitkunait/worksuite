@extends('layouts.member-app')

@section('page-title')
    <div class="row bg-title">
        <!-- .page title -->
        <div class="col-lg-3 col-md-4 col-sm-4 col-xs-12">
            <h4 class="page-title"><i class="{{ $pageIcon }}"></i> @lang($pageTitle)</h4>
        </div>
        <!-- /.page title -->
        <!-- .breadcrumb -->
        <div class="col-lg-9 col-sm-8 col-md-8 col-xs-12 text-right">
            <a href="{{ route('member.expenses.create') }}" class="btn btn-outline btn-success btn-sm">@lang('modules.expenses.addExpense') <i class="fa fa-plus" aria-hidden="true"></i></a>

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
<link rel="stylesheet" href="{{ asset('plugins/bower_components/bootstrap-select/bootstrap-select.min.css') }}">
<link rel="stylesheet" href="{{ asset('plugins/bower_components/bootstrap-datepicker/bootstrap-datepicker.min.css') }}">
<style>
    select.bs-select-hidden, select.selectpicker {
        display: block!important;
    }
</style>
@endpush

@section('content')

    <div class="row">
        <div class="col-md-12">
            <div class="white-box">
        
                @section('filter-section')                    
                    <div class="row"  id="ticket-filters">
               
                        <form action="" id="filter-form">
                            <div class="col-md-12">
                                <h5 >@lang('app.selectDateRange')</h5>
                                <div class="input-daterange input-group" id="date-range">
                                    <input type="text" class="form-control"  autocomplete="off" id="start-date" placeholder="@lang('app.startDate')"
                                        value=""/>
                                    <span class="input-group-addon bg-info b-0 text-white">@lang('app.to')</span>
                                    <input type="text" class="form-control" autocomplete="off" id="end-date" placeholder="@lang('app.endDate')"
                                        value=""/>
                                </div>
                            </div>

                            <div class="col-md-12">
                                <h5 >@lang('app.status')</h5>
                                <div class="form-group">
                                    <select class="form-control selectpicker" name="status" id="status" data-style="form-control">
                                        <option value="all">@lang('app.all')</option>
                                        <option value="pending">@lang('app.pending')</option>
                                        <option value="approved">@lang('app.approved')</option>
                                        <option value="rejected">@lang('app.reject')</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="form-group m-t-10">
                                    <button type="button" id="apply-filters" class="btn btn-success col-md-6"><i class="fa fa-check"></i> @lang('app.apply')</button>
                                    <button type="button" id="reset-filters" class="btn btn-inverse col-md-5 col-md-offset-1"><i class="fa fa-refresh"></i> @lang('app.reset')</button>
                                </div>
                            </div>
                        </form>
                    </div>
                @endsection

                <div class="table-responsive">
                    <table class="table table-bordered table-hover toggle-circle default footable-loaded footable" id="invoice-table">
                        <thead>
                        <tr>
                            <th>#</th>
                            @if($user->can('view_expenses'))
                                <th>@lang('modules.employees.title')</th>
                            @endif
                            <th>@lang('modules.expenses.itemName')</th>
                            <th>@lang('app.price')</th>
                            <th>@lang('modules.expenses.purchaseDate')</th>
                            <th>@lang('app.status')</th>
                            <th>@lang('app.action')</th>
                        </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <!-- .row -->

@endsection

@push('footer-script')
<script src="{{ asset('plugins/bower_components/datatables/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('js/datatables/dataTables.bootstrap.min.js') }}"></script>
<script src="{{ asset('js/datatables/dataTables.responsive.min.js') }}"></script>
<script src="{{ asset('js/datatables/responsive.bootstrap.min.js') }}"></script>
<script src="{{ asset('js/datatables/responsive.bootstrap.min.js') }}"></script><script src="{{ asset('plugins/bower_components/bootstrap-datepicker/bootstrap-datepicker.min.js') }}"></script>
@if($global->locale == 'en')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.7.1/locales/bootstrap-datepicker.{{ $global->locale }}-AU.min.js"></script>
@elseif($global->locale == 'pt-br')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.7.1/locales/bootstrap-datepicker.pt-BR.min.js"></script>
@else
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.7.1/locales/bootstrap-datepicker.{{ $global->locale }}.min.js"></script>
@endif
<script src="{{ asset('plugins/bower_components/bootstrap-daterangepicker/daterangepicker.js') }}"></script>
<script>
    var table;
    $(function() {
        jQuery('#date-range').datepicker({
            toggleActive: true,
            format: '{{ $global->date_picker_format }}',
            language: '{{ $global->locale }}',
            autoclose: true
        });
        loadTable();

        $('body').on('click', '.sa-params', function(){
            var id = $(this).data('expense-id');
            swal({
                title: "@lang('messages.sweetAlertTitle')",
                text: "@lang('messages.expenseText')",
                type: "warning",
                showCancelButton: true,
                confirmButtonColor: "#DD6B55",
                confirmButtonText: "@lang('messages.confirmDelete')",
                cancelButtonText: "@lang('messages.confirmNoArchive')",
                closeOnConfirm: true,
                closeOnCancel: true
            }, function(isConfirm){
                if (isConfirm) {

                    var url = "{{ route('member.expenses.destroy',':id') }}";
                    url = url.replace(':id', id);

                    var token = "{{ csrf_token() }}";

                    $.easyAjax({
                        type: 'POST',
                            url: url,
                            data: {'_token': token, '_method': 'DELETE'},
                        success: function (response) {
                            if (response.status == "success") {
                                $.unblockUI();
//                                    swal("Deleted!", response.message, "success");
                                table._fnDraw();
                            }
                        }
                    });
                }
            });
        });



    });
    function loadTable(){

        var startDate = $('#start-date').val();

        if (startDate == '') {
            startDate = null;
        }

        var endDate = $('#end-date').val();

        if (endDate == '') {
            endDate = null;
        }

        var status = $('#status').val();
        var employee = $('#employee').val();

        table = $('#invoice-table').dataTable({
            responsive: true,
            processing: true,
            serverSide: true,
            destroy: true,
            ajax: '{!! route('member.expenses.data') !!}?startDate=' + encodeURIComponent(startDate) + '&endDate=' + encodeURIComponent(endDate) + '&status=' + status + '&employee=' + employee,
            "order": [[ 0, "desc" ]],
            language: {
                "url": "<?php echo __("app.datatable") ?>"
            },
            "fnDrawCallback": function( oSettings ) {
                $("body").tooltip({
                    selector: '[data-toggle="tooltip"]'
                });
            },
            columns: [
                { data: 'DT_RowIndex', orderable: false, searchable: false },
                @if($user->can('view_expenses'))
                    { data: 'user_id', name: 'user_id', searchable: false },
                @endif
                { data: 'item_name', name: 'item_name' },
                { data: 'price', name: 'price' },
                { data: 'purchase_date', name: 'purchase_date' },
                { data: 'status', name: 'status' },
                { data: 'action', name: 'action', width: '20%' }
            ]
        });
    }

    $('.toggle-filter').click(function () {
        $('#ticket-filters').toggle('slide');
    })

    $('#apply-filters').click(function () {
        loadTable();
    });

    $('#reset-filters').click(function () {
        $('#filter-form')[0].reset();
        loadTable();
    })
</script>
@endpush