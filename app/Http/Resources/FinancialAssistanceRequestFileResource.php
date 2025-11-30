<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FinancialAssistanceRequestFileResource extends JsonResource
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
            'field_name' => $this->field_name,
            'row_id' => $this->row_id,
            'original_name' => $this->original_name,
            'file_type' => $this->file_type,
            'file_size' => $this->file_size,
            'file_size_human' => $this->human_readable_size,
            'url' => $this->url,
            'is_image' => $this->isImage(),
            'is_pdf' => $this->isPdf(),
            'created_at' => $this->created_at->format('d.m.Y H:i'),
            'updated_at' => $this->updated_at->format('d.m.Y H:i'),
        ];
    }
}