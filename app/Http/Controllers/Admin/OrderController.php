<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class OrderController extends Controller
{
    public function index()
    {
        // Get all orders with relationships
        $orders = Order::with(['customer', 'sales', 'invoice'])->latest()->get();
        
        // Calculate statistics
        $stats = [
            'total_orders' => $orders->count(),
            'total_qty' => $orders->sum('total_qty'),
            'total_revenue' => $orders->sum('grand_total'),
            'total_bill' => $orders->sum(function($order) {
                return $order->invoice->total_bill ?? 0;
            }),
            'remaining_due' => $orders->sum(function($order) {
                return $order->invoice->amount_due ?? 0;
            }),
            'pending' => $orders->where('production_status', 'pending')->count(),
            'wip' => $orders->where('production_status', 'wip')->count(),
            'finished' => $orders->where('production_status', 'finished')->count(),
            'cancelled' => $orders->where('production_status', 'cancelled')->count(),
        ];
        
        return view('pages.admin.orders.index', compact('orders', 'stats'));
    }

    public function show(Order $order)
    {
        // Load all necessary relationships for the detail view
        $order->load([
            'customer.village.district.city.province',
            'sale',
            'productCategory',
            'materialCategory',
            'materialTexture',
            'shipping',
            'invoice.payments',
            'orderItems.designVariant',
            'orderItems.sleeve',
            'orderItems.size',
            'extraServices.service',
            'designVariants.orderItems.sleeve',
            'designVariants.orderItems.size',
        ]);

        // Group order items by design variant and sleeve for display
        $designVariants = [];
        foreach ($order->orderItems as $item) {
            $designName = $item->designVariant->design_name ?? 'Default';
            $sleeveId = $item->sleeve_id;
            $sleeveName = $item->sleeve->sleeve_name ?? 'Unknown';

            if (!isset($designVariants[$designName])) {
                $designVariants[$designName] = [];
            }

            if (!isset($designVariants[$designName][$sleeveId])) {
                $designVariants[$designName][$sleeveId] = [
                    'sleeve_name' => $sleeveName,
                    'base_price' => $item->unit_price,
                    'sleeve' => $item->sleeve,
                    'items' => []
                ];
            }

            $designVariants[$designName][$sleeveId]['items'][] = $item;
        }

        return view('pages.admin.orders.show', compact('order', 'designVariants'));
    }

    public function downloadInvoice(Order $order)
    {
        // Load all necessary relationships for PDF
        $order->load([
            'customer.village.district.city.province',
            'sale',
            'productCategory',
            'materialCategory',
            'materialTexture',
            'shipping',
            'invoice',
            'orderItems.designVariant',
            'orderItems.sleeve',
            'orderItems.size',
            'extraServices.service',
            'designVariants.orderItems.sleeve',
            'designVariants.orderItems.size',
        ]);

        // Generate PDF from template
        $pdf = Pdf::loadView('pages.admin.orders.invoice-pdf', compact('order'));
        
        // Set paper size and orientation
        $pdf->setPaper('a4', 'portrait');
        
        // Download with filename
        $filename = 'Invoice-' . $order->invoice->invoice_no . '.pdf';
        
        return $pdf->download($filename);
    }

    public function update(Request $request, Order $order)
    {
        // 1️⃣ Validasi dasar
        $data = $request->validate([
            'deadline'   => 'sometimes|date',
            'notes'      => 'nullable|string',
            'discount'   => 'nullable|integer|min:0',
            'design_variants' => 'array',
            'design_variants.*.id' => 'nullable|exists:design_variants,id',
            'design_variants.*.name' => 'required|string',
            'design_variants.*.items' => 'array',
            'design_variants.*.items.*.id' => 'nullable|exists:order_items,id',
            'design_variants.*.items.*.sleeve_id' => 'required|exists:material_sleeves,id',
            'design_variants.*.items.*.size_id'   => 'required|exists:material_sizes,id',
            'design_variants.*.items.*.qty'       => 'required|integer|min:1',
            'design_variants.*.items.*.unit_price' => 'required|integer|min:0',
            'additionals' => 'array',
            'additionals.*.id' => 'nullable|exists:additional_services,id',
            'additionals.*.name' => 'required|string',
            'additionals.*.price' => 'required|integer|min:0',
        ]);

        // 2️⃣ Update order utama
        $order->update([
            'deadline' => $data['deadline'] ?? $order->deadline,
            'notes'    => $data['notes'] ?? $order->notes,
            'discount' => $data['discount'] ?? $order->discount,
        ]);

        $subTotal = 0;

        // 3️⃣ Update / simpan design variants & items
        foreach ($data['design_variants'] ?? [] as $variantData) {
            $variant = isset($variantData['id'])
                ? $order->designVariants()->find($variantData['id'])
                : $order->designVariants()->create(['design_name' => $variantData['name']]);

            if ($variant) {
                $variant->update(['design_name' => $variantData['name']]);

                foreach ($variantData['items'] ?? [] as $item) {
                    $orderItem = isset($item['id'])
                        ? $variant->orderItems()->find($item['id'])
                        : $variant->orderItems()->make();

                    $subtotal = $item['qty'] * $item['unit_price'];
                    $orderItem->fill([
                        'order_id'          => $order->id,
                        'design_variant_id' => $variant->id,
                        'sleeve_id'         => $item['sleeve_id'],
                        'size_id'           => $item['size_id'],
                        'quantity'          => $item['qty'],
                        'unit_price'        => $item['unit_price'],
                        'subtotal'          => $subtotal,
                    ])->save();

                    $subTotal += $subtotal;
                }
            }
        }

        // 4️⃣ Update / simpan additionals
        $additionalTotal = 0;
        foreach ($data['additionals'] ?? [] as $add) {
            $additional = isset($add['id'])
                ? $order->additionalServices()->find($add['id'])
                : $order->additionalServices()->make();

            $additional->fill([
                'order_id'      => $order->id,
                'addition_name' => $add['name'],
                'price'         => $add['price'],
            ])->save();

            $additionalTotal += $add['price'];
        }

        // 5️⃣ Hitung final price
        $finalPrice = $subTotal + $additionalTotal - $order->discount;
        $order->update([
            'sub_total'   => $subTotal,
            'final_price' => max(0, $finalPrice),
            'total_qty'   => $order->orderItems()->sum('quantity'),
        ]);

        return back()->with('success', 'Order berhasil diperbarui dengan detail, items, dan additionals!');
    }


    public function destroy(Order $order)
    {
        $order->delete();

        return back()->with('success', 'Order berhasil dihapus');
    }
}
