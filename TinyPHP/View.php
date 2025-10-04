<?php
class TinyPHP_View 
{	
	private static $instance;
	
	private $viewDir;
	private $viewFile;
	
	private $viewVars;

	private $viewRenderer;
	
	private $_stylesheets = array();
	private $_headerScripts = array();
	private $_footerScripts = array();
	
	private function __construct(){}
	
	final public static function getInstance(){
		
		if (!isset(self::$instance)) {
			self::$instance = new self();
			self::$instance->init();
		}
		
		return self::$instance;
	}
	
	private function init(){
		
		$this->viewVars = [];
		$this->setViewRenderer(TinyPHP_BladeRenderer::getInstance());
	}
	
	public function setViewRenderer($viewRenderer){
		
		$this->viewRenderer = $viewRenderer;
		$this->setupRenderer();
	}
	
	/*public function getViewRenderer() { return $this->viewRenderer; }*/
	
	public function setViewFile($viewFile){ $this->viewFile = $viewFile; }
	public function getViewFile(){ return $this->viewFile; }

	public function setViewDir($viewDir){ $this->viewDir = $viewDir; }
	public function getViewDir(){ return $this->viewDir; }

	public function setViewVar($_key, $_val){ $this->viewVars[$_key] = $_val; }
	public function setViewVars($vars){ $this->viewVars = array_merge($this->viewVars ?? [], $vars); }
	public function getViewVars($key=null){ return $key ? ($this->viewVars[$key] ?? null) : $this->viewVars; }

	public function registerStylesheet($stylesheet){ if(!in_array($stylesheet,$this->_stylesheets)) $this->_stylesheets[] = $stylesheet; }
	public function registerHeaderScript($script){ if(!in_array($script,$this->_headerScripts)) $this->_headerScripts[] = $script; }
	public function registerFooterScript($script){ if(!in_array($script,$this->_footerScripts)) $this->_footerScripts[] = $script; }

	private function makeStylesheetTags(){ $tags=""; foreach($this->_stylesheets as $s) $tags.='<link href="'.$s.'" type="text/css" rel="stylesheet"/>'."\n"; return $tags; }
	private function makeScriptTags($_scripts){ $tags=""; foreach($_scripts as $s) $tags.='<script src="'.$s.'" type="text/javascript"></script>'."\n"; return $tags; }

	private function setupRenderer(){
		
		$this->setViewVar('styleSheets',$this->makeStylesheetTags());
		$this->setViewVar('headerScripts',$this->makeScriptTags($this->_headerScripts));
		$this->setViewVar('footerScripts',$this->makeScriptTags($this->_footerScripts));

		$this->viewRenderer->setViewFile($this->viewFile);
		$this->viewRenderer->setViewDir($this->viewDir);
		$this->viewRenderer->setViewVars($this->viewVars);
	}
	
	public function render($refreshRenderer=false) {
		$this->setupRenderer();
		echo $this->viewRenderer->render($refreshRenderer);
	}
}
?>
