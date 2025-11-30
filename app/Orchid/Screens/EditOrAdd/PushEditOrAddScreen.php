<?php

namespace App\Orchid\Screens\EditOrAdd;

use App\Models\PushSotrudnikam;
use App\Models\OrganizationStructure;
use Illuminate\Http\Request;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Fields\DateTimer;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Quill;
use Orchid\Screen\Fields\Cropper;
use Orchid\Screen\Fields\Switcher;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Screen;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;

class PushEditOrAddScreen extends Screen
{
    /**
     * Query data.
     */

    public $push;
    public $title;
    public $psp_id;

    public function query(PushSotrudnikam $push): iterable
    {
        $this->psp_id = auth()->user()->psp;
        $this->title = $push->exists;
        return [
            'push' => $push->exists ? $push : [],
        ];


    }

    /**
     * Display header name.
     */
    public function name(): ?string
    {
        return $this->title ? 'Редактировать уведомление' : 'Добавить уведомление';
    }

    /**
     * Button commands.
     */
    public function commandBar(): iterable
    {
        return [
            Button::make('Сохранить')
                ->icon('check')
                ->method('savePushNotification'),
        ];
    }

    /**
     * Views.
     */
    public function layout(): iterable
    {
        $fields = [
            Input::make('push.id')->type('hidden'),
            Select::make('push.lang')
                ->title('Язык')
                ->options([
                    'ru' => 'Русский',
                    'kz' => 'Казахский',
                ])
                ->required(),
            Input::make('push.title')->title('Заголовок')->required(),

            Input::make('push.mini_description')->title('Краткое описание')->required(),

            Quill::make('push.body')->title('Основной текст')->required(),

            Cropper::make('push.photo')->title('Фото')->width(600)->height(300),

            is_null($this->psp_id)
                ?
                    Switcher::make('push.for_all')->sendTrueOrFalse()->title('Для всех')
                :
                    Switcher::make('push.for_all')->sendTrueOrFalse()->disabled()->value(false)->title('Для всех'),

            DateTimer::make('push.expiry_date')->format24hr()->enableTime()->format('Y-m-d H:i')->title('Срок действия')->required(),

            is_null($this->psp_id)

                ?
                    Select::make('push.organizations')->fromModel(OrganizationStructure::class, 'name_ru')->multiple()->title('Организации (если уведомление не для всех)')
                :
                    Select::make('push.organizations')->fromModel(OrganizationStructure::class, 'name_ru')->value($this->psp_id)->disabled()->multiple()->title('Организации (если уведомление не для всех)')
        ];
        return [
            Layout::rows(array_filter($fields)),
        ];
    }

    /**
     * Save push notification.
     */
    public function savePushNotification(Request $request)
    {
        $data = $request->validate([
            'push.id' => 'nullable|integer|exists:push_sotrudnikam,id',
            'push.lang' => 'required|string|in:ru,kz',
            'push.title' => 'required|string|max:255',
            'push.mini_description' => 'required|string|max:255',
            'push.body' => 'required|string',
            'push.photo' => 'nullable|string',
            'push.expiry_date' => 'required|date',
            'push.for_all' => 'nullable|boolean', // добавлено
            'push.organizations' => 'nullable|array',
            'push.organizations.*' => 'integer|exists:organization_structures,id',
        ]);

        // Явно приводим к булеву типу и задаём значение по умолчанию
//        $data['push']['for_all'] = $data['push']['for_all'] ?? false;

        if (is_null($this->psp_id)) {
            if (!$data['push']['for_all']) {
                $data['push']['organizations'] = $request->input('push.organizations', []);
            } else {
                $data['push']['organizations'] = [];
            }
        } else {
            $data['push']['for_all'] = false;
            $data['push']['organizations'] = [$this->psp_id];
        }

        $push = PushSotrudnikam::updateOrCreate(
            ['id' => $data['push']['id'] ?? null],
            $data['push']
        );

        if (!$data['push']['for_all'] && isset($data['push']['organizations'])) {
            $push->organizations()->sync($data['push']['organizations']);
        }

        Toast::info('Уведомление сохранено.');
        return redirect()->back();
    }
}
