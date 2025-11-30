<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FinancialAssistanceTypeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            // 'status' => $this->status,
            // 'sort' => $this->sort,
            'fields' => FinancialAssistanceTypeRowResource::collection($this->whenLoaded('typeRows')),
            // 'created_at' => $this->created_at?->format('d.m.Y H:i'),
            // 'updated_at' => $this->updated_at?->format('d.m.Y H:i'),
        ];
    }
}
