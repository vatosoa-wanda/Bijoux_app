<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// Attention : ce nom entre en conflit avec Illuminate\Support\Collection. Tant que vous n'importez pas les deux dans le même fichier ça ne pose pas de problème, mais soyez vigilant dans vos contrôleurs (utilisez le nom complet \App\Models\Collection si besoin)

class Collection extends Model
{
    protected $table = 'collection';
    protected $primaryKey = 'id_collection';

    const CREATED_AT = 'created_at';
    const UPDATED_AT = null;

    protected $fillable = ['nom', 'saison', 'annee', 'description', 'actif'];

    protected $casts = ['actif' => 'boolean'];

    public function bijoux()
    {
        return $this->hasMany(Bijou::class, 'id_collection');
    }
}