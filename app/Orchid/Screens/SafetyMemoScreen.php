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
            ModalToggle::make('Добавить памятку PDF')
                ->modal('memoPdfModal')
                ->method('savePdfMemo')
                ->icon('plus'),
            ModalToggle::make('Добавить памятку YouTube')
                ->modal('memoVideoModal')
                ->method('saveVideoMemo')
                ->icon('social-youtube'),
        ];
    }

    public function layout(): iterable
    {
        return [
            Layout::tabs([
                'на Казахском' => $this->returnTabTable('memos_kz'),
                'на Русском' => $this->returnTabTable('memos_ru'),
            ]),

            Layout::modal('memoPdfModal', [
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
                ->title('Добавить/Редактировать памятку PDF')
                ->async('asyncMemoPdf'),

            Layout::modal('memoVideoModal', [
                Layout::rows([
                    Input::make('memo.id')->type('hidden'),
                    Input::make('memo.name')
                        ->title('Название')
                        ->required(),
                    Input::make('memo.youtube_url')
                        ->title('Ссылка на YouTube')
                        ->placeholder('https://www.youtube.com/watch?v=...')
                        ->required(),
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
                ->title('Добавить/Редактировать памятку YouTube')
                ->async('asyncMemoVideo'),
        ];
    }

    public function returnTabTable($target)
    {
        return Layout::table($target, [
            TD::make('id', 'ID')->width('50px'),
            TD::make('name', 'Название'),
            TD::make('type', 'Тип')->render(function (SafetyMemo $memo) {
                return $memo->isPdf() ? '📄 PDF' : '🎬 YouTube';
            })->width('100px'),
            TD::make('url', 'Ссылка')->render(function (SafetyMemo $memo) {
                if ($memo->isPdf()) {
                    return "<a href='/storage/{$memo->url}' target='_blank'>Скачать PDF</a>";
                }
                return "<a href='{$memo->url}' target='_blank'>Открыть видео</a>";
            }),
            TD::make('sort', 'Сортировка')->width('100px'),
            TD::make('status', 'Статус')->render(function (SafetyMemo $memo) {
                return $memo->status ? '🟢 Активен' : '🔴 Неактивен';
            })->width('100px'),
            TD::make('actions', 'Действия')->render(function (SafetyMemo $memo) {
                $modalName = $memo->isPdf() ? 'memoPdfModal' : 'memoVideoModal';
                $methodName = $memo->isPdf() ? 'savePdfMemo' : 'saveVideoMemo';
                $asyncMethod = $memo->isPdf() ? 'asyncMemoPdf' : 'asyncMemoVideo';

                return ModalToggle::make('Редактировать')
                    ->modal($modalName)
                    ->method($methodName)
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

    public function asyncMemoPdf(SafetyMemo $memo): array
    {
        return [
            'memo' => $memo,
        ];
    }

    public function asyncMemoVideo(SafetyMemo $memo): array
    {
        return [
            'memo' => [
                'id' => $memo->id,
                'name' => $memo->name,
                'youtube_url' => $memo->url,
                'lang' => $memo->lang,
                'sort' => $memo->sort,
                'status' => $memo->status,
            ],
        ];
    }

    public function savePdfMemo(Request $request)
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
            $pdfFile = $existingMemo?->url;
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
                'url' => $pdfFile,
                'type' => SafetyMemo::TYPE_PDF,
                'lang' => $data['lang'] ?? 'ru',
                'status' => $data['status'] ?? true,
                'sort' => $data['sort'] ?? 0,
            ]
        );

        Toast::info('Памятка PDF успешно сохранена.');
    }

    public function saveVideoMemo(Request $request)
    {
        $data = $request->input('memo');

        $youtubeUrl = $data['youtube_url'] ?? null;

        // Если редактируем и URL не указан - сохраняем старый
        if (empty($youtubeUrl) && !empty($data['id'])) {
            $existingMemo = SafetyMemo::find($data['id']);
            $youtubeUrl = $existingMemo?->url;
        }

        // Проверяем что youtube_url не пустой для новых записей
        if (empty($youtubeUrl) && empty($data['id'])) {
            Toast::error('Необходимо указать ссылку на YouTube.');
            return;
        }

        SafetyMemo::updateOrCreate(
            ['id' => $data['id'] ?? null],
            [
                'name' => $data['name'],
                'url' => $youtubeUrl,
                'type' => SafetyMemo::TYPE_VIDEO,
                'lang' => $data['lang'] ?? 'ru',
                'status' => $data['status'] ?? true,
                'sort' => $data['sort'] ?? 0,
            ]
        );

        Toast::info('Памятка YouTube успешно сохранена.');
    }

    public function deleteMemo(Request $request)
    {
        SafetyMemo::findOrFail($request->input('id'))->delete();
        Toast::info('Памятка удалена!');
    }
}

