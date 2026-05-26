<?php
class Api_TeamsController extends TinyPHP_Controller {

    public function init() {
        $this->setNoRenderer(true);
    }

    private function serviceTeam(): Service_Team {
        return new Service_Team(tenantContext());
    }


    // GET /api/company/teams — list
    // POST /api/company/teams — create
    public function indexAction(TinyPHP_Request $request)
    {
        $context = tenantContext();

        if ($request->isMethod('get')) {
            $data = $this->serviceTeam()->list($context->companyId);
            return response($data)->sendJson();
        }

        if ($request->isMethod('post')) {
            $result = $this->serviceTeam()->create($context->companyId, $context->userId, $request->getInputs());

            if (!$result['success']) {
                return response([], 'Failed to create team', 422)->errors($result['errors'])->sendJson();
            }

            return response($result['data'], 'Team created successfully.', 201)->sendJson();
        }
    }


    // GET /api/company/teams/form-context
    public function formContextAction(TinyPHP_Request $request)
    {
        $context = tenantContext();
        $data = $this->serviceTeam()->getFormContext($context->companyId);
        return response($data)->sendJson();
    }


    // POST /api/company/teams/:id — update
    // DELETE /api/company/teams/:id — delete
    public function entityAction(TinyPHP_Request $request)
    {
        $context = tenantContext();
        $id = $request->getInput('id', 'int', 0);

        if ($request->isMethod('post')) {
            $result = $this->serviceTeam()->update($context->companyId, $context->userId, $id, $request->getInputs());

            if (!$result['success']) {
                return response([], 'Failed to update team', 422)->errors($result['errors'])->sendJson();
            }

            return response([], 'Team updated successfully.')->sendJson();
        }

        if ($request->isMethod('delete')) {
            $result = $this->serviceTeam()->delete($context->companyId, $id);
            return response([], 'Team deleted successfully.')->sendJson();
        }
    }


    // GET /api/company/teams/:id/members — list members
    // POST /api/company/teams/:id/members — add member
    public function membersAction(TinyPHP_Request $request)
    {
        $context = tenantContext();
        $teamId = $request->getInput('id', 'int', 0);

        if ($request->isMethod('get')) {
            $data = $this->serviceTeam()->getMembers($context->companyId, $teamId);
            return response($data)->sendJson();
        }

        if ($request->isMethod('post')) {
            $userId = $request->getInput('user_id', 'int', 0);
            $result = $this->serviceTeam()->addMember($context->companyId, $teamId, $userId, $context->userId);

            if (!$result['success']) {
                return response([], 'Failed to add member', 422)->errors($result['errors'])->sendJson();
            }

            return response([], 'Member added successfully.')->sendJson();
        }
    }


    // DELETE /api/company/teams/:id/members/:userId — remove member
    public function removeMemberAction(TinyPHP_Request $request)
    {
        $context = tenantContext();
        $params = $request->getParams();
        $teamId = (int) ($params['id'] ?? 0);
        $userId = (int) ($params['userId'] ?? 0);

        $this->serviceTeam()->removeMember($context->companyId, $teamId, $userId);

        return response([], 'Member removed successfully.')->sendJson();
    }
}
?>
