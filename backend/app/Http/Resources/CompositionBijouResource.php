<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CompositionBijouResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id_composition'      => $this->id_composition,
            'matiere'             => $this->whenLoaded('matierePremiere', fn () => $this->matierePremiere->nom),
            'id_matiere'          => $this->id_matiere,
            'quantite_necessaire' => (float) $this->quantite_necessaire,
        ];
    }
}