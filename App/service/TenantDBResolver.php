<?php
class Service_TenantDBResolver
{
    public static function resolve(int $companyId)
    {
        // Today: return global DB
        return DB();

        // Tomorrow:
        // return DB::connectToTenantDatabase($companyId);
    }
}