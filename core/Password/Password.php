<?php
/**
 * A Compatibility library with PHP 5.5's simplified password hashing API.
 *
 * @author Anthony Ferrara <ircmaxell@php.net>
 * @license http://www.opensource.org/licenses/mit-license.html MIT License
 * @copyright 2012 The Authors
 */
namespace Core\Password;

use Core\Password\PasswordCompat\binary\binary;

class Password extends binary{
	public function __construct(){
		if (!defined('PASSWORD_BCRYPT')) {
			/**
			 * PHPUnit Process isolation caches constants, but not function declarations.
			 * So we need to check if the constants are defined separately from 
			 * the functions to enable supporting process isolation in userland
			 * code.
			 */
			define('PASSWORD_BCRYPT', 1);
			define('PASSWORD_DEFAULT', PASSWORD_BCRYPT);
			define('PASSWORD_BCRYPT_DEFAULT_COST', 10);
		}
    }
	
	public function password_hash($password, $algo, array $options = array()) {
		if (!function_exists('crypt')) {
			trigger_error("Crypt must be loaded for password_hash to function", E_USER_WARNING);
			return null;
		}
		if (is_null($password) || is_int($password)) {
			$password = (string) $password;
		}
		if (!is_string($password)) {
			trigger_error("password_hash(): Password must be a string", E_USER_WARNING);
			return null;
		}
		if (!is_int($algo)) {
			trigger_error("password_hash() expects parameter 2 to be long, " . gettype($algo) . " given", E_USER_WARNING);
			return null;
		}
		$resultLength = 0;
		switch ($algo) {
			case PASSWORD_BCRYPT:
				$cost = PASSWORD_BCRYPT_DEFAULT_COST;
				if (isset($options['cost'])) {
					$cost = (int) $options['cost'];
					if ($cost < 4 || $cost > 31) {
						trigger_error(sprintf("password_hash(): Invalid bcrypt cost parameter specified: %d", $cost), E_USER_WARNING);
						return null;
					}
				}
				// The length of salt to generate
				$raw_salt_len = 16;
				// The length required in the final serialization
				$required_salt_len = 22;
				$hash_format = sprintf("$2y$%02d$", $cost);
				// The expected length of the final crypt() output
				$resultLength = 60;
				break;
			default:
				trigger_error(sprintf("password_hash(): Unknown password hashing algorithm: %s", $algo), E_USER_WARNING);
				return null;
		}
		$salt_req_encoding = false;
		if (isset($options['salt'])) {
			switch (gettype($options['salt'])) {
				case 'NULL':
				case 'boolean':
				case 'integer':
				case 'double':
				case 'string':
					$salt = (string) $options['salt'];
					break;
				case 'object':
					if (method_exists($options['salt'], '__tostring')) {
						$salt = (string) $options['salt'];
						break;
					}
				case 'array':
				case 'resource':
				default:
					trigger_error('password_hash(): Non-string salt parameter supplied', E_USER_WARNING);
					return null;
			}
			if ($this->_strlen($salt) < $required_salt_len) {
				trigger_error(sprintf("password_hash(): Provided salt is too short: %d expecting %d", $this->_strlen($salt), $required_salt_len), E_USER_WARNING);
				return null;
			} elseif (0 == preg_match('#^[a-zA-Z0-9./]+$#D', $salt)) {
				$salt_req_encoding = true;
			}
		} else {
			$buffer = '';
			$buffer_valid = false;
			if (function_exists('mcrypt_create_iv') && !defined('PHALANGER')) {
				$buffer = mcrypt_create_iv($raw_salt_len, MCRYPT_DEV_URANDOM);
				if ($buffer) {
					$buffer_valid = true;
				}
			}
			if (!$buffer_valid && function_exists('openssl_random_pseudo_bytes')) {
				$strong = false;
				$buffer = openssl_random_pseudo_bytes($raw_salt_len, $strong);
				if ($buffer && $strong) {
					$buffer_valid = true;
				}
			}
			if (!$buffer_valid && @is_readable('/dev/urandom')) {
				$file = fopen('/dev/urandom', 'r');
				$read = 0;
				$local_buffer = '';
				while ($read < $raw_salt_len) {
					$local_buffer .= fread($file, $raw_salt_len - $read);
					$read = $this->_strlen($local_buffer);
				}
				fclose($file);
				if ($read >= $raw_salt_len) {
					$buffer_valid = true;
				}
				$buffer = str_pad($buffer, $raw_salt_len, "\0") ^ str_pad($local_buffer, $raw_salt_len, "\0");
			}
			if (!$buffer_valid || $this->_strlen($buffer) < $raw_salt_len) {
				$buffer_length = $this->_strlen($buffer);
				for ($i = 0; $i < $raw_salt_len; $i++) {
					if ($i < $buffer_length) {
						$buffer[$i] = $buffer[$i] ^ chr(mt_rand(0, 255));
					} else {
						$buffer .= chr(mt_rand(0, 255));
					}
				}
			}
			$salt = $buffer;
			$salt_req_encoding = true;
		}
		if ($salt_req_encoding) {
			// encode string with the Base64 variant used by crypt
			$base64_digits =
				'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/';
			$bcrypt64_digits =
				'./ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
			$base64_string = base64_encode($salt);
			$salt = strtr(rtrim($base64_string, '='), $base64_digits, $bcrypt64_digits);
		}
		$salt = $this->_substr($salt, 0, $required_salt_len);
		$hash = $hash_format . $salt;
		$ret = crypt($password, $hash);
		if (!is_string($ret) || $this->_strlen($ret) != $resultLength) {
			return false;
		}
		return $ret;
	}
	/**
	 * Get information about the password hash. Returns an array of the information
	 * that was used to generate the password hash.
	 *
	 * array(
	 *    'algo' => 1,
	 *    'algoName' => 'bcrypt',
	 *    'options' => array(
	 *        'cost' => PASSWORD_BCRYPT_DEFAULT_COST,
	 *    ),
	 * )
	 *
	 * @param string $hash The password hash to extract info from
	 *
	 * @return array The array of information about the hash.
	 */
	public function password_get_info($hash) {
		$return = array(
			'algo' => 0,
			'algoName' => 'unknown',
			'options' => array(),
		);
		if ($this->_substr($hash, 0, 4) == '$2y$' && $this->_strlen($hash) == 60) {
			$return['algo'] = PASSWORD_BCRYPT;
			$return['algoName'] = 'bcrypt';
			list($cost) = sscanf($hash, "$2y$%d$");
			$return['options']['cost'] = $cost;
		}
		return $return;
	}
	/**
	 * Determine if the password hash needs to be rehashed according to the options provided
	 *
	 * If the answer is true, after validating the password using password_verify, rehash it.
	 *
	 * @param string $hash    The hash to test
	 * @param int    $algo    The algorithm used for new password hashes
	 * @param array  $options The options array passed to password_hash
	 *
	 * @return boolean True if the password needs to be rehashed.
	 */
	public function password_needs_rehash($hash, $algo = PASSWORD_BCRYPT, array $options = array()) {
		$info = password_get_info($hash);
		if ($info['algo'] !== (int) $algo) {
			return true;
		}
		switch ($algo) {
			case PASSWORD_BCRYPT:
				$cost = isset($options['cost']) ? (int) $options['cost'] : PASSWORD_BCRYPT_DEFAULT_COST;
				if ($cost !== $info['options']['cost']) {
					return true;
				}
				break;
		}
		return false;
	}
	/**
	 * Verify a password against a hash using a timing attack resistant approach
	 *
	 * @param string $password The password to verify
	 * @param string $hash     The hash to verify against
	 *
	 * @return boolean If the password matches the hash
	 */
	public function password_verify($password, $hash) {
		if (!function_exists('crypt')) {
			trigger_error("Crypt must be loaded for password_verify to function", E_USER_WARNING);
			return false;
		}
		$ret = crypt($password, $hash);
		if (!is_string($ret) || $this->_strlen($ret) != $this->_strlen($hash) || $this->_strlen($ret) <= 13) {
			return false;
		}
		$status = 0;
		for ($i = 0; $i < $this->_strlen($ret); $i++) {
			$status |= (ord($ret[$i]) ^ ord($hash[$i]));
		}
		return $status === 0;
	}
}