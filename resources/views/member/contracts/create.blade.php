@extends('layouts.member-app')
@push('head-script')
    <link rel="stylesheet" href="{{ asset('plugins/bower_components/bootstrap-datepicker/bootstrap-datepicker.min.css') }}">
    <link rel="stylesheet" href="{{ asset('plugins/bower_components/bootstrap-select/bootstrap-select.min.css') }}">
    <link rel="stylesheet" href="{{ asset('plugins/bower_components/custom-select/custom-select.css') }}">
@endpush
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
                <li><a href="{{ route('member.contracts.index') }}">@lang($pageTitle)</a></li>
                <li class="active">@lang('app.addNew')</li>
            </ol>
        </div>
        <!-- /.breadcrumb -->
    </div>
@endsection

@section('content')

    <div class="row">
        <div class="col-md-12">
            <div class="panel panel-inverse">
                <div class="panel-heading"> @lang('app.add') @lang('app.menu.contract')</div>

                <div class="panel-wrapper collapse in" aria-expanded="true">
                    <div class="panel-body">
                        @if($clients->isEmpty())
                        <div class="text-center">

                            <div class="empty-space" style="height: 200px;">
                                <div class="empty-space-inner">
                                    <div class="icon" style="font-size:30px"><i
                                                class="fa fa-user-secret"></i>
                                    </div>
                                    <div class="title m-b-15">@lang('messages.noClientFound')</div>
                                    <div class="subtitle">
                                        <a href="{{ route('member.clients.create') }}"
                                           class="btn btn-outline btn-success btn-sm">@lang('modules.client.addNewClient') <i class="fa fa-plus"
                                                                                                                              aria-hidden="true"></i></a>

                                    </div>
                                </div>
                            </div>

                        </div>
                        @else
                        {!! Form::open(['id'=>'createContract','class'=>'ajax-form','method'=>'POST']) !!}
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="company_name" class="required">@lang('app.client')</label>
                                    <div>
                                        <select class="select2 form-control" data-placeholder="@lang('app.client')" name="client" id="clientID">
                                            @foreach($clients as $client)
                                                <option
                                                        value="{{ $client->id }}">{{ ucwords($client->name) }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="required" for="subject">@lang('app.subject')</label>
                                    <input type="text" class="form-control" id="subject" name="subject">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="required" for="subject">@lang('app.amount') ({{ $global->currency->currency_symbol }})</label>
                                    <input type="number" min="0" class="form-control" id="amount" name="amount">
                                </div>

                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="required" class="control-label">@lang('modules.contracts.contractType')
                                        <a href="javascript:;"
                                        id="createContractType"
                                        class="btn btn-xs btn-outline btn-success">
                                            <i class="fa fa-plus"></i> @lang('modules.contracts.addContractType')
                                        </a>
                                    </label>
                                    <div>
                                        <select class="select2 form-control" data-placeholder="@lang('app.client')" id="contractType" name="contract_type">
                                            @foreach($contractType as $type)
                                                <option
                                                        value="{{ $type->id }}">{{ ucwords($type->name) }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="required">@lang('modules.timeLogs.startDate')</label>
                                    <input id="start_date" name="start_date" type="text"
                                        class="form-control"
                                        value="{{ \Carbon\Carbon::today()->format($global->date_format) }}">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="required">@lang('modules.timeLogs.endDate')</label>
                                    <input id="end_date" name="end_date" type="text"
                                        class="form-control"
                                        value="{{ \Carbon\Carbon::today()->format($global->date_format) }}">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label>@lang('modules.contracts.notes')</label>
                                    <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                                </div>
                            </div>
                        </div>
                        <button type="submit" id="save-form" class="btn btn-success waves-effect waves-light m-r-10">
                            @lang('app.save')
                        </button>
                        <button type="reset" class="btn btn-inverse waves-effect waves-light">@lang('app.reset')</button>
                         </div>

                    @endif
                </div>
            </div>
        </div>
    </div>
    <!-- .row -->
    {{--Ajax Modal--}}
    <div class="modal fade bs-modal-md in" id="taskCategoryModal" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-md" id="modal-data-application">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true"></button>
                    <span class="caption-subject font-red-sunglo bold uppercase" id="modelHeading"></span>
                </div>
                <div class="modal-body">
                    Loading...
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn default" data-dismiss="modal">Close</button>
                    <button type="button" class="btn blue">Save changes</button>
                </div>
            </div>
            <!-- /.modal-content -->
        </div>
        <!-- /.modal-dialog -->.
    </div>
    {{--Ajax Modal Ends--}}
@endsection

@push('footer-script')
    <script src="{{ asset('plugins/bower_components/bootstrap-datepicker/bootstrap-datepicker.min.js') }}"></script>
    <script src="{{ asset('plugins/bower_components/custom-select/custom-select.min.js') }}"></script>
    <script src="{{ asset('plugins/bower_components/bootstrap-select/bootstrap-select.min.js') }}"></script>
    <script>
        $(".select2").select2({
            formatNoMatches: function () {
                return "{{ __('messages.noRecordFound') }}";
            }
        });
        $("#start_date").datepicker({
            format: '{{ $global->date_picker_format }}',
            todayHighlight: true,
            autoclose: true,
        }).on('changeDate', function (selected) {
            $('#end_date').datepicker({
                format: '{{ $global->date_picker_format }}',
                autoclose: true,
                todayHighlight: true
            });
            var minDate = new Date(selected.date.valueOf());
            $('#end_date').datepicker("update", minDate);
            $('#end_date').datepicker('setStartDate', minDate);
        });
        jQuery('#start_date, #end_date').datepicker({
            autoclose: true,
            todayHighlight: true,
            format: '{{ $global->date_picker_format }}',
        });
        $('#save-form').click(function () {
            $.easyAjax({
                url: '{{route('member.contracts.store')}}',
                container: '#createContract',
                type: "POST",
                redirect: true,
                data: $('#createContract').serialize()
            })
        });
        $('#createContractType').click(function(){
            var url = '{{ route('member.contract-type.create-contract-type')}}';
            $('#modelHeading').html("@lang('modules.contracts.manageContractType')");
            $.ajaxModal('#taskCategoryModal', url);
        })
    </script>
@endpush

