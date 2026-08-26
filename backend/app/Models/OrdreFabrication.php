<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrdreFabrication extends Model
{
    protected $table = 'ordre_fabrication';
    protected $primaryKey = 'id_of';

    protected $fillable = [
        'id_bijou', 'id_statut_prod', 'id_commande', 'reference',
        'quantite_prevue', 'quantite_realisee', 'quantite_rejetee',
        'date_debut', 'date_fin_prevue', 'date_fin_reelle', 'notes',
    ];

    public function bijou()
    {
        return $this->belongsTo(Bijou::class, 'id_bijou');
    }

    public function statutProduction()
    {
        return $this->belongsTo(StatutProduction::class, 'id_statut_prod');
    }

    public function consommations()
    {
        return $this->hasMany(ConsommationOf::class, 'id_of');
    }
}