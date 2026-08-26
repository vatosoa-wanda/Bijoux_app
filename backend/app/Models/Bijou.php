<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bijou extends Model
{
    protected $table = 'bijou';
    protected $primaryKey = 'id_bijou';

    protected $fillable = [
        'id_type_bijou', 'id_collection', 'reference', 'nom',
        'taille', 'complexite', 'temps_fabrication_minutes',
        'photo_url', 'description', 'actif',
    ];

    protected $casts = ['actif' => 'boolean'];

    public function typeBijou()
    {
        return $this->belongsTo(TypeBijou::class, 'id_type_bijou');
    }

    public function collection()
    {
        return $this->belongsTo(Collection::class, 'id_collection');
    }

    public function compositions()
    {
        return $this->hasMany(CompositionBijou::class, 'id_bijou');
    }

    public function ordresFabrication()
    {
        return $this->hasMany(OrdreFabrication::class, 'id_bijou');
    }
}