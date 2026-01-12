<?php

namespace App\Models;

use CodeIgniter\Model;

class RestaurantModel extends Model
{
    protected $table            = 'restaurants';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    
    // Kolom yang boleh diisi (INSERT/UPDATE)
    protected $allowedFields    = ['name', 'address', 'phone', 'email'];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = ''; // Kosongkan jika tabel Anda tidak punya kolom updated_at
    protected $deletedField  = '';

    // Validation Rules (Opsional tapi bagus)
    protected $validationRules      = [
        'name'    => 'required|min_length[3]|max_length[100]',
        'address' => 'required|min_length[5]',
        'phone'   => 'required',
        'email'   => 'required|valid_email' // Hapus is_unique jika bikin ribet saat testing
    ];
    
    protected $validationMessages   = [];
    protected $skipValidation       = false;
    protected $cleanValidationRules = true;
}
