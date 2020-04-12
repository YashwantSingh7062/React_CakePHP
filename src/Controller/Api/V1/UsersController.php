<?php
/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link      https://cakephp.org CakePHP(tm) Project
 * @since     0.2.9
 * @license   https://opensource.org/licenses/mit-license.php MIT License
 */
namespace App\Controller\Api\V1;

use Cake\Core\Configure;
use Cake\Http\Exception\ForbiddenException;
use Cake\Http\Exception\NotFoundException;
use Cake\View\Exception\MissingTemplateException;
use Cake\ORM\TableRegistry;
use Cake\Auth\DefaultPasswordHasher;
use \Firebase\JWT\JWT;

/**
 * Static content controller
 *
 * This controller will render views from Template/Pages/
 *
 * @link https://book.cakephp.org/3.0/en/controllers/pages-controller.html
 */
class UsersController extends AppController
{

    /**
     * Displays a view
     *
     * @param array ...$path Path segments.
     * @return \Cake\Http\Response|null
     * @throws \Cake\Http\Exception\ForbiddenException When a directory traversal attempt.
     * @throws \Cake\Http\Exception\NotFoundException When the view file could not
     *   be found or \Cake\View\Exception\MissingTemplateException in debug mode.
     */

    public function initialize(){
        parent::initialize();
        $this->loadComponent('General');
    }
    
    // prefix :: /api/v1/users
    // Api :: login
    // Params :: email, password, device_id, device_type
    public function login()
    {
        $this->autoRender = false;
        $userTbl = TableRegistry::get('users_old');

        if($this->request->is(['post']))
        {   
            parent::checkIsset(['email','password','device_id','device_type'], $this->request->data);
            $email = parent::validateParameters("Email", $this->request->data['email'], STRING);
            $password = parent::validateParameters("Password", $this->request->data['password'], STRING);
            $device_id = parent::validateParameters("Device Id", $this->request->data['device_id'], STRING);
            $device_type = parent::validateParameters("Device Type", $this->request->data['device_type'], STRING);

            $userData = $userTbl->find('all')->where(['email' => $email])->orWhere(["phone_number" => $email])->first();
            if(!empty($userData))
            {   
                $hasher = new DefaultPasswordHasher();
                $verify = $hasher->check($password, $userData['password']);
                if($verify)
                {   
                    if($userData['role_id'] == USER)
                    {
                        $updateUserData['device_type'] = $device_type; 
                        $updateUserData['device_id'] =  $device_id;

                        if($userData['status'] == ACTIVE)
                        {
                            if($userData['verified_status'] == ACTIVE)
                            {    
                                $userPostData = $userTbl->patchEntity($userData, $updateUserData, ['validate'=>false]);
                                if($userTbl->save($userPostData)){
                                    // USERDATA 
                                    $user['user_id'] = $userData->id;
                                    $user['email'] = $userData->email;
                                    $user['username'] = $userData->name;
                                    $user['profile_picture'] = $this->General->checkFileExist('img/users/'.$userData->profile_picture);
                                    $user['verified_status'] = $userData->verified_status;
                                    try{
                                        // TOKEN
                                        $key = JWT_SECRET_KEY;
                                        $payload = array(
                                            "iss" => SITE_URL,
                                            "aud" => SITE_URL,
                                            "iat" => time(),
                                            "exp" => time() + (60 * 60), // 1 Hour
                                            "user_id" => $userData->id
                                        );

                                        $token = JWT::encode($payload, $key);
                                        parent::sendResponse(200, "Login successfully.", $user, ["token" => $token]);
                                    }catch(\Exception $e){
                                        parent::throwError(401, $e->getMessage()); 
                                    }
                                }else{
                                    parent::throwError(500,"Internal server error."); 
                                }
                            }else{
                                parent::throwError(401,"Please verify your account first."); 
                            }
                        }else{
                            parent::throwError(401,"Your account is deactive by admin. Please email us at ".EMAIL_SENT_FROM);
                        }
                    }else{
                        parent::throwError(401,"Please login as a user account.");                       
                    }
                }else{
                    parent::throwError(401,"Invalid password.");                  
                }   
            }else{
                parent::throwError(401,"Invalid login credential.");             
            }
        }else{
            parent::throwError(405,"Something went wrong. Please try again.");              
        }
    }

    // prefix :: /api/v1/users
    // Api :: signup
    // Parameters :: name, nationality, email, phone_number, password, device_id, device_type
    public function signup(){
        $this->autoRender  = false;
        $userTbl = TableRegistry::get('users_old');
        $emailTemplatesTbl = TableRegistry::get('email_templates');

        if($this->request->is(['post']))
        {   
            parent::checkIsset(['name','nationality','email','phone_number','password','device_id','device_type'], $this->request->data);
            $name = parent::validateParameters("Name", $this->request->data['name'], STRING,["min" => 3, "max" => 150]);
            $nationality = parent::validateParameters("Nationality", $this->request->data['nationality'], STRING,["max" => 150]);
            $email = parent::validateParameters("Email", $this->request->data['email'], STRING,["type" => "email","min" => 3, "max" => 150]);
            $phone_number = parent::validateParameters("Phone Number", $this->request->data['phone_number'], STRING,["min" => 5, "max" => 20]);
            $password = parent::validateParameters("Password", $this->request->data['password'], STRING , [ "type" => "password", "min" => 8]);
            $device_id = parent::validateParameters("Device Id", $this->request->data['device_id'], STRING);
            $device_type = parent::validateParameters("Device Type", $this->request->data['device_type'], STRING);

            $userExistData = $userTbl->find('all')->where(['email'=>$email])->orWhere(["phone_number" => $phone_number])->first();

            if(empty($userExistData))
            {   
                $newPostData = $userTbl->newEntity();
                $newPostData->name = $name;
                $newPostData->nationality = $nationality;
                $newPostData->email = $email;
                $newPostData->phone_number = $phone_number;
                $newPostData->password = $password;
                $newPostData->device_id = $device_id;
                $newPostData->device_type = $device_type;
                $newPostData->otp = rand(1000,9999);
                $newPostData->role_id = USER;
                $newPostData->status = $newPostData->verified_status = INACTIVE;
                $newPostData->status = $newPostData->notification_status = ACTIVE;

                // $userPostData->notification_status = ACTIVE;
                $newPostData->created = $newPostData->modified = date("Y-m-d H:i:s");
                if($userData = $userTbl->save($newPostData))
                {   
                    if($userData['notification_status'] == ACTIVE){
                        $description = REGISTRATION_NOTIFICATION;
                        $this->General->saveNotification($userData->id,"Registration",$description);
                    }

                    $template = $emailTemplatesTbl->find('all')->where(['slug' => 'user_registration'])->first();
                    if($template){
                        $mailMessage = str_replace(array('{username}','{email}','{password}'),array($userData['name'],$userData['email'],$password),$template->description);
                        // parent::sendMail($userData['email'], $template->subject, $mailMessage);
                    }

                    $user['user_id'] = $userData->id;
                    $user['email'] = $userData->email;
                    $user['phone_number'] = $userData->phone_number;
                    $user['name'] = $userData->name;
                    // $user['profile_picture'] = $this->General->checkFileExist('img/users/'.$userData->profile_picture);
                    $user['verified_status'] = $userData->verified_status;

                    parent::sendResponse(201, "User registered successfully.", $user);
                }else{
                    parent::throwError(500,"Unable to register user.");                     
                }
            }else{
                parent::throwError(409,"Entered email address or phone number is already exists."); 
            }
        }else{
            parent::throwError(405,"Something went wrong. Please try again.");              
        }
    }

    // prefix :: /api/v1/users
    // Api :: forgot_password
    // Parameters :: email
    public function forgotPassword()
    {
        $this->autoRender = false;
        $userTbl = TableRegistry::get('users_old');
        $emailTemplateTbl = TableRegistry::get('email_templates');

        if($this->request->is(['post','put']))
        {   
            parent::checkIsset(['email'], $this->request->data);
            $email = parent::validateParameters("Email", $this->request->data['email'], STRING,["type" => "email", "max" => 150]);

            $userData = $userTbl->find('all')->where(['email'=>$email])->first();
            if(!empty($userData))
            {
                if($userData->status == ACTIVE){
                    if($userData->verified_status == ACTIVE)
                    {
                        $userData->otp = rand(1000,9999);
                        if($user = $userTbl->save($userData))
                        {
                            $template = $emailTemplateTbl->find('all')->where(['slug' => 'user_forgot_password'])->first();
                        
                            if(!empty($template))
                            {
                                $message = str_replace(array('{username}','{otp}'),array($user->name, $user->otp),$template->description);
                                // parent::sendMail($user->email, $template->subject, $message);

                                parent::sendResponse(200, "Please check your email to get confirmation code of your account.", ['user_id' => $user->id]);

                            }else{
                                parent::throwError(500,"Unable to send an email.");
                            }
                        }else{
                            parent::throwError(500,"Unable to send an email.");
                        }                    
                    }else{
                        parent::throwError(401,"Please verify your account first.");
                    }
                }else{
                    parent::throwError(401,"Your account is deactive by admin. Please email us at ".EMAIL_SENT_FROM);
                }  
            }else{
                parent::throwError(401,"User not found."); 
            }
        }else{
            parent::throwError(405,"Something went wrong. Please try again.");   
        }
    }

    // prefix :: /api/v1/users
    // Api :: verify_account
    // Parameters :: user_id, otp
    public function verifyAccount()
    {
        $this->autoRender = false; 
        $userTbl = TableRegistry::get('users_old'); 
        
        if($this->request->is(['post']))
        {   
            parent::checkIsset(['user_id', 'otp'], $this->request->data);
            $user_id = parent::validateParameters("User Id", $this->request->data['user_id'], INTEGER,[ "max" => 50]);
            $otp = parent::validateParameters("OTP", $this->request->data['otp'], INTEGER,[ "min"=> 2,"max" => 4]);

            $userData = $userTbl->find('all')->where(['id' => $user_id])->first();

            if(!empty($userData))
            {
                if($userData->otp == "0"){
                    parent::throwError(400,"Please try resend confirmation code to verify your account."); 
                }elseif($userData->otp != $this->request->data['otp']){
                    parent::throwError(401,"Invalid confirmation code.");                                          
                }else{
                    $userData->status = $userData->verified_status = ACTIVE;
                    $userData->otp = 0;

                    if($userTbl->save($userData))
                    {
                        $user['email'] = $userData->email;
                        $user['phone_number'] = $userData->phone_number;
                        
                        parent::sendResponse(200, "Your account verified successfully.", $user);
                    }else{ 
                        parent::throwError(500,"Unable to verify account.");                         
                    }
                }
            }else{
                parent::throwError(401,"User not found.");                  
            }
        }else{
            parent::throwError(405,"Something went wrong. Please try again.");   
        }
    }

    // prefix :: /api/v1/users
    // Api :: resend_otp
    // Parameters :: user_id
    public function resendOtp()
    {  
        $this->autoRender = false;
        $userTbl = TableRegistry::get('users_old');
        $emailTemplatesTbl = TableRegistry::get('email_templates');

        if($this->request->is(['put','post']))
        {   
            parent::checkIsset(['user_id'], $this->request->data);
            $user_id = parent::validateParameters("User Id", $this->request->data['user_id'], INTEGER,["max" => 50]);

            $userData = $userTbl->find('all')->where(['id'=>$user_id])->first();

            if(!empty($userData))
            {   
                $userData->otp = rand(1000,9999);
                if($userTbl->save($userData))
                {
                    $template = $emailTemplatesTbl->find('all')->where(['slug' => 'user_resend_otp'])->first();

                     if($template)
                     {
                        $mailMessage = str_replace(array('{username}','{email}','{otp}'),array($userData['name'],$userData['email'],$userData['otp']),$template->description);
                        // parent::sendMail($userData['email'], $template->subject, $mailMessage);

                        parent::sendResponse(200, "Confirmation code has been resend to your email.");
                    }else{
                        parent::throwError(500,"Unable to resend confirmation code.");
                    }                                        
                }else{
                    parent::throwError(500,"Unable to resend confirmation code.");
                }
            }else{
                parent::throwError(401,"User not found.");
            }
        }else{
            parent::throwError(405,"Something went wrong. Please try again.");   
        } 
    }

    // prefix :: /api/v1/users
    // Api name :: reset_password
    // Parameters :: user_id, password
    public function resetPassword()
    {
        $this->autoRender = false;
        $userTbl = TableRegistry::get('users_old');
        if($this->request->is(["post","put"]))
        {   
            parent::checkIsset(['password'], $this->request->data);
            $user_id = parent::validateParameters("User Id", $this->request->data['user_id'], INTEGER);
            $password = parent::validateParameters("Password", $this->request->data['password'], STRING,["type" => "password","min" => 8]);
            $userData = $userTbl->find('all')->where(["id" => $user_id])->first();

            if(!empty($userData))
            {   
                $userData->password = $password;
                
                if($userTbl->save($userData))
                {   
                    parent::sendResponse(200, "Password has been reset successfully.");

                }else{
                    parent::throwError(500,"Unable to reset password.");
                }
            }else{
                parent::throwError(401,"User not found."); 
            }
        }else{
            parent::throwError(405,"Something went wrong. Please try again.");
        }
    }

    // prefix :: /api/v1/users
    // Api name :: profile
    // Parameters :: name, phone_number
    // Method Accepted :: POST / PUT, GET
    public function profile()
    {
        $this->autoRender = false; 
        $userTbl = TableRegistry::get('users_old');
        $emailTemplatesTbl = TableRegistry::get('email_templates');
        $userData = $this->request->data['user'];

        // GET USER PROFILE
        if($this->request->is(['get'])){
            $user['name'] = $userData->name;
            $user['email'] = $userData->email;                             
            $user['phone_number'] = $userData->phone_number;                             
            $user['nationality'] = $userData->nationality;                             
            
            parent::sendResponse(200, "Data found successfully.", $user);
        }

        //  UPDATE USER PROFILE
        if($this->request->is(["post","put"]))
        {   
            parent::checkIsset(['name','phone_number'], $this->request->data);
            $name = parent::validateParameters("Name", $this->request->data['name'], STRING,["min" => 3, "max" => 150]);
            $phone_number = parent::validateParameters("Phone Number", $this->request->data['phone_number'], STRING,["min" => 5, "max" => 20]);
            
            $userData->name = $name;
            if($phone_number != $userData->phone_number){
                $userExist = $userTbl->find('all')->where(['phone_number' => $phone_number])->first();
                if(empty($userExist)){
                    $userData->phone_number = $phone_number;
                    $template = $emailTemplatesTbl->find('all')->where(['slug' => 'user_verification'])->first();
                    if($template)
                    {
                        $mailMessage = str_replace(array('{username}', '{otp}'),array($userData['name'],$userData['otp']),$template->description);
                        // parent::sendMail($userData['email'], $template->subject, $mailMessage);
                    }else{
                        parent::throwError(500,"Unable to send OTP.");
                    }
                }else{
                    parent::throwError(409,"Contact Number already exist.");
                }  
            }
            
            if($userSaveData = $userTbl->save($userData))
            {   
                $user['email'] = $userSaveData->email;
                $user['name'] = $userSaveData->name;  
                $user['phone_number'] = $userSaveData->phone_number;  
                $user['verified_status'] = $userSaveData->verified_status;
                
                parent::sendResponse(200, "Profile updated successfully.", $user);
            }else{
                parent::throwError(500,"Unable to update profile.");                  
            }
        }

        parent::throwError(405,"Something went wrong. Please try again.");  
    }

    // prefix :: /api/v1/users
    // Api name :: change_password
    // Parameters :: old_password, new_password
    public function changePassword()
    {
        $this->autoRender = false;
        $userTbl = TableRegistry::get('users_old');
        
        if($this->request->is(["post","put"]))
        {   
            parent::checkIsset(['old_password','new_password'], $this->request->data);
            $old_password = parent::validateParameters("Old Password", $this->request->data['old_password'], STRING);
            $new_password = parent::validateParameters("New Password", $this->request->data['new_password'], STRING,["type" => "password","min" => 8]);

            $userData = $this->request->data['user'];
            
            if(!empty($userData))
            {
                $hasher = new DefaultPasswordHasher();
                $verify = $hasher->check($old_password, $userData->password);
                    
                if($verify)
                {
                    $userData->password = $new_password;
                    if($userTbl->save($userData))
                    {
                        parent::sendResponse(200, "Password has been changed successfully.");
                    }else{
                        parent::throwError(500,"Unable to change password.");                  
                    }
                }else{
                    parent::throwError(401,"Invalid current password.");
                }
            }else{
                parent::throwError(401,"User not found."); 
            }
        }else{
            parent::throwError(405,"Something went wrong. Please try again.");
        }
    }

}
