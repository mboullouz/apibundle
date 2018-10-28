<?php
/**
 * Mohamed Boullouz <mohamed.boullouz@gmail.com>
 */

namespace Axescloud\ApiBundle\Utils;

class StringUtils {
    /**
     * Returns true if the string contains $needle, false otherwise. By default
     * the comparison is case-sensitive, but can be made insensitive by setting
     * $caseSensitive to false.
     * @param string $str String to scan
     * @param  string $needle        Substring to look for
     * @param  bool   $caseSensitive Whether or not to enforce case-sensitivity
     * @return bool   Whether or not $str contains $needle
     */
    public static function contains($str, $needle, $caseSensitive = true)
    {
        if ($caseSensitive) {
            return (\mb_strpos($str, $needle, 0) !== false);
        }
        return (\mb_stripos($str, $needle, 0) !== false);
    }
}