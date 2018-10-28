<?php

namespace Axescloud\ApiBundle\Service;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Parser les Queries est extraire des objects/arrays/scalars
 * pour les utiliser avec l'orm doctrine et avoir des données filtrées
 * Pour les parser les expressions where see @WhereQueryParserService
 *
 * @author mboullouz
 *
 */
class FindByQueryParserService {
    protected $container;
    protected $em;
    protected $request;
    protected $logger;

    private $orderBy = [];
    private $findBy = [];
    private $offset = 0;
    private $maxResults = 50;


    public function __construct(ContainerInterface $container) {
        $this->container = $container;
        $this->request = $container->get('request');
        $this->em = $container->get("doctrine")->getManager();
        $this->logger = $this->container->get('logger');

        // parse
        $this->parse();
    }

    public function getOrderBy() {
        return $this->orderBy;
    }

    public function getFindBy() {
        return $this->findBy;
    }

    public function getOffset() {
        return $this->offset;
    }

    public function getMaxResults() {
        return $this->maxResults;
    }

    private function parse() {
        $vm = $this->getData();
        /**
         * If object is null: halt, default result will be used
         */
        if (empty($vm)) {
            return;
        }
        $this->orderBy = $this->parseOrderBy($vm);
        $this->findBy = $this->parseFindBy($vm);
        $this->offset = $this->parseOffset($vm);
        $this->maxResults = $this->parseMaxResults($vm);
    }

    private function parseOrderBy($vm) {
        try {
            return $this->parseArrayKeyValueObject($vm->orderBy);
        } catch (\Exception $e) {
            return [];
        }

    }

    private function parseFindBy($vm) {
        try {
            //if (isset($vm->findBy))
            return $this->parseArrayKeyValueObject($vm->findBy);
        } catch (\Exception $e) {
            return [];
        }
    }

    public function parseMaxResults($vm) {
        try {
            $max = $vm->maxResults;
            if (empty($max) || $max < 0 || $max > 1000) {
                return $this->maxResults;
            }
            return $max;
        } catch (\Exception $e) {
            return $this->maxResults;
        }
    }

    public function parseOffset($vm) {
        try {
            $offset = $vm->offset;
            if (empty($offset) || $offset < 0) {
                return $this->offset;
            }
            return $offset;
        } catch (\Exception $e) {
            return $this->offset;
        }
    }

    private function parseArrayKeyValueObject($arrayKV) {
        $arrayResult = [];
        if (empty($arrayKV) or count($arrayKV) <= 0) {
            return $arrayResult;
        }
        foreach ($arrayKV as $objectKeyValue) {
            //$objectKeyValue instanceof KeyValueViewModel;
            $arrayResult [$objectKeyValue->key] = $objectKeyValue->value;
        }
        return $arrayResult;
    }

    private function getData() {
        $jsonData = $this->request->getContent();
        $this->logger->info('********* QueryService The EndPoint is hit, the query: ' . $jsonData . "  *********** ");
        $phpData = json_decode($jsonData);
        if (!empty($phpData)) {
            return $phpData;
        }
        return null;
    }
}
