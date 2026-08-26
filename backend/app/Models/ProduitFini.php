<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProduitFini extends Model
{
    protected $table = 'produit_fini';
    protected $primaryKey = 'id_produit_fini';

    const CREATED_AT = 'created_at';
    const UPDATED_AT = null;

    protected $fillable = [
        'id_bijou', 'id_of', 'reference', 'quantite_stock',
        'cout_revient', 'prix_vente', 'date_fabrication', 'statut',
    ];

    public function bijou()
    {
        return $this->belongsTo(Bijou::class, 'id_bijou');
    }
}