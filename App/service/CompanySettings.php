<?php
class Service_CompanySettings extends Service_Base
{
    private int $companyId;

    public function __construct(Service_TenantContext $context) {
        parent::__construct($context);
        $this->companyId = $context->companyId;
    }

    /**
     * Get a single setting value. Returns $default if not found.
     */
    public function get(string $key, $default = null): mixed {
        $row = $this->db->fetchOne(
            "SELECT setting_value FROM company_settings WHERE company_id = ? AND setting_key = ? LIMIT 1",
            [$this->companyId, $key]
        );
        return $row ? $row->setting_value : $default;
    }

    /**
     * Set a single setting value (upsert).
     */
    public function set(string $key, $value): void {
        $now = date('Y-m-d H:i:s');
        $existing = $this->db->fetchOne(
            "SELECT id FROM company_settings WHERE company_id = ? AND setting_key = ? LIMIT 1",
            [$this->companyId, $key]
        );
        if ($existing) {
            $this->db->update("company_settings",
                ['setting_value' => $value, 'updated_at' => $now],
                "id = {$existing->id}"
            );
        } else {
            $this->db->insert("company_settings", [
                'company_id'    => $this->companyId,
                'setting_key'   => $key,
                'setting_value' => $value,
                'updated_at'    => $now,
            ]);
        }
    }

    /**
     * Batch-set multiple settings.
     */
    public function setMultiple(array $data): void {
        foreach ($data as $key => $value) {
            $this->set($key, $value);
        }
    }

    /**
     * Get all settings whose keys start with $prefix (e.g. 'round_off').
     * Returns associative array keyed by the full setting_key.
     */
    public function getGroup(string $prefix): array {
        $rows = $this->db->fetchAll(
            "SELECT setting_key, setting_value FROM company_settings
             WHERE company_id = ? AND setting_key LIKE ?",
            [$this->companyId, $prefix . '.%']
        );
        $result = [];
        foreach ($rows as $row) {
            $result[$row->setting_key] = $row->setting_value;
        }
        return $result;
    }

    /**
     * Seed default round-off settings for a newly registered company.
     * Uses INSERT IGNORE so it is safe to call multiple times.
     */
    public static function seedDefaults(int $companyId, $db): void {
        $now      = date('Y-m-d H:i:s');
        $defaults = [
            'round_off.mode'     => 'manual',
            'round_off.round_to' => '1.00',
            'round_off.method'   => 'nearest',
            'so_pdf_template'    => 'template_1',
            'po_pdf_template'    => 'template_1',
        ];
        foreach ($defaults as $key => $value) {
            $db->execute(
                "INSERT IGNORE INTO company_settings (company_id, setting_key, setting_value, updated_at)
                 VALUES (?, ?, ?, ?)",
                [$companyId, $key, $value, $now]
            );
        }
    }

    /**
     * Return round-off config as a typed array ready for JS / service use.
     */
    public function getRoundOffConfig(): array {
        $group = $this->getGroup('round_off');
        return [
            'mode'     => $group['round_off.mode']     ?? 'manual',
            'round_to' => (float) ($group['round_off.round_to'] ?? 1.00),
            'method'   => $group['round_off.method']   ?? 'nearest',
        ];
    }

    /**
     * Compute signed round-off amount given a pre-rounded base and config.
     * Returns 0.0 when mode is 'off'.
     */
    public static function computeRoundOff(float $amount, string $mode, float $roundTo, string $method): float {
        if ($mode === 'off' || $roundTo <= 0) return 0.0;

        switch ($method) {
            case 'up':
                $rounded = ceil($amount / $roundTo) * $roundTo;
                break;
            case 'down':
                $rounded = floor($amount / $roundTo) * $roundTo;
                break;
            default: // nearest
                $rounded = round($amount / $roundTo) * $roundTo;
        }

        return round($rounded - $amount, 4);
    }
}
?>
