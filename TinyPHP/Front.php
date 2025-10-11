<?php
class TinyPHP_Front {

	private static $instance;
	private $dbConnectAttempt = 0;
	private $modules = ['app' => '/app/controllers']; // default module is app
	private $middlewares = ['app' => ["Class"]];

	// helper properties
	private $noRenderer = false;
	private $layoutEnabled = true;
	private $requestObj;
	

	private function __construct() {
		new Loader();
	}

	
	public static function getInstance() {
		
		if (!isset(self::$instance)) {			
			
			$c = __CLASS__;
			self::$instance = new $c;
		}

		return self::$instance;
	}

	
	public function __clone() {
		trigger_error('Clone is not allowed.', E_USER_ERROR);
	}


	public function registerModules(array $modules): void {
		$this->modules = array_merge($this->modules, $modules);
	}


	public function registerMiddlewares(array $middlewares): void {
		$this->middlewares = array_merge($this->middlewares, $middlewares);
	}

	public function setNoRenderer($noRenderer) {
		$this->noRenderer = $noRenderer;
	}

	public function disableLayout($disable) {
		$this->layoutEnabled = (true == $disable) ? false : true;
	}

	public function isLayoutEnabled() {
		return $this->layoutEnabled;
	}


	private function initDatabase() {

		global $db, $dataCache;

		if ($this->dbConnectAttempt === 0) {
			
			$this->dbConnectAttempt = 1;
			$db = TinyPHP_DB::getInstance();
			$dataCache = Models_SQLCache::getInstance();
		}
	}


	private function initRequest() {
		
		$requestObj = TinyPHP_Request::getInstance();
		$this->requestObj = $requestObj->setupRequest(array_keys($this->modules));

		return $this->requestObj;
	}

	private function resolveController(string $module, string $controller) {

		if( !isset($this->modules[$module]) ) {
			throw new \TinyPHP_Exception('[Module Error] The module "app" is not registered. Please add it to your configuration file: config/modules.php', 500);
		}

		$moduleDir = $this->modules[$module];
		$modulePath = APP_PATH ."/http/". ltrim($moduleDir, "/");

		return TinyPHP_ControllerResolver::resolve($module, $controller, $modulePath, false);
	}


	private function resolveAction(TinyPHP_Controller $controller, string $action) {		

		$actionMethod = "{$action}Action";
        if (!method_exists($controller, $actionMethod) || !is_callable([$controller, $actionMethod])) {

			$reflection = new ReflectionClass($controller);
			$filePath = $reflection->getFileName();
			$className = $reflection->getName();			 

			$message = "[Controller Error] Action \"$action\" could not be executed in controller \"$className\".\n"
             . "Controller file: $filePath\n\n"
             . "- The method may not exist, or\n"
             . "- The method exists but is not public.\n\n"
             . "Please check spelling, visibility, and namespace.";

            throw new TinyPHP_Exception($message, 404);			
        }


		return true;
	}


	private function executeControllerAction(TinyPHP_Controller $controller, string $action, TinyPHP_Request $request) {

		// Call init if exists
        if (method_exists($controller, 'init')) {
            $controller->init();
        }

		$actionMethod = "{$action}Action";

		$reflection = new ReflectionMethod($controller, $actionMethod);
		$numParams = $reflection->getNumberOfParameters();
		
		// Call method based on number of parameters
		if ($numParams === 0) {
			$controller->$actionMethod();
		} else {
			$controller->$actionMethod($request);
		}

		return true;
    }


	private function processRequest(TinyPHP_Request $request)
	{
		// If static file, it should show file not found exception		
		if( $request->isStaticFile() ) {
			throw new \TinyPHP_Exception('File not found: '.$request->getRequestUri(), 404);
		}

		// Resolve contoller and action
		$moduleName = $request->getModuleName();
		$controllerName = $request->getControllerName();
		$actionName = $request->getActionName();


		/* Resolve controller */
		$controllerObj = $this->resolveController($moduleName, $controllerName);


		/* Resolve action */
		$this->resolveAction($controllerObj, $actionName);


		/* Run Middleware */
		$pipeline = new TinyPHP_MiddlewarePipeline();

		/* Take registered middleware Global + Module specific */
		$globalMiddlewares = array_merge([TinyPHP_HandleCors::class], $this->middlewares['global'] ?? []);
		$middlewares = array_merge($globalMiddlewares, $this->middlewares[$moduleName] ?? []);

		/* Add to pipeline */
		foreach ($middlewares as $class) {
			
			if (is_string($class) && class_exists($class)) {
				$pipeline->add(new $class());
			}
		}

		// Final handler: controller action
		$finalHandler = function() use ($controllerObj, $actionName, $request) {
			return $this->executeControllerAction($controllerObj, $actionName, $request);
		};


		return $pipeline->handle($request, $finalHandler);
	}


	private function resolveView(TinyPHP_Request $request) {

		$module = $request->getModuleName();
		$controller = $request->getControllerName();
		$action = $request->getActionName();

		$viewObj = TinyPHP_View::getInstance();


		$viewDir = $viewObj->getViewDir() ?: APP_PATH. "/resources/views/" . $module . "/" . $controller;
		$viewFile = $viewObj->getViewFile() ?: "{$action}.blade.php";

		$viewFilePath = $viewDir."/".$viewFile;
		if (!file_exists($viewFilePath)) {

			$filePath = normalizePath($viewFilePath);

			$message = "[View Error] View file \"$viewFile\" not found.\n"
             . "Resolved path: $filePath\n\n"
             . "- Ensure the view file exists at the specified path.\n"
             . "- Check for typos in the filename or folder names";

			throw new TinyPHP_Exception($message, 500);
		}

		return [$viewDir, $viewFile];
	}
	
	
	private function renderView(TinyPHP_Request $request)
	{
		$viewObj = TinyPHP_View::getInstance();		
		$viewObj->setViewVar('pathInfo', [
			'module' => $request->getModuleName(),
			'controller' => $request->getControllerName(),
			'action' => $request->getActionName(),
			'params' => $request->getParams()
		]);

		list($viewDir, $viewFile) = $this->resolveView($request);
		
		$viewObj->setViewDir($viewDir);
		$viewObj->setViewFile($viewFile);
		$viewObj->render();
	}
	

	public function dispatch() 
	{
		try {

			// init request
			$request = $this->initRequest();

			
			// init database
			$this->initDatabase();

			
			// process request
			$this->processRequest($request);


			// render view only if needed
			if( !$this->noRenderer ) {
				$this->renderView($request);
			}

		} catch (TinyPHP_Exception $e) {			
			TinyPHP_Exception::handleException($e);
		} catch (Exception $e) {		 
			TinyPHP_Exception::handleException($e);
		}
	}
}
?>	