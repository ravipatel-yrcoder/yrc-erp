<?php
class TinyPHP_HandleCors extends TinyPHP_Middleware {
    
    protected function process(TinyPHP_Request $request, Closure $next) {
        
        $corsConfig = config("cors");
        $corsEnabledPaths = $corsConfig["paths"] ?? [];

        // Skip if CORS Enabled paths are not defined
        if( !$corsEnabledPaths ) return $next($request);

        
        // Skip if CORS Enabled paths are not matching
        if( !$request->pathIs($corsEnabledPaths) ) return $next($request);

        // Skip if path is excluded for CORS
        $corsExceptPaths = $corsConfig["except_paths"] ?? [];
        if( $corsExceptPaths && $request->pathIs($corsExceptPaths) ) return $next($request);


        // Hanlde CORS headers
        $origin = $request->getOrigin();

        $allowedOrigins = (array) $corsConfig["allowed_origins"] ?? [];
        if ( in_array("*", $allowedOrigins) ) {$allowedOrigins = ["*"];}

        $allowAllOrigin = in_array("*", $allowedOrigins);
        $isOriginAllowed = false;
        if( $allowAllOrigin ) {
            header("Access-Control-Allow-Origin: *");
            $isOriginAllowed = true;
        } else if (in_array($origin, $allowedOrigins, true)) {
            header("Access-Control-Allow-Origin: {$origin}");
            $isOriginAllowed = true;
        }


        // Credentials
        $supportsCredentials = (boolean) $corsConfig["supports_credentials"] ?? false;
        if ($supportsCredentials && !$allowAllOrigin && $isOriginAllowed) {
            header('Access-Control-Allow-Credentials: true');
        }


        // Exposed headers
        $exposedHeaders = (array) $corsConfig["exposed_headers"] ?? [];
        if( $exposedHeaders ) {
            header("Access-Control-Expose-Headers: " . implode(', ', $exposedHeaders));
        }

        // Handle preflight OPTIONS request
        if( $request->isMethod("OPTIONS") ) {

            if( $isOriginAllowed )
            {
                $allowedMethods = (array) $corsConfig["allowed_methods"] ?? [];        
                $allowedHeaders = (array) $corsConfig["allowed_headers"] ?? ["*"];
                $maxAge = (int) $corsConfig["max_age"] ?? 0;        
                

                if( in_array("*", $allowedMethods) ) {
                    $allowedMethods = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'];
                }

                header("Access-Control-Allow-Methods: " . implode(', ', $allowedMethods));
                header("Access-Control-Allow-Headers: " . implode(', ', $allowedHeaders));
                if( $maxAge > 0 ) {
                    header("Access-Control-Max-Age: " . $maxAge);
                }                
            }

            exit(0);
        }        
        
        return $next($request);
    }
}