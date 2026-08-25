<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MatierePremiereResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id_matiere'      => $this->id_matiere,
            'nom'             => $this->nom,
            'couleur'         => $this->couleur,
            'categorie'       => $this->whenLoaded('categorie', fn () => $this->categorie->nom),
            'unite'           => $this->whenLoaded('unite', fn () => $this->unite->code),
            'quantite_stock'  => (float) $this->quantite_stock,
            'seuil_alerte'    => (float) $this->seuil_alerte,
            'prix_unitaire'   => (float) $this->prix_unitaire,
            'statut_stock'    => $this->quantite_stock <= $this->seuil_alerte ? 'ALERTE' : 'OK',
            'date_peremption' => $this->date_peremption,
            'actif'           => $this->actif,
        ];
    }
}