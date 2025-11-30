<?php
namespace App\Orchid\Screens;

use App\Models\OrganizationSigner;
use App\Models\SpravkaSotrudnikam;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\TextArea;
use Orchid\Screen\Screen;
use Orchid\Support\Facades\Alert;
use Orchid\Support\Facades\Layout;

class PdfPreviewScreen extends Screen
{
    public $spravka;

    /**
     * Query data.
     *
     * @return array
     */
    public function query(SpravkaSotrudnikam $spravka): array
    {

        // Установим локаль на казахский
        Carbon::setLocale('kk');

        $this->spravka = $spravka;

        return [
            'spravka' => $spravka,
            'sotrudnik' => $spravka->sotrudnik,
            'text_kz' => '',
            'text_ru' => '',
            'signer' => (object)[
                'position' => '',
                'fio' => ''
            ],
            'logoSrc' => url('storage/omg_logo.jpg'),
            'todayDate' => Carbon::now()->translatedFormat('Y жыл j F'), // Текущая дата и время
        ];
    }

    /**
     * Display header name.
     *
     * @return string
     */
    public function name(): string
    {
        return 'Справка с места работы для сотрудника '. $this->spravka->sotrudnik->fio;
    }

    /**
     * Button commands.
     *
     * @return array
     */
    public function commandBar(): array
    {
        return [];
    }

    /**
     * Views.
     *
     * @return array
     */
    public function layout(): array
    {
        $signer = OrganizationSigner::where('organization_id', $this->spravka->organization_id)->first();

        if(!$signer){
            Alert::error('Ошибка! Невозможно сгенерировать PDF для данной заявки. Подписант не найден для организации ID: '. $this->spravka->organization_id);
            return [];
        }

        if ($signer){
            $this->spravka->update([
                'id_signer' => $signer->id,
            ]);
        }




        return [
            Layout::view('pdf.preview_form'), // HTML-просмотр PDF
//            Layout::columns([
//                Layout::view('pdf.spravka_html_pdf'), // HTML-просмотр PDF
//
//
//
//            ]),

        ];
    }

    /**
     * Обработка изменений.
     */
    public function updatePreview(Request $request): void
    {
        // Обновите данные предпросмотра, если это необходимо
        // На этом этапе просто возвращаем данные, чтобы обновить HTML
    }
}
