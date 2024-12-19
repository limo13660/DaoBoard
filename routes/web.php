<?php

use App\Services\ThemeService;
use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function (Request $request) {
    if (config('daoboard.app_url') && config('daoboard.safe_mode_enable', 0)) {
        if ($request->server('HTTP_HOST') !== parse_url(config('daoboard.app_url'))['host']) {
            abort(403);
        }
    }
    $renderParams = [
        'title' => config('daoboard.app_name', 'DaoBoard'),
        'theme' => config('daoboard.frontend_theme', 'default'),
        'version' => config('app.version'),
        'description' => config('daoboard.app_description', 'DaoBoard is best'),
        'logo' => config('daoboard.logo')
    ];

    if (!config("theme.{$renderParams['theme']}")) {
        $themeService = new ThemeService($renderParams['theme']);
        $themeService->init();
    }

    $renderParams['theme_config'] = config('theme.' . config('daoboard.frontend_theme', 'default'));
    return view('theme::' . config('daoboard.frontend_theme', 'default') . '.dashboard', $renderParams);
});

//TODO:: 兼容
Route::get('/' . config('daoboard.secure_path', config('daoboard.frontend_admin_path', hash('crc32b', config('app.key')))), function () {
    return view('admin', [
        'title' => config('daoboard.app_name', 'DaoBoard'),
        'theme_sidebar' => config('daoboard.frontend_theme_sidebar', 'light'),
        'theme_header' => config('daoboard.frontend_theme_header', 'dark'),
        'theme_color' => config('daoboard.frontend_theme_color', 'default'),
        'background_url' => config('daoboard.frontend_background_url'),
        'version' => config('app.version'),
        'logo' => config('daoboard.logo'),
        'secure_path' => config('daoboard.secure_path', config('daoboard.frontend_admin_path', hash('crc32b', config('app.key'))))
    ]);
});

if (!empty(config('daoboard.subscribe_path'))) {
    Route::get(config('daoboard.subscribe_path'), 'V1\\Client\\ClientController@subscribe')->middleware('client');
}