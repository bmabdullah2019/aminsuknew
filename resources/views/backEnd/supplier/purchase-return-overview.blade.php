@extends('backEnd.layouts.master')
@section('title', 'Purchase Return')

@section('css')
<style>
    .procure-box{border:1px solid #dfe6ef;background:#fff}
    .procure-box .card-body{padding:1rem}
    .procure-modal{position:fixed;inset:0;display:none;align-items:center;justify-content:center;background:rgba(15,23,42,.38);z-index:1055;padding:24px}
    .procure-modal.show{display:flex}
    .procure-modal-dialog{width:min(1080px,100%);max-height:calc(100vh - 48px);overflow:auto}
    .procure-modal-content{background:#fff;border-radius:10px;overflow:hidden}
    .procure-modal-header{display:flex;align-items:center;justify-content:space-between;padding:.9rem 1.2rem;border-bottom:2px solid #f15a24}
    .procure-modal-body{padding:1rem}
    .procure-modal-footer{display:flex;justify-content:flex-end;gap:.5rem;padding:1rem;background:#edf3fb;border-top:1px solid #d7e2ef}
    .procure-close{background:none;border:0;font-size:1.8rem;line-height:1;color:#9aa4b2}
    .compact-label{font-size:.85rem;font-weight:600;margin-bottom:.35rem}
    .return-items-table th,.return-items-table td{vertical-align:middle;font-size:.85rem}
    .return-items-table .item-select{min-width:280px}
    .return-summary{background:#f8fbff;border:1px solid #d7e2ef;border-radius:8px;padding:.9rem}
    .summary-line{display:flex;justify-content:space-between;gap:1rem}
    .summary-line + .summary-line{margin-top:.4rem}
    .summary-line.total{padding-top:.45rem;border-top:1px solid #d7e2ef;font-weight:700}
    .modal-message ul{margin:0;padding-left:1rem}
</style>
@endsection

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <div class="page-title-right">
                    <button type="button" class="btn btn-danger" id="btnOpenReturnModal"><i class="mdi mdi-plus"></i> New Purchase Return</button>
                </div>
                <h4 class="page-title">Purchase Return</h4>
            </div>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-md-4"><div class="procure-box"><div class="card-body py-2"><small>Total Returns</small><h5 class="mb-0">{{ number_format($summary['total_rows'] ?? 0) }}</h5></div></div></div>
        <div class="col-md-4"><div class="procure-box"><div class="card-body py-2"><small>Gross Value</small><h5 class="mb-0">BDT {{ number_format($summary['gross_value'] ?? 0, 2) }}</h5></div></div></div>
        <div class="col-md-4"><div class="procure-box"><div class="card-body py-2"><small>Effective Value</small><h5 class="mb-0">BDT {{ number_format($summary['effective_value'] ?? 0, 2) }}</h5></div></div></div>
    </div>

    <div class="card procure-box">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end mb-3">
                <div class="col-md-3">
                    <label class="form-label">Supplier</label>
                    <select name="supplier_id" class="form-select">
                        <option value="">All</option>
                        @foreach($suppliers as $supplier)
                            <option value="{{ $supplier->id }}" {{ (string) request('supplier_id') === (string) $supplier->id ? 'selected' : '' }}>{{ $supplier->supplier_code }} - {{ $supplier->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Branch</label>
                    <select name="branch_id" class="form-select">
                        <option value="">All</option>
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}" {{ (string) request('branch_id') === (string) $branch->id ? 'selected' : '' }}>{{ $branch->code }} - {{ $branch->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All</option>
                        <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Draft</option>
                        <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Approved</option>
                        <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Reason</label>
                    <select name="return_reason" class="form-select">
                        <option value="">All</option>
                        <option value="damaged" {{ request('return_reason') === 'damaged' ? 'selected' : '' }}>Damaged</option>
                        <option value="wrong_item" {{ request('return_reason') === 'wrong_item' ? 'selected' : '' }}>Wrong Item</option>
                        <option value="quality_issue" {{ request('return_reason') === 'quality_issue' ? 'selected' : '' }}>Quality Issue</option>
                        <option value="over_supply" {{ request('return_reason') === 'over_supply' ? 'selected' : '' }}>Over Supply</option>
                        <option value="other" {{ request('return_reason') === 'other' ? 'selected' : '' }}>Other</option>
                    </select>
                </div>
                <div class="col-md-1">
                    <label class="form-label">Start</label>
                    <input type="date" name="start_date" value="{{ request('start_date') }}" class="form-control">
                </div>
                <div class="col-md-1">
                    <label class="form-label">End</label>
                    <input type="date" name="end_date" value="{{ request('end_date') }}" class="form-control">
                </div>
                <div class="col-md-1 text-md-end">
                    <button class="btn btn-primary" type="submit">Go</button>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Return No</th>
                            <th>Date</th>
                            <th>Supplier</th>
                            <th>Branch</th>
                            <th>Reason</th>
                            <th>Status</th>
                            <th class="text-end">Amount</th>
                            <th class="text-end">Items</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($returns as $return)
                            <tr>
                                <td>{{ $return->return_number }}</td>
                                <td>{{ optional($return->return_date)->format('d/m/Y') }}</td>
                                <td>{{ $return->supplier->name ?? 'N/A' }}</td>
                                <td>{{ $return->branch->code ?? 'N/A' }}</td>
                                <td>{{ $return->return_reason_label }}</td>
                                <td>{{ $return->status_label }}</td>
                                <td class="text-end">{{ number_format((float) $return->total_amount, 2) }}</td>
                                <td class="text-end">{{ $supportsReturnItems ? (int) ($return->items->count() ?? 0) : 0 }}</td>
                                <td>
                                    @if($return->supplier_id)
                                        <a href="{{ route('admin.supplier.purchase-returns', $return->supplier_id) }}" class="btn btn-sm btn-outline-primary">View Supplier</a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="9" class="text-center text-muted">No purchase returns found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($returns->hasPages())
                <div class="mt-3 d-flex justify-content-center">{{ $returns->links() }}</div>
            @endif
        </div>
    </div>
</div>

<div class="procure-modal" id="purchaseReturnModal" aria-hidden="true">
    <div class="procure-modal-dialog">
        <div class="procure-modal-content">
            <div class="procure-modal-header">
                <h5 class="mb-0">New Purchase Return</h5>
                <button type="button" class="procure-close" id="btnCloseReturnModal" aria-label="Close">&times;</button>
            </div>
            <form id="purchaseReturnForm">
                @csrf
                <div class="procure-modal-body">
                    <div id="purchaseReturnMessage" class="modal-message d-none mb-3"></div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="compact-label">Supplier</label>
                            <select id="returnSupplierId" class="form-select">
                                <option value="">Select Supplier</option>
                                @foreach($suppliers as $supplier)
                                    <option value="{{ $supplier->id }}">{{ $supplier->supplier_code }} - {{ $supplier->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="compact-label">Date</label>
                            <input type="date" id="returnDate" class="form-control" value="{{ date('Y-m-d') }}">
                        </div>
                        <div class="col-md-3">
                            <label class="compact-label">Original Purchase</label>
                            <select id="returnPurchaseOrderId" class="form-select">
                                <option value="">Select Purchase Order</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="compact-label">Reason</label>
                            <select id="returnReason" class="form-select">
                                <option value="">Select Reason</option>
                                <option value="damaged">Damaged Goods</option>
                                <option value="wrong_item">Wrong Item</option>
                                <option value="quality_issue">Quality Issue</option>
                                <option value="over_supply">Over Supply</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="col-md-8">
                            <label class="compact-label">Remarks</label>
                            <textarea id="returnNotes" class="form-control" rows="2" placeholder="Remarks"></textarea>
                        </div>
                        <div class="col-md-4">
                            <label class="compact-label">Supplier Info</label>
                            <div class="return-summary">
                                <div class="summary-line"><span>Current Balance</span><strong id="returnSupplierBalance">0.00</strong></div>
                                <div class="summary-line"><span>Recent Returns</span><strong id="returnRecentCount">0</strong></div>
                                <div class="summary-line total"><span>Total Amount</span><strong id="returnGrandTotal">0.00</strong></div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="mb-0">Return Items</h6>
                        <button type="button" class="btn btn-sm btn-primary" id="btnAddReturnItem"><i class="mdi mdi-plus"></i> Add Item</button>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered return-items-table mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Purchase Item</th>
                                    <th>Warehouse</th>
                                    <th>Qty</th>
                                    <th>Unit Cost</th>
                                    <th class="text-end">Line Total</th>
                                    <th>Notes</th>
                                    <th class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody id="returnItemsBody"></tbody>
                        </table>
                    </div>
                </div>
                <div class="procure-modal-footer">
                    <button type="submit" class="btn btn-info text-white" id="btnSaveReturn">Save</button>
                    <button type="button" class="btn btn-danger" id="btnCancelReturn">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('script')
<script>
document.addEventListener('DOMContentLoaded',function(){
const csrfToken=document.querySelector('meta[name="csrf-token"]')?.content||document.querySelector('input[name="_token"]')?.value;
const supplierBaseUrl=@json(rtrim(route('admin.supplier.index'), '/'));
const recordUrl=@json(route('admin.supplier.purchase-returns.record'));
const modal=document.getElementById('purchaseReturnModal');
const body=document.getElementById('returnItemsBody');
const messageBox=document.getElementById('purchaseReturnMessage');
const state={resources:null,rowIndex:0,open:false};
const el={
    supplierId:document.getElementById('returnSupplierId'),
    date:document.getElementById('returnDate'),
    purchaseOrderId:document.getElementById('returnPurchaseOrderId'),
    reason:document.getElementById('returnReason'),
    notes:document.getElementById('returnNotes'),
    supplierBalance:document.getElementById('returnSupplierBalance'),
    recentCount:document.getElementById('returnRecentCount'),
    grandTotal:document.getElementById('returnGrandTotal'),
    save:document.getElementById('btnSaveReturn')
};
function escapeHtml(value){return String(value??'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#039;');}
function parseNumber(value){const parsed=parseFloat(value);return Number.isFinite(parsed)?parsed:0;}
function showMessage(message,type,errors){
    if(!message){messageBox.className='modal-message d-none mb-3';messageBox.innerHTML='';return;}
    let html='<div>'+escapeHtml(message)+'</div>';
    if(Array.isArray(errors)&&errors.length){html+='<ul>';errors.forEach(function(error){html+='<li>'+escapeHtml(error)+'</li>';});html+='</ul>';}
    messageBox.className='modal-message alert alert-'+type+' mb-3';
    messageBox.innerHTML=html;
}
function openModal(){state.open=true;modal.classList.add('show');document.body.style.overflow='hidden';}
function closeModal(){state.open=false;modal.classList.remove('show');document.body.style.overflow='';}
function extractErrors(payload){if(!payload||!payload.errors){return [];}return Object.values(payload.errors).flat().map(function(message){return String(message);});}
function rowTemplate(index){
    return `<tr data-index="${index}">
        <td><select class="form-select form-select-sm item-select" data-name-template="items[__INDEX__][purchase_item_id]" name="items[${index}][purchase_item_id]"><option value="">Select received item</option></select></td>
        <td><select class="form-select form-select-sm warehouse-select" data-name-template="items[__INDEX__][warehouse_id]" name="items[${index}][warehouse_id]"><option value="">Select warehouse</option></select></td>
        <td><input type="number" class="form-control form-control-sm quantity-input" data-name-template="items[__INDEX__][quantity]" name="items[${index}][quantity]" min="0.01" step="0.01" placeholder="0.00"></td>
        <td><input type="number" class="form-control form-control-sm unit-cost-input" data-name-template="items[__INDEX__][unit_cost]" name="items[${index}][unit_cost]" min="0" step="0.01" placeholder="0.00"></td>
        <td class="text-end line-total">0.00</td>
        <td><input type="text" class="form-control form-control-sm" data-name-template="items[__INDEX__][notes]" name="items[${index}][notes]" placeholder="Optional"></td>
        <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger js-remove-return-row"><i class="mdi mdi-close"></i></button></td>
    </tr>`;
}
function renumberRows(){
    Array.from(body.querySelectorAll('tr')).forEach(function(row,index){
        row.dataset.index=String(index);
        row.querySelectorAll('[name]').forEach(function(input){
            const template=input.getAttribute('data-name-template');
            if(template){input.name=template.replace(/__INDEX__/g,String(index));}
        });
    });
}
function populatePurchaseOrders(){
    const options=['<option value="">Select Purchase Order</option>'];
    (state.resources?.purchase_orders||[]).forEach(function(order){
        options.push('<option value="'+order.id+'">'+escapeHtml(order.label)+'</option>');
    });
    el.purchaseOrderId.innerHTML=options.join('');
}
function filteredItems(){
    const selectedOrder=el.purchaseOrderId.value;
    const items=state.resources?.returnable_items||[];
    if(!selectedOrder){return items;}
    return items.filter(function(item){return String(item.purchase_order_id||'')===String(selectedOrder);});
}
function itemOptions(){
    return ['<option value="">Select received item</option>'].concat(filteredItems().map(function(item){
        return '<option value="'+item.id+'" data-po-id="'+(item.purchase_order_id||'')+'" data-warehouse-id="'+(item.warehouse_id||'')+'" data-max-qty="'+Number(item.quantity_received||0).toFixed(2)+'" data-unit-cost="'+Number(item.unit_cost||0).toFixed(2)+'">'+escapeHtml(item.label)+'</option>';
    })).join('');
}
function warehouseOptions(){
    return ['<option value="">Select warehouse</option>'].concat((state.resources?.warehouses||[]).map(function(warehouse){
        return '<option value="'+warehouse.id+'">'+escapeHtml(warehouse.label)+'</option>';
    })).join('');
}
function refreshRowOptions(row){
    row.querySelector('.item-select').innerHTML=itemOptions();
    row.querySelector('.warehouse-select').innerHTML=warehouseOptions();
}
function refreshAllRows(){body.querySelectorAll('tr').forEach(refreshRowOptions);}
function addRow(item){
    const index=state.rowIndex++;
    body.insertAdjacentHTML('beforeend',rowTemplate(index));
    const row=body.lastElementChild;
    refreshRowOptions(row);
    if(item){
        row.querySelector('.item-select').value=item.purchase_item_id?String(item.purchase_item_id):'';
        row.querySelector('.warehouse-select').value=item.warehouse_id?String(item.warehouse_id):'';
        row.querySelector('.quantity-input').value=item.quantity?String(item.quantity):'';
        row.querySelector('.unit-cost-input').value=item.unit_cost?String(item.unit_cost):'';
        row.querySelector('[name$="[notes]"]').value=item.notes||'';
    }
    return row;
}
function isDuplicatePurchaseItem(selectedItemId,exceptRow){
    if(!selectedItemId){return false;}
    return Array.from(body.querySelectorAll('tr')).some(function(row){
        if(exceptRow&&row===exceptRow){return false;}
        return String(row.querySelector('.item-select')?.value||'')===String(selectedItemId);
    });
}
function recalculateTotals(){
    let total=0;
    body.querySelectorAll('tr').forEach(function(row){
        const quantity=parseNumber(row.querySelector('.quantity-input').value);
        const unitCost=parseNumber(row.querySelector('.unit-cost-input').value);
        const lineTotal=quantity*unitCost;
        row.querySelector('.line-total').textContent=lineTotal.toFixed(2);
        total+=lineTotal;
    });
    el.grandTotal.textContent=total.toFixed(2);
    return total;
}
function buildFormData(){
    const formData=new FormData();
    formData.append('_token',csrfToken);
    formData.append('supplier_id',el.supplierId.value);
    formData.append('return_date',el.date.value);
    formData.append('original_purchase_id',el.purchaseOrderId.value);
    formData.append('return_reason',el.reason.value);
    formData.append('notes',el.notes.value||'');
    formData.append('total_amount',recalculateTotals().toFixed(2));
    body.querySelectorAll('tr').forEach(function(row,index){
        row.querySelectorAll('[name]').forEach(function(input){
            const template=input.getAttribute('data-name-template');
            if(template){formData.append(template.replace(/__INDEX__/g,String(index)),input.value??'');}
        });
    });
    return formData;
}
function resetForm(){
    state.resources=null;state.rowIndex=0;el.supplierId.value='';el.date.value=new Date().toISOString().slice(0,10);
    el.purchaseOrderId.innerHTML='<option value="">Select Purchase Order</option>';el.reason.value='';el.notes.value='';
    el.supplierBalance.textContent='0.00';el.recentCount.textContent='0';el.grandTotal.textContent='0.00';body.innerHTML='';addRow();showMessage('','success');
}
function loadSupplierResources(supplierId){
    if(!supplierId){state.resources=null;populatePurchaseOrders();refreshAllRows();el.supplierBalance.textContent='0.00';el.recentCount.textContent='0';return Promise.resolve();}
    return fetch(supplierBaseUrl+'/'+supplierId+'/purchase-returns/data',{headers:{'Accept':'application/json'}})
        .then(function(response){return response.json().then(function(payload){return {ok:response.ok,payload:payload};});})
        .then(function(result){
            if(!result.ok){throw result.payload;}
            state.resources=result.payload;
            populatePurchaseOrders();
            refreshAllRows();
            el.supplierBalance.textContent=Number(result.payload.supplier?.current_balance||0).toFixed(2);
            el.recentCount.textContent=String((result.payload.recent_returns||[]).length);
        })
        .catch(function(payload){showMessage(payload.message||'Unable to load supplier purchase data.','danger',extractErrors(payload));});
}
document.getElementById('btnOpenReturnModal').addEventListener('click',function(){resetForm();openModal();});
document.getElementById('btnCloseReturnModal').addEventListener('click',closeModal);
document.getElementById('btnCancelReturn').addEventListener('click',closeModal);
modal.addEventListener('click',function(event){if(event.target===modal){closeModal();}});
document.addEventListener('keydown',function(event){if(event.key==='Escape'&&state.open){closeModal();}});
document.getElementById('btnAddReturnItem').addEventListener('click',function(){
    const lastRow=body.querySelector('tr:last-child');
    const selectedItemId=String(lastRow?.querySelector('.item-select')?.value||'');
    if(!selectedItemId){
        alert('Please select a purchase item in the current row before adding a new row.');
        lastRow?.querySelector('.item-select')?.focus();
        return;
    }
    if(isDuplicatePurchaseItem(selectedItemId,lastRow)){
        alert('This purchase item is already added.');
        return;
    }
    addRow();
});
el.supplierId.addEventListener('change',function(){showMessage('','success');loadSupplierResources(el.supplierId.value);});
el.purchaseOrderId.addEventListener('change',function(){refreshAllRows();});
body.addEventListener('click',function(event){
    const removeButton=event.target.closest('.js-remove-return-row');
    if(!removeButton){return;}
    if(body.querySelectorAll('tr').length===1){return;}
    removeButton.closest('tr').remove();renumberRows();recalculateTotals();
});
body.addEventListener('change',function(event){
    const row=event.target.closest('tr');
    if(!row){return;}
    if(event.target.classList.contains('item-select')){
        const selectedItemId=String(event.target.value||'');
        if(isDuplicatePurchaseItem(selectedItemId,row)){
            alert('This purchase item is already added.');
            event.target.value='';
            recalculateTotals();
            return;
        }
        const selected=event.target.selectedOptions[0];
        if(selected?.dataset?.warehouseId){row.querySelector('.warehouse-select').value=selected.dataset.warehouseId;}
        if(selected?.dataset?.unitCost&&(!row.querySelector('.unit-cost-input').value||parseNumber(row.querySelector('.unit-cost-input').value)<=0)){row.querySelector('.unit-cost-input').value=selected.dataset.unitCost;}
    }
    recalculateTotals();
});
body.addEventListener('input',function(event){if(event.target.closest('tr')){recalculateTotals();}});
document.getElementById('purchaseReturnForm').addEventListener('submit',function(event){
    event.preventDefault();showMessage('','success');el.save.disabled=true;
    fetch(recordUrl,{method:'POST',headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest'},body:buildFormData()})
        .then(function(response){return response.json().then(function(payload){return {ok:response.ok,payload:payload};}).catch(function(){return {ok:response.ok,payload:{message:'Unexpected server response.'}};});})
        .then(function(result){if(!result.ok){throw result.payload;}showMessage(result.payload.message||'Purchase return recorded successfully.','success');window.location.href=@json(route('admin.supplier.purchase-returns.overview'));})
        .catch(function(payload){showMessage(payload.message||'Failed to save purchase return.','danger',extractErrors(payload));})
        .finally(function(){el.save.disabled=false;});
});
resetForm();
const autoSupplierId=@json(request('supplier_modal'));
if(autoSupplierId){openModal();el.supplierId.value=String(autoSupplierId);loadSupplierResources(autoSupplierId);}
});
</script>
@endsection
