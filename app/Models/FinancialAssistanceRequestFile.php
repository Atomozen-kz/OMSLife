<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Orchid\Filters\Filterable;
use Orchid\Screen\AsSource;

class FinancialAssistanceRequestFile extends Model
{
    use AsSource, Filterable;

    protected $table = 'financial_assistance_request_files';

    protected $fillable = [
        'id_request',
        'field_name',
        'row_id',
        'file_path',
        'original_name',
        'file_type',
        'file_size'
    ];

    protected $casts = [
        'file_size' => 'integer',
    ];

    /**
     * Связь с заявкой на материальную помощь
     */
    public function request(): BelongsTo
    {
        return $this->belongsTo(FinancialAssistanceRequest::class, 'id_request');
    }

    /**
     * Связь с типом строки (полем формы)
     */
    public function typeRow(): BelongsTo
    {
        return $this->belongsTo(FinancialAssistanceTypeRow::class, 'row_id');
    }

    /**
     * Проверка, является ли файл изображением
     */
    public function isImage(): bool
    {
        return in_array($this->file_type, [
            'image/jpeg',
            'image/jpg', 
            'image/png',
            'image/gif',
            'image/bmp',
            'image/webp'
        ]);
    }

    /**
     * Проверка, является ли файл PDF
     */
    public function isPdf(): bool
    {
        return $this->file_type === 'application/pdf';
    }

    /**
     * Получить размер файла в человекочитаемом формате
     */
    public function getHumanReadableSizeAttribute(): string
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Получить полный URL файла
     */
    public function getUrlAttribute(): string
    {
        return asset('storage/' . $this->file_path);
    }

    /**
     * Scope для фильтрации по полю формы
     */
    public function scopeForField($query, string $fieldName)
    {
        return $query->where('field_name', $fieldName);
    }

    /**
     * Scope для фильтрации по заявке
     */
    public function scopeForRequest($query, int $requestId)
    {
        return $query->where('id_request', $requestId);
    }
}