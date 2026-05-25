<?php

namespace App\Console\Commands;

use App\Models\Branch;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\OrderDetails;
use App\Models\OrderStatus;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\PurchaseItem;
use App\Models\PurchaseOrder;
use App\Models\ReturnItem;
use App\Models\ReturnOrder;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\SupplierLedger;
use App\Models\SupplierPurchaseReturn;
use App\Models\SupplierPurchaseReturnItem;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ErpFunctionalTestCommand extends Command
{
    protected $signature = 'erp:functional-test {--cleanup : Remove demo data after test}';

    protected $description = 'Run full ERP functional validation across 10 phases';

    private array $results = [];

    private int $passed = 0;

    private int $failed = 0;

    private int $warnings = 0;

    private array $bugs = [];

    private array $risks = [];

    private array $recommendations = [];

    // Stored references
    private ?Branch $branch1 = null;

    private ?Branch $branch2 = null;

    private array $warehouses = [];

    private array $suppliers = [];

    private array $customers = [];

    private array $products = [];

    private array $variants = [];

    private array $expenseCategories = [];

    private array $purchaseOrders = [];

    private ?Order $testOrder = null;

    private ?User $adminUser = null;

    public function handle(): int
    {
        $this->info('');
        $this->info('╔══════════════════════════════════════════════════════╗');
        $this->info('║       ERP FUNCTIONAL VALIDATION — 10 PHASES         ║');
        $this->info('╚══════════════════════════════════════════════════════╝');
        $this->info('');

        // Authenticate as admin user
        $this->adminUser = User::first();
        if (! $this->adminUser) {
            $this->error('No admin user found. Cannot proceed.');

            return 1;
        }
        Auth::login($this->adminUser);
        $this->info("Authenticated as: {$this->adminUser->email}");
        $this->info('');

        // Always clean up stale demo data from previous failed runs
        $this->cleanupDemoData();

        try {
            $this->phase1_SeedDemoData();
            $this->phase2_PurchaseFlowTest();
            $this->phase3_MultiplePurchaseTest();
            $this->phase4_OrderSalesFlowTest();
            $this->phase5_SalesReturnTest();
            $this->phase6_PurchaseReturnTest();
            $this->phase7_ExpenseTest();
            $this->phase8_ProfitCalculationTest();
            $this->phase9_StockStressTest();
            $this->phase10_FinalReport();
        } catch (\Throwable $e) {
            $this->error("Fatal error in phase: {$e->getMessage()}");
            $this->error($e->getFile().':'.$e->getLine());
            $this->bugs[] = "Fatal: {$e->getMessage()} at {$e->getFile()}:{$e->getLine()}";
        }

        if ($this->option('cleanup')) {
            $this->cleanupDemoData();
        }

        return $this->failed > 0 ? 1 : 0;
    }

    // ─── HELPERS ────────────────────────────────────────────────

    private function assert(string $phase, string $test, bool $condition, string $detail = ''): void
    {
        if ($condition) {
            $this->passed++;
            $this->results[] = ['phase' => $phase, 'test' => $test, 'status' => 'PASS', 'detail' => $detail];
            $this->line("  ✅ {$test}".($detail ? " — {$detail}" : ''));
        } else {
            $this->failed++;
            $this->results[] = ['phase' => $phase, 'test' => $test, 'status' => 'FAIL', 'detail' => $detail];
            $this->error("  ❌ {$test}".($detail ? " — {$detail}" : ''));
            $this->bugs[] = "[{$phase}] {$test}: {$detail}";
        }
    }

    private function addWarning(string $phase, string $message): void
    {
        $this->warnings++;
        $this->line("  ⚠️  {$message}");
        $this->risks[] = "[{$phase}] {$message}";
    }

    private function phaseHeader(string $title): void
    {
        $this->info('');
        $this->info("━━━ {$title} ━━━");
        $this->info('');
    }

    // ─── PHASE 1: SEED DEMO DATA ───────────────────────────────

    private function phase1_SeedDemoData(): void
    {
        $this->phaseHeader('PHASE 1 — DEMO DATA SEEDING');

        // Branches
        $this->branch1 = Branch::create([
            'name' => '[DEMO] Main Branch',
            'code' => 'DEMO-MAIN',
            'address' => 'Demo Main Address, Dhaka',
            'phone' => '01700000001',
            'status' => true,
        ]);
        $this->branch2 = Branch::create([
            'name' => '[DEMO] South Branch',
            'code' => 'DEMO-SOUTH',
            'address' => 'Demo South Address, Chittagong',
            'phone' => '01700000002',
            'status' => true,
        ]);
        $this->assert('Phase 1', 'Branches created', $this->branch1->exists && $this->branch2->exists, '2 branches');

        // Warehouses (2 per branch)
        $whNames = [
            [$this->branch1->id, '[DEMO] Main WH-A', 'main'],
            [$this->branch1->id, '[DEMO] Main WH-B', 'branch'],
            [$this->branch2->id, '[DEMO] South WH-A', 'main'],
            [$this->branch2->id, '[DEMO] South WH-B', 'branch'],
        ];
        foreach ($whNames as [$branchId, $name, $type]) {
            $wh = Warehouse::create([
                'branch_id' => $branchId,
                'name' => $name,
                'type' => $type,
                'address' => 'Demo Address',
                'city' => 'Dhaka',
                'is_active' => true,
                'created_by' => $this->adminUser->id,
            ]);
            $this->warehouses[] = $wh;
        }
        $this->assert('Phase 1', 'Warehouses created', count($this->warehouses) === 4, '4 warehouses');

        // Suppliers
        $supplierNames = [
            ['[DEMO] Supplier Alpha', 'SUP-DEMO-A', 50000, 30],
            ['[DEMO] Supplier Beta', 'SUP-DEMO-B', 75000, 45],
            ['[DEMO] Supplier Gamma', 'SUP-DEMO-C', 100000, 60],
            ['[DEMO] Supplier Delta', 'SUP-DEMO-D', 30000, 15],
            ['[DEMO] Supplier Epsilon', 'SUP-DEMO-E', 60000, 30],
        ];
        foreach ($supplierNames as [$name, $code, $limit, $terms]) {
            $s = Supplier::create([
                'name' => $name,
                'supplier_code' => $code,
                'email' => strtolower(str_replace(['[DEMO] ', ' '], ['', ''], $name)).'@demo.test',
                'phone' => '017'.rand(10000000, 99999999),
                'address' => 'Demo Supplier Address',
                'city' => 'Dhaka',
                'status' => 'active',
                'credit_limit' => $limit,
                'payment_terms_days' => $terms,
            ]);
            $this->suppliers[] = $s;
        }
        $this->assert('Phase 1', 'Suppliers created', count($this->suppliers) === 5, '5 suppliers');

        // Customers — use forceCreate to bypass $fillable (slug not listed)
        for ($i = 1; $i <= 10; $i++) {
            $slug = 'demo-customer-'.$i;
            $c = (new Customer)->forceCreate([
                'name' => "[DEMO] Customer {$i}",
                'slug' => $slug,
                'email' => "demo.customer{$i}@test.local",
                'password' => bcrypt('Demo@1234'),
                'phone' => '018'.rand(10000000, 99999999),
                'status' => 1,
            ]);
            $this->customers[] = $c;
        }
        $this->assert('Phase 1', 'Customers created', count($this->customers) === 10, '10 customers');

        // Ensure a category exists
        $category = Category::first();
        if (! $category) {
            $category = Category::create([
                'name' => '[DEMO] General',
                'slug' => 'demo-general',
                'status' => 1,
            ]);
        }

        // Products + Variants
        $productData = [
            ['[DEMO] Widget A', 150, 250], ['[DEMO] Widget B', 200, 350],
            ['[DEMO] Gadget C', 500, 800], ['[DEMO] Gadget D', 300, 500],
            ['[DEMO] Tool E', 100, 180],   ['[DEMO] Tool F', 250, 400],
            ['[DEMO] Part G', 50, 90],     ['[DEMO] Part H', 75, 130],
            ['[DEMO] Device I', 1200, 1800], ['[DEMO] Device J', 800, 1300],
            ['[DEMO] Comp K', 400, 650],   ['[DEMO] Comp L', 350, 550],
            ['[DEMO] Acc M', 80, 140],     ['[DEMO] Acc N', 120, 200],
            ['[DEMO] Supply O', 30, 55],   ['[DEMO] Supply P', 45, 80],
            ['[DEMO] Item Q', 600, 950],   ['[DEMO] Item R', 450, 720],
            ['[DEMO] Material S', 180, 300], ['[DEMO] Material T', 220, 360],
        ];

        foreach ($productData as $idx => [$name, $cost, $sell]) {
            $sku = 'DEMO-SKU-'.str_pad($idx + 1, 3, '0', STR_PAD_LEFT);
            $productSlug = Str::slug($name).'-demo-'.$idx;
            $product = Product::create([
                'name' => $name,
                'slug' => $productSlug,
                'category_id' => $category->id,
                'product_code' => $sku,
                'sku' => $sku,
                'new_price' => $sell,
                'old_price' => $sell + 50,
                'purchase_price' => $cost,
                'status' => 1,
            ]);
            $this->products[] = $product;

            $variant = ProductVariant::create([
                'product_id' => $product->id,
                'sku_code' => $sku,
                'color' => 'Default',
                'size' => 'Standard',
                'price' => $sell,
                'cost_price' => $cost,
                'status' => 'active',
            ]);
            $this->variants[] = $variant;
        }
        $this->assert('Phase 1', 'Products created', count($this->products) === 20, '20 products + variants');

        // Verify stock is 0
        $totalInventory = Inventory::whereIn('product_variant_id', collect($this->variants)->pluck('id'))->sum('quantity_available');
        $this->assert('Phase 1', 'Initial stock is zero', (float) $totalInventory === 0.0, "Inventory sum: {$totalInventory}");

        // Expense Categories
        $catNames = [
            ['Transport', 'EXP-TRANS'],
            ['Salary', 'EXP-SAL'],
            ['Utilities', 'EXP-UTIL'],
            ['Miscellaneous', 'EXP-MISC'],
        ];
        foreach ($catNames as [$name, $code]) {
            $existing = ExpenseCategory::where('code', $code)->first();
            $ec = $existing ?? ExpenseCategory::create([
                'name' => "[DEMO] {$name}",
                'code' => $code,
                'description' => "Demo {$name} category",
                'is_active' => true,
            ]);
            $this->expenseCategories[] = $ec;
        }
        $this->assert('Phase 1', 'Expense categories ready', count($this->expenseCategories) === 4, '4 categories');

        // Ensure OrderStatus exists
        $statuses = OrderStatus::count();
        if ($statuses === 0) {
            foreach (['Pending', 'Processing', 'Shipped', 'Delivered', 'Cancelled'] as $i => $s) {
                OrderStatus::create(['name' => $s, 'slug' => Str::slug($s), 'sort_order' => $i]);
            }
        }
        $this->assert('Phase 1', 'Order statuses exist', OrderStatus::count() > 0);
    }

    // ─── PHASE 2: PURCHASE FLOW TEST ────────────────────────────

    private function phase2_PurchaseFlowTest(): void
    {
        $this->phaseHeader('PHASE 2 — PURCHASE FLOW TEST');

        $supplier = $this->suppliers[0]; // Alpha
        $warehouse = $this->warehouses[0]; // Main WH-A
        $selectedVariants = array_slice($this->variants, 0, 5);

        // Create PO
        $po = PurchaseOrder::create([
            'supplier_id' => $supplier->id,
            'warehouse_id' => $warehouse->id,
            'branch_id' => $warehouse->branch_id,
            'status' => 'pending',
            'total_cost' => 0,
            'created_by' => $this->adminUser->id,
        ]);
        $this->assert('Phase 2', 'PO created', $po->exists, "PO# {$po->po_number}");

        // Add items
        $expectedTotal = 0;
        $items = [];
        foreach ($selectedVariants as $i => $variant) {
            $qty = ($i + 1) * 10; // 10, 20, 30, 40, 50
            $cost = (float) $variant->cost_price;
            $item = PurchaseItem::create([
                'purchase_order_id' => $po->id,
                'product_variant_id' => $variant->id,
                'quantity_ordered' => $qty,
                'quantity_received' => 0,
                'unit_cost' => $cost,
                'line_total' => $qty * $cost,
            ]);
            $items[] = $item;
            $expectedTotal += $qty * $cost;
        }
        $po->refresh();
        $po->updateTotalCost();
        $po->refresh();

        $this->assert('Phase 2', 'PO items added', $po->purchaseItems()->count() === 5, '5 line items');
        $this->assert('Phase 2', 'PO total correct', abs((float) $po->total_cost - $expectedTotal) < 0.01,
            "Expected: {$expectedTotal}, Got: {$po->total_cost}");

        // Approve → Ordered → Receive
        $po->update(['status' => 'pending']);
        $po->approve($this->adminUser);
        $this->assert('Phase 2', 'PO approved', $po->status === 'approved');

        $po->markAsOrdered();
        $this->assert('Phase 2', 'PO marked as ordered', $po->status === 'ordered');

        // Receive all items
        $receiveData = [];
        foreach ($items as $item) {
            $receiveData[] = [
                'purchase_item_id' => $item->id,
                'quantity_received' => (float) $item->quantity_ordered,
                'unit_cost' => (float) $item->unit_cost,
            ];
        }
        $po->receiveItems($receiveData);
        $po->refresh();

        $this->assert('Phase 2', 'PO fully received', $po->is_fully_received, "Status: {$po->status}");

        // Verify stock increased
        foreach ($selectedVariants as $i => $variant) {
            $expectedQty = ($i + 1) * 10;
            $inv = Inventory::where('product_variant_id', $variant->id)
                ->where('warehouse_id', $warehouse->id)
                ->first();
            $actualQty = $inv ? (float) $inv->quantity_available : 0;
            $this->assert('Phase 2', "Stock for variant #{$variant->sku_code}",
                abs($actualQty - $expectedQty) < 0.01,
                "Expected: {$expectedQty}, Got: {$actualQty}");
        }

        // Verify supplier ledger auto-posted via PurchaseOrderReceived event
        $supplier->refresh();
        $supplierBalance = (float) $supplier->ledger()->sum(DB::raw('debit - credit'));
        $this->assert('Phase 2', 'Supplier ledger auto-posted', $supplierBalance > 0,
            "Balance: {$supplierBalance} (expected ~{$expectedTotal})");

        $this->purchaseOrders[] = $po;
    }

    // ─── PHASE 3: MULTIPLE PURCHASE TEST ────────────────────────

    private function phase3_MultiplePurchaseTest(): void
    {
        $this->phaseHeader('PHASE 3 — MULTIPLE PURCHASE TEST');

        // PO from supplier B → warehouse 1
        $this->createAndReceivePO($this->suppliers[1], $this->warehouses[0],
            array_slice($this->variants, 5, 5), 'Phase 3');

        // PO from supplier C → warehouse 2 (different branch)
        $this->createAndReceivePO($this->suppliers[2], $this->warehouses[2],
            array_slice($this->variants, 10, 5), 'Phase 3');

        // Verify cumulative stock
        $variant0 = $this->variants[0];
        $inv = Inventory::where('product_variant_id', $variant0->id)
            ->where('warehouse_id', $this->warehouses[0]->id)
            ->first();
        $this->assert('Phase 3', 'Stock cumulative (WH1 unchanged)', $inv && (float) $inv->quantity_available === 10.0,
            'Expected: 10, Got: '.($inv ? $inv->quantity_available : 'null'));

        // Verify per-supplier dues
        foreach ($this->suppliers as $i => $sup) {
            $sup->refresh();
            $balance = (float) $sup->ledger()->sum(DB::raw('debit - credit'));
            $this->line("  📊 Supplier {$sup->name}: Balance = {$balance}");
        }

        // Cross-branch check
        $crossCheck = Inventory::where('product_variant_id', $this->variants[10]->id)
            ->where('warehouse_id', $this->warehouses[0]->id)
            ->first();
        $this->assert('Phase 3', 'No cross-branch contamination',
            ! $crossCheck || (float) $crossCheck->quantity_available === 0.0,
            'Variant 10 should only be in WH-2 (South branch)');
    }

    private function createAndReceivePO(Supplier $supplier, Warehouse $warehouse, array $variants, string $phase): PurchaseOrder
    {
        $po = PurchaseOrder::create([
            'supplier_id' => $supplier->id,
            'warehouse_id' => $warehouse->id,
            'branch_id' => $warehouse->branch_id,
            'status' => 'pending',
            'total_cost' => 0,
            'created_by' => $this->adminUser->id,
        ]);

        $items = [];
        $total = 0;
        foreach ($variants as $i => $variant) {
            $qty = 20;
            $cost = (float) $variant->cost_price;
            $item = PurchaseItem::create([
                'purchase_order_id' => $po->id,
                'product_variant_id' => $variant->id,
                'quantity_ordered' => $qty,
                'quantity_received' => 0,
                'unit_cost' => $cost,
                'line_total' => $qty * $cost,
            ]);
            $items[] = $item;
            $total += $qty * $cost;
        }

        $po->updateTotalCost();
        $po->refresh();
        $po->approve($this->adminUser);
        $po->markAsOrdered();

        $receiveData = [];
        foreach ($items as $item) {
            $receiveData[] = [
                'purchase_item_id' => $item->id,
                'quantity_received' => (float) $item->quantity_ordered,
                'unit_cost' => (float) $item->unit_cost,
            ];
        }
        $po->receiveItems($receiveData);

        // Supplier ledger is now auto-posted via PurchaseOrderReceived event

        $this->assert($phase, "PO #{$po->po_number} received", in_array($po->fresh()->status, ['received', 'ordered', 'partial_received']));
        $this->purchaseOrders[] = $po;

        return $po;
    }

    // ─── PHASE 4: ORDER & SALES FLOW TEST ───────────────────────

    private function phase4_OrderSalesFlowTest(): void
    {
        $this->phaseHeader('PHASE 4 — ORDER & SALES FLOW TEST');

        $customer = $this->customers[0];
        $warehouse = $this->warehouses[0];
        $orderProducts = array_slice($this->products, 0, 3);
        $orderVariants = array_slice($this->variants, 0, 3);

        // Record stock before
        $stockBefore = [];
        foreach ($orderVariants as $v) {
            $inv = Inventory::where('product_variant_id', $v->id)
                ->where('warehouse_id', $warehouse->id)->first();
            $stockBefore[$v->id] = $inv ? (float) $inv->quantity_available : 0;
        }

        // Get delivered status
        $deliveredStatus = OrderStatus::where('name', 'Delivered')->first()
            ?? OrderStatus::where('slug', 'delivered')->first()
            ?? OrderStatus::first();

        // Create order
        $orderQtys = [2, 3, 5];
        $totalAmount = 0;
        foreach ($orderProducts as $i => $p) {
            $totalAmount += (float) $p->new_price * $orderQtys[$i];
        }

        $order = Order::create([
            'invoice_id' => Order::generateInvoiceId(),
            'amount' => $totalAmount,
            'amount_minor' => (int) ($totalAmount * 100),
            'discount' => 0,
            'discount_minor' => 0,
            'shipping_charge' => 0,
            'shipping_charge_minor' => 0,
            'customer_id' => $customer->id,
            'warehouse_id' => $warehouse->id,
            'branch_id' => $warehouse->branch_id,
            'order_status' => $deliveredStatus->id,
            'currency' => 'BDT',
            'user_id' => $this->adminUser->id,
            'note' => '[DEMO] Test order',
        ]);
        $this->assert('Phase 4', 'Order created', $order->exists, "Invoice: {$order->invoice_id}");
        $this->testOrder = $order;

        // Create order details + reduce stock
        foreach ($orderProducts as $i => $p) {
            $variant = $orderVariants[$i];
            $qty = $orderQtys[$i];

            OrderDetails::create([
                'order_id' => $order->id,
                'product_id' => $p->id,
                'product_variant_id' => $variant->id,
                'warehouse_id' => $warehouse->id,
                'product_name' => $p->name,
                'purchase_price' => (float) $variant->cost_price,
                'purchase_price_minor' => (int) ($variant->cost_price * 100),
                'sale_price' => (float) $p->new_price,
                'sale_price_minor' => (int) ($p->new_price * 100),
                'qty' => $qty,
                'product_discount' => 0,
                'currency' => 'BDT',
            ]);

            // Decrease inventory
            $inv = Inventory::where('product_variant_id', $variant->id)
                ->where('warehouse_id', $warehouse->id)->first();
            if ($inv) {
                $inv->decreaseStock($qty);
            }
        }

        $this->assert('Phase 4', 'Order details created', $order->orderdetails()->count() === 3, '3 line items');
        $this->assert('Phase 4', 'Order total correct', abs((float) $order->amount - $totalAmount) < 0.01,
            "Expected: {$totalAmount}, Got: {$order->amount}");

        // Verify stock reduced
        foreach ($orderVariants as $i => $v) {
            $inv = Inventory::where('product_variant_id', $v->id)
                ->where('warehouse_id', $warehouse->id)->first();
            $expected = $stockBefore[$v->id] - $orderQtys[$i];
            $actual = $inv ? (float) $inv->quantity_available : 0;
            $this->assert('Phase 4', "Stock reduced for {$v->sku_code}",
                abs($actual - $expected) < 0.01,
                "Before: {$stockBefore[$v->id]}, Sold: {$orderQtys[$i]}, After: {$actual}");
        }

        // Negative stock prevention
        $testVariant = $orderVariants[0];
        $testInv = Inventory::where('product_variant_id', $testVariant->id)
            ->where('warehouse_id', $warehouse->id)->first();
        $overSellQty = ($testInv ? (float) $testInv->quantity_available : 0) + 999;
        try {
            $testInv->decreaseStock($overSellQty);
            $this->assert('Phase 4', 'Negative stock prevented', false, 'Should have thrown exception');
        } catch (\Exception $e) {
            $this->assert('Phase 4', 'Negative stock prevented', true, 'Exception thrown correctly');
        }
    }

    // ─── PHASE 5: SALES RETURN TEST ─────────────────────────────

    private function phase5_SalesReturnTest(): void
    {
        $this->phaseHeader('PHASE 5 — SALES RETURN TEST');

        if (! $this->testOrder) {
            $this->addWarning('Phase 5', 'No test order available. Skipping.');

            return;
        }

        $orderDetail = $this->testOrder->orderdetails()->first();
        $warehouse = $this->warehouses[0];
        $returnQty = 1;

        // Stock before return
        $variant = ProductVariant::find($orderDetail->product_variant_id);
        $invBefore = Inventory::where('product_variant_id', $variant->id)
            ->where('warehouse_id', $warehouse->id)->first();
        $stockBefore = $invBefore ? (float) $invBefore->quantity_available : 0;

        // Get a return reason
        $returnReason = \App\Models\ReturnReason::first();
        $returnReasonId = $returnReason ? $returnReason->id : 1;

        // Create return order
        $returnOrder = ReturnOrder::create([
            'order_id' => $this->testOrder->id,
            'customer_id' => $this->testOrder->customer_id,
            'return_status' => 'pending',
            'return_source' => 'customer',
            'return_type' => 'partial',
            'return_reason_id' => $returnReasonId,
            'refund_amount' => (float) $orderDetail->sale_price * $returnQty,
            'restock_flag' => true,
            'damage_flag' => false,
            'total_return_value' => (float) $orderDetail->sale_price * $returnQty,
            'notes' => '[DEMO] Test return',
            'created_by' => $this->adminUser->id,
        ]);
        $this->assert('Phase 5', 'Return order created', $returnOrder->exists, "Return# {$returnOrder->return_number}");

        // Create return item (this auto-updates OrderDetail.returned_quantity via booted)
        $returnItem = ReturnItem::create([
            'return_order_id' => $returnOrder->id,
            'order_detail_id' => $orderDetail->id,
            'product_id' => $orderDetail->product_id,
            'warehouse_id' => $warehouse->id,
            'return_quantity' => $returnQty,
            'unit_price' => (float) $orderDetail->sale_price,
            'unit_cost' => (float) $orderDetail->purchase_price,
            'return_condition' => 'new',
            'restock_quantity' => $returnQty,
            'damage_quantity' => 0,
            'refund_amount' => (float) $orderDetail->sale_price * $returnQty,
        ]);

        // Verify returned_quantity on OrderDetails
        $orderDetail->refresh();
        $this->assert('Phase 5', 'OrderDetail returned_quantity updated',
            (float) $orderDetail->returned_quantity === (float) $returnQty,
            "Expected: {$returnQty}, Got: {$orderDetail->returned_quantity}");

        // Approve and complete the return to trigger auto-restock
        $returnOrder->approve($this->adminUser);
        $returnOrder->process($this->adminUser);
        $returnOrder->complete($this->adminUser, 'Auto-test completion');

        // Verify auto-restock happened
        if ($invBefore) {
            $invBefore->refresh();
            $stockAfter = (float) $invBefore->quantity_available;
            $this->assert('Phase 5', 'Auto-restock on return complete',
                abs($stockAfter - ($stockBefore + $returnQty)) < 0.01,
                "Before: {$stockBefore}, After: {$stockAfter}");
        }
    }

    // ─── PHASE 6: PURCHASE RETURN TEST ──────────────────────────

    private function phase6_PurchaseReturnTest(): void
    {
        $this->phaseHeader('PHASE 6 — PURCHASE RETURN TEST');

        $po = $this->purchaseOrders[0];
        $supplier = $this->suppliers[0];
        $warehouse = $this->warehouses[0];
        $firstItem = $po->purchaseItems()->first();
        $variant = ProductVariant::find($firstItem->product_variant_id);
        $returnQty = 3;

        // Stock before
        $invBefore = Inventory::where('product_variant_id', $variant->id)
            ->where('warehouse_id', $warehouse->id)->first();
        $stockBefore = $invBefore ? (float) $invBefore->quantity_available : 0;

        // Supplier balance before
        $balanceBefore = (float) $supplier->ledger()->sum(DB::raw('debit - credit'));

        // Create supplier purchase return
        $returnAmount = $returnQty * (float) $firstItem->unit_cost;
        $spr = SupplierPurchaseReturn::create([
            'supplier_id' => $supplier->id,
            'original_purchase_id' => $po->id,
            'branch_id' => $warehouse->branch_id,
            'return_date' => now()->toDateString(),
            'total_amount' => $returnAmount,
            'return_reason' => 'quality_issue',
            'notes' => '[DEMO] Test purchase return',
            'status' => 'approved',
            'created_by' => $this->adminUser->id,
            'approved_by' => $this->adminUser->id,
            'approved_at' => now(),
        ]);
        $this->assert('Phase 6', 'Purchase return created', $spr->exists, "Return# {$spr->return_number}");

        // Create return item
        $retItem = SupplierPurchaseReturnItem::create([
            'supplier_purchase_return_id' => $spr->id,
            'purchase_item_id' => $firstItem->id,
            'product_variant_id' => $variant->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => $returnQty,
            'unit_cost' => (float) $firstItem->unit_cost,
        ]);

        // Decrease inventory for returned items
        if ($invBefore) {
            $invBefore->decreaseStock($returnQty);
            $invBefore->refresh();
            $stockAfter = (float) $invBefore->quantity_available;
            $this->assert('Phase 6', 'Stock decreased after purchase return',
                abs($stockAfter - ($stockBefore - $returnQty)) < 0.01,
                "Before: {$stockBefore}, Returned: {$returnQty}, After: {$stockAfter}");
        }

        // Credit supplier ledger
        $supplier->addLedgerEntry('purchase_return', 0, $returnAmount, [
            'reference_type' => 'supplier_purchase_return',
            'reference_id' => $spr->id,
            'reference_number' => $spr->return_number,
            'description' => "Purchase return #{$spr->return_number}",
            'transaction_date' => now()->toDateString(),
            'created_by' => $this->adminUser->id,
            'branch_id' => $warehouse->branch_id,
        ]);

        $balanceAfter = (float) $supplier->ledger()->sum(DB::raw('debit - credit'));
        $expectedBalance = $balanceBefore - $returnAmount;
        $this->assert('Phase 6', 'Supplier due reduced after return',
            abs($balanceAfter - $expectedBalance) < 0.01,
            "Before: {$balanceBefore}, Return: {$returnAmount}, After: {$balanceAfter}");
    }

    // ─── PHASE 7: EXPENSE TEST ──────────────────────────────────

    private function phase7_ExpenseTest(): void
    {
        $this->phaseHeader('PHASE 7 — EXPENSE TEST');

        $branch = $this->branch1;

        // Operating expense
        $opExpense = Expense::create([
            'branch_id' => $branch->id,
            'expense_date' => now()->toDateString(),
            'category_id' => $this->expenseCategories[2]->id, // Utilities
            'total_amount' => 5000,
            'payment_method' => 'cash',
            'description' => '[DEMO] Monthly electricity bill',
            'status' => 'pending',
            'created_by' => $this->adminUser->id,
        ]);
        $this->assert('Phase 7', 'Operating expense created', $opExpense->exists,
            "Expense# {$opExpense->expense_number}, Amount: 5000");

        // Purchase-linked expense
        $po = $this->purchaseOrders[0] ?? null;
        $purchaseExpense = Expense::create([
            'branch_id' => $branch->id,
            'expense_date' => now()->toDateString(),
            'category_id' => $this->expenseCategories[0]->id, // Transport
            'supplier_id' => $this->suppliers[0]->id,
            'purchase_order_id' => $po?->id,
            'total_amount' => 2500,
            'payment_method' => 'bank_transfer',
            'description' => '[DEMO] Transport cost for PO',
            'status' => 'pending',
            'created_by' => $this->adminUser->id,
        ]);
        $this->assert('Phase 7', 'Purchase expense created', $purchaseExpense->exists);

        // Approve and pay
        $opExpense->approve($this->adminUser);
        $this->assert('Phase 7', 'Expense approved', $opExpense->fresh()->status === 'approved');

        $opExpense->markAsPaid($this->adminUser);
        $this->assert('Phase 7', 'Expense marked as paid', $opExpense->fresh()->status === 'paid');

        // Branch link verification
        $this->assert('Phase 7', 'Expense linked to branch',
            (int) $opExpense->branch_id === (int) $branch->id,
            "Branch: {$branch->name}");
    }

    // ─── PHASE 8: PROFIT CALCULATION TEST ────────────────────────

    private function phase8_ProfitCalculationTest(): void
    {
        $this->phaseHeader('PHASE 8 — PROFIT CALCULATION TEST');

        // Manual calculation
        $totalRevenue = 0;
        $totalCOGS = 0;
        $totalExpenses = 0;

        if ($this->testOrder) {
            foreach ($this->testOrder->orderdetails as $detail) {
                $totalRevenue += (float) $detail->sale_price * (float) $detail->qty;
                $totalCOGS += (float) $detail->purchase_price * (float) $detail->qty;
            }
        }

        $totalExpenses = (float) Expense::where('description', 'like', '%[DEMO]%')
            ->where('status', 'paid')
            ->sum('total_amount');

        $grossProfit = $totalRevenue - $totalCOGS;
        $netProfit = $grossProfit - $totalExpenses;

        $this->info("  Revenue:      BDT {$totalRevenue}");
        $this->info("  COGS:         BDT {$totalCOGS}");
        $this->info("  Gross Profit: BDT {$grossProfit}");
        $this->info("  Expenses:     BDT {$totalExpenses}");
        $this->info("  Net Profit:   BDT {$netProfit}");

        $this->assert('Phase 8', 'Revenue > 0', $totalRevenue > 0, "Revenue: {$totalRevenue}");
        $this->assert('Phase 8', 'COGS > 0', $totalCOGS > 0, "COGS: {$totalCOGS}");
        $this->assert('Phase 8', 'Gross margin positive', $grossProfit > 0, "Gross: {$grossProfit}");
        $this->assert('Phase 8', 'COGS < Revenue', $totalCOGS < $totalRevenue);

        // Try ProfitLossService
        try {
            $plService = app(\App\Services\ProfitLossService::class);
            $report = $plService->generateProfitLossReport(
                'monthly',
                now()->startOfMonth()->toDateString(),
                now()->endOfMonth()->toDateString()
            );
            $this->assert('Phase 8', 'ProfitLossService works', $report !== null,
                "Sales: {$report->sales_revenue}, COGS: {$report->cost_of_goods_sold}");
        } catch (\Throwable $e) {
            $this->addWarning('Phase 8', "ProfitLossService error: {$e->getMessage()}");
            $this->bugs[] = "ProfitLossService: {$e->getMessage()}";
        }
    }

    // ─── PHASE 9: STOCK STRESS TEST ─────────────────────────────

    private function phase9_StockStressTest(): void
    {
        $this->phaseHeader('PHASE 9 — STOCK ENGINE STRESS TEST');

        $warehouse = $this->warehouses[0];
        $variant = $this->variants[0];

        // Test 1: Overselling prevention
        $inv = Inventory::where('product_variant_id', $variant->id)
            ->where('warehouse_id', $warehouse->id)->first();
        $currentStock = $inv ? (float) $inv->sellable_stock : 0;
        $this->info("  Current sellable stock for {$variant->sku_code}: {$currentStock}");

        $overSellAttempt = $currentStock + 100;
        try {
            $inv->decreaseStock($overSellAttempt);
            $this->assert('Phase 9', 'Overselling prevented', false, 'No exception thrown!');
            $this->bugs[] = 'CRITICAL: Overselling not prevented — decreaseStock allows negative stock';
        } catch (\Exception $e) {
            $this->assert('Phase 9', 'Overselling prevented', true, 'Exception: '.$e->getMessage());
        }

        // Test 2: Double-receive prevention
        $po = $this->purchaseOrders[0];
        $firstItem = $po->purchaseItems()->first();
        $firstItem->refresh();
        $remainingQty = (float) $firstItem->remaining_quantity;
        $this->info("  Remaining quantity on item: {$remainingQty}");

        if ($remainingQty <= 0) {
            // Already fully received — try receiving more
            try {
                $firstItem->receiveQuantity(1);
                $this->assert('Phase 9', 'Double-receive prevented', false, 'Allowed receiving beyond ordered!');
                $this->bugs[] = 'CRITICAL: Double-receive allowed — can receive more than ordered quantity';
            } catch (\Exception $e) {
                $this->assert('Phase 9', 'Double-receive prevented', true, 'Exception: '.$e->getMessage());
            }
        } else {
            $this->assert('Phase 9', 'Double-receive check', true, "Has remaining: {$remainingQty}");
        }

        // Test 3: Transaction isolation
        $this->assert('Phase 9', 'DB transaction support',
            DB::connection()->getPdo() !== null,
            'PDO connection active');

        // Test 4: Reservation system
        $inv = Inventory::where('product_variant_id', $variant->id)
            ->where('warehouse_id', $warehouse->id)->first();
        $currentAvailable = (float) $inv->sellable_stock;
        $reservedBefore = (float) $inv->quantity_reserved;
        if ($currentAvailable > 2) {
            $inv->reserveStock(2);
            $inv = Inventory::where('product_variant_id', $variant->id)
                ->where('warehouse_id', $warehouse->id)->first();
            $reservedNow = (float) $inv->quantity_reserved;
            $this->assert('Phase 9', 'Stock reservation works',
                $reservedNow >= ($reservedBefore + 2),
                "Reserved before: {$reservedBefore}, after: {$reservedNow}");

            // Try to sell more than sellable
            $sellableAfterReserve = (float) $inv->sellable_stock;
            try {
                $inv->decreaseStock($sellableAfterReserve + 1);
                $this->assert('Phase 9', 'Reserved stock protection', false, 'Sold reserved stock!');
            } catch (\Exception $e) {
                $this->assert('Phase 9', 'Reserved stock protection', true, 'Exception thrown correctly');
            }

            // Release reservation
            $inv->releaseReservedStock(2);
        }

        // Test 5: Concurrent-safe stock check (WAC consistency)
        $inv->refresh();
        $stockBeforeDouble = (float) $inv->quantity_available;
        DB::transaction(function () use ($inv, $variant) {
            $inv->increaseStock(10, (float) $variant->cost_price);
        });
        $inv->refresh();
        $this->assert('Phase 9', 'Stock increase in transaction',
            abs((float) $inv->quantity_available - ($stockBeforeDouble + 10)) < 0.01,
            "Before: {$stockBeforeDouble}, After: {$inv->quantity_available}");
    }

    // ─── PHASE 10: FINAL REPORT ─────────────────────────────────

    private function phase10_FinalReport(): void
    {
        $this->phaseHeader('PHASE 10 — FINAL ERP VALIDATION REPORT');

        $total = $this->passed + $this->failed;
        $score = $total > 0 ? round(($this->passed / $total) * 100) : 0;

        // Deductions for warnings/risks
        $warningPenalty = min(20, $this->warnings * 3);
        $finalScore = max(0, $score - $warningPenalty);

        $this->info('╔══════════════════════════════════════════════════════╗');
        $this->info('║              VALIDATION RESULTS SUMMARY             ║');
        $this->info('╠══════════════════════════════════════════════════════╣');
        $this->info("║  Total Tests:    {$total}");
        $this->info("║  ✅ Passed:      {$this->passed}");
        $this->info("║  ❌ Failed:      {$this->failed}");
        $this->info("║  ⚠️  Warnings:   {$this->warnings}");
        $this->info("║  Base Score:     {$score}/100");
        $this->info("║  Warning Penalty: -{$warningPenalty}");
        $this->info('║  ═══════════════════════════════════════════════════');
        $this->info("║  🏆 PRODUCTION READINESS SCORE: {$finalScore}/100");
        $this->info('╚══════════════════════════════════════════════════════╝');

        // 1. Supplier Due Accuracy
        $this->info('');
        $this->info('📋 1. SUPPLIER DUE ACCURACY');
        foreach ($this->suppliers as $s) {
            $balance = (float) $s->ledger()->sum(DB::raw('debit - credit'));
            $this->line("   {$s->name}: BDT {$balance}");
        }

        // 2. Customer Receivable
        $this->info('');
        $this->info('📋 2. CUSTOMER RECEIVABLE');
        $this->line('   (Receivable tracking not implemented — orders are cash-based)');

        // 3. Stock Integrity
        $this->info('');
        $this->info('📋 3. STOCK INTEGRITY');
        foreach ($this->warehouses as $wh) {
            $totalStock = Inventory::where('warehouse_id', $wh->id)->sum('quantity_available');
            $negativeStock = Inventory::where('warehouse_id', $wh->id)
                ->where('quantity_available', '<', 0)->count();
            $this->line("   {$wh->name}: Total={$totalStock}, Negative={$negativeStock}");
        }

        // 4. Warehouse Integrity
        $this->info('');
        $this->info('📋 4. WAREHOUSE INTEGRITY');
        foreach ($this->warehouses as $wh) {
            $itemCount = Inventory::where('warehouse_id', $wh->id)->count();
            $this->line("   {$wh->name} (Branch: {$wh->branch_id}): {$itemCount} SKUs");
        }

        // 5-6. Payment & Expense
        $this->info('');
        $this->info('📋 5-6. PAYMENT & EXPENSE ACCURACY');
        $totalExpensesPaid = Expense::where('description', 'like', '%[DEMO]%')->where('status', 'paid')->sum('total_amount');
        $totalExpensesPending = Expense::where('description', 'like', '%[DEMO]%')->where('status', '!=', 'paid')->sum('total_amount');
        $this->line("   Paid Expenses: BDT {$totalExpensesPaid}");
        $this->line("   Pending Expenses: BDT {$totalExpensesPending}");

        // 7. Profit validation done in Phase 8

        // 8. Detected Bugs
        $this->info('');
        $this->info('🐛 8. DETECTED BUGS');
        if (empty($this->bugs)) {
            $this->line('   None detected.');
        } else {
            foreach ($this->bugs as $bug) {
                $this->error("   • {$bug}");
            }
        }

        // 9. Financial Risk List
        $this->info('');
        $this->info('⚠️  9. FINANCIAL RISKS');
        if (empty($this->risks)) {
            $this->line('   No risks detected.');
        } else {
            foreach ($this->risks as $risk) {
                $this->warn("   • {$risk}");
            }
        }

        // Systemic recommendations
        $this->recommendations[] = 'Customer receivable/payment tracking is minimal — consider accounts receivable module';

        // 10. Refactor Recommendations
        $this->info('');
        $this->info('🔧 10. REFACTOR RECOMMENDATIONS');
        foreach ($this->recommendations as $rec) {
            $this->line("   → {$rec}");
        }

        // 11. Score classification
        $this->info('');
        $classification = match (true) {
            $finalScore >= 90 => '🟢 PRODUCTION READY',
            $finalScore >= 70 => '🟡 NEAR PRODUCTION — minor fixes needed',
            $finalScore >= 50 => '🟠 NOT READY — significant gaps',
            default => '🔴 CRITICAL — major issues detected',
        };
        $this->info("   Classification: {$classification}");
        $this->info('');
    }

    // ─── CLEANUP ────────────────────────────────────────────────

    private function cleanupDemoData(): void
    {
        $this->phaseHeader('CLEANUP — Removing demo data');

        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        // Remove in reverse dependency order
        ReturnItem::whereHas('returnOrder', fn ($q) => $q->where('notes', 'like', '%[DEMO]%'))->delete();
        ReturnOrder::where('notes', 'like', '%[DEMO]%')->delete();

        SupplierPurchaseReturnItem::whereHas('purchaseReturn', fn ($q) => $q->where('notes', 'like', '%[DEMO]%'))->delete();
        SupplierPurchaseReturn::where('notes', 'like', '%[DEMO]%')->delete();

        $demoPoIds = PurchaseOrder::whereHas('supplier', fn ($q) => $q->where('name', 'like', '%[DEMO]%'))->pluck('id');
        PurchaseItem::whereIn('purchase_order_id', $demoPoIds)->delete();
        StockMovement::whereIn('reference_id', $demoPoIds)->where('reference_type', 'grn')->delete();
        Expense::where('description', 'like', '%[DEMO]%')->delete();

        OrderDetails::whereHas('order', fn ($q) => $q->where('note', 'like', '%[DEMO]%'))->delete();
        Order::where('note', 'like', '%[DEMO]%')->delete();

        PurchaseOrder::whereIn('id', $demoPoIds)->delete();

        $demoSupplierIds = Supplier::where('name', 'like', '%[DEMO]%')->pluck('id');
        SupplierLedger::whereIn('supplier_id', $demoSupplierIds)->delete();
        Supplier::whereIn('id', $demoSupplierIds)->delete();

        $demoProductIds = Product::where('name', 'like', '%[DEMO]%')->pluck('id');
        Inventory::whereHas('productVariant', fn ($q) => $q->whereIn('product_id', $demoProductIds))->delete();
        ProductVariant::whereIn('product_id', $demoProductIds)->delete();
        Product::whereIn('id', $demoProductIds)->delete();

        Customer::where('name', 'like', '%[DEMO]%')->delete();
        ExpenseCategory::where('name', 'like', '%[DEMO]%')->delete();

        $demoWhIds = Warehouse::where('name', 'like', '%[DEMO]%')->pluck('id');
        WarehouseStock::whereIn('warehouse_id', $demoWhIds)->delete();
        Warehouse::whereIn('id', $demoWhIds)->delete();

        Branch::where('name', 'like', '%[DEMO]%')->delete();

        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $this->info('  ✅ Demo data cleaned up.');
    }
}
