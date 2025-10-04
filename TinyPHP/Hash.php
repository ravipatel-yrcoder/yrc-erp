<?php
class TinyPHP_Hash
{
    /**
     * Hash a password using configured algorithm.
     */
    public static function make(string $password): string
    {
        $driver = config('hashing.driver', 'bcrypt');

        if ($driver === 'argon2id') {
            $options = config('hashing.argon', [
                'memory_cost' => 65536,
                'time_cost' => 4,
                'threads' => 1,
            ]);
            return password_hash($password, PASSWORD_ARGON2ID, $options);
        }

        // Default to bcrypt
        $options = config('hashing.bcrypt', ['cost' => 10]);
        return password_hash($password, PASSWORD_BCRYPT, $options);
    }

    /**
     * Verify a password against a hash.
     */
    public static function check(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    /**
     * Determine if a hash needs rehashing based on current config.
     */
    public static function needsRehash(string $hash): bool
    {
        $driver = config('hashing.driver', 'bcrypt');

        if ($driver === 'argon2id') {
            $options = config('hashing.argon', [
                'memory_cost' => 65536,
                'time_cost' => 4,
                'threads' => 1,
            ]);
            return password_needs_rehash($hash, PASSWORD_ARGON2ID, $options);
        }

        $options = config('hashing.bcrypt', ['cost' => 10]);
        return password_needs_rehash($hash, PASSWORD_BCRYPT, $options);
    }
}