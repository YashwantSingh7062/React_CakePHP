<?php
namespace App\Controller\Component;

use Cake\Controller\Component;
use Cake\ORM\TableRegistry;
use Cake\Mailer\Email;
use Cake\Core\Configure;
use Cake\I18n\Time;
use Cake\Controller\Controller;
use Cake\Controller\Component\CookieComponent;
use Cake\Utility\Security;
use Cake\Event\Event;
use Cake\Network\Exception\NotFoundException;

class GeneralComponent extends Component{
    
    public function checkFileExist($file_path){
        return file_exists($file_path) ? $file_path : WEBROOT.'images/default.jpg';
    }

	public function saveNotification($user_id, $notification_title, $description ){
        $notificationTbl = TableRegistry::get('notifications');

        $notificationEntity = $notificationTbl->newEntity();
        $notificationEntity->user_id = $user_id;
        $notificationEntity->title = $notification_title;
        $notificationEntity->description = $description;
        $notificationEntity->read_status = ACTIVE;
        $notificationEntity->created = $notificationEntity->modified = date("Y-m-d H:i:s");
        if($notificationTbl->save($notificationEntity)){
            return true;
        }else{
            return false;
        }
    }

    public function randomPassword() {
        $alphabet = 'abcdefghijklmnopqrstuvwxyzAB785CDEFGHIJKLMNO4PQRSTUVWXYZ1234567890';
        $pass = array(); //remember to declare $pass as an array
        $alphaLength = strlen($alphabet) - 1; //put the length -1 in cache
        for ($i = 0; $i < 15; $i++) {
            $n = rand(0, $alphaLength);
            $pass[] = $alphabet[$n];
        }
        return implode($pass); //turn the array into a string
    }
     
}