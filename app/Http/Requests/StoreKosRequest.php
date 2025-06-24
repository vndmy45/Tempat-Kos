<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreKosRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nama_kos' => 'required|string|max:255',
            'alamat' => 'required|string',
            'harga' => 'required|numeric',
            'jenis_kost' => 'required|in:Putra,Putri,Campur',
            'longitude' => 'required|numeric',
            'latitude' => 'required|numeric',
            'nilai_rating' => 'nullable|numeric|min:0|max:5',
            'kontak_pemilik' => 'required|string|max:15',
        ];
    }

}
