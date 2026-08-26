<?php

namespace App\Http\Requests;

class OrderRequest extends CartRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'idempotency_key' => ['required', 'ulid'],
        ];
    }

    public function idempotencyKey(): string
    {
        return $this->string('idempotency_key')->toString();
    }
}
