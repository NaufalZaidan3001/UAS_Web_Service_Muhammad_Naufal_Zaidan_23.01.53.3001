<?php

namespace App\Controllers\Api;

use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\HTTP\ResponseInterface;

class Restaurants extends ResourceController
{
    protected $modelName = 'App\Models\RestaurantModel';
    protected $format    = 'json';

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
            ], ResponseInterface::HTTP_OK);
        } catch (\Exception $e) {
            return $this->respond([
                'status' => 'error',
                'message' => $e->getMessage()
            ], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
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
                ], ResponseInterface::HTTP_NOT_FOUND);
            }

            return $this->respond([
                'status' => 'success',
                'data' => $restaurant
            ], ResponseInterface::HTTP_OK);
        } catch (\Exception $e) {
            return $this->respond([
                'status' => 'error',
                'message' => $e->getMessage()
            ], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
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
                ], ResponseInterface::HTTP_BAD_REQUEST);
            }

            $id = $this->model->insert($data);

            return $this->respond([
                'status' => 'success',
                'message' => 'Restaurant created successfully',
                'data' => $this->model->find($id)
            ], ResponseInterface::HTTP_CREATED);
        } catch (\Exception $e) {
            return $this->respond([
                'status' => 'error',
                'message' => $e->getMessage()
            ], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
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
                ], ResponseInterface::HTTP_NOT_FOUND);
            }

            $data = $this->request->getJSON();
            $this->model->update($id, $data);

            return $this->respond([
                'status' => 'success',
                'message' => 'Restaurant updated successfully',
                'data' => $this->model->find($id)
            ], ResponseInterface::HTTP_OK);
        } catch (\Exception $e) {
            return $this->respond([
                'status' => 'error',
                'message' => $e->getMessage()
            ], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
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
                ], ResponseInterface::HTTP_NOT_FOUND);
            }

            $this->model->delete($id);

            return $this->respond([
                'status' => 'success',
                'message' => 'Restaurant deleted successfully'
            ], ResponseInterface::HTTP_OK);
        } catch (\Exception $e) {
            return $this->respond([
                'status' => 'error',
                'message' => $e->getMessage()
            ], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
