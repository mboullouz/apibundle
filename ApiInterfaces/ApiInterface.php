<?php

namespace Axescloud\ApiBundle\ApiInterfaces;

use Symfony\Component\HttpFoundation\Request;

/**
 *
 * Standariser l'interface de l'API
 * @author mboullouz
 *
 */
interface ApiInterface {
    public function queryAction(Request $request);

    public function whereAction(Request $request);

    public function updateEntriesAction();

    public function deleteAction($entity);

    public function describeAction();
}