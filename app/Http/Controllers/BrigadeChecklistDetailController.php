<?php

namespace App\Http\Controllers;

use App\Models\BrigadeChecklistSession;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class BrigadeChecklistDetailController extends Controller
{
    /**
     * Показать детальную информацию о сессии чек-листа
     */
    public function show($id)
    {
        $session = BrigadeChecklistSession::with(['master.sotrudnik', 'brigade', 'responses.checklistItem'])
            ->findOrFail($id);

        return view('admin.brigade-checklist.detail', [
            'session' => $session,
            'responses' => $session->responses,
        ]);
    }

    /**
     * Экспорт сессии в PDF
     */
    public function exportPdf($id)
    {
        $session = BrigadeChecklistSession::with(['master.sotrudnik', 'brigade', 'responses.checklistItem'])
            ->findOrFail($id);

        $pdf = Pdf::loadView('pdf.brigade-checklist-session', [
            'session' => $session,
        ]);

        $filename = 'checklist_session_' . $session->id . '_' . date('Y-m-d_His') . '.pdf';

        return $pdf->download($filename);
    }
}
