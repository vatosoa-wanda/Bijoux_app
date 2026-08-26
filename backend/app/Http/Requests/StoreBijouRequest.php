<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBijouRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'id_type_bijou'  => ['required', 'exists:type_bijou,id_type_bijou'],
            'id_collection'  => ['nullable', 'exists:collection,id_collection'],
            'reference'      => ['required', 'string', 'max:50', 'unique:bijou,reference'],
            'nom'            => ['required', 'string', 'max:100'],
            'taille'         => ['nullable', 'in:XS,S,M,L,XL'],
            'complexite'     => ['nullable', 'in:Simple,Moyenne,Complexe'],
            'temps_fabrication_minutes' => ['required', 'integer', 'min:1'],
            'photo_url'      => ['nullable', 'string', 'max:255'],
            'description'    => ['nullable', 'string'],
        ];
    }
}