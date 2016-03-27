<?php

namespace Core\Upload;

class Upload {
	
	public $tmp_name;
	public $file_name;
	public $errors;
	public $files = [];
	
	private $upload_dir;
	private $extension;
	private $file_size;
	
	private $max_size = 4194304;
	private $extensions;
	
	public function __construct(){
		$this->upload_dir = defined('MY_FOLDER') ? USER_FILES : ROOT . '/public/assets/files/';
		$this->extensions = array('jpg', 'jpeg', 'png', 'gif');
	}
	
	public function resetFiles(){
		$this->files = [];
	}
	
	public function setFolder($folder = null){
		$this->upload_dir = $folder == null ? USER_FILES : $folder;
	}
	
	public function setMaxSize($size = null){
		$this->max_size = $size;
	}
	
	public function setExtensions($extensions = []){
		$this->extensions = $extensions;
	}
	
	public function setFile($file){
		if(isset($_FILES[$file])){
			$this->tmp_name = $_FILES[$file]["tmp_name"];
			$this->file_name = $_FILES[$file]["name"];
			$this->extension = pathinfo($this->file_name, PATHINFO_EXTENSION);
			$this->file_size = $_FILES[$file]["size"];
			return true;
		}
	}
	
	public function fileSended(){
		return $this->tmp_name;
	}
	
	public function canUpload(){
		if($this->tmp_name != null){
			if(in_array($this->extension, $this->extensions))
				if($this->file_size <= $this->max_size)
					return true;
		        else
					return 'Le fichier envoyé est trop volumineux.';
			else
				return 'Le format du fichier n\'est pas correct.';
		}
	}
	
	public function uploadFile(){
		if($this->tmp_name != null){
			if(in_array($this->extension, $this->extensions)){
				if($this->file_size <= $this->max_size){
					$this->file_name = explode('.', $this->file_name)[0] . '_' . uniqid() . '.' . $this->extension;
					$this->files[] = $this->file_name;
					if(move_uploaded_file($this->tmp_name, $this->upload_dir . $this->file_name))
						return true;
					else
						return $this->errors;
				}else
					return 'Le fichier envoyé est trop volumineux.';
			}else
				return 'Le format du fichier n\'est pas correct.';
		}
	}
	
	public function deleteFile($file){
		if(file_exists(USER_FILES . $file))
            return unlink(USER_FILES . $file);
	}
}