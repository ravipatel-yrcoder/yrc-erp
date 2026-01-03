<?php
class Api_AuthController extends TinyPHP_Controller {

	
    /**
     * POST /api/auth/login
     * Login user and return access + refresh tokens
     */
    public function loginAction(TinyPHP_Request $request) {        
        
        if( !$request->isMethod("post") ) {
            response([], "Method not allowed", 405)->sendJson();
        }

        $email = $request->getInput("email");
		$password = $request->getInput("password");

        // Validate required fields
        if (empty($email) || empty($password)) {
            response([], "Email and password are required", 422)->sendJson();
        }

		$user = new Models_User();
		$user->fetchByProperty("email", $email);

        if( $user->isEmpty || !verifyPassword($password, $user->password) ) {            
            response([], "Invalid credentials", 401)->sendJson();
		}


        // this generates the access tokens and save to DB and Cookie
        $tokens = auth()->login($user, $request->getHeader("X-Client-Type"));
        if( !$tokens ) {
            response([], "Login failed: unable to authenticate user or generate access token", 500)->sendJson();
        }


        // send tokens in response
        response($tokens, "Login successfully")->sendJson();
    }


    /**
     * POST /api/auth/logout
     * Logout user
     */
    public function logoutAction(TinyPHP_Request $request) {

        if( !$request->isMethod("post") ) {
            response([], "Method not allowed", 405)->sendJson();
        }

        $refreshToken = $request->getInput("refresh_token", "string", null);
        $response = auth()->logout($request->getHeader("X-Client-Type"), $refreshToken);

        if( $response["success"] === true ) {
            response([], "Logout successfully", 200)->sendJson();
        }

        response([], "Logout could not complete. Try again", $response["httpCode"])->errors([$response["message"]])->sendJson();
    }


    public function refreshTokenAction(TinyPHP_Request $request) {

        if( !$request->isMethod("post") ) {
            response([], "Method not allowed", 405)->sendJson();
        }
        
        $clientType = $request->getHeader("X-Client-Type");
        if( $clientType === "web" ) {
            $refreshToken = cookie("refresh_token");
        } else {
            $refreshToken = $request->getInput("refresh_token");
        }
		
        
        // this generates the access tokens and save to DB and Cookie
        $tokens = auth()->renewAccessToken($refreshToken, $clientType);
        if( !$tokens ) {
            response([], "Refresh failed: unable to refresh access token", 500)->sendJson();
        }

        // send tokens in response
        response($tokens, "Token refreshed successfully")->sendJson();
    }


}