<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CategorieMatiere extends Model
{
    protected $table = 'categorie_matiere';
    protected $primaryKey = 'id_categorie';
    public $timestamps = false;

    protected $fillable = ['nom', 'description'];

    public function matieresPremieres()
    {
        return $this->hasMany(MatierePremiere::class, 'id_categorie');
    }
}