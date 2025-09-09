<?php
// Inicia a sessão no ponto de entrada único
session_start();

// Define uma constante para prevenir acesso direto
define('SYSTEM_LOADED', true);

// Carrega configurações e dependências
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/config.php';

// Carrega o core
require_once __DIR__ . '/../app/core/Router.php';
require_once __DIR__ . '/../app/core/Request.php';
require_once __DIR__ . '/../app/core/helpers.php';

$router = new Router();

/*
|--------------------------------------------------------------------------
| Rotas de Autenticação e Cadastro Público
|--------------------------------------------------------------------------
*/
$router->get('login', 'AuthController@index');
$router->post('login/auth', 'AuthController@auth');
$router->get('logout', 'AuthController@logout');

$router->get('register', 'UserController@create');     // Página de cadastro
$router->post('register/store', 'UserController@store'); // Salvar novo usuário

/*
|--------------------------------------------------------------------------
| Rotas principais (dashboard)
|--------------------------------------------------------------------------
*/
$router->get('/', 'DashboardController@index'); 
$router->get('dashboard', 'DashboardController@index');

/*
|--------------------------------------------------------------------------
| Rotas de Gestão de Usuários (Admin)
|--------------------------------------------------------------------------
*/
$router->get('users/create', 'UserController@create'); 
$router->post('users/store', 'UserController@store');

/*
|--------------------------------------------------------------------------
| Rotas de Diário de Bordo (Runs)
|--------------------------------------------------------------------------
*/
// Fluxo principal de corrida
$router->get('runs/new', 'DiarioBordoController@create');
$router->post('runs/select-vehicle', 'DiarioBordoController@selectVehicle');

$router->get('runs/checklist', 'DiarioBordoController@checklist');
$router->post('runs/checklist/store', 'DiarioBordoController@storeChecklist');

$router->get('runs/start', 'DiarioBordoController@start');
$router->post('runs/start/store', 'DiarioBordoController@storeStart');

$router->get('runs/finish', 'DiarioBordoController@finish');
$router->post('runs/finish/store', 'DiarioBordoController@storeFinish');

// Relatórios e histórico
$router->get('runs/history', 'DiarioBordoController@history');
$router->get('runs/reports/generate', 'DiarioBordoController@generatePdfReport');

// Ajax / operações auxiliares
$router->post('runs/ajax-get-vehicle', 'DiarioBordoController@ajax_get_vehicle');
$router->post('runs/ajax-get-fuels', 'DiarioBordoController@ajax_get_fuels_by_station');
$router->post('runs/fueling/store', 'DiarioBordoController@storeFueling');

/*
|--------------------------------------------------------------------------
| Rotas do Gestor Setorial
|--------------------------------------------------------------------------
*/
// CRUD de usuários
$router->get('sector-manager/users/create', 'SectorManagerController@createUser');
$router->post('sector-manager/users/store', 'SectorManagerController@storeUser');
$router->get('sector-manager/users/manage', 'SectorManagerController@manageUsers');
$router->post('sector-manager/users/update', 'SectorManagerController@updateUser');
$router->post('sector-manager/users/reset-password', 'SectorManagerController@resetUserPassword');
$router->post('sector-manager/users/delete', 'SectorManagerController@deleteUser');

// Gerenciamento de Veículos
$router->get('sector-manager/vehicles', 'SectorManagerController@manageVehicles');
$router->post('sector-manager/vehicles/store', 'SectorManagerController@storeVehicle');
$router->get('sector-manager/ajax/search-vehicles', 'SectorManagerController@ajax_search_vehicles');
$router->post('sector-manager/ajax/get-vehicle', 'SectorManagerController@ajax_get_vehicle');
$router->post('sector-manager/vehicles/update', 'SectorManagerController@updateVehicle');
$router->post('sector-manager/vehicles/delete', 'SectorManagerController@deleteVehicle');
$router->get('sector-manager/vehicles/history', 'SectorManagerController@vehicleHistory');
// ROTA PARA SALVAR CATEGORIA DE VEÍCULO (Admin)
$router->post('sector-manager/ajax/store-category', 'SectorManagerController@ajax_store_category');
$router->post('sector-manager/ajax/update-category', 'SectorManagerController@ajax_update_category'); 
$router->post('sector-manager/ajax/delete-category', 'SectorManagerController@ajax_delete_category'); 


// Histórico e listagem
$router->get('sector-manager/users', 'SectorManagerController@listUsers'); // retrocompatibilidade
$router->get('sector-manager/users/history', 'SectorManagerController@history');
$router->get('sector-manager/history', 'SectorManagerController@history');

// Gerenciamento de Corridas e Abastecimentos
$router->get('sector-manager/records', 'RecordController@index');
$router->get('sector-manager/records/run/history', 'RecordController@runHistory');

// Endpoints AJAX para buscas e obtenção de dados
$router->get('sector-manager/ajax/search-runs', 'RecordController@ajax_search_runs');
$router->post('sector-manager/ajax/get-run', 'RecordController@ajax_get_run');
$router->get('sector-manager/ajax/search-drivers', 'RecordController@ajax_search_drivers');
// Rota específica para buscar veículos na tela de registros de corrida
$router->get('sector-manager/ajax/search-vehicles-for-run', 'RecordController@ajax_search_vehicles');

// <-- ADICIONE ESTA LINHA PARA AUTOCOMPLETE DE "ASSOCIAR CORRIDA" -->
$router->get('sector-manager/ajax/search-runs-for-fueling', 'RecordController@ajax_search_runs_for_fueling');

// Ações de CRUD para Corridas
$router->post('sector-manager/records/run/store', 'RecordController@storeRun');
$router->post('sector-manager/records/run/update', 'RecordController@updateRun');
$router->post('sector-manager/records/run/delete', 'RecordController@deleteRun');

//ROTAS DE EDIÇÃO DE ABASTECIMENTOS 
// Ações de CRUD para Abastecimentos
$router->post('sector-manager/records/fueling/store', 'RecordController@storeFueling');
$router->post('sector-manager/records/fueling/update', 'RecordController@updateFueling');
$router->post('sector-manager/records/fueling/delete', 'RecordController@deleteFueling');

// Endpoints AJAX para Abastecimentos
$router->get('sector-manager/ajax/search-fuelings', 'RecordController@ajax_search_fuelings');
$router->post('sector-manager/ajax/get-fueling', 'RecordController@ajax_get_fueling');

// Rota para o histórico de registros
$router->get('sector-manager/records/history', 'RecordController@recordsHistory');
//EDIÇÃO DE ABASTEICMENTO ACIMA
$router->post('sector-manager/ajax/get-fuel-price', 'RecordController@ajax_get_fuel_price');

//ROTAS DE RELATORIO PARA GESTOR SETORIAL
$router->get('sector-manager/reports', 'ReportController@index');
$router->get('sector-manager/reports/generate', 'ReportController@generatePdfReport');


// Ajax para gestão de usuários
$router->post('sector-manager/ajax/get-user', 'SectorManagerController@ajax_get_user');
$router->get('sector-manager/ajax/search-users', 'SectorManagerController@ajax_search_users');

// Notificações
$router->get('sector-manager/notifications', 'NotificationController@index');
$router->post('sector-manager/notifications/process', 'NotificationController@process');
//VER DETALHE DAS NOTIFICAÇÕES DO CECKLIST
$router->get('admin/notification/detail', 'NotificationController@show'); 


/*
|--------------------------------------------------------------------------
| Rotas do Chat
|--------------------------------------------------------------------------
*/
$router->get('chat', 'ChatController@index');

// Rotas da API para o chat (serão chamadas pelo JavaScript)
$router->get('chat/api/conversations', 'ChatController@ajax_get_conversations');
$router->get('chat/api/messages', 'ChatController@ajax_get_messages');
$router->post('chat/api/send-message', 'ChatController@ajax_send_message');

// --- ROTAS ADICIONADAS ---
$router->get('chat/api/templates', 'ChatController@api_templates');
$router->post('chat/api/save-template', 'ChatController@api_save_template');
$router->get('chat/api/delete-template', 'ChatController@api_delete_template');
$router->post('chat/api/schedule-message', 'ChatController@api_schedule_message');

/*
|--------------------------------------------------------------------------
| Rotas de Admin (Estruturas)
|--------------------------------------------------------------------------
*/
$router->get('admin/structure', 'StructureController@index');
$router->get('admin/structure/ajax_get_structures', 'StructureController@ajax_get_structures');
$router->post('admin/structure/secretariat/store', 'StructureController@storeSecretariat');
$router->post('admin/structure/department/store', 'StructureController@storeDepartment');
$router->post('admin/structure/secretariat/update', 'StructureController@updateSecretariat');
$router->post('admin/structure/department/update', 'StructureController@updateDepartment');
$router->post('admin/structure/secretariat/delete', 'StructureController@deleteSecretariat');
$router->post('admin/structure/department/delete', 'StructureController@deleteDepartment');


$router->get('admin/structure/history', 'StructureController@history');
$router->get('admin/structure/ajax_get_structures', 'StructureController@ajax_get_structures');
$router->post('admin/structure/secretariat/store', 'StructureController@storeSecretariat');


/*
|--------------------------------------------------------------------------
| Rotas de Transferência de Veículos
|--------------------------------------------------------------------------
*/
$router->get('transfers', 'VehicleTransferController@index');
$router->get('transfers/ajax/search-vehicles', 'VehicleTransferController@ajax_search_vehicles'); // Rota para o autocomplete
$router->post('transfers/store', 'VehicleTransferController@store');

//ROTAS PARA APROVAR E REJEITAR
$router->post('transfers/approve', 'VehicleTransferController@approve');
$router->post('transfers/reject', 'VehicleTransferController@reject');

$router->get('transfers/history', 'VehicleTransferController@history'); // Rota para o histórico
$router->post('transfers/return', 'VehicleTransferController@returnVehicle'); // Rota para devolver o veículo
$router->get('transfers/ajax/get-ongoing', 'VehicleTransferController@ajax_get_ongoing_transfers'); // Rota para buscar empréstimos ativos
$router->get('transfers/ajax/get-pending', 'VehicleTransferController@ajax_get_pending_transfers'); // Rota para buscar pendentes

/*
|--------------------------------------------------------------------------
| Rotas de Perfil do Usuário
|--------------------------------------------------------------------------
*/
$router->get('profile', 'ProfileController@index');
$router->post('profile/update', 'ProfileController@update');
$router->post('profile/change-password', 'ProfileController@changePassword');


/*
|--------------------------------------------------------------------------
| Rotas VEICULO EM USO
|--------------------------------------------------------------------------
*/

// Rota para a nova página de status de veículos
$router->get('sector-manager/vehicles/status', 'VehicleStatusController@index');

// Rota para a ação de forçar o encerramento de uma corrida
$router->post('sector-manager/vehicles/force-end-run', 'VehicleStatusController@forceEndRun');

$router->get('sector-manager/vehicles/ajax-search-status', 'VehicleStatusController@ajax_search_status');
$router->get('sector-manager/vehicles/status/history', 'VehicleStatusController@history');

// ROTA PARA ANÁLISE DE COMBUSTÍVEL
$router->get('sector-manager/reports/fuel-analysis', 'FuelReportController@index');
$router->get('profile', 'ProfileController@index');

/*
|--------------------------------------------------------------------------
| Rotas de Controle de Troca de Óleo
|--------------------------------------------------------------------------
*/

$router->get('sector-manager/oil-change', 'OilChangeController@index');
$router->get('sector-manager/oil-change/ajax_get_vehicles', 'OilChangeController@ajax_get_vehicles');
$router->post('sector-manager/oil-change/store', 'OilChangeController@store');
$router->get('sector-manager/oil-stock', 'OilChangeController@stock'); 
$router->get('sector-manager/oil-change/history', 'OilChangeController@history'); // Rota para o histórico


// ROTAS PARA O CRUD DE ESTOQUE
$router->post('sector-manager/oil-stock/store', 'OilChangeController@store_product');
$router->get('sector-manager/oil-stock/get', 'OilChangeController@ajax_get_product');
$router->post('sector-manager/oil-stock/delete', 'OilChangeController@delete_product');

$router->get('sector-manager/oil-stock', 'OilStockController@index');
$router->post('sector-manager/oil-stock/store', 'OilStockController@store');

$router->post('sector-manager/oil-stock/delete', 'OilStockController@delete');
$router->get('sector-manager/categories', 'VehicleCategoryController@index');
$router->post('sector-manager/categories/store', 'VehicleCategoryController@store');
$router->post('sector-manager/categories/delete', 'VehicleCategoryController@delete');
// Adicionar esta rota junto com as outras de 'sector-manager'
$router->get('sector-manager/oil-change/get-intervals', 'OilChangeController@getCategoryIntervals');

$router->post('sector-manager/oil-change/update-interval', 'OilChangeController@updateChangeInterval');
// Adicionado para troca de óleo baseada em categoria
$router->get('/sector-manager/oil-change/get-category-intervals', 'OilChangeController@getCategoryIntervals');

/*
|--------------------------------------------------------------------------
| Rotas de Configurações (Apenas Admin Geral)
|--------------------------------------------------------------------------
*/
$router->get('admin/settings', 'AdminSettingsController@index');
$router->post('admin/settings/update', 'AdminSettingsController@update');


/*
|--------------------------------------------------------------------------
| Rotas do Módulo de Pneus
|--------------------------------------------------------------------------
*/
// Páginas principais do módulo
$router->get('tires/dashboard', 'TireController@dashboard');
$router->get('tires/stock', 'TireController@stock');
$router->get('tires/settings', 'TireController@settings');

// Ações (Formulários POST)
$router->post('tires/stock/store', 'TireController@storeTireInStock');
$router->post('tires/settings/store', 'TireController@storeSettings');

// Endpoints AJAX para interatividade da interface
$router->post('tires/ajax/get-vehicle-layout', 'TireController@ajax_get_vehicle_layout');
$router->post('tires/ajax/perform-action', 'TireController@ajax_perform_action');
$router->get('tires/ajax/get-layouts', 'TireController@ajax_get_layouts');
$router->post('tires/ajax/store-layout', 'TireController@ajax_store_layout');
$router->post('tires/ajax/delete-layout', 'TireController@ajax_delete_layout');
$router->post('tires/ajax/update-category-layout', 'TireController@ajax_update_category_layout');
$router->get('tires/ajax_get_rule_details', 'TireController@ajax_get_rule_details');

/*
|--------------------------------------------------------------------------
| Processamento da requisição
|--------------------------------------------------------------------------
*/
$router->dispatch(new Request());