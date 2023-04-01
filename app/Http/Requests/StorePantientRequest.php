<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePantientRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'birthday' => 'required',
            'name' => 'required|string',
            'mon' => 'required|string',
            'cpf' => 'required|string',
            'cns' => 'required|string',
            'cep' => 'required|string',
            'photo'=>'image|mimes:jpg,png,jpeg,gif,svg|max:2048',
        ];
    }
}
