<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class TrainingRecordArrayExport implements FromArray, WithHeadings
{
    protected $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function array(): array
    {
        return $this->data;
    }

    public function headings(): array
    {
        return [
            'ФИО',
            'Тип обучения',
            'Номер сертификата',
            'Номер протокола',
            'Дата прохождения',
            'Дата окончания',
            'Осталось дней'
        ];
    }
}
