<?php

namespace App\Http\Controllers\Frontend;

use App\Domain\Checkout\CheckoutTotalsService;
use App\Exceptions\StockReservationException;
use App\Helpers\ImageHelper;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\District;
use App\Models\GeneralSetting;
use App\Models\Order;
use App\Models\OrderDetails;
use App\Models\OrderStatus;
use App\Models\PartialOrder;
use App\Models\Payment;
use App\Models\PaymentGateway;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Review;
use App\Models\Shipping;
use App\Models\ShippingCharge;
use App\Models\SmsGateway;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use App\Services\PhoneBlockService;
use App\Services\VariantStockService;
use App\Services\WarehouseStockService;
use App\Support\Money;
use Auth;
use Brian2694\Toastr\Facades\Toastr;
use Cart;
use DB;
use Hash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Session;
use shurjopayv2\ShurjopayLaravelPackage8\Http\Controllers\ShurjopayController;
use Str;
use Throwable;

class CustomerController extends Controller
{
    private const GUEST_CHECKOUT_OTP_MAX_ATTEMPTS = 5;

    private const GUEST_CHECKOUT_OTP_LOCK_MINUTES = 10;

    public function __construct()
    {
        $this->middleware('customer', ['except' => ['register', 'store', 'verify', 'resendotp', 'account_verify', 'login', 'signin', 'logout', 'checkout', 'forgot_password', 'forgot_verify', 'forgot_reset', 'forgot_store', 'forgot_resend', 'order_save', 'order_success', 'order_track', 'order_track_result']]);
    }

    public function review(Request $request)
    {
        $this->validate($request, [
            'ratting' => 'required',
            'review' => 'required',
        ]);

        // data save
        $review = new Review;
        $review->name = Auth::guard('customer')->user()->name ? Auth::guard('customer')->user()->name : 'N / A';
        $review->email = Auth::guard('customer')->user()->email ? Auth::guard('customer')->user()->email : 'N / A';
        $review->product_id = $request->product_id;
        $review->review = $request->review;
        $review->ratting = $request->ratting;
        $review->customer_id = Auth::guard('customer')->user()->id;
        $review->status = 'pending';
        $review->save();

        Toastr::success('Thanks, Your review send successfully', 'Success!');

        return redirect()->back();
    }

    public function login()
    {
        return view('frontEnd.layouts.customer.login');
    }

    public function signin(Request $request)
    {
        $auth_check = Customer::where('phone', $request->phone)->first();
        if ($auth_check) {
            if (Auth::guard('customer')->attempt(['phone' => $request->phone, 'password' => $request->password])) {
                Toastr::success('You are login successfully', 'success!');
                if (Cart::instance('shopping')->count() > 0) {
                    return redirect()->route('customer.checkout');
                }

                return redirect()->intended('customer/account');
            }
            Toastr::error('message', 'Opps! your phone or password wrong');

            return redirect()->back();
        } else {
            Toastr::error('message', 'Sorry! You have no account');

            return redirect()->back();
        }
    }

    public function register()
    {
        return view('frontEnd.layouts.customer.register');
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'name' => 'required',
            'phone' => 'required|unique:customers',
            'password' => 'required|min:6',
        ]);

        $last_id = Customer::orderBy('id', 'desc')->first();
        $last_id = $last_id ? $last_id->id + 1 : 1;
        $store = new Customer;
        $store->name = $request->name;
        $store->slug = strtolower(Str::slug($request->name.'-'.$last_id));
        $store->phone = $request->phone;
        $store->email = $request->email;
        $store->password = bcrypt($request->password);
        $store->verify = 1;
        $store->status = 'active';
        $store->save();

        Toastr::success('Success', 'Account Create Successfully');

        return redirect()->route('customer.login');
    }

    public function verify()
    {
        return view('frontEnd.layouts.customer.verify');
    }

    public function resendotp(Request $request)
    {
        $customer_info = Customer::where('phone', session::get('verify_phone'))->first();
        if (! $customer_info) {
            Toastr::error('Verification session expired. Please register or login again.', 'Failed!');

            return redirect()->route('customer.login');
        }
        $customer_info->verify = rand(1111, 9999);
        $customer_info->save();
        $site_setting = GeneralSetting::where('status', 1)->first();
        $sms_gateway = SmsGateway::where('status', 1)->first();
        if ($sms_gateway) {
            $url = "$sms_gateway->url";
            $data = [
                'api_key' => "$sms_gateway->api_key",
                'number' => $customer_info->phone,
                'type' => 'text',
                'senderid' => "$sms_gateway->serderid",
                'message' => "Dear $customer_info->name!\r\nYour account verify OTP is $customer_info->verify \r\nThank you for using $site_setting->name",
            ];
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
            $response = curl_exec($ch);
            curl_close($ch);

        }
        Toastr::success('Success', 'Resend code send successfully');

        return redirect()->back();
    }

    public function account_verify(Request $request)
    {
        $this->validate($request, [
            'otp' => 'required',
        ]);
        $customer_info = Customer::where('phone', session::get('verify_phone'))->first();
        if (! $customer_info) {
            Toastr::error('Verification session expired. Please try again.', 'Failed!');

            return redirect()->route('customer.login');
        }
        if ($customer_info->verify != $request->otp) {
            Toastr::error('Success', 'Your OTP not match');

            return redirect()->back();
        }

        $customer_info->verify = 1;
        $customer_info->status = 'active';
        $customer_info->save();
        Auth::guard('customer')->loginUsingId($customer_info->id);

        return redirect()->route('customer.account');
    }

    public function forgot_password()
    {
        return view('frontEnd.layouts.customer.forgot_password');
    }

    public function forgot_verify(Request $request)
    {
        $customer_info = Customer::where('phone', $request->phone)->first();
        if (! $customer_info) {
            Toastr::error('Your phone number not found');

            return back();
        }
        $customer_info->forgot = rand(1111, 9999);
        $customer_info->save();
        $site_setting = GeneralSetting::where('status', 1)->first();
        $sms_gateway = SmsGateway::where(['status' => 1, 'forget_pass' => 1])->first();
        if ($sms_gateway) {
            $url = "$sms_gateway->url";
            $data = [
                'api_key' => "$sms_gateway->api_key",
                'number' => $customer_info->phone,
                'type' => 'text',
                'senderid' => "$sms_gateway->serderid",
                'message' => "Dear $customer_info->name!\r\nYour forgot password verify OTP is $customer_info->forgot \r\nThank you for using $site_setting->name",
            ];
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
            $response = curl_exec($ch);
            curl_close($ch);
        }

        session::put('verify_phone', $request->phone);
        Toastr::success('Your account register successfully');

        return redirect()->route('customer.forgot.reset');
    }

    public function forgot_resend(Request $request)
    {
        $customer_info = Customer::where('phone', session::get('verify_phone'))->first();
        if (! $customer_info) {
            Toastr::error('Reset session expired. Please start forgot password again.', 'Failed!');

            return redirect()->route('customer.forgot.password');
        }
        $customer_info->forgot = rand(1111, 9999);
        $customer_info->save();
        $site_setting = GeneralSetting::where('status', 1)->first();
        $sms_gateway = SmsGateway::where(['status' => 1])->first();
        if ($sms_gateway) {
            $url = "$sms_gateway->url";
            $data = [
                'api_key' => "$sms_gateway->api_key",
                'number' => $customer_info->phone,
                'type' => 'text',
                'senderid' => "$sms_gateway->serderid",
                'message' => "Dear $customer_info->name!\r\nYour forgot password verify OTP is $customer_info->forgot \r\nThank you for using $site_setting->name",
            ];
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
            $response = curl_exec($ch);
            curl_close($ch);

        }

        Toastr::success('Success', 'Resend code send successfully');

        return redirect()->back();
    }

    public function forgot_reset()
    {
        if (! Session::get('verify_phone')) {
            Toastr::error('Something wrong please try again');

            return redirect()->route('customer.forgot.password');
        }

        return view('frontEnd.layouts.customer.forgot_reset');
    }

    public function forgot_store(Request $request)
    {

        $customer_info = Customer::where('phone', session::get('verify_phone'))->first();
        if (! $customer_info) {
            Toastr::error('Reset session expired. Please start forgot password again.', 'Failed!');

            return redirect()->route('customer.forgot.password');
        }

        if ($customer_info->forgot != $request->otp) {
            Toastr::error('Success', 'Your OTP not match');

            return redirect()->back();
        }

        $customer_info->forgot = 1;
        $customer_info->password = bcrypt($request->password);
        $customer_info->save();
        if (Auth::guard('customer')->attempt(['phone' => $customer_info->phone, 'password' => $request->password])) {
            Session::forget('verify_phone');
            Toastr::success('You are login successfully', 'success!');

            return redirect()->intended('customer/account');
        }
    }

    public function account()
    {
        return view('frontEnd.layouts.customer.account');
    }

    public function logout(Request $request)
    {
        Auth::guard('customer')->logout();
        Toastr::success('You are logout successfully', 'success!');

        return redirect()->route('customer.login');
    }

    public function checkout()
    {
        $shippingcharge = ShippingCharge::where('status', 1)->get();
        $select_charge = ShippingCharge::where('status', 1)->first();
        $bkash_gateway = PaymentGateway::where(['status' => 1, 'type' => 'bkash'])->first();
        $shurjopay_gateway = PaymentGateway::where(['status' => 1, 'type' => 'shurjopay'])->first();
        $summary = null;

        if ($select_charge) {
            Session::put('shipping', $select_charge->amount);
            if (config('features.checkout.server_breakdown_v2', true) && Cart::instance('shopping')->count() > 0) {
                try {
                    $summary = app(CheckoutTotalsService::class)->calculateForShoppingCart(
                        Cart::instance('shopping')->content(),
                        (int) $select_charge->id,
                        Money::fromMajor((float) Session::get('discount', 0))
                    );
                    Session::put('shipping', Money::toMajorFloat((int) $summary['shipping_minor']));
                } catch (Throwable $e) {
                    report($e);
                }
            }
        } else {
            Log::warning('No active shipping options found');
            Session::put('shipping', 0);
        }

        return view('frontEnd.layouts.customer.checkout', compact('shippingcharge', 'bkash_gateway', 'shurjopay_gateway', 'summary'));
    }

    public function request_checkout_otp(Request $request)
    {
        $validated = $request->validate([
            'phone' => 'required|string|max:30',
        ]);

        $blockedResponse = $this->ensurePhoneAllowedForCheckout($validated['phone'], true);
        if ($blockedResponse) {
            return $blockedResponse;
        }

        if (Auth::guard('customer')->check()) {
            return response()->json(['success' => true, 'message' => 'Logged-in customers do not need OTP.']);
        }

        $lockUntil = (int) Session::get('guest_checkout_otp_lock_until', 0);
        if ($lockUntil > now()->timestamp) {
            return response()->json([
                'success' => false,
                'message' => 'Too many invalid OTP attempts. Please wait before requesting a new OTP.',
            ], 429);
        }

        $otp = (string) random_int(100000, 999999);
        Session::put('guest_checkout_otp_phone', $validated['phone']);
        Session::put('guest_checkout_otp_code_hash', $this->hashOtpCode($otp));
        Session::put('guest_checkout_otp_expires_at', now()->addMinutes(10)->timestamp);
        Session::forget('guest_checkout_otp_verified');
        Session::forget('guest_checkout_otp_verified_phone');
        Session::forget('guest_checkout_otp_verified_at');
        Session::put('guest_checkout_otp_attempts', 0);
        Session::forget('guest_checkout_otp_lock_until');

        $siteSetting = GeneralSetting::where('status', 1)->first();
        $message = 'Your checkout OTP is '.$otp.'. Valid for 10 minutes. '.($siteSetting->name ?? config('app.name'));
        $this->sendSms($validated['phone'], $message);

        return response()->json([
            'success' => true,
            'message' => 'OTP sent successfully.',
        ]);
    }

    public function verify_checkout_otp(Request $request)
    {
        $validated = $request->validate([
            'phone' => 'required|string|max:30',
            'otp' => 'required|digits:6',
        ]);

        $blockedResponse = $this->ensurePhoneAllowedForCheckout($validated['phone'], true);
        if ($blockedResponse) {
            return $blockedResponse;
        }

        $lockUntil = (int) Session::get('guest_checkout_otp_lock_until', 0);
        if ($lockUntil > now()->timestamp) {
            return response()->json([
                'success' => false,
                'message' => 'Too many invalid OTP attempts. Please wait before trying again.',
            ], 429);
        }

        $phone = (string) Session::get('guest_checkout_otp_phone');
        $hash = (string) Session::get('guest_checkout_otp_code_hash');
        $expiresAt = (int) Session::get('guest_checkout_otp_expires_at', 0);

        if ($phone === '' || $hash === '' || $expiresAt < now()->timestamp) {
            $this->registerFailedGuestCheckoutOtpAttempt();

            return response()->json([
                'success' => false,
                'message' => 'OTP expired. Please request a new one.',
            ], 422);
        }

        if ($phone !== $validated['phone'] || ! hash_equals($hash, $this->hashOtpCode($validated['otp']))) {
            $this->registerFailedGuestCheckoutOtpAttempt();

            return response()->json([
                'success' => false,
                'message' => 'Invalid OTP.',
            ], 422);
        }

        Session::put('guest_checkout_otp_verified', true);
        Session::put('guest_checkout_otp_verified_phone', $phone);
        Session::put('guest_checkout_otp_verified_at', now()->timestamp);
        Session::put('guest_checkout_otp_attempts', 0);
        Session::forget('guest_checkout_otp_lock_until');
        Session::forget('guest_checkout_otp_code_hash');
        Session::forget('guest_checkout_otp_expires_at');

        return response()->json([
            'success' => true,
            'message' => 'OTP verified successfully.',
        ]);
    }

    public function order_save(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:30',
            'address' => 'required|string|max:1000',
            'area' => 'required|integer|exists:shipping_charges,id',
            'note' => 'nullable|string|max:1000',
            'payment_method' => 'required|in:cod,bkash,shurjopay',
        ]);

        $blockedResponse = $this->ensurePhoneAllowedForCheckout($validated['phone']);
        if ($blockedResponse) {
            return $blockedResponse;
        }

        if (! Auth::guard('customer')->check() && config('features.checkout.guest_otp_required', false)) {
            $otpVerified = (bool) Session::get('guest_checkout_otp_verified', false);
            $verifiedPhone = (string) Session::get('guest_checkout_otp_verified_phone', '');
            $verifiedAt = (int) Session::get('guest_checkout_otp_verified_at', 0);
            $verificationWindowSeconds = (int) config('features.checkout.guest_otp_verification_window_seconds', 1800);
            $verificationWindowSeconds = max(60, $verificationWindowSeconds);
            $isOtpStillFresh = $verifiedAt > 0 && (now()->timestamp - $verifiedAt) <= $verificationWindowSeconds;

            if (! $otpVerified || ! $isOtpStillFresh || ! hash_equals($verifiedPhone, $validated['phone'])) {
                Toastr::error('Please verify checkout OTP for this phone number before placing the order.', 'Verification Required');

                return redirect()->back()->withInput();
            }
        }

        if (Cart::instance('shopping')->count() <= 0) {
            Toastr::error('Your shopping empty', 'Failed!');

            return redirect()->back();
        }

        // Validate stock availability before processing order
        $stockValidation = $this->validateCartStock();
        if (! $stockValidation['valid']) {
            Toastr::error($stockValidation['message'], 'Stock Error!');

            return redirect()->back();
        }

        $warehouseId = Session::get('warehouse_id');
        if (! $warehouseId) {
            Toastr::error('No warehouse selected for this checkout. Please refresh your cart and try again.', 'Stock Error!');

            return redirect()->back();
        }

        $cartItems = Cart::instance('shopping')->content();
        $discountMinor = Money::fromMajor((float) Session::get('discount', 0));
        $totals = app(CheckoutTotalsService::class)->calculateForShoppingCart(
            $cartItems,
            (int) $validated['area'],
            $discountMinor
        );
        $lineByRowId = collect($totals['lines'])->keyBy('row_id');
        $shipping_area = ShippingCharge::where('id', $validated['area'])->firstOrFail();

        if (Auth::guard('customer')->user()) {
            $customer_id = (int) Auth::guard('customer')->user()->id;
        } else {
            $exits_customer = Customer::where('phone', $validated['phone'])->select('phone', 'id')->first();
            if ($exits_customer) {
                $customer_id = (int) $exits_customer->id;
            } else {
                $password = rand(111111, 999999);
                $store = new Customer;
                $store->name = $validated['name'];
                $store->slug = strtolower(Str::slug($validated['name'].'-'.$validated['phone']));
                $store->phone = $validated['phone'];
                $store->password = bcrypt($password);
                $store->verify = 1;
                $store->status = 'active';
                $store->save();
                $customer_id = (int) $store->id;
            }
        }

        // merge partial order
        $deviceId = $request->cookie('partial_device_id_v1') ?? $request->input('device_id');
        if ($deviceId) {
            $partial = PartialOrder::where('device_id', $deviceId)->where('status', 'incomplete')->first();
            if ($partial) {
                $validated['name'] = $validated['name'] ?: $partial->name;
                $validated['phone'] = $validated['phone'] ?: $partial->phone;
                $validated['address'] = $validated['address'] ?: $partial->address;
                $partial->update(['status' => 'completed']);
            }
        }

        /** @var \App\Services\WarehouseStockService $warehouseStockService */
        $warehouseStockService = app(WarehouseStockService::class);

        // Create the order + reserve stock atomically to avoid race conditions.
        try {
            $order = DB::transaction(function () use ($customer_id, $validated, $shipping_area, $warehouseStockService, $warehouseId, $cartItems, $totals, $lineByRowId) {
                $order = new Order;
                $order->invoice_id = Order::generateInvoiceId();
                $order->amount = Money::toMajorInt($totals['final_minor']);
                $order->amount_minor = $totals['final_minor'];
                $order->discount = Money::toMajorInt($totals['discount_minor']);
                $order->discount_minor = $totals['discount_minor'];
                $order->shipping_charge = Money::toMajorInt($totals['shipping_minor']);
                $order->shipping_charge_minor = $totals['shipping_minor'];
                $order->currency = $totals['currency'];
                $order->order_public_token = (string) Str::uuid();
                $order->customer_id = $customer_id;
                $order->warehouse_id = $warehouseId;
                $order->order_status = 1;
                $order->note = $validated['note'] ?? null;
                $order->save();

                $shipping = new Shipping;
                $shipping->order_id = $order->id;
                $shipping->customer_id = $customer_id;
                $shipping->name = $validated['name'];
                $shipping->phone = $validated['phone'];
                $shipping->address = $validated['address'];
                $shipping->area = $shipping_area->name;
                $shipping->save();

                $payment = new Payment;
                $payment->order_id = $order->id;
                $payment->customer_id = $customer_id;
                $payment->payment_method = $validated['payment_method'];
                $payment->gateway = $validated['payment_method'] === 'cod' ? 'cod' : $validated['payment_method'];
                $payment->amount = Money::toMajorInt($totals['final_minor']);
                $payment->amount_minor = $totals['final_minor'];
                $payment->currency = $totals['currency'];
                $payment->payment_status = 'pending';
                $payment->save();

                foreach ($cartItems as $cart) {
                    $line = $lineByRowId->get((string) $cart->rowId);
                    if (! $line) {
                        throw new \RuntimeException('Unable to resolve checkout line item.');
                    }

                    $variant = $this->resolveCartVariant($cart);
                    $itemWarehouseId = (int) ($cart->options->warehouse_id ?? $warehouseId);

                    $order_details = new OrderDetails;
                    $order_details->order_id = $order->id;
                    $order_details->product_id = (int) $cart->id;
                    $order_details->product_variant_id = $variant ? $variant->id : null;
                    $order_details->warehouse_id = $itemWarehouseId;
                    $order_details->product_name = $cart->name;
                    $order_details->purchase_price = (int) ($cart->options->purchase_price ?? 0);
                    $order_details->purchase_price_minor = Money::fromMajor((float) ($cart->options->purchase_price ?? 0));
                    $order_details->product_color = $cart->options->product_color ?? null;
                    $order_details->product_size = $cart->options->product_size ?? null;
                    $order_details->sale_price = Money::toMajorInt((int) $line['unit_minor']);
                    $order_details->sale_price_minor = (int) $line['unit_minor'];
                    $order_details->currency = $totals['currency'];
                    $order_details->qty = (int) $line['qty'];
                    $order_details->save();

                    if ($variant) {
                        $variantStockService = app(VariantStockService::class);
                        try {
                            $variantStockService->reserveStock($itemWarehouseId, $variant->id, $order_details->qty, $order->id);
                        } catch (Throwable $reserveException) {
                            // Production-grade diagnostics: capture exact cart/variant/warehouse + both stock sources.
                            Log::error('Checkout reserveStock failed (variant)', [
                                'exception_class' => get_class($reserveException),
                                'exception_message' => $reserveException->getMessage(),
                                'cart_product_id' => (int) $cart->id,
                                'cart_row_id' => (string) $cart->rowId,
                                'cart_qty' => (int) $order_details->qty,
                                'cart_options' => [
                                    'warehouse_id' => $cart->options->warehouse_id ?? null,
                                    'product_variant_id' => $cart->options->product_variant_id ?? null,
                                    'variant_id' => $cart->options->variant_id ?? null,
                                    'product_size' => $cart->options->product_size ?? null,
                                    'product_color' => $cart->options->product_color ?? null,
                                ],
                                'resolved_variant_id' => (int) ($variant->id ?? 0),
                                'resolved_variant_product_id' => (int) ($variant->product_id ?? 0),
                                'session_warehouse_id' => (int) ($warehouseId ?? 0),
                                'itemWarehouseId' => (int) $itemWarehouseId,
                            ]);

                            // Check available stock from the stock-management tables only.
                            $availableQty = (float) (\App\Models\Inventory::query()
                                ->where('warehouse_id', $itemWarehouseId)
                                ->where('product_variant_id', (int) $variant->id)
                                ->selectRaw('CASE WHEN (quantity_available - quantity_reserved) > 0 THEN (quantity_available - quantity_reserved) ELSE 0 END AS sellable_stock')
                                ->value('sellable_stock') ?? 0);

                            $availableQtyFromWarehouseStock = null;
                            if ($availableQty <= 0) {
                                $availableQtyFromWarehouseStock = (float) (\App\Models\WarehouseStock::query()
                                    ->where('warehouse_id', $itemWarehouseId)
                                    ->where('product_variant_id', (int) $variant->id)
                                    ->selectRaw('CASE WHEN (physical_quantity - reserved_quantity) > 0 THEN (physical_quantity - reserved_quantity) ELSE 0 END AS sellable_stock')
                                    ->value('sellable_stock') ?? 0);

                                $availableQty = $availableQtyFromWarehouseStock;
                            }

                            Log::error('Checkout reserveStock stock snapshot (variant)', [
                                'itemWarehouseId' => (int) $itemWarehouseId,
                                'variant_id' => (int) $variant->id,
                                'available_qty_inventory_sellable' => (float) $availableQty,
                                'available_qty_warehouse_stock_sellable' => $availableQtyFromWarehouseStock,
                                'requested_qty' => (float) $order_details->qty,
                            ]);

                            if ($availableQty <= 0) {
                                throw StockReservationException::stockNotFound(
                                    $itemWarehouseId,
                                    (int) $order_details->product_id,
                                    (float) $order_details->qty,
                                    (int) ($variant->id ?? 0)
                                );
                            }

                            throw StockReservationException::insufficientStock(
                                $itemWarehouseId,
                                (int) $order_details->product_id,
                                (float) $order_details->qty,
                                $availableQty,
                                (int) ($variant->id ?? 0)
                            );
                        }
                    } else {

                        try {
                            $warehouseStockService->reserveStock(
                                $itemWarehouseId,
                                (int) $order_details->product_id,
                                (float) $order_details->qty,
                                (int) $order->id,
                                'order',
                                "Reserved stock for order #{$order->id}"
                            );
                        } catch (Throwable $reserveException) {
                            $availableQty = (float) (WarehouseStock::query()
                                ->where('warehouse_id', $itemWarehouseId)
                                ->where('product_id', (int) $order_details->product_id)
                                ->selectRaw('CASE WHEN (physical_quantity - reserved_quantity) > 0 THEN (physical_quantity - reserved_quantity) ELSE 0 END AS sellable_stock')
                                ->value('sellable_stock') ?? 0);

                            if ($availableQty <= 0) {
                                throw StockReservationException::stockNotFound(
                                    $itemWarehouseId,
                                    (int) $order_details->product_id,
                                    (float) $order_details->qty
                                );
                            }

                            throw StockReservationException::insufficientStock(
                                $itemWarehouseId,
                                (int) $order_details->product_id,
                                (float) $order_details->qty,
                                $availableQty
                            );
                        }
                    }
                }

                app(\App\Services\LegacyAccountingPostingService::class)->postSale($order->fresh());

                return $order;
            });
        } catch (StockReservationException $e) {
            $cartItem = null;

            // Prefer matching the failing variant line (when available) to avoid wrong product names.
            if ($e->getProductVariantId()) {
                $cartItem = $cartItems->first(function ($ci) use ($e) {
                    $ciProductId = (int) ($ci->id ?? 0);
                    $ciVariantId = (int) data_get($ci, 'options.product_variant_id', data_get($ci, 'options.variant_id', 0));

                    return $ciProductId === (int) $e->getProductId() && $ciVariantId === (int) $e->getProductVariantId();
                });
            }

            // Fallback to product-level match.
            if (! $cartItem) {
                $cartByProductId = $cartItems->keyBy('id');
                $cartItem = $cartByProductId->get($e->getProductId());
            }

            $productName = $cartItem ? $cartItem->name : ('Product #'.$e->getProductId());


            $warehouseName = null;
            try {
                $warehouseName = optional(Warehouse::select('id', 'name')->find($warehouseId))->name;
            } catch (Throwable $warehouseLookupError) {
                $warehouseName = null;
            }

            Log::warning('Checkout stock reservation failed', [
                'reason' => $e->getReason(),
                'warehouse_id' => $e->getWarehouseId(),
                'warehouse_name' => $warehouseName,
                'product_id' => $e->getProductId(),
                'product_name' => $productName,
                'requested_qty' => $e->getRequestedQuantity(),
                'available_qty' => $e->getAvailableQuantity(),
                'customer_id' => $customer_id,
            ]);

            Toastr::error("Insufficient stock for '{$productName}'.", 'Stock Error!');

            return redirect()->back();
        } catch (Throwable $e) {
            Log::error('Checkout order placement failed', [
                'warehouse_id' => $warehouseId,
                'customer_id' => $customer_id,
                'exception_class' => get_class($e),
                'exception' => $e->getMessage(),
                'exception_file' => $e->getFile(),
                'exception_line' => $e->getLine(),
            ]);

            Toastr::error('Unable to place order right now. Please try again.', 'Failed!');

            return redirect()->back()->withInput();
        }

        PartialOrder::where('phone', $validated['phone'])->delete();
        if (! Auth::guard('customer')->check()) {
            $this->clearGuestCheckoutOtpSession();
        }

        Toastr::success('Thanks, your order has been created successfully.', 'Success!');
        $site_setting = GeneralSetting::where('status', 1)->first();
        $this->sendSms(
            $validated['phone'],
            "Dear {$validated['name']}!\r\nYour order#{$order->invoice_id} has been created. Thank you for using ".($site_setting->name ?? config('app.name'))
        );

        $successUrl = route('customer.order_success', ['id' => $order->id, 't' => $order->order_public_token]);
        if ($validated['payment_method'] === 'bkash') {
            return redirect('/bkash/checkout-url/create?order_id='.$order->id);
        }

        if ($validated['payment_method'] === 'shurjopay') {
            $customerEmail = Auth::guard('customer')->user()?->email
                ?? Customer::query()->whereKey($customer_id)->value('email')
                ?? ('customer+'.$order->id.'@example.invalid');

            $info = [
                'currency' => 'BDT',
                'amount' => Money::toMajorInt((int) $order->amount_minor),
                'order_id' => (string) $order->invoice_id,
                'discsount_amount' => 0,
                'disc_percent' => 0,
                'client_ip' => $request->ip(),
                'customer_name' => $validated['name'],
                'customer_phone' => $validated['phone'],
                'email' => $customerEmail,
                'customer_address' => $validated['address'],
                'customer_city' => (string) $shipping_area->name,
                'customer_state' => (string) $shipping_area->name,
                'customer_postcode' => '1212',
                'customer_country' => 'BD',
                'value1' => $order->id,
            ];

            Session::put('pending_online_order_id', $order->id);
            $shurjopay_service = new ShurjopayController;

            return $shurjopay_service->checkout($info);
        }

        Cart::instance('shopping')->destroy();

        return redirect($successUrl);
    }

    public function orders()
    {
        $orders = Order::where('customer_id', Auth::guard('customer')->user()->id)->with('status')->latest()->get();

        return view('frontEnd.layouts.customer.orders', compact('orders'));
    }

    public function order_success(Request $request, $id)
    {
        $order = Order::with(['orderdetails', 'shipping', 'payment', 'status'])->where('id', $id)->firstOrFail();

        if (Auth::guard('customer')->check()) {
            if ((int) Auth::guard('customer')->id() !== (int) $order->customer_id) {
                abort(403, 'Unauthorized order access.');
            }

            $shouldFirePurchasePixel = $this->markOrderPurchasePixelAsFired($order);

            return view('frontEnd.layouts.customer.order_success', compact('order', 'shouldFirePurchasePixel'));
        }

        $token = (string) $request->query('t', '');
        if ($token === '' || ! hash_equals((string) ($order->order_public_token ?? ''), $token)) {
            abort(403, 'Unauthorized order access.');
        }

        $shouldFirePurchasePixel = $this->markOrderPurchasePixelAsFired($order);

        return view('frontEnd.layouts.customer.order_success', compact('order', 'shouldFirePurchasePixel'));
    }

    public function invoice(Request $request)
    {
        $order = Order::where(['id' => $request->id, 'customer_id' => Auth::guard('customer')->user()->id])->with('orderdetails', 'payment', 'shipping', 'customer')->firstOrFail();

        return view('frontEnd.layouts.customer.invoice', compact('order'));
    }

    public function order_note(Request $request)
    {
        $order = Order::where(['id' => $request->id, 'customer_id' => Auth::guard('customer')->user()->id])->firstOrFail();

        return view('frontEnd.layouts.customer.order_note', compact('order'));
    }

    public function profile_edit(Request $request)
    {
        $profile_edit = Customer::where(['id' => Auth::guard('customer')->user()->id])->firstOrFail();
        $districts = District::distinct()->select('district')->get();
        $areas = District::where(['district' => $profile_edit->district])->select('area_name', 'id')->get();

        return view('frontEnd.layouts.customer.profile_edit', compact('profile_edit', 'districts', 'areas'));
    }

    public function profile_update(Request $request)
    {
        $update_data = Customer::where(['id' => Auth::guard('customer')->user()->id])->firstOrFail();

        $image = $request->file('image');
        if ($image) {
            // image processing with native GD
            $name = time().'-'.$image->getClientOriginalName();
            $name = preg_replace('"\.(jpg|jpeg|png|webp)$"', '.webp', $name);
            $name = strtolower(Str::slug($name));
            $uploadpath = 'public/uploads/customer/';
            $imageUrl = $uploadpath.$name;
            ImageHelper::resizeAndSaveWebp($image->getRealPath(), $imageUrl, 120, 120, 90);
        } else {
            $imageUrl = $update_data->image;
        }

        $update_data->name = $request->name;
        $update_data->phone = $request->phone;
        $update_data->email = $request->email;
        $update_data->address = $request->address;
        $update_data->district = $request->district;
        $update_data->area = $request->area;
        $update_data->image = $imageUrl;
        $update_data->save();

        Toastr::success('Your profile update successfully', 'Success!');

        return redirect()->route('customer.account');
    }

    public function order_track()
    {
        return view('frontEnd.layouts.customer.order_track');
    }

    public function order_track_result(Request $request)
    {
        $validated = $request->validate([
            'phone' => 'nullable|string|max:30|required_without:invoice_id',
            'invoice_id' => 'nullable|string|max:60|required_without:phone',
        ]);

        $phone = trim((string) ($validated['phone'] ?? ''));
        $invoiceId = Str::upper(trim((string) ($validated['invoice_id'] ?? '')));

        $ordersQuery = Order::query()
            ->with([
                'status:id,name',
                'orderdetails:id,order_id,product_name,qty,sale_price',
                'shipping:id,order_id,phone,name,address,area',
            ]);

        if ($phone !== '') {
            $normalizedPhone = preg_replace('/\D+/', '', $phone);

            $ordersQuery->whereHas('shipping', function ($query) use ($phone, $normalizedPhone) {
                $query->where('phone', $phone);

                if (! empty($normalizedPhone)) {
                    $query->orWhereRaw(
                        "REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(phone, ' ', ''), '-', ''), '+', ''), '(', ''), ')', '') = ?",
                        [$normalizedPhone]
                    );
                }
            });
        }

        if ($invoiceId !== '') {
            $ordersQuery->whereRaw('UPPER(invoice_id) = ?', [$invoiceId]);
        }

        $orders = $ordersQuery
            ->orderByDesc('id')
            ->get();

        if ($orders->isEmpty()) {
            Toastr::error('No order found with the provided phone or invoice ID.', 'Failed!');

            return redirect()->back()->withInput();
        }

        // Fetch live Steadfast courier status for orders that have tracking data
        $steadfastStatuses = [];
        $steadfast = app(\App\Services\SteadfastService::class);
        if ($steadfast->isConfigured()) {
            foreach ($orders as $order) {
                if ($order->steadfast_tracking_code || $order->steadfast_consignment_id) {
                    try {
                        $result = null;
                        if ($order->steadfast_consignment_id) {
                            $result = $steadfast->statusByConsignmentId((int) $order->steadfast_consignment_id);
                        } elseif ($order->steadfast_tracking_code) {
                            $result = $steadfast->statusByTrackingCode($order->steadfast_tracking_code);
                        }

                        if ($result && isset($result['delivery_status'])) {
                            $steadfastStatuses[$order->id] = $result;
                            // Also update the cached status on the order
                            if ($order->steadfast_status !== $result['delivery_status']) {
                                $order->steadfast_status = $result['delivery_status'];
                                $order->save();
                            }
                        }
                    } catch (\Throwable $e) {
                        // Silently fail — show internal status as fallback
                        \Illuminate\Support\Facades\Log::warning('Customer tracking: Steadfast status fetch failed', [
                            'order_id' => $order->id,
                            'message' => $e->getMessage(),
                        ]);
                    }
                }
            }
        }

        return view('frontEnd.layouts.customer.tracking_result', compact('orders', 'steadfastStatuses'));
    }

    public function change_pass()
    {
        return view('frontEnd.layouts.customer.change_password');
    }

    /**
     * Resolve cart item variant deterministically.
     *
     * Priority:
     * 1) Explicit variant id from cart options.
     * 2) Exact active match by size/color.
     * 3) Single active variant fallback.
     */
    private function resolveCartVariant($cartItem): ?ProductVariant
    {
        if (! Schema::hasTable('product_variants')) {
            return null;
        }

        $productId = (int) ($cartItem->id ?? 0);
        if ($productId <= 0) {
            return null;
        }

        try {
            $variantId = (int) data_get($cartItem, 'options.product_variant_id', data_get($cartItem, 'options.variant_id', 0));
            if ($variantId > 0) {
                $explicitVariant = ProductVariant::query()
                    ->whereKey($variantId)
                    ->where('product_id', $productId)
                    ->where('status', 'active')
                    ->first();

                if ($explicitVariant) {
                    return $explicitVariant;
                }

                Log::warning('Cart contains invalid/inactive variant id', [
                    'product_id' => $productId,
                    'variant_id' => $variantId,
                ]);
            }

            $size = trim((string) data_get($cartItem, 'options.product_size', ''));
            $color = trim((string) data_get($cartItem, 'options.product_color', ''));

            if ($size !== '' || $color !== '') {
                $query = ProductVariant::query()
                    ->where('product_id', $productId)
                    ->where('status', 'active');

                if ($size !== '') {
                    $query->where('size', $size);
                }
                if ($color !== '') {
                    $query->where('color', $color);
                }

                $matches = $query->limit(2)->get();
                if ($matches->count() === 1) {
                    return $matches->first();
                }

                if ($matches->count() > 1) {
                    Log::warning('Ambiguous variant resolution by size/color', [
                        'product_id' => $productId,
                        'size' => $size,
                        'color' => $color,
                    ]);
                }
            }

            $singleVariant = ProductVariant::query()
                ->where('product_id', $productId)
                ->where('status', 'active')
                ->limit(2)
                ->get();

            return $singleVariant->count() === 1 ? $singleVariant->first() : null;
        } catch (Throwable $exception) {
            Log::warning('Variant resolution skipped due runtime issue', [
                'product_id' => $productId,
                'message' => $exception->getMessage(),
            ]);

            return null;
        }
    }

    private function sendSms(string $phone, string $message): void
    {
        $smsGateway = SmsGateway::where(['status' => 1, 'order' => '1'])->first();
        if (! $smsGateway) {
            return;
        }

        try {
            $data = [
                'api_key' => (string) $smsGateway->api_key,
                'number' => $phone,
                'type' => 'text',
                'senderid' => (string) $smsGateway->serderid,
                'message' => $message,
            ];

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, (string) $smsGateway->url);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
            curl_exec($ch);
            curl_close($ch);
        } catch (Throwable $e) {
            report($e);
        }
    }

    private function hashOtpCode(string $otp): string
    {
        return hash_hmac('sha256', $otp, (string) config('app.key', 'checkout-otp'));
    }

    private function registerFailedGuestCheckoutOtpAttempt(): void
    {
        $attempts = (int) Session::get('guest_checkout_otp_attempts', 0) + 1;
        Session::put('guest_checkout_otp_attempts', $attempts);

        if ($attempts >= self::GUEST_CHECKOUT_OTP_MAX_ATTEMPTS) {
            Session::put('guest_checkout_otp_lock_until', now()->addMinutes(self::GUEST_CHECKOUT_OTP_LOCK_MINUTES)->timestamp);
            Session::forget('guest_checkout_otp_code_hash');
            Session::forget('guest_checkout_otp_expires_at');
            Session::forget('guest_checkout_otp_verified');
            Session::forget('guest_checkout_otp_verified_phone');
            Session::forget('guest_checkout_otp_verified_at');
        }
    }

    private function clearGuestCheckoutOtpSession(): void
    {
        Session::forget('guest_checkout_otp_phone');
        Session::forget('guest_checkout_otp_code_hash');
        Session::forget('guest_checkout_otp_expires_at');
        Session::forget('guest_checkout_otp_verified');
        Session::forget('guest_checkout_otp_verified_phone');
        Session::forget('guest_checkout_otp_verified_at');
        Session::forget('guest_checkout_otp_attempts');
        Session::forget('guest_checkout_otp_lock_until');
    }

    private function markOrderPurchasePixelAsFired(Order $order): bool
    {
        if (! $this->isOrderPurchasePixelEligible($order)) {
            return false;
        }

        if (! Schema::hasTable('orders') || ! Schema::hasColumn('orders', 'purchase_pixel_fired_at')) {
            $firedOrders = collect((array) Session::get('purchase_pixel_fired_orders', []))
                ->map(fn ($id) => (int) $id)
                ->filter(fn (int $id) => $id > 0)
                ->values()
                ->all();

            if (in_array((int) $order->id, $firedOrders, true)) {
                return false;
            }

            $firedOrders[] = (int) $order->id;
            Session::put('purchase_pixel_fired_orders', array_values(array_unique($firedOrders)));

            return true;
        }

        return DB::transaction(function () use ($order): bool {
            $lockedOrder = Order::query()
                ->whereKey((int) $order->id)
                ->lockForUpdate()
                ->first();

            if (! $lockedOrder || ! empty($lockedOrder->purchase_pixel_fired_at)) {
                return false;
            }

            if (! $this->isOrderPurchasePixelEligible($lockedOrder)) {
                return false;
            }

            $lockedOrder->purchase_pixel_fired_at = now();
            $lockedOrder->save();

            return true;
        });
    }

    private function isOrderPurchasePixelEligible(Order $order): bool
    {
        if (! Schema::hasTable('orders') || ! Schema::hasColumn('orders', 'order_status')) {
            return false;
        }

        $statusId = (int) ($order->order_status ?? 0);
        if ($statusId <= 0) {
            return false;
        }

        $status = $order->relationLoaded('status') ? $order->status : null;
        if (! $status && Schema::hasTable('order_statuses')) {
            $status = OrderStatus::query()->select(['id', 'name', 'slug'])->find($statusId);
        }

        if (! $status) {
            return false;
        }

        $slug = Str::slug((string) ($status->slug ?: $status->name));

        return in_array($slug, ['confirmed', 'processing', 'shipped', 'delivered'], true);
    }

    private function ensurePhoneAllowedForCheckout(string $phone, bool $expectsJson = false)
    {
        $phoneBlock = app(PhoneBlockService::class)->getActiveBlockForPhone($phone);
        if (! $phoneBlock) {
            return null;
        }

        $message = 'This phone number is blocked for new orders due to repeated cancellations. Please contact support.';

        if ($expectsJson) {
            return response()->json([
                'success' => false,
                'message' => $message,
            ], 403);
        }

        Toastr::error($message, 'Blocked');

        return redirect()->back()->withInput();
    }

    public function password_update(Request $request)
    {
        $this->validate($request, [
            'old_password' => 'required',
            'new_password' => 'required',
            'confirm_password' => 'required_with:new_password|same:new_password|',
        ]);

        $customer = Customer::find(Auth::guard('customer')->user()->id);
        $hashPass = $customer->password;

        if (Hash::check($request->old_password, $hashPass)) {

            $customer->fill([
                'password' => Hash::make($request->new_password),
            ])->save();

            Toastr::success('Success', 'Password changed successfully!');

            return redirect()->route('customer.account');
        } else {
            Toastr::error('Failed', 'Old password not match!');

            return redirect()->back();
        }
    }

    /**
     * Validate cart items against available stock
     */
    private function validateCartStock(): array
    {
        try {
            $cartItems = Cart::instance('shopping')->content();

            // Get all active warehouses for fallback
            $activeWarehouses = Warehouse::active()->get();

            if ($activeWarehouses->isEmpty()) {
                Log::warning('Stock validation: No active warehouses found');

                return [
                    'valid' => false,
                    'message' => 'No active warehouse found. Please contact support.',
                ];
            }

            Log::info('Stock validation started', [
                'cart_items_count' => $cartItems->count(),
                'active_warehouses_count' => $activeWarehouses->count(),
            ]);

            $selectedWarehouse = null;
            $warehouseSwitched = false;

            foreach ($cartItems as $cartItem) {
                $itemWarehouseId = $cartItem->options->warehouse_id ?? null;

                // Check if product has variants
                $variant = $this->resolveCartVariant($cartItem);

                // Load product to check if it actually uses variants for stock
                $product = Product::find($cartItem->id);
                $useVariantStock = $variant && $product && $product->has_variant;

                // Find a warehouse that has stock for this product
                $warehouseWithStock = null;

                if ($useVariantStock) {
                    // Use variant stock system
                    $variantStockService = app(VariantStockService::class);

                    // First try the item's warehouse
                    if ($itemWarehouseId && $activeWarehouses->contains('id', $itemWarehouseId)) {
                        $availableStock = $variantStockService->checkStockAvailability($itemWarehouseId, $variant->id, $cartItem->qty);
                        if ($availableStock) {
                            $warehouseWithStock = $activeWarehouses->find($itemWarehouseId);
                        }
                    }

                    // If not, try the session warehouse
                    if (! $warehouseWithStock) {
                        $sessionWarehouseId = Session::get('warehouse_id');
                        if ($sessionWarehouseId && $activeWarehouses->contains('id', $sessionWarehouseId)) {
                            $availableStock = $variantStockService->checkStockAvailability($sessionWarehouseId, $variant->id, $cartItem->qty);
                            if ($availableStock) {
                                $warehouseWithStock = $activeWarehouses->find($sessionWarehouseId);
                            }
                        }
                    }

                    // If still not found, try all warehouses
                    if (! $warehouseWithStock) {
                        foreach ($activeWarehouses as $warehouse) {
                            $availableStock = $variantStockService->checkStockAvailability($warehouse->id, $variant->id, $cartItem->qty);
                            if ($availableStock) {
                                $warehouseWithStock = $warehouse;
                                break;
                            }
                        }
                    }
                } else {
                    // Use old warehouse stock system
                    $warehouseStocks = WarehouseStock::where('product_id', $cartItem->id)
                        ->whereIn('warehouse_id', $activeWarehouses->pluck('id'))
                        ->whereRaw('(physical_quantity - reserved_quantity) >= ?', [(int) $cartItem->qty])
                        ->with('warehouse')
                        ->get();

                    // First try the item's warehouse
                    if ($itemWarehouseId) {
                        $warehouseWithStock = $warehouseStocks->first(function ($ws) use ($itemWarehouseId) {
                            return $ws->warehouse_id == $itemWarehouseId;
                        })?->warehouse;
                    }

                    // If not, try the session warehouse
                    if (! $warehouseWithStock) {
                        $sessionWarehouseId = Session::get('warehouse_id');
                        if ($sessionWarehouseId) {
                            $warehouseWithStock = $warehouseStocks->first(function ($ws) use ($sessionWarehouseId) {
                                return $ws->warehouse_id == $sessionWarehouseId;
                            })?->warehouse;
                        }
                    }

                    // If still not found, use any warehouse with stock
                    if (! $warehouseWithStock && $warehouseStocks->isNotEmpty()) {
                        $warehouseWithStock = $warehouseStocks->first()->warehouse;
                    }
                }

                if (! $warehouseWithStock) {
                    Log::warning('Stock validation: No warehouse has sufficient stock for product', [
                        'product_id' => $cartItem->id,
                        'product_name' => $cartItem->name,
                        'required_quantity' => $cartItem->qty,
                        'has_variant' => $variant ? true : false,
                    ]);

                    if ($variant) {
                        // Variant-level available stock from the stock-management tables only.
                        $totalAvailable = (float) (\App\Models\Inventory::query()
                            ->where('product_variant_id', (int) $variant->id)
                            ->selectRaw('SUM(CASE WHEN (quantity_available - quantity_reserved) > 0 THEN (quantity_available - quantity_reserved) ELSE 0 END) AS total_available')
                            ->value('total_available') ?? 0);

                        if ($totalAvailable <= 0) {
                            $totalAvailable = (float) (WarehouseStock::query()
                                ->where('product_variant_id', (int) $variant->id)
                                ->selectRaw('SUM(CASE WHEN (physical_quantity - reserved_quantity) > 0 THEN (physical_quantity - reserved_quantity) ELSE 0 END) AS total_available')
                                ->value('total_available') ?? 0);
                        }

                        // Product-level pool (for clearer troubleshooting message).
                        $totalProductAvailable = (float) (WarehouseStock::query()
                            ->where('product_id', (int) $cartItem->id)
                            ->selectRaw('SUM(CASE WHEN (physical_quantity - reserved_quantity) > 0 THEN (physical_quantity - reserved_quantity) ELSE 0 END) AS total_available')
                            ->value('total_available') ?? 0);

                        $variantLabel = trim(implode(' / ', array_filter([
                            (string) ($variant->color ?? ''),
                            (string) ($variant->size ?? ''),
                        ])));
                        $variantLabel = $variantLabel !== '' ? $variantLabel : ('Variant #'.$variant->id);

                        return [
                            'valid' => false,
                            'message' => "Insufficient stock for '{$cartItem->name}'. Selected variant: {$variantLabel}. Total available: {$totalAvailable}, Required: {$cartItem->qty}",
                        ];
                    } else {
                        $totalAvailable = (float) (WarehouseStock::query()
                            ->where('product_id', $cartItem->id)
                            ->selectRaw('SUM(CASE WHEN (physical_quantity - reserved_quantity) > 0 THEN (physical_quantity - reserved_quantity) ELSE 0 END) AS total_available')
                            ->value('total_available') ?? 0);

                        return [
                            'valid' => false,
                            'message' => "Insufficient stock for '{$cartItem->name}'. Total available: {$totalAvailable}, Required: {$cartItem->qty}",
                        ];
                    }
                }

                // Set the selected warehouse to the one that has stock
                if (! $selectedWarehouse) {
                    $selectedWarehouse = $warehouseWithStock;
                } elseif ($selectedWarehouse->id !== $warehouseWithStock->id) {
                    // If different warehouses have stock for different products, we need to choose one
                    // For simplicity, prefer the main warehouse, then the first one found
                    $mainWarehouse = $activeWarehouses->first(function ($w) {
                        return $w->type === 'main';
                    });

                    if ($mainWarehouse && ($mainWarehouse->id === $selectedWarehouse->id || $mainWarehouse->id === $warehouseWithStock->id)) {
                        $selectedWarehouse = $mainWarehouse;
                    }
                    // Otherwise keep the first one selected
                }

                // Update cart item warehouse if different
                if ($itemWarehouseId != $warehouseWithStock->id) {
                    Cart::instance('shopping')->update($cartItem->rowId, [
                        'options' => array_merge($cartItem->options->toArray(), ['warehouse_id' => $warehouseWithStock->id]),
                    ]);
                    $warehouseSwitched = true;
                }
            }

            if (! $selectedWarehouse) {
                return [
                    'valid' => false,
                    'message' => 'Unable to find a warehouse with stock for all cart items.',
                ];
            }

            // Update session warehouse if changed
            if ((int) Session::get('warehouse_id') !== (int) $selectedWarehouse->id) {
                Session::put('warehouse_id', $selectedWarehouse->id);
                $warehouseSwitched = true;
            }

            if ($warehouseSwitched) {
                Log::info('Stock validation: Warehouse switched for better stock availability', [
                    'new_warehouse_id' => $selectedWarehouse->id,
                    'new_warehouse_name' => $selectedWarehouse->name,
                ]);
            }

            Log::info('Stock validation: All items passed validation', [
                'selected_warehouse_id' => $selectedWarehouse->id,
                'selected_warehouse_name' => $selectedWarehouse->name,
                'warehouse_switched' => $warehouseSwitched,
            ]);

            return ['valid' => true, 'message' => 'Stock validation passed'];

        } catch (\Exception $e) {
            Log::error('Stock validation error', [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'valid' => false,
                'message' => 'Unable to validate stock. Please try again.',
            ];
        }
    }
}
