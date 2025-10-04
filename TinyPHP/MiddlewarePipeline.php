<?php
class TinyPHP_MiddlewarePipeline {
    
    protected array $middlewares = [];

    public function add(TinyPHP_Middleware $middleware) {
        $this->middlewares[] = $middleware;
    }

    public function handle(TinyPHP_Request $request, Closure $finalHandler) {
        $pipeline = array_reduce(
            array_reverse($this->middlewares),
            fn($next, $middleware) => fn($req) => $middleware->handle($req, $next),
            $finalHandler
        );

        return $pipeline($request);
    }
}