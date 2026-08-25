<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompositionBijou extends Model
{
    protected $table = 'composition_bijou';
    protected $primaryKey = 'id_composition';
    public $timestamps = false;

    protected $fillable = ['id_bijou', 'id_matiere', 'quantite_necessaire'];

    public function bijou()
    {
        return $this->belongsTo(Bijou::class, 'id_bijou');
    }

    public function matierePremiere()
    {
        return $this->belongsTo(MatierePremiere::class, 'id_matiere');
    }
}