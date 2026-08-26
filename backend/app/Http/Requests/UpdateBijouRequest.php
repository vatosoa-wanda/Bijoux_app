<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBijouRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'id_type_bijou'  => ['sometimes', 'required', 'exists:type_bijou,id_type_bijou'],
            'id_collection'  => ['nullable', 'exists:collection,id_collection'],
            'reference'      => ['sometimes', 'required', 'string', 'max:50', Rule::unique('bijou', 'reference')->ignore($this->bijou)],
            'nom'            => ['sometimes', 'required', 'string', 'max:100'],
            'taille'         => ['nullable', 'in:XS,S,M,L,XL'],
            'complexite'     => ['nullable', 'in:Simple,Moyenne,Complexe'],
            'temps_fabrication_minutes' => ['sometimes', 'required', 'integer', 'min:1'],
            'photo_url'      => ['nullable', 'string', 'max:255'],
            'description'    => ['nullable', 'string'],
            'actif'          => ['boolean'],
        ];
    }
}