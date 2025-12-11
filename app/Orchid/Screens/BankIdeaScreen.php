<?php

namespace App\Orchid\Screens;

use App\Models\BankIdea;
use App\Models\BankIdeasStatusHistory;
use Illuminate\Support\Facades\Storage;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Screen;
use Orchid\Screen\Sight;
use Orchid\Screen\TD;
use Orchid\Support\Facades\Layout;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\ModalToggle;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Fields\TextArea;
use Orchid\Screen\Fields\Input;
use Orchid\Support\Facades\Alert;

class BankIdeaScreen extends Screen
{
    public $bankIdea;

    public function query(BankIdea $bankIdea): iterable
    {
        $this->bankIdea = $bankIdea->load('author', 'comments', 'votes', 'files', 'statusHistory.changer');
        return [
            'bankIdea' => $this->bankIdea,
        ];
    }

    public function name(): ?string
    {
        return 'Просмотр идеи';
    }

    public function commandBar(): iterable
    {
        // Используем ModalToggle вместо поля Select в commandBar
        return [
            ModalToggle::make('Изменить статус')
                ->modal('changeStatusModal')
                ->icon('pencil')
                ->method('changeStatus')
                ->color('warning')
                ->parameters(['id' => $this->bankIdea->id]),

            Link::make('Назад')
                ->icon('arrow-left')
                ->route('platform.screens.idea'),
        ];
    }

    public function layout(): iterable
    {
        return [
            Layout::legend('bankIdea', [
                Sight::make('problem', 'Описание проблемы')
                    ->render(fn(BankIdea $idea) => $idea->problem ?? '-'),

                Sight::make('solution', 'Предлагаемое решение')
                    ->render(fn(BankIdea $idea) => $idea->solution ?? '-'),

                Sight::make('expected_effect', 'Ожидаемый эффект')
                    ->render(fn(BankIdea $idea) => $idea->expected_effect ?? '-'),

                Sight::make('status', 'Статус')
                    ->render(fn(BankIdea $idea) => '<span class="btn btn-outline-primary">' . e($idea->status_label) . '</span>'),

                Sight::make('author', 'Автор')
                    ->render(fn(BankIdea $idea) => optional($idea->author)->full_name),

                Sight::make('created_at', 'Дата создания')
                    ->render(fn(BankIdea $idea) => $idea->created_at->format('d.m.Y H:i')),

                Sight::make('files', 'Файлы')
                    ->render(function (BankIdea $idea) {
                        return $idea->files->isNotEmpty()
                           ? collect($idea->files)->map(function ($file) {
                               // Используем внутренний маршрут скачивания, чтобы избежать 403 и проблем с доступом
                               $url = route('platform.idea.file.download', ['file' => $file->id]);

                               $name = e(basename($file->path_to_file) ?: ($file->original_name ?? 'Файл'));
                               return "<a href=\"" . e($url) . "\" target=\"_blank\" rel=\"noopener\">{$name}</a>";
                           })->implode('<br>')
                           : 'Нет файлов';
                    }),
            ]),

            // Модал для изменения статуса
            Layout::modal('changeStatusModal', [
                Layout::rows([
                    Select::make('status')
                        ->title('Новый статус')
                        ->options($this->statusOptions())
                        ->value($this->bankIdea->status ?? BankIdea::STATUS_SUBMITTED)
                        ->required(),

                    TextArea::make('note')
                        ->rows(3)
                        ->max(1000)
                        ->title('Примечание (необязательно)'),
                ])
                ]
            )->title('Изменить статус идеи'),

            // История статусов внизу страницы
            Layout::view('platform.bank_ideas.status_history', [
                'history' => $this->bankIdea->statusHistory ?? collect(),
            ]),

        ];
    }

    /**
     * Меняем статус идеи и добавляем запись в историю
     */
    public function changeStatus($id)
    {
        $bankIdea = BankIdea::findOrFail($id);

        $newStatus = (int) request('status');
        if ($bankIdea->status === $newStatus) {
            Alert::info('Статус не изменён — выбран тот же статус.');
            return redirect()->route('platform.screens.idea.view', ['bankIdea' => $id]);
        }

        // Обновляем статус
        $oldStatus = $bankIdea->status;
        $bankIdea->update(['status' => $newStatus]);

        // Сохраняем в историю
        BankIdeasStatusHistory::create([
            'bank_idea_id' => $bankIdea->id,
            'status' => $newStatus,
            'changed_by' => optional(auth()->user())->id ?? null,
            'note' => request('note') ?? null,
        ]);

        Alert::info('Статус успешно обновлён: ' . (BankIdea::$statusLabels[$newStatus] ?? $newStatus));

        return redirect()->route('platform.screens.idea.view', ['bankIdea' => $id]);
    }

    /**
     * Возвращает массив опций статусов для Select
     */
    private function statusOptions(): array
    {
        return collect(BankIdea::$statusLabels)->mapWithKeys(function ($label, $key) {
            return [$key => $label];
        })->toArray();
    }
}
