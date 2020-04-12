<?php
namespace App\Middleware;

use Cake\I18n\Time;
use Cake\ORM\TableRegistry;

class CheckIpMiddleware
{
    public function __invoke($request, $response, $next)
    {   
        $securityTbl = TableRegistry::get('security_ip_check');
        $securityData = $securityTbl->find('all')->where(['ip_address' => $request->clientIp()])->first();

        if(!empty($securityData)){
            if($securityData['hit_count'] >= 10){
                $this->throwError(401, "Your Ip address is blocked. Please email us at ".EMAIL_SENT_FROM." for getting access again.");     
            }
        }
        $response = $next($request, $response);
        return $response;
    }

    public function throwError($code , $message){
        http_response_code($code);
        $errorMessage = json_encode(['message' => $message]);
        echo $errorMessage;
        exit;
    }
}