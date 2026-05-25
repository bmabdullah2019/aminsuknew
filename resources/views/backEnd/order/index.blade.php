@extends('backEnd.layouts.master')
@section('title',$order_status->name.' Order')
@section('css')
<style>
.wc-orders-page .wc-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
}

.wc-orders-page .wc-search-wrap {
    margin-top: 10px;
    margin-bottom: 10px;
}

.wc-orders-page .wc-actions .btn {
    border-radius: 0 !important;
}

.wc-orders-page .wc-search-wrap .btn {
    border-radius: 0 !important;
}

.wc-orders-page .button-list.custom-btn-list {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
}

.wc-orders-page .button-list.custom-btn-list a,
.wc-orders-page .button-list.custom-btn-list button {
    border-radius: 0 !important;
}

.wc-orders-page .risk-badge {
    transition: all 0.3s ease;
    text-decoration: none !important;
    position: relative;
    overflow: hidden;
    font-size: 11px;
    font-weight: 700;
    padding: 6px 10px;
}

.wc-orders-page .risk-badge:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    text-decoration: none !important;
}

.wc-orders-page .risk-badge:active {
    transform: translateY(0);
}

#fraudModal .modal-dialog {
    max-width: 540px;
}
</style>
@endsection
@section('content')
<div class="container-fluid wc-orders-page">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <div class="page-title-right">
<a href="{{route('admin.order.create')}}" class="btn btn-danger"><i class="fe-shopping-cart"></i> Add New</a>
                </div>
                <h4 class="page-title">{{$order_status->name}} Order ({{$order_status->orders_count}})</h4>
            </div>
        </div>
    </div>


    <div class="row order_page">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="wc-toolbar">
                        <div class="wc-actions">
                            <a data-bs-toggle="modal" data-bs-target="#asignUser" class="btn btn-success"><i class="fe-user-plus"></i> Assign User</a>
                            <a data-bs-toggle="modal" data-bs-target="#changeStatus" class="btn btn-primary"><i class="fe-refresh-cw"></i> Change Status</a>
                            <a href="{{route('admin.order.bulk_destroy')}}" class="btn btn-danger order_delete" data-url="{{route('admin.order.bulk_destroy')}}"><i class="fe-trash-2"></i> Delete Selected</a>
                            <a href="{{route('admin.order.order_print')}}" class="btn btn-info multi_order_print"><i class="fe-printer"></i> Print</a>
                            @if($steadfast)
                            <a href="{{route('admin.bulk_courier', 'steadfast')}}?status=4" class="btn btn-info multi_order_courier"><i class="fe-truck"></i> Steadfast</a>
                            @endif
                            <a data-bs-toggle="modal" data-bs-target="#pathao" class="btn btn-info"><i class="fe-truck"></i> Pathao</a>
                            <span class="wc-selected-indicator">
                                <i class="fe-check-square"></i> Selected:
                                <strong id="selected-order-count">0</strong>
                            </span>
                        </div>
                        <div class="wc-search-wrap">
                            <form class="wc-search-form" method="GET" action="{{ url()->current() }}">
                                <input type="text" name="keyword" placeholder="Search by invoice or phone" value="{{ request('keyword') }}" aria-label="Search orders">
                                <button class="btn btn-info" type="submit"><i class="fe-search"></i> Search</button>
                            </form>
                        </div>
                    </div>

                    <div class="table-responsive wc-table">
                        <table id="datatable-buttons" class="table table-striped w-100">
                            <thead>
                                <tr>
                                    <th style="width:2%">
                                        <div class="form-check mb-0">
                                            <input type="checkbox" class="form-check-input checkall" value="" aria-label="Select all orders">
                                        </div>
                                    </th>
                                    <th style="width:4%">SL</th>
                                    <th style="width:10%">Action</th>
                                    <th style="width:10%">Invoice</th>
                                    <th style="width:14%">Updated</th>
                                    <th style="width:16%">Customer</th>
                                    <th style="width:10%">Phone</th>
                                    <th style="width:10%">Products</th>
                                    <th style="width:10%">Risk</th>
                                    <th style="width:8%">Amount</th>
                                    <th style="width:10%">Tracking</th>
                                    <th style="width:10%">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($show_data as $value)
                                <tr>
                                    <td><input type="checkbox" class="checkbox form-check-input" value="{{$value->id}}" aria-label="Select order {{$value->invoice_id}}"></td>
                                    <td>{{$loop->iteration}}</td>
                    <td>
                                        <div class="button-list custom-btn-list">
                                            <a href="{{route('admin.order.invoice',['invoice_id'=>$value->invoice_id])}}" title="Invoice"><i class="fe-eye"></i></a>
                                            <a href="{{route('admin.order.process',['invoice_id'=>$value->invoice_id])}}" title="Process"><i class="fe-settings"></i></a>
                                            <a href="{{route('admin.order.edit',['invoice_id'=>$value->invoice_id])}}" title="Edit"><i class="fe-edit"></i></a>
                                            <form method="post" action="{{route('admin.order.destroy')}}" class="d-inline">
                                                @csrf
                                                <input type="hidden" value="{{$value->id}}" name="id">
                                                <button type="submit" title="Delete" class="delete-confirm"><i class="fe-trash-2"></i></button>
                                            </form>
                                        </div>
                                    </td>
                                    <td><span class="fw-semibold">{{$value->invoice_id}}</span></td>
                                    <td>
                                        <div class="fw-semibold">{{ $value->updated_at ? $value->updated_at->format('d M Y') : '' }}</div>
                                        <small class="text-muted">{{ $value->updated_at ? $value->updated_at->format('h:i A') : '' }}</small>
                                    </td>
                                    <td class="wc-order-customer">
                                        <strong>{{$value->shipping?$value->shipping->name:''}}</strong>
                                    </td>
                                    <td>{{$value->shipping?$value->shipping->phone:''}}</td>
                                    <td class="wc-thumbnail-group">
                                        @foreach($value->orderdetails as $detail)
                                        <img src="{{asset($detail->image?$detail->image->image:'')}}" height="30" width="30" alt="" style="margin-right: 5px;">
                                        @endforeach
                                    </td>
                                    <td>
                                        <span class="badge bg-{{$value->fraud_risk_class ?? 'secondary'}} rounded-pill risk-badge"
                                              style="cursor: pointer;"
                                              data-phone="{{$value->shipping?$value->shipping->phone:''}}"
                                              data-order-id="{{$value->id}}"
                                              title="Click for detailed risk analysis">
                                            {{$value->fraud_risk_text ?? 'N/A'}}
                                        </span>
                                    </td>
                                    <td><strong>BDT {{number_format((float) $value->amount, 2)}}</strong></td>
                                    <td>
                                        @if($value->steadfast_tracking_code)
                                        <code style="font-weight:700;color:#667eea;">{{ $value->steadfast_tracking_code }}</code>
                                        <button class="btn btn-outline-primary btn-sm sf-sync-btn mt-1" data-order-id="{{ $value->id }}" style="padding:2px 6px;font-size:0.7rem;border-radius:4px;" title="Sync Steadfast status"><i class="fe-refresh-cw"></i></button>
                                        @if($value->steadfast_status)
                                        <br><small class="text-muted">{{ str_replace('_',' ',$value->steadfast_status) }}</small>
                                        @endif
                                        @else
                                        <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td><span class="wc-status-badge">{{$value->status?$value->status->name:''}}</span></td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="custom-paginate">
                        {{$show_data->links('pagination::bootstrap-4')}}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade wc-modal" id="asignUser" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Assign User</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="{{route('admin.order.assign')}}" id="order_assign" method="POST">
      @csrf
      <div class="modal-body">
        <div class="form-group">
            <select name="user_id" id="user_id" class="form-control">
                <option value="">Select..</option>
                @foreach($users as $value)
                <option value="{{$value->id}}">{{$value->name}}</option>
                @endforeach
            </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <button type="submit" class="btn btn-success">Submit</button>
      </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade wc-modal" id="changeStatus" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Change Status</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="{{route('admin.order.status')}}" id="order_status_form" method="POST">
      @csrf
      <div class="modal-body">
        <div class="form-group">
            <select name="order_status" id="order_status" class="form-control">
                <option value="">Select..</option>
                @foreach($orderstatus as $value)
                <option value="{{$value->id}}">{{$value->name}}</option>
                @endforeach
            </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <button type="submit" class="btn btn-success">Submit</button>
      </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade wc-modal" id="pathao" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Pathao Courier</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="{{route('admin.order.pathao')}}" id="order_sendto_pathao">

      <div class="modal-body">
        <div class="form-group">
            <label for="pathaostore" class="form-label">Store</label>
            <select name="pathaostore" id="pathaostore" class="pathaostore form-control">
                <option value="">Select Store...</option>
                @if(isset($pathaostore['data']['data']))
                @foreach($pathaostore['data']['data'] as $store)
                <option value="{{$store['store_id']}}">{{$store['store_name']}}</option>
                @endforeach
                @endif
            </select>
            @if ($errors->has('pathaostore'))
              <span class="invalid-feedback" role="alert">
                <strong>{{ $errors->first('pathaostore') }}</strong>
              </span>
            @endif
        </div>
        <div class="form-group mt-3">
          <label for="pathaocity" class="form-label">City</label>
           <select name="pathaocity" id="pathaocity" class="chosen-select pathaocity form-control" style="width:100%">
             <option value="">Select City...</option>
             @if(isset($pathaocities['data']['data']))
             @foreach($pathaocities['data']['data'] as $city)
             <option value="{{$city['city_id']}}">{{$city['city_name']}}</option>
             @endforeach
             @endif
           </select>
            @if ($errors->has('pathaocity'))
              <span class="invalid-feedback" role="alert">
                <strong>{{ $errors->first('pathaocity') }}</strong>
              </span>
            @endif
        </div>
        <div class="form-group mt-3">
          <label for="pathaozone" class="form-label">Zone</label>
          <select name="pathaozone" id="pathaozone" class="pathaozone chosen-select form-control {{ $errors->has('pathaozone') ? ' is-invalid' : '' }}" style="width:100%"></select>
          @if ($errors->has('pathaozone'))
            <span class="invalid-feedback" role="alert">
              <strong>{{ $errors->first('pathaozone') }}</strong>
            </span>
          @endif
        </div>
        <div class="form-group mt-3">
          <label for="pathaoarea" class="form-label">Area</label>
          <select name="pathaoarea" id="pathaoarea" class="pathaoarea chosen-select form-control {{ $errors->has('pathaoarea') ? ' is-invalid' : '' }}" style="width:100%"></select>
          @if ($errors->has('pathaoarea'))
            <span class="invalid-feedback" role="alert">
              <strong>{{ $errors->first('pathaoarea') }}</strong>
            </span>
          @endif
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <button type="submit" class="btn btn-success">Submit</button>
      </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade wc-modal" id="fraudModal" tabindex="-1" aria-labelledby="fraudModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title" id="fraudModalLabel">
          <i class="fe-shield me-2"></i>Detailed Fraud Risk Analysis
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" id="fraudModalBody">
        <div class="text-center">
          <i class="fa fa-spinner fa-spin fa-2x"></i><br>
          Loading fraud analysis...
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

@endsection

@section('script')
<script>
$(document).ready(function(){
    const fraudDetailedRoute = @json(route('admin.fraud-checker.detailed'));

    function selectedCount() {
      return $('input.checkbox:checked').length;
    }

    function updateSelectedCount() {
      $('#selected-order-count').text(selectedCount());
    }

    function setBulkLoadingState(isLoading) {
      $('.wc-actions').toggleClass('wc-bulk-action-loading', isLoading);
    }

    $(".checkall").on('change',function(){
      $(".checkbox").prop('checked',$(this).is(":checked"));
      updateSelectedCount();
    });

    $(document).on('change', '.checkbox', function(){
      updateSelectedCount();
    });

    $(document).on('submit', 'form#order_assign', function(e){
        e.preventDefault();
        setBulkLoadingState(true);
        var url = $(this).attr('action');
        var csrfToken = $(this).find('input[name="_token"]').val();
        let user_id=$(document).find('select#user_id').val();

        var order = $('input.checkbox:checked').map(function(){
          return $(this).val();
        });
        var order_ids=order.get();

        if(order_ids.length ==0){
            toastr.error('Please Select An Order First !');
            setBulkLoadingState(false);
            return ;
        }

        $.ajax({
           type:'POST',
           url:url,
           data:{user_id,order_ids,_token:csrfToken},
           success:function(res){
               if(res.status=='success'){
                toastr.success(res.message);
                window.location.reload();

            }else{
                toastr.error('Failed something wrong');
                setBulkLoadingState(false);
            }
           },
           error:function(xhr) {
             var errMsg = 'Failed something wrong';
             if (xhr && xhr.responseJSON) {
                 if (xhr.responseJSON.errors) {
                     errMsg = Object.values(xhr.responseJSON.errors)[0][0];
                 } else if (xhr.responseJSON.message) {
                     errMsg = xhr.responseJSON.message;
                 }
             }
             toastr.error(errMsg);
             setBulkLoadingState(false);
           }
        });
    });

    $(document).on('submit', 'form#order_status_form', function(e){
        e.preventDefault();
        setBulkLoadingState(true);
        var url = $(this).attr('action');
        var csrfToken = $(this).find('input[name="_token"]').val();
        let order_status=$(document).find('select#order_status').val();

        var order = $('input.checkbox:checked').map(function(){
          return $(this).val();
        });
        var order_ids=order.get();

        if(order_ids.length ==0){
            toastr.error('Please Select An Order First !');
            setBulkLoadingState(false);
            return ;
        }

        $.ajax({
           type:'POST',
           url:url,
           data:{order_status,order_ids,_token:csrfToken},
           success:function(res){
               if(res.status=='success'){
                toastr.success(res.message);
                window.location.reload();

            }else{
                toastr.error('Failed something wrong');
                setBulkLoadingState(false);
            }
           },
           error:function(xhr) {
             var errMsg = 'Failed something wrong';
             if (xhr && xhr.responseJSON) {
                 if (xhr.responseJSON.errors) {
                     errMsg = Object.values(xhr.responseJSON.errors)[0][0];
                 } else if (xhr.responseJSON.message) {
                     errMsg = xhr.responseJSON.message;
                 }
             }
             toastr.error(errMsg);
             setBulkLoadingState(false);
           }
        });
    });

    $(document).on('click', '.order_delete', function(e){
        e.preventDefault();
        setBulkLoadingState(true);
        var url = $(this).data('url') || $(this).attr('href');
        var csrfToken = $('meta[name="csrf-token"]').attr('content');
        var order = $('input.checkbox:checked').map(function(){
          return $(this).val();
        });
        var order_ids=order.get();

        if(order_ids.length ==0){
            toastr.error('Please Select An Order First !');
            setBulkLoadingState(false);
            return ;
        }

        $.ajax({
           type:'POST',
           url:url,
           data:{order_ids,_method:'DELETE',_token:csrfToken},
           success:function(res){
               if(res.status=='success'){
                toastr.success(res.message);
                window.location.reload();

            }else{
                toastr.error('Failed something wrong');
                setBulkLoadingState(false);
            }
           },
           error:function(xhr) {
             var errMsg = 'Failed something wrong';
             if (xhr && xhr.responseJSON) {
                 if (xhr.responseJSON.errors) {
                     errMsg = Object.values(xhr.responseJSON.errors)[0][0];
                 } else if (xhr.responseJSON.message) {
                     errMsg = xhr.responseJSON.message;
                 }
             }
             toastr.error(errMsg);
             setBulkLoadingState(false);
           }
        });
    });

    $(document).on('click', '.multi_order_print', function(e){
        e.preventDefault();
        var url = $(this).attr('href');
        var order = $('input.checkbox:checked').map(function(){
          return $(this).val();
        });
        var order_ids=order.get();

        if(order_ids.length ==0){
            toastr.error('Please Select Atleast One Order!');
            return ;
        }
        $.ajax({
           type:'GET',
           url,
           data:{order_ids},
           success:function(res){
               if(res.status=='success'){
                    var myWindow = window.open("", "_blank");
                    myWindow.document.write(res.view);
            }else{
                toastr.error('Failed something wrong');
            }
           }
        });
    });

    $(document).on('click', '.multi_order_courier', function(e){
        e.preventDefault();
        setBulkLoadingState(true);
        var url = $(this).attr('href');
        var order = $('input.checkbox:checked').map(function(){
          return $(this).val();
        });
        var order_ids=order.get();

        if(order_ids.length ==0){
            toastr.error('Please Select An Order First !');
            setBulkLoadingState(false);
            return ;
        }

        $.ajax({
           type:'GET',
           url:url,
           data:{order_ids},
           success:function(res){
               if(res.status=='success'){
                    toastr.success(res.message);
                    if(res.success){ toastr.success(res.success); }
                    if(res.failed){ toastr.error(res.failed); }
                    window.location.reload();

            }else{
                toastr.error('Failed something wrong');
                setBulkLoadingState(false);
            }
           },
           error:function(xhr) {
             var errMsg = 'Failed something wrong';
             if (xhr && xhr.responseJSON) {
                 if (xhr.responseJSON.errors) {
                     errMsg = Object.values(xhr.responseJSON.errors)[0][0];
                 } else if (xhr.responseJSON.message) {
                     errMsg = xhr.responseJSON.message;
                 }
             }
             toastr.error(errMsg);
             setBulkLoadingState(false);
           }
        });
    });

    function updateRiskBadge($badge, response) {
        var basicRisk = response && response.data && response.data.basic_risk ? response.data.basic_risk : null;
        if (!basicRisk) {
            return;
        }

        var badgeClass = basicRisk.badge_class || 'secondary';
        var riskText = basicRisk.text || 'Unknown';
        var riskScore = parseInt(basicRisk.score, 10);
        var formattedText = isNaN(riskScore) ? riskText : (riskText + ' (' + riskScore + '%)');

        $badge
            .removeClass(function(index, className) {
                var currentClass = (typeof className === 'string') ? className : '';
                return (currentClass.match(/(^|\s)bg-\S+/g) || []).join(' ');
            })
            .addClass('bg-' + badgeClass)
            .text(formattedText)
            .attr('title', 'Click for detailed risk analysis');
    }

    function showFraudModal() {
        var modalEl = document.getElementById('fraudModal');
        if (window.bootstrap && window.bootstrap.Modal) {
            var modalInstance = (typeof window.bootstrap.Modal.getInstance === 'function')
                ? (window.bootstrap.Modal.getInstance(modalEl) || new window.bootstrap.Modal(modalEl))
                : new window.bootstrap.Modal(modalEl);
            modalInstance.show();
            return;
        }
        if (typeof window.jQuery !== 'undefined' && typeof window.jQuery.fn.modal === 'function') {
            $('#fraudModal').modal('show');
        }
    }

    $(document).on('click', '.risk-badge', function() {
        var $badge = $(this);
        var phone = $badge.data('phone') || '';
        var orderId = $badge.data('order-id');

        if (!phone && !orderId) {
            toastr.error('No phone number available for this order');
            return;
        }

        showFraudModal();
        $('#fraudModalBody').html('<div class="text-center"><i class="fa fa-spinner fa-spin fa-2x"></i><br>Loading fraud analysis...</div>');

        function renderFallbackRequest(errorMessage) {
            $.ajax({
                url: fraudDetailedRoute,
                method: 'GET',
                timeout: 30000,
                data: {
                    phone: phone,
                    order_id: orderId,
                    force_fresh: 0
                },
                success: function(response) {
                    if (response.success) {
                        updateRiskBadge($badge, response);
                        var warningHtml = '<div class="alert alert-warning mb-3">' + errorMessage + ' Showing cached fraud data.</div>';
                        $('#fraudModalBody').html(warningHtml + response.html);
                    } else {
                        $('#fraudModalBody').html('<div class="alert alert-warning">' + response.message + '</div>');
                    }
                },
                error: function() {
                    $('#fraudModalBody').html('<div class="alert alert-danger">' + errorMessage + '</div>');
                }
            });
        }

        $.ajax({
            url: fraudDetailedRoute,
            method: 'GET',
            timeout: 45000,
            data: {
                phone: phone,
                order_id: orderId,
                force_fresh: 1
            },
            success: function(response) {
                if (response.success) {
                    updateRiskBadge($badge, response);
                    $('#fraudModalBody').html(response.html);
                } else {
                    $('#fraudModalBody').html('<div class="alert alert-warning">' + response.message + '</div>');
                }
            },
            error: function(xhr) {
                var serverMessage = xhr && xhr.responseJSON && xhr.responseJSON.message
                    ? xhr.responseJSON.message
                    : null;

                if (xhr && xhr.status === 403) {
                    toastr.error(serverMessage || 'Permission denied for risk details');
                    $('#fraudModalBody').html('<div class="alert alert-danger">' + (serverMessage || 'Permission denied for risk details') + '</div>');
                } else if (xhr && xhr.status === 0) {
                    toastr.error('Live fraud check network error, trying cached data');
                    renderFallbackRequest('Live fraud check failed due to network error.');
                } else {
                    toastr.error(serverMessage || 'Live fraud check failed, trying cached data');
                    renderFallbackRequest(serverMessage || 'Live fraud check failed.');
                }
            }
        });
    });

    updateSelectedCount();

    // Steadfast status sync per-order
    $(document).on('click', '.sf-sync-btn', function(e){
        e.preventDefault();
        var $btn = $(this);
        var orderId = $btn.data('order-id');
        $btn.prop('disabled', true).html('<i class="fe-loader"></i>');
        $.ajax({
            url: @json(route('admin.steadfast.sync-status')),
            method: 'POST',
            data: { order_id: orderId, _token: $('meta[name="csrf-token"]').attr('content') },
            success: function(res) {
                if (res.success) {
                    toastr.success(res.message);
                    var $td = $btn.closest('td');
                    $td.find('small').text(res.delivery_status.replace(/_/g, ' '));
                } else {
                    toastr.error(res.message || 'Sync failed');
                }
            },
            error: function() { toastr.error('Sync request failed'); },
            complete: function() { $btn.prop('disabled', false).html('<i class="fe-refresh-cw"></i>'); }
        });
    });
});
</script>
@endsection
