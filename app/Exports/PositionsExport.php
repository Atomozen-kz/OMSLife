<?php

namespace App\Exports;

use App\Models\Position;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;

class PositionsExport implements FromQuery, WithHeadings
{
    public function query()
    {
        // Выбираем только нужные столбцы id, name_kz:
        return Position::select('id','name_ru');
    }

    // Если хотите добавить заголовки в первые строку
    public function headings(): array
    {
        return [
            'id',
            'name_kz',
        ];
    }

    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        // Аналогично выбираем нужные поля
        return Position::select('id','name_ru')->get();
    }

}
