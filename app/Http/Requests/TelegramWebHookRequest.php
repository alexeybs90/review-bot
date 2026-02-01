<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TelegramWebHookRequest extends FormRequest
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
            'message' => 'required_without:callback_query|array',
            'callback_query' => 'required_without:message|array',

            'message.text' => 'required_with:message|string',
            'message.message_id' => 'required_with:message|numeric',
            'message.from' => 'required_with:message|array',
            'message.chat' => 'required_with:message|array',
            'message.from.first_name' => 'required_with:message.from|string',
            'message.chat.id' => 'required_with:message.chat|string',

            'callback_query.text' => 'required_with:callback_query|string',
            'callback_query.message_id' => 'required_with:callback_query|numeric',
            'callback_query.from' => 'required_with:callback_query|array',
            'callback_query.chat' => 'required_with:callback_query|array',
            'callback_query.from.first_name' => 'required_with:callback_query.from|string',
            'callback_query.chat.id' => 'required_with:callback_query.chat|string',
        ];
    }
}
