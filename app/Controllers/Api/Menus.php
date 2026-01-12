<?php
namespace App\Controllers\Api;
use CodeIgniter\RESTful\ResourceController;

class MenusController extends ResourceController
{
    protected $modelName = 'App\Models\MenuModel';
    protected $format = 'json';

    public function options()
    {
        return $this->response->setStatusCode(200)
            ->setHeader('Access-Control-Allow-Origin', '*')
            ->setHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS')
            ->setHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization');
    }

    public function getByRestaurant($restaurantId = null)
    {
        try {
            $menus = $this->model->where('restaurant_id', $restaurantId)->findAll();
            return $this->respond(['status' => 'success', 'data' => $menus])
                ->setHeader('Access-Control-Allow-Origin', '*');
        } catch (\Exception $e) {
            return $this->failServerError($e->getMessage())->setHeader('Access-Control-Allow-Origin', '*');
        }
    }
}
