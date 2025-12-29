<?php

namespace App\Orchid\Screens;

use App\Models\SafetyMemo;
use Illuminate\Http\Request;
use Orchid\Attachment\Models\Attachment;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\ModalToggle;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Fields\Switcher;
use Orchid\Screen\Fields\Upload;
use Orchid\Screen\Screen;
use Orchid\Screen\TD;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;

class SafetyMemoScreen extends Screen
{
    public function query(): iterable
    {
        return [
            'memos_kz' => SafetyMemo::where('lang', 'kz')->orderBy('sort')->paginate(15, ['*'], 'kz_page'),
            'memos_ru' => SafetyMemo::where('lang', 'ru')->orderBy('sort')->paginate(15, ['*'], 'ru_page'),
        ];
    }

    public function name(): ?string
    {
        return 'Памятки по тех. безопасности';
    }

    public function description(): ?string
    {
        return 'Управление памятками по технической безопасности';
    }

    public function commandBar(): iterable
    {
        return [
            ModalToggle::make('Добавить памятку')
                ->modal('memoModal')
                ->method('saveMemo')
                ->icon('plus'),
        ];
    }

    public function layout(): iterable
    {
        return [
            Layout::tabs([
                'на Казахском' => $this->returnTabTable('memos_kz'),
                'на Русском' => $this->returnTabTable('memos_ru'),
            ]),

            Layout::modal('memoModal', [
                Layout::rows([
                    Input::make('memo.id')->type('hidden'),
                    Input::make('memo.name')
                        ->title('Название')
                        ->required(),
                    Upload::make('memo.pdf_file_upload')
                        ->title('PDF файл')
                        ->acceptedFiles('.pdf')
                        ->maxFiles(1),
                    Select::make('memo.lang')
                        ->title('Язык')
                        ->options([
                            'kz' => 'Казахский',
                            'ru' => 'Русский',
                        ])
                        ->required(),
                    Input::make('memo.sort')
                        ->title('Сортировка')
                        ->type('number')
                        ->value(0),
                    Switcher::make('memo.status')
                        ->title('Активный')
                        ->value(true)
                        ->sendTrueOrFalse(),
                ]),
            ])
                ->title('Добавить/Редактировать памятку')
                ->async('asyncMemo'),
        ];
    }

    public function returnTabTable($target)
    {
        return Layout::table($target, [
            TD::make('id', 'ID')->width('50px'),
            TD::make('name', 'Название'),
            TD::make('pdf_file', 'PDF файл')->render(function (SafetyMemo $memo) {
                return "<a href='/storage/{$memo->pdf_file}' target='_blank'>Скачать PDF</a>";
            }),
            TD::make('sort', 'Сортировка')->width('100px'),
            TD::make('status', 'Статус')->render(function (SafetyMemo $memo) {
                return $memo->status ? '🟢 Активен' : '🔴 Неактивен';
            })->width('100px'),
            TD::make('actions', 'Действия')->render(function (SafetyMemo $memo) {
                return ModalToggle::make('Редактировать')
                    ->modal('memoModal')
                    ->method('saveMemo')
                    ->modalTitle('Редактировать памятку')
                    ->asyncParameters(['memo' => $memo->id])
                    . ' ' .
                    Button::make('Удалить')
                        ->method('deleteMemo')
                        ->parameters(['id' => $memo->id])
                        ->confirm('Вы уверены, что хотите удалить эту памятку?')
                        ->icon('trash');
            }),
        ]);
    }

    public function asyncMemo(SafetyMemo $memo): array
    {
        return [
            'memo' => $memo,
        ];
    }

    public function saveMemo(Request $request)
    {
        $data = $request->input('memo');

        $pdfFile = null;

        // Если загружен новый файл
        $attachmentIds = $data['pdf_file_upload'] ?? [];
        if (!empty($attachmentIds)) {
            $attachmentId = $attachmentIds[0];
            $attachment = Attachment::find($attachmentId);
            if ($attachment) {
                $pdfFile = $attachment->relativeUrl;
            }
        }

        // Если редактируем и файл не загружен - сохраняем старый
        if (empty($pdfFile) && !empty($data['id'])) {
            $existingMemo = SafetyMemo::find($data['id']);
            $pdfFile = $existingMemo?->pdf_file;
        }

        // Проверяем что pdf_file не пустой для новых записей
        if (empty($pdfFile) && empty($data['id'])) {
            Toast::error('Необходимо загрузить PDF файл.');
            return;
        }

        SafetyMemo::updateOrCreate(
            ['id' => $data['id'] ?? null],
            [
                'name' => $data['name'],
                'pdf_file' => $pdfFile,
                'lang' => $data['lang'] ?? 'ru',
                'status' => $data['status'] ?? true,
                'sort' => $data['sort'] ?? 0,
            ]
        );

        Toast::info('Памятка успешно сохранена.');
    }

    public function deleteMemo(Request $request)
    {
        SafetyMemo::findOrFail($request->input('id'))->delete();
        Toast::info('Памятка удалена!');
    }
}

