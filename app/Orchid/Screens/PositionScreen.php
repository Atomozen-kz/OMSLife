<?php

namespace App\Orchid\Screens;

use App\Imports\PositionsImport;
use App\Models\Position;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use League\Csv\Reader;
use Maatwebsite\Excel\Facades\Excel;
use Orchid\Attachment\Models\Attachment;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\ModalToggle;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Upload;
use Orchid\Screen\Screen;
use Orchid\Screen\TD;
use Orchid\Support\Facades\Alert;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;

class PositionScreen extends Screen
{
    /**
     * Fetch data to be displayed on the screen.
     *
     * @return array
     */
    public function query(): iterable
    {
        return [
            'positions' => Position::paginate(),
            ];
    }

    /**
     * The name of the screen displayed in the header.
     *
     * @return string|null
     */
    public function name(): ?string
    {
        return 'Управление должностями';
    }

    /**
     * The screen's action buttons.
     *
     * @return \Orchid\Screen\Action[]
     */
    public function commandBar(): iterable
    {
        return [
            ModalToggle::make('Добавить должность')
                ->modal('createPositionModal')
                ->method('createOrUpdatePosition')
                ->icon('plus'),
            ModalToggle::make('Импорт из Excel перевод на казахский')
                ->modal('importPositions')
                ->method('importExcel')
                ->icon('cloud-upload'),
//            ModalToggle::make('Импортировать CSV')
//                ->modal('importCsvModal')
//                ->method('importCsv'),
        ];
    }

    /**
     * The screen's layout elements.
     *
     * @return \Orchid\Screen\Layout[]|string[]
     */
    public function layout(): iterable
    {
        return [
        // Таблица для отображения должностей
        Layout::table('positions', [
            TD::make('name_ru', 'Название на русском')
                ->sort()
                ->filter(Input::make()),

            TD::make('name_kz', 'Название на казахском')
                ->sort()
                ->filter(Input::make()),

            TD::make('Действия')
                ->render(function (Position $position) {
                    return ModalToggle::make('Редактировать')
                        ->modal('editPositionModal')
                        ->method('createOrUpdatePosition')
                        ->modalTitle('Редактирование должности')
                        ->asyncParameters(['position' => $position->id])
                        ->icon('note')
                        ->confirm('Вы уверены, что хотите редактировать эту запись?');
                }),

            TD::make('')
                ->render(function (Position $position) {
                    return Button::make('Удалить')
                        ->method('deletePosition')
                        ->parameters(['id' => $position->id])
                        ->icon('trash')
                        ->confirm('Вы уверены, что хотите удалить эту должность?');
                }),
        ]),
        // Модальное окно для создания и редактирования
        Layout::modal('createPositionModal', [
            Layout::rows([
                Input::make('position.name_ru')
                    ->title('Название на русском')
                    ->required(),
                Input::make('position.name_kz')
                    ->title('Название на казахском')
                    ->required(),
            ]),
        ])->title('Добавить должность')->applyButton('Сохранить')->closeButton('Отмена'),

        Layout::modal('editPositionModal', [
            Layout::rows([
                Input::make('position.id')->type('hidden'),
                Input::make('position.name_ru')
                    ->title('Название на русском')
                    ->required(),
                Input::make('position.name_kz')
                    ->title('Название на казахском')
                    ->required(),
            ]),
        ])->async('asyncGetPosition')
            ->title('Редактирование должности')
            ->applyButton('Сохранить')
            ->closeButton('Отмена'),

        Layout::modal('importCsvModal', [
            Layout::rows([
                Input::make('csv_file')
                    ->type('file')
                    ->title('Выберите файл')
                    ->required(),
            ]),
        ])->title('Импорт CSV')->applyButton('Импортировать'),

            // Модальное окно c Upload-полем
            Layout::modal('importPositions', [
                Layout::rows([
                    Input::make('excel_file')
                        ->type('file')
                        ->title('Выберите Excel файл')
                        ->acceptedFiles('.xlsx, .xls, .csv')
                        ->help('Выберите файл для обновления/импорта записей')
                ]),
            ])
                ->title('Импорт данных')
                ->applyButton('Импортировать')  // Текст на кнопке "Применить"
                ->size('modal-lg'),            // Размер модального окна (необязательно)

            ];
    }
    /**
     * Асинхронное получение данных для редактирования
     */
    public function asyncGetPosition(Position $position): array
    {
        return [
            'position' => $position,
        ];
    }


    public function importCsv(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt',
        ]);

        $file = $request->file('csv_file');
        $path = $file->getRealPath();

        // Чтение файла с League\Csv
        $csv = Reader::createFromPath($path, 'r');
        $csv->setHeaderOffset(0); // Установка первой строки как заголовков

        $records = $csv->getRecords(); // Получение записей в виде ассоциативного массива

        foreach ($records as $row) {
            // Убедитесь, что данные есть и корректны
            if (!isset($row['id'], $row['name_ru'], $row['name_kz'])) {
                continue; // Пропускаем строки с отсутствующими колонками
            }

            Position::updateOrCreate(
                ['id' => trim($row['id'])], // Условие поиска по id
                [
                    'name_ru' => trim($row['name_ru']),
                    'name_kz' => trim($row['name_kz']),
                ] // Обновляемые значения
            );
        }

        Toast::info('Импорт завершен.');
    }

    /**
     * Метод, который вызывается при нажатии на кнопку "Импортировать" в модальном окне
     */
    public function importExcel(Request $request)
    {
        // Массив ID загруженных вложений
        $file = $request->file('excel_file');

        if (!$file) {
            Alert::error('Файл не выбран!');
            return;
        }
        Excel::import(new PositionsImport, $file);

        Alert::info('Импорт успешно завершён!');
//        return redirect()->route('platform.positions');
    }

    /**
     * Метод для создания и обновления должности
     */
    public function createOrUpdatePosition(Request $request): void
    {
        $request->validate([
            'position.name_ru' => 'required|string',
            'position.name_kz' => 'required|string',
        ]);

        Position::updateOrCreate(
            ['id' => $request->input('position.id')],
            $request->input('position')
        );

        Toast::info('Должность успешно сохранена');
    }

    /**
     * Метод для удаления должности
     */
    public function deletePosition(Request $request): void
    {
        Position::findOrFail($request->get('id'))->delete();

        Toast::info('Должность удалена');
    }
}
