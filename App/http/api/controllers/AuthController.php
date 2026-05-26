<?php
class Api_AuthController extends TinyPHP_Controller {

	
    /**
     * POST /api/auth/login
     * Login user and return access + refresh tokens
     */
    public function loginAction(TinyPHP_Request $request) {

        $email    = $request->getInput("email");
        $password = $request->getInput("password");

        if (empty($email) || empty($password)) {
            return response([], "Email and password are required", 422)->sendJson();
        }

        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

        if (auth()->isRateLimited($ip)) {
            return response([], "Too many failed login attempts. Please try again in 15 minutes.", 429)->sendJson();
        }

        $user = new Models_User();
        $user->fetchByProperty("email", $email);

        if ($user->isEmpty || !verifyPassword($password, $user->password)) {
            auth()->recordFailedLoginAttempt($ip);
            return response([], "Invalid credentials", 401)->sendJson();
        }

        auth()->clearRateLimit($ip);

        $tokens = auth()->login($user, $request->getHeader("X-Client-Type"));
        if (!$tokens) {
            return response([], "Login failed: unable to authenticate user or generate access token", 500)->sendJson();
        }

        return response($tokens, "Login successfully")->sendJson();
    }


    /**
     * POST /api/auth/logout
     * Logout user
     */
    public function logoutAction(TinyPHP_Request $request) {

        $refreshToken = $request->getInput("refresh_token", "string", null);
        auth()->logout($request->getHeader("X-Client-Type"), $refreshToken);

        return response([], "Logout successfully", 200)->sendJson();
    }


    /**
     * POST /api/auth/forgot-password
     */
    public function forgotPasswordAction(TinyPHP_Request $request) {

        $email = trim($request->getInput("email", "String", ""));

        if (empty($email) || !isValidEmail($email)) {
            return response([], "A valid email address is required.", 422)->sendJson();
        }

        auth()->forgotPassword($email);

        // Always 200 — never reveal whether the email exists
        return response([], "If that email is registered, you will receive a password reset link shortly.", 200)->sendJson();
    }


    /**
     * POST /api/auth/reset-password
     */
    public function resetPasswordAction(TinyPHP_Request $request) {

        $token                = $request->getInput("token", "String", "");
        $email                = $request->getInput("email", "String", "");
        $password             = $request->getInput("password", "String", "");
        $passwordConfirmation = $request->getInput("password_confirmation", "String", "");

        if (empty($token) || empty($email)) {
            return response([], "Invalid or missing reset token.", 422)->sendJson();
        }

        $result = auth()->resetPassword($token, $email, $password, $passwordConfirmation);

        if (!$result["success"]) {
            return response([], "Password reset failed.", 422)->errors($result["errors"])->sendJson();
        }

        return response([], "Password reset successfully. Please log in with your new password.", 200)->sendJson();
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