<?php

namespace App\Http\Controllers\V1\Client;

use App\Http\Controllers\Controller;
use App\Protocols\General;
use App\Protocols\Singbox\Singbox;
use App\Protocols\Singbox\SingboxOld;
use App\Protocols\ClashMeta;
use App\Services\ServerService;
use App\Services\UserService;
use App\Utils\Helper;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    public function subscribe(Request $request)
    {
        $flag = $request->input('flag')
            ?? ($_SERVER['HTTP_USER_AGENT'] ?? '');
        $flag = strtolower($flag);
        $user = $request->user;
        // account not expired and is not banned.
        $userService = new UserService();
        if ($userService->isAvailable($user)) {
            $serverService = new ServerService();
            $servers = $serverService->getAvailableServers($user);
            if ($flag) {
                if (!strpos($flag, 'sing')) {
                    $this->setSubscribeInfoToServers($servers, $user);
                    foreach (array_reverse(glob(app_path('Protocols') . '/*.php')) as $file) {
                        $file = 'App\\Protocols\\' . basename($file, '.php');
                        $class = new $file($user, $servers);
                        if (strpos($flag, $class->flag) !== false) {
                            return $class->handle();
                        }
                    }
                }
                if (strpos($flag, 'sing') !== false) {
                    $version = null;
                    if (preg_match('/sing-box\s+([0-9.]+)/i', $flag, $matches)) {
                        $version = $matches[1];
                    }
                    if (!is_null($version) && $version >= '1.12.0') {
                        $class = new Singbox($user, $servers);
                    } else {
                        $class = new SingboxOld($user, $servers);
                    }
                    return $class->handle();
                }
            }
            $class = new General($user, $servers);
            return $class->handle();
        }
    }

    private function setSubscribeInfoToServers(&$servers, $user)
    {
        // 设置默认时区为上海
        date_default_timezone_set('Asia/Shanghai');

        if (!isset($servers[0])) return;
        if (!(int)config('v2board.show_info_to_server_enable', 0)) return;

        $useTraffic = $user['u'] + $user['d'];
        $totalTraffic = $user['transfer_enable'];
        $remainingTraffic = Helper::trafficConvert($totalTraffic - $useTraffic);

        // 计算剩余天数
        if ($user['expired_at']) {
            $remainingDays = ceil(($user['expired_at'] - time()) / 86400);
            $expiredDate = $remainingDays > 0 ? "套餐剩余 {$remainingDays} 天过期" : "已过期";
        } else {
            $recycleNotice = "超过六个月未使用自动回收全部流量";
            $expiredDate = '套餐长期有效';
        }

        $userService = new UserService();
        $resetDay = $userService->getResetDay($user);
        // 插入更新时间（自定义格式）
        array_unshift($servers, array_merge($servers[0], [
            'name' => "您在 " . ltrim(date('m'), '0') . '月' . ltrim(date('d'), '0') . '日 ' . date('H:i') . ' 更新了订阅',
        ]));

        if (isset($recycleNotice)) {
            array_unshift($servers, array_merge($servers[0], [
                'name' => $recycleNotice,
            ]));
        }
        array_unshift($servers, array_merge($servers[0], [
            'name' => "🇦🇶{$expiredDate}",
        ]));
        array_unshift($servers, array_merge($servers[0], [
            'name' => "🇦🇶流量剩余：{$remainingTraffic}",
        ]));
        array_unshift($servers, array_merge($servers[0], [
            'name' => "🇦🇶客服📮:ydtdcloud@gmail.com",
        ]));
        array_unshift($servers, array_merge($servers[0], [
            'name' => "🇦🇶官网: 云上部落.top",
        ]));
    }
}
