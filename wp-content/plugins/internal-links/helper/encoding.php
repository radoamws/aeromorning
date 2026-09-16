<?php

namespace ILJ\Helper;

/**
 * Encoding toolset
 *
 * Methods for encoding / decoding of strings for the application
 *
 * @package ILJ\Helper
 * @since   1.0.0
 */
class Encoding {

	/**
	 * Masks (back-)slashes for saving into the postmeta table through WordPress sanitizing methods
	 *
	 * @since  1.0.0
	 * @param  string $regex_string The full regex pattern
	 * @return string
	 */
	public static function maskSlashes($regex_string) {
		return str_replace('\\', '|', $regex_string);
	}

	/**
	 * Unmasks sanitized (back-)slashes for retrieving a pattern from the postmeta table
	 *
	 * @since  1.0.0
	 * @param  string $masked_string The masked regex pattern
	 * @return string
	 */
	public static function unmaskSlashes($masked_string) {
		return str_replace('|', '\\', $masked_string);
	}

	/**
	 * Translates a pseudo selection rule to its regex pattern
	 *
	 * @since  1.0.0
	 * @param  string $pseudo The given pseudo pattern
	 * @return string
	 */
	public static function translatePseudoToRegex($pseudo) {
		$word_pattern = '(?:\b\w+\b\s*)';
		$regex        = preg_replace('/\s*{(\d+)}\s*/', ' ' . $word_pattern . '{\1} ', $pseudo);
		$regex        = preg_replace('/\s*{\+(\d+)}\s*/', ' ' . $word_pattern . '{\1,} ', $regex);
		$regex        = preg_replace('/\s*{\-(\d+)}\s*/', ' ' . $word_pattern . '{1,\1} ', $regex);
		$regex        = preg_replace('/^\s*(.+?)\s*$/', '\1', $regex);
		return $regex;
	}
	
	

	/**
	 * Translates a regex pattern to its equivalent pseudo pattern
	 *
	 * @since  1.0.0
	 * @param  string $regex The given regex pattern
	 * @return string
	 */
	public static function translateRegexToPseudo($regex) {
		$pseudo = preg_replace('/\(\?\:\\\b\\\w\+\\\b\\\s\*\)\{(\d+)\}/', '{\1}', $regex);
		$pseudo = preg_replace('/\(\?\:\\\b\\\w\+\\\b\\\s\*\){(\d+),}/', '{+\1}', $pseudo);
		$pseudo = preg_replace('/\(\?\:\\\b\\\w\+\\\b\\\s\*\){(\d+),(\d+)}/', '{-\2}', $pseudo);
		 // Handle simpler keywords without (?!ilj_)
		 $pseudo = preg_replace('/\(\?\:\\\b\\\w\+\\\b\\\s\*\)\{(\d+),(\d+)\}/', '{-\2}', $pseudo);
		 $pseudo = preg_replace('/\(\?\:\\\b\\\w\+\\\b\\\s\*\)\{(\d+),\}/', '{+\1}', $pseudo);
		 $pseudo = preg_replace('/\(\?\:\\\b\\\w\+\\\b\\\s\*\)\{(\d+)\}/', '{\1}', $pseudo);
		return $pseudo;
	}

	/**
	 * Decorates and manipulates a given pattern for matching optimization
	 *
	 * @since  1.1.5
	 * @param  string $pattern
	 * @return string
	 */
	public static function mask_pattern($pattern) {
		$phrase = '(?<phrase>%2$s%1$s%3$s)';

		$boundary_start = '(?<=^|\s|\"|\'|\{|\[|\<|\(|\,)';
		$boundary_end   = '(?=$|\s|\"|\.|\?|\!|\,|\)|\}|\]|\>|\;|\:)';

		 // For non ascii char:
		if (preg_match('/[[:^print:]]/', strtolower($pattern))) {
			$boundary_start = $boundary_end = '\b';
		}

		// starting/ending with special char:
		if ('' != $boundary_start && !preg_match('/^[a-z0-9àâçéèêëîïôûùüÿñæœ]/', strtolower($pattern))) {
			$boundary_start = '(?<=^|\s|\"|\'|\{|\[|\<|\(|\,)';
		}
		if ('' != $boundary_end && !preg_match('/[a-z0-9àâçéèêëîïôûùüÿñæœ]$/', strtolower($pattern))) {
			$boundary_end = '(?=$|\s|\"|\.|\?|\!|\,|\)|\}|\]|\>|\;|\:)';
		}

		// For specific for Devanagari characters:
		if (preg_match('/^\p{Devanagari}+$/u', $pattern)) {
			$boundary_start = '(?<=^|\s)';
			$boundary_end   = '(?=$|\s)';
		}

		// Automatically skip "ilj_" words in gap matches
		// Replace simple gap: (?:\b\w+\b\s*) with negative lookahead
		$pattern = preg_replace_callback(
			'/\(\?:\\\\b\\\\w\+\\\\b\\\\s\*\)/',
			function() {
				// Replace with: any word NOT starting with ilj_
				return '(?:\b(?!ilj_)\w+\b\s*)';
			},
			$pattern
		);

		$masked_pattern = sprintf($phrase, $pattern, $boundary_start, $boundary_end);
		return $masked_pattern;
	}

	/**
	 * Decodes a JSON string to an array and returns false if not parseable
	 *
	 * @since 1.2.0
	 * @param string $data
	 *
	 * @return array|bool
	 */
	public static function jsonToArray($data) {
		$json = json_decode($data, true);

		if (null === $json && json_last_error() !== JSON_ERROR_NONE) {
			return false;
		}

		return $json;
	}

	/**
	 * Used to escaped special characters that are ascii
	 *
	 * @param  string $pattern
	 * @return string
	 */
	public static function escape_ascii($pattern) {
		// Escape special Characters if not non asci
		if (!preg_match('/[[:^print:]]/', strtolower($pattern))) {
			$pattern = preg_replace('/([^A-Za-z0-9-{}+.\s])/', '\\\\$1', $pattern);
		}
		return $pattern;
	}

	/**
	 * Reverse the transformed pattern to a more readable format.
	 *
	 * @param  String $input Keyword fetched from DB
	 * @return String
	 */
	public static function reverse_transformed_pattern($input) {
		return preg_replace_callback(
			'/\(\?:\|b\|w\+\|b\|s\*\)\{(\d+)(,(\d*))?\}/',
			function ($matches) {
				$start = (int) $matches[1];
				$hasComma = isset($matches[2]) && '' !== $matches[2];
				$end = isset($matches[3]) ? trim($matches[3]) : '';
	
				if ($hasComma) {
					if ('' === $end) {
						// Case: {3,} => {+3}
						return ' {+'.$start.'}';
					} else {
						// Case: {1,3} => {-3} (assuming it always starts at 1)
						return ' {-'.$end.'}';
					}
				} else {
					// Case: {3} => {3}
					return ' {'.$start.'}';
				}
			},
			$input
		);

	}
	
	/**
	 * Checks if the keyword is a structured regex keyword (Gap feature)
	 *
	 * @param  String $keyword
	 * @return String
	 */
	public static function is_gap_style_regex($keyword) {
		return strpos($keyword, '(?:|b|w+|b|s*)') !== false;
	}
	
	/**
	 * Escape special characters for mysql
	 *
	 * @param  String $pattern
	 * @return String
	 */
	public static function escape_mysql_regex_specials($pattern) {
		$specials = array('\\', '|', '+', '*', '.', '?', '^', '$', '(', ')', '[', ']', '{', '}', '/');
		foreach ($specials as $char) {
			$pattern = str_replace($char, '\\' . $char, $pattern);
		}
		return $pattern;
	}
		
	/**
	 * Formats a keyword to a pipe structure
	 *
	 * @param  String $pseudo
	 * @return String
	 */
	public static function format_keyword_to_pipe_structure($pseudo) {
		// Add a pipe before every non-word character (i.e., special char)
		// \w matches a-z, A-Z, 0-9, and underscore. Everything else gets a `|` before it.
		return preg_replace('/([^\w\s£€])/u', '\\|$1', $pseudo);
	}
	
	/**
	 * Escape selected regex characters in a keyword.
	 *
	 * @param  String $keyword
	 * @return String
	 */
	public static function escape_selected_regex_chars($keyword) {
		$specials = array('^', '$', '[', ']', '/');

		foreach ($specials as $char) {
			$keyword = str_replace($char, '\\' . $char, $keyword);
		}

		return $keyword;
	}
}
