<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UniteMesure extends Model
{
    protected $table = 'unite_mesure';
    protected $primaryKey = 'id_unite';
    public $timestamps = false;

    protected $fillable = ['code', 'libelle'];

    public function matieresPremieres()
    {
        return $this->hasMany(MatierePremiere::class, 'id_unite');
    }
}