<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class SizInventoryTemplateExport implements FromArray, WithHeadings, WithStyles, WithColumnWidths
{
    /**
     * Возвращает данные для шаблона (примеры строк)
     */
    public function array(): array
    {
        return [
            ['Костюм нефтяника летний', 'Комплект', 3, null, 10, null, 3, null, null, 6, null, null, 1, 35, null, null, null, null, null, 4, null, 3, null, null, null, null, 1, null],
            ['Костюм нефтяника зимний', 'Комплект', null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null],
            ['Костюм ИТР летний', 'Комплект', null, null, 5, null, null, 10, null, 6, 5, null, 5, null, 5, 3, 4, null, null, 8, 3, 3, null, null, null, null, null, null],
            ['Костюм ИТР зимний', 'Комплект', null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null],
            ['Комбинезон летний', 'Комплект', null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null],
        ];
    }

    /**
     * Заголовки столбцов (размеры из скриншота)
     */
    public function headings(): array
    {
        return [
            'Вид СИЗ',
            'ЕИ',
            '42/2',
            '42/3',
            '44/2',
            '44/3',
            '46/2',
            '46/3',
            '46/4',
            '48/2',
            '48/3',
            '48/4',
            '50/2',
            '50/3',
            '50/4',
            '52/3',
            '52/4',
            '54/3',
            '54/4',
            '56/3',
            '56/4',
            '58/3',
            '58/4',
            '60/3',
            '60/4',
            '62/4',
            '64/4',
            '66/4',
        ];
    }

    /**
     * Применяем стили к ячейкам
     */
    public function styles(Worksheet $sheet)
    {
        // Стилизация заголовка (строка 1)
        $sheet->getStyle('A1:AB1')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 11,
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'D3D3D3'],
            ],
        ]);

        // Применяем цвета к столбцам с размерами (аналогично скриншоту)
        // Желтые столбцы
        $sheet->getStyle('C1:C100')->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFFF00']],
        ]);

        // Зеленые столбцы
        $sheet->getStyle('D1:D100')->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '92D050']],
        ]);

        // Светло-зеленые столбцы
        $sheet->getStyle('E1:E100')->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'C6E0B4']],
        ]);

        // Голубые столбцы
        $sheet->getStyle('F1:G100')->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '00B0F0']],
        ]);

        $sheet->getStyle('H1:H100')->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '00B0F0']],
        ]);

        // Розовые столбцы
        $sheet->getStyle('I1:K100')->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FF00FF']],
        ]);

        // Оранжевые столбцы
        $sheet->getStyle('L1:N100')->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFC000']],
        ]);

        // Светло-желтые столбцы
        $sheet->getStyle('O1:P100')->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFFF99']],
        ]);

        // Светло-голубые столбцы
        $sheet->getStyle('Q1:R100')->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'B4C7E7']],
        ]);

        // Светло-оранжевые столбцы
        $sheet->getStyle('S1:T100')->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F4B084']],
        ]);

        // Зеленые столбцы (яркие)
        $sheet->getStyle('U1:V100')->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '00FF00']],
        ]);

        // Синие столбцы
        $sheet->getStyle('W1:X100')->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0070C0']],
        ]);

        // Желтые столбцы
        $sheet->getStyle('Y1:Y100')->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFFF00']],
        ]);

        // Серые столбцы
        $sheet->getStyle('Z1:AB100')->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'A6A6A6']],
        ]);

        // Выравнивание по центру для всех ячеек
        $sheet->getStyle('A1:AB100')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A1:AB100')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

        return [];
    }

    /**
     * Устанавливаем ширину столбцов
     */
    public function columnWidths(): array
    {
        return [
            'A' => 30, // Вид СИЗ
            'B' => 15, // ЕИ
            'C' => 8,  // Размеры
            'D' => 8,
            'E' => 8,
            'F' => 8,
            'G' => 8,
            'H' => 8,
            'I' => 8,
            'J' => 8,
            'K' => 8,
            'L' => 8,
            'M' => 8,
            'N' => 8,
            'O' => 8,
            'P' => 8,
            'Q' => 8,
            'R' => 8,
            'S' => 8,
            'T' => 8,
            'U' => 8,
            'V' => 8,
            'W' => 8,
            'X' => 8,
            'Y' => 8,
            'Z' => 8,
            'AA' => 8,
            'AB' => 8,
        ];
    }
}
