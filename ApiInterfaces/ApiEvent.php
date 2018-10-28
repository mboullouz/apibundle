<?php

namespace Axescloud\ApiBundle\ApiInterfaces;

use Axescloud\ApiBundle\Types\EntityID;

interface ApiEvent {
    /**
     * operate on the object passed by reference
     * Only when the entity already exists in the DB BEFORE the operation
     * @param $entity
     * @return mixed $entity
     */
    function preUpdate(&$entity);

    /**
     * Only when the entity already exists in the DB BEFORE the operation
     * @param $id
     * @return mixed $entity
     */
    function postUpdate(EntityID $id);

    /**
     * operate on the object passed by reference
     * Only for new entities (without the Id )
     * @param $entity
     * @return mixed $entity
     */
    function preInsert(&$entity);

    /**
     * Only for new inserted entities
     * NB: this method is called AFTER a EntityManager#Persist(..) so the entity has a NEW Id, this is not
     *      taken in account and only previous presence of Id is considered
     * @param $id
     * @return mixed $entity
     */
    function postInsert($id);
}