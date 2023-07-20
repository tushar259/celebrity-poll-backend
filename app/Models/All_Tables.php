<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class All_Tables extends Model
{
    // use HasFactory;
    protected $table = "all_tables";
    protected $primaryKey ="id";
	public $timestamps = false;
}
