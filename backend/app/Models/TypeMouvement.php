<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TypeMouvement extends Model
{
    protected $table = 'type_mouvement';
    protected $primaryKey = 'id_type_mvt';
    public $timestamps = false;

    protected $fillable = ['code', 'libelle', 'sens'];

    public function mouvements()
    {
        return $this->hasMany(MouvementStock::class, 'id_type_mvt');
    }
}