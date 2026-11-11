<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class City extends Model
{
    use HasFactory;

    public function country()
    {
        return $this->belongsTo('App\Models\Country', 'country_id')->select('id', 'country_name');
    }
}
