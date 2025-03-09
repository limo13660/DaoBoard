<?php
namespace App\Http\Routes\V1;

use Illuminate\Contracts\Routing\Registrar;

class MoetorRoute
{
    public function map(Registrar $router)
    {
        $router->group([
                'prefix' => 'moetor'
            ], function ($router) {
            
            $router->get('/config', 'V1\\Moetor\\MoetorController@get');
        });
    }
}