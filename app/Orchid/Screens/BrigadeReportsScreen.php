<?php

namespace App\Orchid\Screens;

use App\Models\BrigadeReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\ModalToggle;
use Orchid\Screen\Fields\DateTimer;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Screen;
use Orchid\Screen\TD;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;

class BrigadeReportsScreen extends Screen
{
    /**
     * Display header name.
     *
     * @var string
     */
    public $name = 'Сводки по бригадам';

    /**
     * Display header description.
     *
     * @var string
     */
    public $description = 'Управление сводками по бригадам';

    /**
     * Query data.
     *
     * @return array
     */
    public function query(): iterable
    {
        return [
            'reports' => BrigadeReport::orderBy('date', 'desc')->paginate(15),
        ];
    }

    /**
     * Button commands.
     *
     * @return \Orchid\Screen\Action[]
     */
    public function commandBar(): iterable
    {
        return [
            ModalToggle::make('Добавить сводку')
                ->modal('createOrUpdateReportModal')
                ->modalTitle('Добавить сводку')
                ->method('createOrUpdateReport')
                ->icon('plus'),
        ];
    }

    /**
     * Views.
     *
     * @return \Orchid\Screen\Layout[]
     */
    public function layout(): iterable
    {
        return [
            Layout::table('reports', [
                TD::make('date', 'Дата')
                    ->render(function (BrigadeReport $report) {
                        return $report->date->format('d.m.Y');
                    })
                    ->sort(),

                TD::make('file', 'Файл')
                    ->render(function (BrigadeReport $report) {
                        if ($report->file) {
                            return '<a href="' . url($report->file) . '" target="_blank">Скачать</a>';
                        }
                        return '-';
                    }),

                TD::make('created_at', 'Дата создания')
                    ->render(function (BrigadeReport $report) {
                        return $report->created_at->format('d.m.Y H:i');
                    })
                    ->sort(),

                TD::make('Действия')
                    ->align(TD::ALIGN_CENTER)
                    ->width('150px')
                    ->render(function (BrigadeReport $report) {
                        return
                            ModalToggle::make('Редактировать')
                                ->modal('createOrUpdateReportModal')
                                ->modalTitle('Редактировать сводку')
                                ->method('createOrUpdateReport')
                                ->asyncParameters(['report' => $report->id])
                                ->icon('pencil')
                            . ' ' .
                            Button::make('Удалить')
                                ->method('deleteReport')
                                ->confirm('Вы действительно хотите удалить эту сводку?')
                                ->parameters(['id' => $report->id])
                                ->icon('trash');
                    }),
            ]),

            Layout::modal('createOrUpdateReportModal', [
                Layout::rows([
                    Input::make('report.id')->type('hidden'),

                    DateTimer::make('report.date')
                        ->title('Дата сводки')
                        ->format('Y-m-d')
                        ->required()
                        ->help('Выберите дату, на которую составлена сводка'),

                    Input::make('report.file')
                        ->type('file')
                        ->title('Файл сводки (Word документ)')
                        ->accept('.doc,.docx')
                        ->help('Максимальный размер файла: 25 МБ. Форматы: .doc, .docx'),
                ]),
            ])
                ->async('asyncGetReport')
                ->applyButton('Сохранить')
                ->closeButton('Отмена'),
        ];
    }

    /**
     * Асинхронная загрузка данных сводки для редактирования
     *
     * @param BrigadeReport $report
     * @return array
     */
    public function asyncGetReport(BrigadeReport $report): array
    {
        return [
            'report' => $report,
        ];
    }

    /**
     * Создать или обновить сводку
     *
     * @param Request $request
     * @return void
     */
    public function createOrUpdateReport(Request $request): void
    {
        $validated = $request->validate([
            'report.id' => 'nullable|integer',
            'report.date' => 'required|date',
            'report.file' => 'nullable|file|mimes:doc,docx|max:25600', // 25 MB в KB
        ]);

        $data = $validated['report'];
        $reportId = $data['id'] ?? null;

        // Находим или создаём новую запись
        $report = $reportId ? BrigadeReport::find($reportId) : new BrigadeReport();

        if (!$report && $reportId) {
            Toast::error('Сводка не найдена.');
            return;
        }

        // Устанавливаем дату
        $report->date = $data['date'];

        // Обрабатываем файл, если он загружен
        if ($request->hasFile('report.file')) {
            // Удаляем старый файл, если он существует
            if ($report->file) {
                $oldFilePath = str_replace('storage/', '', $report->file);
                if (Storage::disk('public')->exists($oldFilePath)) {
                    Storage::disk('public')->delete($oldFilePath);
                }
            }

            // Получаем загруженный файл
            $uploadedFile = $request->file('report.file');

             // Создаём безопасное имя файла: "brigade_report_{date}_{timestamp}.{extension}"
            $date = \Carbon\Carbon::parse($data['date'])->format('Y-m-d');
            $timestamp = time();
            $extension = $uploadedFile->getClientOriginalExtension();
            $fileName = "brigade_report_{$date}_{$timestamp}.{$extension}";

            // Сохраняем файл
            $filePath = $uploadedFile->storeAs('brigade_reports', $fileName, 'public');
            $report->file = 'storage/' . $filePath;
        }

        $report->save();

        Toast::info('Сводка успешно сохранена.');
    }

    /**
     * Удалить сводку
     *
     * @param int $id
     * @return void
     */
    public function deleteReport(int $id): void
    {
        $report = BrigadeReport::find($id);

        if ($report) {
            // Удаляем файл, если он существует
            if ($report->file) {
                $filePath = str_replace('storage/', '', $report->file);
                if (Storage::disk('public')->exists($filePath)) {
                    Storage::disk('public')->delete($filePath);
                }
            }

            // Удаляем запись
            $report->delete();

            Toast::info('Сводка успешно удалена.');
        } else {
            Toast::error('Сводка не найдена.');
        }
    }
}
