<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\FinancialAssistanceType;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Обновляем существующие записи, преобразуя полные HTML документы в центральную часть
        $types = FinancialAssistanceType::whereNotNull('statement_html')->get();
        
        foreach ($types as $type) {
            $html = $type->statement_html;
            
            // Если HTML содержит заголовок и footer, извлекаем только центральную часть
            if ($this->isFullDocument($html)) {
                $contentOnly = $this->extractContentOnly($html);
                $type->update(['statement_html' => $contentOnly]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // В down методе мы не можем восстановить полные документы,
        // так как header и footer теперь генерируются динамически
        // Оставляем пустым, так как изменения не критичны
    }
    
    /**
     * Проверить, является ли HTML полным документом
     */
    private function isFullDocument(string $html): bool
    {
        return strpos($html, '<h2>Заявление на материальную помощь</h2>') !== false ||
               strpos($html, 'Подпись:') !== false ||
               strpos($html, 'Дата подачи:') !== false;
    }
    
    /**
     * Извлечь только центральную часть из полного HTML документа
     */
    private function extractContentOnly(string $html): string
    {
        // Удаляем стандартный заголовок
        $content = preg_replace('/<h2>Заявление на материальную помощь<\/h2>.*?<hr>/s', '', $html);
        
        // Удаляем информацию о типе помощи
        $content = preg_replace('/<p><strong>Тип помощи:<\/strong>.*?<\/p>/s', '', $content);
        
        // Удаляем стандартное начало
        $content = preg_replace('/Я,\s*\{\{[^}]+\}\},\s*прошу предоставить мне материальную помощь\./s', '', $content);
        
        // Удаляем подпись и дату в конце
        $content = preg_replace('/<br><br>\s*<p>Дата подачи:.*?<p>Подпись:.*?<\/p>/s', '', $content);
        $content = preg_replace('/<p>Дата подачи:.*?<\/p>/s', '', $content);
        $content = preg_replace('/<p>Подпись:.*?<\/p>/s', '', $content);
        
        // Очищаем лишние пробелы и переносы
        $content = trim($content);
        
        // Если после очистки осталось мало контента, возвращаем шаблон по умолчанию
        if (strlen(strip_tags($content)) < 50) {
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
        
        return $content;
    }
};
