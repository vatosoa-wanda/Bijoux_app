<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ControleQualiteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id_controle'         => $this->id_controle,
            'of'                  => $this->whenLoaded('ordreFabrication', fn () => $this->ordreFabrication->reference),
            'date_controle'       => $this->date_controle,
            'quantite_controlee'  => $this->quantite_controlee,
            'quantite_validee'    => $this->quantite_validee,
            'quantite_rejetee'    => $this->quantite_rejetee,
            'commentaire'         => $this->commentaire,
            'defauts'             => DefautConstateResource::collection($this->whenLoaded('defauts')),
        ];
    }
}