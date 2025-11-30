<?php

namespace App\Services;

use App\Models\OrganizationStructure;
use Illuminate\Support\Collection;

class StructureServices{
    public function getFirstParentStructure()
    {
        $organizations = OrganizationStructure::where('parent_id', '=', null)->get();

        $result = [
            'kz' => [],
            'ru' => []
        ];

        foreach ($organizations as $organization) {
            $result['kz'][] = [
                'id' => $organization->id,
                'name' => $organization->name_kz,
            ];
            $result['ru'][] = [
                'id' => $organization->id,
                'name' => $organization->name_ru,
            ];
        }

        return $result;
    }
}
