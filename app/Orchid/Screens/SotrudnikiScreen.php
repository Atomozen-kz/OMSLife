<?php

namespace App\Orchid\Screens;

use App\Http\Requests\OrchidSotrudnikiRequest;
use App\Models\OrganizationStructure;
use App\Models\Position;
use App\Models\Sotrudniki;
use App\Models\SotrudnikiCodes;
use App\Orchid\Filters\SotrudnikiFioFilter;
use App\Orchid\Layouts\rows\addOrUpdateSotrudnikModal;
use App\Orchid\Layouts\SotrudnikiSelection;
use App\Orchid\Layouts\SpisokSotrudnikovTable;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use League\Csv\Reader;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Actions\ModalToggle;
use Orchid\Screen\Fields\DateTimer;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Fields\Upload;
use Orchid\Screen\Screen;
use Orchid\Screen\TD;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;

class SotrudnikiScreen extends Screen
{
    /**
     * Fetch data to be displayed on the screen.
     *
     * @return array
     */
    public function query(): iterable
    {
        return [
            'sotrudniki' => Sotrudniki::with(['organization', 'position'])
                ->select('*', DB::raw("full_name AS fio"))
                ->filters()
                ->filtersApply([SotrudnikiFioFilter::class])
                ->paginate(),
        ];
    }

    public function filters(): array
    {
        return [
            SotrudnikiFioFilter::class,
        ];
    }

    /**
     * The name of the screen displayed in the header.
     *
     * @return string|null
     */
    public function name(): ?string
    {
        return 'Управление сотрудниками';
    }

    /**
     * The screen's action buttons.
     *
     * @return \Orchid\Screen\Action[]
     */
    public function commandBar(): iterable
    {
        return [
            Link::make('Структура организации')
                ->route('platform.organization.structure')
                ->class('btn btn-warning')
                ->icon('building'),

            Link::make(' Должности')
                ->route('platform.positions')
                ->class('btn btn-warning')
                ->icon('bs.briefcase'),

            ModalToggle::make('Добавить сотрудника')
                ->modal('createOrUpdateSotrudnikaModal')
                ->modalTitle('Добавить сотрудника')
                ->method('createOrUpdateSotrudnika')
                ->class('btn btn-primary mr-2')
                ->icon('plus'),
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
            SotrudnikiSelection::class,

            // Таблица список сотрудников
            SpisokSotrudnikovTable::class,

            // Модальное окно для добавление или редактирвоание сотрудника
            Layout::modal('createOrUpdateSotrudnikaModal', [
                addOrUpdateSotrudnikModal::class
            ])->async('asyncsotrudnik')
            ->applyButton('Сохранить')
            ->closeButton('Отмена')
        ];
    }

    public function asyncsotrudnik(Sotrudniki $sotrudnik): array
    {
        return [
            'sotrudnik' => $sotrudnik,
        ];
    }

    public function createOrUpdateSotrudnika(OrchidSotrudnikiRequest  $request)
    {
        // Получаем данные сотрудника из запроса
        $data = $request->input('sotrudnik', []);

        // Если есть ИИН, автоматически заполняем birthdate и gender
//        if (!empty($data['iin'])) {
//            $iinData = $this->parseIINData($data['iin']);
//
//            if ($iinData) {
//                $data['birthdate'] = $iinData['birthdate'];
//                $data['gender'] = $iinData['gender'];
//            }
//        }

        // Поиск сотрудника по ID, если существует
        $sotrudnik = Sotrudniki::find($request->input('sotrudnik.id'));

        if ($sotrudnik) {
            // Обновление данных существующего сотрудника
            $sotrudnik->update($data);
            Toast::info('Данные сотрудника обновлены!');
        } else {
            // Создание нового сотрудника
            Sotrudniki::create($data);
            Toast::info('Сотрудник успешно добавлен!');
        }
    }

    /**
     * Парсит ИИН и возвращает дату рождения и пол
     *
     * @param string|null $iin
     * @return array|null
     */
    private function parseIINData($iin)
    {
        if (!$iin) {
            return null;
        }

        // Убираем пробелы и проверяем длину
        $iin = preg_replace('/\s+/', '', $iin);

        if (strlen($iin) !== 12 || !is_numeric($iin)) {
            return null;
        }

        // Первые 6 цифр - дата рождения (YYMMDD)
        $year = substr($iin, 0, 2);
        $month = substr($iin, 2, 2);
        $day = substr($iin, 4, 2);

        // 7-я цифра - век и пол
        $centuryGender = (int)substr($iin, 6, 1);

        // Определяем век и пол
        $century = null;
        $gender = null;

        switch ($centuryGender) {
            case 1:
                $century = 1800;
                $gender = 'male';
                break;
            case 2:
                $century = 1800;
                $gender = 'female';
                break;
            case 3:
                $century = 1900;
                $gender = 'male';
                break;
            case 4:
                $century = 1900;
                $gender = 'female';
                break;
            case 5:
                $century = 2000;
                $gender = 'male';
                break;
            case 6:
                $century = 2000;
                $gender = 'female';
                break;
            default:
                return null;
        }

        // Формируем полную дату рождения
        $fullYear = $century + (int)$year;

        try {
            // Проверяем корректность даты
            $birthdate = Carbon::createFromFormat('Y-m-d', sprintf('%04d-%02d-%02d', $fullYear, $month, $day));

            return [
                'birthdate' => $birthdate->format('Y-m-d'),
                'gender' => $gender
            ];
        } catch (\Exception $e) {
            return null;
        }
    }

    // Метод для удаления сотрудника
    public function remove($id)
    {
        Sotrudniki::findOrFail($id)->delete();
        Toast::info('Сотрудник успешно удален');
    }

}
