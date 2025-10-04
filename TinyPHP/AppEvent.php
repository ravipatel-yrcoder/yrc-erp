<?php
final class TinyPHP_AppEvent
{
	private static $instance;
	
	private $eventHandlers = array();
		
	private function __construct(){}
	
	final public static function getInstance(){
		if (!isset(self::$instance)) {
			$c = __CLASS__;
			self::$instance = new $c;
			self::$instance->init();
		}
		return self::$instance;
	}
	
	private function init()
	{
		
	} 
	
	public static function raiseEvent($_eventName,$args=array())
	{
		
		$obj = self::getInstance();

		if(array_key_exists($_eventName, $obj->eventHandlers) && is_array($args))
		{
			foreach($obj->eventHandlers[$_eventName] as $handler)
			{
				$c = 'EventHandlers_'. $handler;
				if(class_exists($c))
				{
					$hnd = new $c();
					$methodName = 'on'.ucfirst($_eventName);
					if(method_exists($hnd,$methodName))
					{
						call_user_func_array(array($hnd,$methodName), $args);
					
					}
				}
			}
		}
	}
	
	public function attachHandler($_eventName, $_handlerClass)
	{
		if(!array_key_exists($_eventName, $this->eventHandlers))
		{
			$this->eventHandlers[$_eventName] = array();
		}
		
		if(!empty($_handlerClass))	
		{
			if(!in_array($_handlerClass, $this->eventHandlers[$_eventName]))
			{
				array_push($this->eventHandlers[$_eventName],$_handlerClass);
			}
			
		}
	}
	
	
	public function removeHandler($_eventName, $_handlerClass)
	{
		
	}
	
}