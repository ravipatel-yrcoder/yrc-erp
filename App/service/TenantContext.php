<?php
class Service_TenantContext
{
    public int $companyId;
    public int $userId;

    public function __construct(int $companyId, int $userId)
    {
        $this->companyId = $companyId;
        $this->userId = $userId;
    }
}