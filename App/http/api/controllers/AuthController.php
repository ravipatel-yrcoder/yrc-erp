<?php
class Api_AuthController extends TinyPHP_Controller {

	
    /**
     * POST /api/auth/login
     * Login user and return access + refresh tokens
     */
    public function loginAction(TinyPHP_Request $request) {        
        
        $email = $request->getInput("email");
		$password = $request->getInput("password");

        // Validate required fields
        if (empty($email) || empty($password)) {
            return response([], "Email and password are required", 422)->sendJson();
        }

		$user = new Models_User();
		$user->fetchByProperty("email", $email);

        if( $user->isEmpty || !verifyPassword($password, $user->password) ) {            
            return response([], "Invalid credentials", 401)->sendJson();
		}


        // this generates the access tokens and save to DB and Cookie
        $tokens = auth()->login($user, $request->getHeader("X-Client-Type"));
        if( !$tokens ) {
            return response([], "Login failed: unable to authenticate user or generate access token", 500)->sendJson();
        }

        // send tokens in response
        return response($tokens, "Login successfully")->sendJson();
    }


    /**
     * POST /api/auth/logout
     * Logout user
     */
    public function logoutAction(TinyPHP_Request $request) {

        $refreshToken = $request->getInput("refresh_token", "string", null);
        $response = auth()->logout($request->getHeader("X-Client-Type"), $refreshToken);

        if( $response["success"] === true ) {
            return response([], "Logout successfully", 200)->sendJson();
        }

        return response([], "Logout could not complete. Try again", $response["httpCode"])->errors([$response["message"]])->sendJson();
    }


    public function refreshTokenAction(TinyPHP_Request $request) {

        $clientType = $request->getHeader("X-Client-Type");
        if( $clientType === "web" ) {
            $refreshToken = cookie("refresh_token");
        } else {
            $refreshToken = $request->getInput("refresh_token");
        }
		
        
        // this generates the access tokens and save to DB and Cookie
        $tokens = auth()->renewAccessToken($refreshToken, $clientType);
        if( !$tokens ) {
            return response([], "Refresh failed: unable to refresh access token", 500)->sendJson();
        }

        // send tokens in response
        return response($tokens, "Token refreshed successfully")->sendJson();
    }


}