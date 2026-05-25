@extends('backEnd.layouts.master')
@section('title', 'Purchase')

@section('css')
<style>
    .procure-box{border:1px solid #dfe6ef;background:#fff}
    .procure-box .card-body{padding:1rem}
    .procure-modal{position:fixed;inset:0;display:none;align-items:center;justify-content:center;background:rgba(15,23,42,.38);z-index:1055;padding:24px}
    .procure-modal.show{display:flex}
    .procure-modal-dialog{width:min(1180px,100%);max-height:calc(100vh - 48px);overflow:auto}
    .procure-modal-content{background:#fff;border-radius:10px;overflow:hidden}
    .procure-modal-header{display:flex;align-items:center;justify-content:space-between;padding:.9rem 1.2rem;border-bottom:2px solid #f15a24}
    .procure-modal-body{padding:1rem}
    .procure-modal-footer{display:flex;justify-content:flex-end;gap:.5rem;padding:1rem;background:#edf3fb;border-top:1px solid #d7e2ef}
    .procure-close{background:none;border:0;font-size:1.8rem;line-height:1;color:#9aa4b2}
    .compact-label{font-size:.85rem;font-weight:600;margin-bottom:.35rem}
    .purchase-items-table th,.purchase-items-table td{vertical-align:middle;font-size:.85rem}
    .purchase-items-table input,.purchase-items-table select{min-width:110px}
    .purchase-items-table .product-select{min-width:200px}
    .purchase-items-table .variant-select{min-width:180px}
    .line-total{font-weight:700}
    .purchase-summary{background:#f8fbff;border:1px solid #d7e2ef;border-radius:8px;padding:.9rem}
    .summary-row{display:flex;justify-content:space-between;gap:1rem;font-size:.92rem}
    .summary-row + .summary-row{margin-top:.35rem}
    .summary-row.total{padding-top:.45rem;border-top:1px solid #d7e2ef;font-weight:700}
    .modal-message ul{margin:0;padding-left:1rem}
    .btn-grid-action{display:inline-flex;align-items:center;justify-content:center;width:34px;height:34px;border:0;background:#315d84;color:#fff;border-radius:2px}
    .btn-grid-action + .btn-grid-action{margin-left:4px}
    .btn-grid-action.btn-danger{background:#b54b3f}
    .btn-grid-action.btn-success{background:#2e8b57}
    .btn-grid-action.btn-info{background:#5b76b6}
</style>
@endsection

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <div class="page-title-right">
                    @can('grn-create')
                    <button type="button" class="btn btn-danger" id="btnOpenPurchaseModal"><i class="mdi mdi-plus"></i> New Purchase</button>
                    @endcan
                </div>
                <h4 class="page-title">Purchase</h4>
            </div>
        </div>
    </div>

    <div class="card procure-box">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end mb-3">
                @if(request('status'))
                    <input type="hidden" name="status" value="{{ request('status') }}">
                @endif
                <div class="col-md-4">
                    <label class="form-label">Search</label>
                    <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Search purchase number or invoice">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Supplier</label>
                    <select name="supplier_id" class="form-select">
                        <option value="">All Suppliers</option>
                        @foreach($suppliers as $supplier)
                            <option value="{{ $supplier->id }}" {{ (string) request('supplier_id') === (string) $supplier->id ? 'selected' : '' }}>
                                {{ $supplier->supplier_code }} - {{ $supplier->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Warehouse</label>
                    <select name="warehouse_id" class="form-select">
                        <option value="">All Warehouses</option>
                        @foreach($warehouses as $warehouse)
                            <option value="{{ $warehouse->id }}" {{ (string) request('warehouse_id') === (string) $warehouse->id ? 'selected' : '' }}>
                                {{ $warehouse->code }} - {{ $warehouse->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 text-md-end">
                    <button class="btn btn-primary" type="submit">Search</button>
                    <a href="{{ route('admin.grn.index') }}" class="btn btn-secondary">Reset</a>
                </div>
            </form>

            <div class="mb-3 d-flex flex-wrap gap-2">
                <a href="{{ route('admin.grn.index', ['status' => 'draft']) }}" class="btn btn-sm {{ request('status') === 'draft' ? 'btn-warning' : 'btn-outline-warning' }}">Draft</a>
                <a href="{{ route('admin.grn.index', ['status' => 'approved']) }}" class="btn btn-sm {{ request('status') === 'approved' ? 'btn-success' : 'btn-outline-success' }}">Approved</a>
                <a href="{{ route('admin.grn.index') }}" class="btn btn-sm {{ request('status') ? 'btn-outline-info' : 'btn-info text-white' }}">All</a>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Purchase No</th>
                            <th>Date</th>
                            <th>Warehouse</th>
                            <th>Supplier</th>
                            <th>Invoice</th>
                            <th class="text-end">Items</th>
                            <th class="text-end">Amount</th>
                            <th>Status</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($grns as $grn)
                            <tr>
                                <td>{{ $grn->grn_number }}</td>
                                <td>{{ optional($grn->grn_date)->format('d/m/Y') }}</td>
                                <td>{{ $grn->warehouse->name ?? 'N/A' }}</td>
                                <td>{{ $grn->supplier->name ?? 'N/A' }}</td>
                                <td>{{ $grn->invoice_number ?: '-' }}</td>
                                <td class="text-end">{{ (int) ($grn->items_count ?? 0) }}</td>
                                <td class="text-end">{{ number_format((float) ($grn->total_amount ?? 0), 2) }}</td>
                                <td>
                                    @if($grn->status === 'approved')
                                        <span class="badge bg-success">Approved</span>
                                    @else
                                        <span class="badge bg-warning text-dark">{{ ucfirst($grn->status) }}</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @can('grn-view')
                                    <a href="{{ route('admin.grn.show', $grn->id) }}" class="btn-grid-action btn-info" title="View"><i class="fe-eye"></i></a>
                                    @endcan
                                    @if($grn->status === 'draft')
                                        @can('grn-edit')
                                        <button type="button" class="btn-grid-action js-edit-purchase" data-id="{{ $grn->id }}" title="Edit"><i class="fe-edit"></i></button>
                                        @endcan
                                        @can('grn-delete')
                                        <form method="POST" action="{{ route('admin.grn.destroy', $grn->id) }}" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn-grid-action btn-danger delete-confirm" title="Delete"><i class="fe-trash-2"></i></button>
                                        </form>
                                        @endcan
                                        @can('grn-approve')
                                        <form method="POST" action="{{ route('admin.grn.approve', $grn->id) }}" class="d-inline">
                                            @csrf
                                            <button type="button" class="btn-grid-action btn-success change-confirm" title="Approve"><i class="fe-check"></i></button>
                                        </form>
                                        @endcan
                                    @endif
                                    @can('grn-view')
                                    <a href="{{ route('admin.grn.print', $grn->id) }}" target="_blank" class="btn-grid-action" title="Print"><i class="fe-printer"></i></a>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted">No purchases found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($grns->hasPages())
                <div class="mt-3 d-flex justify-content-center">{{ $grns->links() }}</div>
            @endif
        </div>
    </div>
</div>

<div class="procure-modal" id="purchaseModal" aria-hidden="true">
    <div class="procure-modal-dialog">
        <div class="procure-modal-content">
            <div class="procure-modal-header">
                <h5 class="mb-0" id="purchaseModalTitle">New Purchase</h5>
                <button type="button" class="procure-close" id="btnClosePurchaseModal" aria-label="Close">&times;</button>
            </div>
            <form id="purchaseForm">
                @csrf
                <div class="procure-modal-body">
                    <div id="purchaseFormMessage" class="modal-message d-none mb-3"></div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-3">
                            <label class="compact-label">Warehouse</label>
                            <select id="purchaseWarehouseId" class="form-select" required>
                                <option value="">Select Warehouse</option>
                                @foreach($warehouses as $warehouse)
                                    <option value="{{ $warehouse->id }}">{{ $warehouse->code }} - {{ $warehouse->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="compact-label">Supplier</label>
                            <select id="purchaseSupplierId" class="form-select">
                                <option value="">Select Supplier</option>
                                @foreach($suppliers as $supplier)
                                    <option value="{{ $supplier->id }}">{{ $supplier->supplier_code }} - {{ $supplier->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="compact-label">Purchase Date</label>
                            <input type="date" id="purchaseDate" class="form-control" value="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="col-md-2">
                            <label class="compact-label">Invoice Date</label>
                            <input type="date" id="purchaseInvoiceDate" class="form-control">
                        </div>
                        <div class="col-md-2">
                            <label class="compact-label">Invoice No</label>
                            <input type="text" id="purchaseInvoiceNumber" class="form-control" placeholder="Invoice No">
                        </div>
                        <div class="col-md-2">
                            <label class="compact-label">Shipping Cost</label>
                            <input type="number" id="purchaseShippingCost" class="form-control" min="0" step="0.01" value="0">
                        </div>
                        <div class="col-md-2">
                            <label class="compact-label">Other Charges</label>
                            <input type="number" id="purchaseOtherCharges" class="form-control" min="0" step="0.01" value="0">
                        </div>
                        <div class="col-md-8">
                            <label class="compact-label">Remarks</label>
                            <textarea id="purchaseNotes" class="form-control" rows="2" placeholder="Remarks"></textarea>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="mb-0">Purchase Items</h6>
                        <button type="button" class="btn btn-sm btn-primary" id="btnAddPurchaseItem"><i class="mdi mdi-plus"></i> Add Item</button>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-4">
                            <label class="compact-label">Product <span class="text-muted fw-normal">(search)</span></label>
                            <!-- No select2: input drives a vanilla results list; select remains the source of truth -->
                            <input type="text" id="entryProductSearchInput" class="form-control form-control-sm" placeholder="Type name, SKU, or code…">
                            <div id="entryProductSearchResults" class="position-absolute bg-white border rounded shadow-sm" style="display:none;z-index:2000;max-height:240px;overflow:auto;width:100%;"></div>
                            <input type="hidden" id="entryProductId" value="">
                            <small class="text-muted">Min. 2 characters. Variant loads after selection.</small>
                        </div>
                        <div class="col-md-2">
                            <label class="compact-label">Variant</label>
                            <select id="entryVariantId" class="form-select form-select-sm">
                                <option value="">Select Variant</option>
                            </select>
                        </div>
                        <div class="col-md-1">
                            <label class="compact-label">Order Qty</label>
                            <input type="number" id="entryOrderedQty" class="form-control form-control-sm" min="0.01" step="0.01" placeholder="0.00">
                        </div>
                        <div class="col-md-1">
                            <label class="compact-label">Recv Qty</label>
                            <input type="number" id="entryQty" class="form-control form-control-sm" min="0.01" step="0.01" placeholder="0.00">
                        </div>
                        <div class="col-md-1">
                            <label class="compact-label">Cost</label>
                            <input type="number" id="entryUnitCost" class="form-control form-control-sm" min="0" step="0.01" placeholder="0.00">
                        </div>
                        <div class="col-md-1">
                            <label class="compact-label">Tax %</label>
                            <input type="number" id="entryTaxRate" class="form-control form-control-sm" min="0" max="100" step="0.01" value="0">
                        </div>
                        <div class="col-md-1">
                            <label class="compact-label">Batch</label>
                            <input type="text" id="entryBatch" class="form-control form-control-sm" placeholder="Batch">
                        </div>
                        <div class="col-md-2">
                            <label class="compact-label">Expiry</label>
                            <input type="date" id="entryExpiry" class="form-control form-control form-control-sm">
                        </div>
                    </div>

                    <div class="table-responsive mb-3">
                        <table class="table table-bordered purchase-items-table mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Product</th>
                                    <th>Variant</th>
                                    <th>Order Qty</th>
                                    <th>Recv Qty</th>
                                    <th>Unit Cost</th>
                                    <th>Tax %</th>
                                    <th>Batch</th>
                                    <th>Expiry</th>
                                    <th class="text-end">Amount</th>
                                    <th class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody id="purchaseItemsBody"></tbody>
                        </table>
                    </div>

                    <div class="row g-3 align-items-start">
                        <div class="col-md-7">
                            <div class="small text-muted">Variant list loads after product selection. This popup uses plain dropdowns with no plugin dependency.</div>
                        </div>
                        <div class="col-md-5">
                            <div class="purchase-summary">
                                <div class="summary-row"><span>Sub Total</span><strong id="purchaseSubtotal">0.00</strong></div>
                                <div class="summary-row"><span>Tax Total</span><strong id="purchaseTaxTotal">0.00</strong></div>
                                <div class="summary-row"><span>Shipping + Other</span><strong id="purchaseExtrasTotal">0.00</strong></div>
                                <div class="summary-row total"><span>Grand Total</span><strong id="purchaseGrandTotal">0.00</strong></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="procure-modal-footer">
                    <button type="submit" class="btn btn-info text-white" id="btnSavePurchase">Save</button>
                    <button type="button" class="btn btn-danger" id="btnCancelPurchase">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@php
    $purchaseProductsJson = $products->map(function ($product) {
        return [
            'id' => (int) $product->id,
            'name' => (string) $product->name,
            'sku' => (string) ($product->sku ?? ''),
            'default_cost' => (float) ($product->purchase_price ?? $product->new_price ?? 0),
        ];
    })->values();
@endphp

@section('script')
<script>
document.addEventListener('DOMContentLoaded',function(){
const csrfToken=document.querySelector('meta[name="csrf-token"]')?.content||document.querySelector('input[name="_token"]')?.value;
const productSearchUrl=@json(route('admin.grn.api.search-products'));
const variantApiUrl=@json($variantApiUrl);
const baseGrnUrl=@json(rtrim(route('admin.grn.index'), '/'));
const indexUrl=@json(route('admin.grn.index'));
const storeUrl=@json(route('admin.grn.store'));
const products=@json($purchaseProductsJson);

const modal=document.getElementById('purchaseModal');
const form=document.getElementById('purchaseForm');
const itemsBody=document.getElementById('purchaseItemsBody');
const formMessage=document.getElementById('purchaseFormMessage');
const state={editId:null,rowIndex:0,open:false};
let entryVariantMap={};

const el={
    title:document.getElementById('purchaseModalTitle'),
    warehouseId:document.getElementById('purchaseWarehouseId'),
    supplierId:document.getElementById('purchaseSupplierId'),
    date:document.getElementById('purchaseDate'),
    invoiceDate:document.getElementById('purchaseInvoiceDate'),
    invoiceNumber:document.getElementById('purchaseInvoiceNumber'),
    shippingCost:document.getElementById('purchaseShippingCost'),
    otherCharges:document.getElementById('purchaseOtherCharges'),
    notes:document.getElementById('purchaseNotes'),
    save:document.getElementById('btnSavePurchase'),
    subtotal:document.getElementById('purchaseSubtotal'),
    taxTotal:document.getElementById('purchaseTaxTotal'),
    extrasTotal:document.getElementById('purchaseExtrasTotal'),
    grandTotal:document.getElementById('purchaseGrandTotal'),
    entryProductId:document.getElementById('entryProductId'),
    entryVariantId:document.getElementById('entryVariantId'),
    entryOrderedQty:document.getElementById('entryOrderedQty'),
    entryQty:document.getElementById('entryQty'),
    entryUnitCost:document.getElementById('entryUnitCost'),
    entryTaxRate:document.getElementById('entryTaxRate'),
    entryBatch:document.getElementById('entryBatch'),
    entryExpiry:document.getElementById('entryExpiry'),
    entryProductSearchInput:document.getElementById('entryProductSearchInput'),
    entryProductSearchResults:document.getElementById('entryProductSearchResults')
};

function escapeHtml(value){return String(value??'').replace(/&/g,'&amp;').replace(/</g,'<').replace(/>/g,'>').replace(/"/g,'"').replace(/'/g,'&#039;');}
function parseNumber(value){const parsed=parseFloat(value);return Number.isFinite(parsed)?parsed:0;}

function showMessage(message,type,errors){
    if(!message){formMessage.className='modal-message d-none mb-3';formMessage.innerHTML='';return;}
    let html='<div>'+escapeHtml(message)+'</div>';
    if(Array.isArray(errors)&&errors.length){html+='<ul>';errors.forEach(function(error){html+='<li>'+escapeHtml(error)+'</li>';});html+='</ul>';}
    formMessage.className='modal-message alert alert-'+type+' mb-3';
    formMessage.innerHTML=html;
}
function openModal(){state.open=true;modal.classList.add('show');document.body.style.overflow='hidden';}
function closeModal(){state.open=false;modal.classList.remove('show');document.body.style.overflow='';}

const productOptions = products.map(function(product){
    return '<option value="'+product.id+'" data-sku="'+escapeHtml(product.sku)+'" data-default-cost="'+Number(product.default_cost||0).toFixed(2)+'">'+escapeHtml(product.name)+'</option>';
}).join('');

function rowTemplate(index){
    return `<tr data-index="${index}">
        <td><select class="form-select form-select-sm product-select" data-name-template="items[__INDEX__][product_id]" name="items[${index}][product_id]" required><option value="">Select Product</option>${productOptions}</select></td>
        <td>
            <select class="form-select form-select-sm variant-select" data-name-template="items[__INDEX__][product_variant_id]" name="items[${index}][product_variant_id]"><option value="">Select Variant</option></select>
            <input type="hidden" class="sku-input" data-name-template="items[__INDEX__][sku]" name="items[${index}][sku]">
            <input type="hidden" class="color-input" data-name-template="items[__INDEX__][color]" name="items[${index}][color]">
            <input type="hidden" class="size-input" data-name-template="items[__INDEX__][size]" name="items[${index}][size]">
            <input type="hidden" class="age-input" data-name-template="items[__INDEX__][age]" name="items[${index}][age]">
        </td>
        <td><input type="number" class="form-control form-control-sm ordered-qty-input" data-name-template="items[__INDEX__][ordered_quantity]" name="items[${index}][ordered_quantity]" min="0.01" step="0.01" placeholder="0.00"></td>
        <td><input type="number" class="form-control form-control-sm quantity-input" data-name-template="items[__INDEX__][quantity]" name="items[${index}][quantity]" min="0.01" step="0.01" placeholder="0.00" required></td>
        <td><input type="number" class="form-control form-control-sm unit-cost-input" data-name-template="items[__INDEX__][unit_cost]" name="items[${index}][unit_cost]" min="0" step="0.01" placeholder="0.00" required></td>
        <td><input type="number" class="form-control form-control-sm tax-rate-input" data-name-template="items[__INDEX__][tax_rate]" name="items[${index}][tax_rate]" min="0" max="100" step="0.01" value="0"></td>
        <td><input type="text" class="form-control form-control-sm" data-name-template="items[__INDEX__][batch_number]" name="items[${index}][batch_number]" placeholder="Batch"></td>
        <td><input type="date" class="form-control form-control-sm" data-name-template="items[__INDEX__][expiry_date]" name="items[${index}][expiry_date]"></td>
        <td class="text-end line-total">0.00</td>
        <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger js-remove-row"><i class="mdi mdi-close"></i></button></td>
    </tr>`;
}

function renumberRows(){
    Array.from(itemsBody.querySelectorAll('tr')).forEach(function(row,index){
        row.dataset.index=String(index);
        row.querySelectorAll('[name]').forEach(function(input){
            const template=input.getAttribute('data-name-template');
            if(template){input.name=template.replace(/__INDEX__/g,String(index));}
        });
    });
}

function clearForm(){
    state.editId=null;state.rowIndex=0;el.title.textContent='New Purchase';form.reset();
    el.date.value=new Date().toISOString().slice(0,10);el.shippingCost.value='0';el.otherCharges.value='0';
    itemsBody.innerHTML='';resetEntryBuilder();showMessage('','success');recalculateTotals();
}

function setRowValues(row,item){
    row.querySelector('.product-select').value=item.product_id?String(item.product_id):'';
    row.querySelector('.ordered-qty-input').value=item.ordered_quantity?String(item.ordered_quantity):'';
    row.querySelector('.quantity-input').value=item.quantity?String(item.quantity):'';
    row.querySelector('.unit-cost-input').value=item.unit_cost?String(item.unit_cost):'';
    row.querySelector('.tax-rate-input').value=item.tax_rate?String(item.tax_rate):'0';
    row.querySelector('[name$="[batch_number]"]').value=item.batch_number||'';
    row.querySelector('[name$="[expiry_date]"]').value=item.expiry_date||'';
    row.querySelector('.sku-input').value=item.sku||'';
    row.querySelector('.color-input').value=item.color||'';
    row.querySelector('.size-input').value=item.size||'';
    row.querySelector('.age-input').value=item.age||'';
    row.dataset.selectedVariantId=item.product_variant_id?String(item.product_variant_id):'';
    row.dataset.selectedSku=item.sku||'';
}

function addRow(item){
    const index=state.rowIndex++;
    itemsBody.insertAdjacentHTML('beforeend',rowTemplate(index));
    const row=itemsBody.lastElementChild;
    if(item){setRowValues(row,item);}
    loadVariants(row).finally(recalculateTotals);
    return row;
}

function resetEntryBuilder(){
    entryVariantMap={};
    el.entryProductId.value='';
    if(el.entryProductSearchInput){el.entryProductSearchInput.value='';}
    el.entryVariantId.innerHTML='<option value="">Select Variant</option>';
    el.entryOrderedQty.value='';
    el.entryQty.value='';
    el.entryUnitCost.value='';
    el.entryTaxRate.value='0';
    el.entryBatch.value='';
    el.entryExpiry.value='';
}

function loadEntryVariants(){
    const productId=el.entryProductId.value;
    el.entryVariantId.innerHTML='<option value="">Select Variant</option>';
    entryVariantMap={};
    if(!productId){return;}
    fetch(variantApiUrl+'?product_id='+encodeURIComponent(productId),{headers:{'Accept':'application/json'}})
        .then(function(response){if(!response.ok){throw new Error('Failed to load variants.');}return response.json();})
        .then(function(payload){
            const variants=Array.isArray(payload.variants)?payload.variants:[];
            if(!variants.length){
                el.entryVariantId.innerHTML='<option value="">Default / No Variant</option>';
                return;
            }
            let options='<option value="">Select Variant</option>';
            variants.forEach(function(variant){
                entryVariantMap[String(variant.id)]=variant;
                options+='<option value="'+variant.id+'">'+escapeHtml(buildVariantLabel(variant))+'</option>';
            });
            el.entryVariantId.innerHTML=options;
        })
        .catch(function(){el.entryVariantId.innerHTML='<option value="">No variants found</option>';});
}

function getEntryItemPayload(){
    const productId=el.entryProductId.value;
    const variantId=el.entryVariantId.value;
    if(!productId){return null;}
    const variant=entryVariantMap[String(variantId)]||null;
    let product=products.find(function(item){return String(item.id)===String(productId);})||null;

    const sku=variant?.sku_code||product?.sku||'';
    const unitCostInput=parseNumber(el.entryUnitCost.value);
    const variantCost=parseNumber(variant?.cost_price);

    // default cost from initial product list (no select2 metadata)
    const defaultCost=parseNumber(product?.default_cost);

    return {
        product_id:productId,
        product_variant_id:variantId||'',
        sku:sku,
        color:variant?.color||'',
        size:variant?.size||'',
        age:variant?.age||'',
        ordered_quantity:el.entryOrderedQty.value||'',
        quantity:el.entryQty.value||'',
        unit_cost:unitCostInput>0?unitCostInput.toFixed(2):(variantCost>0?variantCost.toFixed(2):defaultCost.toFixed(2)),
        tax_rate:el.entryTaxRate.value||'0',
        batch_number:el.entryBatch.value||'',
        expiry_date:el.entryExpiry.value||''
    };
}

function getRowSelectionKey(row){
    if(!row){return '';}
    const productId=String(row.querySelector('.product-select')?.value||'');
    const variantId=String(row.querySelector('.variant-select')?.value||'');
    if(!productId){return '';}
    return productId+'::'+variantId;
}

function hasDuplicateSelection(selectionKey,exceptRow){
    if(!selectionKey){return false;}
    return Array.from(itemsBody.querySelectorAll('tr')).some(function(row){
        if(exceptRow&&row===exceptRow){return false;}
        return getRowSelectionKey(row)===selectionKey;
    });
}

function syncVariantMeta(row){
    const selected=row.querySelector('.variant-select').selectedOptions[0];
    const productSelect=row.querySelector('.product-select');
    const defaultSku=productSelect.selectedOptions[0]?.dataset?.sku||'';
    const defaultCost=productSelect.selectedOptions[0]?.dataset?.defaultCost||'0';
    row.querySelector('.sku-input').value=selected?.dataset?.sku||defaultSku;
    row.querySelector('.color-input').value=selected?.dataset?.color||'';
    row.querySelector('.size-input').value=selected?.dataset?.size||'';
    row.querySelector('.age-input').value=selected?.dataset?.age||'';

    const unitCostInput=row.querySelector('.unit-cost-input');
    if(!unitCostInput.value||parseNumber(unitCostInput.value)<=0){
        unitCostInput.value=selected?.dataset?.cost||defaultCost||'0';
    }
}

function buildVariantLabel(variant){
    const parts=[variant.color,variant.size,variant.age].filter(Boolean);
    const suffix=parts.length?parts.join(' / '):(variant.label||'Default Variant');
    return (variant.sku_code?variant.sku_code+' - ':'')+suffix;
}

function loadVariants(row){
    const productId=row.querySelector('.product-select').value;
    const variantSelect=row.querySelector('.variant-select');
    const selectedVariantId=row.dataset.selectedVariantId||'';
    const selectedSku=row.dataset.selectedSku||'';
    if(!productId){
        variantSelect.innerHTML='<option value="">Select Variant</option>';
        row.querySelector('.sku-input').value='';
        row.querySelector('.color-input').value='';
        row.querySelector('.size-input').value='';
        row.querySelector('.age-input').value='';
        return Promise.resolve();
    }
    variantSelect.innerHTML='<option value="">Loading...</option>';
    return fetch(variantApiUrl+'?product_id='+encodeURIComponent(productId),{headers:{'Accept':'application/json'}})
        .then(function(response){if(!response.ok){throw new Error('Failed to load variants.');}return response.json();})
        .then(function(payload){
            const variants=Array.isArray(payload.variants)?payload.variants:[];
            if(!variants.length){
                variantSelect.innerHTML='<option value="">Default / No Variant</option>';
                syncVariantMeta(row);
                return;
            }
            let options='<option value="">Select Variant</option>';
            variants.forEach(function(variant){
                const isSelected=(selectedVariantId&&String(variant.id)===String(selectedVariantId))||(!selectedVariantId&&selectedSku&&String(variant.sku_code)===String(selectedSku));
                options+='<option value="'+variant.id+'" data-sku="'+escapeHtml(variant.sku_code||'')+'" data-color="'+escapeHtml(variant.color||'')+'" data-size="'+escapeHtml(variant.size||'')+'" data-age="'+escapeHtml(variant.age||'')+'" data-cost="'+Number(variant.cost_price||0).toFixed(2)+'"'+(isSelected?' selected':'')+'>'+escapeHtml(buildVariantLabel(variant))+'</option>';
            });
            variantSelect.innerHTML=options;
            syncVariantMeta(row);
        })
        .catch(function(){
            variantSelect.innerHTML='<option value="">No variants found</option>';
            syncVariantMeta(row);
        })
        .finally(function(){row.dataset.selectedVariantId='';row.dataset.selectedSku='';recalculateTotals();});
}

function recalculateRow(row){
    const quantity=parseNumber(row.querySelector('.quantity-input').value);
    const unitCost=parseNumber(row.querySelector('.unit-cost-input').value);
    const lineTotal=quantity*unitCost;
    row.querySelector('.line-total').textContent=lineTotal.toFixed(2);
    return {subtotal:lineTotal,tax:lineTotal*(parseNumber(row.querySelector('.tax-rate-input').value)/100)};
}
function recalculateTotals(){
    let subtotal=0;let taxTotal=0;
    itemsBody.querySelectorAll('tr').forEach(function(row){
        const totals=recalculateRow(row);subtotal+=totals.subtotal;taxTotal+=totals.tax;
    });
    const extras=parseNumber(el.shippingCost.value)+parseNumber(el.otherCharges.value);
    const grandTotal=subtotal+taxTotal+extras;
    el.subtotal.textContent=subtotal.toFixed(2);
    el.taxTotal.textContent=taxTotal.toFixed(2);
    el.extrasTotal.textContent=extras.toFixed(2);
    el.grandTotal.textContent=grandTotal.toFixed(2);
}

function extractErrors(payload){
    if(!payload||!payload.errors){return [];}
    return Object.values(payload.errors).flat().map(function(message){return String(message);});
}

function parseResponse(response){
    const contentType=response.headers.get('content-type')||'';
    if(contentType.includes('application/json')){
        return response.json()
            .then(function(payload){return {ok:response.ok,payload:payload};})
            .catch(function(){return {ok:response.ok,payload:{message:'Invalid JSON response from server.'}};});
    }
    return response.text().then(function(body){
        let message='Unexpected server response.';
        if(response.status===401){message='Please log in again and try saving the purchase.';}
        else if(response.status===403){message='You do not have permission to perform this purchase action.';}
        else if(response.status===419){message='Your session expired. Reload the page and try again.';}
        else if(response.status>=500){message='Server error occurred while saving the purchase.';}
        else if(response.status>=300&&response.status<400){message='Request was redirected unexpectedly. Reload and try again.';}
        else if(body&&body.includes('The given data was invalid')){message='Validation failed. Please review required purchase fields.';}
        return {ok:false,payload:{message:message}};
    });
}

function buildFormData(){
    const formData=new FormData();
    formData.append('_token',csrfToken);
    formData.append('warehouse_id',el.warehouseId.value);
    formData.append('supplier_id',el.supplierId.value);
    formData.append('grn_date',el.date.value);
    formData.append('invoice_date',el.invoiceDate.value);
    formData.append('invoice_number',el.invoiceNumber.value);
    formData.append('shipping_cost',el.shippingCost.value||'0');
    formData.append('other_charges',el.otherCharges.value||'0');
    formData.append('notes',el.notes.value||'');
    itemsBody.querySelectorAll('tr').forEach(function(row,index){
        row.querySelectorAll('[name]').forEach(function(input){
            const template=input.getAttribute('data-name-template');
            if(template){formData.append(template.replace(/__INDEX__/g,String(index)),input.value??'');}
        });
    });
    if(state.editId){formData.append('_method','PUT');}
    return formData;
}

function populateForm(grn){
    clearForm();
    state.editId=grn.id;
    el.title.textContent='Edit Purchase';
    el.warehouseId.value=grn.warehouse_id?String(grn.warehouse_id):'';
    el.supplierId.value=grn.supplier_id?String(grn.supplier_id):'';
    el.date.value=grn.grn_date||new Date().toISOString().slice(0,10);
    el.invoiceDate.value=grn.invoice_date||'';
    el.invoiceNumber.value=grn.invoice_number||'';
    el.shippingCost.value=Number(grn.shipping_cost||0).toFixed(2);
    el.otherCharges.value=Number(grn.other_charges||0).toFixed(2);
    el.notes.value=grn.notes||'';
    itemsBody.innerHTML='';state.rowIndex=0;
    if(Array.isArray(grn.items)&&grn.items.length){grn.items.forEach(function(item){addRow(item);});}
    resetEntryBuilder();
    recalculateTotals();
}

function loadPurchase(id){
    showMessage('','success');
    fetch(baseGrnUrl+'/'+id+'/data',{headers:{'Accept':'application/json'}})
        .then(parseResponse)
        .then(function(result){
            if(!result.ok){throw result.payload;}
            populateForm(result.payload.grn);
            openModal();
        })
        .catch(function(payload){
            showMessage(payload.message||'Unable to load purchase.','danger',extractErrors(payload));
        });
}

function hideEntryProductResults(){
    if(el.entryProductSearchResults){el.entryProductSearchResults.style.display='none';}
}
function renderEntryProductResults(results){
    if(!el.entryProductSearchResults){return;}
    if(!Array.isArray(results)||!results.length){
        el.entryProductSearchResults.innerHTML='<div class="p-2 text-muted">No products found</div>';
        el.entryProductSearchResults.style.display='block';
        return;
    }
    el.entryProductSearchResults.innerHTML=results.map(function(item){
        const id=String(item.id ?? '');
        const text=item.text ?? '';
        return '<button type="button" class="d-block text-start w-100 border-0 bg-white p-2" data-id="'+id+'" data-text="'+escapeHtml(text)+'">'+escapeHtml(text)+'</button>';
    }).join('');
    el.entryProductSearchResults.style.display='block';
}

let entryProductDebounceTimer=null;
function initEntryProductSearch(){
    if(!el.entryProductSearchInput){return;}
    el.entryProductSearchInput.addEventListener('input',function(){
        const term=(el.entryProductSearchInput.value||'').trim();
        if(term.length<2){
            hideEntryProductResults();
            return;
        }
        clearTimeout(entryProductDebounceTimer);
        entryProductDebounceTimer=setTimeout(function(){
            fetch(productSearchUrl+'?q='+encodeURIComponent(term)+'&limit=35',{headers:{'Accept':'application/json'}})
                .then(function(resp){if(!resp.ok){throw new Error('Product search failed');}return resp.json();})
                .then(function(data){
                    const results=Array.isArray(data.results)?data.results:[];
                    renderEntryProductResults(results);
                })
                .catch(function(){
                    renderEntryProductResults([]);
                });
        },250);
    });

    document.addEventListener('click',function(e){
        if(!el.entryProductSearchResults||!el.entryProductSearchInput){return;}
        const within = el.entryProductSearchResults.contains(e.target) || el.entryProductSearchInput.contains(e.target);
        if(!within){hideEntryProductResults();}
    });

    if(el.entryProductSearchResults){
        el.entryProductSearchResults.addEventListener('click',function(e){
            const btn=e.target.closest('button[data-id]');
            if(!btn){return;}
            const id=btn.getAttribute('data-id');
            const text=btn.getAttribute('data-text')||'';
            el.entryProductId.value=id;
            el.entryProductSearchInput.value=text;
            hideEntryProductResults();
            // reset variant/unit cost then load variants
            resetEntryBuilder();
            el.entryProductId.value=id;
            el.entryProductSearchInput.value=text;
            loadEntryVariants();
        });
    }
}

const openPurchaseModalButton=document.getElementById('btnOpenPurchaseModal');
if(openPurchaseModalButton){
    openPurchaseModalButton.addEventListener('click',function(){clearForm();openModal();});
}
document.getElementById('btnClosePurchaseModal').addEventListener('click',closeModal);
document.getElementById('btnCancelPurchase').addEventListener('click',closeModal);
modal.addEventListener('click',function(event){if(event.target===modal){closeModal();}});
document.addEventListener('keydown',function(event){if(event.key==='Escape'&&state.open){closeModal();}});
document.querySelectorAll('.js-edit-purchase').forEach(function(button){button.addEventListener('click',function(){loadPurchase(button.dataset.id);});});

document.getElementById('btnAddPurchaseItem').addEventListener('click',function(){
    const itemPayload=getEntryItemPayload();
    if(!itemPayload){
        alert('Please select a product before adding item.');
        el.entryProductSearchInput.focus();
        return;
    }
    if(parseNumber(itemPayload.quantity)<=0){
        alert('Please enter received quantity greater than zero.');
        el.entryQty.focus();
        return;
    }
    const selectionKey=String(itemPayload.product_id)+'::'+String(itemPayload.product_variant_id||'');
    if(!selectionKey){
        alert('Please select a product before adding item.');
        el.entryProductSearchInput.focus();
        return;
    }
    if(hasDuplicateSelection(selectionKey,null)){
        alert('This product/variant is already added.');
        return;
    }
    addRow(itemPayload);
    resetEntryBuilder();
    el.entryProductSearchInput.focus();
});

itemsBody.addEventListener('click',function(event){
    const removeButton=event.target.closest('.js-remove-row');
    if(!removeButton){return;}
    if(itemsBody.querySelectorAll('tr').length===1){return;}
    removeButton.closest('tr').remove();renumberRows();recalculateTotals();
});

itemsBody.addEventListener('change',function(event){
    const row=event.target.closest('tr');
    if(!row){return;}
    if(event.target.classList.contains('product-select')){
        row.querySelector('.unit-cost-input').value='';
        loadVariants(row);
        return;
    }
    if(event.target.classList.contains('variant-select')){
        const selectionKey=getRowSelectionKey(row);
        if(hasDuplicateSelection(selectionKey,row)){
            alert('This product/variant is already added.');
            event.target.value='';
            syncVariantMeta(row);
            recalculateTotals();
            return;
        }
        syncVariantMeta(row);
    }
    recalculateTotals();
});

itemsBody.addEventListener('input',function(event){if(event.target.closest('tr')){recalculateTotals();}});
el.shippingCost.addEventListener('input',recalculateTotals);
el.otherCharges.addEventListener('input',recalculateTotals);

el.entryVariantId.addEventListener('change',function(){
    const variant=entryVariantMap[String(el.entryVariantId.value)]||null;
    const product=products.find(function(item){return String(item.id)===String(el.entryProductId.value);})||null;
    if(!el.entryUnitCost.value||parseNumber(el.entryUnitCost.value)<=0){
        const cost=parseNumber(variant?.cost_price)||parseNumber(product?.default_cost);
        el.entryUnitCost.value=cost>0?cost.toFixed(2):'';
    }
});

form.addEventListener('submit',function(event){
    event.preventDefault();
    showMessage('','success');
    el.save.disabled=true;

    const url=state.editId?(baseGrnUrl+'/'+state.editId+'/update'):storeUrl;
    fetch(url,{
        method:'POST',
        headers:{
            'Accept':'application/json',
            'X-Requested-With':'XMLHttpRequest',
            'X-CSRF-TOKEN':csrfToken
        },
        body:buildFormData()
    })
    .then(parseResponse)
    .then(function(result){
        if(!result.ok){throw result.payload;}
        showMessage(result.payload.message||'Purchase saved successfully.','success');
        window.location.href=indexUrl;
    })
    .catch(function(payload){
        showMessage(payload.message||'Failed to save purchase.','danger',extractErrors(payload));
    })
    .finally(function(){el.save.disabled=false;});
});

clearForm();

const autoModal=@json(request('modal'));
const autoEditId=@json(request('edit'));
if(autoEditId){loadPurchase(autoEditId);}else if(autoModal==='create'){openModal();}

initEntryProductSearch();
});
</script>
@endsection
