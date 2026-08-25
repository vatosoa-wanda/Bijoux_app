<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MouvementStockResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id_mouvement'      => $this->id_mouvement,
            'matiere'           => $this->whenLoaded('matierePremiere', fn () => $this->matierePremiere->nom),
            'type_mouvement'    => $this->whenLoaded('typeMouvement', fn () => $this->typeMouvement->libelle),
            'sens'              => $this->whenLoaded('typeMouvement', fn () => $this->typeMouvement->sens),
            'quantite'          => (float) $this->quantite,
            'prix_total'        => $this->prix_total !== null ? (float) $this->prix_total : null,
            'reference_externe' => $this->reference_externe,
            'commentaire'       => $this->commentaire,
            'date_mouvement'    => $this->date_mouvement,
        ];
    }
}