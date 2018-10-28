<?php

namespace Axescloud\ApiBundle\Utils;

/**
 * Routine for operators:
 * There is two categories: Logic operators and comparaison operators
 * @author mboullouz
 *
 */
class QueryOperators {
	//logic operators
	const AND_LOGIC_OPERATOR = "AND";
	const OR_LOGIC_OPERATOR  = "OR";

	//comparaison operators
	const  EQUAL_COMPARAISON_OPERATOR="=";
	const  SUPERIOR_OPERATOR=">";
	const  SUPERIOR_OR_EQUAL_OPERATOR=">=";
	const  INFERIOR_OPERATOR="<";
	const  INFERIOR_OR_EQUAL_OPERATOR="<=";
	const  IN ="IN";
	const  IS = "IS";
	const  IS_NULL="IS_NULL";

	static function getLogicList() {
		return array (
				self::AND_LOGIC_OPERATOR,
				self::OR_LOGIC_OPERATOR
		);
	}
	static function isInLogicOperators($operator){
		if (in_array($operator, self::getLogicList())) {
			return true;
		}
		return false;
	}

	static function getComparaisonList() {
		return array (
				self::EQUAL_COMPARAISON_OPERATOR,
				self::SUPERIOR_OPERATOR,
				self::SUPERIOR_OR_EQUAL_OPERATOR,
				self::INFERIOR_OPERATOR,
				self::INFERIOR_OR_EQUAL_OPERATOR,
				self::IN,
				self::IS,
				self::IS_NULL
		);
	}
	static function isInComparaisonOperators($operator){
		if (in_array($operator, self::getComparaisonList())) {
			return true;
		}
		return false;
	}

}