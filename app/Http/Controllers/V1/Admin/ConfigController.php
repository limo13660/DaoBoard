<?php

namespace App\Http\Controllers\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ConfigSave;
use App\Jobs\SendEmailJob;
use App\Services\TelegramService;
use App\Utils\Dict;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;

class ConfigController extends Controller
{
    public function getEmailTemplate()
    {
        $path = resource_path('views/mail/');
        $files = array_map(function ($item) use ($path) {
            return str_replace($path, '', $item);
        }, glob($path . '*'));
        return response([
            'data' => $files
        ]);
    }

    public function getThemeTemplate()
    {
        $path = public_path('theme/');
        $files = array_map(function ($item) use ($path) {
            return str_replace($path, '', $item);
        }, glob($path . '*'));
        return response([
            'data' => $files
        ]);
    }

    public function testSendMail(Request $request)
    {
        $obj = new SendEmailJob([
            'email' => $request->user['email'],
            'subject' => 'This is daoboard test email',
            'template_name' => 'notify',
            'template_value' => [
                'name' => config('daoboard.app_name', 'DaoBoard'),
                'content' => 'This is daoboard test email',
                'url' => config('daoboard.app_url')
            ]
        ]);
        return response([
            'data' => true,
            'log' => $obj->handle()
        ]);
    }

    public function setTelegramWebhook(Request $request)
    {
        $hookUrl = secure_url('/api/v1/guest/telegram/webhook?access_token=' . md5(config('daoboard.telegram_bot_token', $request->input('telegram_bot_token'))));
        $telegramService = new TelegramService($request->input('telegram_bot_token'));
        $telegramService->getMe();
        $telegramService->setWebhook($hookUrl);
        return response([
            'data' => true
        ]);
    }

    public function fetch(Request $request)
    {
        $key = $request->input('key');
        $data = [
            'ticket' => [
                'ticket_status' => config('daoboard.ticket_status', 0)
            ],
            'deposit' => [
                'deposit_bounus' => config('daoboard.deposit_bounus', [])
            ],
            'invite' => [
                'invite_force' => (int)config('daoboard.invite_force', 0),
                'invite_commission' => config('daoboard.invite_commission', 10),
                'invite_gen_limit' => config('daoboard.invite_gen_limit', 5),
                'invite_never_expire' => config('daoboard.invite_never_expire', 0),
                'commission_first_time_enable' => config('daoboard.commission_first_time_enable', 1),
                'commission_auto_check_enable' => config('daoboard.commission_auto_check_enable', 1),
                'commission_withdraw_limit' => config('daoboard.commission_withdraw_limit', 100),
                'commission_withdraw_method' => config('daoboard.commission_withdraw_method', Dict::WITHDRAW_METHOD_WHITELIST_DEFAULT),
                'withdraw_close_enable' => config('daoboard.withdraw_close_enable', 0),
                'commission_distribution_enable' => config('daoboard.commission_distribution_enable', 0),
                'commission_distribution_l1' => config('daoboard.commission_distribution_l1'),
                'commission_distribution_l2' => config('daoboard.commission_distribution_l2'),
                'commission_distribution_l3' => config('daoboard.commission_distribution_l3')
            ],
            'site' => [
                'logo' => config('daoboard.logo'),
                'force_https' => (int)config('daoboard.force_https', 0),
                'stop_register' => (int)config('daoboard.stop_register', 0),
                'app_name' => config('daoboard.app_name', 'DaoBoard'),
                'app_description' => config('daoboard.app_description', 'DaoBoard is best!'),
                'app_url' => config('daoboard.app_url'),
                'subscribe_url' => config('daoboard.subscribe_url'),
                'subscribe_path' => config('daoboard.subscribe_path'),
                'try_out_plan_id' => (int)config('daoboard.try_out_plan_id', 0),
                'try_out_hour' => (int)config('daoboard.try_out_hour', 1),
                'tos_url' => config('daoboard.tos_url'),
                'currency' => config('daoboard.currency', 'CNY'),
                'currency_symbol' => config('daoboard.currency_symbol', '¥'),
            ],
            'subscribe' => [
                'plan_change_enable' => (int)config('daoboard.plan_change_enable', 1),
                'reset_traffic_method' => (int)config('daoboard.reset_traffic_method', 0),
                'surplus_enable' => (int)config('daoboard.surplus_enable', 1),
                'new_order_event_id' => (int)config('daoboard.new_order_event_id', 0),
                'renew_order_event_id' => (int)config('daoboard.renew_order_event_id', 0),
                'change_order_event_id' => (int)config('daoboard.change_order_event_id', 0),
                'show_info_to_server_enable' => (int)config('daoboard.show_info_to_server_enable', 0)
            ],
            'frontend' => [
                'frontend_theme' => config('daoboard.frontend_theme', 'daoboard'),
                'frontend_theme_sidebar' => config('daoboard.frontend_theme_sidebar', 'light'),
                'frontend_theme_header' => config('daoboard.frontend_theme_header', 'dark'),
                'frontend_theme_color' => config('daoboard.frontend_theme_color', 'default'),
                'frontend_background_url' => config('daoboard.frontend_background_url'),
            ],
            'server' => [
                'server_token' => config('daoboard.server_token'),
                'server_pull_interval' => config('daoboard.server_pull_interval', 60),
                'server_push_interval' => config('daoboard.server_push_interval', 60),
                'device_limit_mode' => config('daoboard.device_limit_mode', 0)
            ],
            'email' => [
                'email_template' => config('daoboard.email_template', 'default'),
                'email_host' => config('daoboard.email_host'),
                'email_port' => config('daoboard.email_port'),
                'email_username' => config('daoboard.email_username'),
                'email_password' => config('daoboard.email_password'),
                'email_encryption' => config('daoboard.email_encryption'),
                'email_from_address' => config('daoboard.email_from_address')
            ],
            'telegram' => [
                'telegram_bot_enable' => config('daoboard.telegram_bot_enable', 0),
                'telegram_bot_token' => config('daoboard.telegram_bot_token'),
                'telegram_discuss_link' => config('daoboard.telegram_discuss_link')
            ],
            'app' => [
                'windows_version' => config('daoboard.windows_version'),
                'windows_download_url' => config('daoboard.windows_download_url'),
                'macos_version' => config('daoboard.macos_version'),
                'macos_download_url' => config('daoboard.macos_download_url'),
                'android_version' => config('daoboard.android_version'),
                'android_download_url' => config('daoboard.android_download_url')
            ],
            'safe' => [
                'email_verify' => (int)config('daoboard.email_verify', 0),
                'safe_mode_enable' => (int)config('daoboard.safe_mode_enable', 0),
                'secure_path' => config('daoboard.secure_path', config('daoboard.frontend_admin_path', hash('crc32b', config('app.key')))),
                'email_whitelist_enable' => (int)config('daoboard.email_whitelist_enable', 0),
                'email_whitelist_suffix' => config('daoboard.email_whitelist_suffix', Dict::EMAIL_WHITELIST_SUFFIX_DEFAULT),
                'email_gmail_limit_enable' => config('daoboard.email_gmail_limit_enable', 0),
                'recaptcha_enable' => (int)config('daoboard.recaptcha_enable', 0),
                'recaptcha_key' => config('daoboard.recaptcha_key'),
                'recaptcha_site_key' => config('daoboard.recaptcha_site_key'),
                'register_limit_by_ip_enable' => (int)config('daoboard.register_limit_by_ip_enable', 0),
                'register_limit_count' => config('daoboard.register_limit_count', 3),
                'register_limit_expire' => config('daoboard.register_limit_expire', 60),
                'password_limit_enable' => (int)config('daoboard.password_limit_enable', 1),
                'password_limit_count' => config('daoboard.password_limit_count', 5),
                'password_limit_expire' => config('daoboard.password_limit_expire', 60)
            ]
        ];
        if ($key && isset($data[$key])) {
            return response([
                'data' => [
                    $key => $data[$key]
                ]
            ]);
        };
        // TODO: default should be in Dict
        return response([
            'data' => $data
        ]);
    }

    public function save(ConfigSave $request)
    {
        $data = $request->validated();
        $config = config('daoboard');
        foreach (ConfigSave::RULES as $k => $v) {
            if (!in_array($k, array_keys(ConfigSave::RULES))) {
                unset($config[$k]);
                continue;
            }
            if (array_key_exists($k, $data)) {
                $config[$k] = $data[$k];
            }
        }
        $data = var_export($config, 1);
        if (!File::put(base_path() . '/config/daoboard.php', "<?php\n return $data ;")) {
            abort(500, '修改失败');
        }
        if (function_exists('opcache_reset')) {
            if (opcache_reset() === false) {
                abort(500, '缓存清除失败，请卸载或检查opcache配置状态');
            }
        }
        Artisan::call('config:cache');
        if(Cache::has('WEBMANPID')) {
            $pid = Cache::get('WEBMANPID');
            Cache::forget('WEBMANPID');
            return response([
                'data' => posix_kill($pid, 15)
            ]);
        }
        return response([
            'data' => true
        ]);
    }
}
