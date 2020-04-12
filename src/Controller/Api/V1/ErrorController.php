<?php
/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         3.3.4
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace App\Controller\Api\V1;

use Cake\ORM\TableRegistry;
use Cake\Event\Event;

/**
 * Error Handling Controller
 *
 * Controller used by ExceptionRenderer to render error responses.
 */
class ErrorController extends AppController
{
    /**
     * Initialization hook method.
     *
     * @return void
     */
    public function initialize()
    {
        
    }

    /**
     * beforeFilter callback.
     *
     * @param \Cake\Event\Event $event Event.
     * @return \Cake\Http\Response|null|void
     */
    public function beforeFilter(Event $event)
    {
    }

    /**
     * beforeRender callback.
     *
     * @param \Cake\Event\Event $event Event.
     * @return \Cake\Http\Response|null|void
     */
    public function beforeRender(Event $event)
    {
        parent::beforeRender($event);
        $this->viewBuilder()->setTemplatePath('Error');  
    }

    /**
     * afterFilter callback.
     *
     * @param \Cake\Event\Event $event Event.
     * @return \Cake\Http\Response|null|void
     */
    public function afterFilter(Event $event)
    {
    }

    public function index(){
        http_response_code(404);
        $errorMessage = json_encode(['message' => "Call to undefined API."]);
        echo $errorMessage;
        exit;
    }

    public function hackerMessage(){
        $securityTbl = TableRegistry::get('security_ip_check');
        $securityData = $securityTbl->find('all')->where(["ip_address" => $this->request->clientIp()])->first();
        if(!empty($securityData)){
            $securityData->hit_count += 1; 
            $securityData->last_modified =date("Y-m-d H:i:s"); 
        }else{
           $securityData = $securityTbl->newEntity();
           $securityData->ip_address =  $this->request->clientIp();
           $securityData->hit_count =  1;
           $securityData->created = $securityData->last_modified = date("Y-m-d H:i:s");
        }
        $securityTbl->save($securityData);
        http_response_code(406);
        echo json_encode(["message" => "Don't try to be an oversmart. Hackers not allowed. We're tracking your IP for security purpose."]);
        exit;
    }
}
