<?php

namespace App\Orchid\Screens;

use App\Models\Faq;
use App\Models\FaqsCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Orchid\Screen\Actions\ModalToggle;
use Orchid\Screen\Fields\CheckBox;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Fields\Switcher;
use Orchid\Screen\Fields\TextArea;
use Orchid\Screen\Layouts\Modal;
use Orchid\Screen\Layouts\Table;
use Orchid\Screen\Screen;
use Orchid\Screen\TD;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;

class FaqScreen extends Screen
{

    public $category;

    public function query($id_category): array
    {
        $this->category = FaqsCategory::find($id_category);

        return [
            'faqs' => Faq::where('id_category', $id_category)->paginate(15),
            'faqs_kz' => Faq::where('id_category', $id_category)->where('lang', 'kz')->paginate(15),
            'faqs_ru' => Faq::where('id_category', $id_category)->where('lang', 'ru')->paginate(15),
        ];
    }

    public function name():string
    {
        return $this->category->name_kz;
    }

    public function description():string
    {
        return $this->category->name_ru;
    }

    public function commandBar(): array
    {
        return [
            ModalToggle::make('Добавить/Редактировать')
                ->modal('editFaqModal')
                ->method('saveFaq')
                ->icon('plus'),
        ];
    }

    public function layout(): array
    {
        return [

            Layout::tabs([

                'на Казахском' => $this->returnTabTable('faqs_kz'),
                'на Русском' => $this->returnTabTable('faqs_ru'),

            ]),


            Layout::modal('editFaqModal', Layout::rows([
                Input::make('faq.id')->type('hidden'),
                Input::make('faq.question')->title('Вопрос')->required(),
                TextArea::make('faq.answer')->title('Ответ')->rows(15)->required(),
                Select::make('faq.lang')
                    ->title('Язык')
                    ->options([
                        'kz' => 'Казахский',
                        'ru' => 'Русский',
                    ])
                    ->required(),
//                Input::make('faq.sort')
//                    ->title('Сортировка')
//                    ->type('number')
//                    ->value(111),
                Switcher::make('faq.status')
                    ->title('Активный')
                    ->value(true)
                    ->sendTrueOrFalse(),
            ]))
                ->title($this->category->name_kz.' - '. $this->category->name_ru)
                ->async('asyncFaq'),
        ];
    }

    public function returnTabTable($target){
        return Layout::table($target, [
            TD::make('id', 'ID'),
            TD::make('question', 'Вопрос'),
            TD::make('answer', 'Ответ')->render(function (Faq $faq) {
                return str_replace("\n", "<br>", $faq->answer);
            }),
            TD::make('status', 'Статус')->render(function (Faq $faq) {
                return $faq->status ? '🟢 Активен' : '🔴 Неактивен';
            }),
//            TD::make('sort', 'Сортировка'),
//            TD::make('lang', 'Язык'),
//            TD::make('id_user', 'Пользователь')->render(function (Faq $faq) {
//                return $faq->user->name ?? 'Не указано';
//            }),
            TD::make('actions', 'Действия')->render(function (Faq $faq) {
                return ModalToggle::make('Редактировать')
                    ->modal('editFaqModal')
                    ->method('saveFaq')
                    ->modalTitle('Редактировать вопрос')
                    ->asyncParameters(['faq' => $faq->id]);
            }),
        ]);
    }

    public function asyncFaq(Faq $faq)
    {
        return [
            'faq' => $faq,
        ];
    }

    public function saveFaq(Request $request)
    {
        $data = $request->input('faq');

//        dd($data);

        Faq::updateOrCreate(
            ['id' => $data['id'] ?? null],
            [
                'id_category' => $this->category->id ?? null,
                'question' => $data['question'],
                'answer' => $data['answer'],
                'status' => $data['status'] ?? true,
                'sort' => $data['sort'] ?? 111,
                'lang' => $data['lang'] ?? 'kz',
                'id_user' => Auth::id(),
            ]
        );

        Toast::info('Вопрос успешно сохранен.');
    }
}
