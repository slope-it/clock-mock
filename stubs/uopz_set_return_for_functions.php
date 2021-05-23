<?php

/**
 * Provide a return value for an existing function
 * @link https://secure.php.net/manual/en/function.uopz-set-return.php
 * @param string $function The name of an existing function
 * @param mixed $value The value the function should return. If a Closure is provided and the execute flag is set, the Closure will be executed in place of the original function
 * @param bool $execute If true, and a Closure was provided as the value, the Closure will be executed in place of the original function.
 * @return bool
 * @since 7.0
 */
function uopz_set_return(string $function, $value, $execute = false): bool {}

/**
 * Unsets a previously set return value for a function
 * @link https://secure.php.net/manual/en/function.uopz-unset-return.php
 * @param string $function The name of an existing function
 * @return bool
 * @since 7.0
 */
function uopz_unset_return(string $function): bool {}
