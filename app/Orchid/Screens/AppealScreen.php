<?php
namespace App\Orchid\Screens;

use App\Models\Appeal;
use App\Models\AppealTopic;
use App\Models\AppealTopicUser;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Actions\ModalToggle;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Fields\Switcher;
use Orchid\Screen\Layouts\Modal;
use Orchid\Screen\Screen;
use Orchid\Screen\TD;
use Orchid\Support\Facades\Alert;
use Orchid\Support\Facades\Layout;

class AppealScreen extends Screen
{

    protected $topicName = '';
    public function query(): array
    {
        $userId = Auth::id();

        // Получаем ID тем, к которым прикреплен текущий пользователь
        $userTopicIds = AppealTopicUser::where('id_user', $userId)
            ->pluck('id_topic')
            ->toArray();

        if (!empty($userTopicIds)) {
            // Показываем только обращения по темам, к которым прикреплен админ
            $appeals = Appeal::whereIn('id_topic', $userTopicIds)->orderBy('id', 'desc')->paginate(10);

            // Получаем названия тем для отображения в описании
            $topics = AppealTopic::whereIn('id', $userTopicIds)->get();
            $this->topicName = $topics->count() > 1
                ? $topics->count() . ' тем'
                : ($topics->first()->title_ru ?? '');
        } else {
            // Если пользователь не прикреплен ни к одной теме, показываем весь список
            $appeals = Appeal::orderBy('id', 'desc')->paginate(10); // Пустой результат
            $this->topicName = 'Все темы';
        }

        return [
            'appeals' => $appeals,
        ];
    }

    public function commandBar(): array
    {
        // if ($this->topicName){
            return [
                Link::make('Часто задаваемые вопросы')
                    ->route('platform.faq')
                    ->icon('question-circle'),



                Link::make(' Управление темами')
                    ->route('platform.appeal.topics')
                    ->icon('gear')
                    ->class('btn btn-outline-primary'),
            ];
        // }else{
        //     return [];
        // }

    }

    public function name():?string
    {
        return 'Обращения';
    }
    public function description():?string {
            return $this->topicName ?? 'Список всех обращений';
    }

    public function layout(): array
    {

        return [
            Layout::table('appeals', [
                TD::make('id', 'ID')->sort(),

                TD::make('status', 'Статус')->render(function (Appeal $appeal) {
                    $statusClass = match($appeal->status) {
                        Appeal::STATUS_NEW => 'badge-primary',
                        Appeal::STATUS_IN_PROGRESS => 'badge-warning',
                        Appeal::STATUS_ANSWERED => 'badge-info',
                        Appeal::STATUS_CLOSED => 'badge-success',
                        Appeal::STATUS_REJECTED => 'badge-danger',
                        default => 'badge-secondary'
                    };
                    return '<span class="bg-primary badge '.$statusClass.'">'.$appeal->status_name.'</span>';
                }),

                TD::make('id_topic', 'Тема обращения')->render(function (Appeal $appeal) {
                    return $appeal->topic->title_ru;
                }),

                TD::make('title', 'Название'),

                TD::make('sotrudnik', 'Сотрудник')->render(function (Appeal $appeal){
                    return $appeal->sotrudnik->fio;
                }),

                TD::make('organization', 'Организация')->render(function (Appeal $appeal) {
                    return $appeal->organization->name_ru;
                }),

                TD::make('answers_count', 'Ответы')->render(function (Appeal $appeal) {
                    $count = $appeal->getAnswersCount();
                    return $count > 0
                        ? '<span class="bg-primary badge badge-info">'.$count.'</span>'
                        : '<span class="text-muted">-</span>';
                }),

                TD::make('created_at', 'Дата создания')->render(function (Appeal $appeal) {
                    return $appeal->created_at->format('d.m.Y H:i');
                }),

                TD::make('actions', 'Действия')->render(function (Appeal $appeal) {
                    return Link::make('Посмотреть')
                        ->route('platform.appeal.view', $appeal->id);
                }),
            ]),


        ];
    }


}
