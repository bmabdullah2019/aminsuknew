<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8" />

    <title>@yield('title') - {{$generalsetting->name}}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
    <!-- App favicon -->
    <link rel="shortcut icon" href="{{asset($generalsetting->favicon)}}" />

    <!-- Bootstrap css -->
    <link href="{{asset('public/backEnd/')}}/assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
    <!-- App css -->
    <link href="{{asset('public/backEnd/')}}/assets/css/app.min.css" rel="stylesheet" type="text/css" id="app-style" />
    <!-- icons -->
    <link href="{{asset('public/backEnd/')}}/assets/css/icons.min.css" rel="stylesheet" type="text/css" />
    <!-- toastr css -->
    <link rel="stylesheet" href="{{asset('public/backEnd/')}}/assets/css/toastr.min.css" />
    <!-- custom css -->
    <link href="{{asset('public/backEnd/')}}/assets/css/custom.css" rel="stylesheet" type="text/css" />
    <link href="{{asset('public/backEnd/')}}/assets/css/worldclass-admin.css?v=2026.05.11" rel="stylesheet" type="text/css" />
    
    <!-- Head js -->
    @yield('css')
    <script src="{{asset('public/backEnd/')}}/assets/js/head.js"></script>
  </head>

  <!-- body start -->
  <body class="wc-admin-shell" data-layout-mode="default" data-theme="light" data-layout-width="fluid" data-topbar-color="dark" data-menu-position="fixed" data-leftbar-color="light" data-leftbar-size="default" data-sidebar-user="false">
    <!-- License validation disabled -->
    <!-- Begin page -->
    <div id="wrapper">
      <!-- Topbar Start -->
      <div class="navbar-custom">
        <div class="container-fluid">
          <ul class="list-unstyled topnav-menu float-end mb-0">
            <li class="dropdown d-inline-block d-lg-none">
              <a class="nav-link dropdown-toggle arrow-none waves-effect waves-light" data-bs-toggle="dropdown" href="#" role="button" aria-haspopup="false" aria-expanded="false">
                <i class="fe-search noti-icon"></i>
              </a>
              <div class="dropdown-menu dropdown-lg dropdown-menu-end p-0">
                <form class="p-3">
                  <input type="text" class="form-control" placeholder="Search ..." aria-label="Recipient's username" />
                </form>
              </div>
            </li>

            <li class="dropdown d-none d-lg-inline-block">
              <a class="nav-link dropdown-toggle arrow-none waves-effect waves-light" data-toggle="fullscreen" href="#">
                <i class="fe-maximize noti-icon"></i>
              </a>
            </li>

            <li class="dropdown notification-list topbar-dropdown">
              <a class="nav-link dropdown-toggle waves-effect waves-light" data-bs-toggle="dropdown" href="#" role="button" aria-haspopup="false" aria-expanded="false">
                <i class="fe-bell noti-icon"></i>
                <span class="badge bg-danger rounded-circle noti-icon-badge">{{$neworder}}</span>
              </a>
              <div class="dropdown-menu dropdown-menu-end dropdown-lg">
                <!-- item-->
                <div class="dropdown-item noti-title">
                  <h5 class="m-0">
                    <span class="float-end">
                      <a href="{{route('admin.orders',['slug'=>'pending'])}}" class="text-dark">
                        <small>View All</small>
                      </a>
                    </span>
                    Orders
                  </h5>
                </div>

                <div class="noti-scroll" data-simplebar>
                  @foreach($pendingorder as $porder)
                  <!-- item-->
                  <a href="{{route('admin.orders',['slug'=>'pending'])}}" class="dropdown-item notify-item active">
                    <div class="notify-icon">
                      <img src="{{asset($porder->customer?$porder->customer->image:'')}}" class="img-fluid rounded-circle" alt="" />
                    </div>
                    <p class="notify-details">{{$porder->customer?$porder->customer->name:''}}</p>
                    <p class="text-muted mb-0 user-msg">
                      <small>Invoice : {{$porder->invoice_id}}</small>
                    </p>
                  </a>
                  @endforeach

                  <!-- item-->
                </div>

                <!-- All-->
                <a href="{{route('admin.orders',['slug'=>'pending'])}}" class="dropdown-item text-center text-primary notify-item notify-all">
                  View all
                  <i class="fe-arrow-right"></i>
                </a>
              </div>
            </li>

            <li class="dropdown notification-list topbar-dropdown">
              <a class="nav-link dropdown-toggle nav-user me-0 waves-effect waves-light" data-bs-toggle="dropdown" href="#" role="button" aria-haspopup="false" aria-expanded="false">
                <img src="{{asset(Auth::user()->image)}}" alt="user-image" class="rounded-circle" />
                <span class="pro-user-name ms-1"> {{Auth::user()->name}} <i class="mdi mdi-chevron-down"></i> </span>
              </a>
              <div class="dropdown-menu dropdown-menu-end profile-dropdown">
                <!-- item-->
                <div class="dropdown-header noti-title">
                  <h6 class="text-overflow m-0">Welcome !</h6>
                </div>

                <!-- item-->
                <a href="{{route('admin.dashboard')}}" class="dropdown-item notify-item">
                  <i class="fe-user"></i>
                  <span>Dashboard</span>
                </a>

                <!-- item-->
                <a href="{{route('admin.change_password')}}" class="dropdown-item notify-item">
                  <i class="fe-settings"></i>
                  <span>Change Password</span>
                </a>

                <!-- item-->
                <a href="{{route('locked')}}" class="dropdown-item notify-item">
                  <i class="fe-lock"></i>
                  <span>Lock Screen</span>
                </a>

                <div class="dropdown-divider"></div>

                <!-- item-->
                <a
                  href="{{ route('logout') }}"
                  onclick="event.preventDefault();
                  document.getElementById('logout-form').submit();"
                  class="dropdown-item notify-item"
                >
                  <i class="fe-log-out me-1"></i>
                  <span>Logout</span>
                </a>
                <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                  @csrf
                </form>
              </div>
            </li>

            <!--<li class="dropdown notification-list">-->
            <!--    <a href="javascript:void(0);" class="nav-link right-bar-toggle waves-effect waves-light">-->
            <!--        <i class="fe-settings noti-icon"></i>-->
            <!--    </a>-->
            <!--</li>-->
          </ul>

          <!-- LOGO -->
          <div class="logo-box">
            <a href="{{url('admin/dashboard')}}" class="logo text-center">
              <span class="logo-sm">
                <img src="{{asset($generalsetting->dark_logo ?? $generalsetting->white_logo)}}" alt="{{$generalsetting->name}}" height="30" />
              </span>
              <span class="logo-lg">
                <img src="{{asset($generalsetting->dark_logo ?? $generalsetting->white_logo)}}" alt="{{$generalsetting->name}}" height="35" />
              </span>
            </a>
          </div>

          <ul class="list-unstyled topnav-menu topnav-menu-left m-0">
            <li>
              <button class="button-menu-mobile waves-effect waves-light">
                <i class="fe-menu"></i>
              </button>
            </li>

            <li>
              <!-- Mobile menu toggle (Horizontal Layout)-->
              <a class="navbar-toggle nav-link" data-bs-toggle="collapse" data-bs-target="#topnav-menu-content">
                <div class="lines">
                  <span></span>
                  <span></span>
                  <span></span>
                </div>
              </a>
              <!-- End mobile menu toggle-->
            </li>

            <li class="dropdown d-none d-xl-block">
              <a class="nav-link dropdown-toggle waves-effect waves-light" href="{{route('home')}}" target="_blank"> <i data-feather="globe"></i> Visit Site </a>
            </li>
          </ul>
          <div class="clearfix"></div>
        </div>
      </div>
      <!-- end Topbar -->

      <!-- ========== Left Sidebar Start ========== -->
      <div class="left-side-menu">
        <div class="h-100" data-simplebar>
          <!-- User box -->
          <div class="user-box text-center">
            <div class="position-relative d-inline-block">
              @if(Auth::user()->image && file_exists(public_path(Auth::user()->image)))
                <img src="{{asset(Auth::user()->image)}}" alt="{{Auth::user()->name}}" class="rounded-circle avatar-lg border border-2 border-white" style="width: 80px; height: 80px; object-fit: cover;" />
              @else
                <div class="rounded-circle avatar-lg border border-2 border-white d-flex align-items-center justify-content-center" style="width: 80px; height: 80px; background: #f2efff; font-size: 2rem; font-weight: bold; color: #6d4aff;">
                  {{strtoupper(substr(Auth::user()->name, 0, 1))}}
                </div>
              @endif
              <span class="position-absolute bottom-0 end-0 bg-success rounded-circle p-1" style="width: 20px; height: 20px; border: 2px solid white;"></span>
            </div>
            
            <div class="dropdown mt-3">
              <a href="javascript: void(0);" class="dropdown-toggle h6 mt-2 mb-1 d-block" data-bs-toggle="dropdown" style="color: #111827 !important; text-decoration: none;">
                <strong>{{Auth::user()->name}}</strong>
              </a>
              <div class="dropdown-menu user-pro-dropdown">
                <!-- item-->
                <a href="{{route('admin.change_password')}}" class="dropdown-item notify-item">
                  <i class="fe-user me-2"></i>
                  <span>My Account</span>
                </a>

                <!-- item-->
                <a href="javascript:void(0);" class="dropdown-item notify-item">
                  <i class="fe-settings me-2"></i>
                  <span>Settings</span>
                </a>

                <!-- item-->
                <a href="{{route('locked')}}" class="dropdown-item notify-item">
                  <i class="fe-lock me-2"></i>
                  <span>Lock Screen</span>
                </a>
                
                <div class="dropdown-divider"></div>

                <!-- item-->
                <a
                  href="{{ route('logout') }}"
                  onclick="event.preventDefault();
                                                     document.getElementById('logout-form').submit();"
                  class="dropdown-item notify-item text-danger"
                >
                  <i class="fe-log-out me-2"></i>
                  <span>Logout</span>
                </a>
                <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                  @csrf
                </form>
              </div>
            </div>
            <p style="color: #737d90 !important; font-size: 0.8rem; margin-bottom: 0;">
              <i class="fe-shield me-1"></i>
              Administrator
            </p>
            <small style="color: #9aa3b2; font-size: 0.75rem;">
              Online <span class="text-success">&bull;</span>
            </small>
          </div>

          <!--- Sidemenu -->
          <div id="sidebar-menu">
            <ul id="side-menu">
              <li class="wc-menu-utility">
                <div class="wc-menu-search-wrap">
                  <i data-feather="search" class="wc-menu-search-icon"></i>
                  <input
                    type="search"
                    id="sidebar-menu-search"
                    class="form-control wc-menu-search-input"
                    placeholder="Search"
                    autocomplete="off"
                    aria-label="Search dashboard menu"
                  />
                  <button type="button" id="sidebar-menu-search-clear" class="wc-menu-search-clear d-none" aria-label="Clear menu search">&times;</button>
                </div>
                <div class="wc-quick-actions" role="navigation" aria-label="Quick actions">
                  <a href="{{route('admin.products.create')}}" class="wc-quick-action-link">
                    <i data-feather="plus-square"></i>
                    <span>New Product</span>
                  </a>
                  <a href="{{route('admin.grn.index')}}" class="wc-quick-action-link">
                    <i data-feather="truck"></i>
                    <span>New Purchase</span>
                  </a>
                  <a href="{{route('admin.orders',['slug'=>'pending'])}}" class="wc-quick-action-link">
                    <i data-feather="shopping-cart"></i>
                    <span>Pending Orders</span>
                  </a>
                  <a href="{{route('admin.stock.alerts')}}" class="wc-quick-action-link">
                    <i data-feather="alert-triangle"></i>
                    <span>Stock Alerts</span>
                  </a>
                </div>
                <p id="wc-menu-search-empty" class="wc-menu-search-empty d-none">No matching menu item. Try a broader keyword.</p>
              </li>

              <!-- Dashboard -->
              <li class="menu-title">Main</li>
              <li>
                <a href="{{url('admin/dashboard')}}">
                  <i data-feather="home"></i>
                  <span> Dashboard </span>
                  @if(isset($low_stock_count) && $low_stock_count > 0)
                    <span class="badge bg-warning rounded-pill ms-1">{{$low_stock_count}}</span>
                  @endif
                </a>
              </li>

              <li>
                <a href="{{route('admin.pos.index')}}">
                  <i data-feather="monitor"></i>
                  <span> POS System </span>
                </a>
              </li>

              <!-- ERP Core Navigation -->
              @php
                try {
                  $activeAlerts = \App\Models\StockAlert::where('status', 'active')->count();
                } catch (\Exception $e) {
                  $activeAlerts = 0;
                }

                try {
                  $orderMenuStatuses = \App\Models\OrderStatus::query()
                    ->select('name', 'slug', 'status')
                    ->where(function ($query) {
                      $query->where('status', 'active')
                        ->orWhere('status', '1')
                        ->orWhere('status', 1);
                    })
                    ->orderBy('id')
                    ->get();
                } catch (\Exception $e) {
                  $orderMenuStatuses = collect([
                    (object) ['name' => 'Pending', 'slug' => 'pending'],
                    (object) ['name' => 'Confirmed', 'slug' => 'confirmed'],
                    (object) ['name' => 'Processing', 'slug' => 'processing'],
                    (object) ['name' => 'Shipped', 'slug' => 'shipped'],
                    (object) ['name' => 'Delivered', 'slug' => 'delivered'],
                    (object) ['name' => 'Cancelled', 'slug' => 'cancelled'],
                    (object) ['name' => 'Returned', 'slug' => 'returned'],
                    (object) ['name' => 'Refunded', 'slug' => 'refunded'],
                  ]);
                }
              @endphp
              <li class="menu-title">ERP Core</li>
              @canany(['product-list', 'category-list', 'brand-list', 'supplier-list', 'grn-list', 'warehouse-list', 'stock-list', 'transfer-list', 'adjustment-list', 'loss-list', 'inventory-view', 'attribute-list'])
              <li class="{{ request()->is('admin/inventory*') || request()->is('admin/products*') || request()->is('admin/supplier*') || request()->is('admin/grn*') || request()->is('admin/warehouse*') || request()->is('admin/stock*') || request()->is('admin/transfer*') || request()->is('admin/adjustment*') || request()->is('admin/loss*') ? 'mm-active' : '' }}">
                <a href="javascript:void(0);" data-bs-toggle="collapse" data-bs-target="#sidebar-erp-inventory" aria-expanded="{{ request()->is('admin/inventory*') || request()->is('admin/products*') || request()->is('admin/supplier*') || request()->is('admin/grn*') || request()->is('admin/warehouse*') || request()->is('admin/stock*') || request()->is('admin/transfer*') || request()->is('admin/adjustment*') || request()->is('admin/loss*') ? 'true' : 'false' }}">
                  <i data-feather="archive"></i>
                  <span> Inventory </span>
                  @if(isset($activeAlerts) && $activeAlerts > 0)
                    <span class="badge bg-danger rounded-pill ms-1">{{$activeAlerts}}</span>
                  @endif
                  <span class="menu-arrow"></span>
                </a>
                <div class="collapse {{ request()->is('admin/inventory*') || request()->is('admin/products*') || request()->is('admin/supplier*') || request()->is('admin/grn*') || request()->is('admin/warehouse*') || request()->is('admin/stock*') || request()->is('admin/transfer*') || request()->is('admin/adjustment*') || request()->is('admin/loss*') ? 'show' : '' }}" id="sidebar-erp-inventory">
                  <ul class="nav-second-level">
                    <li>
                      <a href="{{route('admin.inventory.index')}}"><i data-feather="grid"></i> Inventory Dashboard</a>
                    </li>
                    <li>
                      <a href="javascript:void(0);" data-bs-toggle="collapse" data-bs-target="#sidebar-erp-inventory-products">
                        <i data-feather="box"></i> Product Catalog
                        <span class="menu-arrow"></span>
                      </a>
                      <div class="collapse" id="sidebar-erp-inventory-products">
                        <ul class="nav-second-level">
                          <li>
                            <a href="{{route('admin.products.index')}}">Product Manage</a>
                          </li>
                          <li>
                            <a href="{{route('admin.products.create')}}">New Product</a>
                          </li>
                          <li>
                            <a href="{{route('admin.products.create')}}?product_type=variable">New Variable Product</a>
                          </li>
                          <li>
                            <a href="{{route('admin.catalog-attributes.index')}}">Catalog Attributes</a>
                          </li>
                          <li>
                            <a href="{{route('admin.products.price_edit')}}">Price Edit</a>
                          </li>
                          <li>
                            <a href="{{route('admin.categories.index')}}">Categories</a>
                          </li>
                          <li>
                            <a href="{{route('admin.subcategories.index')}}">Sub Categories</a>
                          </li>
                          <li>
                            <a href="{{route('admin.childcategories.index')}}">Child Categories</a>
                          </li>
                          <li>
                            <a href="{{route('admin.brands.index')}}">Brands</a>
                          </li>
                        </ul>
                      </div>
                    </li>
                    <li>
                      <a href="javascript:void(0);" data-bs-toggle="collapse" data-bs-target="#sidebar-erp-inventory-purchase">
                        <i data-feather="shopping-bag"></i> Purchase
                        <span class="menu-arrow"></span>
                      </a>
                      <div class="collapse {{ request()->is('admin/supplier*') || request()->is('admin/grn*') ? 'show' : '' }}" id="sidebar-erp-inventory-purchase">
                        <ul class="nav-second-level">
                          <li>
                            <a href="{{route('admin.supplier.index')}}">Supplier</a>
                          </li>
                          <li>
                            <a href="{{route('admin.grn.index')}}">Purchase</a>
                          </li>
                          <li>
                            <a href="{{route('admin.supplier.purchase-returns.overview')}}">Purchase Return</a>
                          </li>
                          <li>
                            <a href="{{route('admin.supplier.payments.overview')}}">Bill Payment</a>
                          </li>
                          <li>
                            <a href="{{route('admin.supplier.adjustments.index')}}">Supplier Adjustment</a>
                          </li>
                        </ul>
                      </div>
                    </li>
                    <li>
                      <a href="{{route('admin.warehouse.index')}}"><i data-feather="home"></i> Warehouses</a>
                    </li>
                    <li>
                      <a href="{{route('admin.stock.inventory')}}"><i data-feather="package"></i> Stock Overview</a>
                    </li>
                    <li>
                      <a href="{{route('admin.transfer.index')}}"><i data-feather="repeat"></i> Stock Transfer</a>
                    </li>
                    <li>
                      <a href="javascript:void(0);" data-bs-toggle="collapse" data-bs-target="#sidebar-erp-inventory-controls">
                        <i data-feather="sliders"></i> Stock Controls
                        <span class="menu-arrow"></span>
                      </a>
                      <div class="collapse" id="sidebar-erp-inventory-controls">
                        <ul class="nav-second-level">
                          <li>
                            <a href="{{route('admin.stock.movements')}}">Stock Movements</a>
                          </li>
                          <li>
                            <a href="{{route('admin.stock.alerts')}}">Stock Alerts</a>
                          </li>
                          <li>
                            <a href="{{route('admin.stock.dead-stock')}}">Dead Stock</a>
                          </li>
                          <li>
                            <a href="{{route('admin.stock.audit')}}">Stock Audit</a>
                          </li>
                          <li>
                            <a href="{{route('admin.adjustment.index')}}">Stock Adjustments</a>
                          </li>
                          <li>
                            <a href="{{route('admin.loss.index')}}">Stock Loss</a>
                          </li>
                        </ul>
                      </div>
                    </li>
                  </ul>
                </div>
              </li>
              @endcanany

              @canany(['customer-list', 'order-view', 'return-management-view', 'partial-order-list'])
              <li class="{{ request()->is('admin/customers*') || request()->is('admin/order*') || request()->is('admin/returns*') || request()->is('admin/partial-orders*') ? 'mm-active' : '' }}">
                <a href="javascript:void(0);" data-bs-toggle="collapse" data-bs-target="#sidebar-erp-sales" aria-expanded="{{ request()->is('admin/customers*') || request()->is('admin/order*') || request()->is('admin/returns*') || request()->is('admin/partial-orders*') ? 'true' : 'false' }}">
                  <i data-feather="shopping-cart"></i>
                  <span> Sales </span>
                  @if($neworder > 0)
                    <span class="badge bg-danger rounded-pill ms-1">{{$neworder}}</span>
                  @endif
                  <span class="menu-arrow"></span>
                </a>
                <div class="collapse {{ request()->is('admin/customers*') || request()->is('admin/order*') || request()->is('admin/returns*') || request()->is('admin/partial-orders*') ? 'show' : '' }}" id="sidebar-erp-sales">
                  <ul class="nav-second-level">
                    <li>
                      <a href="{{route('admin.customers.index')}}"><i data-feather="user"></i> Customers</a>
                    </li>
                    <li>
                      <a href="javascript:void(0);" data-bs-toggle="collapse" data-bs-target="#sidebar-erp-sales-orders">
                        <i data-feather="file-text"></i> Orders
                        <span class="menu-arrow"></span>
                      </a>
                      <div class="collapse" id="sidebar-erp-sales-orders">
                        <ul class="nav-second-level">
                          <li>
                            <a href="{{route('admin.orders',['slug'=>'all'])}}">All Orders</a>
                          </li>
                          @foreach($orderMenuStatuses as $orderMenuStatus)
                            <li>
                              <a href="{{route('admin.orders',['slug' => $orderMenuStatus->slug])}}">{{$orderMenuStatus->name}}</a>
                            </li>
                          @endforeach
                          <li>
                            <a href="{{route('admin.partial-orders.index')}}">Partial Orders</a>
                          </li>
                        </ul>
                      </div>
                    </li>
                    <li>
                      <a href="javascript:void(0);" data-bs-toggle="collapse" data-bs-target="#sidebar-erp-sales-returns">
                        <i data-feather="rotate-ccw"></i> Returns
                        <span class="menu-arrow"></span>
                      </a>
                      <div class="collapse" id="sidebar-erp-sales-returns">
                        <ul class="nav-second-level">
                          <li>
                            <a href="{{route('admin.returns.index')}}">All Returns</a>
                          </li>
                          <li>
                            <a href="{{route('admin.returns.dashboard')}}">Returns Dashboard</a>
                          </li>
                          <li>
                            <a href="{{route('admin.returns.analytics')}}">Returns Analytics</a>
                          </li>
                        </ul>
                      </div>
                    </li>
                  </ul>
                </div>
              </li>
              @endcanany

              @php
                $accountsMenuItems = [
                    ['label' => 'Chart of Accounts', 'route' => 'admin.accounts.index', 'icon' => 'book', 'active' => 'admin.accounts.index'],
                    ['label' => 'Subsidiary', 'route' => 'admin.accounts.subsidiary.index', 'icon' => 'layers', 'active' => 'admin.accounts.subsidiary.*'],
                    ['label' => 'Voucher', 'route' => 'admin.accounts.voucher.index', 'icon' => 'file-text', 'active' => 'admin.accounts.voucher.*'],
                    ['label' => 'Fiscal Year Closing', 'route' => 'admin.accounts.fiscal-year.index', 'icon' => 'calendar', 'active' => 'admin.accounts.fiscal-year.*'],
                    ['label' => 'Opening', 'route' => 'admin.accounts.opening-balance.index', 'icon' => 'archive', 'active' => 'admin.accounts.opening-balance.*'],
                    ['label' => 'Account Settings', 'route' => 'admin.accounts.settings.edit', 'icon' => 'sliders', 'active' => 'admin.accounts.settings.*'],
                    ['label' => 'Payment Head Mapping', 'route' => 'admin.accounts.payment-head-mappings.index', 'icon' => 'settings', 'active' => 'admin.accounts.payment-head-mappings.*'],
                    ['label' => 'Financial Reports', 'route' => 'admin.accounts.reports.index', 'icon' => 'bar-chart-2', 'active' => 'admin.accounts.reports.*'],
                ];
                $accountsMenuOpen = collect($accountsMenuItems)->contains(fn ($item) => request()->routeIs($item['active']));
              @endphp
              @canany(['accounts-view', 'accounts-reports'])
              <li class="{{ $accountsMenuOpen ? 'mm-active' : '' }}">
                <a href="javascript:void(0);" data-bs-toggle="collapse" data-bs-target="#sidebar-erp-accounts" aria-expanded="{{ $accountsMenuOpen ? 'true' : 'false' }}">
                  <i data-feather="dollar-sign"></i>
                  <span> Accounts </span>
                  <span class="menu-arrow"></span>
                </a>
                <div class="collapse {{ $accountsMenuOpen ? 'show' : '' }}" id="sidebar-erp-accounts">
                  <ul class="nav-second-level">
                    @foreach ($accountsMenuItems as $item)
                    <li>
                      <a href="{{ route($item['route']) }}" class="{{ request()->routeIs($item['active']) ? 'active' : '' }}">
                        <i data-feather="{{ $item['icon'] }}"></i> {{ $item['label'] }}
                      </a>
                    </li>
                    @endforeach
                  </ul>
                </div>
              </li>
              @endcanany

              @php
                $reportsMenuOpen = request()->is('admin/reports-new/sales*')
                  || request()->is('admin/purchase-report*')
                  || request()->is('admin/stock-report*')
                  || request()->is('admin/reports*')
                  || request()->is('admin/returns*')
                  || request()->is('admin/profit-loss/losses*');
              @endphp
              @canany(['report-stock-balance', 'report-stock-movement', 'report-stock-valuation', 'report-dead-stock', 'report-transfer', 'report-loss', 'supplier-report-aging', 'supplier-report-ledger', 'expense-reports', 'profit-loss-reports'])
              <li class="{{ $reportsMenuOpen ? 'mm-active' : '' }}">
                <a href="javascript:void(0);" data-bs-toggle="collapse" data-bs-target="#sidebar-erp-reports" aria-expanded="{{ $reportsMenuOpen ? 'true' : 'false' }}">
                  <i data-feather="pie-chart"></i>
                  <span> Reports </span>
                  <span class="menu-arrow"></span>
                </a>
                <div class="collapse {{ $reportsMenuOpen ? 'show' : '' }}" id="sidebar-erp-reports">
                  <ul class="nav-second-level">
                    <li class="menu-title mt-2 mb-1 px-3 text-uppercase small">Operations</li>
                    <li>
                      <a href="{{ route('admin.reports.daily') }}"><i data-feather="calendar"></i> Daily Report</a>
                    </li>
                    <li>
                      <a href="{{ route('admin.reports.inventory-summary') }}"><i data-feather="layers"></i> Inventory Summary</a>
                    </li>
                    <li>
                      <a href="{{ route('admin.reports-new.sales') }}"><i data-feather="bar-chart"></i> Sales Report</a>
                    </li>
                    <li>
                      <a href="{{route('admin.purchase_report')}}"><i data-feather="shopping-bag"></i> Purchase Report</a>
                    </li>
                    <li>
                      <a href="{{route('admin.stock_report', ['stock_status' => 'low_stock'])}}"><i data-feather="alert-triangle"></i> Low Stock Report</a>
                    </li>
                    <li>
                      <a href="{{ route('admin.stock.movements') }}"><i data-feather="repeat"></i> Stock Ledger</a>
                    </li>
                    <li>
                      <a href="{{ route('admin.reports.purchase-returns') }}"><i data-feather="corner-down-left"></i> Purchase Return Statement</a>
                    </li>
                    <li>
                      <a href="{{ route('admin.reports.sales-returns') }}"><i data-feather="corner-down-left"></i> Sales Return Statement</a>
                    </li>
                    <li>
                      <a href="{{ route('admin.reports.damage') }}"><i data-feather="slash"></i> Damage Report</a>
                    </li>
                    <li>
                      <a href="{{ route('admin.reports.month-wise-sales-comparative') }}"><i data-feather="trending-up"></i> Month wise Sales Comparative</a>
                    </li>

                    <li class="menu-title mt-3 mb-1 px-3 text-uppercase small">Party</li>
                    <li>
                      <a href="{{ route('admin.reports.supplier-ledger') }}"><i data-feather="truck"></i> Supplier Ledger</a>
                    </li>
                    <li>
                      <a href="{{ route('admin.reports.bill-payments') }}"><i data-feather="credit-card"></i> Bill Payment Statement</a>
                    </li>
                    <li>
                      <a href="{{ route('admin.reports.customer-ledger') }}"><i data-feather="users"></i> Customer Ledger</a>
                    </li>
                    <li>
                      <a href="{{ route('admin.reports.money-receipt') }}"><i data-feather="file-text"></i> Money Receipt</a>
                    </li>

                    <li class="menu-title mt-3 mb-1 px-3 text-uppercase small">Accounting</li>
                    <li>
                      <a href="{{route('admin.accounts.supplier-payables')}}"><i data-feather="credit-card"></i> Supplier Due</a>
                    </li>
                    <li>
                      <a href="{{route('admin.accounts.customer-receivables')}}"><i data-feather="users"></i> Customer Due</a>
                    </li>
                    <li>
                      <a href="{{route('admin.returns.analytics')}}"><i data-feather="rotate-ccw"></i> Returns Analytics</a>
                    </li>
                    <li>
                      <a href="{{route('admin.profit-loss.warehouse-wise')}}"><i data-feather="home"></i> Warehouse Wise P&amp;L</a>
                    </li>
                    <li>
                      <a href="{{route('admin.profit-loss.product-wise')}}"><i data-feather="box"></i> Product Wise P&amp;L</a>
                    </li>
                    <li>
                      <a href="{{route('admin.profit-loss.inventory-valuation')}}"><i data-feather="layers"></i> Inventory Valuation</a>
                    </li>
                    <li>
                      <a href="{{route('admin.profit-loss.costing-comparison')}}"><i data-feather="shuffle"></i> Costing Comparison</a>
                    </li>
                  </ul>
                </div>
              </li>
              @endcanany

              @canany(['review-list'])
              <li class="menu-title">Marketing</li>
              @php
                $pending_reviews = \App\Models\Review::where('status', 'pending')->count();
              @endphp
              <li>
                <a href="javascript:void(0);" data-bs-toggle="collapse" data-bs-target="#sidebar-product-review">
                  <i data-feather="star"></i>
                  <span> Reviews </span>
                  <span class="menu-arrow"></span>
                </a>
                <div class="collapse" id="sidebar-product-review">
                  <ul class="nav-second-level">
                    <li>
                      <a href="{{route('admin.reviews.pending')}}"><i data-feather="file-plus"></i> Pending Reviews ({{ $pending_reviews }})</a>
                    </li>
                    <li>
                      <a href="{{route('admin.reviews.create')}}"><i data-feather="file-plus"></i> Create</a>
                    </li>
                    <li>
                      <a href="{{route('admin.reviews.index')}}"><i data-feather="file-plus"></i> All Reviews</a>
                    </li>
                  </ul>
                </div>
              </li>
              <!-- nav items end -->
              @endcanany

              @canany(['campaign-list'])
              <li>
                <a href="javascript:void(0);" data-bs-toggle="collapse" data-bs-target="#sidebar-landing-page">
                  <i data-feather="airplay"></i>
                  <span> Landing Page </span>
                  <span class="menu-arrow"></span>
                </a>
                <div class="collapse" id="sidebar-landing-page">
                  <ul class="nav-second-level">

                    <li>
                      <a href="{{route('admin.campaign.create')}}"><i data-feather="file-plus"></i> Create</a>
                    </li>
                    <li>
                      <a href="{{route('admin.campaign.index')}}"><i data-feather="file-plus"></i> Campaign</a>
                    </li>
                  </ul>
                </div>
              </li>
              @endcanany
              <!-- nav items end -->
              
              @canany(['user-list', 'role-list', 'permission-list', 'customer-list'])
              <li class="menu-title">Administration</li>
              <li>
                <a href="javascript:void(0);" data-bs-toggle="collapse" data-bs-target="#sidebar-users">
                  <i data-feather="user"></i>
                  <span> Users </span>
                  <span class="menu-arrow"></span>
                </a>
                <div class="collapse" id="sidebar-users">
                  <ul class="nav-second-level">
                    <li>
                      <a href="{{route('admin.users.index')}}"><i data-feather="file-plus"></i> Users</a>
                    </li>
                    <li>
                      <a href="{{route('admin.roles.index')}}"><i data-feather="file-plus"></i> Roles</a>
                    </li>
                    <li>
                      <a href="{{route('admin.permissions.index')}}"><i data-feather="file-plus"></i> Permissions</a>
                    </li>
                    <li>
                      <a href="{{route('admin.customers.index')}}"><i data-feather="file-plus"></i> Customers</a>
                    </li>
                    <li>
                      <a href="{{route('admin.customers.ip_block')}}"><i data-feather="shield"></i> IP Block</a>
                    </li>
                    <li>
                      <a href="{{route('admin.customers.phone_block')}}"><i data-feather="phone-off"></i> Phone Block</a>
                    </li>
                  </ul>
                </div>
              </li>
              @endcanany
              <!-- nav items -->
              @php
                $siteSettingsMenuOpen = request()->routeIs('admin.settings.*')
                  || request()->routeIs('admin.socialmedias.*')
                  || request()->routeIs('admin.contact.*')
                  || request()->routeIs('admin.pages.*')
                  || request()->routeIs('admin.shipping.*')
                  || request()->routeIs('admin.shippingcharges.*')
                  || request()->routeIs('admin.orderstatus.*');
              @endphp
              @canany(['setting-list', 'social-list', 'contact-list', 'page-list', 'shipping-list', 'orderstatus-list'])
              <li class="{{ $siteSettingsMenuOpen ? 'mm-active' : '' }}">
                <a href="javascript:void(0);" data-bs-toggle="collapse" data-bs-target="#siebar-sitesetting">
                  <i data-feather="settings"></i>
                  <span> Site Settings </span>
                  <span class="menu-arrow"></span>
                </a>
                <div class="collapse {{ $siteSettingsMenuOpen ? 'show' : '' }}" id="siebar-sitesetting">
                  <ul class="nav-second-level">
                    <li>
                      <a href="{{route('admin.settings.index')}}"><i data-feather="file-plus"></i> General Setting</a>
                    </li>

                    <li>
                      <a href="{{route('admin.socialmedias.index')}}"><i data-feather="file-plus"></i> Social Media</a>
                    </li>
                    <li>
                      <a href="{{route('admin.contact.index')}}"><i data-feather="file-plus"></i> Contact</a>
                    </li>
                    <li>
                      <a href="{{route('admin.pages.index')}}"><i data-feather="file-plus"></i> Create Page</a>
                    </li>
                    <li class="menu-title mt-3 mb-1 px-3 text-uppercase small">Shipping</li>
                    <li>
                      <a href="{{route('admin.shippingcharges.index')}}"><i data-feather="truck"></i> Shipping</a>
                    </li>
                    <li>
                      <a href="{{ route('admin.shipping.profiles.index') }}"><i data-feather="layers"></i> Shipping Profiles</a>
                    </li>
                    <li>
                      <a href="{{ route('admin.shipping.zones.index') }}"><i data-feather="map-pin"></i> Shipping Zones</a>
                    </li>
                    <li>
                      <a href="{{ route('admin.shipping.rates.index') }}"><i data-feather="dollar-sign"></i> Shipping Rates</a>
                    </li>
                    <li>
                      <a href="{{route('admin.orderstatus.index')}}"><i data-feather="file-plus"></i> Order Status</a>
                    </li>
                  </ul>
                </div>
              </li>
              @endcanany
              <!-- nav items end -->
              @hasrole('Admin')
              <li class="menu-title">Integrations</li>
              <li>
                <a href="javascript:void(0);" data-bs-toggle="collapse" data-bs-target="#sidebar-api-integration">
                  <i data-feather="save"></i>
                  <span> API Integration </span>
                  <span class="menu-arrow"></span>
                </a>
                <div class="collapse" id="sidebar-api-integration">
                  <ul class="nav-second-level">
                    <li>
                      <a href="{{route('admin.paymentgeteway.manage')}}"><i data-feather="file-plus"></i> Payment Gateway</a>
                    </li>
                    <li>
                      <a href="{{route('admin.smsgeteway.manage')}}"><i data-feather="file-plus"></i> SMS Gateway</a>
                    </li>
                    <li>
                      <a href="{{route('admin.courierapi.manage')}}"><i data-feather="file-plus"></i> Courier API</a>
                    </li>
                    <li>
                      <a href="{{route('admin.fraud-checker-api.manage')}}"><i data-feather="shield"></i> Fraud Checker API</a>
                    </li>
                  </ul>
                </div>
              </li>
              <li class="{{ request()->is('admin/steadfast*') ? 'mm-active' : '' }}">
                <a href="javascript:void(0);" data-bs-toggle="collapse" data-bs-target="#sidebar-steadfast" aria-expanded="{{ request()->is('admin/steadfast*') ? 'true' : 'false' }}">
                  <i data-feather="truck"></i>
                  <span> Steadfast Courier </span>
                  <span class="menu-arrow"></span>
                </a>
                <div class="collapse {{ request()->is('admin/steadfast*') ? 'show' : '' }}" id="sidebar-steadfast">
                  <ul class="nav-second-level">
                    <li>
                      <a href="{{route('admin.steadfast.dashboard')}}"><i data-feather="activity"></i> Dashboard</a>
                    </li>
                    <li>
                      <a href="{{route('admin.steadfast.return-requests')}}"><i data-feather="rotate-ccw"></i> Return Requests</a>
                    </li>
                    <li>
                      <a href="{{route('admin.steadfast.payments')}}"><i data-feather="credit-card"></i> Payments</a>
                    </li>
                    <li>
                      <a href="{{route('admin.steadfast.police-stations')}}"><i data-feather="map-pin"></i> Police Stations</a>
                    </li>
                  </ul>
                </div>
              </li>
              @endhasrole
              <!-- nav items end -->
              @canany(['pixel-list', 'tagmanager-list'])
              <li>
                <a href="javascript:void(0);" data-bs-toggle="collapse" data-bs-target="#sidebar-pixel-gtm">
                  <i data-feather="save"></i>
                  <span> Google Pixel &amp; GTM </span>
                  <span class="menu-arrow"></span>
                </a>
                <div class="collapse" id="sidebar-pixel-gtm">
                  <ul class="nav-second-level">
                    <li>
                      <a href="{{route('admin.tagmanagers.index')}}"><i data-feather="file-plus"></i> Tag Manager</a>
                    </li>
                    <li>
                      <a href="{{route('admin.pixels.index')}}"><i data-feather="file-plus"></i> Pixel Manage</a>
                    </li>
                  </ul>
                </div>
              </li>
              @endcanany
              <!-- nav items end -->
              @canany(['banner-list', 'banner-category-list'])
              <li>
                <a href="javascript:void(0);" data-bs-toggle="collapse" data-bs-target="#siebar-banner">
                  <i data-feather="image"></i>
                  <span> Banner & Ads </span>
                  <span class="menu-arrow"></span>
                </a>
                <div class="collapse" id="siebar-banner">
                  <ul class="nav-second-level">
                    <li>
                      <a href="{{route('admin.banner_category.index')}}"><i data-feather="file-plus"></i> Banner Category</a>
                    </li>
                    <li>
                      <a href="{{route('admin.banners.index')}}"><i data-feather="file-plus"></i> Manage Banners</a>
                    </li>
                  </ul>
                </div>
              </li>
              @endcanany
              <!-- nav items end -->
            </ul>
          </div>
          <!-- End Sidebar -->

          <div class="clearfix"></div>
        </div>
        <!-- Sidebar -left -->
      </div>
      <!-- Left Sidebar End -->

      <div class="content-page">
        <div class="content">
          @yield('content')
        </div>
        <!-- content -->

        <!-- Footer Start -->
        <footer class="footer">
          <div class="container-fluid">
            <div class="row">
              <div class="col-md-12 text-end"><a href="https://source-bd.com" target="_blank">Website Designed by: Source-Tech</a></div>
            </div>
          </div>
        </footer>
        <!-- end Footer -->
      </div>
    </div>
    <!-- END wrapper -->

    <!-- Right Sidebar -->
    <div class="right-bar">
      <div data-simplebar class="h-100">
        <!-- Nav tabs -->
        <ul class="nav nav-tabs nav-bordered nav-justified" role="tablist">
          <li class="nav-item">
            <a class="nav-link py-2" data-bs-toggle="tab" href="#chat-tab" role="tab">
              <i class="mdi mdi-message-text d-block font-22 my-1"></i>
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link py-2" data-bs-toggle="tab" href="#tasks-tab" role="tab">
              <i class="mdi mdi-format-list-checkbox d-block font-22 my-1"></i>
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link py-2 active" data-bs-toggle="tab" href="#settings-tab" role="tab">
              <i class="mdi mdi-cog-outline d-block font-22 my-1"></i>
            </a>
          </li>
        </ul>

        <!-- Tab panes -->
        <div class="tab-content pt-0">
          <div class="tab-pane" id="chat-tab" role="tabpanel">
            <form class="search-bar p-3">
              <div class="position-relative">
                <input type="text" class="form-control" placeholder="Search..." />
                <span class="mdi mdi-magnify"></span>
              </div>
            </form>
          </div>

          <div class="tab-pane" id="tasks-tab" role="tabpanel">
            <h6 class="fw-medium p-3 m-0 text-uppercase">Working Tasks</h6>
          </div>
          <div class="tab-pane active" id="settings-tab" role="tabpanel">
            <h6 class="fw-medium px-3 m-0 py-2 font-13 text-uppercase bg-light">
              <span class="d-block py-1">Theme Settings</span>
            </h6>

            <div class="p-3">
              <div class="alert alert-warning" role="alert"><strong>Customize </strong> the overall color scheme, sidebar menu, etc.</div>

              <h6 class="fw-medium font-14 mt-4 mb-2 pb-1">Color Scheme</h6>
              <div class="form-check form-switch mb-1">
                <input type="checkbox" class="form-check-input" name="layout-color" value="light" id="light-mode-check" checked />
                <label class="form-check-label" for="light-mode-check">Light Mode</label>
              </div>

              <div class="form-check form-switch mb-1">
                <input type="checkbox" class="form-check-input" name="layout-color" value="dark" id="dark-mode-check" />
                <label class="form-check-label" for="dark-mode-check">Dark Mode</label>
              </div>

              <!-- Width -->
              <h6 class="fw-medium font-14 mt-4 mb-2 pb-1">Width</h6>
              <div class="form-check form-switch mb-1">
                <input type="checkbox" class="form-check-input" name="layout-width" value="fluid" id="fluid-check" checked />
                <label class="form-check-label" for="fluid-check">Fluid</label>
              </div>
              <div class="form-check form-switch mb-1">
                <input type="checkbox" class="form-check-input" name="layout-width" value="boxed" id="boxed-check" />
                <label class="form-check-label" for="boxed-check">Boxed</label>
              </div>

              <!-- Menu positions -->
              <h6 class="fw-medium font-14 mt-4 mb-2 pb-1">Menus (Leftsidebar and Topbar) Positon</h6>

              <div class="form-check form-switch mb-1">
                <input type="checkbox" class="form-check-input" name="menu-position" value="fixed" id="fixed-check" checked />
                <label class="form-check-label" for="fixed-check">Fixed</label>
              </div>

              <div class="form-check form-switch mb-1">
                <input type="checkbox" class="form-check-input" name="menu-position" value="scrollable" id="scrollable-check" />
                <label class="form-check-label" for="scrollable-check">Scrollable</label>
              </div>

              <!-- Left Sidebar-->
              <h6 class="fw-medium font-14 mt-4 mb-2 pb-1">Left Sidebar Color</h6>

              <div class="form-check form-switch mb-1">
                <input type="checkbox" class="form-check-input" name="leftbar-color" value="light" id="light-check" />
                <label class="form-check-label" for="light-check">Light</label>
              </div>

              <div class="form-check form-switch mb-1">
                <input type="checkbox" class="form-check-input" name="leftbar-color" value="dark" id="dark-check" checked />
                <label class="form-check-label" for="dark-check">Dark</label>
              </div>

              <div class="form-check form-switch mb-1">
                <input type="checkbox" class="form-check-input" name="leftbar-color" value="brand" id="brand-check" />
                <label class="form-check-label" for="brand-check">Brand</label>
              </div>

              <div class="form-check form-switch mb-3">
                <input type="checkbox" class="form-check-input" name="leftbar-color" value="gradient" id="gradient-check" />
                <label class="form-check-label" for="gradient-check">Gradient</label>
              </div>

              <!-- size -->
              <h6 class="fw-medium font-14 mt-4 mb-2 pb-1">Left Sidebar Size</h6>

              <div class="form-check form-switch mb-1">
                <input type="checkbox" class="form-check-input" name="leftbar-size" value="default" id="default-size-check" checked />
                <label class="form-check-label" for="default-size-check">Default</label>
              </div>

              <div class="form-check form-switch mb-1">
                <input type="checkbox" class="form-check-input" name="leftbar-size" value="condensed" id="condensed-check" />
                <label class="form-check-label" for="condensed-check">Condensed <small>(Extra Small size)</small></label>
              </div>

              <div class="form-check form-switch mb-1">
                <input type="checkbox" class="form-check-input" name="leftbar-size" value="compact" id="compact-check" />
                <label class="form-check-label" for="compact-check">Compact <small>(Small size)</small></label>
              </div>

              <!-- User info -->
              <h6 class="fw-medium font-14 mt-4 mb-2 pb-1">Sidebar User Info</h6>

              <div class="form-check form-switch mb-1">
                <input type="checkbox" class="form-check-input" name="sidebar-user" value="fixed" id="sidebaruser-check" />
                <label class="form-check-label" for="sidebaruser-check">Enable</label>
              </div>

              <!-- Topbar -->
              <h6 class="fw-medium font-14 mt-4 mb-2 pb-1">Topbar</h6>

              <div class="form-check form-switch mb-1">
                <input type="checkbox" class="form-check-input" name="topbar-color" value="dark" id="darktopbar-check" checked />
                <label class="form-check-label" for="darktopbar-check">Dark</label>
              </div>

              <div class="form-check form-switch mb-1">
                <input type="checkbox" class="form-check-input" name="topbar-color" value="light" id="lighttopbar-check" />
                <label class="form-check-label" for="lighttopbar-check">Light</label>
              </div>

              <div class="d-grid mt-4">
                <button class="btn btn-primary" id="resetBtn">Reset to Default</button>
                <a href="https://1.envato.market/uboldadmin" class="btn btn-danger mt-3" target="_blank"><i class="mdi mdi-basket me-1"></i> Purchase Now</a>
              </div>
            </div>
          </div>
        </div>
      </div>
      <!-- end slimscroll-menu-->
    </div>
    <!-- /Right-bar -->

    <!-- Right bar overlay-->
    <div class="rightbar-overlay"></div>

    <!-- Vendor js -->
    <script src="{{asset('public/backEnd/')}}/assets/js/vendor.min.js"></script>

    <!-- App js -->
    <script src="{{asset('public/backEnd/')}}/assets/js/app.min.js"></script>
    <script src="{{asset('public/backEnd/')}}/assets/js/toastr.min.js"></script>
    {!! Toastr::message() !!}
    <script src="{{asset('public/backEnd/')}}/assets/js/sweetalert.min.js"></script>
    <script type="text/javascript">
      $(".delete-confirm").click(function (event) {
        var form = $(this).closest("form");
        event.preventDefault();
        swal({
          title: `Are you sure you want to delete this record?`,
          text: "If you delete this, it will be gone forever.",
          icon: "warning",
          buttons: true,
          dangerMode: true,
        }).then((willDelete) => {
          if (willDelete) {
            form.submit();
          }
        });
      });
      $(".change-confirm").click(function (event) {
        var form = $(this).closest("form");
        event.preventDefault();
        swal({
          title: `Are you sure you want to change this record?`,
          icon: "warning",
          buttons: true,
          dangerMode: true,
        }).then((willDelete) => {
          if (willDelete) {
            form.submit();
          }
        });
      });
    </script>
    <!--patho courier-->
    <script type="text/javascript">
        $(document).ready(function() {
            $('.pathaocity').change(function() {
                var id = $(this).val();
                if (id) {
                    $.ajax({
                        type: "GET",
                        url: "{{ url('admin/pathao-city') }}?city_id=" + id,
                        success: function(res) {
                            if (res && res.data && res.data.data) {
                                $(".pathaozone").empty();
                                $(".pathaozone").append('<option value="">Select..</option>');
                                $.each(res.data.data, function(index, zone) {
                                    $(".pathaozone").append('<option value="' + zone.zone_id + '">' + zone.zone_name + '</option>');
                                    $('.pathaozone').trigger("chosen:updated");
                                });
                            } else {
                                 $(".pathaoarea").empty();
                                $(".pathaozone").empty();
                            }
                        }
                    });
                } else {
                     $(".pathaoarea").empty();
                    $(".pathaozone").empty();
                }
            });
        });
    </script>
    <script type="text/javascript"> 
        $(document).ready(function() {
            $('.pathaozone').change(function() {
                var id = $(this).val();
                if (id) {
                    $.ajax({
                        type: "GET",
                        url: "{{ url('admin/pathao-zone') }}?zone_id=" + id,
                        success: function(res) {
                            if (res && res.data && res.data.data) {
                                $(".pathaoarea").empty();
                                $(".pathaoarea").append('<option value="">Select..</option>');
                                $.each(res.data.data, function(index, area) {
                                    $(".pathaoarea").append('<option value="' + area.area_id + '">' + area.area_name + '</option>');
                                    $('.pathaoarea').trigger("chosen:updated");
                                });
                            } else {
                                $(".pathaoarea").empty();
                            }
                        }
                    });
                } else {
                    $(".pathaoarea").empty();
                }
            });
        });
    </script>
    <script type="text/javascript">
      $(function () {
        const $sideMenu = $("#side-menu");
        const $menuSearchInput = $("#sidebar-menu-search");
        const $menuSearchClear = $("#sidebar-menu-search-clear");
        const $menuSearchEmpty = $("#wc-menu-search-empty");

        const normalizeMenuText = function (value) {
          return (value || "").toString().toLowerCase().replace(/\s+/g, " ").trim();
        };

        const currentUrl = window.location.href.split(/[?#]/)[0];
        const currentPath = window.location.pathname.replace(/\/+$/, "");

        $("#side-menu a[href]").not(".wc-quick-action-link").each(function () {
          const itemUrl = this.href.split(/[?#]/)[0];
          const itemPath = new URL(itemUrl, window.location.origin).pathname.replace(/\/+$/, "");
          if (itemUrl === currentUrl || (itemPath !== "" && itemPath !== "/" && currentPath.indexOf(itemPath) === 0)) {
            $(this).addClass("wc-active-link");
            $(this).parents("li").addClass("mm-active");
            $(this).parents(".collapse").addClass("show");
          }
        });

        const refreshMenuSectionVisibility = function () {
          $sideMenu.children("li.menu-title").each(function () {
            const $title = $(this);
            let $next = $title.next("li");
            let hasVisibleItems = false;

            while ($next.length && !$next.hasClass("menu-title")) {
              if (!$next.hasClass("wc-menu-hidden")) {
                hasVisibleItems = true;
                break;
              }
              $next = $next.next("li");
            }

            $title.toggleClass("wc-menu-hidden", !hasVisibleItems);
          });
        };

        const resetMenuSearch = function () {
          $sideMenu.find("li").removeClass("wc-menu-hidden wc-search-hit");
          $sideMenu.find(".collapse").each(function () {
            const $collapse = $(this);
            if ($collapse.find("a.wc-active-link").length === 0) {
              $collapse.removeClass("show");
            }
          });
          refreshMenuSectionVisibility();
          $menuSearchEmpty.addClass("d-none");
          $menuSearchClear.addClass("d-none");
        };

        const applyMenuSearch = function (keyword) {
          if (!keyword) {
            resetMenuSearch();
            return;
          }

          let matchCount = 0;
          $sideMenu.find("li").not(".wc-menu-utility").addClass("wc-menu-hidden");
          $sideMenu.find("li").removeClass("wc-search-hit");

          $sideMenu.find("a[href]").not(".wc-quick-action-link").each(function () {
            const $link = $(this);
            const linkText = normalizeMenuText($link.text());
            if (linkText.indexOf(keyword) === -1) {
              return;
            }

            matchCount += 1;
            const $item = $link.closest("li");
            $item.removeClass("wc-menu-hidden").addClass("wc-search-hit");
            $item.parents("li").removeClass("wc-menu-hidden");
            $item.parents(".collapse").addClass("show").each(function () {
              $(this).closest("li").removeClass("wc-menu-hidden");
            });
          });

          refreshMenuSectionVisibility();
          $menuSearchEmpty.toggleClass("d-none", matchCount > 0);
          $menuSearchClear.removeClass("d-none");
        };

        if ($menuSearchInput.length) {
          $menuSearchInput.on("input", function () {
            applyMenuSearch(normalizeMenuText($(this).val()));
          });

          $menuSearchClear.on("click", function () {
            $menuSearchInput.val("");
            resetMenuSearch();
            $menuSearchInput.trigger("focus");
          });
        }

        localStorage.removeItem("sidebar_scroll");

        const lockSidebarScroll = function () {
          const sideMenuScroll = document.querySelector(".left-side-menu .simplebar-content-wrapper") || document.querySelector(".left-side-menu .h-100");

          if (!sideMenuScroll) {
            return;
          }

          const initialTop = sideMenuScroll.scrollTop;

          setTimeout(function () {
            sideMenuScroll.scrollTop = initialTop;
          }, 250);

          const preserveTriggerPosition = function (trigger) {
            const triggerTop = trigger.getBoundingClientRect().top;

            const restore = function () {
              const nextTop = trigger.getBoundingClientRect().top;
              sideMenuScroll.scrollTop += nextTop - triggerTop;
            };

            requestAnimationFrame(restore);
            setTimeout(restore, 80);
            setTimeout(restore, 180);
            setTimeout(restore, 340);
          };

          $sideMenu.find("[data-bs-toggle='collapse']").on("click", function () {
            preserveTriggerPosition(this);
          });
        };

        lockSidebarScroll();

        // Optimize form submission handler to batch DOM updates
        $("form").on("submit", function () {
          const $form = $(this);
          if ($form.data("wc-submitting")) {
            return;
          }

          $form.data("wc-submitting", true);
          const $submit = $form.find("button[type='submit'], input[type='submit']").first();
          if ($submit.length) {
            // Use requestAnimationFrame for smoother state changes
            requestAnimationFrame(() => {
              $submit.addClass("wc-btn-loading").prop("disabled", true).attr("aria-disabled", "true");
            });
          }
        });

        // Apply consistent admin form grid styling by default.
        // Scoped to main content to avoid changing topbar/sidebar search forms.
        const $contentForms = $(".content-page .content").find("form");
        $contentForms.each(function () {
          const $form = $(this);
          if ($form.hasClass("wc-no-form-grid")) {
            return;
          }
          $form.addClass("wc-form-grid");

          // Auto-enhance legacy Bootstrap grid forms (row/col) to match the grid system
          // without forcing markup rewrites everywhere.
          $form.find(".row").each(function () {
            const $row = $(this);
            const hasCols = $row.children("[class*='col-']").length > 0;
            if (!hasCols) {
              return;
            }
            $row.addClass("wc-field-grid");
            $row.children("[class*='col-']").addClass("wc-field-cell");
          });
        });
      });
    </script>
    @yield('script')
  </body>
</html>
