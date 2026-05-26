<?php
class Service_Auth extends Service_PlatformBase {

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
        
        $sql = "SELECT a.*, b.name AS company_name, b.status AS company_status FROM users AS a
                INNER JOIN companies AS b ON b.id=a.company_id
                WHERE
                a.id=?";
        $user = $this->db->fetchOne($sql, [$uid]);
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

        // For web clients read from cookie. Also use cookie as fallback
        // when $refreshToken is not in the request body (web app never sends it in body).
        if ($clientType == "web" || !$refreshToken) {
            $refreshToken = cookie("refresh_token") ?: $refreshToken;
        }

        if (!$refreshToken) {
            // Already logged out (no cookie) — treat as success and clear cookies anyway
            if ($clientType == "web") $this->clearAuthCookies();
            return ["success" => true, "message" => "Logout successful", "httpCode" => 200];
        }

        // Revoke refresh token; filter by token_type to match the token creation pattern
        $authToken = new Models_AuthToken();
        $authToken->fetchByProperty(["token_type", "token_hash", "revoked"], ["refresh", $refreshToken, 0]);

        if (!$authToken->isEmpty) {
            $authToken->revoked = 1;
            $authToken->update(["revoked"]);
        }
        // If token is already revoked or not found, still treat as successful logout (idempotent)

        // Clear httpOnly cookies for web clients
        if ($clientType == "web") {
            $this->clearAuthCookies();
        }

        return ["success" => true, "message" => "Logout successful", "httpCode" => 200];
    }


    public function check() {

        return $this->user() !== null;
    }


    // -------------------------------------------------------------------------
    // Rate limiting
    // -------------------------------------------------------------------------

    public function isRateLimited(string $ip): bool
    {
        $row = $this->db->fetchOne(
            "SELECT blocked_until FROM login_rate_limits WHERE ip = ? LIMIT 1",
            [$ip]
        );
        if (!$row || !$row->blocked_until) {
            return false;
        }
        return strtotime($row->blocked_until) > time();
    }

    public function recordFailedLoginAttempt(string $ip): void
    {
        $now = date("Y-m-d H:i:s");
        $row = $this->db->fetchOne(
            "SELECT id, attempts FROM login_rate_limits WHERE ip = ? LIMIT 1",
            [$ip]
        );
        if ($row) {
            $attempts    = (int) $row->attempts + 1;
            $blockedUntil = $attempts >= 5 ? date("Y-m-d H:i:s", time() + 900) : null;
            $this->db->update("login_rate_limits", [
                "attempts"        => $attempts,
                "blocked_until"   => $blockedUntil,
                "last_attempt_at" => $now,
            ], "id = {$row->id}");
        } else {
            $this->db->insert("login_rate_limits", [
                "ip"              => $ip,
                "attempts"        => 1,
                "blocked_until"   => null,
                "last_attempt_at" => $now,
            ]);
        }
    }

    public function clearRateLimit(string $ip): void
    {
        $this->db->query("DELETE FROM login_rate_limits WHERE ip = ?", [$ip]);
    }


    // -------------------------------------------------------------------------
    // Password reset
    // -------------------------------------------------------------------------

    public function forgotPassword(string $email): array
    {
        $user = $this->db->fetchOne(
            "SELECT id, first_name FROM users WHERE email = ? AND status = 'active' LIMIT 1",
            [strtolower(trim($email))]
        );

        // Always return success — never reveal whether the email exists
        if (!$user) {
            return ["success" => true];
        }

        $now = date("Y-m-d H:i:s");

        // Invalidate any existing unused tokens for this user
        $this->db->query(
            "UPDATE password_resets SET used_at = ? WHERE user_id = ? AND used_at IS NULL",
            [$now, $user->id]
        );

        $rawToken  = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $rawToken);
        $expiresAt = date("Y-m-d H:i:s", time() + 3600);

        $this->db->insert("password_resets", [
            "user_id"    => $user->id,
            "token_hash" => $tokenHash,
            "expires_at" => $expiresAt,
            "used_at"    => null,
            "created_at" => $now,
        ]);

        $this->sendPasswordResetEmail(strtolower(trim($email)), $rawToken, $user->first_name);

        return ["success" => true];
    }

    public function resetPassword(string $rawToken, string $email, string $password, string $passwordConfirmation): array
    {
        if (strlen($password) < 8) {
            $this->addError(validationErrMsg('password_too_short', 'Password'), 'password');
        }
        if ($password !== $passwordConfirmation) {
            $this->addError(validationErrMsg('password_mismatch', ''), 'password_confirmation');
        }
        if ($this->hasErrors()) {
            return ["success" => false, "errors" => $this->getErrors()];
        }

        $tokenHash = hash('sha256', $rawToken);
        $row = $this->db->fetchOne(
            "SELECT pr.id, pr.user_id, pr.expires_at, pr.used_at
             FROM password_resets pr
             INNER JOIN users u ON u.id = pr.user_id
             WHERE pr.token_hash = ? AND u.email = ?
             LIMIT 1",
            [$tokenHash, strtolower(trim($email))]
        );

        if (!$row || $row->used_at !== null) {
            $this->addError(validationErrMsg('invalid_reset_token', ''), 'token');
            return ["success" => false, "errors" => $this->getErrors()];
        }

        if (strtotime($row->expires_at) < time()) {
            $this->addError(validationErrMsg('expired_reset_token', ''), 'token');
            return ["success" => false, "errors" => $this->getErrors()];
        }

        $now = date("Y-m-d H:i:s");

        $this->db->startTransaction();
        try {
            $this->db->query(
                "UPDATE users SET password = ?, updated_at = ? WHERE id = ?",
                [hashPassword($password), $now, $row->user_id]
            );
            $this->db->query(
                "UPDATE password_resets SET used_at = ? WHERE id = ?",
                [$now, $row->id]
            );
            // Revoke all active tokens — force re-login everywhere
            $this->db->query(
                "UPDATE auth_tokens SET revoked = 1 WHERE user_id = ? AND revoked = 0",
                [$row->user_id]
            );
            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }

        return ["success" => true];
    }

    private function sendPasswordResetEmail(string $to, string $rawToken, string $name): void
    {
        $resetUrl = rtrim(config('app.url'), '/') . '/reset-password?token=' . urlencode($rawToken) . '&email=' . urlencode($to);
        $appName  = config('app.name');
        $fromEmail = config('app.support_email', 'noreply@zentraqone.com');

        $subject = "Reset your {$appName} password";
        $body    = "
            <div style='font-family:sans-serif;max-width:520px;margin:0 auto;'>
                <p>Hi {$name},</p>
                <p>We received a request to reset your <strong>{$appName}</strong> password. Click the button below to choose a new password.</p>
                <p style='text-align:center;margin:32px 0;'>
                    <a href='{$resetUrl}'
                       style='background:#0d6efd;color:#fff;padding:12px 28px;text-decoration:none;border-radius:6px;font-size:15px;display:inline-block;'>
                        Reset My Password
                    </a>
                </p>
                <p style='color:#666;font-size:13px;'>Or copy this link into your browser:<br>{$resetUrl}</p>
                <p style='color:#666;font-size:13px;'>This link expires in <strong>1 hour</strong>. If you did not request a password reset, you can safely ignore this email.</p>
                <hr style='border:none;border-top:1px solid #eee;margin:24px 0;'>
                <p style='color:#999;font-size:12px;'>The {$appName} Team</p>
            </div>
        ";

        $mailer = new Helpers_Mailer();
        $mailer->sendMail("{$appName} <{$fromEmail}>", $to, $subject, $body);
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