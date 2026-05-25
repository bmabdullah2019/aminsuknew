@extends('backEnd.layouts.master')
@section('title','Opening Balances')
@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box"><h4 class="page-title">Opening Balances</h4></div>
        </div>
    </div>
    <div class="card">
        <div class="card-body">
            <form id="openingForm">
                @csrf
                <div class="table-responsive" style="max-height:600px;overflow-y:auto;">
                    <table class="table table-bordered table-sm">
                        <thead class="table-dark sticky-top">
                            <tr><th>Code</th><th>Account Name</th><th>Debit</th><th>Credit</th></tr>
                        </thead>
                        <tbody>
                            @foreach($heads as $h)
                            @php
                                $ob = $openings[$h->HeadId] ?? null;
                            @endphp
                            <tr>
                                <td>{{ $h->HeadCode }}</td>
                                <td>{{ $h->HeadName }}</td>
                                <td>
                                    <input type="hidden" name="HeadId[]" value="{{ $h->HeadId }}">
                                    <input type="number" name="Debit[]" class="form-control form-control-sm debit-input" value="{{ $ob ? number_format($ob->Debit, 2, '.', '') : '0.00' }}" step="0.01" min="0">
                                </td>
                                <td>
                                    <input type="number" name="Credit[]" class="form-control form-control-sm credit-input" value="{{ $ob ? number_format($ob->Credit, 2, '.', '') : '0.00' }}" step="0.01" min="0">
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="sticky-bottom bg-light">
                            <tr class="fw-bold">
                                <td colspan="2" class="text-end">Total:</td>
                                <td id="totalDebit">0.00</td>
                                <td id="totalCredit">0.00</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <div class="mt-3 text-end">
                    <button type="submit" class="btn btn-primary btn-lg"><i class="mdi mdi-content-save"></i> Save Opening Balances</button>
                </div>
            </form>
            <div id="saveMessage" class="mt-3" style="display:none;"></div>
        </div>
    </div>
</div>
@endsection
@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const csrfToken = document.querySelector('input[name="_token"]')?.value;
    function updateTotals() {
        let d = 0, c = 0;
        document.querySelectorAll('.debit-input').forEach(i => d += parseFloat(i.value || 0));
        document.querySelectorAll('.credit-input').forEach(i => c += parseFloat(i.value || 0));
        document.getElementById('totalDebit').textContent = d.toFixed(2);
        document.getElementById('totalCredit').textContent = c.toFixed(2);
    }
    document.querySelectorAll('.debit-input, .credit-input').forEach(i => i.addEventListener('input', updateTotals));
    updateTotals();

    document.getElementById('openingForm').addEventListener('submit', function(e) {
        e.preventDefault();
        fetch("{{ route('admin.accounts.opening-balance.store') }}", {
            method: 'POST', headers: {'X-CSRF-TOKEN': csrfToken}, body: new FormData(this)
        }).then(r => r.json()).then(res => {
            const msg = document.getElementById('saveMessage');
            msg.className = res.hasError ? 'alert alert-danger' : 'alert alert-success';
            msg.textContent = res.message;
            msg.style.display = 'block';
        });
    });
});
</script>
@endsection
