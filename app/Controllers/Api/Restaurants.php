<?php

namespace App\Controllers\Api;

use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\HTTP\ResponseInterface;

class Restaurants extends ResourceController
{
    protected $modelName = 'App\Models\RestaurantModel';
    protected $format    = 'json';

    /**
     * Handle Preflight Request (OPTIONS)
     * Penting untuk mengatasi error CORS
     */
    public function options()
    {
        return $this->response
            ->setHeader('Access-Control-Allow-Origin', '*') 
            ->setHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS, PUT, DELETE')
            ->setHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With')
            ->setStatusCode(200);
    }

    /**
     * GET /api/v1/restaurants
     * Return all restaurants
     */
    public function index()
    {
        try {
            $restaurants = $this->model->findAll();
            
            return $this->respond([
                'status' => 'success',
                'data' => $restaurants
            ], ResponseInterface::HTTP_OK)
            ->setHeader('Access-Control-Allow-Origin', '*'); // Tambahkan header ini

        } catch (\Exception $e) {
            return $this->respond([
                'status' => 'error',
                'message' => $e->getMessage()
            ], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR)
            ->setHeader('Access-Control-Allow-Origin', '*');
        }
    }

    /**
     * GET /api/v1/restaurants/:id
     * Return restaurant by ID
     */
    public function show($id = null)
    {
        try {
            $restaurant = $this->model->find($id);
            
            if (!$restaurant) {
                return $this->respond([
                    'status' => 'error',
                    'message' => 'Restaurant not found'
                ], ResponseInterface::HTTP_NOT_FOUND)
                ->setHeader('Access-Control-Allow-Origin', '*');
            }

            return $this->respond([
                'status' => 'success',
                'data' => $restaurant
            ], ResponseInterface::HTTP_OK)
            ->setHeader('Access-Control-Allow-Origin', '*');

        } catch (\Exception $e) {
            return $this->respond([
                'status' => 'error',
                'message' => $e->getMessage()
            ], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR)
            ->setHeader('Access-Control-Allow-Origin', '*');
        }
    }

    /**
     * POST /api/v1/restaurants
     * Create new restaurant
     */
    public function create()
    {
        try {
            $data = $this->request->getJSON();

            // Validate input
            if (!$this->validate([
                'name' => 'required|min_length[3]',
                'address' => 'required|min_length[5]',
                'phone' => 'required',
                'email' => 'required|valid_email|is_unique[restaurants.email]'
            ])) {
                return $this->respond([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $this->validator->getErrors()
                ], ResponseInterface::HTTP_BAD_REQUEST)
                ->setHeader('Access-Control-Allow-Origin', '*');
            }

            $id = $this->model->insert($data);

            return $this->respond([
                'status' => 'success',
                'message' => 'Restaurant created successfully',
                'data' => $this->model->find($id)
            ], ResponseInterface::HTTP_CREATED)
            ->setHeader('Access-Control-Allow-Origin', '*');

        } catch (\Exception $e) {
            return $this->respond([
                'status' => 'error',
                'message' => $e->getMessage()
            ], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR)
            ->setHeader('Access-Control-Allow-Origin', '*');
        }
    }

    /**
     * PUT /api/v1/restaurants/:id
     * Update restaurant
     */
    public function update($id = null)
    {
        try {
            if (!$this->model->find($id)) {
                return $this->respond([
                    'status' => 'error',
                    'message' => 'Restaurant not found'
                ], ResponseInterface::HTTP_NOT_FOUND)
                ->setHeader('Access-Control-Allow-Origin', '*');
            }

            $data = $this->request->getJSON();
            $this->model->update($id, $data);

            return $this->respond([
                'status' => 'success',
                'message' => 'Restaurant updated successfully',
                'data' => $this->model->find($id)
            ], ResponseInterface::HTTP_OK)
            ->setHeader('Access-Control-Allow-Origin', '*');

        } catch (\Exception $e) {
            return $this->respond([
                'status' => 'error',
                'message' => $e->getMessage()
            ], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR)
            ->setHeader('Access-Control-Allow-Origin', '*');
        }
    }

    /**
     * DELETE /api/v1/restaurants/:id
     * Delete restaurant
     */
    public function delete($id = null)
    {
        try {
            if (!$this->model->find($id)) {
                return $this->respond([
                    'status' => 'error',
                    'message' => 'Restaurant not found'
                ], ResponseInterface::HTTP_NOT_FOUND)
                ->setHeader('Access-Control-Allow-Origin', '*');
            }

            $this->model->delete($id);

            return $this->respond([
                'status' => 'success',
                'message' => 'Restaurant deleted successfully'
            ], ResponseInterface::HTTP_OK)
            ->setHeader('Access-Control-Allow-Origin', '*');

        } catch (\Exception $e) {
            return $this->respond([
                'status' => 'error',
                'message' => $e->getMessage()
            ], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR)
            ->setHeader('Access-Control-Allow-Origin', '*');
        }
    }
}
