<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link      http://cakephp.org CakePHP(tm) Project
 * @since     0.2.9
 * @license   http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace App\Controller\Api\V1;

use Cake\Controller\Controller;
use Cake\Event\Event;
use Cake\Network\Email\Email;
use Cake\Auth\DefaultPasswordHasher;
use Cake\ORM\TableRegistry;

// use Cake\Core\Configure;
// use Cake\ORM\TableRegistry;



/**
 * Application Controller
 *
 * Add your application-wide methods in the class below, your controllers
 * will inherit them.
 *
 * @link http://book.cakephp.org/3.0/en/controllers.html#the-app-controller
 */
class AppController extends Controller
{

    /**
     * Initialization hook method.
     *
     * Use this method to add common initialization code like loading components.
     *
     * e.g. `$this->loadComponent('Security');`
     *
     * @return void
     */
    public function initialize()
    {
        parent::initialize();
		$this->loadComponent('Flash');


		// $dataList = TableRegistry::get('configs');
        // $data = $dataList->find('all')->toArray();
        
        // foreach($data as $key => $value )
        // {
        //     Configure::write('App.'.$value->slug,$value->value);
        // }

    }
    
    public function sendMail($to, $subject, $message, $from = null){
		$email = new Email();
		$email->transport('default');
		$result = $email->from([EMAIL_SENT_FROM => $subject])
			->to($to)
			->emailFormat('html')
			->subject($subject)
			->send($message);
			
			/* debug($result);exit; */
		return $result;
	}

    public function validateParameters($fieldName, $value, $dataType, $validation = [], $required = true){
        // Required Field Validation
        if($required == true && empty($value)){
            $this->throwError(400,"$fieldName is required");
        }

        // DataType Validation
        switch($dataType){
            case STRING : 
                if(!is_string($value)){
                    $this->throwError(400,"$fieldName should be of string type.");
                }
                break;

            case INTEGER : 
                if(!is_numeric($value)){
                    $this->throwError(400,"$fieldName should be of integer type.");
                }
                break;
            
            case BOOLEAN : 
                if(!is_bool($value)){
                    $this->throwError(400,"$fieldName should be of boolean type.");
                }
                break;

            case FLOAT : 
                if(!is_float($value)){
                    $this->throwError(400,"$fieldName should be of float type.");
                }
                break;

            default :  $this->throwError(400,"Unknown type is passed for $fieldName");
        }
        
        // Additional Validations
        if(count($validation) > 0){
            foreach($validation as $validationKey => $validationValue){
                if($validationKey == "min"){
                    if(strlen($value) < $validationValue){
                        $this->throwError(400,"$fieldName should be more than $validationValue characters.");
                    }
                }

                if($validationKey == "max"){
                    if(strlen($value) > $validationValue){
                        $this->throwError(400,"$fieldName should be less than $validationValue characters.");
                    }
                }

                if($validationKey == "type"){
                    if($validationValue == "password"){
                        if(strlen($value) < 8){
                            $this->throwError(400,"$fieldName should be more than 8 characters.");
                        }
                        $hasher = new DefaultPasswordHasher();
                        $value = $hasher->hash($value);
                    }

                    if($validationValue == "email"){
                        // Remove all illegal characters from email
                        $email = filter_var($value, FILTER_SANITIZE_EMAIL);

                        // Validate e-mail
                        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
                            $this->throwError(400,"$fieldName is not a valid email address.");
                        }
                    }
                }
            }
        }

        return $value;
    }

    public function throwError($code , $message){
        http_response_code($code);
        $errorMessage = json_encode(['message' => $message]);
        echo $errorMessage;
        exit;
    }

    public function sendResponse($code, $message, $data = [], $extra_data = []){
        http_response_code($code);
        $response['message'] = $message;
        if(count($extra_data) > 0){
            foreach($extra_data as $extraKey => $eData){
                $response[$extraKey] = $eData;
            }
        }
        if(count($data) > 0){
            $response['data'] = $data;
        }
        echo json_encode($response);
        exit;
    }


    public function checkIsset($requiredFieldArray, $dataArray){
        if($diffArray = array_diff($requiredFieldArray, array_keys($dataArray))){
            $parameterRequired = implode(', ' , $diffArray);
            if(count($diffArray) < 2){
                $message = $parameterRequired." parameter is required.";
            }else{
                $message = $parameterRequired." parameters are required.";
            }
            $this->throwError(400,$message);
        }
    }
    
}
