<?php

namespace Axescloud\ApiBundle\Utils;

/**
 *
 * @author mboullouz
 *        
 */
class RefUtils {

    /**
     * @param $fullName
     * @return string
     * @throws \Exception
     */
    static function pathToRepo($fullName) {
		$pathArr = explode ( "\\", $fullName );
		$entityName = $pathArr [count ( $pathArr ) - 1];
		$bundleName = null;
		foreach ( $pathArr as $path ) {
			if ((strpos ( $path, 'Bundle' ) !== false)) {
				$bundleName = $path;
			}
		}
		if (empty ( $bundleName )) {
			throw new \Exception ( "Bundle Not found" );
		}
		return $pathArr [0] . $bundleName . ":" . $entityName;
	}
}

