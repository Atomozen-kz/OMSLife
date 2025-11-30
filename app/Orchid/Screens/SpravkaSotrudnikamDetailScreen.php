<?php

namespace App\Orchid\Screens;

use App\Models\SpravkaSotrudnikam;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Kreait\Firebase\Exception\FirebaseException;
use Kreait\Firebase\Exception\MessagingException;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Fields\Upload;
use Orchid\Screen\Screen;
use Orchid\Screen\Sight;
use Orchid\Screen\TD;
use Orchid\Support\Facades\Alert;
use Orchid\Support\Facades\Layout;
use Kreait\Firebase\Messaging;

class SpravkaSotrudnikamDetailScreen extends Screen
{
    /**
     * Fetch data to be displayed on the screen.
     *
     * @return array
     */
    public $spravka;
    public function query(SpravkaSotrudnikam $spravka): iterable
    {
        $this->spravka = $spravka;
        return [
            'spravka' => $spravka->load(['organization', 'sotrudnik']),
        ];
    }

    /**
     * The name of the screen displayed in the header.
     *
     * @return string|null
     */
    public function name(): ?string
    {
        return 'Детали заявки на справку: '.$this->spravka->sotrudnik->last_name;
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
     * Описание экрана.
     */
    public function description(): ?string
    {
        return 'Просмотр и управление заявкой на справку с места работы';
    }


    /**
     * The screen's layout elements.
     *
     * @return \Orchid\Screen\Layout[]|string[]
     */
    public function layout(): iterable
    {
        return [
            Layout::legend('spravka', [
                Sight::make('id', 'ID'),
                Sight::make('iin', 'ИИН'),
                Sight::make('employee.name', 'Сотрудник')->render(function (SpravkaSotrudnikam $certificate) {
                    return $certificate->sotrudnik->last_name . ' ' . $certificate->sotrudnik->first_name . ' ' . $certificate->sotrudnik->father_name;
                }),
                Sight::make('organizationStructure.name_ru', 'Организация')->render(
                    function (SpravkaSotrudnikam $certificate) {
                        return $certificate->organization->name_ru;
                    }
                ),
//                Sight::make('pdf_path', 'Документ')->render(function (SpravkaSotrudnikam $certificate) {
//                   return $certificate->pdf_path ? Link::make('Посмотреть')->route(Storage::path($certificate->pdf_path)) : 'Не сформирован';
//                }),
                Sight::make('status', 'Статус')->render(function (SpravkaSotrudnikam $certificate) {
                    switch ($certificate->status) {
                        case '1':
                            return '<span class="badge bg-warning">В ожидании</span>';
                        case '2':
                            return '<span class="badge bg-info">В процессе</span>';
                        case '7':
                            return '<span class="badge bg-success">Завершена</span>';
                        default:
                            return 'Не определен';
                    }
                }),
                Sight::make('created_at', 'Создана')->render(function (SpravkaSotrudnikam $certificate) {
                    return $certificate->created_at->format('d.m.Y H:i');
                }),
                Sight::make('Управление')->render(function (SpravkaSotrudnikam $certificate) {

                })
            ]),

            Layout::view('pdf/sign_pdf'),

            Layout::rows([

                Button::make('Генерировать PDF')
                    ->icon('doc')
                    ->method('generatePdf'),


                Button::make('Подписать')
                    ->icon('file-pdf')
                    ->addClass('connectAndSign'),

                Button::make('Отметить как готово')
                    ->icon('check')
                    ->method('markAsCompleted')
//                    ->canSee(function ($certificate) {
//                        return $certificate->status == 1 && $certificate->pdf_path;
//                    }),
            ]),

            // Можно добавить дополнительные элементы, если необходимо
        ];
    }

    /**
     * Метод для генерации PDF-файла.
     */
    public function generatePdf(SpravkaSotrudnikam $spravka)
    {
        if ($spravka->status != 1) {
            Alert::error('Ошибка! Невозможно сгенерировать PDF для данной заявки.');
            return;
        }

        // Кодирование изображения в Base64
        $logoPath = public_path('storage/omg_logo.jpg');
        $logoData = base64_encode(file_get_contents($logoPath));
        $logoSrc = 'data:image/jpeg;base64,' . $logoData;

        // Генерация PDF из Blade шаблона
        $pdf = Pdf::loadView('pdf.spravka_html_pdf', [
            'sotrudnik' => $spravka->sotrudnik,
            'certificate' => $spravka,
            'logoSrc' => $logoSrc,
        ]);

        // Сохранение PDF-файла
        $fileName = 'spravki/'.$spravka->id . '_' . time() . '.pdf';
        Storage::put( $fileName, $pdf->output());

        // Обновление пути к PDF в записи
        $spravka->pdf_path = $fileName;
        $spravka->status = 2;
        $spravka->save();

        Alert::info('Успех', 'PDF-файл справки успешно сгенерирован.');
    }

    /**
     * Метод для отметки заявки как готовой.
     */
    public function markAsCompleted(SpravkaSotrudnikam $certificate)
    {
        if ($certificate->status != 'processing' || !$certificate->pdf_path) {
            Alert::error('Ошибка', 'Невозможно отметить заявку как готовую.');
            return;
        }

        DB::beginTransaction();

        try {
            $certificate->status = 'completed';
            $certificate->save();

            // Отправка уведомления сотруднику
            $employee = $certificate->employee;
            if ($employee->fcm_token) {
                $messageData = [
                    'title' => 'Справка готова',
                    'body' => 'Ваша справка с места работы готова и доступна для скачивания.',
                    'image' => null,
                    'data' => [
                        'certificate_id' => $certificate->id,
                        'pdf_url' => Storage::url($certificate->pdf_path),
                    ],
                ];

                $token = $employee->fcm_token;

                $message = Messaging\CloudMessage::withTarget('token', $token)
                    ->withNotification([
                        'title' => $messageData['title'],
                        'body' => $messageData['body'],
                        'image' => $messageData['image'],
                    ])
                    ->withData($messageData['data']);

                try {
                    app('firebase.messaging')->send($message);
                } catch (MessagingException | FirebaseException $e) {
                    Log::error("Ошибка при отправке уведомления на токен {$token}: " . $e->getMessage());
                }
            }

            DB::commit();

            Alert::info('Успех', 'Заявка успешно отмечена как завершенная.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Ошибка при завершении заявки: ' . $e->getMessage());

            Alert::error('Ошибка', 'Не удалось завершить заявку.');
        }

        return redirect()->route('platform.employment-certificate.list');
    }
}
