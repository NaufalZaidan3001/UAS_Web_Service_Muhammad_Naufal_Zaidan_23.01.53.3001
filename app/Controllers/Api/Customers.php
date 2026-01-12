<?php
namespace App\Controllers\Api;
use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\HTTP\ResponseInterface;

class CustomersController extends ResourceController
{
    protected $modelName = 'App\Models\CustomerModel';
    protected $format = 'json';

    public function options()
    {
        return $this->response
            ->setHeader('Access-Control-Allow-Origin', '*')
            ->setHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS, PUT, DELETE')
            ->setHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With')
            ->setStatusCode(200);
    }

    public function create()
    {
        try {
            $data = $this->request->getJSON();
            
            // Cek apakah email sudah ada
            $existing = $this->model->where('email', $data->email)->first();
            if ($existing) {
                return $this->respond([
                    'status' => 'success',
                    'data' => $existing
                ])->setHeader('Access-Control-Allow-Origin', '*');
            }

            $id = $this->model->insert($data);
            return $this->respondCreated([
                'status' => 'success',
                'data' => $this->model->find($id)
            ])->setHeader('Access-Control-Allow-Origin', '*');

        } catch (\Exception $e) {
            return $this->failServerError($e->getMessage())
                ->setHeader('Access-Control-Allow-Origin', '*');
        }
    }
}
