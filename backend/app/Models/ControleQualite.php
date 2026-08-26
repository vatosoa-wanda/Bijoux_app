<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ControleQualite extends Model
{
    protected $table = 'controle_qualite';
    protected $primaryKey = 'id_controle';

    const CREATED_AT = 'date_controle';
    const UPDATED_AT = null;

    protected $fillable = ['id_of', 'quantite_controlee', 'quantite_validee', 'quantite_rejetee', 'commentaire'];

    public function defauts()
    {
        return $this->hasMany(DefautConstate::class, 'id_controle');
    }

    public function ordreFabrication()
    {
        return $this->belongsTo(OrdreFabrication::class, 'id_of');
    }
}