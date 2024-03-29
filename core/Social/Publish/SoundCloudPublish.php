<?php

namespace Core\Social\Publish;

trait SoundCloudPublish {
	
	private $configured = false;
	
	public function __construct(){
		//set configured
	}
	
	public function uploadTrackSC($track = []){
		if($track != null){
			try {
				return $this->post('soundcloud', 'tracks', $track);
			}catch (Services_Soundcloud_Invalid_Http_Response_Code_Exception $e) {
				exit($e->getMessage());
			}
		}else {
			return false;
		}
	}
	
}