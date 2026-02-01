<?php

namespace App\Orchid\Screens;

use App\Models\SizType;
use App\Models\SizInventory;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\ModalToggle;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Screen;
use Orchid\Screen\TD;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;
use App\Imports\SizInventoryImport;
use App\Exports\SizInventoryTemplateExport;

class SizInventoryScreen extends Screen
{
    /**
     * Fetch data to be displayed on the screen.
     */
    public function query(): iterable
    {
        return [
            'inventory' => SizInventory::with('sizType')->paginate(20),
            'sizTypes' => SizType::all()->pluck('name_ru', 'id'),
        ];
    }

    /**
     * The name of the screen displayed in the header.
     */
    public function name(): ?string
    {
        return 'Наличие СИЗ';
    }

    /**
     * The screen's description.
     */
    public function description(): ?string
    {
        return 'Управление наличием средств индивидуальной защиты';
    }

    /**
     * The screen's action buttons.
     */
    public function commandBar(): iterable
    {
        return [
            ModalToggle::make('Добавить наличие')
                ->modal('createInventoryModal')
                ->method('createOrUpdateInventory')
                ->icon('plus'),

            ModalToggle::make('Импорт Excel')
                ->modal('importModal')
                ->method('importFromExcel')
                ->icon('cloud-upload')
                ->class('btn btn-success'),

            Button::make('Скачать шаблон Excel')
                ->method('exportTemplate')
                ->icon('download')
                ->class('btn btn-info'),
        ];
    }

    /**
     * The screen's layout elements.
     */
    public function layout(): iterable
    {
        return [
            Layout::table('inventory', [
                TD::make('sizType.name_ru', 'Вид СИЗ')
                    ->sort()
                    ->filter(Input::make()),

                TD::make('size', 'Размер')
                    ->sort()
                    ->filter(Input::make()),

                TD::make('quantity', 'Количество')
                    ->sort()
                    ->render(function (SizInventory $inventory) {
                        return $inventory->quantity;
                    }),

                TD::make('sizType.unit_ru', 'Ед. измерения'),

                TD::make('Действия')
                    ->align(TD::ALIGN_CENTER)
                    ->width('150px')
                    ->render(function (SizInventory $inventory) {
                        return ModalToggle::make('Редактировать')
                            ->modal('editInventoryModal')
                            ->method('createOrUpdateInventory')
                            ->modalTitle('Редактирование наличия')
                            ->asyncParameters(['inventory' => $inventory->id])
                            ->icon('pencil');
                    }),

                TD::make('', '')
                    ->align(TD::ALIGN_CENTER)
                    ->width('100px')
                    ->render(function (SizInventory $inventory) {
                        return Button::make('Удалить')
                            ->method('deleteInventory')
                            ->parameters(['id' => $inventory->id])
                            ->icon('trash')
                            ->confirm('Вы уверены, что хотите удалить эту запись?');
                    }),
            ]),

            // Модальное окно для создания
            Layout::modal('createInventoryModal', [
                Layout::rows([
                    Select::make('inventory.siz_type_id')
                        ->title('Вид СИЗ')
                        ->fromModel(SizType::class, 'name_ru', 'id')
                        ->required()
                        ->help('Выберите вид СИЗ'),

                    Input::make('inventory.size')
                        ->title('Размер')
                        ->placeholder('42/2, 44/3, XL и т.д.')
                        ->required()
                        ->help('Введите размер (например: 42/2, 44/3, S, M, L)'),

                    Input::make('inventory.quantity')
                        ->title('Количество')
                        ->type('number')
                        ->value(0)
                        ->required(),
                ]),
            ])
                ->title('Добавить наличие')
                ->applyButton('Сохранить')
                ->closeButton('Отмена'),

            // Модальное окно для редактирования
            Layout::modal('editInventoryModal', [
                Layout::rows([
                    Input::make('inventory.id')->type('hidden'),

                    Select::make('inventory.siz_type_id')
                        ->title('Вид СИЗ')
                        ->fromModel(SizType::class, 'name_ru', 'id')
                        ->required(),

                    Input::make('inventory.size')
                        ->title('Размер')
                        ->required()
                        ->disabled()
                        ->help('Размер нельзя изменить. Удалите и создайте новую запись.'),

                    Input::make('inventory.quantity')
                        ->title('Количество')
                        ->type('number')
                        ->required(),
                ]),
            ])
                ->async('asyncGetInventory')
                ->title('Редактирование наличия')
                ->applyButton('Сохранить')
                ->closeButton('Отмена'),

            // Модальное окно для импорта
            Layout::modal('importModal', [
                Layout::rows([
                    Select::make('import_mode')
                        ->title('Режим импорта')
                        ->options([
                            'replace' => 'Полная замена (удалить существующие данные и загрузить новые)',
                            'update' => 'Обновление количества (суммировать с существующими)',
                        ])
                        ->required()
                        ->help('Выберите режим импорта данных'),

                    Input::make('file')
                        ->type('file')
                        ->title('Выберите файл Excel')
                        ->accept('.xlsx, .xls')
                        ->required()
                        ->help('Загрузите файл в формате Excel (.xlsx или .xls)'),
                ]),
            ])
                ->title('Импорт данных из Excel')
                ->applyButton('Импортировать')
                ->closeButton('Отмена'),
        ];
    }

    /**
     * Асинхронное получение данных для редактирования
     */
    public function asyncGetInventory(SizInventory $inventory): array
    {
        return [
            'inventory' => $inventory,
        ];
    }

    /**
     * Создание или обновление наличия
     */
    public function createOrUpdateInventory(Request $request): void
    {
        $request->validate([
            'inventory.siz_type_id' => 'required|exists:siz_types,id',
            'inventory.size' => 'required|string|max:255',
            'inventory.quantity' => 'required|integer|min:0',
        ]);

        $data = $request->input('inventory');

        // Если это обновление (есть id), не проверяем уникальность
        if (isset($data['id'])) {
            $inventory = SizInventory::findOrFail($data['id']);
            $inventory->update([
                'quantity' => $data['quantity'],
            ]);
        } else {
            // При создании проверяем уникальность комбинации siz_type_id + size
            $exists = SizInventory::where('siz_type_id', $data['siz_type_id'])
                ->where('size', $data['size'])
                ->exists();

            if ($exists) {
                Toast::error('Запись с таким видом СИЗ и размером уже существует!');
                return;
            }

            SizInventory::create($data);
        }

        Toast::info('Наличие СИЗ успешно сохранено');
    }

    /**
     * Удаление наличия
     */
    public function deleteInventory(Request $request): void
    {
        SizInventory::findOrFail($request->get('id'))->delete();

        Toast::info('Запись удалена');
    }

    /**
     * Импорт данных из Excel
     */
    public function importFromExcel(Request $request): void
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls',
            'import_mode' => 'required|in:replace,update',
        ]);

        $file = $request->file('file');
        $mode = $request->input('import_mode');

        if (!$file) {
            Toast::error('Файл не загружен');
            return;
        }

        try {
            ini_set('memory_limit', '512M');
            set_time_limit(300);

            $import = new SizInventoryImport($mode);
            Excel::import($import, $file);

            $errors = $import->getErrors();

            if (count($errors) > 0) {
                // Логируем ошибки в файл
                $logPath = storage_path('logs/siz_import_errors_' . now()->format('Y_m_d_H_i_s') . '.txt');
                $logContent = "=== Ошибки импорта СИЗ ===\n";
                $logContent .= "Дата: " . now()->format('d.m.Y H:i:s') . "\n";
                $logContent .= "Режим: " . ($mode === 'replace' ? 'Полная замена' : 'Обновление количества') . "\n";
                $logContent .= "Обработано строк: " . $import->getProcessedCount() . "\n";
                $logContent .= "Ошибок: " . count($errors) . "\n\n";

                foreach ($errors as $error) {
                    $logContent .= "Строка {$error['row']}: {$error['error']}\n";
                }

                file_put_contents($logPath, $logContent);

                // Формируем сообщение для пользователя с первыми 3 ошибками
                $errorMessage = 'Импорт завершен с ошибками. Обработано строк: ' . $import->getProcessedCount() . '. Ошибок: ' . count($errors) . "\n\n";
                $errorMessage .= "Первые ошибки:\n";

                $displayErrors = array_slice($errors, 0, 3);
                foreach ($displayErrors as $error) {
                    $errorMessage .= "• Строка {$error['row']}: {$error['error']}\n";
                }

                if (count($errors) > 3) {
                    $errorMessage .= "\n... и еще " . (count($errors) - 3) . " ошибок.";
                }

                $errorMessage .= "\n\nПолный лог ошибок: storage/logs/siz_import_errors_" . now()->format('Y_m_d_H_i_s') . ".txt";

                Toast::warning($errorMessage);
            } else {
                Toast::success('Импорт успешно завершен! Обработано строк: ' . $import->getProcessedCount());
            }
        } catch (\Exception $e) {
            Toast::error('Ошибка импорта: ' . $e->getMessage());
        }
    }

    /**
     * Экспорт шаблона Excel
     */
    public function exportTemplate()
    {
        return Excel::download(new SizInventoryTemplateExport, 'shablon_siz.xlsx');
    }
}
