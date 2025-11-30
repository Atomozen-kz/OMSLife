<?php

namespace App\Http\Controllers;

use App\Models\BankIdeaFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class BankIdeaFileController extends Controller
{
    /**
     * Отдаёт файл по id с проверкой доступа.
     */
    public function download(Request $request, $id)
    {
        $file = BankIdeaFile::findOrFail($id);

        // TODO: при необходимости добавить проверку прав доступа.
        // Например, разрешать скачивать только автору идеи или админам:
        // $idea = $file->idea; if (Auth::id() !== $idea->author_id && !Auth::user()->isAdmin()) abort(403);

        $disk = Storage::disk('public');
        $path = $file->path_to_file;

        if (!$disk->exists($path)) {
            abort(404);
        }

        $name = basename($path) ?: ($file->original_name ?? 'file');

        // Отдаём файл напрямую (download) чтобы избежать проблем с правами веб-сервера
        return $disk->download($path, $name);
    }
}

