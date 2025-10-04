<?php
class TinyPHP_Exception extends Exception {
	
	protected int $httpStatusCode = 500;

	public function __construct(string $message = "", int $httpStatusCode = 0)
    {
		parent::__construct($message);
		$this->code = $httpStatusCode;
    }

    public function getHttpStatusCode(): int{        
		return $this->code;
    }

	public static function register()
    {
        // Catch uncaught exceptions
        set_exception_handler([self::class, 'handleException']);

        // Convert PHP errors to exceptions
        set_error_handler([self::class, 'handleError']);

        // Catch fatal errors on shutdown
        register_shutdown_function([self::class, 'handleShutdown']);
    }

    public static function handleException($exception)
    {
        $httpStatusCode = $bladeFleName = 500;
		if ($exception instanceof self) {

			$httpStatusCode = $exception->getHttpStatusCode();
			if( $httpStatusCode == 404 || $httpStatusCode == 403 ) {
				$bladeFleName = $httpStatusCode;
			}
		}
        
        $request = TinyPHP_Request::getInstance();
        $module = $request->getModuleName() ?? "";
                
        if( strtoupper($module) == "API" ) {
            
            // make JSON response for API module
            
            $apiErrorMsg = "Server error";
            if( $httpStatusCode == 404 ) {
                $apiErrorMsg = "Resource not found";
            } else if( $httpStatusCode == 403 ) {
                $apiErrorMsg = "Unauthorized";
            }
            
            response([], $apiErrorMsg, $httpStatusCode)->errors([$exception->getMessage()])->sendJson();
        }        

        http_response_code($httpStatusCode);

        try {

            $viewFile = "{$bladeFleName}.blade.php";                        
            if(config('app.debug')) {

                // Always show framwork error pages in debug mode with error trace
                $viewDir = TINY_PHP_PATH."/Views/errors/";
            } else {

                // First check if user has defined error pages, if not show frawork error pages
                $viewDir = APP_PATH. "/resources/views/errors/";
                $filePath = $viewDir."".$viewFile;
                if (!file_exists($filePath)) {
                    $viewDir = TINY_PHP_PATH."/Views/errors/";
                }
            }
            
            $viewObj = TinyPHP_View::getInstance();
            $viewObj->setViewDir($viewDir);
            $viewObj->setViewFile($viewFile);
            $viewObj->setViewVars(['exception' =>$exception]);
            $viewObj->render(true);

        } catch (\Throwable $e) {
            
            // Final fallback (in case error view is also broken)
            echo "<h1>Error {$httpStatusCode}</h1>";
            echo "<p>" . htmlspecialchars($exception->getMessage()) . "</p>";
        }
        
    }

    public static function handleError($errno, $errstr, $errfile, $errline)
    {
        // Convert errors to exceptions so they are handled the same way
        throw new self($errstr, $errno);
    }

    public static function handleShutdown()
    {
        $error = error_get_last();
        if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            // Wrap fatal error into TinyPHP_Exception
            $exception = new self($error['message'], $error['type']);
            self::handleException($exception);
        }
    }
}
?>