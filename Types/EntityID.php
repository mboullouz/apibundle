<?php

namespace Axescloud\ApiBundle\Types;
use Axescloud\ApiBundle\Execeptions\BadArgumentException;

/**
 *
 * @author mboullouz
 *        
 */
class EntityID {
	private $eid;
	public function __construct($val) {
		$this->validate ( $val );
		$this->eid = $val;
	}
	public function getValue() {
		return $this->eid;
	}
	public function getId() {
		return $this->getValue ();
	}
	static function of($val) {
		return new self ( $val );
	}
	static function from($obj) {
		if (! method_exists ( $obj, 'getId' ))
			throw new BadArgumentException ();
		return new self ( $obj->getId () );
	}
	public function __toString() {
		return "" . $this->eid;
	}
	private function validate($val) {
		if ((! is_string ( $val ) && ! is_numeric ( $val )) or is_null ( $val )) {
			throw new BadArgumentException ();
		}
		return true;
	}
}

