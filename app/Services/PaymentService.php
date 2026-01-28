<?php

namespace App\Services;

use App\Models\Payment;
use App\Payments\Contracts\PaymentInterface;

class PaymentService
{
    protected string $method;
    protected string $class;
    protected array $config = [];
    protected PaymentInterface $payment;

    public function __construct(string $method, ?int $id = null, ?string $uuid = null)
    {
        $this->method = $method;
        $this->class  = '\\App\\Payments\\' . $method;

        if (!class_exists($this->class)) {
            abort(500, 'payment gate not found');
        }

        if (!is_subclass_of($this->class, PaymentInterface::class)) {
            abort(500, 'invalid payment gate');
        }

        $model = null;
        if ($id !== null) {
            $model = Payment::find($id);
        } elseif ($uuid !== null) {
            $model = Payment::where('uuid', $uuid)->first();
        }

        if (!$model) {
            abort(404, 'payment config not found');
        }

        $payment = $model->toArray();

        $this->config = array_merge(
            $payment['config'] ?? [],
            [
                'id'            => $payment['id'],
                'uuid'          => $payment['uuid'],
                'enable'        => (bool) $payment['enable'],
                'notify_domain' => $payment['notify_domain'] ?? null,
            ]
        );

        $this->payment = new $this->class($this->config);
    }

    public function notify(array $params)
    {
        if (!($this->config['enable'] ?? false)) {
            abort(403, 'payment gate disabled');
        }

        return $this->payment->notify($params);
    }

    public function pay(array $order, string $referer)
    {
        if (!($this->config['enable'] ?? false)) {
            abort(403, 'payment gate disabled');
        }

        $notifyUrl = url("/api/v1/guest/payment/notify/{$this->method}/{$this->config['uuid']}");

        if (!empty($this->config['notify_domain'])) {
            $parsed = parse_url($notifyUrl);
            $notifyUrl = rtrim($this->config['notify_domain'], '/') . ($parsed['path'] ?? '');
        }

        $referer = rtrim($referer, '/');

        return $this->payment->pay([
            'notify_url'   => $notifyUrl,
            'return_url'   => $referer . '/#/order/' . $order['trade_no'],
            'trade_no'     => $order['trade_no'],
            'total_amount' => $order['total_amount'],
            'user_id'      => $order['user_id'] ?? null,
            'stripe_token' => $order['stripe_token'] ?? null,
        ]);
    }

    public function form(): array
    {
        $form = $this->payment->form();

        foreach ($form as $key => &$item) {
            if (array_key_exists($key, $this->config)) {
                $item['value'] = $this->config[$key];
            }
        }

        return $form;
    }
}
