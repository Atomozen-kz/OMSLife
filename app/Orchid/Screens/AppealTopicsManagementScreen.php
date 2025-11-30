<?php

namespace App\Orchid\Screens;

use App\Models\AppealTopic;
use App\Models\AppealTopicUser;
use App\Models\User;
use Illuminate\Http\Request;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\ModalToggle;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Relation;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Fields\Switcher;
use Orchid\Screen\Layouts\Modal;
use Orchid\Screen\Screen;
use Orchid\Screen\TD;
use Orchid\Support\Facades\Alert;
use Orchid\Support\Facades\Layout;

class AppealTopicsManagementScreen extends Screen
{
    public function query(): array
    {
        $topics = AppealTopic::with(['assignedUsers', 'user', 'appeals'])
            ->orderBy('status', 'desc')
            ->orderBy('title_ru')
            ->get();

        $users = User::whereRaw("`users`.`permissions` like '%\"platform.appeal\":\"1\"%'")
            ->orWhereHas('roles', function ($query) {
                $query->whereRaw("`roles`.`permissions` like '%\"platform.appeal\":\"1\"%'");
            })
            ->orderBy('name')
            ->get();

        return [
            'topics' => $topics,
            'users' => $users,
        ];
    }

    public function name(): ?string
    {
        return 'Управление темами обращений';
    }

    public function description(): ?string
    {
        return 'Создание тем обращений и назначение администраторов для получения уведомлений';
    }

    public function commandBar(): array
    {
        return [
            ModalToggle::make('Создать тему')
                ->modal('createTopicModal')
                ->icon('plus')
                ->method('createTopic'),
        ];
    }

    public function layout(): array
    {
        return [
            Layout::table('topics', [
                TD::make('status', 'Статус')
                    ->sort()
                    ->render(function (AppealTopic $topic) {
                        $badgeClass = $topic->status ? 'badge-success' : 'badge-danger';
                        $text = $topic->status ? 'Активна' : 'Неактивна';
                        return '<span class="bg-primary badge ' . $badgeClass . '">' . $text . '</span>';
                    }),

                TD::make('title_ru', 'Название темы (RU)')
                    ->sort()
                    ->filter()
                    ->render(function (AppealTopic $topic) {
                        return '<strong>' . $topic->title_ru . '</strong>';
                    }),

                TD::make('title_kz', 'Название темы (KZ)')
                    ->render(function (AppealTopic $topic) {
                        return $topic->title_kz ?: '<span class="text-muted">Не указано</span>';
                    }),

                TD::make('user.name', 'Основной ответственный')
                    ->render(function (AppealTopic $topic) {
                        return $topic->user ? $topic->user->name : '<span class="text-muted">Не назначен</span>';
                    }),

                TD::make('assigned_users', 'Назначенные администраторы')
                    ->render(function (AppealTopic $topic) {
                        $users = $topic->assignedUsers;
                        if ($users->isEmpty()) {
                            return '<span class="text-muted">Никто не назначен</span>';
                        }
                        
                        return $users->map(function ($user) use ($topic) {
                            return '<span class="bg-primary badge badge-primary me-1 mb-1">' . $user->name . 
                                   ' <a href="#" onclick="if(confirm(\'Удалить назначение?\')) { removeUserAssignment(' . $topic->id . ', ' . $user->id . '); }" class="btn-close btn-close-white ms-1" style="font-size: 0.6em;"></a></span>';
                        })->implode(' ');
                    }),

                TD::make('appeals_count', 'Кол-во обращений')
                    ->render(function (AppealTopic $topic) {
                        $count = $topic->appeals->count();
                        $badgeClass = $count > 0 ? 'badge-info' : 'badge-secondary';
                        return '<span class="badge ' . $badgeClass . '">' . $count . '</span>';
                    }),

                TD::make('actions', 'Действия')
                    ->render(function (AppealTopic $topic) {
                        return ModalToggle::make('Назначить админа')
                            ->modal('assignUserModal')
                            ->method('assignUser')
                            ->modalTitle('Назначить админа на тему: '.$topic->title_ru)
                            ->asyncParameters(['topic' => $topic->id])
                            ->icon('person-plus')
                            ->class('btn btn-sm btn-outline-primary me-1') .
                            
                            ModalToggle::make('Редактировать')
                            ->modal('editTopicModal')
                            ->asyncParameters(['topic' => $topic->id])
                            ->icon('pencil')
                            ->class('btn btn-sm btn-outline-secondary');
                    }),
            ]),

            Layout::modal('createTopicModal', [
                Layout::rows([
                    Input::make('title_ru')
                        ->title('Название темы (русский)')
                        ->required()
                        ->placeholder('Введите название темы на русском языке')
                        ->help('Основное название темы, которое будет отображаться в системе'),

                    Input::make('title_kz')
                        ->title('Название темы (казахский)')
                        ->placeholder('Введите название темы на казахском языке')
                        ->help('Перевод названия темы на казахский язык'),



                    Switcher::make('status')
                        ->title('Активная тема')
                        ->sendTrueOrFalse()
                        ->value(true)
                        ->help('Активные темы доступны для выбора при создании обращений'),
                ])
            ])->title('Создать новую тему обращения'),

            Layout::modal('assignUserModal', [
                Layout::rows([
                    Relation::make('assign_user_id')
                        ->title('Выберите администратора')
                        ->fromModel(User::class, 'name')
                        ->applyScope('usersWithAppealAccess')
                        ->displayAppend('full')
                        ->required()
                        ->help('Выберите администратора, который будет получать уведомления по этой теме'),
                        
                    Input::make('topic_id')->type('hidden'),
                ])
            ])->title('Назначить администратора на тему')->async('asyncGetTopicData')->applyButton('Назначить')->method('assignUser'),
            
            Layout::modal('editTopicModal', [
                Layout::rows([
                    Input::make('topic.id')->type('hidden'),
                    
                    Input::make('topic.title_ru')
                        ->title('Название темы (русский)')
                        ->required()
                        ->placeholder('Введите название темы на русском языке'),

                    Input::make('topic.title_kz')
                        ->title('Название темы (казахский)')
                        ->placeholder('Введите название темы на казахском языке'),



                    Switcher::make('topic.status')
                        ->title('Активная тема')
                        ->sendTrueOrFalse()
                        ->help('Активные темы доступны для выбора при создании обращений'),
                ])
            ])->title('Редактировать тему')->async('asyncGetTopicForEdit')->applyButton('Сохранить')->method('updateTopic'),
            
            Layout::view('admin.topic-management-scripts'),
        ];
    }

    public function createTopic(Request $request)
    {
        $request->validate([
            'title_ru' => 'required|string|max:255|unique:appeal_topics,title_ru',
            'title_kz' => 'nullable|string|max:255',
            'status' => 'boolean',
        ]);

        $topic = AppealTopic::create([
            'title_ru' => $request->get('title_ru'),
            'title_kz' => $request->get('title_kz'),
            'status' => $request->get('status', true),
        ]);

        Alert::success("Тема \"{$topic->title_ru}\" успешно создана");

        return redirect()->route('platform.appeal.topics');
    }

    /**
     * Получить пользователей с доступом к разделу обращений
     */
    private function getUsersWithAppealAccess(): array
    {
        $users = User::where(function ($query) {
                $query->whereRaw("`users`.`permissions` like '%\"platform.appeal\":\"1\"%'")
                      ->orWhereHas('roles', function ($roleQuery) {
                          $roleQuery->whereRaw("`roles`.`permissions` like '%\"platform.appeal\":\"1\"%'");
                      });
            })
            ->orderBy('name')
            ->get();

        return $users->pluck('name', 'id')->toArray();
    }

    public function assignUser(Request $request)
    {
        $request->validate([
            'topic_id' => 'required|integer|exists:appeal_topics,id',
            'assign_user_id' => 'required|integer|exists:users,id',
        ]);

        $topicId = $request->get('topic_id');
        $userId = $request->get('assign_user_id');

        // Проверяем, не существует ли уже такое назначение
        $exists = AppealTopicUser::where('id_topic', $topicId)
                                ->where('id_user', $userId)
                                ->exists();

        if ($exists) {
            Alert::warning('Данный пользователь уже назначен на эту тему');
            return redirect()->route('platform.appeal.topics');
        }

        // Проверяем, что пользователь имеет права на работу с обращениями
        $user = User::find($userId);
        $hasPermission = $user->whereRaw("`users`.`permissions` like '%\"platform.appeal\":\"1\"%'")
            ->orWhereHas('roles', function ($query) {
                $query->whereRaw("`roles`.`permissions` like '%\"platform.appeal\":\"1\"%'");
            })
            ->exists();

        if (!$hasPermission) {
            Alert::error('Выбранный пользователь не имеет прав для работы с обращениями');
            return redirect()->route('platform.appeal.topics');
        }

        // Проверяем, не назначен ли уже этот пользователь на эту тему
        $existingAssignment = AppealTopicUser::where('id_topic', $topicId)
            ->where('id_user', $userId)
            ->first();

        if ($existingAssignment) {
            $topic = AppealTopic::find($topicId);
            Alert::warning("Пользователь {$user->name} уже назначен на тему \"{$topic->title_ru}\"");
            return redirect()->route('platform.appeal.topics');
        }

        // Создаем назначение
        AppealTopicUser::create([
            'id_topic' => $topicId,
            'id_user' => $userId,
        ]);

        $topic = AppealTopic::find($topicId);

        Alert::success("Пользователь {$user->name} успешно назначен на тему \"{$topic->title_ru}\"");

        return redirect()->route('platform.appeal.topics');
    }

    public function removeAssignment(Request $request)
    {
        $request->validate([
            'topic_id' => 'required|integer',
            'user_id' => 'required|integer',
        ]);

        $deleted = AppealTopicUser::where('id_topic', $request->get('topic_id'))
                                 ->where('id_user', $request->get('user_id'))
                                 ->delete();

        if ($deleted) {
            Alert::success('Назначение успешно удалено');
        } else {
            Alert::error('Назначение не найдено');
        }

        return redirect()->route('platform.appeal.topics');
    }

    public function asyncGetTopicData(AppealTopic $topic): array
    {
        // Загружаем тему с уже назначенными пользователями
        $topic->load('assignedUsers');
        
        // Получаем ID уже назначенных пользователей
        $assignedUserIds = $topic->assignedUsers->pluck('id')->toArray();
        
        // Получаем пользователей с доступом к разделу обращений
        $usersWithAccess = User::where(function ($query) {
                $query->whereRaw("`users`.`permissions` like '%\"platform.appeal\":\"1\"%'")
                      ->orWhereHas('roles', function ($roleQuery) {
                          $roleQuery->whereRaw("`roles`.`permissions` like '%\"platform.appeal\":\"1\"%'");
                      });
            })
            ->orderBy('name')
            ->get();
        
        // Исключаем уже назначенных пользователей
        if (!empty($assignedUserIds)) {
            $availableUsers = $usersWithAccess->whereNotIn('id', $assignedUserIds);
        } else {
            $availableUsers = $usersWithAccess;
        }

        // Формируем массив опций для Select
        $userOptions = $availableUsers->pluck('name', 'id')->toArray();

        $result = [
            'topic_id' => $topic->id,
            'assign_user_id' => $userOptions,
            'available_users_count' => count($userOptions),
            'topic_title' => $topic->title_ru,
            // Отладочная информация
            'debug_users_with_access_count' => $usersWithAccess->count(),
            'debug_assigned_users' => $assignedUserIds,
            'debug_available_count' => $availableUsers->count(),
        ];

        // Логируем результат для отладки
        \Log::info('asyncGetTopicData result:', $result);

        return $result;
    }

    public function asyncGetTopicForEdit(AppealTopic $topic): array
    {
        return [
            'topic' => $topic,
        ];
    }

    public function updateTopic(Request $request)
    {
        $request->validate([
            'topic.id' => 'required|integer|exists:appeal_topics,id',
            'topic.title_ru' => 'required|string|max:255',
            'topic.title_kz' => 'nullable|string|max:255',
            'topic.status' => 'boolean',
        ]);

        $topicData = $request->get('topic');
        $topic = AppealTopic::find($topicData['id']);

        // Проверяем уникальность названия
        $existingTopic = AppealTopic::where('title_ru', $topicData['title_ru'])
                                   ->where('id', '!=', $topic->id)
                                   ->first();
        
        if ($existingTopic) {
            Alert::error('Тема с таким названием уже существует');
            return redirect()->route('platform.appeal.topics');
        }

        $topic->update([
            'title_ru' => $topicData['title_ru'],
            'title_kz' => $topicData['title_kz'],
            'status' => $topicData['status'] ?? false,
        ]);

        Alert::success("Тема \"{$topic->title_ru}\" успешно обновлена");

        return redirect()->route('platform.appeal.topics');
    }
}
