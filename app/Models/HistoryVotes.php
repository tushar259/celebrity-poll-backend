<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HistoryVotes extends Model
{
    // use HasFactory;
    protected $table = "history_votes";
    protected $primaryKey ="id";
	public $timestamps = false;
	protected $fillable = [
        'star_name',
        'total_votes_received',
        'total_nominations',
        'total_won',
        // Add any other fields that you want to allow mass assignment for
    ];
}
