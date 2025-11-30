<?php

namespace App\Imports;

use App\Models\Position;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

class PositionsImport implements ToCollection
{
    public function collection(Collection $rows)
    {
        // Пропускаем заголовок, если у вас в 1-й строке именно заголовки
        $rows->skip(1)->each(function ($row) {
            Position::where('id', $row[0])
                ->update([
                    'name_kz' => $row[1],
                ]);
        });
    }
}
