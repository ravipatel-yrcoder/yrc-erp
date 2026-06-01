<?php
class Admin_FeaturesController extends TinyPHP_Controller {

    public function indexAction(TinyPHP_Request $request) {
        
        //$db = DB('platform_db');
        $db = DB();        

        $features = $db->fetchAll(
            "SELECT f.id, f.`key`, f.name, f.route, f.route_type, f.is_active,
                    m.name AS module_name, m.id AS module_id,
                    GROUP_CONCAT(DISTINCT m2.name ORDER BY m2.sort_order SEPARATOR ', ') AS all_modules,
                    GROUP_CONCAT(DISTINCT mfm.module_id)                                 AS module_ids
             FROM   features f
             LEFT   JOIN modules          m   ON m.id  = f.module_id
             LEFT   JOIN module_feature_map mfm ON mfm.feature_id = f.id
             LEFT   JOIN modules          m2  ON m2.id = mfm.module_id
             GROUP  BY f.id, f.`key`, f.name, f.route, f.route_type, f.is_active, m.name, m.id
             ORDER  BY m.sort_order ASC, f.name ASC"
        );

        // Normalise module_ids to an integer array for JS filtering
        foreach ($features as $row) {
            $row->module_id_list = $row->module_ids
                ? array_map('intval', explode(',', $row->module_ids))
                : [];
        }

        $modules = $db->fetchAll(
            "SELECT id, name FROM modules WHERE is_active = 1 ORDER BY sort_order ASC"
        );

        $this->setViewVar('features', $features);
        $this->setViewVar('modules', $modules);
    }

    public function formContextAction(TinyPHP_Request $request) {
        $this->setNoRenderer(true);

        //$db  = DB('platform_db');

        $db = DB();
        $id  = $request->getInput('id', 'Int', 0);

        $modules = $db->fetchAll(
            "SELECT id, name FROM modules WHERE is_active = 1 ORDER BY sort_order ASC"
        );

        $feature        = null;
        $extraModuleIds = [];
        if ($id) {
            $feature = $db->fetchOne("SELECT * FROM features WHERE id = ?", [$id]);
            if ($feature) {
                $maps = $db->fetchAll(
                    "SELECT module_id FROM module_feature_map WHERE feature_id = ? AND module_id != ?",
                    [$id, (int) $feature->module_id]
                );
                $extraModuleIds = array_column(array_map('get_object_vars', $maps), 'module_id');
            }
        }

        response(['modules' => $modules, 'feature' => $feature, 'extra_module_ids' => $extraModuleIds])->sendJson();
    }

    public function storeAction(TinyPHP_Request $request) {
        $this->setNoRenderer(true);

        //$db     = DB('platform_db');

        $db = DB();
        $inputs = $request->getInputs();

        $errors = $this->validateInputs($inputs, $db, 0);
        if (!empty($errors)) {
            response([], 'Validation failed', 422)->errors($errors)->sendJson();
        }

        $now         = date('Y-m-d H:i:s');
        $accessLevel = $this->sanitizeAccessLevel($inputs['access_level'] ?? '');
        $moduleId    = !empty($inputs['module_id']) ? (int) $inputs['module_id'] : null;

        $id = $db->insert('features', [
            'module_id'    => $moduleId,
            'key'          => trim($inputs['key']),
            'name'         => trim($inputs['name']),
            'description'  => trim($inputs['description'] ?? ''),
            'route'        => trim($inputs['route'] ?? ''),
            'route_type'   => in_array($inputs['route_type'] ?? '', ['front','api','both']) ? $inputs['route_type'] : 'front',
            'access_level' => $accessLevel,
            'is_active'    => ($inputs['is_active'] ?? '0') === '1' ? 1 : 0,
            'created_at'   => $now,
            'updated_at'   => $now,
        ]);

        if ($accessLevel === 'subscription' && $moduleId) {
            $extraModuleIds = array_filter(array_map('intval', (array) ($inputs['extra_module_ids'] ?? [])));
            $this->syncModuleMap($db, $id, $moduleId, $extraModuleIds, $now);
        }

        response(['id' => $id], 'Feature created', 201)->sendJson();
    }

    public function updateAction(TinyPHP_Request $request) {
        $this->setNoRenderer(true);

        $id = $request->getInput('id', 'Int', 0);
        //$db = DB('platform_db');
        $db = DB();

        $existing = $db->fetchOne("SELECT id FROM features WHERE id = ?", [$id]);
        if (!$existing) {
            response([], 'Feature not found', 404)->sendJson();
        }

        $inputs = $request->getInputs();
        $errors = $this->validateInputs($inputs, $db, $id);
        if (!empty($errors)) {
            response([], 'Validation failed', 422)->errors($errors)->sendJson();
        }

        $now         = date('Y-m-d H:i:s');
        $accessLevel = $this->sanitizeAccessLevel($inputs['access_level'] ?? '');
        $moduleId    = !empty($inputs['module_id']) ? (int) $inputs['module_id'] : null;

        $db->query(
            "UPDATE features SET module_id=?, `key`=?, name=?, description=?, route=?, route_type=?, access_level=?, is_active=?, updated_at=? WHERE id=?",
            [
                $moduleId,
                trim($inputs['key']),
                trim($inputs['name']),
                trim($inputs['description'] ?? ''),
                trim($inputs['route'] ?? ''),
                in_array($inputs['route_type'] ?? '', ['front','api','both']) ? $inputs['route_type'] : 'front',
                $accessLevel,
                ($inputs['is_active'] ?? '0') === '1' ? 1 : 0,
                $now,
                $id,
            ]
        );

        if ($accessLevel === 'subscription' && $moduleId) {
            $extraModuleIds = array_filter(array_map('intval', (array) ($inputs['extra_module_ids'] ?? [])));
            $this->syncModuleMap($db, $id, $moduleId, $extraModuleIds, $now);
        } else {
            // Clear any stale module_feature_map rows when switching away from subscription
            $db->query("DELETE FROM module_feature_map WHERE feature_id = ?", [$id]);
        }

        response(['id' => $id], 'Feature updated', 200)->sendJson();
    }

    public function deleteAction(TinyPHP_Request $request) {
        $this->setNoRenderer(true);

        $id = $request->getInput('id', 'Int', 0);
        //$db = DB('platform_db');
        $db = DB();

        $existing = $db->fetchOne("SELECT id FROM features WHERE id = ?", [$id]);
        if (!$existing) {
            response([], 'Feature not found', 404)->sendJson();
        }

        $db->query("DELETE FROM module_feature_map WHERE feature_id = ?", [$id]);
        $db->query("DELETE FROM features WHERE id = ?", [$id]);

        response([], 'Feature deleted', 200)->sendJson();
    }

    private function syncModuleMap($db, int $featureId, int $primaryModuleId, array $extraModuleIds, string $now): void {
        $allIds = array_values(array_unique(array_merge([$primaryModuleId], $extraModuleIds)));

        // Remove rows no longer in the set
        $placeholders = implode(',', array_fill(0, count($allIds), '?'));
        $db->query(
            "DELETE FROM module_feature_map WHERE feature_id = ? AND module_id NOT IN ({$placeholders})",
            array_merge([$featureId], $allIds)
        );

        // Insert any missing rows
        foreach ($allIds as $moduleId) {
            $exists = $db->fetchOne(
                "SELECT id FROM module_feature_map WHERE feature_id = ? AND module_id = ?",
                [$featureId, $moduleId]
            );
            if (!$exists) {
                $db->insert('module_feature_map', [
                    'module_id'  => $moduleId,
                    'feature_id' => $featureId,
                    'created_at' => $now,
                ]);
            }
        }
    }

    private function sanitizeAccessLevel(string $value): string {
        return in_array($value, ['subscription', 'core', 'super_admin'], true) ? $value : 'subscription';
    }

    private function validateInputs(array $inputs, $db, int $excludeId): array {
        $errors      = [];
        $accessLevel = $this->sanitizeAccessLevel($inputs['access_level'] ?? '');

        if (empty(trim($inputs['name'] ?? ''))) {
            $errors['name'] = 'Name is required';
        }
        if (empty(trim($inputs['key'] ?? ''))) {
            $errors['key'] = 'Key is required';
        }
        // module_id is required only for subscription-level features
        if ($accessLevel === 'subscription' && empty($inputs['module_id'])) {
            $errors['module_id'] = 'Module is required for subscription features';
        }

        return $errors;
    }
}
?>
