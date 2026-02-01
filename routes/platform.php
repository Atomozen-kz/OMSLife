<?php

declare(strict_types=1);

use App\Http\Controllers\DocumentController;
use App\Http\Controllers\DocumentSignController;
use App\Orchid\Screens\AppealScreen;
use App\Orchid\Screens\AppealViewScreen;
use App\Orchid\Screens\BankIdeaScreen;
use App\Orchid\Screens\BankIdeasScreen;
use App\Orchid\Screens\BrigadeReportsScreen;
use App\Orchid\Screens\EditOrAdd\GlobalPageEditOrAddScreen;
use App\Orchid\Screens\EditOrAdd\NewsEditOrAddScreen;
use App\Orchid\Screens\EditOrAdd\PushEditOrAddScreen;
use App\Orchid\Screens\Examples\ExampleActionsScreen;
use App\Orchid\Screens\Examples\ExampleCardsScreen;
use App\Orchid\Screens\Examples\ExampleChartsScreen;
use App\Orchid\Screens\Examples\ExampleFieldsAdvancedScreen;
use App\Orchid\Screens\Examples\ExampleFieldsScreen;
use App\Orchid\Screens\Examples\ExampleGridScreen;
use App\Orchid\Screens\Examples\ExampleLayoutsScreen;
use App\Orchid\Screens\Examples\ExampleScreen;
use App\Orchid\Screens\Examples\ExampleTextEditorsScreen;
use App\Orchid\Screens\ExtractionScreen;
use App\Orchid\Screens\FaqsCategoryScreen;
use App\Orchid\Screens\FinancialAssistanceTypeListScreen;
use App\Orchid\Screens\FinancialAssistanceTypeViewScreen;
use App\Orchid\Screens\FinancialAssistanceSignerListScreen;
use App\Orchid\Screens\FinancialAssistanceRequestListScreen;
use App\Orchid\Screens\FaqScreen;
use App\Orchid\Screens\GlobalPagesScreen;
use App\Orchid\Screens\LoyaltyCardListScreen;
use App\Orchid\Screens\NewsCategoryScreen;
use App\Orchid\Screens\NewsCommentsScreen;
use App\Orchid\Screens\NewsScreen;
use App\Orchid\Screens\OneSotrudnikScreen;
use App\Orchid\Screens\OrganizationSignersScreen;
use App\Orchid\Screens\OrganizationStructureScreen;
use App\Orchid\Screens\PayrollSlipScreen;
use App\Orchid\Screens\PdfPreviewScreen;
use App\Orchid\Screens\PdfViewScreen;
use App\Orchid\Screens\PickupPointScreen;
use App\Orchid\Screens\PlatformScreen;
use App\Orchid\Screens\PositionScreen;
use App\Orchid\Screens\PromzonaMapEditScreen;
use App\Orchid\Screens\PromzonaScreen;
use App\Orchid\Screens\PushSotrudnikamScreen;
use App\Orchid\Screens\Role\RoleEditScreen;
use App\Orchid\Screens\Role\RoleListScreen;
use App\Orchid\Screens\ServicesVarScreen;
use App\Orchid\Screens\SotrudnikiScreen;
use App\Orchid\Screens\SpravkaSotrudnikamDetailScreen;
use App\Orchid\Screens\SpravkaSotrudnikamScreen;
use App\Orchid\Screens\StoriesScreen;
use App\Orchid\Screens\SurveyListScreen;
use App\Orchid\Screens\SurveyQuestionScreen;
use App\Orchid\Screens\SurveyReportScreen;
use App\Orchid\Screens\TrainingCenterScreen;
use App\Orchid\Screens\User\UserEditScreen;
use App\Orchid\Screens\User\UserListScreen;
use App\Orchid\Screens\User\UserProfileScreen;
use App\Orchid\Screens\StatisticsScreen;
use App\Orchid\Screens\StatisticsMonthDetailScreen;
use App\Orchid\Screens\ContactScreen;
use Illuminate\Support\Facades\Route;
use Tabuna\Breadcrumbs\Trail;

/*
|--------------------------------------------------------------------------
| Dashboard Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the need "dashboard" middleware group. Now create something great!
|
*/


Route::screen('org_structure', OrganizationStructureScreen::class)->name('platform.organization.structure');
Route::screen('org_structure/remove', OrganizationStructureScreen::class)->name('platform.organization.structure.remove');

Route::screen('sotrudniki', SotrudnikiScreen::class)
    ->name('platform.sotrudniki');

Route::screen('sotrudnik/{sotrudnik}', OneSotrudnikScreen::class)
    ->name('platform.sotrudnik');

Route::screen('positions', PositionScreen::class)->name('platform.positions');
Route::screen('positions/createOrUpdatePosition', PositionScreen::class)->name('platform.createOrUpdatePosition');

Route::screen('contacts', ContactScreen::class)->name('platform.contacts');

Route::screen('news-category', NewsCategoryScreen::class)->name('platform.news-сategory');


Route::screen('news', NewsScreen::class)->name('platform.news');
Route::screen('news/editOrAdd/{news?}', NewsEditOrAddScreen::class)->name('platform.news.editOrAdd');
Route::screen('news-comments/{id_news}', NewsCommentsScreen::class)
    ->name('platform.news.comments');

Route::screen('stories', StoriesScreen::class)->name('platform.stories');

Route::screen('push', PushSotrudnikamScreen::class)->name('platform.push-sotrudnikam');
Route::screen('push/editOrAdd/{push?}', PushEditOrAddScreen::class)->name('platform.push.editOrAdd');

Route::screen('pickup-point', PickupPointScreen::class)->name('platform.pickup-point');

Route::screen('surveys-list', SurveyListScreen::class)->name('platform.surveys');
Route::screen('surveys-list/questions/{survey_id?}', SurveyQuestionScreen::class)->name('platform.survey.question');
Route::screen('surveys/report/{survey}', SurveyReportScreen::class)->name('platform.survey.report');

Route::screen('service-variable', ServicesVarScreen::class)->name('platform.service-variable');

Route::screen('organization-signers', OrganizationSignersScreen::class)->name('platform.organization-signers');

Route::screen('spravka-sotrudnikam', SpravkaSotrudnikamScreen::class)->name('platform.spravka-sotrudnikam');
Route::screen('spravka-sotrudnikam/{?success}', SpravkaSotrudnikamScreen::class)->name('platform.spravka-sotrudnikam-success');
Route::screen('pdf-preview/{spravka}', PdfPreviewScreen::class)->name('platform.pdf-preview');
Route::screen('pdf-view/{spravka}', PdfViewScreen::class)->name('platform.pdf-view');


Route::post('/generate-pdf', [DocumentController::class, 'generatePdf'])->name('generate.pdf');
Route::post('/save-signed-pdf', [DocumentController::class, 'saveSignedPdf'])->name('save-signed-pdf');
Route::post('/back-to-edit-spravka', [DocumentController::class, 'backToEdit'])->name('spravka.backToEdit');

Route::screen('spravka-sotrudnikam/detail/{spravka?}', SpravkaSotrudnikamDetailScreen::class)->name('platform.spravka-sotrudnikam-detail');

Route::get('/document/sign/{documentId}', [DocumentController::class, 'showSignForm'])->name('document.sign');
Route::post('/document/sign', [DocumentController::class, 'signDocument'])->name('document.sign.post');

Route::get('/document/sign_new/{documentId}', [DocumentSignController::class, 'showSignForm'])->name('document.sign.new');

//Route::screen('stories_category', StoriesCategoryScreen::class)->name('platform.stories-category');
Route::screen('/loyalty-cards', LoyaltyCardListScreen::class)->name('platform.loyalty-cards');
Route::screen('/promzona-map', PromzonaScreen::class)->name('platform.promzona-map');
Route::screen('/promzona-geo-objects', \App\Orchid\Screens\PromzonaGeoObjectsScreen::class)->name('platform.promzona-geo-objects');
Route::screen('/promzona-edit-point/{geoObject?}', PromzonaMapEditScreen::class)->name('platform.promzona-edit-point');
Route::post('/promzona-geo-objects/save', [PromzonaMapEditScreen::class, 'saveGeoObject']);
Route::get('/promzona-geo-objects/children', [PromzonaMapEditScreen::class, 'getChildren']);

Route::screen('/training-center', TrainingCenterScreen::class)->name('platform.training-center');

Route::screen('/payroll-slip', PayrollSlipScreen::class)->name('platform.payroll-slip');

Route::screen('/extraction', ExtractionScreen::class)->name('platform.extraction');

Route::screen('/faqCategory', FaqsCategoryScreen::class)->name('platform.faqs-category');
Route::screen('/faq/{id_category?}', FaqScreen::class)->name('platform.faq');

Route::screen('/appeal', AppealScreen::class)->name('platform.appeal');
Route::screen('/appeal/view/{appeal}', AppealViewScreen::class)->name('platform.appeal.view');
Route::post('/appeal/view/{appeal}/change-status', [AppealViewScreen::class, 'changeStatus'])->name('platform.appeal.change-status');
Route::post('/appeal/view/{appeal}/add-answer', [AppealViewScreen::class, 'addAnswer'])->name('platform.appeal.add-answer');
Route::post('/appeal/view/{appeal}/transfer', [AppealViewScreen::class, 'transferAppeal'])->name('platform.appeal.transfer');

// СИЗ (Средства индивидуальной защиты)
Route::screen('/siz-types', \App\Orchid\Screens\SizTypesScreen::class)->name('platform.siz.types');
Route::screen('/siz-inventory', \App\Orchid\Screens\SizInventoryScreen::class)->name('platform.siz.inventory');

// Main
Route::screen('/main', PlatformScreen::class)
    ->name('platform.main');

// Platform > Profile
Route::screen('profile', UserProfileScreen::class)
    ->name('platform.profile')
    ->breadcrumbs(fn (Trail $trail) => $trail
        ->parent('platform.index')
        ->push(__('Profile'), route('platform.profile')));

// Platform > System > Users > User
Route::screen('users/{user}/edit', UserEditScreen::class)
    ->name('platform.systems.users.edit')
    ->breadcrumbs(fn (Trail $trail, $user) => $trail
        ->parent('platform.systems.users')
        ->push($user->name, route('platform.systems.users.edit', $user)));

// Platform > System > Users > Create
Route::screen('users/create', UserEditScreen::class)
    ->name('platform.systems.users.create')
    ->breadcrumbs(fn (Trail $trail) => $trail
        ->parent('platform.systems.users')
        ->push(__('Create'), route('platform.systems.users.create')));

// Platform > System > Users
Route::screen('users', UserListScreen::class)
    ->name('platform.systems.users')
    ->breadcrumbs(fn (Trail $trail) => $trail
        ->parent('platform.index')
        ->push(__('Users'), route('platform.systems.users')));

// Platform > System > Roles > Role
Route::screen('roles/{role}/edit', RoleEditScreen::class)
    ->name('platform.systems.roles.edit')
    ->breadcrumbs(fn (Trail $trail, $role) => $trail
        ->parent('platform.systems.roles')
        ->push($role->name, route('platform.systems.roles.edit', $role)));

// Platform > System > Roles > Create
Route::screen('roles/create', RoleEditScreen::class)
    ->name('platform.systems.roles.create')
    ->breadcrumbs(fn (Trail $trail) => $trail
        ->parent('platform.systems.roles')
        ->push(__('Create'), route('platform.systems.roles.create')));

// Platform > System > Roles
Route::screen('roles', RoleListScreen::class)
    ->name('platform.systems.roles')
    ->breadcrumbs(fn (Trail $trail) => $trail
        ->parent('platform.index')
        ->push(__('Roles'), route('platform.systems.roles')));

// Example...
Route::screen('example', ExampleScreen::class)
    ->name('platform.example')
    ->breadcrumbs(fn (Trail $trail) => $trail
        ->parent('platform.index')
        ->push('Example Screen'));

Route::screen('/examples/form/fields', ExampleFieldsScreen::class)->name('platform.example.fields');
Route::screen('/examples/form/advanced', ExampleFieldsAdvancedScreen::class)->name('platform.example.advanced');
Route::screen('/examples/form/editors', ExampleTextEditorsScreen::class)->name('platform.example.editors');
Route::screen('/examples/form/actions', ExampleActionsScreen::class)->name('platform.example.actions');

Route::screen('/examples/layouts', ExampleLayoutsScreen::class)->name('platform.example.layouts');
Route::screen('/examples/grid', ExampleGridScreen::class)->name('platform.example.grid');
Route::screen('/examples/charts', ExampleChartsScreen::class)->name('platform.example.charts');
Route::screen('/examples/cards', ExampleCardsScreen::class)->name('platform.example.cards');

Route::screen('/idea', BankIdeasScreen::class,)->name('platform.screens.idea');
Route::screen('/idea/{bankIdea}', BankIdeaScreen::class)->name('platform.screens.idea.view');

// Маршрут для скачивания файла идеи
Route::get('/idea/file/{file}/download', [\App\Http\Controllers\BankIdeaFileController::class, 'download'])->name('platform.idea.file.download');

Route::screen('/globalPages', GlobalPagesScreen::class)->name('platform.global-pages');
Route::screen('/globalPages/editOrAdd/{data?}', GlobalPageEditOrAddScreen::class)->name('platform.global-pages.editOrAdd');
Route::post('/globalPages/removePage/{id}', [GlobalPagesScreen::class,'removePage']);

// Маршруты для управления темами обращений
Route::screen('/appeal/topics', \App\Orchid\Screens\AppealTopicsManagementScreen::class)->name('platform.appeal.topics');
Route::post('/appeal/topics/create', [\App\Orchid\Screens\AppealTopicsManagementScreen::class, 'createTopic'])->name('platform.appeal.topics.create');
Route::post('/appeal/topics/update', [\App\Orchid\Screens\AppealTopicsManagementScreen::class, 'updateTopic'])->name('platform.appeal.topics.update');
Route::post('/appeal/topics/assign', [\App\Orchid\Screens\AppealTopicsManagementScreen::class, 'assignUser'])->name('platform.appeal.topics.assign');
Route::post('/appeal/topics/remove-assignment', [\App\Orchid\Screens\AppealTopicsManagementScreen::class, 'removeAssignment'])->name('platform.appeal.topics.remove-assignment');

// Маршруты для материальной помощи
Route::screen('/financial-assistance/types', \App\Orchid\Screens\FinancialAssistanceTypeListScreen::class)->name('platform.financial-assistance.types');
Route::screen('/financial-assistance/types/{type}', \App\Orchid\Screens\FinancialAssistanceTypeViewScreen::class)->name('platform.financial-assistance.types.view');
Route::screen('/financial-assistance/types/{type}/add-field', \App\Orchid\Screens\FinancialAssistanceTypeAddFieldScreen::class)->name('platform.financial-assistance.types.add-field');
Route::screen('/financial-assistance/types/{type}/edit-field/{field}', \App\Orchid\Screens\FinancialAssistanceTypeEditFieldScreen::class)->name('platform.financial-assistance.types.edit-field');
Route::screen('/financial-assistance/types/{type}/edit-template', \App\Orchid\Screens\FinancialAssistanceTypeEditTemplateScreen::class)->name('platform.financial-assistance.types.edit-template');
Route::screen('/financial-assistance/signers', \App\Orchid\Screens\FinancialAssistanceSignerListScreen::class)->name('platform.financial-assistance.signers');
Route::screen('/financial-assistance/requests', \App\Orchid\Screens\FinancialAssistanceRequestListScreen::class)->name('platform.financial-assistance.requests');

// Роуты для просмотра HTML документов материальной помощи
Route::get('/financial-assistance/request/{request}/html', [\App\Http\Controllers\FinancialAssistanceController::class, 'showRequestHtml'])->name('platform.financial-assistance.request.html');
Route::get('/financial-assistance/request/{request}/pdf', [\App\Http\Controllers\FinancialAssistanceController::class, 'getOrGenerateRequestPdf'])->name('platform.financial-assistance.request.pdf');
Route::get('/financial-assistance/request/{request}/regenerate-pdf', [\App\Http\Controllers\FinancialAssistanceController::class, 'generateRequestPdf'])->name('platform.financial-assistance.request.regenerate-pdf');
Route::get('/financial-assistance/type/{type}/preview', [\App\Http\Controllers\FinancialAssistanceController::class, 'showTypePreview'])->name('platform.financial-assistance.type.preview');
Route::get('/financial-assistance/type/{type}/content-only', [\App\Http\Controllers\FinancialAssistanceController::class, 'showContentOnly'])->name('platform.financial-assistance.type.content-only');
Route::get('/financial-assistance/type/{type}/full-preview', [\App\Http\Controllers\FinancialAssistanceController::class, 'showFullPreview'])->name('platform.financial-assistance.type.full-preview');

// Роут для детального просмотра заявки
Route::screen('/financial-assistance/request/{request}/view', \App\Orchid\Screens\FinancialAssistanceRequestViewScreen::class)->name('platform.financial-assistance.request.view');

Route::screen('/statistics', StatisticsScreen::class)->name('platform.statistics');
Route::screen('/statistics/{month}', StatisticsMonthDetailScreen::class)->name('platform.statistics.month');

// Ремонт скважин - Цехи и Бригады
Route::screen('/remont-brigades', \App\Orchid\Screens\RemontBrigadesScreen::class)->name('platform.remont-brigades');
Route::screen('/remont-brigades/{month}', \App\Orchid\Screens\RemontBrigadesMonthDetailScreen::class)->name('platform.remont-brigades.month');

// Планы ремонта скважин (V2)
Route::screen('/remont-plans', \App\Orchid\Screens\RemontBrigadesPlanScreen::class)->name('platform.remont-plans');
Route::screen('/remont-plans/workshop/{workshop}', \App\Orchid\Screens\RemontBrigadesPlanWorkshopScreen::class)->name('platform.remont-plans.workshop');
Route::screen('/remont-plans/month/{month}', \App\Orchid\Screens\RemontBrigadesPlanMonthScreen::class)->name('platform.remont-plans.month');
Route::screen('/remont-plans/brigade/{brigade}', \App\Orchid\Screens\RemontBrigadeAllDataScreen::class)->name('platform.remont-plans.brigade');

// Посещаемые места (партнёры)
Route::screen('/partner-places', \App\Orchid\Screens\PartnerPlaceScreen::class)->name('platform.partner-places');

// Памятки по тех. безопасности
Route::screen('/safety-memos', \App\Orchid\Screens\SafetyMemoScreen::class)->name('platform.safety-memos');

// Сводки по бригадам
Route::screen('/brigade-reports', \App\Orchid\Screens\BrigadeReportsScreen::class)->name('platform.brigade-reports');

// Логистика и МТС
Route::screen('/logistics-documents', \App\Orchid\Screens\LogisticsDocumentsScreen::class)->name('platform.logistics-documents');

// Чек-листы для мастеров бригад
Route::screen('/brigade-checklist/items', \App\Orchid\Screens\BrigadeChecklistItemsScreen::class)->name('platform.brigade-checklist.items');
Route::screen('/brigade-checklist/masters', \App\Orchid\Screens\BrigadeMastersScreen::class)->name('platform.brigade-checklist.masters');
Route::screen('/brigade-checklist/responses', \App\Orchid\Screens\BrigadeChecklistResponsesScreen::class)->name('platform.brigade-checklist.responses');

// Детальная страница чек-листа (обычный маршрут вместо screen)
Route::get('/brigade-checklist/detail/{id}', [\App\Http\Controllers\BrigadeChecklistDetailController::class, 'show'])
    ->name('platform.brigade-checklist.session.detail');
Route::get('/brigade-checklist/detail/{id}/export-pdf', [\App\Http\Controllers\BrigadeChecklistDetailController::class, 'exportPdf'])
    ->name('platform.brigade-checklist.session.export-pdf');
