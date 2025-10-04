<?php
use Illuminate\View\Engines\CompilerEngine;
use Illuminate\View\Engines\EngineResolver;
use Illuminate\View\Factory;
use Illuminate\View\FileViewFinder;
use Illuminate\View\Compilers\BladeCompiler;
use Illuminate\Events\Dispatcher;
use Illuminate\Filesystem\Filesystem;

class TinyPHP_BladeRenderer extends TinyPHP_ViewRenderer {

	private static $instance;
	private $blade;
	private $initialized = false;

	private function __construct(){}

	final public static function getInstance(){
		
		if(!isset(self::$instance)) {
			self::$instance = new self();
		}
		
		return self::$instance;
	}


	private function ensureInitialized(){
		
		if($this->initialized) return;
		
		$filesystem = new Filesystem();
		$viewPaths = [$this->getViewDir(), APP_PATH . '/resources/views', TINY_PHP_PATH . '/Views'];
		
		$viewFinder = new FileViewFinder($filesystem, $viewPaths);

		$compileDir = APP_PATH.'/storage/cache';
		if(!is_dir($compileDir)) mkdir($compileDir,0777,true);
		
		$bladeCompiler = new BladeCompiler($filesystem,$compileDir);

		$bladeCompiler->directive('csrf', function() {
			return '<?php echo csrfField(); ?>';
		});
		

		$resolver = new EngineResolver();
		$resolver->register('blade', function() use ($bladeCompiler,$filesystem) { 
			return new CompilerEngine($bladeCompiler, $filesystem); 
		});

		$this->blade = new Factory($resolver, $viewFinder, new Dispatcher());
		$this->blade->addExtension('blade.php','blade');

		$this->initialized = true;
	}

	public function render($forceReRender=false) {

		if($forceReRender === true) $this->initialized = false;

		$this->ensureInitialized();
		
		$viewFile = basename($this->getViewFile(), '.blade.php'); // e.g., "index"
		$vars = $this->getViewVars();
		
		//return $this->blade->make($viewFile, $vars)->render();

		ob_start();
		try {
			$output = $this->blade->make($viewFile, $vars)->render();
			ob_end_clean();
			return $output;
		} catch (\Throwable $e) {
			ob_end_clean();
			throw $e; // let TinyPHP_Exception handle it
		}

	}
}
?>