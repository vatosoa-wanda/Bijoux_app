<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DefautConstate extends Model
{
    protected $table = 'defaut_constate';
    protected $primaryKey = 'id_defaut';
    public $timestamps = false;

    protected $fillable = ['id_controle', 'id_type_defaut', 'quantite', 'photo_url', 'commentaire'];

    public function typeDefaut()
    {
        return $this->belongsTo(TypeDefaut::class, 'id_type_defaut');
    }
}