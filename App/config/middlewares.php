<?php
return [
    'global' => [Middleware_Log::class],
    'app' => [Middleware_Csrf::class, Middleware_AppAuth::class, Middleware_AppRedirectIfAuth::class],
    'api' => [Middleware_ApiAuth::class],
];