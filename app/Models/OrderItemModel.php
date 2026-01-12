<?php
namespace App\Models;
use CodeIgniter\Model;

class OrderItemModel extends Model
{
    protected $table = 'order_items';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $allowedFields = ['order_id', 'menu_id', 'quantity', 'price'];
    protected $useTimestamps = true;
}
