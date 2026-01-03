<?php
class TinyPHP_Request
{
	private static $instance;
	private $requestURI;
	private $uriParts;
	private $moduleName;
	private $controllerName;
	private $actionName;
	private $params;
    private $headers;
	private $jsonData = [];

	private function __construct() {}	
	
	/**
	 * returns singleton TinyPHP_Request object
	 * 
	 * @return TinyPHP_Request
	 * 
	 */

	public static function getInstance() {
		if (!isset(self::$instance)) {
			$c = __CLASS__;
			self::$instance = new $c;
		}
		return self::$instance;
	}

	private function parseJson() {
		
		$contentType = $this->getHeader('Content-Type') ?? '';
        if (strpos($contentType, 'application/json') !== false) {
            $this->jsonData = json_decode(file_get_contents('php://input'), true) ?: [];
        }
	}

	public function getMethod() {
		return strtolower($_SERVER['REQUEST_METHOD'] ?? 'get');
	}

	public function getModuleName() {
		return $this->moduleName;
	}

	public function setModuleName($_moduleName) {
		$this->moduleName = $_moduleName;
	}

	public function getControllerName() {
		return $this->controllerName;
	}

	public function setControllerName($_controllerName) {
		$this->controllerName = $_controllerName;
	}

	public function getActionName() {
		return $this->actionName;
	}

	public function setActionName($_actionName) {
		$this->actionName = $_actionName;
	}

	public function getParams() {
		return $this->params;
	}

	public function setParams($_params = array()) {
		$this->params = $_params;
	}

	public function getURIParts() {
		return $this->uriParts;
	}

	public function getInputs() {
		return array_merge($_GET, $_POST, $this->jsonData, $this->getParams());
	}

	public function getInput(string $key, string $type = '', mixed $default = null) {

		$all = $this->getInputs();

		if (!array_key_exists($key, $all)) {
			return $default;
		}

		$value = $all[$key];


		// No type requested — return as-is
		if ($type === '') {
			return $value ?? $default;
		}

		return match (strtolower($type)) {
			'string'  => (string)$value,
			'int', 'integer' => (int)$value,
			'float', 'double' => (float)$value,
			'bool', 'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
			'array'   => is_array($value) ? $value : (array)$value,
			default   => $default,
		};
	}

	public function hasInput(string $key) {
		return array_key_exists($key, $this->getInputs());
	}

    public function getHeader($_param, $_type = '', $_default = '') {
		if (isset($this->headers[$_param])) {

			if (empty($_type)) {
				if(is_array($this->headers[$_param]) || is_object($this->headers[$_param]))
				{
					return $this->headers[$_param];
				}
				else
				{
					if (trim($this->headers[$_param]) == '')
						return $_default;
					else
						return $this->headers[$_param];
				}
			}
			else {
				$typeMatches = false;
				if ('string' == $_type) {
					if (is_string($this->headers[$_param]))
						$typeMatches = true;
				}
				elseif ('int' == $_type) {
					if (is_int($this->headers[$_param]))
						$typeMatches = true;
				}
				elseif ('numeric' == $_type) {
					if (is_numeric($this->headers[$_param]))
						$typeMatches = true;
				}

				if ($typeMatches)
					return $this->headers[$_param];
				else
					return $_default;
			}
		}
		else
		{
			return $_default;
		}
	}
    
	public function getPost() {
		if ($_SERVER['REQUEST_METHOD'] == "POST")
			return $_POST;
	}

	public function getGet() {
		if ($_SERVER['REQUEST_METHOD'] == "GET")
			return $_GET;
	}
        
	public function getJson() {
		return $this->jsonData;
	}
        
	public function getHeaders(){
		if($this->headers){
			return $this->headers;
		}
		return array();
	}

	public function isMethod(string $name) :  bool {
		return strtolower($name) === strtolower($_SERVER['REQUEST_METHOD']);
	}

    
	public function setupRequest($modules) {
		
		$this->sanitizeRequest();

		$this->requestURI = $this->requestURI ?: $_SERVER['REQUEST_URI'];
		$path = strtok($this->requestURI, '?'); // Remove query string
		$path = trim($path, '/');

		$pathParts = $path !== '' ? explode('/', $path) : [];
    	$this->uriParts = $pathParts;


		$router = TinyPHP_Router::getInstance();
		$route = $router->matchRoute($this->uriParts);

		$routeMatched = false;
		if ($route !== false)
		{
			$routeMatched = true;
			if ($route !== false) {
				$router->convertRoute($route, $this);
			}

			$paramVars = $this->getParams();
			if (!empty($paramVars)) {
				array_walk_recursive($paramVars, [$this, 'doSanitization']);
			}
			$this->setParams($paramVars);
		}		


		 // Default parsing if no route matched
		if (!$routeMatched)
		{
			$step = 0;

			// Module
			$moduleName = $pathParts[$step] ?? 'app';
			$this->moduleName = in_array($moduleName, $modules) ? strtolower($moduleName) : 'app';
			if ($this->moduleName !== 'app') $step++;

			
			// Controller
			$this->controllerName = strtolower($pathParts[$step] ?? 'index');
			$step++;

			
			// Action
			$this->actionName = strtolower($pathParts[$step] ?? 'index');
			$step++;


			// Parameters
			$paramVars = [];
			$params = array_slice($pathParts, $step);
			for ($i = 0; $i < count($params); $i++) {
				if ($params[$i] !== '') {
					$key = $params[$i];
					$value = $params[$i + 1] ?? '';
					$paramVars[$key] = $value;
					$i++;
				}
			}

			// Merge extra parameters
			if (!empty($_extraParams)) {
				$paramVars = array_merge($paramVars, $_extraParams);
			}

			$this->setParams($paramVars);
		}

		// Headers
		$this->headers = getallheaders();

		// Parse json input request
		$this->parseJson();
		
		return $this;
	}

	public function getCsrfToken() {
		
		// first check if exist in header
        $csrfHeader = $this->getHeader("X-CSRF-Token");
        if ($csrfHeader) {
            return $csrfHeader;
        }

        // fallback check in POST
        $csrfInput = $this->getInput("_token");
        if( !$csrfInput ) {
            return $csrfInput;
        }

        return "";
	}

	public function expectedJson() {

		// Check if the request has X-Requested-With: XMLHttpRequest (classic AJAX)
    	$isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

    	// Check if the Accept header contains application/json
    	$acceptHeader = $_SERVER['HTTP_ACCEPT'] ?? '';
    	$wantsJson = stripos($acceptHeader, 'application/json') !== false;

    	return $isAjax || $wantsJson;
	}

	public function getIp() {
		
		// Define the list of headers that might contain the client's IP address
        $ipHeaders = [
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        ];

		// Iterate through the headers to find a valid IP address
        foreach ($ipHeaders as $header) {
            if (!empty($_SERVER[$header])) {
                // If the header contains multiple IPs, split them and validate each
                $ipList = explode(',', $_SERVER[$header]);
                foreach ($ipList as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP)) {
                        return $ip;
                    }
                }
            }
        }

        // Return null if no valid IP address is found
        return null;
	}

	public function getUserAgent() {
		
        // Retrieve the User-Agent header from the request
        return $_SERVER['HTTP_USER_AGENT'] ?? null;
    }

	
	public function getDeviceInfo() {
		
		$userAgent = $this->getUserAgent();

		$device = 'Unknown Device';
		$os = 'Unknown OS';
		$browser = 'Unknown Browser';

		// Detect OS
		if (preg_match('/linux/i', $userAgent)) $os = 'Linux';
		elseif (preg_match('/macintosh|mac os x/i', $userAgent)) $os = 'Mac';
		elseif (preg_match('/windows|win32/i', $userAgent)) $os = 'Windows';

		// Detect Browser
		if (preg_match('/MSIE/i', $userAgent)) $browser = 'Internet Explorer';
		elseif (preg_match('/Firefox/i', $userAgent)) $browser = 'Firefox';
		elseif (preg_match('/Chrome/i', $userAgent)) $browser = 'Chrome';
		elseif (preg_match('/Safari/i', $userAgent)) $browser = 'Safari';
		elseif (preg_match('/Opera/i', $userAgent)) $browser = 'Opera';
		elseif (preg_match('/Edge/i', $userAgent)) $browser = 'Edge';

		// Detect Device Type
		if (preg_match('/mobile/i', $userAgent)) $device = 'Mobile';
		elseif (preg_match('/tablet/i', $userAgent)) $device = 'Tablet';
		else $device = 'Desktop';

		return [
			'os' => $os,
			'browser' => $browser,
			'device' => $device,
		];
	}

	public function getRequestUri() {
		return $this->requestURI;
	}

	public function pathIs(string|array $patterns) {

		$uri = $this->getRequestUri();

		foreach ((array) $patterns as $pattern) {
			
			// Ensure the pattern starts with '/'
			if ($pattern && $pattern[0] !== '/') {
				$pattern = '/' . $pattern;
			}

			// Use fnmatch to support '*' wildcard
			if (fnmatch($pattern, $uri)) {
				return true;
			}
		}

		return false;
	}

	public function getOrigin(): ?string
	{
		foreach ($_SERVER as $key => $value) {
			if (strpos($key, 'HTTP_') === 0) {
				$headerName = str_replace('_', '-', substr($key, 5));
				$headerName = ucwords(strtolower($headerName), '-');
				if ($headerName === 'Origin' && !empty($value)) {
					return str_replace(["\r", "\n"], '', trim($value));
				}
			}
		}

		return null;
	}

	public function isStaticFile() {
		
		$requestURI = strtok($this->getRequestUri(), '?');
    	$basename = basename($requestURI);

    	// Check for dot and common file extensions
    	return strpos($basename, '.') !== false && preg_match('/\.(jpg|jpeg|png|gif|css|js|svg|ico|woff2|ttf|pdf)$/i', $basename);
	}
	

	public function sanitizeRequest() {

		$sources = [$_GET, $_POST, $_REQUEST];
		foreach ($sources as $key => $source) {
			array_walk_recursive($source, [$this, 'doSanitization']);
		}
	}

	private function doSanitization(&$item) {

		if (is_string($item)) {
			static $purifier;

			if (!$purifier) {

				$config = \HTMLPurifier_Config::createDefault();

				// Whitelist allowed tags and attributes (including safe <img> handling)
				$config->set('HTML.Allowed', 
					'h1,h2,h3,h4,h5,h6,b,div,span,font,p,i,a[href],ul,ol,table,tr,td,li,pre,hr,blockquote,img[src|alt|width|height],strong,br,small'
				);

				// Optional: prevent ID attributes from being added
				$config->set('Attr.EnableID', false);

				// Optional: disable caching (useful for development)
				$config->set('Cache.DefinitionImpl', null);

				// Create purifier instance
				$purifier = new \HTMLPurifier($config);
			}

			// Purify the string
			$item = $purifier->purify($item);
		}
	}
}
?>