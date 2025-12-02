<?php

namespace App\Orchid\Screens;

use App\Models\PromzonaObject;
use App\Models\PromzonaType;
use App\Models\Sotrudniki;
use Illuminate\Http\Request;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Actions\ModalToggle;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Map;
use Orchid\Screen\Fields\Relation;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Fields\Switcher;
use Orchid\Screen\Layouts\Modal;
use Orchid\Screen\Layouts\Table;
use Orchid\Screen\Screen;
use Orchid\Screen\TD;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;

class PromzonaScreen extends Screen
{
    protected $parentId;

    protected $title_name = 'Промзона';
    public function query(Request $request): iterable
    {
        $this->parentId = $request->get('parent_id', null);

        if ($this->parentId) {
            $object = PromzonaObject::find($this->parentId);
            $this->title_name = $object->type->name_ru. ' - '.$object->number;
        }

        return [
            'promzonaObjects' => PromzonaObject::where('parent_id', $this->parentId)->with(['type', 'sotrudnik'])->paginate(),
            'promzonaTypes' => PromzonaType::paginate(),
        ];
    }

    public function name(): ?string
    {
        return $this->title_name;
    }

    public function commandBar(): iterable
    {
        return [

            ModalToggle::make('Список типов объектов')
                ->modal('promzonaTypeModal')
                ->modalTitle('Список типов объектов')
                ->icon('list'),

            ModalToggle::make('Добавить объект')
                ->modal('createOrUpdateObjectModal')
                ->modalTitle('Добавить объект в Промзону')
                ->parameters(['parent_id' => $this->parentId])
                ->method('createOrUpdateObject')
                ->icon('plus'),

        ];
    }

    public function layout(): iterable
    {
        return [
            // Таблица объектов Промзоны
            Layout::table('promzonaObjects', [

                TD::make('type.name_ru', 'Тип объекта')

                    ->render(function (PromzonaObject $object) {

                    return Link::make($object->type->name_ru . ' - ' . $object->number)
                                ->route('platform.promzona-map', ['parent_id' => $object->id])
                        ;

                }),


                TD::make('sotrudnik.fio', 'Добавил'),


                TD::make('status', 'Статус проверки')
                    ->render(function (PromzonaObject $object) {
                        return $object->status ? '✅ Проверено'.
                            ModalToggle::make('Редактировать')
                                ->modal('createOrUpdateObjectModal')
                                ->modalTitle('Редактировать объект')
                                ->method('createOrUpdateObject')
                                ->asyncParameters(['object' => $object->id])
                                ->icon('pencil')

                            : '❌ Не проверено'
                            . ' ' .
                            ModalToggle::make('Проверить')
                                ->modal('createOrUpdateObjectModal')
                                ->modalTitle('Проверить объект')
                                ->method('createOrUpdateObject')
                                ->asyncParameters(['object' => $object->id])
                                ->icon('pencil')
                            ;
                    }),
                TD::make('Действия')
                    ->render(function (PromzonaObject $object) {
                        return  Button::make('Удалить')
                            ->method('deleteObject')
                            ->parameters(['id' => $object->id])
                            ->confirm('Вы уверены, что хотите удалить этот объект?')
                            ->icon('trash')
                            ;
                    }),
            ]),

            // Модальное окно для работы с PromzonaType
            Layout::modal('promzonaTypeModal', [
                Layout::table('promzonaTypes', [
                    TD::make('name_kz', 'Название (KZ)'),
                    TD::make('name_ru', 'Название (RU)'),
                    TD::make('icon_text', 'Код иконка'),
                    TD::make('status', 'Статус')
                        ->render(function (PromzonaType $type) {
                            return $type->status ? '🟢 Активен' : '🔴 Неактивен';
                        }),
                    TD::make('Действия')->render(function (PromzonaType $type) {
                        return ModalToggle::make('Редактировать')
                            ->modal('createOrUpdateTypeModal')
                            ->method('createOrUpdateType')
                            ->asyncParameters(['type' => $type->id])
                            ->icon('pencil')
                            ->modalTitle('Редактировать тип объекта: '.$type->name_ru);
                    })

                ]),
                Layout::rows([
                    ModalToggle::make('Добавить тип')
                        ->modal('createOrUpdateTypeModal')
                        ->modalTitle('Добавить тип объекта')
                        ->method('createOrUpdateType')
                        ->icon('plus'),
                ]),
            ])->size(Modal::SIZE_LG)
                ->title('Список типов объектов')
                ->withoutApplyButton(),

            // Модальное окно для добавления/редактирования объекта
            Layout::modal('createOrUpdateObjectModal', [
                Layout::rows([

                    Input::make('promzonaObject.id')->type('hidden'),

                    Switcher::make('promzonaObject.status')
                        ->sendTrueOrFalse()
                        ->title('Статус проверки'),

                    Relation::make('promzonaObject.id_type')
                        ->fromModel(PromzonaType::class, 'name_ru', 'id')
                        ->title('Тип объекта')
                        ->required(),

                    Relation::make('promzonaObject.id_sotrudnik')
                        ->fromModel(Sotrudniki::class, 'full_name')
                        ->displayAppend('fio')
                        ->title('ФИО сотрудника')
                        ->allowEmpty()
                        ->required(),

                    Input::make('promzonaObject.number')
                        ->title('Название / Номер объекта')
                        ->required(),

                    Map::make('promzonaObject.coordinate')
                        ->value([43.3477078668619,52.86336159675163])
                        ->popover('Карта')
                        ->zoom(11)
                        ->name('promzonaObject')
                        ->latitude('latitude')
                        ->longitude('longitude')
                        ->title('Местоположение')
                        ->required(),
                ]),
            ])  ->async('asyncGetPromzonaObjects')
                ->applyButton('Сохранить')
                ->closeButton('Отмена'),

            // Модальное окно для добавления/редактирования типа объекта
            Layout::modal('createOrUpdateTypeModal', [
                Layout::rows([
                    Input::make('promzonaType.id')->type('hidden'),

                    Input::make('promzonaType.name_kz')
                        ->title('Название (KZ)')
                        ->required(),

                    Input::make('promzonaType.name_ru')
                        ->title('Название (RU)')
                        ->required(),

                    Input::make('promzonaType.icon_text')
                        ->title('Код иконки')
                        ->required(),

                    Switcher::make('promzonaType.status')
                        ->sendTrueOrFalse()
                        ->title('Активен'),
                ]),
            ])  ->async('asyncGetPromzonaTypes')
                ->applyButton('Сохранить')
                ->closeButton('Отмена'),
        ];
    }

    public function asyncGetPromzonaObjects(PromzonaObject $object){
        return [
            'promzonaObject' => $object,
        ];
    }

    public function asyncGetPromzonaTypes(PromzonaType $type){
        return [
            'promzonaType' => $type,
        ];
    }

    public function createOrUpdateObject(Request $request)
    {
        $data = $request->validate([
            'promzonaObject.id' => 'nullable|integer|exists:promzona_objects,id',
            'promzonaObject.id_type' => 'required|exists:promzona_types,id',
            'promzonaObject.id_sotrudnik' => 'required|exists:sotrudniki,id',
            'promzonaObject.number' => 'required|string|max:255',
            'promzonaObject.lat' => 'required|numeric',
            'promzonaObject.lng' => 'required|numeric',
            'promzonaObject.status' => 'boolean',
        ]);

        $data['promzonaObject']['parent_id'] = $request->input('parent_id', null);

        PromzonaObject::updateOrCreate(
            ['id' => $data['promzonaObject']['id'] ?? null],
            $data['promzonaObject']
        );

        Toast::info('Объект успешно сохранен.');
    }

    public function createOrUpdateType(Request $request)
    {
        $request->validate([
            'promzonaType.id' => 'nullable|integer|exists:promzona_types,id',
            'promzonaType.name_kz' => 'required|string|max:255',
            'promzonaType.name_ru' => 'required|string|max:255',
            'promzonaType.icon_text' => 'required|string|max:255',
            'promzonaType.status' => 'boolean',
        ]);

        PromzonaType::updateOrCreate(
            ['id' => $request->input('promzonaType.id')],
            $request->input('promzonaType')
        );

        Toast::info('Тип объекта успешно сохранен.');
    }

    public function deleteObject(int $id){
        PromzonaObject::findOrFail($id)->delete();
        Toast::info('Объект успешно удален.');
    }
}
