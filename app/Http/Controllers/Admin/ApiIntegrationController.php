<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Courierapi;
use App\Models\FraudCheckerApi;
use App\Models\Order;
use App\Models\PaymentGateway;
use App\Models\SmsGateway;
use App\Services\FraudCheckerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;
use Toastr;

class ApiIntegrationController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:payment-config-update', ['only' => ['pay_update', 'sms_update', 'courier_update', 'fraud_checker_update']]);
        $this->middleware('permission:order-view', ['only' => ['fraud_checker_detailed', 'fraud_checker_test']]);
    }

    public function pay_manage()
    {
        $bkash = PaymentGateway::where('type', '=', 'bkash')->first();
        $shurjopay = PaymentGateway::where('type', '=', 'shurjopay')->first();

        return view('backEnd.apiintegration.pay_manage', compact('bkash', 'shurjopay'));
    }

    public function pay_update(Request $request)
    {

        $update_data = PaymentGateway::findOrFail($request->id);
        $input = $request->all();
        $input['status'] = $request->status ? 1 : 0;
        $update_data->update($input);

        Toastr::success('Success', 'Data update successfully');

        return redirect()->back();
    }

    public function sms_manage()
    {
        $sms = SmsGateway::first();

        return view('backEnd.apiintegration.sms_manage', compact('sms'));
    }

    public function sms_update(Request $request)
    {

        $update_data = SmsGateway::findOrFail($request->id);
        $input = $request->all();
        $input['status'] = $request->status ? 1 : 0;
        $input['order'] = $request->order ? 1 : 0;
        $input['forget_pass'] = $request->forget_pass ? 1 : 0;
        $input['password_g'] = $request->password_g ? 1 : 0;
        $update_data->update($input);

        Toastr::success('Success', 'Data update successfully');

        return redirect()->back();
    }

    public function courier_manage()
    {
        $steadfast = Courierapi::where('type', '=', 'steadfast')->first();
        $pathao = Courierapi::where('type', '=', 'pathao')->first();

        return view('backEnd.apiintegration.courier_manage', compact('steadfast', 'pathao'));
    }

    public function courier_update(Request $request)
    {

        $update_data = Courierapi::findOrFail($request->id);
        $input = $request->all();
        $input['status'] = $request->status ? 1 : 0;
        $update_data->update($input);

        Toastr::success('Success', 'Data update successfully');

        return redirect()->back();
    }

    public function fraud_checker_manage()
    {
        $fraudApi = FraudCheckerApi::first();

        return view('backEnd.apiintegration.fraud_checker_manage', compact('fraudApi'));
    }

    public function fraud_checker_update(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'api_url' => 'required|url',
            'api_key' => 'required|string|max:255',
            'query_type' => 'required|in:basic,detailed,comprehensive',
        ]);

        $normalizedApiUrl = FraudCheckerService::normalizeConfiguredApiUrl((string) $request->api_url);

        $fraudApi = FraudCheckerApi::first();

        if ($fraudApi) {
            $fraudApi->update([
                'name' => $request->name,
                'api_url' => $normalizedApiUrl,
                'api_key' => $request->api_key,
                'query_type' => $request->query_type,
                'description' => $request->description,
                'status' => $request->status ? 1 : 0,
            ]);
        } else {
            FraudCheckerApi::create([
                'name' => $request->name,
                'api_url' => $normalizedApiUrl,
                'api_key' => $request->api_key,
                'query_type' => $request->query_type,
                'description' => $request->description,
                'status' => $request->status ? 1 : 0,
            ]);
        }

        Toastr::success('Success', 'Fraud Checker API settings updated successfully');

        return redirect()->back();
    }

    public function fraud_checker_test(Request $request)
    {
        try {
            $fraudService = new \App\Services\FraudCheckerService;
            $testResult = $fraudService->testApiConnection();

            if ($testResult['success']) {
                Toastr::success('Success', $testResult['message']);
            } else {
                Toastr::error('Error', $testResult['message']);
            }

            return response()->json($testResult);
        } catch (Throwable $e) {
            Toastr::error('Error', 'Test failed: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Test failed: '.$e->getMessage(),
            ]);
        }
    }

    public function fraud_checker_detailed(Request $request)
    {
        try {
            $phone = $request->input('phone');
            $orderId = $request->input('order_id');

            // Allow the frontend to pass only order_id; we will derive phone from the order's shipping info.
            if (! $phone && $orderId) {
                $order = Order::with('shipping')->find($orderId);
                $phone = $order && $order->shipping ? $order->shipping->phone : null;
            }

            if (! $phone) {
                return response()->json([
                    'success' => false,
                    'message' => 'Phone number is required',
                ]);
            }

            $fraudService = new \App\Services\FraudCheckerService;
            $forceFresh = (bool) $request->boolean('force_fresh', false);
            $analysisData = $fraudService->getDetailedAnalysis($phone, $orderId, $forceFresh);
            $recommendations = $fraudService->getRiskRecommendations($analysisData);

            // Render the detailed view
            $html = view('backEnd.order.fraud_detailed', compact('analysisData', 'recommendations'))->render();

            return response()->json([
                'success' => true,
                'html' => $html,
                'data' => $analysisData,
            ]);

        } catch (Throwable $e) {
            Log::error('Detailed fraud check error', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error retrieving detailed analysis: '.$e->getMessage(),
            ], 500);
        }
    }
}
