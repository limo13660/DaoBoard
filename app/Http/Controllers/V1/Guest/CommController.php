<?php

namespace App\Http\Controllers\V1\Guest;

use App\Http\Controllers\Controller;
use App\Utils\Dict;
use Illuminate\Support\Facades\Http;

class CommController extends Controller
{
    public function config()
    {
        return response([
            'data' => [
                'tos_url' => config('daoboard.tos_url'),
                'is_email_verify' => (int)config('daoboard.email_verify', 0) ? 1 : 0,
                'is_invite_force' => (int)config('daoboard.invite_force', 0) ? 1 : 0,
                'email_whitelist_suffix' => (int)config('daoboard.email_whitelist_enable', 0)
                    ? $this->getEmailSuffix()
                    : 0,
                'is_recaptcha' => (int)config('daoboard.recaptcha_enable', 0) ? 1 : 0,
                'recaptcha_site_key' => config('daoboard.recaptcha_site_key'),
                'app_description' => config('daoboard.app_description'),
                'app_url' => config('daoboard.app_url'),
                'logo' => config('daoboard.logo'),
            ]
        ]);
    }

    private function getEmailSuffix()
    {
        $suffix = config('daoboard.email_whitelist_suffix', Dict::EMAIL_WHITELIST_SUFFIX_DEFAULT);
        if (!is_array($suffix)) {
            return preg_split('/,/', $suffix);
        }
        return $suffix;
    }
}
