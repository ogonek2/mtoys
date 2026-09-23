<?php

namespace App\Http\Controllers;

use App\Models\Orders;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class InvoiceController extends Controller
{
    public function generateInvoice($orderId)
    {
        $order = Orders::findOrFail($orderId);
        
        // Получаем расшифрованные данные
        $orderData = [
            'id' => $order->id,
            'name' => $order->name,
            'lastname' => $order->lastname,
            'fathername' => $order->fathername,
            'phone' => $order->phone,
            'total_price' => $order->total_price,
            'delivery_service' => $order->delivery_service,
            'city' => $order->city,
            'warehouse' => $order->warehouse,
            'manual_address' => $order->manual_address,
            'payment' => $order->payment,
            'comment' => $order->comment,
            'cart_items' => $order->cart_items,
            'created_at' => $order->created_at,
        ];
        
        // Генерируем PDF
        $pdf = Pdf::loadView('invoices.order', compact('orderData'));
        $pdf->setPaper('A4', 'portrait');
        
        $filename = 'invoice_' . $order->id . '_' . date('Y-m-d') . '.pdf';
        
        return $pdf->download($filename);
    }
    
    public function viewInvoice($orderId)
    {
        $order = Orders::findOrFail($orderId);
        
        // Получаем расшифрованные данные
        $orderData = [
            'id' => $order->id,
            'name' => $order->name,
            'lastname' => $order->lastname,
            'fathername' => $order->fathername,
            'phone' => $order->phone,
            'total_price' => $order->total_price,
            'delivery_service' => $order->delivery_service,
            'city' => $order->city,
            'warehouse' => $order->warehouse,
            'manual_address' => $order->manual_address,
            'payment' => $order->payment,
            'comment' => $order->comment,
            'cart_items' => $order->cart_items,
            'created_at' => $order->created_at,
        ];
        
        // Генерируем PDF для просмотра
        $pdf = Pdf::loadView('invoices.order', compact('orderData'));
        $pdf->setPaper('A4', 'portrait');
        
        return $pdf->stream('invoice_' . $order->id . '.pdf');
    }
}
