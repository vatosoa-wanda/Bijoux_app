<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BijouResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id_bijou'    => $this->id_bijou,
            'reference'   => $this->reference,
            'nom'         => $this->nom,
            'type_bijou'  => $this->whenLoaded('typeBijou', fn () => $this->typeBijou->nom),
            'taille'      => $this->taille,
            'complexite'  => $this->complexite,
            'temps_fabrication_minutes' => $this->temps_fabrication_minutes,
            'photo_url'   => $this->photo_url,
            'actif'       => $this->actif,
            'compositions' => CompositionBijouResource::collection($this->whenLoaded('compositions')),
        ];
    }
}