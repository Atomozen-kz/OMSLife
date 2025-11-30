<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FinancialAssistanceTypeRowResource extends JsonResource
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
            'type' => $this->type,
            'type_name' => $this->getFieldTypes()[$this->type] ?? $this->type,
            'required' => $this->required,
            'default_value' => $this->resolveDefaultValue(auth()->user()),
            'sort' => $this->sort,
            'available_placeholders' => $this->when(
                $request->route()->getName() === 'api.financial-assistance.type.details',
                $this->getAvailablePlaceholders()
            ),
        ];
    }
}
