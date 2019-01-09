<?php

namespace php;

require_once(DIR_CRM.'vendor/autoload.php');

class ViewGenerator extends BaseObject{

	protected $data = array();
	protected $view = null;
	protected $page = null;

	public function __construct($page){
		$this->page = $page;
	}

	public function setData($data){
		$this->data = $data;
	}

	public function setView($view){
		$this->view = $view;
	}

	public function getOutput(){
		try{
			$loader = new \Twig_Loader_Filesystem(DIR_VIEWS);
			$twig = new \Twig_Environment ( $loader);
			//$twig = new Twig_Environment ( $loader, array('cache'=>DIR_VIEWS_COMPILED, ));
			if(is_null($this->view)){
				$this->view = strtolower($this->page).'.html';
			}
			return $twig->render($this->view,$this->data);
		} catch (\Exception $e) {
			die ('ERROR: ' . $e->getMessage());
		}
	}
}
