<?php

namespace App\Orchid\Screens;

use App\Http\Controllers\mobile\PushSotrudnikamController;
use App\Models\OrganizationSigner;
use App\Models\SpravkaSotrudnikam;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Actions\ModalToggle;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Fields\Upload;
use Orchid\Screen\Screen;
use Orchid\Screen\Sight;
use Orchid\Screen\TD;
use Orchid\Support\Facades\Alert;
use Orchid\Support\Facades\Layout;

class SpravkaSotrudnikamScreen extends Screen
{
    /**
     * Fetch data to be displayed on the screen.
     *
     * @return array
     */
    public function query($success = null): iterable
    {
        if ($success == 'podpisana') {
            Alert::success('Справка успешно подписан');
        }
        $adminId = Auth::user()->id;

        $signer = OrganizationSigner::where('user_id', $adminId)->first();

        if ($signer){
            return [
                'spravka' => SpravkaSotrudnikam::with(['sotrudnik', 'organization'])->where('organization_id', $signer->organization_id)->orderBy('status')->orderByDesc('id')->paginate(),
            ];

        }else{
            return [
                'spravka' => SpravkaSotrudnikam::with(['sotrudnik', 'organization'])->orderBy('status')->orderByDesc('id')->paginate(),
            ];
        }

    }

    /**
     * The name of the screen displayed in the header.
     *
     * @return string|null
     */
    public function name(): ?string
    {
        return 'Заявки на справки';
    }

    /**
     * Описание экрана.
     */
    public function description(): ?string
    {
        return 'Управление заявками сотрудников на получение справок с места работы';
    }

    /**
     * The screen's action buttons.
     *
     * @return \Orchid\Screen\Action[]
     */
    public function commandBar(): iterable
    {
        return [];
    }

    /**
     * The screen's layout elements.
     *
     * @return \Orchid\Screen\Layout[]|string[]
     */
    public function layout(): iterable
    {
        return [
            Layout::table('spravka', [
                TD::make('id', 'ID')->sort()->width('100px'),

                TD::make('iin', 'ИИН')->sort()->width('120px'),

                TD::make('name', 'Сотрудник')->render(function (SpravkaSotrudnikam $certificate) {
                    return $certificate->sotrudnik->full_name;
                }),

                TD::make('organizationStructure.name_ru', 'Организация')->render(function (SpravkaSotrudnikam $certificate) {
                    return $certificate->organization->name_ru;
                }),

                TD::make('status', 'Статус')->render(function (SpravkaSotrudnikam $certificate) {
                    switch ($certificate->status) {
                        case '1':
                            return '<span class="badge bg-warning">В ожидании</span>';
                        case '2':
                            return '<span class="badge bg-info">В процессе</span>';
                        case '3':
                            return '<span class="badge bg-info">Подписан</span>';
                        case '7':
                            return '<span class="badge bg-success">Завершена</span>';
                        default:
                            return 'Не определен';
                    }
                })->width('150px')->cantHide(),

                TD::make('pdf_path', 'Документ')->render(function (SpravkaSotrudnikam $certificate) {
                   return $certificate->pdf_path && $certificate->ddc_path && in_array($certificate->status, [3,7]) ?
                       '<a href = "'.Storage::disk('private')->url($certificate->ddc_path).'">Посмотреть</a>'  : ' ';
                }),

                TD::make('created_at', 'Создана')->sort()->render(function (SpravkaSotrudnikam $certificate) {
                    return $certificate->created_at->format('d.m.Y H:i');
                }),

                TD::make('actions', 'Действия')->align(TD::ALIGN_CENTER)->width('200px')->render(function (SpravkaSotrudnikam $certificate) {
                    if ($certificate->status == 1) {
                        return
                                Link::make('Просмотр и генерировать PDF')
                                    ->icon('eye')
                                    ->route('platform.pdf-preview', ['spravka' => $certificate->id]);
//                            Button::make('Генерировать PDF')
//                                ->icon('file-pdf')
//                                ->method('generatePdf')
//                                ->parameters(['spravka' => $certificate->id]);

                    } elseif ($certificate->status == 2) {
                        return Link::make('Подписать')
                            ->icon('file-pdf')
                            ->route('platform.pdf-view', ['spravka' => $certificate->id])
                                ->class('btn btn-primary')

                            ;

                    }elseif ($certificate->status == 3) {
                            return Button::make('Готово')
                            ->icon('check')
                            ->method('completeCertificate')
                            ->parameters(['spravka' => $certificate->id]);
                    } elseif ($certificate->status == 7) {
                        return 'Документ готов и отправлен сотруднику';
                    }else{
                        return '';
                    }
                }),
            ]),

            // Модальное окно для обработки заявки
            Layout::modal('processCertificateModal', [
                Layout::rows([
                    Select::make('certificate.status')
                        ->title('Статус заявки')
                        ->options([
                            'processing' => 'В процессе',
                            'completed' => 'Завершена',
                        ])
                        ->required()
                        ->help('Выберите новый статус заявки'),

                    // Поле для загрузки PDF (если статус изменяется на завершена)
                    Upload::make('certificate.pdf')
                        ->title('Сформированный PDF')
                        ->acceptedFiles('application/pdf')
                        ->maxFiles(1)
                        ->help('Загрузите сформированный PDF-файл справки'),
//                        ->canSee(function () {
//                            return request()->input('certificate.status') == 'completed';
//                        }),
                ]),
            ])->title('Обработка заявки')
                ->applyButton('Сохранить')
                ->closeButton('Отмена')
                ->async('asyncGetCertificate'),

            // Модальное окно для просмотра деталей
            Layout::modal('viewCertificateModal', [
                // Можно добавить просмотр деталей, если нужно
            ]),
        ];
    }

    public function generatePdf(SpravkaSotrudnikam $spravka)
    {
        if ($spravka->status != 1) {
            Alert::error('Ошибка! Невозможно сгенерировать PDF для данной заявки.');
            return;
        }

        // Кодирование изображения в Base64
//        $logoPath = public_path('storage/omg_logo.jpg');
//        $logoData = base64_encode(file_get_contents($logoPath));
//        $logoSrc = 'data:image/jpeg;base64,' . $logoData;

        // Генерация PDF из Blade шаблона
        $pdf = Pdf::loadView('pdf.spravka_html_pdf', [
            'sotrudnik' => $spravka->sotrudnik,
            'certificate' => $spravka,
//            'logoSrc' => $logoSrc,
        ]);

        // Сохранение PDF-файла
        $fileName = 'spravki/'.$spravka->id . '_' . time() . '.pdf';
        Storage::disk('public')->put($fileName, $pdf->output());

        // Обновление пути к PDF в записи
        $spravka->pdf_path = $fileName;
        $spravka->status = 2;
        $spravka->save();

        Alert::info('Успех', 'PDF-файл справки успешно сгенерирован.');
    }

    /**
     * Асинхронное получение данных заявки для модального окна.
     */
    public function asyncGetCertificate(SpravkaSotrudnikam $certificate): array
    {
        return [
            'certificate' => $certificate
        ];
    }

    /**
     * Обработчик завершения заявки без загрузки файла (если требуется).
     */
    public function completeCertificate(SpravkaSotrudnikam $spravka)
    {
        $message_data = array(
            'title' => 'Справка с места работы',
            'body' => 'Справка готова. Можете скачать документ',
            'image' => NULL,
            'data' => [
                'type' => 'spravka',
                'id' => $spravka->id
            ],
        );

        PushSotrudnikamController::sendPushSotrudniku($spravka->sotrudnik_id, $message_data);

        $spravka->status = 7;
        $spravka->save();
    }
}
