<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\DesignVariant;
use App\Models\Invoice;
use App\Models\MaterialCategory;
use App\Models\MaterialSize;
use App\Models\MaterialSleeve;
use App\Models\MaterialTexture;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStage;
use App\Models\ProductCategory;
use App\Models\ProductionStage;
use App\Models\Sale;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class OrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all necessary data
        $customers = Customer::all();
        $sales = Sale::all();
        $productCategories = ProductCategory::all();
        $materialCategories = MaterialCategory::all();
        $materialTextures = MaterialTexture::all();
        $materialSleeves = MaterialSleeve::all();
        $materialSizes = MaterialSize::all();
        $productionStages = ProductionStage::all();

        // If no data exists, return
        if ($customers->isEmpty() || $sales->isEmpty() || $productCategories->isEmpty()) {
            $this->command->warn('Please run CustomerSeeder, SaleSeeder, and ProductSeeder first!');
            return;
        }

        $colors = ['Merah', 'Biru', 'Hitam', 'Putih', 'Hijau', 'Kuning', 'Navy', 'Abu-abu'];
        $priorities = ['normal', 'high'];
        $shippingTypes = ['pickup', 'delivery'];

        // Create 20 orders with order_date = today, deadline = 1-2 weeks from today
        for ($i = 1; $i <= 20; $i++) {
            $customer = $customers->random();
            $sale = $sales->random();
            $productCategory = $productCategories->random();
            $materialCategory = $materialCategories->random();
            $materialTexture = $materialTextures->random();
            
            // Order date = today
            $orderDate = Carbon::today();
            
            // Deadline = random between 7-14 days from today
            $deadline = Carbon::today()->addDays(rand(7, 14));
            
            $priority = $priorities[array_rand($priorities)];
            $shippingType = $shippingTypes[array_rand($shippingTypes)];
            $productionStatus = 'pending'; // Default: pending only
            
            // Create order with temporary values (will be updated after items created)
            $order = Order::create([
                'priority' => $priority,
                'customer_id' => $customer->id,
                'sales_id' => $sale->id,
                'order_date' => $orderDate,
                'deadline' => $deadline,
                'product_category_id' => $productCategory->id,
                'product_color' => $colors[array_rand($colors)],
                'material_category_id' => $materialCategory->id,
                'material_texture_id' => $materialTexture->id,
                'notes' => 'Order sample #' . $i,
                'shipping_type' => $shippingType,
                'shipping_status' => 'pending',
                'production_status' => $productionStatus,
                'finished_date' => null, // No finished date since all orders are pending
                'total_qty' => 0,
                'subtotal' => 0,
                'discount' => 0,
                'grand_total' => 0,
            ]);

            // Create design variants (1-2 variants per order)
            $variantCount = rand(1, 2);
            $designVariants = [];
            
            for ($v = 1; $v <= $variantCount; $v++) {
                $designVariant = DesignVariant::create([
                    'order_id' => $order->id,
                    'design_name' => 'Desain ' . chr(64 + $v), // A, B, C
                ]);
                $designVariants[] = $designVariant;
            }

            // Create order items (2-4 items per order)
            $itemCount = rand(2, 4);
            $totalQty = 0;
            $subtotal = 0;

            for ($item = 1; $item <= $itemCount; $item++) {
                $designVariant = $designVariants[array_rand($designVariants)];
                $sleeve = $materialSleeves->random();
                $size = $materialSizes->random();
                $qty = rand(5, 20);
                $unitPrice = rand(50000, 150000);
                $itemSubtotal = $qty * $unitPrice;

                OrderItem::create([
                    'order_id' => $order->id,
                    'design_variant_id' => $designVariant->id,
                    'sleeve_id' => $sleeve->id,
                    'size_id' => $size->id,
                    'qty' => $qty,
                    'unit_price' => $unitPrice,
                    'subtotal' => $itemSubtotal,
                ]);
                
                // Accumulate totals
                $totalQty += $qty;
                $subtotal += $itemSubtotal;
            }

            // Calculate discount and grand total
            $discount = rand(0, 10) * 5000; // Random discount 0-50k
            $grandTotal = $subtotal - $discount;

            // Update order totals
            $order->update([
                'total_qty' => $totalQty,
                'subtotal' => $subtotal,
                'discount' => $discount,
                'grand_total' => $grandTotal,
            ]);

            // Create invoice
            $invoiceNo = 'INV-' . date('Ymd') . '-' . str_pad($i, 4, '0', STR_PAD_LEFT);
            $amountPaid = rand(0, 1) ? rand(0, $grandTotal) : 0; // Random partial payment or no payment
            $amountDue = $grandTotal - $amountPaid;
            
            $invoiceStatus = 'unpaid';
            if ($amountPaid == 0) {
                $invoiceStatus = 'unpaid';
            } elseif ($amountDue == 0) {
                $invoiceStatus = 'paid';
            } else {
                $invoiceStatus = 'dp'; // Down payment (partial)
            }

            Invoice::create([
                'order_id' => $order->id,
                'invoice_no' => $invoiceNo,
                'total_bill' => $grandTotal,
                'amount_paid' => $amountPaid,
                'amount_due' => $amountDue,
                'status' => $invoiceStatus,
                'notes' => null,
            ]);

            // Create order stages for production tracking
            foreach ($productionStages as $productionStage) {
                // All stages default to pending
                OrderStage::create([
                    'order_id' => $order->id,
                    'stage_id' => $productionStage->id,
                    'status' => 'pending',
                ]);
            }
        }

        $this->command->info('Created 20 orders with order_date = today and deadline = 1-2 weeks from today');
    }
}
