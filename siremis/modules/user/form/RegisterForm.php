<?php 
include_once(MODULE_PATH."/system/form/UserForm.php");

class RegisterForm extends UserForm
{
/**
     * Create a user record
     *
     * @return void
     */
    public function CreateUser()
    {
        $recArr = $this->readInputRecord();
        $this->setActiveRecord($recArr);
        if (count($recArr) == 0)
            return;

        if ($this->_checkDupUsername())
        {
            $errorMessage = $this->GetMessage("USERNAME_USED");
			$errors['fld_username'] = $errorMessage;
			$this->processFormObjError($errors);
			return;
        }

        if ($this->_checkDupEmail())
        {
            $errorMessage = $this->GetMessage("EMAIL_USED");
			$errors['fld_email'] = $errorMessage;
			$this->processFormObjError($errors);
			return;
        }
                
        try
        {
            $this->ValidateForm();
        }
        catch (ValidationException $e)
        {
            $this->processFormObjError($e->m_Errors);
            return;
        }
        
        $recArr['create_by']="0";
        $recArr['update_by']="0";

        $this->_doInsert($recArr);
                
        //set default user role to sip user
		$userinfo = $this->getActiveRecord();
        $userRoleObj = BizSystem::getObject('system.do.UserRoleDO');
        $uesrRoloArr =array(
        				"user_id"=>$userinfo['Id'],
        				"role_id"=>"3",  //role 3 is SIP user
        				); 
         
        $userRoleObj->insertRecord($uesrRoloArr);

        //record event log   
        global $g_BizSystem;     
        $eventlog 	= BizSystem::getService(EVENTLOG_SERIVCE);
        $logComment=array($userinfo['username'],$_SERVER['REMOTE_ADDR']);
    	$eventlog->log("USER_MANAGEMENT", "MSG_USER_REGISTERED", $logComment);   
    	     
        //send user email
        //$emailObj 	= BizSystem::getService(USER_EMAIL_SERIVCE);
        //$emailObj->UserWelcomeEmail($userinfo['Id']);
        
        //init profile for future use like redirect to my account view
        $profile = $g_BizSystem->InituserProfile($userinfo['username']);
        

		$serUserObj = BizSystem::getObject('ser.sbs.authdb.do.SubscriberDO');
		$serUserArr = array(
							"username"=>$recArr['username'],
							"domain"=>$recArr['domain'],
							"password"=>$recArr['password'],
							"email_address"=>$recArr['email']
						);
		$serUserObj->InsertRecord($serUserArr);
        $this->processPostAction();
    }
}

?>
