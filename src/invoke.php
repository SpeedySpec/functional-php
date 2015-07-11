<?php
/**
 * @package Mimic\Functional
 * @since 0.1.0
 * @license MIT
 */

/** @package Mimic\Functional */
namespace Mimic\Functional;

use Traversable;

/**
 * Execute single callback with optional arguments.
 *
 * @api
 * @since 0.1.0
 *
 * @param callable $callback
 * @param mixed ...$args
 * @return mixed
 */
function apply($callback) {
	if ( func_num_args() === 1 ) {
		return $callback();
	}
	return call_user_func_array($callback, array_slice(func_get_args(), 1));
}


/**
 * Execute callback when test callback passed.
 *
 * @api
 * @since 0.1.0
 *
 * @param callable $callback
 * @param callable $test
 * @return Closure {
 *     @return boolean|mixed
 *       False on failure.
 * }
 */
function attempt($callback, $test) {
	return function() use ($test, $callback) {
		if ($test() === false) {
			return false;
		}
		return $callback();
	};
}

/**
 * Executes left callback and if it fails will execute the right callback.
 *
 * @api
 * @since 0.1.0
 *
 * @param callable $left
 * @param callable $right
 * @return Closure {
 *     @return mixed
 * }
 */
function either($left, $right) {
	return function() use ($left, $right) {
		$value = $left();
		if ($value !== false) {
			return $value;
		}
		return $right();
	};
}

/**
 * Execute callback.
 *
 * @api
 * @since 0.1.0
 *
 * @param callable ...$args
 * @return array<mixed>
 */
function execute() {
	if (func_num_args() < 1) {
		return array();
	}

	return map(func_get_args(), function($element) {
		if ( is_callable($element) ) {
			return apply($element);
		}
		return null;
	});
}

/**
 * Invoke method on class on collection passing all results.
 *
 * @api
 * @since 0.1.0
 *
 * @param Traversable|array<string|object> $collection
 *   It is possible to call both class methods by passing strings and instance
 *   methods by passing objects or both by mixing both types as long as the
 *   method exists in the class.
 * @param string $methodName
 * @param array<mixed> $arguments
 *   This is passed to the new callback based on collection element and given
 *   method name.
 * @return array<mixed>
 */
function invoke($collection, $methodName, array $arguments = array()) {
	return map($collection, function($element) use ($methodName, $arguments) {
		return invokeIf($element, $methodName, $arguments);
	});
}

/**
 * Retrieve result from first successful method call on object from collection.
 *
 * @api
 * @since 0.1.0
 *
 * @param Traversable|array<string|object> $collection
 *   It is possible to call both class methods by passing strings and instance
 *   methods by passing objects or both by mixing both types as long as the
 *   method exists in the class.
 * @param string $methodName
 * @param array<mixed> $arguments
 *   This is passed to the new callback based on collection element and given
 *   method name.
 * @return mixed
 */
function invokeFirst($collection, $methodName, array $arguments = array()) {
	$callback = function($element) use ($methodName) {
		return is_callable(array($element, $methodName));
	};
	return invokeIf(first($collection, $callback), $methodName, $arguments);
}

/**
 * Invoke method on object if callable and return result or default.
 *
 * @api
 * @since 0.1.0
 *
 * @param object|string $object
 * @param string $methodName
 * @param array $arguments
 * @param mixed $default
 *   Optional. Default is null. Will be callback, if not callable.
 * @return mixed
 */
function invokeIf($object, $methodName, array $arguments = array(), $default = null) {
	$callback = array($object, $methodName);
	if ( ! is_callable($callback) ) {
		return $default;
	}
	return call_user_func_array($callback, $arguments);
}

/**
 * Retrieve result from last successful method call on object from collection.
 *
 * @api
 * @since 0.1.0
 *
 * @param Traversable|array<string|object> $collection
 *   It is possible to call both class methods by passing strings and instance
 *   methods by passing objects or both by mixing both types as long as the
 *   method exists in the class.
 * @param string $methodName
 * @param array<mixed> $arguments
 *   This is passed to the new callback based on collection element and given
 *   method name.
 * @return mixed
 */
function invokeLast($collection, $methodName, array $arguments = array()) {
	return invokeFirst(array_reverse($collection, true), $methodName, $arguments);
}

/**
 * Memorize caches the result for a given set of arguments.
 *
 * Functions or callbacks that use this must have pure functions or callbacks
 * that return the same result every time the same set of arguments are passed.
 * Not following this will break the execution of the process and given
 * inaccurate results.
 *
 * @api
 * @since 0.1.0
 *
 * @param callable $callback
 * @return MemoizeCache
 */
function memoize($callback) {
	static $_container = array();

	$hash = hash_array(array($callback));

	if ( ! isset($_container[ $hash ]) ) {
		$_container[ $hash ] = new MemoizeCache($callback);
	}

	return $_container[ $hash ];
}

/**
 * Negate result of collection item callback.
 *
 * @api
 * @since 0.1.0
 *
 * @param MapCollectionCallback|callable
 * @return MapCollectionCallback|callable
 */
function negate($callback) {
	return function($element, $index, $collection) use ($callback) {
		return ! $callback($element, $index, $collection);
	};
}
