<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AccountResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->when($this->resource !== null, $this->id),
            'tenant_id' => $this->when($this->resource !== null, $this->tenant_id),
            'balance' => $this->when($this->resource !== null, number_format($this->balance, 2)),
            'created_at' => $this->when($this->resource !== null, $this->created_at?->toIso8601String()),
            'updated_at' => $this->when($this->resource !== null, $this->updated_at?->toIso8601String()),
        ];
    }
}
