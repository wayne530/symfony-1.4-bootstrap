<?php

/**
 * Functions useful for building templates
 */

/**
 * Quotes a string for html output
 *
 * @param $string string|array  input string(s)
 *
 * @return string|array  entity-encoded string appropriate for html
 */
function qh($string) {
    if (! is_array($string)) {
        return htmlentities($string, ENT_QUOTES, 'UTF-8');
    }
    $return = array();
    foreach ($string as $array_element) {
        $return[] = qh($array_element);
    }
    return $return;
}

/**
 * Quotes a string for xml output
 *
 * @param $string string|array  input string(s)
 *
 * @return string|array  entity-encoded string appropriate for xml
 */
function qx($string) {
    if (! is_array($string)) {
        return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
    }
    $return = array();
    foreach ($string as $array_element) {
        $return[] = qx($array_element);
    }
    return $return;
}

/**
 * Returns excerpt of the input string with the given length
 *
 * @param string $string  input string
 * @param integer $length  max length
 * @param string $suffix  excerpt suffix to append
 * @param bool $cut  whether to excerpt in the middle of words (default: false)
 *
 * @return string  excerpted string, or the original if its length is less than the excerpt length
 */
function excerpt($string, $length, $suffix = 'É', $cut = false) {
    if (mb_strlen($string) < $length) { return $string; }
    $string = preg_replace('/\|/', '', $string);
    $fragments = explode('|', wordwrap($string, $length - mb_strlen($suffix) - 1, '|', $cut));
    $excerpt = $fragments[0] . ' ' . $suffix;
    return $excerpt;
}
