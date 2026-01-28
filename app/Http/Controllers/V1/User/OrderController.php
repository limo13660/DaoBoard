<?php

namespace App\Http\Controllers\V1\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\OrderSave;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\User;
use App\Services\CouponService;
use App\Services\OrderService;
use App\Services\PaymentService;
use App\Services\PlanService;
use App\Services\UserService;
use App\Utils\Helper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function fetch(Request $request)
    {
        $orders = Order::with('plan')
            ->where('user_id', $request->user['id'])
            ->when($request->input('status') !== null, function ($q) use ($request) {
                $q->where('status', $request->input('status'));
            })
            ->orderByDesc('created_at')
            ->get();

        return response([
            'data' => $orders->makeHidden(['id', 'user_id'])
        ]);
    }

    public function detail(Request $request)
    {
        $order = Order::where('user_id', $request->user['id'])
            ->where('trade_no', $request->input('trade_no'))
            ->first();

        if (!$order) {
            abort(404, __('Order does not exist'));
        }

        if ($order->plan_id == 0) {
            $order->plan = ['id' => 0, 'name' => 'deposit'];
            $order->bounus = $this->getbounus($order->total_amount);
            $order->get_amount = $order->total_amount + $order->bounus;

            return response(['data' => $order]);
        }

        $order->plan = Plan::find($order->plan_id);
        if (!$order->plan) {
            abort(404, __('Subscription plan does not exist'));
        }

        if ($order->surplus_order_ids) {
            $order->surplus_orders = Order::whereIn('id', $order->surplus_order_ids)->get();
        }

        return response(['data' => $order]);
    }

    public function save(OrderSave $request)
    {
        $userService = new UserService();

        if ($userService->isNotCompleteOrderByUserId($request->user['id'])) {
            abort(409, __('You have an unpaid or pending order'));
        }

        DB::beginTransaction();

        try {
            $order = new Order();
            $orderService = new OrderService($order);
            $user = User::lockForUpdate()->find($request->user['id']);

            if ($request->input('plan_id') == 0) {
                $amount = (int)$request->input('deposit_amount');
                if ($amount <= 0 || $amount >= 9999999) {
                    abort(422, __('Invalid deposit amount'));
                }

                $order->user_id = $user->id;
                $order->plan_id = 0;
                $order->period = 'deposit';
                $order->trade_no = Helper::generateOrderNo();
                $order->total_amount = $amount;

                $orderService->setOrderType($user);
                $orderService->setInvite($user);
                $order->save();

                DB::commit();
                return response(['data' => $order->trade_no]);
            }

            $planService = new PlanService($request->input('plan_id'));
            $plan = $planService->plan;

            if (!$plan) {
                abort(404, __('Subscription plan does not exist'));
            }

            $order->user_id = $user->id;
            $order->plan_id = $plan->id;
            $order->period = $request->input('period');
            $order->trade_no = Helper::generateOrderNo();
            $order->total_amount = $plan[$order->period];

            if ($request->input('coupon_code')) {
                $couponService = new CouponService($request->input('coupon_code'));
                if (!$couponService->use($order)) {
                    abort(422, __('Coupon failed'));
                }
                $order->coupon_id = $couponService->getId();
            }

            $orderService->setVipDiscount($user);
            $orderService->setOrderType($user);

            if ($user->balance > 0 && $order->total_amount > 0) {
                $use = min($user->balance, $order->total_amount);
                $userService->addBalance($user->id, -$use);
                $order->balance_amount = $use;
                $order->total_amount -= $use;
            }

            $orderService->setInvite($user);
            $order->save();

            DB::commit();
            return response(['data' => $order->trade_no]);

        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function checkout(Request $request)
    {
        $order = Order::where('trade_no', $request->input('trade_no'))
            ->where('user_id', $request->user['id'])
            ->where('status', 0)
            ->lockForUpdate()
            ->first();

        if (!$order) {
            abort(404, __('Order not found or already paid'));
        }

        if ($order->total_amount <= 0) {
            $orderService = new OrderService($order);
            $orderService->paid($order->trade_no);
            return response(['type' => -1, 'data' => true]);
        }

        $payment = Payment::find($request->input('method'));
        if (!$payment || !$payment->enable) {
            abort(403, __('Payment method unavailable'));
        }

        $order->payment_id = $payment->id;
        $order->handling_amount = round(
            $order->total_amount * ($payment->handling_fee_percent / 100)
            + $payment->handling_fee_fixed
        );
        $order->save();

        $paymentService = new PaymentService($payment->payment, $payment->id);

        $result = $paymentService->pay([
            'trade_no'     => $order->trade_no,
            'total_amount' => $order->total_amount + ($order->handling_amount ?? 0),
            'user_id'      => $order->user_id,
            'stripe_token' => $request->input('token')
        ], $request->headers->get('referer'));

        return response(['type' => $result['type'], 'data' => $result['data']]);
    }

    public function cancel(Request $request)
    {
        $order = Order::where('trade_no', $request->input('trade_no'))
            ->where('user_id', $request->user['id'])
            ->where('status', 0)
            ->first();

        if (!$order) {
            abort(404, __('Order not found'));
        }

        (new OrderService($order))->cancel();
        return response(['data' => true]);
    }

    private function getbounus($total_amount)
    {
        $tiers = config('v2board.deposit_bounus', []);
        $bonus = 0;

        foreach ($tiers as $tier) {
            [$amount, $add] = explode(':', $tier);
            if ($total_amount >= ((int)$amount * 100)) {
                $bonus = max($bonus, (int)$add * 100);
            }
        }
        return $bonus;
    }
}
