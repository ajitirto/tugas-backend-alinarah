<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => [
                'required',
                'string',
                'max:255',
            ],

            'body' => [
                'required',
                'string',
            ],

            'user_id' => [
                'required',
                'integer',
                'exists:users,id',
            ],

            'tags' => [
                'nullable',
                'array',
            ],

            'tags.*' => [
                'string',
            ],
        ];
    }
}
