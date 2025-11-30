<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class FinancialAssistanceRequestResource extends JsonResource
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
            'type' => [
                'id' => $this->assistanceType->id,
                'name' => $this->assistanceType->name,
                'description' => $this->whenLoaded('assistanceType', $this->assistanceType->description),
            ],
            'status' => [
                'code' => $this->status,
                'name' => $this->status_name,
            ],
            'form_data' => $this->form_data,
            'comment' => $this->comment,
            'submitted_at' => $this->submitted_at?->format('d.m.Y H:i'),
            'processed_at' => $this->processed_at?->format('d.m.Y H:i'),
            'sotrudnik' => [
                'id' => $this->sotrudnik->id,
                'name' => $this->sotrudnik->fio ?? ($this->sotrudnik->last_name . ' ' . $this->sotrudnik->first_name . ' ' . $this->sotrudnik->father_name),
                'full_name' => $this->sotrudnik->fio ?? ($this->sotrudnik->last_name . ' ' . $this->sotrudnik->first_name . ' ' . $this->sotrudnik->father_name),
                'position' => $this->sotrudnik->position->name_ru ?? null,
                'organization' => $this->sotrudnik->organization->name_ru ?? null,
            ],
            'signer' => $this->when($this->signer, [
                'name' => $this->signer?->full_name,
                'position' => $this->signer?->position,
            ]),
            'files' => FinancialAssistanceRequestFileResource::collection($this->whenLoaded('files')),
            'history' => FinancialAssistanceRequestHistoryResource::collection($this->whenLoaded('statusHistory')),
            'pdf_url' => $this->pdf_path && Storage::disk('public')->exists($this->pdf_path) 
                ? Storage::disk('public')->url($this->pdf_path) 
                : null,
            'pdf_available' => $this->pdf_path && Storage::disk('public')->exists($this->pdf_path),
            'created_at' => $this->created_at->format('d.m.Y H:i'),
            'updated_at' => $this->updated_at->format('d.m.Y H:i'),
        ];
    }
}
