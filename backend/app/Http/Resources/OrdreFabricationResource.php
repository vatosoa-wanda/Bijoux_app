<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrdreFabricationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id_of'              => $this->id_of,
            'reference'          => $this->reference,
            'bijou'              => $this->whenLoaded('bijou', fn () => $this->bijou->nom),
            'statut'             => $this->whenLoaded('statutProduction', fn () => $this->statutProduction->libelle),
            'code_statut'        => $this->whenLoaded('statutProduction', fn () => $this->statutProduction->code),
            'quantite_prevue'    => $this->quantite_prevue,
            'quantite_realisee'  => $this->quantite_realisee,
            'quantite_rejetee'   => $this->quantite_rejetee,
            'date_debut'         => $this->date_debut,
            'date_fin_prevue'    => $this->date_fin_prevue,
            'date_fin_reelle'    => $this->date_fin_reelle,
        ];
    }
}