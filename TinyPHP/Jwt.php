<?php
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;

class TinyPHP_Jwt
{
    /**
     * Generate Access Token
     */
    public static function generateAccessToken(array $payload = []): string
    {
        $ttl = config('jwt.ttl', 60); // minutes
        $now = time();

        $payload['iat'] = $now;
        $payload['exp'] = $now + ($ttl * 60); // seconds

        return JWT::encode($payload, config('jwt.secret'), config('jwt.algo'));
    }

    /**
     * Generate Refresh Token
     */
    public static function generateRefreshToken(array $payload = []): string
    {
        $ttl = config('jwt.refresh_ttl', 20160); // minutes
        $now = time();

        $payload['iat'] = $now;
        $payload['exp'] = $now + ($ttl * 60);
        $payload['jti'] = bin2hex(random_bytes(32)); // unique ID

        return JWT::encode($payload, config('jwt.secret'), config('jwt.algo'));
    }

    /**
     * Decode Token
     */
    public static function decodeToken(string $token): ?array
    {
        try {
            $decoded = JWT::decode($token, new Key(config('jwt.secret'), config('jwt.algo')));
            return (array)$decoded;
        } catch (ExpiredException $e) {
            return ['error' => 'expired'];
        } catch (SignatureInvalidException $e) {
            return ['error' => 'invalid_signature'];
        } catch (\Exception $e) {
            return ['error' => 'invalid_token'];
        }
    }

    /**
     * Validate Token (signature + expiration)
     */
    public static function validateToken(string $token): bool
    {
        $decoded = self::decodeToken($token);

        if (!$decoded || isset($decoded['error'])) {
            return false;
        }

        // Check expiration
        if (isset($decoded['exp']) && time() > $decoded['exp']) {
            return false;
        }

        return true;
    }

    /**
     * Refresh Access Token using Refresh Token
     */
    public static function refreshAccessToken(string $refreshToken): ?string
    {
        if (!self::validateToken($refreshToken)) {
            return null;
        }

        $decoded = self::decodeToken($refreshToken);

        // Remove claims that will be re-generated
        unset($decoded['iat'], $decoded['exp'], $decoded['jti']);

        return self::generateAccessToken($decoded);
    }

}