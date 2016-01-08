<?php

namespace App\Controller\User;

use Core\Form\BootstrapForm;

class UsersController extends AppController{
	
	public $form;
	
	public function __construct(){
		parent::__construct();
		$this->template = 'blank';
	}
	
	public function login(){
		$error = false;
		if($this->logged == true)
			$this->redirect(PATH_USER);
		else if(!empty($_POST)){
			$remember_me = isset($_POST['remember_me']) ? true : false;
			if($this->auth->login($_POST['user_email'], $_POST['user_password'], $remember_me))
				$this->redirect(PATH_USER);
			else
				$error = $this->getAlert('Identifiants incorrects', 'danger');
		}

		$this->title = 'Connexion';
		$this->render('login', compact('error'));
	}
	
	public function register(){
		$error = false;
		if($this->logged == true)
			$this->redirect(PATH_USER);
		else if(!empty($_POST)){
			$register = $this->auth->register($_POST['user_email'], $_POST['user_password'], $_POST['repeat_password'], $_POST["g-recaptcha-response"], $_POST['user_phone']);
			
			if($register === true):
				$error = $this->getAlert('Votre compte a bien été créé !', 'success');
			    $error .= $this->getAlert('Veuillez vérifier vos mails pour activer votre compte.', 'info');
			else:
				$error = $this->getAlert($register, 'danger');
			endif;
		}

		$this->title = 'Inscription';
		$this->render('register', compact('error'));
	}
	
	public function active_account($key){
		$error = false;
		if($this->logged == true)
			$this->redirect(PATH_USER);
		else if(!empty($key)){
			$validation = $this->auth->active_account($key);
			if($validation === true)
				$error = $this->getAlert('Votre compte a été validé ! <a href="' . LINK_LOGIN . '">Connexion</a>', 'success');
			else
				$error = $this->getAlert($validation, 'danger');
		}else
			$this->redirect();

		$this->title = 'Validation';
		$this->render('validate', compact('error'));
	}
	
	public function forgot(){
		$error = false;
		
		if($this->logged == true)
			$this->redirect(PATH_USER);
		else if(!empty($_POST)){
			$forgot = $this->auth->forgot($_POST['user_email'], $_POST["g-recaptcha-response"]);
			
			if($forgot === true)
				$error = $this->getAlert('Un email vous a été envoyé !', 'success');
			else
				$error = $this->getAlert($forgot, 'danger');
		}
		
		$this->title = 'Mot de passe oublié';
		$this->render('forgot', compact('error'));
	}
	
	public function logout(){
		$this->auth->logout();
		$this->redirect(LINK_LOGIN);
	}
	
	public function getUser($id){
		return $this->find($id);
	}
}
