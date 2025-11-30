<?php
namespace App\Orchid\Screens;

use App\Models\SpravkaSotrudnikam;
use Orchid\Screen\Screen;
use Orchid\Support\Facades\Layout;

class PdfViewScreen extends Screen
{
    public $spravka;

    /**
     * Query data.
     *
     * @return array
     */
    public function query(SpravkaSotrudnikam $spravka): array
    {
        return [
            'spravka' => $spravka,
        ];
    }

    /**
     * Display header name.
     *
     * @return string
     */
    public function name(): string
    {
        return 'Просмотр PDF';
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
        return [
            Layout::view('pdf.pdf_view'), // Подключение кастомного Blade-шаблона
        ];
    }
}
