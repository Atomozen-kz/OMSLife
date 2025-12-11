<?php

namespace App\Orchid\Screens;

use App\Models\BankIdea;
use App\Models\BankIdeasType;
use Illuminate\Http\Request;
use Orchid\Screen\Actions\ModalToggle;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Switcher;
use Orchid\Screen\Fields\TextArea;
use Orchid\Screen\Screen;
use Orchid\Screen\TD;
use Orchid\Screen\Layouts\Modal;
use Orchid\Screen\Layouts\Table;
use Orchid\Support\Facades\Layout;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Actions\Button;
use Orchid\Support\Facades\Alert;
use Orchid\Support\Facades\Toast;

class BankIdeasScreen extends Screen
{
    public function query(): iterable
    {
        return [
            'approvedIdeas' => $this->approvedIdeas(),
            'notApprovedIdeas' => $this->notApprovedIdeas(),
            'types' => BankIdeasType::all(),
        ];
    }

    public function name(): ?string
    {
        return 'Банк идей';
    }

    public function commandBar(): iterable
    {
        return [
            ModalToggle::make('Типы идей')
                ->modal('showTypesModal')
                ->icon('list'),
        ];
    }

    public function layout(): iterable
    {
        return [
            Layout::tabs([
                'Одобренные идеи' => Layout::table('approvedIdeas', $this->ideaColumns(true)),
                'Заявки' => Layout::table('notApprovedIdeas', $this->ideaColumns(false)),
            ]),

            Layout::modal('showTypesModal', [
                Layout::table('types', [
                    TD::make('name_kz', 'Название (KZ)')
                        ->render(function (BankIdeasType $type) { return $type->name_kz; }),
                    TD::make('name_ru', 'Название (RU)')
                        ->render(function (BankIdeasType $type) { return $type->name_ru; }),
                    TD::make('status', 'Статус')->render(function (BankIdeasType $type) {
                        return $type->status ? 'Включен' : 'Отключен';
                    }),
                    TD::make('Действия')->render(function (BankIdeasType $type) {
                        return ModalToggle::make('Редактировать')
                            ->modal('addOrUpdateTypeModal')
                            ->modalTitle('Редактировать тип')
                            ->method('saveType')
                            ->asyncParameters(['type' => $type->id])
                            ->icon('pencil');
                    }),
                ]),

                Layout::rows([
                    ModalToggle::make('Добавить тип')
                        ->modal('addOrUpdateTypeModal')
                        ->modalTitle('Добавить тип')
                        ->method('saveType')
                        ->icon('plus'),
                ]),
            ])
                ->title('Типы для банка идей')
                ->withoutApplyButton()
                ->size('modal-lg')
                ->applyButton('Закрыть'),

            Layout::modal('addOrUpdateTypeModal', [
                Layout::rows([
                    Input::make('type.id')->type('hidden'),

                    Input::make('type.name_ru')
                        ->title('Название (RU)')
                        ->required(),

                    Input::make('type.name_kz')
                        ->title('Название (KZ)')
                        ->required(),

                    Switcher::make('type.status')
                        ->sendTrueOrFalse()
                        ->title('Статус (включен / отключен)'),
                ]),
            ])
                ->async('asyncGetType')
                ->title('Добавить/Редактировать тип')
                ->applyButton('Сохранить')
                ->closeButton('Отмена'),
        ];
    }

    private function ideaColumns(bool $isApproved): array
    {
        return [
            TD::make('title_link', 'Название')
                ->render(function (BankIdea $idea) {
                    return Link::make($idea->problem)
                        ->route('platform.screens.idea.view', ['bankIdea' => $idea->id]);
                }),

            TD::make('problem', 'Описание проблемы')
                ->render(function (BankIdea $idea) {
                    return $idea->problem ?? '-';
                }),

            TD::make('solution', 'Предлагаемое решение')
                ->render(function (BankIdea $idea) {
                    return $idea->solution ?? '-';
                }),


            TD::make('expected_effect', 'Ожидаемый эффект')
                ->render(function (BankIdea $idea) {
                    return $idea->expected_effect ?? '-';
                }),

            TD::make('tags', 'Информация')->render(function (BankIdea $idea) {
                       return "Автор: {$idea->author->full_name} <br>Создано: {$idea->created_at->format('d.m.Y H:i')}";
                   }),

                    TD::make('status', 'Статус')
                        ->render(fn (BankIdea $idea) => $idea->status ? 'Одобрено' : 'На рассмотрении'),
        ];
    }

    private function approvedIdeas()
    {
        return BankIdea::where('status', true)
            ->with('author', 'type')
            ->paginate();
    }

    private function notApprovedIdeas()
    {
        return BankIdea::where('status', false)
            ->with('author', 'type')
            ->paginate();
    }

    public function asyncGetType(BankIdeasType $type)
    {
        return ['type' => $type];
    }

    public function saveType(Request $request)
    {
        $data = $request->validate([
            'type.id' => 'nullable|integer',
            'type.name_ru' => 'required|string',
            'type.name_kz' => 'required|string',
            'type.status' => 'boolean',
        ]);

        $t = $data['type'];

        if (!empty($t['id'])) {
            $type = BankIdeasType::find($t['id']);
            if ($type) {
                $type->update([
                    'name_ru' => $t['name_ru'],
                    'name_kz' => $t['name_kz'],
                    'status' => $t['status'] ?? true,
                ]);
            }
        } else {
            BankIdeasType::create([
                'name_ru' => $t['name_ru'],
                'name_kz' => $t['name_kz'],
                'status' => $t['status'] ?? true,
            ]);
        }

        Toast::info('Тип успешно сохранён.');
    }
}
