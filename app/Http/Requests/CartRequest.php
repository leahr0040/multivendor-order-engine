<?php

namespace App\Http\Requests;

use App\Data\CartLineData;
use App\Data\UnavailableProductsData;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Validator;

class CartRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1'],
            'items.*.product' => ['required', 'string', 'distinct'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'user' => ['nullable', 'string', 'exists:users,ulid'],
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $unknown = $this->productUlids()->diff($this->productIds()->keys());

            if ($unknown->isNotEmpty()) {
                $validator->errors()->add('items', __('cart.unknown', ['products' => $unknown->implode(', ')]));
            }
        }];
    }

    /**
     * @return Collection<int, CartLineData>
     */
    public function cartLines(): Collection
    {
        return $this->collect('items')->map(fn (array $item) => new CartLineData(
            product_id: $this->productIds()[$item['product']],
            quantity: (int) $item['quantity'],
        ))->values();
    }

    // chooses the loyalty tier for pricing; it is not an authenticated identity
    public function customer(): ?User
    {
        return once(fn () => $this->filled('user')
            ? User::firstWhere('ulid', $this->input('user'))
            : null);
    }

    public function unavailable(UnavailableProductsData $unavailable): ValidationException
    {
        $ulids = $this->productIds()
            ->filter(fn (int $id) => $unavailable->product_ids->contains($id))
            ->keys();

        return ValidationException::withMessages([
            'items' => __('cart.unavailable', ['products' => $ulids->implode(', ')]),
        ]);
    }

    /**
     * @return Collection<int, string>
     */
    private function productUlids(): Collection
    {
        return $this->collect('items')->pluck('product')->values();
    }

    /**
     * One snapshot per request: validation, pricing and the failure message must
     * agree on what was submitted.
     *
     * @return Collection<string, int>
     */
    private function productIds(): Collection
    {
        return once(function (): Collection {
            /** @var Collection<string, int> $ids */
            $ids = Product::whereIn('ulid', $this->productUlids())->pluck('id', 'ulid');

            return $ids;
        });
    }
}
