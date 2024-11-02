<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Integrations extends Model
{
    use HasFactory;

    protected $table = 'integrations';

    protected $fillable = [
        'id',
        'retail_url',
        'active',
        'selected_inputs',
        'retail_token',
        'dadata_apiKey',
        'dadata_secretKey',
    ];
}
