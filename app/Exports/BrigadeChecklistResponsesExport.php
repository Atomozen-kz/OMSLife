<?php

namespace App\Exports;

use App\Models\BrigadeChecklistSession;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class BrigadeChecklistResponsesExport implements FromCollection, WithHeadings, WithMapping, WithColumnWidths, WithStyles
{
    protected $sessions;

    public function __construct(Collection $sessions)
    {
        $this->sessions = $sessions;
    }

    /**
     * @return Collection
     */
    public function collection()
    {
        // Разворачиваем sessions в отдельные строки для каждого ответа
        $rows = collect();

        foreach ($this->sessions as $session) {
            foreach ($session->responses as $response) {
                $rows->push([
                    'session' => $session,
                    'response' => $response,
                ]);
            }
        }

        return $rows;
    }

    /**
     * Заголовки таблицы
     */
    public function headings(): array
    {
        return [
            'Дата и время',
            'Мастер (ФИО)',
            'Бригада',
            'Номер скважины',
            'ТК',
            'Мероприятие',
            'Тип ответа',
            'Комментарий',
        ];
    }

    /**
     * Маппинг данных для строк
     */
    public function map($row): array
    {
        $session = $row['session'];
        $response = $row['response'];

        return [
            $session->completed_at?->format('d.m.Y H:i') ?? '',
            $session->full_name_master ?? '',
            $session->brigade_name ?? '',
            $session->well_number ?? '',
            $session->tk ?? '',
            $response->checklistItem->event_name ?? '',
            $this->getResponseTypeName($response->response_type),
            $response->response_text ?? '',
        ];
    }

    /**
     * Ширина колонок
     */
    public function columnWidths(): array
    {
        return [
            'A' => 18,  // Дата и время
            'B' => 30,  // Мастер
            'C' => 20,  // Бригада
            'D' => 15,  // Скважина
            'E' => 12,  // ТК
            'F' => 40,  // Мероприятие
            'G' => 15,  // Тип ответа
            'H' => 50,  // Комментарий
        ];
    }

    /**
     * Стили для таблицы
     */
    public function styles(Worksheet $sheet)
    {
        return [
            // Заголовок
            1 => [
                'font' => [
                    'bold' => true,
                    'size' => 12,
                ],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E8F4F8'],
                ],
            ],
        ];
    }

    /**
     * Получить читаемое название типа ответа
     */
    protected function getResponseTypeName(string $type): string
    {
        $types = [
            'dangerous' => 'Опасно',
            'safe' => 'Безопасно',
            'other' => 'Другое',
        ];

        return $types[$type] ?? $type;
    }
}
