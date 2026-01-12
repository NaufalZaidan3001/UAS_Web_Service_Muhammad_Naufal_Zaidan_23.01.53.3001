<?php
namespace App\Controllers\Api;
use CodeIgniter\RESTful\ResourceController;

class OrdersController extends ResourceController
{
    protected $modelName = 'App\Models\OrderModel';
    protected $format = 'json';

    public function options()
    {
        return $this->response->setStatusCode(200)
            ->setHeader('Access-Control-Allow-Origin', '*')
            ->setHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS')
            ->setHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization');
    }

    // Buat Pesanan
    public function create()
    {
        try {
            $data = $this->request->getJSON();
            $db = \Config\Database::connect();
            
            // Hitung total harga
            $totalPrice = 0;
            $menuModel = new \App\Models\MenuModel();
            foreach ($data->items as $item) {
                $menu = $menuModel->find($item->menuId); // Perhatikan: frontend kirim 'menuId'
                if($menu) $totalPrice += $menu['price'] * $item->quantity;
            }

            $orderId = $this->model->insert([
                'customer_id' => $data->customer_id,
                'restaurant_id' => $data->restaurant_id,
                'total_price' => $totalPrice,
                'status' => 'pending'
            ]);

            $orderItemModel = new \App\Models\OrderItemModel();
            foreach ($data->items as $item) {
                $menu = $menuModel->find($item->menuId);
                $orderItemModel->insert([
                    'order_id' => $orderId,
                    'menu_id' => $item->menuId,
                    'quantity' => $item->quantity,
                    'price' => $menu['price']
                ]);
            }

            return $this->respondCreated([
                'status' => 'success',
                'data' => ['id' => $orderId]
            ])->setHeader('Access-Control-Allow-Origin', '*');

        } catch (\Exception $e) {
            return $this->failServerError($e->getMessage())->setHeader('Access-Control-Allow-Origin', '*');
        }
    }

    // Cari Pesanan by Email
    public function getByEmail($email = null)
    {
        try {
            // Join 3 tabel: Orders, Customers, Restaurants
            $orders = $this->model
                ->select('orders.*, restaurants.name as restaurant_name')
                ->join('customers', 'customers.id = orders.customer_id')
                ->join('restaurants', 'restaurants.id = orders.restaurant_id')
                ->where('customers.email', $email)
                ->orderBy('created_at', 'DESC')
                ->findAll();

            return $this->respond(['status' => 'success', 'data' => $orders])
                ->setHeader('Access-Control-Allow-Origin', '*');
        } catch (\Exception $e) {
            return $this->failServerError($e->getMessage())->setHeader('Access-Control-Allow-Origin', '*');
        }
    }
}
