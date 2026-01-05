<?php

declare(strict_types=1);

namespace App\Orchid;

use App\Models\SpravkaSotrudnikam;
use Orchid\Platform\Dashboard;
use Orchid\Platform\ItemPermission;
use Orchid\Platform\OrchidServiceProvider;
use Orchid\Screen\Actions\Menu;
use Orchid\Support\Color;
use App\Orchid\Screens\SotrudnikiScreen;
use App\Orchid\Screens\OneSotrudnikScreen;
use App\Orchid\Screens\StatisticsScreen;
use App\Orchid\Screens\StatisticsMonthDetailScreen;

class PlatformProvider extends OrchidServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @param Dashboard $dashboard
     *
     * @return void
     */
    public function boot(Dashboard $dashboard): void
    {
        parent::boot($dashboard);

        // ...
    }

    /**
     * Register the application menu.
     *
     * @return Menu[]
     */
    public function menu(): array
    {
        return [
//            Menu::make('Get Started')
//                ->icon('bs.book')
//                ->title('Navigation')
//                ->route(config('platform.index')),

//            Menu::make('Структура')
//                ->icon('bs.shuffle')
//                ->title('Орг Структура')
//                ->route('platform.organization.structure')
//                ->permission('platform.organization.structure')
//            ,

//            Menu::make('Должности')
//                ->icon('bs.star')
//                ->route('platform.positions'),

            Menu::make('Сотрудники')
                ->icon('bs.people')
                ->route('platform.sotrudniki')
                ->title('Отдел кадров')
                ->permission('platform.sotrudniki'),

            Menu::make('Справка с места работы')
                ->icon('bs.filetype-pdf')
                ->route('platform.spravka-sotrudnikam')
                ->permission('platform.spravka-sotrudnikam')
                ->badge(fn()=> SpravkaSotrudnikam::where('status', 1)->count(), Color::DANGER)
                ->divider(),

            Menu::make('Корпоративный учебный центр')
                ->icon('bs.person-vcard')
                ->title('Корпоративный учебный центр')
                ->route('platform.training-center')
                ->permission('platform.training-center'),

            Menu::make('Контакты АУП')
                ->icon('bs.person-rolodex')
                ->route('platform.contacts')
                ->title('Контакты')
                ->permission('platform.contacts')
                ->divider(),

//            Menu::make('Категорий новостей')
//                ->icon('bs.book-open')
//                ->title('Новости и Сторисы')
//                ->route('platform.news-сategory'),

            Menu::make('Новости')
                ->title('Департамент по связям с общественностью')
                ->icon('bs.newspaper')
                ->route('platform.news')
                ->permission('platform.news')
            ,

            Menu::make('Сторисы')
                ->icon('bs.file-image')
                ->route('platform.stories')
                ->permission('platform.stories')
            ,

            Menu::make('Статистика')
                ->icon('bs.bar-chart')
                ->route('platform.statistics')
                ->permission('platform.statistics')
            ,

            Menu::make('Уведомление для сотрудников')
                ->icon('bs.bell')
                ->route('platform.push-sotrudnikam')
                ->permission('platform.push-sotrudnikam')
            ,

            Menu::make('Часто задаваемое вопросы')
                ->icon('bs.patch-question-fill')
                ->route('platform.faqs-category')
                ->permission('platform.faq')
            ,

            Menu::make('Банк идеи')
                ->icon('bs.lightbulb')
                ->route('platform.screens.idea')
                ->permission('platform.idea')
            ,

            Menu::make('Опросы')
                ->icon('bs.ui-checks')
                ->route('platform.surveys')
                ->permission('platform.surveys')
            ->divider(),

            Menu::make('Пункты выдачи молока')
                ->icon('bs.qr-code-scan')
                ->title('Сервисы')
                ->route('platform.pickup-point')
                ->permission('platform.pickup-point')
            ,

            Menu::make('Посещаемые места')
                ->icon('bs.geo-alt-fill')
                ->route('platform.partner-places')
                ->permission('platform.partner-places')
            ,

            Menu::make('Карты лояльности')
                ->icon('bs.percent')
                ->route('platform.loyalty-cards')
                ->permission('platform.loyalty-cards')
                ->divider(),

            Menu::make('Подписанты')
                ->icon('bs.vector-pen')
                ->title('Отдел кадров')
                ->route('platform.organization-signers')
                ->permission('platform.organization-signers'),

//            Menu::make('Карта промзоны')
//                ->icon('bs.pin-map-fill')
//                ->route('platform.promzona-geo-objects')
//                ->permission('platform.promzona-geo-objects')
//                ->divider(),

//            Menu::make('Карта промзоны OLD')
//                ->icon('bs.pin-map-fill')
//                ->route('platform.promzona-map')
//                ->permission('platform.promzona-map')
//                ->divider(),



            Menu::make('Памятки по тех. безопасности')
                ->icon('bs.shield-exclamation')
                ->route('platform.safety-memos')
                ->permission('platform.safety-memos')
                ->divider(),

            Menu::make('Добыча нефти')
                ->icon('bs.reception-4')
                ->title('Добыча нефти')
                ->route('platform.extraction')
                ->permission('platform.extraction'),

            Menu::make('Ремонт скважин')
                ->icon('bs.tools')
                ->route('platform.remont-brigades')
                ->permission('platform.remont-brigades'),

            Menu::make('Планы ремонта (V2)')
                ->icon('bs.calendar-check')
                ->route('platform.remont-plans')
                ->permission('platform.remont-plans')
                ->divider(),

            Menu::make('Жировки')
                ->icon('bs.file-earmark-pdf')
                ->title('Жировки')
                ->route('platform.payroll-slip')
                ->permission('platform.payroll-slip')
                ->divider(),

           Menu::make('Типы материальной помощи')
               ->icon('bs.cash-coin')
               ->title('Материальная помощь')
               ->route('platform.financial-assistance.types')
               ->permission('platform.financial-assistance.types'),

//           Menu::make('Подписанты мат. помощи')
//               ->icon('bs.person-check')
//               ->route('platform.financial-assistance.signers')
//               ->permission('platform.financial-assistance.signers'),

           Menu::make('Заявки на мат. помощь')
               ->icon('bs.file-earmark-text')
               ->route('platform.financial-assistance.requests')
               ->permission('platform.financial-assistance.requests')
               ->divider(),

           Menu::make('Обращение | Вопросы')
               ->icon('bs.question-octagon-fill')
               ->title('Обращение | Вопросы')
               ->route('platform.appeal')
               ->permission('platform.appeal')
               ->divider(),


//            Menu::make('Sample Screen')
//                ->icon('bs.collection')
//                ->route('platform.example')
//                ->title('Example')
//                ->badge(fn () => 6),
////
//            Menu::make('Form Elements')
//                ->icon('bs.card-list')
//                ->route('platform.example.fields')
//                ->active('*/examples/form/*'),
//
//            Menu::make('Overview Layouts')
//                ->icon('bs.window-sidebar')
//                ->route('platform.example.layouts'),
//
//            Menu::make('Grid System')
//                ->icon('bs.columns-gap')
//                ->route('platform.example.grid'),
//
//            Menu::make('Charts')
//                ->icon('bs.bar-chart')
//                ->route('platform.example.charts'),
//
//            Menu::make('Cards')
//                ->icon('bs.card-text')
//                ->route('platform.example.cards')
//                ->divider(),

            Menu::make(__('Users'))
                ->icon('bs.people')
                ->route('platform.systems.users')
                ->permission('platform.systems.users')
                ->title(__('Access Controls')),

            Menu::make(__('Roles'))
                ->icon('bs.shield')
                ->route('platform.systems.roles')
                ->permission('platform.systems.roles')
                ->divider(),

            Menu::make('GlobalPages')
//                ->icon('bs.book-bell')
                ->route('platform.global-pages')
                ->permission('platform.systems.roles')
                ->divider(),

            Menu::make('Service Variable')
                ->icon('bs.book-bell')
                ->route('platform.service-variable')
                ->permission('platform.service-variable')
                ->divider(),

//            Menu::make('Documentation')
//                ->title('Docs')
//                ->icon('bs.box-arrow-up-right')
//                ->url('https://orchid.software/en/docs')
//                ->target('_blank'),
//
//            Menu::make('Changelog')
//                ->icon('bs.box-arrow-up-right')
//                ->url('https://github.com/orchidsoftware/platform/blob/master/CHANGELOG.md')
//                ->target('_blank')
//                ->badge(fn () => Dashboard::version(), Color::DARK),
        ];
    }

    /**
     * @return Dashboard[]
     */
    public function registerScreens(): array
    {
        return [
            SotrudnikiScreen::class,
            OneSotrudnikScreen::class,
            StatisticsScreen::class,
            StatisticsMonthDetailScreen::class,
        ];
    }

    /**
     * Register permissions for the application.
     *
     * @return ItemPermission[]
     */
    public function registerPermissions(): array
    {
        return [
            ItemPermission::group(__('System'))
                ->addPermission('platform.systems.roles', __('Roles'))
                ->addPermission('platform.systems.users', __('Users')),

            ItemPermission::group('Структура организации')
            ->addPermission('platform.organization.structure', 'Структура')
            ->addPermission('platform.positions', 'Должности')
            ->addPermission('platform.sotrudniki', 'Сотрудники')
            ->addPermission('platform.contacts', 'Контакты АУП'),

            ItemPermission::group('Департамент по связям с общественностью')
                ->addPermission('platform.news', 'Новости')
                ->addPermission('platform.stories', 'Сторисы')
                ->addPermission('platform.push-sotrudnikam', 'Уведомление для сотрудников')
                ->addPermission('platform.surveys', 'Опросы')
                ->addPermission('platform.idea', 'Банк идеи'),

            ItemPermission::group('Сервисы')
                ->addPermission('platform.pickup-point', 'Пункты выдачи молока')
                ->addPermission('platform.partner-places', 'Посещаемые места')
                ->addPermission('platform.loyalty-cards', 'Карты лояльности'),

            ItemPermission::group('Статистика')
                ->addPermission('platform.statistics', 'Статистика'),

            ItemPermission::group('Подписанты')
                ->addPermission('platform.organization-signers', 'Подписанты'),

            ItemPermission::group('Отдел кадров')
                ->addPermission('platform.spravka-sotrudnikam', 'Справка с места работы'),
//
//            ItemPermission::group('Карта промзоны')
//                ->addPermission('platform.promzona-map', 'Карта промзоны'),

            ItemPermission::group('Карта промзоны')
                ->addPermission('platform.promzona-geo-objects', 'Карта промзоны (new)'),

            ItemPermission::group('Корпоративный учебный центр')
                ->addPermission('platform.training-center', 'Корпоративный учебный центр')
                ->addPermission('platform.safety-memos', 'Памятки по тех. безопасности'),

            ItemPermission::group('КУЦ админ')
                ->addPermission('platform.training-center-admin', 'КУЦ админ'),

            ItemPermission::group('Добыча нефти')
                ->addPermission('platform.extraction', 'Добыча нефти')
                ->addPermission('platform.remont-brigades', 'Ремонт скважин')
                ->addPermission('platform.remont-plans', 'Планы ремонта (V2)'),

            ItemPermission::group('Жировки')
                ->addPermission('platform.payroll-slip', 'Жировки'),

            ItemPermission::group('Материальная помощь')
                ->addPermission('platform.financial-assistance.types', 'Типы материальной помощи')
                ->addPermission('platform.financial-assistance.signers', 'Подписанты материальной помощи')
                ->addPermission('platform.financial-assistance.requests', 'Заявки на материальную помощь'),

            ItemPermission::group('Обращение | Вопросы')
                ->addPermission('platform.appeal', 'Обращение | Вопросы'),

            ItemPermission::group('Часто задаваемое вопросы')
                ->addPermission('platform.faq', 'Часто задаваемое вопросы'),

//            ItemPermission::group('Часто задаваемое вопровы')
//                ->addPermission('platform.faq', 'Часто задаваемое вопровы'),

            ItemPermission::group('Сервисная переменная')
                ->addPermission('platform.service-variable', 'Сервисная переменная'),

        ];
    }
}
