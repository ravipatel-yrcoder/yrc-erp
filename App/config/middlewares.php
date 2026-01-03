<?php
return [
    'global' => [Middleware_Log::class],
    'app' => [Middleware_Csrf::class, Middleware_AppRedirectIfAuth::class, Middleware_AppAuth::class],
    'api' => [Middleware_ApiAuth::class],
];