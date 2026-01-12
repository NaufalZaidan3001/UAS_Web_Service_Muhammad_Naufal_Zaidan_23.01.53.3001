<?php
namespace App\Models;
use CodeIgniter\Model;

class MenuModel extends Model
{
    protected $table = 'menus';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $allowedFields = ['restaurant_id', 'name', 'description', 'price', 'category', 'is_available'];
    protected $useTimestamps = true;
}
