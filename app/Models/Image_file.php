<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Image_file extends Model
{
    // use HasFactory;
    protected $table = "images";
    protected $primaryKey ="id";
	public $timestamps = false;
}
