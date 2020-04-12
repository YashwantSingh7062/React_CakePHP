<?php
namespace App\Middleware;

use Cake\I18n\Time;
use \Firebase\JWT\JWT;
use Cake\ORM\TableRegistry;

class JwtAuthMiddleware
{
    public function __invoke($request, $response, $next)
    {   
        /*
        $check = $this->allow($request,[
            ["controller" => "Users","action" => "login"],
            ["controller" => "Users", "action" => "signup"],
            ["controller" => "Users", "action" => "forgotPassword"],
            ["controller" => "Users", "action" => "verifyAccount"],
            ["controller" => "Users", "action" => "resendOtp"],
            ["controller" => "Users", "action" => "resetPassword"],

        ],["Error"]);

        */
        try{
            $token = $this->getBearerToken();
            $decoded = JWT::decode($token, JWT_SECRET_KEY, array('HS256'));
            $userData = $this->validateTokenData($decoded->user_id);
            $request->data['user'] = $userData;
            $response = $next($request, $response);
            return $response;
        }
        catch(\Exception $e){
            $this->throwError(401, $e->getMessage());
        }
    }

    /*
        // If you want to use the allow function implementation 
        function allow($request, $routesArray, $controllerAccess = []){
            $controller = $request->params['controller'];
            $action = $request->params['action'];
            foreach($routesArray as $routes){
                if($controller == $routes['controller'] && $action == $routes['action']){
                    return false;
                }
            }
            if(!empty($controllerAccess)){
                foreach($controllerAccess as $controllerCheck){
                    if($controller == $controllerCheck){
                        return false;
                    }
                }
            }
            if(!isset($request->params['prefix']) || $request->params['prefix'] != "api/v1"){
                return false;
            }
            return true;
        }
    */
    function getAuthorizationHeader(){
        $headers = null;
        if (isset($_SERVER['Authorization'])) {
            $headers = trim($_SERVER["Authorization"]);
        }
        else if (isset($_SERVER['HTTP_AUTHORIZATION'])) { //Nginx or fast CGI
            $headers = trim($_SERVER["HTTP_AUTHORIZATION"]);
        } elseif (function_exists('apache_request_headers')) {
            $requestHeaders = apache_request_headers();
            // Server-side fix for bug in old Android versions (a nice side-effect of this fix means we don't care about capitalization for Authorization)
            $requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
            //print_r($requestHeaders);
            if (isset($requestHeaders['Authorization'])) {
                $headers = trim($requestHeaders['Authorization']);
            }
        }
        return $headers;
    }
    
    function getBearerToken() {
        $headers = $this->getAuthorizationHeader();
        // HEADER: Get the access token from the header
        if (!empty($headers)) {
            if (preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
                return $matches[1];
            }
        }
        $this->throwError(401,"Access Denied");
    }

    function validateTokenData($user_id){
        $userTbl = TableRegistry::get('users_old');
        $userData = $userTbl->find('all')->where(['id' => $user_id])->first();

        if(empty($userData)){
            $this->throwError(500,"User not found. Try to login again.");
        }

        if($userData['status'] == INACTIVE){
            $this->throwError(401,"Your account is deactive by admin. Please email us at ".EMAIL_SENT_FROM);
        }

        return $userData;
    }

    public function throwError($code , $message){
        http_response_code($code);
        $errorMessage = json_encode(['message' => $message]);
        echo $errorMessage;
        exit;
    }
}