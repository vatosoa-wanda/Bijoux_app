<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MouvementStock extends Model
{
    protected $table = 'mouvement_stock';
    protected $primaryKey = 'id_mouvement';

    const CREATED_AT = 'date_mouvement';
    const UPDATED_AT = null;

    protected $fillable = [
        'id_matiere', 'id_type_mvt', 'quantite',
        'prix_total', 'reference_externe', 'commentaire',
    ];

    public function matierePremiere()
    {
        return $this->belongsTo(MatierePremiere::class, 'id_matiere');
    }

    public function typeMouvement()
    {
        return $this->belongsTo(TypeMouvement::class, 'id_type_mvt');
    }
}