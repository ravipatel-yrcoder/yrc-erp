<?php
abstract class TinyPHP_ViewRenderer {
	
	private $viewFile;
	private $viewDir;
	private $viewVars;	
	
	abstract public function render();
	
	final public function setViewFile($viewFile) { $this->viewFile = $viewFile; }
	final public function setViewDir($viewDir) { $this->viewDir = $viewDir; }
	final public function setViewVars($viewVars) { $this->viewVars = $viewVars; }
	
	final public function getViewFile() { return $this->viewFile; }
	final public function getViewDir() { return $this->viewDir; }
	final public function getViewVars() { return $this->viewVars; }
}
?>