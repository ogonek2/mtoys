<?php

namespace App\Http\Controllers;

use App\Mail\OrderPlacedAdminMail;
use App\Mail\OrderPlacedCustomerMail;
use App\Models\Orders;
use App\Models\Product;
use App\Services\ShopSettings;
use App\Services\TelegramNotifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class OrderController extends Controller
{
    public function submit(Request $request)
    {
        $request->validate([
            'name' => 'required|min:2',
            'lastname' => 'required|min:2',
            'phone' => 'required',
            'email' => 'required|email|max:190',
            'delivery_service' => 'required',
            'payment' => 'required',
        ], [
            'email.required' => 'Вкажіть email',
            'email.email' => 'Некоректний email',
        ]);

        $cart = json_decode($request->cart, true) ?? [];

        if (empty($cart) || ! is_array($cart)) {
            return response()->json(['error' => 'Корзина пуста'], 400);
        }

        $minOrderTotal = ShopSettings::minOrderTotal();
        $orderTotal = is_numeric($request->total_price) ? (float) $request->total_price : 0;
        if ($minOrderTotal > 0 && $orderTotal < $minOrderTotal) {
            $symbol = ShopSettings::get('currency_symbol', '₴');

            return response()->json([
                'error' => sprintf(
                    'Мінімальна сума замовлення — %s %s',
                    number_format($minOrderTotal, 0, '.', ' '),
                    $symbol
                ),
            ], 422);
        }

        foreach ($cart as &$item) {
            if (! isset($item['name'], $item['price'], $item['quantity'])) {
                return response()->json(['error' => 'В корзине есть товары с неполными данными'], 400);
            }

            if (! isset($item['articule']) || $item['articule'] === '') {
                $item['articule'] = 'Не указан';
            }

            $item['price'] = is_numeric($item['price']) ? (float) $item['price'] : 0;
            $item['quantity'] = is_numeric($item['quantity']) ? (int) $item['quantity'] : 1;

            if (! empty($item['isWholesale']) && isset($item['wholesalePrice'], $item['wholesaleMinQuantity'])) {
                $wholesalePrice = (float) $item['wholesalePrice'];
                $wholesaleMinQuantity = (int) $item['wholesaleMinQuantity'];
                if ($item['quantity'] >= $wholesaleMinQuantity && $wholesalePrice > 0) {
                    $item['price'] = $wholesalePrice;
                    $item['wholesale_applied'] = true;
                }
            }
        }
        unset($item);

        $orderData = [
            'delivery_service' => $request->delivery_service,
            'city' => $request->city ?? '',
            'warehouse' => $request->warehouse ?? '',
            'manual_address' => $request->manual_address ?? '',
            'name' => $request->name,
            'lastname' => $request->lastname,
            'fathername' => $request->fathername ?? '',
            'phone' => $request->phone,
            'email' => $request->email,
            'comment' => $request->comment ?? '',
            'cart' => json_encode($cart, JSON_UNESCAPED_UNICODE),
            'total_price' => $orderTotal,
            'payment' => $request->payment,
            'status' => Orders::STATUS_NEW,
            'tracking_number' => null,
        ];

        if ($orderData['delivery_service'] === 'novaposhta') {
            if ($orderData['city'] === '' || $orderData['warehouse'] === '') {
                return response()->json([
                    'error' => 'Для доставки Новой Почтой необходимо указать город и отделение',
                ], 400);
            }
        } elseif ($orderData['delivery_service'] !== 'pickup' && $orderData['manual_address'] === '') {
            return response()->json([
                'error' => 'Для выбранного способа доставки необходимо указать адрес',
            ], 400);
        }

        $order = Orders::create($orderData);

        try {
            \App\Services\ShopAnalytics::forgetCache();
        } catch (Throwable) {
            // ignore
        }

        $adminEmail = (string) ShopSettings::get('contact_email', '');
        if ($adminEmail === '') {
            $adminEmail = (string) env('MAIL_ADMIN_ADDRESS', 'office@mtoys.com.ua');
        }

        try {
            Mail::to($request->email)->send(new OrderPlacedCustomerMail($order, $cart, $orderTotal));
        } catch (Throwable $e) {
            Log::error('Customer order email failed: '.$e->getMessage());
        }

        try {
            Mail::to($adminEmail)->send(new OrderPlacedAdminMail($order, $cart, $orderTotal));
        } catch (Throwable $e) {
            Log::error('Admin order email failed: '.$e->getMessage());
        }

        $tgOk = TelegramNotifier::sendNewOrder(
            [
                'id' => $order->id,
                'name' => $order->name,
                'lastname' => $order->lastname,
                'fathername' => $order->fathername,
                'phone' => $order->phone,
                'email' => $order->email,
                'delivery_service' => $order->delivery_service,
                'payment' => $order->payment,
                'city' => $order->city,
                'warehouse' => $order->warehouse,
                'manual_address' => $order->manual_address,
                'comment' => $order->comment,
            ],
            $cart,
            $orderTotal
        );

        if (! $tgOk) {
            Log::warning('Order created but Telegram notify failed', ['order_id' => $order->id]);
        }

        try {
            $productIds = collect($cart)->pluck('id')->filter()->all();
            $products = Product::query()->whereIn('id', $productIds)->get()->keyBy('id');
            $items = [];
            $simpleItems = [];

            foreach ($cart as $item) {
                $product = $products[$item['id'] ?? null] ?? null;
                if (! $product) {
                    continue;
                }

                $items[] = [
                    'item_name' => $product->name,
                    'item_id' => $item['articule'] ?? 'Не указан',
                    'item_brand' => $product->brand ?? 'Без бренду',
                    'item_category' => $product->categories()->first()->name ?? 'Без категорії',
                    'price' => $item['price'],
                    'discount' => $product->discount,
                    'quantity' => $item['quantity'] ?? 1,
                    'currency' => 'UAH',
                ];

                $simpleItems[] = [
                    'id' => $item['articule'] ?? null,
                    'google_business_vertical' => 'retail',
                ];
            }

            session()->flash('purchase_js', [
                'event' => 'purchase',
                'ecommerce' => [
                    'transaction_id' => $order->id,
                    'value' => $orderTotal,
                    'items' => $items,
                ],
                'items' => $simpleItems,
            ]);
        } catch (Throwable $e) {
            Log::warning('purchase_js build failed: '.$e->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => 'Замовлення успішно оформлено',
            'order_id' => $order->id,
            'redirect' => url('/thank-you'),
        ]);
    }

    public function saveAbandoned(Request $request)
    {
        $phone = $request->input('phone');
        $cartJson = $request->input('cart');
        $cart = json_decode($cartJson, true);

        \DB::table('abandoned_carts')->updateOrInsert(
            ['phone' => $phone],
            [
                'cart_data' => json_encode($cart),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        return response()->json(['success' => true]);
    }

    public function print(Orders $order)
    {
        return view('orders.print', [
            'order' => $order,
            'items' => $order->cart_items,
            'shop' => ShopSettings::public(),
        ]);
    }
}
