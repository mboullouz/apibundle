<?php

namespace Axescloud\ApiBundle\Service;

use Axescloud\ApiBundle\Execeptions\QueryParserException;
use Axescloud\ApiBundle\Utils\JsonSerializer;
use Axescloud\ApiBundle\Utils\QueryOperators;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use JMS\Serializer\SerializerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Parser les expressions where @WhereQueryExecutorService et extraction
 * des relations entre les opérateurs
 *
 * @author mboullouz
 *
 */
class WhereQueryExecutorService {
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var EntityManager
     */
    protected $em;
    protected $request;
    protected $serializer;
    protected $logger;
    private $comparatorsArray = [];
    private $maxResults = 50;
    private $offset = 0;
    private $operatorType = "AND";
    private $orderBy = [];

    /**
     * WhereQueryExecutorService constructor.
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container) {
        $this->container = $container;
        $this->request = $container->get('request');
        $this->em = $container->get("doctrine")->getManager();
        $this->serializer =  SerializerBuilder::create()->build();
        $this->logger = $this->container->get('logger');
        $this->operatorType = QueryOperators::AND_LOGIC_OPERATOR;
        // parse
        $this->parse();
    }

    private function parse() {
        $queryObject = $this->getData();
        $this->maxResults = $this->parseMaxResults($queryObject);
        $this->offset = $this->parseOffset($queryObject);
        $this->operatorType = $this->parseOperatorType($queryObject);
        $this->comparatorsArray = $this->parseComparatorsArray($queryObject);

        $this->orderBy = $this->parseOrderBy($queryObject);
    }

    /**
     *
     * @return array of comparators objects
     */
    public function executeWhereQueryForRepository($repositoryName, $filters=[]) {
        $repository = $this->em->getRepository($repositoryName);
        /**
         * @var $query Query|QueryBuilder
         */
        $query = $repository->createQueryBuilder('A');
        $query = $query->where(' 1=1 ');
        $query = $this->parseConstructStringWhereQuery($query);
        $query = $query->setMaxResults($this->maxResults);
        $query = $query->setFirstResult($this->offset);
        $query = $this->parseConstructStringOrderByQuery($query);
        $query = $query->getQuery();
        $sql = $query->getSQL();
        $this->logger->info($sql);
        try {
            $results = $query->getResult();
            if (! empty ( $filters ) && ! empty ( $results )) {
            	foreach ( $filters as $modifier ) {
            		$results = array_filter ( $results, $modifier );
            	}
            }
        } catch (\Exception $e) {
            throw new QueryParserException ($e->getMessage());
        }
        return  JsonSerializer::toJson($results);
    }

    /**
     * @param $queryBuilder QueryBuilder
     * @return mixed
     */
    private function parseConstructStringOrderByQuery(&$queryBuilder) {
        if (empty ($this->orderBy)) {
            $this->logger->info('********* WhereQueryExecutorService  "orderBy" array is null *********** ');
        }
        //Je croie que pour le moment il ne peut y avoir qu'un seul orderby (un tableau a une case --')
        foreach ($this->orderBy as $key => $value) {
            try {
                $queryBuilder->orderBy("A." . $key, $value);
            } catch (\Exception $e) {
                throw new QueryParserException ("Can't parse order by in  the object");
            }
        }
        return $queryBuilder;
    }

    /**
     * @param $queryBuilder QueryBuilder
     * @return mixed
     */
    private function parseConstructStringWhereQuery(&$queryBuilder) {
        /*if (empty ($this->comparatorsArray)) {
            $this->logger->error('********* WhereQueryExecutorService  operator  of type array is null *********** ');
        }*/
        foreach ($this->comparatorsArray as $comparatorObject) {
            $comparatorObject = $this->parseComparatorObject($comparatorObject);
            if (empty ($comparatorObject)) {
                return $queryBuilder;
            }
            try {

                $valueName = $this->uniqueStringName();
                if ($comparatorObject ['operator'] == QueryOperators::IN) { // Case IN
                    $queryString = ' :' . $valueName . ' MEMBER OF A.' . $comparatorObject ['attribute'];
                    if ($this->operatorType == QueryOperators::AND_LOGIC_OPERATOR) {
                        $queryBuilder->andWhere($queryString);
                    } else {
                        $queryBuilder->orWhere($queryString);
                    }
                    $queryBuilder->setParameter($valueName, [$valueName => $comparatorObject ['value']]);

                } else if (
                    ($comparatorObject ['operator'] == "IS" && $comparatorObject ['value'] == "NULL")
                    || ($comparatorObject ['operator'] == "IS_NULL")) {

                    $queryString = 'A.' . $comparatorObject ['attribute'] . ' IS null ';
                    if ($this->operatorType == QueryOperators::AND_LOGIC_OPERATOR) {
                        $queryBuilder->andWhere($queryString);
                    } else {
                        $queryBuilder->orWhere($queryString);
                    }

                } else {
                    $queryString = 'A.' . $comparatorObject ['attribute'] . ' ' . $comparatorObject ['operator'] . ' :' . $valueName;
                    if ($this->operatorType == QueryOperators::AND_LOGIC_OPERATOR) {
                        $queryBuilder->andWhere($queryString);
                    } else {
                        $queryBuilder->orWhere($queryString);
                    }
                    $queryBuilder->setParameter($valueName, $comparatorObject ['value']);
                }
            } catch (\Exception $e) {
                throw new QueryParserException ("Can't parse Operators in  the object");
            }
        }
        return $queryBuilder;
    }

    /**
     * Generate a random string with length
     *
     * @param int $length
     * @return string
     */
    public function uniqueStringName($length = 10) {
        return substr(str_shuffle(str_repeat($x = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', ceil($length / strlen($x)))), 1, $length);
    }

    private function parseComparatorObject($obj) {
        if (empty ($obj)) {
            return null;
        }

        try {
            $attribut = $obj->attribute;
            $value = $obj->value;
            $operator = $obj->operator;
            if (empty ($operator) || !QueryOperators::isInComparaisonOperators($operator)) {
                $operator = QueryOperators::EQUAL_COMPARAISON_OPERATOR;
            }
            return [
                "attribute" => $attribut,
                "value"     => $value,
                "operator"  => $operator
            ];
        } catch (\Exception $e) {
            throw new QueryParserException ("Can't parse Operator object, please check Operators attributes");
        }
    }

    /**
     * Try to get the property Comparators from the deserialized object
     * if not present or null, fall back to void array
     *
     * @param $vm mixed  deserialized object
     * @return array
     */
    public function parseComparatorsArray($vm) {
        try {
            $comparators = $vm->comparators;
            if (empty ($comparators)) {
                return $this->comparatorsArray;
            }
            return $comparators;
        } catch (\Exception $e) {
            return $this->comparatorsArray;
        }
    }

    public function parseMaxResults($vm) {
        try {
            $max = $vm->maxResults;
            if (empty ($max) || $max < 0 || $max > 1000) {
                return $this->maxResults;
            }
            return $max;
        } catch (\Exception $e) {
            return $this->maxResults;
        }
    }

    public function parseOffset($vm) {
        try {
            $offsetVm = $vm->offset;
            if (empty ($offsetVm) || $offsetVm < 0) {
                return $this->offset;
            }
            return $offsetVm;
        } catch (\Exception $e) {
            return $this->offset;
        }
    }

    private function parseOrderBy($vm) {
        try {
            return $this->parseArrayKeyValueObject($vm->orderBy);
        } catch (\Exception $e) {
            return [];
        }

    }

    private function parseArrayKeyValueObject($arrayKV) {
        $arrayResult = [];
        foreach ($arrayKV as $objectKeyValue) {
            //$objectKeyValue instanceof KeyValueViewModel;
            $arrayResult [$objectKeyValue->key] = $objectKeyValue->value;
        }
        return $arrayResult;
    }

    /**
     * operatorType may not be present!
     *
     * @param mixed $vm
     * @return string
     */
    public function parseOperatorType($vm) {
        try {
            $operatorType = $vm->operatorType;
            if (empty ($operatorType) || !QueryOperators::isInLogicOperators($operatorType)) {
                return $this->operatorType;
            }
            return $operatorType;
        } catch (\Exception $e) {
            return $this->operatorType;
        }
    }

    private function getData() {
        $jsonData = $this->request->getContent();
        $uri = $this->request->getUri();
        $this->logger->info('********* WhereQueryExecutorService The EndPoint:' . $uri . ' is hit, the where-query: ' . $jsonData . "  *********** ");
        $phpData = json_decode($jsonData);
        if (!empty ($phpData)) {
            return $phpData;
        } else {
            throw new QueryParserException ("Unable to deserialize the object, please check the format and syntax, deserialized json object is empty!");
        }
    }
}