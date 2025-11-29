<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RequestRegister extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
        'FirstName'=>'required|string|max:255',
        'LastName'=>'required|string|max:255',
        'mobail'=>'required|string|min:10|unique:users,mobail',
        'password'=>'required|string|max:15|confirmed',
        'ProfileImage'=>'nullable|image|mimes:png,jpg,jpeg,gif|max:2048',
        'BirthDate'=>'required|date|before:today',
        'CardImage'=>'nullable|image|mimes:png,jpg,jpeg,gif|max:2048'


        ];
    }
}
