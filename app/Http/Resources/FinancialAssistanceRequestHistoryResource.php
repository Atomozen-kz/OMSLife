<?php

namespace App\Http\Resources;

use App\Models\FinancialAssistanceRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FinancialAssistanceRequestHistoryResource extends JsonResource
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
            'status' => [
                'code' => $this->new_status,
                'name' => FinancialAssistanceRequest::getStatuses()[$this->new_status] ?? 'Неизвестно',
            ],
            'comment' => $this->comment,
            'changed_by' => [
                'name' => $this->whenLoaded('changedBy', $this->changedBy?->name ?? 'Система'),
            ],
            'changed_at' => $this->changed_at
                ? $this->changed_at->format('d.m.Y H:i')
                : $this->created_at->format('d.m.Y H:i'),
            'created_at' => $this->created_at->format('d.m.Y H:i'),
        ];
    }
}
