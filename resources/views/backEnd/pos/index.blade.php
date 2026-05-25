@extends('backEnd.layouts.master')
@section('title', 'Point of Sale')
@section('css')
<style>
:root{--pos-primary:#1e293b;--pos-accent:#10b981;--pos-accent-hover:#059669;--pos-bg:#f1f5f9;--pos-card:#fff;--pos-border:#e2e8f0;--pos-text:#334155;--pos-muted:#94a3b8}
.content-page .content{padding:0!important;margin:0!important}.container-fluid{padding:0!important}
.footer{display:none!important}
.pos-wrapper{display:flex;gap:0;height:calc(100vh - 70px);overflow:hidden;background:var(--pos-bg)}
.pos-left{flex:0 0 45%;display:flex;flex-direction:column;border-right:1px solid var(--pos-border);background:var(--pos-card)}
.pos-right{flex:1;display:flex;flex-direction:column;background:var(--pos-bg)}
.pos-session-bar{background:var(--pos-primary);color:#fff;padding:10px 20px;display:flex;justify-content:space-between;align-items:center;font-size:.85rem;border-radius:0}
.pos-session-bar .badge{background:var(--pos-accent);font-size:.75rem;padding:4px 10px}
.pos-cart-area{flex:1;overflow-y:auto;padding:0}
.pos-cart-table{width:100%;border-collapse:collapse;font-size:.85rem}
.pos-cart-table thead{background:#f8fafc;position:sticky;top:0;z-index:2}
.pos-cart-table th{padding:10px 12px;font-weight:600;color:var(--pos-muted);text-transform:uppercase;font-size:.72rem;letter-spacing:.5px;border-bottom:2px solid var(--pos-border)}
.pos-cart-table td{padding:8px 12px;border-bottom:1px solid var(--pos-border);vertical-align:middle}
.pos-cart-table .cart-img{width:40px;height:40px;object-fit:cover;border-radius:6px;border:1px solid var(--pos-border)}
.pos-cart-table .qty-ctrl{display:flex;align-items:center;gap:4px}
.pos-cart-table .qty-ctrl button{width:26px;height:26px;border:1px solid var(--pos-border);background:#fff;border-radius:4px;font-size:.9rem;cursor:pointer;display:flex;align-items:center;justify-content:center}
.pos-cart-table .qty-ctrl button:hover{background:var(--pos-accent);color:#fff;border-color:var(--pos-accent)}
.pos-cart-table .qty-ctrl input{width:40px;text-align:center;border:1px solid var(--pos-border);border-radius:4px;font-size:.82rem;padding:2px}
.pos-cart-table .btn-remove-item{background:none;border:none;color:#ef4444;font-size:1.1rem;cursor:pointer;padding:2px 6px}
.pos-bottom{border-top:1px solid var(--pos-border);background:#fff}
.pos-customer-summary{display:flex;gap:0}
.pos-customer{flex:1;padding:16px 20px;border-right:1px solid var(--pos-border)}
.pos-customer h6{font-size:.8rem;font-weight:700;color:var(--pos-primary);margin-bottom:10px;text-transform:uppercase;letter-spacing:.5px}
.pos-customer .form-control{font-size:.82rem;padding:6px 10px;border-radius:6px;border:1px solid var(--pos-border)}
.pos-customer .form-control:focus{border-color:var(--pos-accent);box-shadow:0 0 0 2px rgba(16,185,129,.15)}
.pos-summary{flex:0 0 280px;padding:16px 20px}
.pos-summary-row{display:flex;justify-content:space-between;padding:5px 0;font-size:.85rem;color:var(--pos-text)}
.pos-summary-row.total{font-size:1.1rem;font-weight:700;color:var(--pos-primary);border-top:2px solid var(--pos-border);padding-top:10px;margin-top:6px}
.pos-summary-row .currency{color:var(--pos-accent);font-weight:600}
.btn-complete-sale{background:var(--pos-accent);color:#fff;border:none;padding:12px 0;width:100%;border-radius:8px;font-size:.95rem;font-weight:600;cursor:pointer;margin-top:12px;transition:all .2s}
.btn-complete-sale:hover{background:var(--pos-accent-hover);transform:translateY(-1px);box-shadow:0 4px 12px rgba(16,185,129,.3)}
.btn-complete-sale:disabled{opacity:.5;cursor:not-allowed;transform:none}
.pos-products-header{padding:12px 16px;background:#fff;border-bottom:1px solid var(--pos-border);display:flex;gap:10px;align-items:center}
.pos-products-header h5{margin:0;font-size:.9rem;font-weight:700;color:var(--pos-primary);white-space:nowrap}
.pos-search-input{flex:1;border:1px solid var(--pos-border);border-radius:8px;padding:8px 14px;font-size:.85rem;outline:none}
.pos-search-input:focus{border-color:var(--pos-accent);box-shadow:0 0 0 2px rgba(16,185,129,.15)}
.pos-products-grid{flex:1;overflow-y:auto;padding:12px;display:grid;grid-template-columns:repeat(2,1fr);gap:10px;align-content:start}
.pos-product-card{background:#fff;border:1px solid var(--pos-border);border-radius:10px;padding:10px;cursor:pointer;transition:all .2s;position:relative;display:flex;flex-direction:column;align-items:center;text-align:center}
.pos-product-card:hover{border-color:var(--pos-accent);box-shadow:0 4px 16px rgba(0,0,0,.08);transform:translateY(-2px)}
.pos-product-card img{width:70px;height:70px;object-fit:cover;border-radius:8px;margin-bottom:8px}
.pos-product-card .product-name{font-size:.78rem;font-weight:600;color:var(--pos-text);line-height:1.3;margin-bottom:4px;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
.pos-product-card .product-price{font-size:.85rem;font-weight:700;color:var(--pos-accent)}
.pos-product-card .stock-badge{position:absolute;top:6px;left:6px;font-size:.65rem;padding:2px 6px;border-radius:4px;font-weight:600}
.stock-badge.in-stock{background:#dcfce7;color:#16a34a}
.stock-badge.low-stock{background:#fef9c3;color:#ca8a04}
.stock-badge.out-stock{background:#fee2e2;color:#dc2626}
.pos-empty-cart{text-align:center;padding:60px 20px;color:var(--pos-muted)}
.pos-empty-cart i{font-size:3rem;margin-bottom:12px;display:block}
.btn-cart-clear{background:none;border:1px solid #ef4444;color:#ef4444;padding:4px 12px;border-radius:6px;font-size:.75rem;cursor:pointer}
.btn-cart-clear:hover{background:#ef4444;color:#fff}
.pos-loading{text-align:center;padding:40px;color:var(--pos-muted)}
.customer-suggestions{position:absolute;z-index:100;background:#fff;border:1px solid var(--pos-border);border-radius:6px;max-height:150px;overflow-y:auto;width:100%;box-shadow:0 4px 12px rgba(0,0,0,.1);display:none}
.customer-suggestions .suggestion-item{padding:8px 12px;cursor:pointer;font-size:.82rem;border-bottom:1px solid var(--pos-border)}
.customer-suggestions .suggestion-item:hover{background:#f0fdf4}
.pos-cart-empty-placeholder{width:40px;height:40px;background:var(--pos-border);border-radius:6px}
@media(max-width:1200px){.pos-left{flex:0 0 50%}.pos-products-grid{grid-template-columns:repeat(2,1fr)}}
@media(max-width:768px){.pos-wrapper{flex-direction:column;height:auto}.pos-left,.pos-right{flex:1 1 auto}.pos-customer-summary{flex-direction:column}}
</style>
@endsection

@section('content')
<div class="pos-wrapper">
  {{-- LEFT PANEL --}}
  <div class="pos-left">
    <div class="pos-session-bar">
      <div>
        <strong>POS Terminal</strong>
        <span class="ms-2 text-white-50">{{ Auth::user()->name }}</span>
      </div>
      <div>
        <span>Session</span>
        <span class="badge ms-1">SL-{{ date('ymdHi') }}-{{ rand(100,999) }}</span>
        <button class="btn-cart-clear ms-2" onclick="clearCart()" title="Clear Cart">🗑 Clear</button>
      </div>
    </div>

    <div class="pos-cart-area">
      <table class="pos-cart-table">
        <thead>
          <tr>
            <th style="width:50px">IMG</th>
            <th>ITEM</th>
            <th style="width:100px">QTY</th>
            <th style="width:80px">PRICE</th>
            <th style="width:70px">DISC</th>
            <th style="width:90px">SUBTOTAL</th>
            <th style="width:36px"></th>
          </tr>
        </thead>
        <tbody id="pos-cart-body">
          <tr id="pos-empty-row">
            <td colspan="7">
              <div class="pos-empty-cart">
                <i class="fe-shopping-cart"></i>
                <p>Cart is empty. Click products to add.</p>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div class="pos-bottom">
      <div class="pos-customer-summary">
        <div class="pos-customer">
          <h6>Customer</h6>
          <div class="row g-2">
            <div class="col-6 position-relative">
              <input type="text" id="pos-customer-name" class="form-control" placeholder="Customer Name">
            </div>
            <div class="col-6 position-relative">
              <input type="text" id="pos-customer-phone" class="form-control" placeholder="Mobile Number" autocomplete="off">
              <div class="customer-suggestions" id="customer-suggestions"></div>
            </div>
            <div class="col-6">
              <input type="text" id="pos-customer-address" class="form-control" placeholder="Address">
            </div>
            <div class="col-6">
              <select id="pos-delivery-area" class="form-control" onchange="updateShippingFee()">
                <option value="" data-amount="0">Select Delivery Area</option>
                @foreach($shippingCharges as $charge)
                  <option value="{{ $charge->name }}" data-amount="{{ $charge->amount }}">{{ $charge->name }}</option>
                @endforeach
              </select>
            </div>
          </div>
        </div>
        <div class="pos-summary">
          <div class="pos-summary-row">
            <span>Sub Total</span>
            <span class="currency" id="pos-subtotal">৳0</span>
          </div>
          <div class="pos-summary-row">
            <span>Shipping Fee</span>
            <span><input type="number" id="pos-shipping" value="0" min="0" style="width:70px;text-align:right;border:1px solid var(--pos-border);border-radius:4px;font-size:.82rem;padding:2px 6px"></span>
          </div>
          <div class="pos-summary-row">
            <span>Discount</span>
            <span><input type="number" id="pos-discount" value="0" min="0" style="width:70px;text-align:right;border:1px solid var(--pos-border);border-radius:4px;font-size:.82rem;padding:2px 6px"></span>
          </div>
          <div class="pos-summary-row total">
            <span>Grand Total</span>
            <span class="currency" id="pos-grand-total">৳0</span>
          </div>
          <button class="btn-complete-sale" id="btn-complete-sale" disabled onclick="completeSale()">Complete Sale</button>
        </div>
      </div>
    </div>
  </div>

  {{-- RIGHT PANEL --}}
  <div class="pos-right">
    <div class="pos-products-header">
      <h5>PRODUCTS</h5>
      <input type="search" class="pos-search-input" id="pos-product-search" placeholder="Search product by name..." autocomplete="off">
    </div>
    <div class="pos-products-grid" id="pos-products-grid">
      <div class="pos-loading">Loading products...</div>
    </div>
  </div>
</div>
@endsection

@section('script')
<script>
(function(){
  var cart = [];
  var searchTimer = null;
  var productsUrl = @json(route('admin.pos.products'));
  var customersUrl = @json(route('admin.pos.customers'));
  var completeSaleUrl = @json(route('admin.pos.complete-sale'));
  var csrfToken = document.querySelector('meta[name="csrf-token"]').content;
  var currentPage = 1;
  var lastPage = 1;
  var isLoading = false;
  var currentSearch = '';
  var noImagePlaceholder = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iODAiIGhlaWdodD0iODAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PHJlY3Qgd2lkdGg9IjgwIiBoZWlnaHQ9IjgwIiBmaWxsPSIjZTJlOGYwIi8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGRvbWluYW50LWJhc2VsaW5lPSJtaWRkbGUiIHRleHQtYW5jaG9yPSJtaWRkbGUiIGZpbGw9IiM5NGEzYjgiIGZvbnQtc2l6ZT0iMTIiPk5vIEltZzwvdGV4dD48L3N2Zz4=';

  // Load products
  function loadProducts(page, append){
    if(isLoading) return;
    isLoading = true;
    var grid = document.getElementById('pos-products-grid');
    if(!append) grid.innerHTML = '<div class="pos-loading">Loading...</div>';

    var url = productsUrl + '?page=' + page + (currentSearch ? '&q=' + encodeURIComponent(currentSearch) : '');
    fetch(url).then(function(r){return r.json()}).then(function(res){
      isLoading = false;
      if(!append) grid.innerHTML = '';
      if(res.success && res.data.length){
        currentPage = res.current_page;
        lastPage = res.last_page;
        res.data.forEach(function(p){
          var stockClass = p.stock > 10 ? 'in-stock' : (p.stock > 0 ? 'low-stock' : 'out-stock');
          var stockText = 'Stock: ' + (p.stock || 0);
          var card = document.createElement('div');
          card.className = 'pos-product-card';
          card.onclick = function(){ addToCart(p); };
          card.innerHTML = '<span class="stock-badge '+stockClass+'">'+stockText+'</span>'
            + '<img src="'+(p.image || noImagePlaceholder)+'" alt="'+p.name+'" onerror="this.src=\''+noImagePlaceholder+'\'">'
            + '<div class="product-name">'+p.name+'</div>'
            + '<div class="product-price">TK '+Number(p.price).toLocaleString()+'</div>';
          grid.appendChild(card);
        });
      } else if(!append){
        grid.innerHTML = '<div class="pos-loading">No products found.</div>';
      }
    }).catch(function(){
      isLoading = false;
      if(!append) grid.innerHTML = '<div class="pos-loading">Failed to load products.</div>';
    });
  }

  // Scroll to load more
  document.getElementById('pos-products-grid').addEventListener('scroll', function(){
    var el = this;
    if(el.scrollTop + el.clientHeight >= el.scrollHeight - 50 && currentPage < lastPage && !isLoading){
      loadProducts(currentPage + 1, true);
    }
  });

  // Search
  document.getElementById('pos-product-search').addEventListener('input', function(){
    var q = this.value.trim();
    clearTimeout(searchTimer);
    searchTimer = setTimeout(function(){
      currentSearch = q;
      currentPage = 1;
      loadProducts(1, false);
    }, 350);
  });

  // Add to cart
  function addToCart(product){
    var existing = cart.find(function(c){return c.product_id === product.id});
    if(existing){
      existing.qty++;
    } else {
      cart.push({
        product_id: product.id,
        name: product.name,
        price: product.price,
        image: product.image,
        qty: 1,
        discount: 0,
        stock: product.stock
      });
    }
    renderCart();
  }

  // Render cart
  function renderCart(){
    var tbody = document.getElementById('pos-cart-body');
    var btn = document.getElementById('btn-complete-sale');
    if(!cart.length){
      tbody.innerHTML = '<tr id="pos-empty-row"><td colspan="7"><div class="pos-empty-cart"><i class="fe-shopping-cart"></i><p>Cart is empty. Click products to add.</p></div></td></tr>';
      btn.disabled = true;
      updateTotals();
      return;
    }
    btn.disabled = false;
    var html = '';
    cart.forEach(function(item, idx){
      var lineTotal = (item.price * item.qty) - item.discount;
      html += '<tr>'
        + '<td><img class="cart-img" src="'+(item.image||noImagePlaceholder)+'" onerror="this.src=\''+noImagePlaceholder+'\'"></td>'
        + '<td><strong style="font-size:.82rem">'+item.name+'</strong></td>'
        + '<td><div class="qty-ctrl">'
        + '<button onclick="posQty('+idx+',-1)">−</button>'
        + '<input type="number" value="'+item.qty+'" min="1" onchange="posQtySet('+idx+',this.value)">'
        + '<button onclick="posQty('+idx+',1)">+</button>'
        + '</div></td>'
        + '<td>৳'+Number(item.price).toLocaleString()+'</td>'
        + '<td><input type="number" value="'+item.discount+'" min="0" style="width:55px;font-size:.8rem;border:1px solid var(--pos-border);border-radius:4px;padding:2px 4px;text-align:right" onchange="posDisc('+idx+',this.value)"></td>'
        + '<td><strong>৳'+Math.max(0,lineTotal).toLocaleString()+'</strong></td>'
        + '<td><button class="btn-remove-item" onclick="posRemove('+idx+')" title="Remove">×</button></td>'
        + '</tr>';
    });
    tbody.innerHTML = html;
    updateTotals();
  }

  function updateTotals(){
    var subtotal = 0;
    cart.forEach(function(item){
      subtotal += Math.max(0, (item.price * item.qty) - item.discount);
    });
    var shipping = parseFloat(document.getElementById('pos-shipping').value) || 0;
    var discount = parseFloat(document.getElementById('pos-discount').value) || 0;
    var grand = Math.max(0, subtotal + shipping - discount);
    document.getElementById('pos-subtotal').textContent = '৳' + subtotal.toLocaleString();
    document.getElementById('pos-grand-total').textContent = '৳' + grand.toLocaleString();
  }

  // Cart controls (global)
  window.posQty = function(idx, delta){
    if(!cart[idx]) return;
    cart[idx].qty = Math.max(1, cart[idx].qty + delta);
    renderCart();
  };
  window.posQtySet = function(idx, val){
    if(!cart[idx]) return;
    cart[idx].qty = Math.max(1, parseInt(val) || 1);
    renderCart();
  };
  window.posDisc = function(idx, val){
    if(!cart[idx]) return;
    cart[idx].discount = Math.max(0, parseFloat(val) || 0);
    renderCart();
  };
  window.posRemove = function(idx){
    cart.splice(idx, 1);
    renderCart();
  };
  window.clearCart = function(){
    if(cart.length && !confirm('Clear all items from cart?')) return;
    cart = [];
    renderCart();
  };

  // Shipping / discount change
  document.getElementById('pos-shipping').addEventListener('input', updateTotals);
  document.getElementById('pos-discount').addEventListener('input', updateTotals);

  window.updateShippingFee = function() {
    var select = document.getElementById('pos-delivery-area');
    var amount = 0;
    if (select.selectedIndex > 0) {
      amount = select.options[select.selectedIndex].getAttribute('data-amount');
    }
    document.getElementById('pos-shipping').value = amount;
    updateTotals();
  };

  // Customer search
  var phoneInput = document.getElementById('pos-customer-phone');
  var suggestionsBox = document.getElementById('customer-suggestions');
  var customerTimer = null;
  phoneInput.addEventListener('input', function(){
    var q = this.value.trim();
    clearTimeout(customerTimer);
    if(q.length < 3){ suggestionsBox.style.display='none'; return; }
    customerTimer = setTimeout(function(){
      fetch(customersUrl + '?q=' + encodeURIComponent(q))
        .then(function(r){return r.json()})
        .then(function(res){
          if(res.success && res.data.length){
            suggestionsBox.innerHTML = '';
            res.data.forEach(function(c){
              var div = document.createElement('div');
              div.className = 'suggestion-item';
              div.textContent = c.name + ' - ' + c.phone;
              div.onclick = function(){
                document.getElementById('pos-customer-name').value = c.name;
                phoneInput.value = c.phone;
                document.getElementById('pos-customer-address').value = c.address || '';
                document.getElementById('pos-delivery-area').value = c.area || '';
                suggestionsBox.style.display='none';
              };
              suggestionsBox.appendChild(div);
            });
            suggestionsBox.style.display='block';
          } else {
            suggestionsBox.style.display='none';
          }
        });
    }, 400);
  });
  document.addEventListener('click', function(e){
    if(!suggestionsBox.contains(e.target) && e.target !== phoneInput){
      suggestionsBox.style.display='none';
    }
  });

  // Complete sale
  window.completeSale = function(){
    if(!cart.length) return;
    var btn = document.getElementById('btn-complete-sale');
    btn.disabled = true;
    btn.textContent = 'Processing...';

    var items = cart.map(function(c){
      return {product_id:c.product_id, qty:c.qty, price:c.price, discount:c.discount};
    });

    fetch(completeSaleUrl, {
      method: 'POST',
      headers: {'Content-Type':'application/json','X-CSRF-TOKEN':csrfToken,'Accept':'application/json'},
      body: JSON.stringify({
        items: items,
        customer_name: document.getElementById('pos-customer-name').value,
        customer_phone: document.getElementById('pos-customer-phone').value,
        customer_address: document.getElementById('pos-customer-address').value,
        delivery_area: document.getElementById('pos-delivery-area').value,
        shipping_fee: parseFloat(document.getElementById('pos-shipping').value) || 0,
        discount: parseFloat(document.getElementById('pos-discount').value) || 0,
        payment_method: 'Cash'
      })
    })
    .then(function(r){return r.json()})
    .then(function(res){
      btn.textContent = 'Complete Sale';
      if(res.success){
        alert('✅ Sale Completed!\nInvoice: ' + res.invoice_id + '\nTotal: ৳' + Number(res.grand_total).toLocaleString());
        cart = [];
        renderCart();
        document.getElementById('pos-customer-name').value = '';
        document.getElementById('pos-customer-phone').value = '';
        document.getElementById('pos-customer-address').value = '';
        document.getElementById('pos-delivery-area').value = '';
        document.getElementById('pos-shipping').value = '0';
        document.getElementById('pos-discount').value = '0';
        loadProducts(1, false);
      } else {
        alert('❌ Error: ' + (res.message || 'Sale failed'));
        btn.disabled = false;
      }
    })
    .catch(function(e){
      btn.textContent = 'Complete Sale';
      btn.disabled = false;
      alert('❌ Network error. Please try again.');
    });
  };

  // Init
  loadProducts(1, false);
})();
</script>
@endsection
