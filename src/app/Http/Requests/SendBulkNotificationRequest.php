<?php

declare(strict_types = 1);

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class SendBulkNotificationRequest extends FormRequest
{
    public function rules(): array
    {
        return [
          'channel'          => ['required', 'in:sms,email'],
          'type'             => ['required', 'in:transactional,bulk'],
          'message'          => ['required', 'string', 'max:1000'],
          'idempotency_key'  => ['required', 'string', 'max:255'],
          'recipients'       => ['required', 'array', 'min:1', 'max:10000'],
          'recipients.*.id'  => ['required', 'string'],
          'recipients.*.address' => ['required', 'string'], // телефон или email
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            response()->json([
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422)
        );
    }
}