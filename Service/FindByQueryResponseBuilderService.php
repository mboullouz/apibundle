<?php

namespace Axescloud\ApiBundle\Service;

use Axescloud\ApiBundle\Execeptions\QueryParserException;
use Axescloud\ApiBundle\Utils\JsonSerializer;
use Doctrine\ORM\EntityManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Construire les les query et preparer les reponse Json
 *
 * @author mboullouz
 *
 */
class FindByQueryResponseBuilderService {

    /**
     *
     * @var \Symfony\Component\DependencyInjection\Container
     */
    private $container;

    /**
     *
     * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
     */
    public function __construct(ContainerInterface $container) {
        $this->container = $container;
    }

    /**
     * Get reponse by repository
     *
     * @param string $repositoryName
     * @return string: encoded json string
     */
    public function getJsonResponseForRepository($repositoryName = "", $filters = [], $transformers = []): string {
        /** @var EntityManager $em */
        $em = $this->container->get("doctrine");
        $queryParser = $this->container->get("Axescloud.FindByQueryParserService");

        try {
            $entities = $em->getRepository($repositoryName)->findBy($queryParser->getFindBy(), $queryParser->getOrderBy(), $queryParser->getMaxResults(), $queryParser->getOffset());
            if (!empty ($transformers) && !empty ($entities)) {
                foreach ($entities as $et) {
                    foreach ($transformers as $modifier) {
                        $modifier ($et);//by ref
                    }
                }
            }
            if (!empty ($filters) && !empty ($entities)) {
                foreach ($filters as $modifier) {
                    $entities = array_filter($entities, $modifier);
                }
            }
        } catch (\Exception $e) {
            throw new  QueryParserException ("Please check the syntax, entity fields or|and parameters. " . $e->getMessage());
        }
        return JsonSerializer::toJson($entities);
    }
}
