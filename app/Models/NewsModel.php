<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NewsModel extends Model
{
    // use HasFactory;
    protected $table = "news_model";
    protected $primaryKey ="id";
	public $timestamps = false;
}
