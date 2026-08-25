<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Parametre extends Model
{
    protected $table = 'parametre';
    protected $primaryKey = 'id_parametre';

    const CREATED_AT = null;
    const UPDATED_AT = 'updated_at';

    protected $fillable = ['cle', 'valeur', 'description'];
}