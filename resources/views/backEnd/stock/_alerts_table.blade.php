<div class="table-responsive report-sticky-container">
    <table class="table nowrap w-100 alerts-table">
        <thead>
            <tr>
                <th style="width:2%">SL</th>
                <th style="width:15%">Product</th>
                <th style="width:12%">Warehouse</th>
                <th style="width:12%">Alert Type</th>
                <th style="width:10%">Severity</th>
                <th style="width:10%">Current Qty</th>
                <th style="width:15%">Message</th>
                <th style="width:10%">Status</th>
                <th style="width:10%">Date</th>
                <th style="width:14%">Action</th>
            </tr>
        </thead>
        <tbody>
            @forelse($alerts as $key=>$alert)
            <tr class="alert-row" data-alert-id="{{$alert->id}}">
                <td>{{$alert->id}}</td>
                <td><strong>{{$alert->product->name ?? 'N/A'}}</strong></td>
                <td>{{$alert->warehouse->name ?? 'N/A'}}</td>
                <td>
                    <span class="badge bg-soft-info text-info">
                        {{ucfirst(str_replace('_', ' ', $alert->alert_type))}}
                    </span>
                </td>
                <td>
                    @if($alert->severity == 'critical')
                        <span class="badge bg-danger">Critical</span>
                    @elseif($alert->severity == 'high')
                        <span class="badge bg-warning">High</span>
                    @elseif($alert->severity == 'medium')
                        <span class="badge bg-info">Medium</span>
                    @else
                        <span class="badge bg-secondary">Low</span>
                    @endif
                </td>
                <td><strong>{{number_format($alert->current_quantity, 2)}}</strong></td>
                <td><small>{{$alert->message}}</small></td>
                <td>
                    @if($alert->status == 'active')
                        <span class="badge bg-soft-warning text-warning">Active</span>
                    @else
                        <span class="badge bg-soft-success text-success">Resolved</span>
                    @endif
                </td>
                <td>{{$alert->created_at->format('d M Y')}}</td>
                <td>
                    <div class="d-flex flex-wrap gap-1">
                        @if($alert->status == 'active')
                        <button class="btn btn-sm btn-success resolve-alert-btn" 
                                data-alert-id="{{$alert->id}}" 
                                title="Resolve"
                                type="button">
                            <i class="fe-check"></i> Resolve
                        </button>
                        @endif
                        <a href="{{route('admin.stock.show',[$alert->warehouse_id, $alert->product_id])}}" 
                           class="btn btn-sm btn-info" 
                           title="View Stock">
                            <i class="fe-eye"></i>
                        </a>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="10" class="text-center text-muted py-4">
                    <i class="fe-inbox" style="font-size: 2rem;"></i><br>
                    No alerts found
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
