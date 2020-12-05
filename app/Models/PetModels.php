<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PetModels extends Model
{
    protected $table = 'pet';
  	protected $fillable = ['name','category','photourls','tags','status'];
}
