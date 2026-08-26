<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DefautConstateResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id_defaut'    => $this->id_defaut,
            'type_defaut'  => $this->whenLoaded('typeDefaut', fn () => $this->typeDefaut->libelle),
            'quantite'     => $this->quantite,
            'commentaire'  => $this->commentaire,
            'photo_url'    => $this->photo_url,
        ];
    }
}