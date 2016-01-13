<?php

namespace App\Controller\Admin;

class AdminController extends \App\Controller\User\AppController{
	
	public $pages;
	
	public function __construct(){
		parent::__construct();
		$this->template = 'default';
		$this->viewPath = ROOT . '/app/Views/admin/';
		$this->loadElement('Admin');
		$this->pages = $this->Website->getPages('admin');
		
		if(!$this->User->isAdmin)
			$this->noPerms();
	}
	
	public function general(){
		$this->render('general', compact(''));
	}
	
	/* -- PAGES (EXEMPLE) -- */
	
	public function getPages($error = null){
		$pages = $this->Website->getPages('all');
		$this->render('pages', compact('pages', 'error'));
	}
	
	public function addPage(){
		if(!empty($_POST))
			if($this->Admin->addPage() === true)
				$error = $this->getAlert('La page a été ajoutée !', 'success');
			else
				$error = $this->getAlert('Erreur lors de l\'ajout', 'danger');
		$this->pages($error);
	}
	
	public function editPage(){
		if(!empty($_POST))
			if($this->Admin->editPage() === true)
				$error = $this->getAlert('La page a été modifiée !', 'success');
			else
				$error = $this->getAlert('Erreur lors de la modification', 'danger');
		$this->pages($error);
	}
	
	public function deletePage($id){
		$this->Admin->deletePage($id);
		$this->pages($this->getAlert('La page a été supprimée !', 'success'));
	}

}