<?php

namespace ZF2EntityAudit\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use SimpleThings\EntityAudit\Utils\ArrayDiff;
use Doctrine\ORM\Mapping\ClassMetadata;

class IndexController extends AbstractActionController {

    /**
     * @return ServiceLocatorInterface
     */
    public function getServiceLocator() {
        return parent::getServiceLocator();
    }

    /**
     * @return \SimpleThings\EntityAudit\AuditReader
     */
    public function getAuditReader() {
        $sm = $this->getServiceLocator();
        return $sm->get("auditReader");
    }

    /**
     * @return \SimpleThings\EntityAudit\AuditManager
     */
    public function getAuditManager() {
        $sm = $this->getServiceLocator();
        return $sm->get("auditManager");
    }

    /**
     * @return EntityManager
     */
    public function getEntityManager() {
        $sm = $this->getServiceLocator();
        return $sm->get("default");
    }

    /**
     * Renders a paginated list of revisions.
     *
     * @param int $page
     * @return  \Zend\View\Model\ViewModel
     */
    public function indexAction() {
        $page = (int) $this->getEvent()->getRouteMatch()->getParam('page');
        $reader = $this->getAuditReader();
        $revisions = $reader->findRevisionHistory(20, 20 * ($page - 1));
        return new ViewModel(
                        array("revisions" => $revisions)
        );
    }

    /**
     * Shows entities changed in the specified revision.
     *
     * @param integer $rev
     * @return \Zend\View\Model\ViewModel
     * 
     */
    public function viewRevisionAction() {
        $rev = (int) $this->getEvent()->getRouteMatch()->getParam('rev');
        $revision = $this->getAuditReader()->findRevision($rev);
        if (!$revision) {
            echo(sprintf('Revision %i not found', $rev));
        }
        $changedEntities = $this->getAuditReader()->findEntitesChangedAtRevision($rev);

        return new ViewModel(array(
                    'revision' => $revision,
                    'changedEntities' => $changedEntities,
                ));
    }

    /**
     * Lists revisions for the supplied entity.
     *
     * @param string $className
     * @param string $id
     * @return \Zend\View\Model\ViewModel
     */
    public function viewEntityAction() {
        $className = $this->getEvent()->getRouteMatch()->getParam('className');
        $id = $this->getEvent()->getRouteMatch()->getParam('id');

        $ids = explode(',', $id);
        $revisions = $this->getAuditReader()->findRevisions($className, $ids);
        return new ViewModel(array(
                    'id' => $id,
                    'className' => $className,
                    'revisions' => $revisions,
                ));
    }

    /**
     * Shows the data for an entity at the specified revision.
     *
     * @param string $className
     * @param string $id Comma separated list of identifiers
     * @param int $rev
     * @return \Zend\View\Model\ViewModel
     */
    public function viewdetailAction() {
        $className = $this->getEvent()->getRouteMatch()->getParam('className');
        $id = $this->getEvent()->getRouteMatch()->getParam('id');
        $rev = $this->getEvent()->getRouteMatch()->getParam('rev');
        $em = $this->getEntityManager();
        $metadata = $em->getClassMetadata($className);

        $ids = explode(',', $id);
        $entity = $this->getAuditReader()->find($className, $ids, $rev);

        $data = $this->getEntityValues($metadata, $entity);
        krsort($data);

        return new ViewModel(array(
                    'id' => $id,
                    'rev' => $rev,
                    'className' => $className,
                    'entity' => $entity,
                    'data' => $data,
                ));
    }

    /**
     * Compares an entity at 2 different revisions.
     *
     * 
     * @param string $className
     * @param string $id Comma separated list of identifiers
     * @param null|int $oldRev if null, pulled from the posted data
     * @param null|int $newRev if null, pulled from the posted data
     * @return Response
     */
    public function compareAction() {
        $className = $this->getEvent()->getRouteMatch()->getParam('className');
        $id = $this->getEvent()->getRouteMatch()->getParam('id');
        $oldRev = $this->getEvent()->getRouteMatch()->getParam('oldRev');
        $newRev = $this->getEvent()->getRouteMatch()->getParam('newRev');
        
        $em = $this->getEntityManager();
        $metadata = $em->getClassMetadata($className);
        $posted_data = $this->params()->fromPost();
        if (null === $oldRev) {
            $oldRev = $posted_data['oldRev'];
        }

        if (null === $newRev) {
            $newRev = $posted_data["newRev"];
        }
        $ids = explode(',', $id);
        $oldEntity = $this->getAuditReader()->find($className, $ids, $oldRev);
        $oldData = $this->getEntityValues($metadata, $oldEntity);

        $newEntity = $this->getAuditReader()->find($className, $ids, $newRev);
        $newData = $this->getEntityValues($metadata, $newEntity);

        $differ = new ArrayDiff();
        $diff = $differ->diff($oldData, $newData);

        return new ViewModel(array(
                    'className' => $className,
                    'id' => $id,
                    'oldRev' => $oldRev,
                    'newRev' => $newRev,
                    'diff' => $diff,
                ));
    }

    protected function getEntityValues(ClassMetadata $metadata, $entity) {
        $fields = $metadata->getFieldNames();

        $return = array();
        foreach ($fields AS $fieldName) {
            $return[$fieldName] = $metadata->getFieldValue($entity, $fieldName);
        }

        return $return;
    }

}
