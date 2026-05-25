@extends('backEnd.layouts.master')

@section('title', 'Partial Order Details')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4>Partial Order Details</h4>
                    <a href="{{ route('admin.partial-orders.index') }}" class="btn btn-secondary">Back to List</a>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h5>Order Information</h5>
                            <table class="table table-bordered">
                                <tr>
                                    <th>ID</th>
                                    <td>{{ $item->id }}</td>
                                </tr>
                                <tr>
                                    <th>Name</th>
                                    <td>{{ $item->name }}</td>
                                </tr>
                                <tr>
                                    <th>Phone</th>
                                    <td>{{ $item->phone }}</td>
                                </tr>
                                <tr>
                                    <th>Address</th>
                                    <td>{{ $item->address }}</td>
                                </tr>
                                <tr>
                                    <th>Shipping</th>
                                    <td>{{ ($item->meta ?? [])['shipping_area'] ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th>Email</th>
                                    <td>{{ ($item->meta ?? [])['email'] ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th>Status</th>
                                    <td>{{ $item->status }}</td>
                                </tr>
                                <tr>
                                    <th>IP Address</th>
                                    <td>{{ ($item->meta ?? [])['ip'] ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th>Created At</th>
                                    <td>{{ $item->created_at->format('Y-m-d H:i:s') }}</td>
                                </tr>
                                <tr>
                                    <th>Updated At</th>
                                    <td>{{ $item->updated_at->format('Y-m-d H:i:s') }}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h5>Products</h5>
                            @if($item->products)
                                <div class="table-responsive">
                                    <table class="table table-bordered">
                                        <thead>
                                            <tr>
                                                <th>Product ID</th>
                                                <th>Quantity</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($item->products as $product)
                                            <tr>
                                                <td>{{ $product['id'] }}</td>
                                                <td>{{ $product['qty'] }}</td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <p>No products found.</p>
                            @endif
                        </div>
                    </div>

                    @if($item->status == 'incomplete')
                    <div class="row mt-3">
                        <div class="col-12">
                            <button class="btn btn-success convert-btn" data-id="{{ $item->id }}">Convert to Order</button>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('.convert-btn').on('click', function() {
        const id = $(this).data('id');
        if (confirm('Convert this partial order to a full order?')) {
            $.post(`/admin/partial-orders/${id}/convert`, {
                _token: '{{ csrf_token() }}'
            })
            .done(function(response) {
                alert('Converted successfully');
                location.reload();
            })
            .fail(function() {
                alert('Conversion failed');
            });
        }
    });
});
</script>
@endsection
