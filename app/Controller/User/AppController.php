<?php

namespace App\Controller\User;

use Core\Form\BootstrapForm;

class AppController extends \App\Controller\AppController{
    	
	protected $user_data;
	
	public function __construct(){
		parent::__construct();
		if($this->logged === false && $this->page != '/manage/login/' && $this->page != '/manage/register/'){
			$this->redirect(LINK_LOGIN);
			exit;
		}else if($this->logged === true){
            $this->loadElement('User', true);
			$this->User->setLevel();
			
			$this->user_data = $this->User->findUser();
		}
		
		$this->viewPath = ROOT . '/app/Views/user/';
		$this->form = new BootstrapForm($_POST);
	}
	
	protected function noPerms(){
		$msg = $this->getAlert('Vous n\'avez pas l\'autorisation d\'accéder à cette page. Cette erreur a été reportée aux administrateurs.', 'danger');
		$this->viewPath = ROOT . '/app/Views/user/';
		$this->render('no-perm', compact('msg'));
		exit;
	}
}
