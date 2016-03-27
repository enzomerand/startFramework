<?php

namespace Core\Password\PasswordCompat\binary;

class binary{
        /**
         * Count the number of bytes in a string
         *
         * We cannot simply use strlen() for this, because it might be overwritten by the mbstring extension.
         * In this case, strlen() will count the number of *characters* based on the internal encoding. A
         * sequence of bytes might be regarded as a single multibyte character.
         *
         * @param string $binary_string The input string
         *
         * @internal
         * @return int The number of bytes
         */
        public function _strlen($binary_string) {
            if (function_exists('mb_strlen')) {
                return mb_strlen($binary_string, '8bit');
            }
            return strlen($binary_string);
        }
        /**
         * Get a substring based on byte limits
         *
         * @see _strlen()
         *
         * @param string $binary_string The input string
         * @param int    $start
         * @param int    $length
         *
         * @internal
         * @return string The substring
         */
        public function _substr($binary_string, $start, $length) {
            if (function_exists('mb_substr')) {
                return mb_substr($binary_string, $start, $length, '8bit');
            }
            return substr($binary_string, $start, $length);
        }
        /**
         * Check if current PHP version is compatible with the library
         *
         * @return boolean the check result
         */
        public function check() {
            static $pass = NULL;
            if (is_null($pass)) {
                if (function_exists('crypt')) {
                    $hash = '$2y$04$usesomesillystringfore7hnbRJHxXVLeakoG8K30oukPsA.ztMG';
                    $test = crypt("password", $hash);
                    $pass = $test == $hash;
                } else {
                    $pass = false;
                }
            }
            return $pass;
        }
}