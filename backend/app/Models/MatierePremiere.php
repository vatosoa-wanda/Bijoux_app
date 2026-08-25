<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MatierePremiere extends Model
{
    protected $table = 'matiere_premiere';
    protected $primaryKey = 'id_matiere';

    protected $fillable = [
        'id_categorie', 'id_unite', 'nom', 'couleur',
        'quantite_stock', 'seuil_alerte', 'prix_unitaire',
        'date_peremption', 'actif',
    ];

    protected $casts = [
        'quantite_stock' => 'decimal:2',
        'seuil_alerte'   => 'decimal:2',
        'prix_unitaire'  => 'decimal:4',
        'date_peremption'=> 'date',
        'actif'          => 'boolean',
    ];

    public function categorie()
    {
        return $this->belongsTo(CategorieMatiere::class, 'id_categorie');
    }

    public function unite()
    {
        return $this->belongsTo(UniteMesure::class, 'id_unite');
    }

    public function mouvements()
    {
        return $this->hasMany(MouvementStock::class, 'id_matiere');
    }

    public function compositions()
    {
        return $this->hasMany(CompositionBijou::class, 'id_matiere');
    }
}