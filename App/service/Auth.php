<?php
class Service_Auth extends Service_Base {

    private $user = null;
    
    /**
     * Get current authenticated user
     */
    public function user(): mixed {

        if( $this->user !== null ) {
            return $this->user;
        }

        $accessToken = $this->getTokenFromRequest();

        if( !$accessToken ) {
            return null;
        }

        // validate token
        $payload = Service_AuthToken::validateAccessToken($accessToken);
        if( !$payload ) {
            return null;
        }

        $uid = $payload["uid"] ?? 0;
        
        global $db;
        $sql = "SELECT a.*, b.name AS company_name, b.status AS company_status, b.plan AS company_plan FROM users AS a
                INNER JOIN companies AS b ON b.id=a.company_id
                WHERE
                a.id=?";
        $user = $db->fetchOne($sql, [$uid]);
        if( !$user || $user->status != 'active' || $user->company_status != 'active' ) {
            return null;
        }

        $this->user = $user;

        return $this->user;
    }
    

    public function getCompanyId(): int {
        return $this->user()->company_id ?? 0;
    }
    
    
    public function login(Models_User $user, string $clientType): mixed {

        // Auth Token Service generate token

        $tokens = Service_AuthToken::generateTokens($user);
        if( !$tokens ) {
            return null;
        }


        // Set cookie and session based on client type
        if( $clientType === "web" ) {

            // set tokens in cookies
            $this->setAccessCookie($tokens["access_token"], $tokens["access_token_expires_at"]);
            $this->setRefreshCookie($tokens["refresh_token"], $tokens["refresh_token_expires_at"]);
            
            // set user id in session for further use
            #TO:DO Session logic goes here
        }

        return [
            'access_token' => $tokens["access_token"],
            'refresh_token' => $tokens["refresh_token"],
            'expires_in' => $tokens["access_token_expires_in"],
        ];
    }


    public function renewAccessToken(string $refreshToken, string $clientType): mixed {

        $tokens = Service_AuthToken::refreshAccessToken($refreshToken);
        if (!$tokens) {
            return null;
        }

        
        // Set cookie and session based on client type
        if( $clientType === "web" ) {

            // set tokens in cookies(will be used in next request)
            $this->setAccessCookie($tokens["access_token"], $tokens["access_token_expires_at"]);

            // sync current request
            $_COOKIE['access_token'] = $tokens["access_token"];

            // set user id in session for further use
            #TO:DO Session logic goes heress
        }

        return [
            'access_token' => $tokens["access_token"],
            'refresh_token' => $tokens["refresh_token"],
            'expires_in' => $tokens["access_token_expires_in"],
        ];
    }


    public function logout(string $clientType, $refreshToken=null): array {

        $return = ["success" => false, "message" => "", "httpCode" => 500];

        if( $clientType == "web" ) {

            // take from cookie
            $refreshToken = cookie("refresh_token");
        }


        if( !$refreshToken ) {
            $return["message"] = "Missing refresh token";
            $return["httpCode"] = 400;
            return $return;
        }
        
        // revoke refresh token
        $authToken = new Models_AuthToken();
        $authToken->fetchByProperty(["token_hash", "revoked"], [$refreshToken, 0]);
        if( $authToken->isEmpty ) {
            $return["message"] = "Invalid refresh token";
            $return["httpCode"] = 401;
            return $return;
        }

        $authToken->revoked = 1;
        $authToken->update(["revoked"]);
        if( !$authToken->getUpdatedRows() > 0 ) {
            $return["message"] = "Unable to revoke refresh token";
            $return["httpCode"] = 500;
            return $return;
        }


        // cleaar httpOnly cookie for web logout
        if( $clientType == "web" ) {
            $this->clearAuthCookies();
        }

        return ["success" => true, "message" => "Logout successful", "httpCode" => 200];
    }


    public function check() {
        
        return $this->user() !== null;
    }


    private function getTokenFromRequest() {

        $request = TinyPHP_Request::getInstance();
        
        $jwtToken = null;
        
        $authHeader = $request->getHeader("Authorization");
        if ($authHeader) {
            $jwtToken = trim(substr($authHeader, 7));
        }

        if( !$jwtToken ) {

            // take from cookie
            $jwtToken = cookie("access_token");
        }

        return $jwtToken;
    }



    /**
     * Cookies
     */
    private function setAccessCookie(string $token, int $exp)
    {
        setcookie('access_token', $token, [
            'expires' => $exp,
            'path' => '/',
            'secure' => config('APP_ENV') === 'production',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    private function setRefreshCookie(string $token, int $exp) {
        
        setcookie('refresh_token', $token, [
            'expires' => $exp,
            'path' => '/',
            'secure' => config('APP_ENV') === 'production',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    private function clearAuthCookies()
    {
        setcookie('access_token', '', [
            'expires' => time() - 3600,
            'path' => '/',
            'secure' => config('APP_ENV') === 'production',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        setcookie('refresh_token', '', [
            'expires' => time() - 3600,
            'path' => '/',
            'secure' => config('APP_ENV') === 'production',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }


}
?>