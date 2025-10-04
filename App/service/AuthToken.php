<?php
class Service_AuthToken extends Service_Base {


    /**
     * Token Generation and Save Refresh Token in DB
     */
    public static function generateTokens(Models_User $user): mixed
    {
        // Generate short-lived Access Token (JWT)
        $payload = ["uid" => $user->id];
        $accessToken = TinyPHP_Jwt::generateAccessToken($payload);
        $accessPayload = TinyPHP_Jwt::decodeToken($accessToken);


        // Refresh token hash
        $opaqueToken = self::generateOpaqueToken();
        $refreshTokenHash = $opaqueToken["hash"];


        // Save refresh token in DB
        $authToken = new Models_AuthToken();
        $authToken->user_id = $user->id;
        $authToken->token_type = "refresh";
        $authToken->token_hash = $refreshTokenHash;
        $authToken->expires_at = date('Y-m-d H:i:s', $opaqueToken["expires_at"]);
        $authToken->device_info = request()->getDeviceInfo();
        $authToken->ip_address = request()->getIp();
        $tokenId = $authToken->create();
        if( !$tokenId ) {
            return null;
        }

        return [
            "access_token" => $accessToken,
            "access_token_expires_at" => $accessPayload["exp"],
            "access_token_expires_in" => $accessPayload["exp"] - time(),
            "refresh_token" => $refreshTokenHash,
            "refresh_token_expires_at" => $opaqueToken["expires_at"],
        ];
    }


    /**
     * Opaque Token
     */
    public static function generateOpaqueToken(): array {

        $ttl = config('jwt.refresh_ttl', 20160); // minutes
        $now = time();
        
        $rawToken = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $rawToken);
        $expiresAt = $now + ($ttl * 60);

        return [
            'raw' => $rawToken,
            'hash' => $tokenHash,
            'expires_at' => $expiresAt,
        ];
    }


    public static function revokeRefreshToken(string $refreshToken): bool {
        return true;
    }



    /**
     * Token Validation
     */
    public static function validateAccessToken(?string $token): ?array
    {
        if (!$token) return null;
        return TinyPHP_Jwt::validateToken($token) ? TinyPHP_Jwt::decodeToken($token) : null;
    }

    /**
     * Refresh Access Token
     */
    public static function refreshAccessToken(string $refreshToken): mixed {
        
        $authToken = new Models_AuthToken();
        $authToken->fetchByProperty(
            ["token_type", "token_hash", "revoked"],
            ["refresh", $refreshToken, 0]
        );

        if ($authToken->isEmpty) return null;

        $refreshTokenExpiresAt = strtotime($authToken->expires_at);


        if (time() > $refreshTokenExpiresAt) {
            $authToken->revoked = 1;
            $authToken->update(["revoked", "last_used_at"]);
            return null;
        }

        $accessToken = TinyPHP_Jwt::generateAccessToken(['uid' => $authToken->user_id]);
        $accessPayload = TinyPHP_Jwt::decodeToken($accessToken);


        return [
            "access_token" => $accessToken,
            "access_token_expires_at" => $accessPayload["exp"],
            "access_token_expires_in" => $accessPayload["exp"] - time(),
            "refresh_token" => $refreshToken,
            "refresh_token_expires_at" => $refreshTokenExpiresAt,
        ];
    }

    /**
     * Logout
     */
    /*
    public static function logoutSingle(string $refreshToken, bool $clearCookies = false): bool
    {
        $authToken = new Models_AuthToken();
        $authToken->fetchByProperty(
            ["token_type", "token_hash", "revoked"],
            ["refresh", $refreshToken, 0]
        );

        if ($authToken->isEmpty) return false;

        $authToken->revoked = 1;
        $authToken->update(["revoked", "last_used_at"]);

        if ($clearCookies) {
            self::clearAuthCookies();
        }

        return true;
    }

    public static function logoutAll(int $userId, bool $clearCookies = false): int
    {
        $authToken = new Models_AuthToken();
        $count = $authToken->updateByConditions(
            ["revoked" => 1, "last_used_at" => date('Y-m-d H:i:s')],
            ["user_id" => $userId, "token_type" => "refresh", "revoked" => 0]
        );

        if ($clearCookies) {
            self::clearAuthCookies();
        }

        return $count;
    }
    */
}
?>