<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PetTags extends Model
{
    protected $table = 'pet_tags';
  	protected $fillable = ['pet','tag','active'];
}
