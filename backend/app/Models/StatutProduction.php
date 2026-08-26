<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StatutProduction extends Model
{
    protected $table = 'statut_production';
    protected $primaryKey = 'id_statut_prod';
    public $timestamps = false;

    protected $fillable = ['code', 'libelle', 'ordre'];

    public function ordresFabrication()
    {
        return $this->hasMany(OrdreFabrication::class, 'id_statut_prod');
    }
}