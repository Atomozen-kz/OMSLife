<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Orchid\Attachment\Attachable;
use Orchid\Filters\Filterable;
use Orchid\Screen\AsSource;

class FinancialAssistanceType extends Model
{
    use AsSource, Filterable, Attachable;

    protected $table = 'financial_assistance_types';

    protected $fillable = [
        'name',
        'description',
        'statement_html',
        'status',
        'sort'
    ];

    protected $casts = [
        'status' => 'boolean',
        'sort' => 'integer',
    ];

    /**
     * Связь с полями типа
     */
    public function typeRows(): HasMany
    {
        return $this->hasMany(FinancialAssistanceTypeRow::class, 'id_type')->orderBy('sort');
    }

    /**
     * Связь с заявками
     */
    public function requests(): HasMany
    {
        return $this->hasMany(FinancialAssistanceRequest::class, 'id_type');
    }

    /**
     * Scope для активных типов
     */
    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    /**
     * Scope для сортировки
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort')->orderBy('name');
    }

    /**
     * Получить полный HTML документ, объединяя header, content и footer
     */
    public function getFullHtmlDocument($request = null, $sotrudnik = null, $signer = null): string
    {
        $headerData = [
            'sotrudnik' => $sotrudnik,
            'assistance_type' => $this,
            'current_date' => date('d.m.Y'),
            'department' => $sotrudnik->organization->name_ru ?? null,
        ];

        if ($request) {
            $headerData['request_id'] = $request->id;
        }

        $footerData = [
            'sotrudnik' => $sotrudnik,
            'signer' => $signer,
            'current_date' => date('d.m.Y'),
            'processed_date' => $request->processed_at ? $request->processed_at->format('d.m.Y') : null,
            'request_id' => $request->id ?? null,
        ];

        $header = view('pdf.financial-assistance-header', $headerData)->render();
        $content = $this->statement_html ?? $this->getDefaultContentTemplate();
        $footer = view('pdf.financial-assistance-footer', $footerData)->render();

        return $header . $content . $footer;
    }

    /**
     * Получить шаблон центральной части по умолчанию
     */
    public function getDefaultContentTemplate(): string
    {
        return '
<div class="main-content">
    <h2>Основание для предоставления материальной помощи</h2>
    
    <p>Прошу предоставить материальную помощь в связи с:</p>
    
    <div class="form-fields">
        {{form_fields}}
    </div>
    
    <div style="margin-top: 20px;">
        <p><strong>Сумма материальной помощи:</strong> _________________ тенге</p>
        <p><strong>Дополнительные комментарии:</strong></p>
        <div style="border: 1px solid #ddd; min-height: 60px; padding: 10px; margin-top: 10px;">
            
        </div>
    </div>
</div>';
    }
}
