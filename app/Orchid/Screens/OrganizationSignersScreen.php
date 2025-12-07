<?php

namespace App\Orchid\Screens;

use App\Models\OrganizationSigner;
use App\Models\OrganizationStructure;
use App\Models\User;
use Illuminate\Http\Request;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\ModalToggle;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Relation;
use Orchid\Screen\Fields\Switcher;
use Orchid\Screen\Layouts\Table;
use Orchid\Screen\Screen;
use Orchid\Screen\TD;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;

class OrganizationSignersScreen extends Screen
{
    public function query(): iterable
    {
        return [
            'signers' => OrganizationSigner::with('user')->paginate(),
        ];
    }

    public function name(): ?string
    {
        return 'Подписанты';
    }

    public function commandBar(): iterable
    {
        return [
            ModalToggle::make('Добавить подписанта')
                ->modal('signerModal')
                ->method('saveSigner')
                ->icon('plus'),
        ];
    }

    public function layout(): iterable
    {
        return [
            Layout::table('signers', [
                TD::make('status', 'Статус')->render(function (OrganizationSigner $signer) {
                    return $signer->status? '🟢 Активен' : '🔴 Неактивен';
                }),
                TD::make('user.name', 'Пользователь'),
                TD::make('last_name', 'Фамилия'),
                TD::make('first_name', 'Имя'),
                TD::make('father_name', 'Отчество'),
                TD::make('iin', 'ИИН'),
                TD::make('position', 'Должность'),
                TD::make('Действия')
                    ->render(function (OrganizationSigner $signer) {
                        return ModalToggle::make('Редактировать')
                                ->modal('signerModal')
                                ->method('saveSigner')
                                ->asyncParameters(['signer' => $signer->id])
                                ->icon('pencil')
                            . ' ' .
                            Button::make('Удалить')
                                ->method('deleteSigner')
                                ->parameters(['id' => $signer->id])
                                ->confirm('Вы уверены, что хотите удалить этого подписанта?')
                                ->icon('trash');
                    }),
            ]),

            Layout::modal('signerModal', [
                Layout::rows([
                    Input::make('signer.id')->type('hidden'),
                    Switcher::make('signer.status')
                        ->sendTrueOrFalse()
                        ->title('Статус'),

                    Relation::make('signer.user_id')
                        ->fromModel(User::class, 'name')
                        ->title('Пользователь')
                        ->required(),

                    Input::make('signer.last_name')
                        ->title('Фамилия')
                        ->required(),

                    Input::make('signer.first_name')
                        ->title('Имя')
                        ->required(),

                    Input::make('signer.father_name')
                        ->title('Отчество'),

                    Input::make('signer.iin')
                        ->title('ИИН')
                        ->required(),

                    Input::make('signer.position')
                        ->title('Должность')
                        ->required(),
                ]),
            ])->async('asyncGetSigner')
                ->applyButton('Сохранить')
                ->closeButton('Отмена')
                ->title('Добавить/Редактировать подписанта'),
        ];
    }

    public function asyncGetSigner(OrganizationSigner $signer): array
    {
        return [
            'signer' => $signer,
        ];
    }

    public function saveSigner(Request $request)
    {
        $data = $request->validate([
            'signer.user_id' => 'required|exists:users,id',
            'signer.last_name' => 'required|string|max:255',
            'signer.first_name' => 'required|string|max:255',
            'signer.father_name' => 'nullable|string|max:255',
            'signer.iin' => 'required|string|max:12',
            'signer.position' => 'required|string|max:255',
            'signer.status' => 'required|boolean',
        ]);

        OrganizationSigner::updateOrCreate(
            ['id' => $request->input('signer.id')],
            $data['signer']
        );

        Toast::info('Подписант успешно сохранен.');
    }

    public function deleteSigner(Request $request)
    {
        OrganizationSigner::findOrFail($request->input('id'))->delete();
        Toast::info('Подписант успешно удален.');
    }
}
