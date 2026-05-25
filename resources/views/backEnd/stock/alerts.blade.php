@extends('backEnd.layouts.master')
@section('title','Stock Alerts')
@section('content')
<div class="container-fluid">
    
    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <div class="page-title-right">
                    <a href="{{route('admin.stock.balance')}}" class="btn btn-info rounded-pill"><i class="fe-list"></i> Stock Balance</a>
                    <button class="btn btn-secondary rounded-pill" id="clear-filters-btn">
                        <i class="fe-x"></i> Clear Filters
                    </button>
                </div>
                <h4 class="page-title">Stock Alerts</h4>
            </div>
        </div>
    </div>       
    <!-- end page title --> 
   <div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <!-- Filters Section -->
                <div class="row mb-3 alert-filters">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="search-input" class="form-label">Search Product</label>
                            <input type="text" 
                                   id="search-input" 
                                   class="form-control" 
                                   placeholder="Search by product name or SKU"
                                   value="{{request('search')}}">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="alert-type-filter" class="form-label">Alert Type</label>
                            <select id="alert-type-filter" class="form-control">
                                <option value="">All Types</option>
                                <option value="low_stock" {{request('alert_type')=='low_stock'?'selected':''}}>Low Stock</option>
                                <option value="out_of_stock" {{request('alert_type')=='out_of_stock'?'selected':''}}>Out of Stock</option>
                                <option value="expiring_stock" {{request('alert_type')=='expiring_stock'?'selected':''}}>Expiring Stock</option>
                                <option value="dead_stock" {{request('alert_type')=='dead_stock'?'selected':''}}>Dead Stock</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label for="severity-filter" class="form-label">Severity</label>
                            <select id="severity-filter" class="form-control">
                                <option value="">All Severities</option>
                                <option value="critical" {{request('severity')=='critical'?'selected':''}}>Critical</option>
                                <option value="high" {{request('severity')=='high'?'selected':''}}>High</option>
                                <option value="medium" {{request('severity')=='medium'?'selected':''}}>Medium</option>
                                <option value="low" {{request('severity')=='low'?'selected':''}}>Low</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label for="status-filter" class="form-label">Status</label>
                            <select id="status-filter" class="form-control">
                                <option value="active" {{request('status','active')=='active'?'selected':''}}>Active</option>
                                <option value="resolved" {{request('status')=='resolved'?'selected':''}}>Resolved</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label class="form-label">&nbsp;</label>
                            <button class="btn btn-info w-100" id="apply-filters-btn">
                                <i class="fe-filter"></i> Filter
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Loading spinner -->
                <div id="loading-spinner" class="text-center" style="display: none; padding: 20px;">
                    <div class="spinner-border text-primary" role="status">
                        <span class="sr-only">Loading...</span>
                    </div>
                    <p class="mt-2">Loading alerts...</p>
                </div>

                <!-- Results info and alerts container -->
                <div id="alerts-container">
                    @include('backEnd.stock._alerts_table', compact('alerts'))
                </div>

                <!-- Pagination -->
                <div class="custom-paginate" id="pagination-container">
                    {{$alerts->links('pagination::bootstrap-5')}}
                </div>
            </div>
        </div>
    </div>
   </div>
</div>
@endsection

@push('script')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const apiUrl = "{{ route('admin.stock.alerts.data') }}";
    const form = document.querySelector('.alert-filters');
    
    // Get filter values
    function getFilterValues() {
        return {
            search: document.getElementById('search-input').value,
            alert_type: document.getElementById('alert-type-filter').value,
            severity: document.getElementById('severity-filter').value,
            status: document.getElementById('status-filter').value,
            page: 1
        };
    }

    // Load alerts dynamically
    function loadAlerts(filters) {
        const spinner = document.getElementById('loading-spinner');
        const container = document.getElementById('alerts-container');
        
        spinner.style.display = 'block';
        
        fetch(apiUrl + '?' + new URLSearchParams(filters), {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            spinner.style.display = 'none';
            
            if (data.success) {
                container.innerHTML = data.html;
                document.getElementById('pagination-container').innerHTML = data.pagination;
                
                // Re-attach event listeners
                attachEventListeners();
            } else {
                container.innerHTML = `<div class="alert alert-danger">Error: ${data.message}</div>`;
            }
        })
        .catch(error => {
            spinner.style.display = 'none';
            container.innerHTML = `<div class="alert alert-danger">Error loading alerts. Please try again.</div>`;
            console.error('Error:', error);
        });
    }

    // Attach event listeners to resolve buttons and pagination
    function attachEventListeners() {
        // Resolve alert buttons
        document.querySelectorAll('.resolve-alert-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const alertId = this.dataset.alertId;
                resolveAlert(alertId);
            });
        });

        // Pagination links
        document.querySelectorAll('.pagination a').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const url = new URL(this.href);
                const page = url.searchParams.get('page');
                
                const filters = getFilterValues();
                filters.page = page;
                
                loadAlerts(filters);
                // Scroll to top
                document.querySelector('.card-body').scrollIntoView({ behavior: 'smooth' });
            });
        });
    }

    // Resolve an alert
    function resolveAlert(alertId) {
        if (!confirm('Are you sure you want to resolve this alert?')) {
            return;
        }

        const resolveUrl = "{{ route('admin.stock.alerts.resolve', ':id') }}".replace(':id', alertId);
        
        fetch(resolveUrl, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Show success message
                showAlert('Stock alert resolved successfully!', 'success');
                
                // Reload alerts
                setTimeout(() => {
                    loadAlerts(getFilterValues());
                }, 1000);
            } else {
                showAlert(data.message || 'Failed to resolve alert', 'danger');
            }
        })
        .catch(error => {
            showAlert('Error resolving alert. Please try again.', 'danger');
            console.error('Error:', error);
        });
    }

    // Show alert message
    function showAlert(message, type) {
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
        alertDiv.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        document.querySelector('.card-body').insertBefore(alertDiv, document.querySelector('.alert-filters'));
        
        // Auto-dismiss after 3 seconds
        setTimeout(() => {
            alertDiv.remove();
        }, 3000);
    }

    // Event listeners for filter changes
    document.getElementById('apply-filters-btn').addEventListener('click', function() {
        loadAlerts(getFilterValues());
    });

    document.getElementById('clear-filters-btn').addEventListener('click', function() {
        document.getElementById('search-input').value = '';
        document.getElementById('alert-type-filter').value = '';
        document.getElementById('severity-filter').value = '';
        document.getElementById('status-filter').value = 'active';
        loadAlerts(getFilterValues());
    });

    // Real-time search
    let searchTimeout;
    document.getElementById('search-input').addEventListener('keyup', function(e) {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            loadAlerts(getFilterValues());
        }, 500);
    });

    // Auto-load on filter change
    const filterElements = ['alert-type-filter', 'severity-filter', 'status-filter'];
    filterElements.forEach(id => {
        document.getElementById(id).addEventListener('change', function() {
            loadAlerts(getFilterValues());
        });
    });

    // Initial event listener attachment
    attachEventListeners();
});
</script>
@endpush

