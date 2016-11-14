<?php
/**
 * Upload Class
 */

namespace Core\Upload;

/**
 * Cette classe permet de gérer l'envoi de fichier(s)
 *
 * @package startFramework\Core\Upload
 * @author  CocktailFuture
 * @version 2.0
 * @license CC-BY-NC-SA-4.0 Creative Commons Attribution Non Commercial Share Alike 4.0
 * @todo    Ajouter d'autres fonctionnalités (ex: vérifier la présence d'un
 *          fichier après envoi) et simplifier l'utilisation
 */
class Upload {

    /**
     * Nom temporaire du fichier
     *
     * @var string
     */
	public $tmp_name;

	/**
	 * Nom définitif du fichier
	 *
	 * @var string
	 */
	public $file_name;

	/**
	 * Contient les erreurs
	 *
	 * @var array
	 */
	public $errors;

	/**
	 * Contient le où les fichiers (permet l'envoi multiple)
	 *
	 * @var array
	 */
	public $files = [];

    /**
     * Répertoire où seront stockés les fichiers
     *
     * @var string
     */
	private $upload_dir;

	/**
	 * Extension du fichier actuel
	 *
	 * @var string
	 */
	private $extension;

	/**
	 * Taille du fichier actuel
	 *
	 * @var bool
	 */
	private $file_size;

    /**
     * Taille maximum pour l'envoi de fichiers
     *
     * @todo Rendre ce paramètre dynamique via la bdd
     * @var  int
     */
	private $max_size = 4194304;

	/**
	 * Extensions valides à l'envoi
	 *
	 * @var array
	 */
	private $extensions;

    /**
     * Initialisation de la classe
     */
	public function __construct(){
		$this->upload_dir = defined('MY_FOLDER') ? (defined('USER_FILES') ? USER_FILE : ROOT) : ROOT . '/public/assets/files/';
		$this->extensions = array('jpg', 'jpeg', 'png', 'gif');
	}

	/**
	 * Permet de remettre à zéro les fichiers qui doivent être envoyés
	 *
	 * @example "En cas d'échec de validation du formulaire"
	 */
	public function resetFiles(){
		$this->files = [];
	}

    /**
     * Permet de définir le dossier où seront stockés le(s) fichier(s)
     *
     * @param string|null $folder
     */
	public function setFolder($folder = null){
		$this->upload_dir = $folder == null ? USER_FILES : ($folder == 'root' ? ROOT . '/public/assets/files/' : $folder);
	}

    /**
     * Permet de récupérer le dossier actuel où seront stockés les fichiers envoyés
     *
     * @return string
     */
	public function getFolder(){
		return $this->upload_dir;
	}

    /**
     * Permet de définir la taille maximale des fichiers qui peuvent être envoyés
     *
     * @param int $size
     */
	public function setMaxSize($size = null){
		$this->max_size = $size;
	}

    /**
     * Permet de définir les extensions valables
     *
     * @example <86> <1>
     *
     * @param array $extensions
     */
	public function setExtensions($extensions = []){
		$this->extensions = $extensions;
	}

    /**
     * Permet d'ajouter un fichier à la file d'attente d'envoi et
     * d'enregistrer ses caractéristiques
     *
     * @uses   $_FILES
     * @param  mixed $file Variable de téléchargement de fichier via HTTP ($_FILES)
     * @return bool|void   Retourne le succès
     */
	public function setFile($file){
		if(isset($_FILES[$file])){
			$this->tmp_name = $_FILES[$file]["tmp_name"];
			$this->file_name = $_FILES[$file]["name"];
			$this->extension = pathinfo($this->file_name, PATHINFO_EXTENSION);
			$this->file_size = $_FILES[$file]["size"];
			return true;
		}
	}

    /**
     * Permet de vérifier que le fichier à bien été inclu dans le formulaire
     *
     * @return string
     */
	public function fileSended(){
		return $this->tmp_name;
	}

    /**
     * Permet de vérifier s'il est possible d'envoyer le fichier
     *
     * @todo   Renvoyer un code d'erreur
     * @return string|bool Retourne une erreur ou le succès réussi
     */
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

    /**
     * Permet d'envoyer le fichier
     *
     * @todo   Renvoyer un code d'erreur
     * @return string|bool Retourne une erreur ou le succès réussi
     */
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

    /**
     * Permet de supprimer un fichier
     *
     * @uses   $_FILES
     * @todo   Protéger la fonction avec validation, etc
     * @param  mixed     $file   Variable de téléchargement de fichier via HTTP ($_FILES)
     * @param  string    $folder Le dossier où se trouve le fichier
     * @return bool|void         Retourne le succès
     */
	public function deleteFile($file, $folder = USER_FILES){
		if(file_exists($folder . $file))
            return unlink($folder . $file);
	}
}
