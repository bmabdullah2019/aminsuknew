@extends('backEnd.layouts.master')
@section('title','Supplier Reports')
@section('content')
<div class="container-fluid">

    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <h4 class="page-title">Supplier Reports</h4>
            </div>
        </div>
    </div>
    <!-- end page title -->

    <div class="row">
        <!-- Report Types -->
        <div class="col-lg-3">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Report Types</h5>
                    <div class="list-group">
                        <a href="{{route('admin.supplier.reports', ['type' => 'aging'])}}"
                           class="list-group-item list-group-item-action {{ ($reportType ?? 'aging') == 'aging' ? 'active' : '' }}">
                            <i class="fe-calendar me-2"></i>Aging Report
                        </a>
                        <a href="{{route('admin.supplier.reports', ['type' => 'dues'])}}"
                           class="list-group-item list-group-item-action {{ ($reportType ?? 'aging') == 'dues' ? 'active' : '' }}">
                            <i class="fe-alert-triangle me-2"></i>Supplier Dues
                        </a>
                        <a href="{{route('admin.supplier.reports', ['type' => 'performance'])}}"
                           class="list-group-item list-group-item-action {{ ($reportType ?? 'aging') == 'performance' ? 'active' : '' }}">
                            <i class="fe-star me-2"></i>Performance Metrics
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Report Content -->
        <div class="col-lg-9">
            @switch($reportType ?? 'aging')
                @case('aging')
                    @include('backEnd.supplier.reports.aging')
                    @break

                @case('dues')
                    @include('backEnd.supplier.reports.dues')
                    @break

                @case('performance')
                    @include('backEnd.supplier.reports.performance')
                    @break

                @default
                    @include('backEnd.supplier.reports.aging')
            @endswitch
        </div>
    </div>
</div>
@endsection
