<?php

namespace Axescloud\ApiBundle\Service;

use Axescloud\ApiBundle\ApiInterfaces\ApiEvent;
use Axescloud\ApiBundle\Execeptions\BadArgumentException;
use Axescloud\ApiBundle\Execeptions\BadRequestException;
use Axescloud\ApiBundle\Execeptions\QueryParserException;
use Axescloud\ApiBundle\Types\EntityID;
use Axescloud\ApiBundle\Utils\ModelStateResponse;
use Axescloud\ApiBundle\Utils\StringUtils;
use Doctrine\ORM\EntityManager;
use JMS\Serializer\SerializerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Parser les request Update/Insert en fonction de la présence de l'Id
 * et en se pasant sur les propiétés dans l'objet
 *
 * @author mboullouz
 *
 */
class InsertUpdateEntriesService {
    /**
     * @var $container ContainerInterface
     */
    protected $container;
    /**
     * @var $em EntityManager
     */
    protected $em;
    protected $request;
    protected $serializer;
    protected $logger;
    private $propsArray = [];
    private $entryId = null;

    /**
     *
     * @param $container
     */
    public function __construct(ContainerInterface $container) {
        $this->container = $container;
        $this->request = $container->get('request');
        $this->em = $container->get("doctrine")->getManager();
        $this->serializer = SerializerBuilder::create()->build();
        $this->logger = $this->container->get('logger');
        // parse
        $this->parse();
    }

    /**
     * @throws QueryParserException
     */
    private function parse() {
        $parsedObject = $this->getData();
        $this->entryId = $this->parseEntryId($parsedObject);
        $this->propsArray = $this->parsePropsArray($parsedObject);
    }

    /**
     * Valider l'objet avant la persistence, si une constraint est violee
     * BadRequest( code status 400) sera envoye au client
     * @param mixed $entity
     * @param mixed $contraints
     * @throws BadRequestException
     */
    private function validate($entity, $contraints) {

        $modelState = new ModelStateResponse(1);
        foreach ($contraints as $contraint) {
            $errors = $this->container->get('validator')->validate($entity, $contraint);
            if (count($errors) > 0) {
                $modelState->setEtat(0);
                foreach ($errors as $err) {
                    $modelState->addMessage($err->getMessage());
                }
            }
        }
        $this->logger->error("validation contraints: " . $modelState);
        if ($modelState->etat == 0) {
            throw new BadRequestException($modelState);
        }
    }

    /**
     * @param       $repositoryName
     * @param array $contraints
     * @param array $events
     * @return ModelStateResponse
     */
    public function executeForRepository($repositoryName, $contraints = [], $events = []) {

        if (!StringUtils::contains($repositoryName, "\\")) {
            throw new BadArgumentException("Entiy class should be of format \\bundle\\className...");
        }
        $entity = $this->getEntity($repositoryName);
        $entity = $this->populateEntity($entity, $repositoryName);
        /** Validations */
        $this->validate($entity, $contraints);
        /** @var  $events  ApiEvent[] */
        foreach ($events as $event) {
            if (empty($this->entryId)) {
                $event->preInsert($entity);
            } else {
                $event->postUpdate(EntityID::of($this->entryId));
            }
        }
        $this->em->persist($entity);
        try {
            $this->em->flush();
        } catch (\Exception $e) {
            $this->logger->error("err: " . $e->getMessage());
        }

        /** @var  $event ApiEvent */
        foreach ($events as $event) {
            if (empty($this->entryId)) {
                $event->postInsert($entity);
            } else {
                $event->postUpdate(EntityID::from($entity));
            }
            $this->logger->info("Post update Operation on: " . "");
        }
        return ModelStateResponse::success(" Opération effectuée avec succès ", [$entity->getId()]);
    }

    private function getParamClassMethodByProperty($repositoryName, $propName, $propValue) {
        $reflectedClass = new \ReflectionClass ($repositoryName);
        if (is_array($propValue)) {
            $propSetterMethodName = "add" . substr(ucwords($propName), 0, -1);
        } else {
            $propSetterMethodName = "set" . ucwords($propName);
        }

        if (!$reflectedClass->hasMethod($propSetterMethodName)) {
            throw new QueryParserException ("Impossible to find the prop: " . $propName
                . " in the current object, and cant find method: " . $propSetterMethodName);
        }

        $reflectionMethod = new \ReflectionMethod($repositoryName, $propSetterMethodName);
        return $reflectionMethod;
    }

    private function getParamClassMethodForGetCollection($repositoryName, $propName, $propValue) {
        if (is_array($propValue)) {
            $propSetterMethodName = "get" . substr(ucwords($propName), 0, -1) . 's';
        } else {
            return null;
        }
        $reflectionMethod = new \ReflectionMethod($repositoryName, $propSetterMethodName);
        return $reflectionMethod;
    }

    private function getParamClassMethodForRemoveCollection($repositoryName, $propName, $propValue) {
        if (is_array($propValue)) {
            $propSetterMethodName = "remove" . substr(ucwords($propName), 0, -1);
        } else {
            return null;
        }
        $reflectionMethod = new \ReflectionMethod($repositoryName, $propSetterMethodName);
        return $reflectionMethod;
    }

    /**
     * Populate the entity passed by reference
     *
     * @param mixed  $entity doctrine Entity classe $entity
     * @param string $repositoryName to check proprieties existence and invok setters by Reflection
     *
     * @return object $entity populated
     */
    public function populateEntity(&$entity, $repositoryName) {
        foreach ($this->propsArray as $prop) {
            try { /* be sure that we can get prop from the array */
                $propName = $prop->propName;
                $propValue = $prop->propValue;
            } catch (\Exception $e) {
                throw new QueryParserException ("Parse error: An object in the Props array is not valid ");
            }

            $reflectionMethod = $this->getParamClassMethodByProperty($repositoryName, $propName, $propValue);
            $params = $reflectionMethod->getParameters();
            $paramClass = $params['0']->getClass();
            if (is_array($propValue)) {
                $getMethodForCollection = $this->getParamClassMethodForGetCollection($repositoryName, $propName, $propValue);
                $existingElements = $getMethodForCollection->invoke($entity);
                /** vider les élements existants */
                foreach ($existingElements as $exElm) {
                    $removeMethode = $this->getParamClassMethodForRemoveCollection($repositoryName, $propName, $propValue);
                    if (!empty($removeMethode)) {
                        $removeMethode->invoke($entity, $exElm);
                    }
                }
                foreach ($propValue as $id) {
                    $relatedEntity = $this->getIndividualRelatedEntityByParamProperties($params, $id);
                    $reflectionMethod->invoke($entity, $relatedEntity);
                }

            } else if (!empty($paramClass)) { /* cas complex object */
                $paramClassName = $paramClass->getName();
                if ((strpos($paramClassName, 'Date') !== false)) {
                    $propValue = \DateTime::createFromFormat("Y-m-d H:i:s", $propValue);
                } else {
                    $propValue = $this->getIndividualRelatedEntityByParamProperties($params, $propValue);
                }
                $reflectionMethod->invoke($entity, $propValue);
            } else { /* final case: simple scalar  */
                $reflectionMethod->invoke($entity, $propValue);
            }
        }
        return $entity;
    }

    /**
     *  get related entity by parsing  bundle path and entity name
     * @param            $paramProperties
     * @param string|int $id
     * @return null|object
     * @throws QueryParserException
     */
    public function getIndividualRelatedEntityByParamProperties($paramProperties, $id) {

        $fullName = $paramProperties['0']->getClass()->getName();
        $pathArr = explode("\\", $fullName);
        $entityName = $pathArr[count($pathArr) - 1];
        $bundleName = null;
        foreach ($pathArr as $path) {
            if ((strpos($path, 'Bundle') !== false)) {
                $bundleName = $path;
            }
        }
        if (empty($bundleName)) {
            throw new QueryParserException ("A complex prop is defined but impossible to find");
        }
        try {
            $entity = $this->em->getRepository($pathArr[0] . $bundleName . ":" . $entityName)->find($id);
            if (empty($entity)) {
                throw new QueryParserException ("The related complex prop is not found for the id: $id and entity name: $entityName");
            }
            return $entity;
        } catch (\Exception $e) {
            throw new QueryParserException ("The related complex prop is not in the current repository " . $e->getMessage());
        }
    }

    private function getEntity($repositoryName) {
        $repository = $this->em->getRepository($repositoryName);
        $entity = null;
        if (!empty ($this->entryId)) {
            try {
                $entity = $repository->find($this->entryId);
            } catch (\Exception $e) {
                throw new QueryParserException ($e->getMessage(), $e->getPrevious(), $e->getCode());
            }
            if (empty ($entity)) { // still empty
                throw new QueryParserException ("Can't find an entity with the id: " . $this->entryId . " in the current repository ( $repositoryName )");
            } else {
                return $entity;
            }
        }
        return $this->getInstanceByRepositoryName($repositoryName);
    }

    public function getInstanceByRepositoryName($repositoryName) {
        $reflectedClass = new \ReflectionClass ($repositoryName);
        // check if can be instantiable
        if (!$reflectedClass->IsInstantiable()) {
            throw new QueryParserException ("Internal error: Can't instanciate the required object");
        }
        try {
            $instance = $reflectedClass->newInstance();
        } catch (\Exception $e) {
            throw new QueryParserException ("Internal error: Constructeur need arguments" . $e->getMessage());
        }
        return $instance;
    }

    /**
     * Try to get the property Comparators from the deserialized object
     * if not present or null, fall back to void array
     *
     * @param array $vm deserialized object
     * @return array
     */
    public function parsePropsArray($vm) {
        try {
            $comparators = $vm->props;
            if (empty ($comparators)) {
                return $this->propsArray;
            }
            return $comparators;
        } catch (\Exception $e) {
            return $this->propsArray;
        }
    }

    public function parseEntryId($parsedObject) {
        try {
            $entryId = $parsedObject->id;
            if (empty ($entryId) || $entryId < 0) {
                return $this->entryId;
            }
            return $entryId;
        } catch (\Exception $e) {
            return $this->entryId;
        }
    }

    /**
     * Get data from the incoming request
     * @throws QueryParserException
     */
    private function getData() {
        $jsonData = $this->request->getContent();
        $phpData = json_decode($jsonData);
        try {
            $uri = $this->request->getUri();
            $this->logger->info('********* API_UPDATE_op at The EndPoint:' . $uri . ' , the update_data: ' . $jsonData . "  *********** ");
        } catch (\Exception $e) {

        }

        if (!empty ($phpData)) {
            return $phpData;
        } else {
            throw new QueryParserException ("Unable to deserialize the object, please check the format and syntax, deserialized json object is empty! ");
        }
    }


}
