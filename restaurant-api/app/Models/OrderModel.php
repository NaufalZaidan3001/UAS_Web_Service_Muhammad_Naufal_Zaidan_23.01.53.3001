<?php

namespace App\Models;

use CodeIgniter\Model;

class OrderModel extends Model
{
    protected $table            = 'orders';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['customer_id', 'restaurant_id', 'total_price', 'status'];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    // Validation
    protected $validationRules      = [];
    protected $validationMessages   = [];
    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

    /**
     * Get order dengan detail items dan customer
     */
    public function getOrderDetails($orderId)
    {
        return $this->db->table('orders o')
            ->select('o.*, c.name as customer_name, c.phone, c.address, r.name as restaurant_name')
            ->join('customers c', 'o.customer_id = c.id')
            ->join('restaurants r', 'o.restaurant_id = r.id')
            ->where('o.id', $orderId)
            ->get()
            ->getRowArray();
    }

    /**
     * Get order items
     */
    public function getOrderItems($orderId)
    {
        return $this->db->table('order_items oi')
            ->select('oi.*, m.name as menu_name, m.description')
            ->join('menus m', 'oi.menu_id = m.id')
            ->where('oi.order_id', $orderId)
            ->get()
            ->getResultArray();
    }

    /**
     * Create order dengan items
     */
    public function createOrderWithItems($orderId, $items)
    {
        $itemModel = model('ItemModel');
        $totalPrice = 0;

        foreach ($items as $item) {
            $menu = $this->db->table('menus')->find($item['menu_id']);
            
            $totalPrice += $menu['price'] * $item['quantity'];

            $itemModel->insert([
                'order_id' => $orderId,
                'menu_id' => $item['menu_id'],
                'quantity' => $item['quantity'],
                'price' => $menu['price']
            ]);
        }

        return $this->update($orderId, ['total_price' => $totalPrice]);
    }

    /**
     * Get customer orders
     */
    public function getCustomerOrders($customerId)
    {
        return $this->db->table('orders')
            ->where('customer_id', $customerId)
            ->orderBy('created_at', 'DESC')
            ->get()
            ->getResultArray();
    }

    /**
     * Get restaurant orders
     */
    public function getRestaurantOrders($restaurantId)
    {
        return $this->db->table('orders')
            ->where('restaurant_id', $restaurantId)
            ->orderBy('created_at', 'DESC')
            ->get()
            ->getResultArray();
    }

    /**
     * Get order statistics
     */
    public function getOrderStatistics($restaurantId)
    {
        return $this->db->table('orders')
            ->selectCount('id', 'total_orders')
            ->selectSum('total_price', 'total_revenue')
            ->where('restaurant_id', $restaurantId)
            ->where('status', 'delivered')
            ->get()
            ->getRowArray();
    }
}
