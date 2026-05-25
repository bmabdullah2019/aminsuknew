@extends('backEnd.layouts.master')

@section('title', 'Partial Orders')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4>Partial Orders</h4>
                    <div class="card-header-actions">
                        <button id="bulk-delete-btn" class="btn btn-danger btn-sm" style="display: none;">Delete Selected</button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Select</th>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Phone</th>
                                    <th>Products</th>
                                    <th>IP</th>
                                    <th>Updated At</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($items as $partial)
                                <tr>
                                    <td><input type="checkbox" class="row-checkbox" value="{{ $partial->id }}"></td>
                                    <td>{{ $partial->id }}</td>
                                    <td>{{ $partial->name }}</td>
                                    <td>{{ $partial->phone }}</td>
                                    <td>
                                        @if($partial->products)
                                            @foreach($partial->products as $product)
                                                ID: {{ $product['id'] }} (Qty: {{ $product['qty'] }})
                                            @endforeach
                                        @endif
                                    </td>
                                    <td>{{ ($partial->meta ?? [])['ip'] ?? '' }}</td>
                                    <td>{{ $partial->updated_at->format('Y-m-d H:i') }}</td>
                                    <td>
                                        <a href="{{ route('admin.partial-orders.show', $partial->id) }}" class="btn btn-sm btn-info">View</a>
                                        <button class="btn btn-sm btn-success convert-btn" data-id="{{ $partial->id }}">Convert to Order</button>
                                        <form action="{{ route('admin.partial-orders.delete', $partial->id) }}" method="POST" style="display: inline;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this partial order?')">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    {{ $items->links() }}
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

    // Select all checkbox
    $('#select-all').on('change', function() {
        $('.row-checkbox').prop('checked', $(this).prop('checked'));
        toggleBulkDeleteButton();
    });

    // Individual checkboxes
    $(document).on('change', '.row-checkbox', function() {
        toggleBulkDeleteButton();
    });

    function toggleBulkDeleteButton() {
        const checkedBoxes = $('.row-checkbox:checked');
        if (checkedBoxes.length > 0) {
            $('#bulk-delete-btn').show();
        } else {
            $('#bulk-delete-btn').hide();
        }
    }

    // Bulk delete
    $('#bulk-delete-btn').on('click', function() {
        const selectedIds = $('.row-checkbox:checked').map(function() {
            return $(this).val();
        }).get();

        if (selectedIds.length === 0) {
            alert('Please select at least one partial order to delete.');
            return;
        }

        if (confirm('Are you sure you want to delete the selected partial orders?')) {
            $.ajax({
                url: '{{ route("admin.partial-orders.bulk-delete") }}',
                method: 'POST',
                data: {
                    ids: selectedIds,
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    if (response.success) {
                        alert(response.message);
                        location.reload();
                    } else {
                        alert(response.message);
                    }
                },
                error: function() {
                    alert('An error occurred while deleting the partial orders.');
                }
            });
        }
    });
});
</script>
@endsection
