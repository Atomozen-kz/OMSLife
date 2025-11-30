<?php
namespace App\Orchid\Screens;

use App\Models\Appeal;
use App\Models\AppealAnswer;
use App\Models\AppealMedia;
use App\Models\AppealStatusHistory;
use App\Models\AppealTopic;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\ModalToggle;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Fields\TextArea;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Switcher;
use Orchid\Screen\Fields\Upload;
use Orchid\Screen\Layouts\Modal;
use Orchid\Screen\Screen;
use Orchid\Screen\Layouts\Legend;
use Orchid\Screen\Sight;
use Orchid\Screen\TD;
use Orchid\Support\Facades\Alert;
use Orchid\Support\Facades\Layout;
use App\Orchid\Layouts\TimelineLayout;
use App\Orchid\Layouts\AppealInfoLayout;
use App\Orchid\Layouts\AppealAnswersLayout;

class AppealViewScreen extends Screen
{
    protected $id_appeal;
    
    public function query(Appeal $appeal): array
    {
        $this->id_appeal = $appeal->id;
        
        // Загружаем обращение со связанными данными
        $appeal->load(['statusHistory.changedBy', 'answers.answeredBy', 'answers.media', 'appealMedia']);
        
        return [
            'appeal' => $appeal,
            'statusHistory' => $appeal->statusHistory()->orderBy('created_at', 'asc')->get(),
            'answers' => $appeal->answers,
        ];
    }

    public function name(): ?string
    {
        return 'Просмотр обращения № '.$this->id_appeal;
    }

    public $description = 'Детальная информация об обращении';

    public function commandBar(): array
    {
        return [
            ModalToggle::make('Изменить статус')
                ->modal('changeStatusModal')
                ->icon('pencil')
                ->method('changeStatus'),
                // ->canSee(Auth::user()->hasAccess('platform.appeal.edit')),
                


            ModalToggle::make('Передать обращение')
                ->modal('transferAppealModal')
                ->icon('share')
                ->method('transferAppeal')
                // ->canSee(Auth::user()->hasAccess('platform.appeal.transfer')),
        ];
    }

    public function layout(): array
    {
        return [
            // Красивая карточка с информацией об обращении
            AppealInfoLayout::make('appeal'),

            // Красивая timeline для истории статусов
            TimelineLayout::make('statusHistory'),

            // Красивые ответы на обращение
            AppealAnswersLayout::make('answers'),

            Layout::modal('changeStatusModal', [
                Layout::rows([
                    Select::make('new_status')
                        ->title('Новый статус')
                        ->options(Appeal::getStatusNames())
                        ->required(),
                        
                    TextArea::make('comment')
                        ->title('Комментарий')
                        ->placeholder('Укажите причину изменения статуса...')
                        ->rows(3),
                ])
            ])->title('Изменить статус обращения'),



            Layout::modal('transferAppealModal', [
                Layout::rows([
                    Select::make('transfer_to_user_id')
                        ->title('Передать пользователю')
                        ->fromQuery(User::whereRaw("`users`.`permissions` like '%\"platform.appeal\":\"1\"%'")
                            ->orWhereHas('roles', function ($query) {
                                $query->whereRaw("`roles`.`permissions` like '%\"platform.appeal\":\"1\"%'");
                            })
                            ->where('id', '!=', Auth::id()), 'name')
                        ->empty('Выберите пользователя...')
                        ->required()
                        ->help('Выберите пользователя, которому будет передано обращение'),

                    Select::make('new_topic_id')
                        ->title('Новая тема обращения (необязательно)')
                        ->fromQuery(AppealTopic::where('status', true), 'title_ru')
                        ->empty('Оставить текущую тему...')
                        ->help('Если нужно изменить тему обращения при передаче'),
                        
                    TextArea::make('transfer_reason')
                        ->title('Причина передачи')
                        ->placeholder('Укажите причину передачи обращения...')
                        ->required()
                        ->rows(3)
                        ->help('Обязательно укажите причину передачи обращения'),
                ])
            ])->title('Передать обращение другому сотруднику'),

            // Форма для добавления ответа
            Layout::rows([
                Select::make('new_status')
                    ->title('Изменить статус')
                    ->options(Appeal::getStatusNames())
                    ->empty('Оставить текущий статус...')
                    ->help('Выберите новый статус для обращения или оставьте пустым'),

                TextArea::make('answer')
                    ->title('Ответить на обращение')
                    ->placeholder('Введите ваш ответ...')
                    ->required()
                    ->rows(6),

                Switcher::make('is_public')
                    ->title('Публичный ответ')
                    ->sendTrueOrFalse()
                    ->value(true)
                    ->help('Будет ли ответ виден пользователю'),

                Upload::make('answer_files')
                    ->title('Прикрепить файлы')
                    ->acceptedFiles('.jpg,.jpeg,.png,.pdf,.doc,.docx,.xls,.xlsx')
                    ->multiple()
                    ->help('Можно прикрепить несколько файлов'),

                Button::make('Отправить ответ')
                    ->icon('paper-plane')
                    ->method('addAnswer')
                    ->class('btn btn-primary mt-3'),
            ])->title('Ответить на обращение'),
        ];
    }

    public function changeStatus(Request $request, Appeal $appeal)
    {
        $request->validate([
            'new_status' => 'required|integer|in:1,2,3,4,5',
            'comment' => 'nullable|string|max:1000',
        ]);

        $changed = $appeal->changeStatus(
            $request->get('new_status'),
            $request->get('comment'),
            Auth::id()
        );

        if ($changed) {
            Alert::success('Статус обращения успешно изменен');
        } else {
            Alert::warning('Статус не был изменен');
        }

        return redirect()->route('platform.appeal.view', $appeal);
    }

    public function addAnswer(Request $request, Appeal $appeal)
    {
        $request->validate([
            'answer' => 'required|string|max:10000',
            'new_status' => 'nullable|integer|in:1,2,3,4,5',
            'is_public' => 'boolean',
            'answer_files' => 'nullable|array',
            'answer_files.*' => 'string', // ID attachment'ов из Orchid
        ]);

        // Создаем ответ
        $appealAnswer = $appeal->addAnswer(
            $request->get('answer'),
            Auth::id(),
            $request->get('is_public', true)
        );

        // Обрабатываем загруженные файлы через Orchid Attachments
        $attachmentIds = $request->get('answer_files', []);
        if (!empty($attachmentIds)) {
            foreach ($attachmentIds as $attachmentId) {
                try {
                    // Получаем attachment из Orchid
                    $attachment = \Orchid\Attachment\Models\Attachment::find($attachmentId);
                    
                    if ($attachment) {
                        AppealMedia::create([
                            'id_appeal' => $appeal->id,
                            'id_answer' => $appealAnswer->id,
                            'file_path' => $attachment->path . $attachment->name . '.' . $attachment->extension,
                            'file_type' => $attachment->mime,
                        ]);
                    }
                } catch (\Exception $e) {
                    \Log::error('Ошибка при сохранении файла ответа: ' . $e->getMessage());
                }
            }
        }

        // Изменяем статус если указан новый
        $newStatus = $request->get('new_status');
        if ($newStatus && $newStatus != $appeal->status) {
            $appeal->changeStatus(
                $newStatus,
                'Статус изменен при добавлении ответа',
                Auth::id()
            );
        }

        Alert::success('Ответ успешно добавлен');

        return redirect()->route('platform.appeal.view', $appeal);
    }

    public function transferAppeal(Request $request, Appeal $appeal)
    {
        $request->validate([
            'transfer_to_user_id' => 'required|integer|exists:users,id',
            'new_topic_id' => 'nullable|integer|exists:appeal_topics,id',
            'transfer_reason' => 'required|string|max:1000',
        ]);

        $transferToUserId = $request->get('transfer_to_user_id');
        $newTopicId = $request->get('new_topic_id');
        $transferReason = $request->get('transfer_reason');
        
        // Получаем информацию о пользователе, которому передаём
        $transferToUser = User::find($transferToUserId);
        
        if (!$transferToUser) {
            Alert::error('Пользователь не найден');
            return redirect()->route('platform.appeal.view', $appeal);
        }

        $commentParts = ["Обращение передано пользователю: {$transferToUser->name}"];
        $topicChanged = false;
        
        // Если изменяется тема обращения
        if ($newTopicId && $newTopicId != $appeal->id_topic) {
            $oldTopic = $appeal->topic;
            $newTopic = AppealTopic::find($newTopicId);
            
            if ($newTopic) {
                // Обновляем тему обращения
                $appeal->update(['id_topic' => $newTopicId]);
                
                $commentParts[] = "Тема изменена с '{$oldTopic->title_ru}' на '{$newTopic->title_ru}'";
                $topicChanged = true;
            }
        }
        
        $commentParts[] = "Причина: {$transferReason}";
        $fullComment = implode(". ", $commentParts);

        // Создаём запись в истории статусов о передаче
        AppealStatusHistory::create([
            'id_appeal' => $appeal->id,
            'old_status' => $appeal->status,
            'new_status' => $appeal->status, // Статус не меняется при передаче
            'changed_by' => Auth::id(),
            'comment' => $fullComment
        ]);

        $successMessage = "Обращение успешно передано пользователю: {$transferToUser->name}";
        if ($topicChanged) {
            $successMessage .= " с изменением темы";
        }

        Alert::success($successMessage);

        return redirect()->route('platform.appeal.view', $appeal);
    }
}
