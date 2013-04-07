<?php

namespace ZF2EntityAuditTest;

if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    $loader = include __DIR__ . '/../vendor/autoload.php';
} elseif (file_exists(__DIR__ . '/../../../autoload.php')) {
    $loader = include __DIR__ . '/../../../autoload.php';
} else {
    throw new RuntimeException('vendor/autoload.php could not be found. Did you run `php composer.phar install`?');
}
/* var $loader \Composer\Autoload\ClassLoader */
$loader->add('ZF2EntityAuditTest\\', __DIR__);

//error_reporting(E_ALL | E_STRICT);
chdir(__DIR__);

use Zend\ServiceManager\ServiceManager;
use Zend\Mvc\MvcEvent;
use Zend\Mvc\Service\ServiceManagerConfig;

class BootStrap
{

    protected $serviceManager = null  ;

    public function __Construct()
    {
        $configuration = include __DIR__ . '/TestConfiguration.php';
        if (isset($configuration['output_buffering']) && $configuration['output_buffering']) {
            ob_start(); // required to test sessions
        }
        $this->serviceManager = new ServiceManager(new ServiceManagerConfig(
            isset($configuration['service_manager']) ? $configuration['service_manager'] : array()
        ));
        $this->serviceManager->setService('ApplicationConfig', $configuration);
        $this->serviceManager->setFactory('ServiceListener', 'Zend\Mvc\Service\ServiceListenerFactory');

        /** @var $moduleManager \Zend\ModuleManager\ModuleManager */
        $moduleManager = $this->serviceManager->get('ModuleManager');
        $moduleManager->loadModules();
        $this->serviceManager->setAllowOverride(true);

        $application = $this->serviceManager->get('Application');
        $event  = new MvcEvent();
        $event->setTarget($application);
        $event->setApplication($application)
            ->setRequest($application->getRequest())
            ->setResponse($application->getResponse())
            ->setRouter($this->serviceManager->get('Router'));

        /// lets create user
        $em = $this->serviceManager->get("doctrine.entitymanager.orm_default");
        $conn = $em->getConnection();
        if(file_exists(__DIR__ ."/../vendor/zf-commons/zfc-user/data/schema.sqlite.sql")){
            $sql = file_get_contents(__DIR__ ."/../vendor/zf-commons/zfc-user/data/schema.sqlite.sql");
        }elseif(file_exists(__DIR__ ."/../../../../vendor/zf-commons/zfc-user/data/schema.sqlite.sql")){
            $sql = file_get_contents(__DIR__ ."/../../../../vendor/zf-commons/zfc-user/data/schema.sqlite.sql");
        }else{
            throw new \Exception("please check the zfc user sql file" , 500);
        }
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetchAll();

    }

    public function getServiceManager()
    {
        return $this->serviceManager;
    }
}
