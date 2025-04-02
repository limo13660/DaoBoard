<?php

namespace App\Http\Controllers\V1\Client;

use App\Http\Controllers\Controller;
use App\Protocols\General;
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
            if (!strpos($flag, 'sing')) {
                $this->setSubscribeInfoToServers($servers, $user);
            }
            if ($flag) {
                foreach (array_reverse(glob(app_path('Protocols') . '/*.php')) as $file) {
                    $file = 'App\\Protocols\\' . basename($file, '.php');
                    $class = new $file($user, $servers);
                    if (strpos($flag, $class->flag) !== false) {
                        return $class->handle();
                    }
                }
            }
            $class = new General($user, $servers);
            return $class->handle();
        }
    }

    private function setSubscribeInfoToServers(&$servers, $user)

{
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
        $recycleNotice = "超过六个月未使用自动回收流量";
        $expiredDate = '套餐长期有效';
    }

    $userService = new UserService();
    $resetDay = $userService->getResetDay($user);

    // 按顺序插入，使最终数组顺序为：官网 > 客服 > 剩余流量 > 到期信息 > 回收提示（仅长期有效时）
    array_unshift($servers, array_merge($servers[0], [
        'name' => "官网: 云上部落.top",
    ]));
    array_unshift($servers, array_merge($servers[0], [
        'name' => "客服🐧: 1612633758",
    ]));
    array_unshift($servers, array_merge($servers[0], [
        'name' => "流量剩余：{$remainingTraffic}",
    ]));
    array_unshift($servers, array_merge($servers[0], [
        'name' => "{$expiredDate}",
    ]));

    // 如果是长期有效，则再添加一条流量回收提醒
    if (isset($recycleNotice)) {
        array_unshift($servers, array_merge($servers[0], [
            'name' => $recycleNotice,
        ]));
    }
}


}
