@extends('backEnd.layouts.master')
@section('title', 'Money Receipt (Print)')

@section('css')
<style>
  @media print {
    header, footer, .left-side-menu, .navbar-custom, .no-print { display: none !important; }
    .container-fluid { padding: 0 !important; }
    .receipt-box { border: 1px solid #ccc; padding: 16px; }
  }
</style>
@endsection

@section('content')
@php
  $amt = ((int)($payment->amount_minor ?? 0) > 0) ? ((int)$payment->amount_minor / 100) : (float)($payment->amount ?? 0);
@endphp

<div class="container-fluid">
  <div class="row no-print">
    <div class="col-12 text-end">
      <button class="btn btn-sm btn-primary" onclick="window.print()">Print</button>
    </div>
  </div>

  <div class="receipt-box">
    <div class="d-flex justify-content-between align-items-start mb-3">
      <div>
        <h4 class="mb-1">Money Receipt</h4>
        <div class="text-muted">MR-{{ $payment->id }}</div>
      </div>
      <div class="text-end">
        <div><strong>Date:</strong> {{ optional($payment->created_at)->format('Y-m-d') }}</div>
        <div><strong>Status:</strong> {{ $payment->payment_status }}</div>
      </div>
    </div>

    <div class="row mb-3">
      <div class="col-md-6">
        <div><strong>Customer:</strong> {{ $payment->customer?->name ?? 'N/A' }}</div>
        <div><strong>Phone:</strong> {{ $payment->customer?->phone ?? '' }}</div>
      </div>
      <div class="col-md-6">
        <div><strong>Invoice:</strong> {{ $payment->order?->invoice_id ?? '' }}</div>
        <div><strong>Method:</strong> {{ $payment->payment_method ?? '' }}</div>
        <div><strong>TRX:</strong> {{ $payment->trx_id ?? '' }}</div>
      </div>
    </div>

    <table class="table table-sm table-bordered mb-0">
      <tr>
        <th class="w-50">Received Amount</th>
        <td class="text-end">BDT {{ number_format($amt, 2) }}</td>
      </tr>
    </table>

    <div class="mt-4 d-flex justify-content-between">
      <div style="width: 45%;">
        <div style="border-top: 1px solid #000; padding-top: 4px;">Received By</div>
      </div>
      <div style="width: 45%;" class="text-end">
        <div style="border-top: 1px solid #000; padding-top: 4px;">Authorized Signature</div>
      </div>
    </div>
  </div>
</div>
@endsection

