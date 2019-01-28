<?php

namespace Treto\PortalBundle\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Treto\PortalBundle\Document\SecureDocument;

class TaskHistoryController extends AbstractDiscussionController {

    public function setAction() {
        $id = $this->param('id'); // get request id if exists
        $data = $this->fromJson(); // source data
        $history = $data['history']; // source document
        if (!$history) {
            return $this->fail('wrong input');
        }
        $dontNotifyParticipants = isset($data['silent']) ? $data['silent'] : false; // if silent set
        $docInBase = $this->getRepoAndFindOneByAnyId('Portal', $history['taskId'], false); // initial doc var
        $main = $this->getMainDocFor($docInBase, true);
        $errors = [];
        $result = ['debug' => []];


        if (!$id) { // ========== CREATE ========== 
            $historyObj = new \Treto\PortalBundle\Document\TaskHistory();
            $errors = $historyObj->setDocument($history, $this->get('treto.validator'), $this->getUser()->getRoles());
            if (!$this->isUnid($historyObj->GetUnid())) {
                $historyObj->SetUnid();
            }
            $historyObj->setAuthorLogin($this->getUserPortalData()->GetLogin());
            if (empty($history['security'])) {
                $historyObj->setDefaultSecurity($this->getUser());
            }
            $result += $this->processNotifications($main ? $main : $docInBase, $docInBase, true, true, $dontNotifyParticipants);
        } else { // ========== EDIT ========== 
            $historyObj = $this->getRepoAndFindOneByAnyId('TaskHistory', $id, false);
            if (!$historyObj) {
                return $this->fail('document not found');
            }
            $historyObj->setUser($this->getUser());
            $fieldsChanged = $historyObj->fromArray($history, ['Author', 'AuthorRus']);
            $result['fieldsChanged'] = count($fieldsChanged);

            if (!$this->getUser()->can('write', $historyObj)) {
                if ((count($fieldsChanged) > 2) || !isset($fieldsChanged['AttachedDoc'])) {
                    return $this->fail('permission denied');
                } // else it's ok, anybody can change AttachedDoc
            }

            $errors = $this->get('treto.validator')->validate($historyObj);
            if (!empty($errors)) {
                return $this->fail($errors);
            }

            $result += $this->processNotifications($main ? $main : $docInBase, $docInBase, true);
        }

        $this->getDM()->persist($historyObj);
        $this->getDM()->flush();

        return $this->success($result);
    }

}
