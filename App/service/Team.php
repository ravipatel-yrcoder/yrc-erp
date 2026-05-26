<?php
class Service_Team extends Service_Base
{
    public function list(int $companyId): array
    {
        $rows = $this->db->fetchAll(
            "SELECT t.id, t.name, t.description, t.status,
                    COUNT(tm.id) AS member_count
             FROM teams t
             LEFT JOIN team_members tm ON tm.team_id = t.id
             WHERE t.company_id = ?
             GROUP BY t.id
             ORDER BY t.name ASC",
            [$companyId]
        );

        return array_map('get_object_vars', $rows);
    }


    public function create(int $companyId, int $userId, array $data): array
    {
        $this->validate($data, $companyId);
        if ($this->hasErrors()) {
            return ['success' => false, 'errors' => $this->getErrors()];
        }

        $team              = new Models_Team();
        $team->company_id  = $companyId;
        $team->name        = trim($data['name']);
        $team->description = trim($data['description'] ?? '') ?: null;
        $team->status      = 'active';
        $team->created_by  = $userId;
        $team->updated_by  = $userId;

        $teamId = $team->create();
        if (!$teamId) {
            throw new Exception('Failed to create team');
        }

        return ['success' => true, 'data' => ['id' => (int) $teamId]];
    }


    public function update(int $companyId, int $userId, int $id, array $data): array
    {
        $existing = $this->db->fetchOne(
            "SELECT id FROM teams WHERE id = ? AND company_id = ? LIMIT 1",
            [$id, $companyId]
        );
        if (!$existing) {
            throw new Service_Exception('Team not found', 404);
        }

        $this->validate($data, $companyId, $id);
        if ($this->hasErrors()) {
            return ['success' => false, 'errors' => $this->getErrors()];
        }

        $team              = new Models_Team($id);
        $team->name        = trim($data['name']);
        $team->description = trim($data['description'] ?? '') ?: null;
        $team->updated_by  = $userId;

        if (!$team->update()) {
            throw new Exception('Failed to update team');
        }

        return ['success' => true, 'data' => []];
    }


    public function delete(int $companyId, int $id): array
    {
        $existing = $this->db->fetchOne(
            "SELECT id FROM teams WHERE id = ? AND company_id = ? LIMIT 1",
            [$id, $companyId]
        );
        if (!$existing) {
            throw new Service_Exception('Team not found', 404);
        }

        $team = new Models_Team($id);
        if (!$team->delete()) {
            throw new Exception('Failed to delete team');
        }

        return ['success' => true, 'data' => []];
    }


    public function getFormContext(int $companyId): array
    {
        $users = $this->db->fetchAll(
            "SELECT u.id, u.name, u.email
             FROM users u
             WHERE u.company_id = ? AND u.status = 'active'
             ORDER BY u.name ASC",
            [$companyId]
        );

        return ['users' => array_map('get_object_vars', $users)];
    }


    public function getMembers(int $companyId, int $teamId): array
    {
        $team = $this->db->fetchOne(
            "SELECT id FROM teams WHERE id = ? AND company_id = ? LIMIT 1",
            [$teamId, $companyId]
        );
        if (!$team) {
            throw new Service_Exception('Team not found', 404);
        }

        $rows = $this->db->fetchAll(
            "SELECT u.id, u.name, u.email, tm.created_at AS joined_at
             FROM team_members tm
             JOIN users u ON u.id = tm.user_id
             WHERE tm.team_id = ?
             ORDER BY u.name ASC",
            [$teamId]
        );

        return array_map('get_object_vars', $rows);
    }


    public function addMember(int $companyId, int $teamId, int $userId, int $createdBy): array
    {
        $team = $this->db->fetchOne(
            "SELECT id FROM teams WHERE id = ? AND company_id = ? LIMIT 1",
            [$teamId, $companyId]
        );
        if (!$team) {
            throw new Service_Exception('Team not found', 404);
        }

        $user = $this->db->fetchOne(
            "SELECT id FROM users WHERE id = ? AND company_id = ? AND status = 'active' LIMIT 1",
            [$userId, $companyId]
        );
        if (!$user) {
            throw new Service_Exception('User not found', 404);
        }

        $existing = $this->db->fetchOne(
            "SELECT id FROM team_members WHERE team_id = ? AND user_id = ? LIMIT 1",
            [$teamId, $userId]
        );
        if ($existing) {
            return ['success' => true, 'data' => []];
        }

        $member             = new Models_TeamMember();
        $member->team_id    = $teamId;
        $member->user_id    = $userId;
        $member->created_by = $createdBy;

        if (!$member->create()) {
            throw new Exception('Failed to add team member');
        }

        return ['success' => true, 'data' => []];
    }


    public function removeMember(int $companyId, int $teamId, int $userId): array
    {
        $team = $this->db->fetchOne("SELECT id FROM teams WHERE id = ? AND company_id = ? LIMIT 1", [$teamId, $companyId]);
        
        if (!$team) {
            throw new Service_Exception('Team not found', 404);
        }

        $this->db->query("DELETE FROM team_members WHERE team_id = ? AND user_id = ?", [$teamId, $userId]);

        return ['success' => true, 'data' => []];
    }


    /**
     * Returns all user IDs sharing at least one team with the given user.
     * Includes the user themselves. Used for "team" scope filtering.
     * Returns empty array if the user is not in any team.
     */
    public function getTeamMemberIds(int $userId, int $companyId): array
    {
        $rows = $this->db->fetchAll(
            "SELECT DISTINCT tm2.user_id
             FROM team_members tm1
             JOIN teams t ON t.id = tm1.team_id AND t.company_id = ? AND t.status = 'active'
             JOIN team_members tm2 ON tm2.team_id = tm1.team_id
             WHERE tm1.user_id = ?",
            [$companyId, $userId]
        );

        if (empty($rows)) {
            return [];
        }

        return array_map(fn($r) => (int) $r->user_id, $rows);
    }


    private function validate(array $data, int $companyId, int $excludeId = 0): void
    {
        $name = trim($data['name'] ?? '');
        if ($name === '') {
            $this->addError(validationErrMsg('required', 'Team name'), 'name');
            return;
        }

        $sql      = "SELECT id FROM teams WHERE company_id = ? AND name = ?";
        $bindings = [$companyId, $name];
        if ($excludeId > 0) {
            $sql      .= " AND id != ?";
            $bindings[] = $excludeId;
        }
        $sql .= " LIMIT 1";

        if ($this->db->fetchOne($sql, $bindings)) {
            $this->addError(validationErrMsg('duplicate', 'Team name'), 'name');
        }
    }
}
?>
