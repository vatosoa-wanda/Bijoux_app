<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TypeDefaut extends Model
{
    protected $table = 'type_defaut';
    protected $primaryKey = 'id_type_defaut';
    public $timestamps = false;

    protected $fillable = ['code', 'libelle', 'description'];
}