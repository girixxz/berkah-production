<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    protected $fillable = [
        'priority',
        'customer_id',
        'sales_id',
        'order_date',
        'deadline',
        'product_category_id',
        'product_color',
        'material_category_id',
        'material_texture_id',
        'notes',
        'shipping_type',
        'shipping_status',
        'shipping_date',
        'total_qty',
        'subtotal',
        'discount',
        'grand_total',
        'production_status',
        'work_order_status',
        'wip_date',
        'finished_date',
        'cancelled_date',
    ];

    protected $casts = [
        'order_date' => 'datetime',
        'deadline' => 'datetime',
        'wip_date' => 'datetime',
        'finished_date' => 'datetime',
        'cancelled_date' => 'datetime',
        'shipping_date' => 'datetime',
        'subtotal' => 'decimal:2',
        'discount' => 'decimal:2',
        'grand_total' => 'decimal:2',
    ];

    /**
     * Get the customer that owns the order
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    /**
     * Get the sales person that handles this order
     */
    public function sales(): BelongsTo
    {
        return $this->belongsTo(Sale::class, 'sales_id');
    }

    /**
     * Alias for sales() - for consistency
     */
    public function sale(): BelongsTo
    {
        return $this->sales();
    }

    /**
     * Get the product category for this order
     */
    public function productCategory(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'product_category_id');
    }

    /**
     * Get the material category for this order
     */
    public function materialCategory(): BelongsTo
    {
        return $this->belongsTo(MaterialCategory::class, 'material_category_id');
    }

    /**
     * Get the material texture for this order
     */
    public function materialTexture(): BelongsTo
    {
        return $this->belongsTo(MaterialTexture::class, 'material_texture_id');
    }

    /**
     * Get all design variants for this order
     */
    public function designVariants(): HasMany
    {
        return $this->hasMany(DesignVariant::class, 'order_id');
    }

    /**
     * Get all order items for this order
     */
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'order_id');
    }

    /**
     * Get all extra services for this order
     */
    public function extraServices(): HasMany
    {
        return $this->hasMany(ExtraService::class, 'order_id');
    }

    /**
     * Get the invoice for this order
     */
    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class, 'order_id');
    }

    /**
     * Get all order stages for this order
     */
    public function orderStages(): HasMany
    {
        return $this->hasMany(OrderStage::class, 'order_id');
    }

    /**
     * Get all work orders for this order
     */
    public function workOrders(): HasMany
    {
        return $this->hasMany(WorkOrder::class, 'order_id');
    }

    /**
     * Check if all order stages are completed and auto-update production status
     * Returns true if status was changed, false otherwise
     */
    public function checkAndUpdateProductionStatus(): bool
    {
        // Get all order stages for this order
        $orderStages = $this->orderStages()->get();
        
        // If no stages exist, don't change status
        if ($orderStages->isEmpty()) {
            return false;
        }
        
        // Check if all stages are done
        $allDone = $orderStages->every(function ($stage) {
            return $stage->status === 'done';
        });
        
        // If all stages are done and current status is wip, update to finished
        if ($allDone && $this->production_status === 'wip') {
            $this->update([
                'production_status' => 'finished',
                'finished_date' => now()
            ]);
            return true;
        }
        
        // If not all stages are done but current status is finished, revert back to wip
        if (!$allDone && $this->production_status === 'finished') {
            $this->update([
                'production_status' => 'wip',
                'finished_date' => null,
                // wip_date tetap dipertahankan (tidak di-reset) karena order sudah pernah WIP
            ]);
            return true;
        }
        
        return false;
    }
}
