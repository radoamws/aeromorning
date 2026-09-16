<?php
namespace ILJ\Helper;

use function ILJ\ilj_try_include_file;

if (!class_exists('Brumann\Polyfill\DisallowedClassesSubstitutor')) ilj_try_include_file('vendor/brumann/polyfill-unserialize/src/DisallowedClassesSubstitutor.php', 'require_once');
if (!class_exists('Brumann\Polyfill\Unserialize')) ilj_try_include_file('vendor/brumann/polyfill-unserialize/src/Unserialize.php', 'require_once');

/**
 * Loader
 *
 * Class for miscellaneous methods.
 *
 * @package ILJ\Helper
 */
final class Misc {

	/**
	 * Unserializes data only if it was serialized.
	 *
	 * @param string $data Data that might be unserialized.
	 * @return mixed Unserialized data can be any type.
	 */
	public static function maybe_unserialize($data) {
		if (is_serialized($data)) { // Don't attempt to unserialize data that wasn't serialized going in.
			return self::unserialize(trim($data), false);
		}
		return $data;
	}

	/**
	 * Unserialize data while maintaining compatibility across PHP versions due to different number of arguments required by PHP's "unserialize" function
	 *
	 * @param string        $serialized_data Data to be unserialized, should be one that is already serialized
	 * @param boolean|array $allowed_classes Either an array of class names which should be accepted, false to accept no classes, or true to accept all classes
	 * @param integer       $max_depth       The maximum depth of structures permitted during unserialization, and is intended to prevent stack overflows
	 * @return mixed Unserialized data can be any of types (integer, float, boolean, string, array or object)
	 */
	public static function unserialize($serialized_data, $allowed_classes = false, $max_depth = 0) {
		if (version_compare(PHP_VERSION, '5.2', '<=')) {
			$result = unserialize($serialized_data); // For PHP 5.2 users, the search-replace feature has been removed, meaning that any input provided in this context will not undergo search-replace processing
		} else {
			$result = call_user_func(array('Brumann\Polyfill\Unserialize', 'unserialize'), $serialized_data, array('allowed_classes' => $allowed_classes, 'max_depth' => $max_depth));
		}
		return $result;
	}
}
