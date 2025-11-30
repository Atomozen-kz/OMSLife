<?php

namespace App\Http\Controllers\mobile;

use App\Http\Controllers\Controller;
use App\Models\GlobalPage;
use Illuminate\Http\Request;

class GlobalPageController extends Controller
{
    public function show($id)
    {
        $page = GlobalPage::find($id);
        if (!$page) {
            return response()->json(['error' => 'Page not found'], 404);
        }
        $response = [
            'id'   => $page->id,
            'name' => $page->{'name_' . app()->getLocale()} ?? null,
            'slug' => $page->slug ?? 'error',
            'body' => $page->{'body_' . app()->getLocale()} ?? null,
        ];
        return response()->json(['data' => $response ?? null]);
    }

    public function index(Request $request)
    {
        $lang = $request->input('lang');
        $langColumn = 'name_' . $lang;
        $pages = GlobalPage::select('id', $langColumn, 'slug')->get();

        $response = [];
        foreach ($pages as $page) {
            $response[] = [
                'name' => $page[$langColumn] ?? null,
                'id'   => $page['id'],
                'slug' => $page['slug'] ?? 'error',
            ];
        }
        return response()->json(['data' => $response]);
    }

}
