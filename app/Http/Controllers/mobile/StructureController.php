<?php

namespace App\Http\Controllers\mobile;

use App\Http\Controllers\Controller;
use App\Models\OrganizationStructure;
use App\Services\StructureServices;
use Illuminate\Http\Request;

class StructureController extends Controller
{
    public function getFirstParentStructure(StructureServices $structureServices)
    {
        return response()->json($structureServices->getFirstParentStructure());
    }
}
