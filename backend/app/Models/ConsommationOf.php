<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConsommationOf extends Model
{
    protected $table = 'consommation_of';
    protected $primaryKey = 'id_consommation';

    const CREATED_AT = 'date_consommation';
    const UPDATED_AT = null;

    protected $fillable = ['id_of', 'id_matiere', 'quantite_consommee'];
}