<?php
/**
 * Mohamed Boullouz <mohamed.boullouz@gmail.com>
 */

namespace Axescloud\ApiBundle\Tests;

class FakeUser {
    private $id;
    private $name;

    /**
     * FakeUser constructor.
     * @param $id
     * @param $name
     */
    public function __construct($id=1, $name="Vola Johns") {
        $this->id = $id;
        $this->name = $name;
    }

    /**
     * @return mixed
     */
    public function getId() {
        return $this->id;
    }

    /**
     * @param mixed $id
     */
    public function setId($id) {
        $this->id = $id;
    }

    /**
     * @return mixed
     */
    public function getName() {
        return $this->name;
    }

    /**
     * @param mixed $name
     */
    public function setName($name) {
        $this->name = $name;
    }


}