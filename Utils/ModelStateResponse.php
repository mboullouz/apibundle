<?php

namespace Axescloud\ApiBundle\Utils;

use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\ConstraintViolationList;

/**
 * @author mboullouz
 *
 */
class ModelStateResponse {

    public $etat = 0;
    public $message = "";
    public $warnings = [];
    public $newEntities = [];

    public function __construct($etat = 0, $message = " ", array $newEntities = []) {
        $this->etat = $etat;
        $this->message = $message;
        $this->newEntities = $newEntities;
        $this->code = $etat;
    }

    public static function buildFromErrors(ConstraintViolationList $errors = null): ModelStateResponse {
        $modelState = new ModelStateResponse (0);
        if (is_null($errors)) { //prevent iterating over empty list
            return $modelState;
        }
        foreach ($errors as $err) {
            /** @var ConstraintViolationInterface $err */
            $modelState->addMessage($err->getMessage());
        }
        return $modelState;
    }

    public static function success(string $msg, array $nEntities = []): ModelStateResponse {
        return new self(1, $msg, $nEntities);
    }

    public static function fail(string $msg): ModelStateResponse {
        return new self(0, $msg, []);
    }

    public function addMessage($message) {
        $this->message .= " " . $message;
        return $this;
    }

    public function setEtat($etat): ModelStateResponse {
        if ($etat == 0) {
            $this->etat = 0;
        } else {
            $this->etat = 1;
        }
        $this->code = $this->etat;
        return $this;
    }

    public function addWarning($msg): ModelStateResponse {
        $this->warnings [] = $msg;
        return $this;
    }

    public function addId($id): ModelStateResponse {
        $this->newEntities [] = $id;
        return $this;
    }

    public function clearWarnings(): ModelStateResponse {
        $this->warnings = [];
        return $this;
    }

    public function clearMessages(): ModelStateResponse {
        $this->message = "";
        return $this;
    }

    public function clearAll(): ModelStateResponse {
        $this->etat = 0;
        $this->warnings [] = [];
        $this->newEntities [] = [];
        $this->message = "";
        return $this;
    }

    public function __toString() {
        return json_encode($this);
    }

    /**
     * @return string
     */
    public function toJson($deep = 1): string {
        return json_encode($this);
    }
}