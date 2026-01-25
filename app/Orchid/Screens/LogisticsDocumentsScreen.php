<?php

namespace App\Orchid\Screens;

use App\Models\LogisticsDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\ModalToggle;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Screen;
use Orchid\Screen\TD;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;

class LogisticsDocumentsScreen extends Screen
{
    /**
     * Display header name.
     *
     * @var string
     */
    public $name = 'Логистика и МТС';

    /**
     * Display header description.
     *
     * @var string
     */
    public $description = 'Управление документами логистики и материально-технического снабжения';

    /**
     * Query data.
     *
     * @return array
     */
    public function query(): iterable
    {
        return [
            'logistics_ru' => LogisticsDocument::where('lang', 'ru')
                ->orderBy('created_at', 'desc')
                ->paginate(15),
            'logistics_kz' => LogisticsDocument::where('lang', 'kz')
                ->orderBy('created_at', 'desc')
                ->paginate(15),
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
            ModalToggle::make('Добавить документ')
                ->modal('createOrUpdateDocumentModal')
                ->modalTitle('Добавить документ')
                ->method('createOrUpdateDocument')
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
            Layout::tabs([
                'Русский' => $this->tableLogisticsLang('logistics_ru'),
                'Казахский' => $this->tableLogisticsLang('logistics_kz'),
            ]),

            Layout::modal('createOrUpdateDocumentModal', [
                Layout::rows([
                    Input::make('document.id')->type('hidden'),

                    Input::make('document.name')
                        ->title('Название документа')
                        ->required()
                        ->placeholder('Введите название документа')
                        ->help('Название документа'),

                    Select::make('document.lang')
                        ->title('Язык')
                        ->options([
                            'ru' => 'Русский',
                            'kz' => 'Казахский',
                        ])
                        ->required()
                        ->help('Выберите язык документа'),

                    Select::make('document.type')
                        ->title('Тип файла')
                        ->options([
                            'excel' => 'Excel',
                            'word' => 'Word',
                            'pdf' => 'PDF',
                        ])
                        ->required()
                        ->help('Выберите тип файла'),

                    Input::make('document.file')
                        ->type('file')
                        ->title('Файл документа')
                        ->help('Максимальный размер файла: 25 МБ'),
                ]),
            ])
                ->async('asyncGetDocument')
                ->applyButton('Сохранить')
                ->closeButton('Отмена'),
        ];
    }

    /**
     * Метод для создания таблицы документов по языку
     */
    public function tableLogisticsLang($target)
    {
        return Layout::table($target, [
            TD::make('name', 'Название')
                ->render(function (LogisticsDocument $document) {
                    return "<strong>{$document->name}</strong>";
                })
                ->width('300px'),

            TD::make('type', 'Тип')
                ->render(function (LogisticsDocument $document) {
                    $typeIcon = $document->type_icon;
                    $typeNames = [
                        'excel' => 'Excel',
                        'word' => 'Word',
                        'pdf' => 'PDF',
                    ];
                    $typeName = $typeNames[$document->type] ?? $document->type;

                    return "<span style='color: {$typeIcon['color']}; font-weight: bold;'>
                                <i class='{$typeIcon['icon']}'></i> {$typeName}
                            </span>";
                })
                ->width('120px'),

            TD::make('file', 'Файл')
                ->render(function (LogisticsDocument $document) {
                    if ($document->file) {
                        return '<a href="' . url($document->file) . '" target="_blank">Скачать</a>';
                    }
                    return '-';
                })
                ->width('100px'),

            TD::make('created_at', 'Дата создания')
                ->render(function (LogisticsDocument $document) {
                    return $document->created_at->format('d.m.Y H:i');
                })
                ->width('150px'),

            TD::make('Действия')
                ->align(TD::ALIGN_CENTER)
                ->width('150px')
                ->render(function (LogisticsDocument $document) {
                    return
                        ModalToggle::make('Редактировать')
                            ->modal('createOrUpdateDocumentModal')
                            ->modalTitle('Редактировать документ')
                            ->method('createOrUpdateDocument')
                            ->asyncParameters(['document' => $document->id])
                            ->icon('pencil')
                        . ' ' .
                        Button::make('Удалить')
                            ->method('deleteDocument')
                            ->confirm('Вы действительно хотите удалить этот документ?')
                            ->parameters(['id' => $document->id])
                            ->icon('trash');
                }),
        ]);
    }

    /**
     * Асинхронная загрузка данных документа для редактирования
     *
     * @param LogisticsDocument $document
     * @return array
     */
    public function asyncGetDocument(LogisticsDocument $document): array
    {
        return [
            'document' => $document,
        ];
    }

    /**
     * Создать или обновить документ
     *
     * @param Request $request
     * @return void
     */
    public function createOrUpdateDocument(Request $request): void
    {
        $validated = $request->validate([
            'document.id' => 'nullable|integer',
            'document.name' => 'required|string|max:255',
            'document.lang' => 'required|in:ru,kz',
            'document.type' => 'required|in:excel,word,pdf',
            'document.file' => 'nullable|file|max:25600', // 25 MB в KB
        ]);

        $data = $validated['document'];
        $documentId = $data['id'] ?? null;

        // Находим или создаём новую запись
        $document = $documentId ? LogisticsDocument::find($documentId) : new LogisticsDocument();

        if (!$document && $documentId) {
            Toast::error('Документ не найден.');
            return;
        }

        // Устанавливаем основные поля
        $document->name = $data['name'];
        $document->lang = $data['lang'];
        $document->type = $data['type'];

        // Обрабатываем файл, если он загружен
        if ($request->hasFile('document.file')) {
            // Валидация MIME-типов в зависимости от типа документа
            $mimeTypes = [
                'excel' => 'mimes:xlsx,xls',
                'word' => 'mimes:doc,docx',
                'pdf' => 'mimes:pdf',
            ];

            $request->validate([
                'document.file' => $mimeTypes[$data['type']],
            ], [
                'document.file.mimes' => 'Файл должен соответствовать выбранному типу документа.',
            ]);

            // Удаляем старый файл, если он существует
            if ($document->file) {
                $oldFilePath = str_replace('storage/', '', $document->file);
                if (Storage::disk('public')->exists($oldFilePath)) {
                    Storage::disk('public')->delete($oldFilePath);
                }
            }

            // Получаем загруженный файл
            $uploadedFile = $request->file('document.file');

            // Создаём имя файла: "{name}_{timestamp}.{extension}"
            $timestamp = time();
            $extension = $uploadedFile->getClientOriginalExtension();
            $fileName = "{$data['name']}_{$timestamp}.{$extension}";

            // Сохраняем файл
            $filePath = $uploadedFile->storeAs('logistics_documents', $fileName, 'public');
            $document->file = 'storage/' . $filePath;
        }

        $document->save();

        Toast::info('Документ успешно сохранен.');
    }

    /**
     * Удалить документ
     *
     * @param int $id
     * @return void
     */
    public function deleteDocument(int $id): void
    {
        $document = LogisticsDocument::find($id);

        if ($document) {
            // Удаляем файл, если он существует
            if ($document->file) {
                $filePath = str_replace('storage/', '', $document->file);
                if (Storage::disk('public')->exists($filePath)) {
                    Storage::disk('public')->delete($filePath);
                }
            }

            // Удаляем запись
            $document->delete();

            Toast::info('Документ успешно удален.');
        } else {
            Toast::error('Документ не найден.');
        }
    }
}
