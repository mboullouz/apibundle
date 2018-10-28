<?php

namespace Axescloud\ApiBundle\Utils;

use Axescloud\ApiBundle\Execeptions\BadArgumentException;
/**
 *
 * A partir du chemin complet d'une entity Doctrine (ou n'importe quelle class) créer un tableau de
 * ses propriété et de ses commentaires
 *
 * @author mboullouz
 *
 */
class EntityDescriptor {

    static $classToDescribe = "";

    /**
     *
     * @param string $entityClassFullName
     *        the full name (+ path to the entity class)
     * @example 'MyBundle\Entity\User'
     * @return array: ['property'=>'comment'] kind
     * @throws \Exception
     */
    public static function  describe($entityClassFullName) {
        if (empty ($entityClassFullName)) {
            throw new BadArgumentException("Impossible de décrire une classe/entity dont le nom/chemin est vide");
        }
        self::$classToDescribe = $entityClassFullName;
        $descriptorArray = []; // gather descriptions in key/value array.
        $objectReflectionClass = new \ReflectionClass ($entityClassFullName);
        $defaultProps = $objectReflectionClass->getDefaultProperties();

        foreach ($defaultProps as $key => $prop) {
            $proportyReflectionClass = new \ReflectionProperty ($entityClassFullName, $key);
            $comment = $proportyReflectionClass->getDocComment();
            $descriptorArray [$key] = self::getCleanComment($comment);
        }

        return $descriptorArray;
    }

    /**
     * Comments my contain unusful strings and symbols
     *
     * @param string $comment
     * @return string
     */
    public static function getCleanComment($comment) {
        self::analyseClassForIncoherence($comment);
        $comment = str_replace([
            "\n",
            "\r\n",
            "\r"
        ], '', $comment); // new lines
        $comment = str_replace([
            "*",
            '@',
            '\\',
            'ORM',
            'GeneratedValue',
            '"',
            "/"
        ], '', $comment);
        $comment = preg_replace('/\s+/', ' ', $comment);
        return $comment;
    }

    private static function analyseClassForIncoherence($comment) {
        try {
            if ((strpos($comment, 'ManyToOne') !== false || strpos($comment, 'OneToMany') !== false || strpos($comment, 'ManyToMany') !== false) && strpos($comment, 'MaxDepth') == false) {
               // $this->logger->error("*********** La classe: " . $this->classToDescribe . " contient ManyToMany/OneToMany/ManayToMany" . " Mais aucun MaxDepth n'est spécifiée *************");
            }
        } catch (\Exception $e) {
            // Do nothing the logging staff is not important!
        }
    }
}
