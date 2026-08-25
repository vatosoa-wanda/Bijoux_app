<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TypeBijou extends Model
{
    protected $table = 'type_bijou';
    protected $primaryKey = 'id_type_bijou';
    public $timestamps = false;

    protected $fillable = ['nom', 'description'];

    public function bijoux()
    {
        return $this->hasMany(Bijou::class, 'id_type_bijou');
    }
}