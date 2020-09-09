<?php

declare(strict_types=1);

namespace Dakujem\Cumulus;

/**
 * Creating middleware and pipelines in a breeze...
 *
 * @author Andrej Rypak <xrypak@gmail.com>
 */
final class Breeze
{
    /**
     * Build a simple callable pipeline that executes all callables in $stages
     * and passes their return value from one to another, returning the result of the last one.
     *
     * @param callable[] $stages an array of callables with signature fn($x):$y
     * @return callable Returns a function with signature fn($x=null):$y
     */
    public static function tube(array $stages): callable
    {
        return function ($passable = null) use ($stages) {
            return array_reduce($stages, function ($passable, callable $callable) {
                return call_user_func($callable, $passable);
            }, $passable);
        };
        // ^ rough equivalent of:
//    return function ($passable) use ($stages) {
//        foreach ($stages as $stage) {
//            $passable = call_user_func($stage, $passable);
//        }
//        return $passable;
//    };
    }

    /**
     * Builds a LIFO callable pipeline.
     * The stages are executed in reversed order - LIFO - the last stage is executed first!
     *
     * These pipelines are commonly used as middleware dispatchers
     * and resemble an onion with layers added on top of each other.
     * The outer-most layer is executed first.
     *
     * @param callable[] $stages an array of callables with signature fn($x,callable $next):$y
     * @return callable Returns a callable with signature fn($x):$y
     */
    public static function onion(array $stages): callable
    {
        return static::buildInvertedMiddlewareDispatcher(array_reverse($stages));
    }

    /**
     * Builds a FIFO callable pipeline.
     *
     * Layers are executed in the provided order - the first stage is executed first.
     * This execution is inverted (inside-out) compared to the traditional onion model used for middleware.
     *
     * @param callable[] $stages an array of callables with signature fn($x,callable $next):$y
     * @return callable Returns a callable with signature fn($x):$y
     */
    public static function invertedOnion(array $stages): callable
    {
        return static::buildInvertedMiddlewareDispatcher($stages);
    }

    /**
     * @internal
     *
     * @param callable[] $reversedMiddlewareStack
     * @return callable Returns a callable with signature fn($x):$y
     */
    private static function buildInvertedMiddlewareDispatcher(array $reversedMiddlewareStack): callable
    {
        if ($reversedMiddlewareStack !== []) {
            //
            // Recursive function that builds a callable onion pipeline from an array of callable stages.
            //
            $rec = null;
            $rec = function (array $pipeline, ?callable $previous) use (&$rec): callable {
                //
                // When the pipeline is not empty, it containes "stages".
                // Stages are callables with signature fn($x,$next):$y.
                //
                // At this point, the current stage is wrapped in a "wrapper" closure with signature fn($x):$y
                // which is then passed as the $next argument to the following stage.
                //
                // Repeat recursively until the pipeline is empty.
                //
                if ($pipeline !== []) {
                    // Note: array_pop (with array_reverse) is used instead of array_shift for performance reasons
                    $current = array_pop($pipeline);
                    // Note: The wrapper function from the previous iteration becomes the $next argument for current stage.
//                    $next = fn($value) => call_user_func($current, $value, $previous ?? static::identity()); // PHP 7.4 onwards
                    $next = function ($value) use ($current, $previous) {
                        return call_user_func($current, $value, $previous ?? static::identity());
                    };
                    // Note: The $next wrapper becomes $previous in the following iteration.
                    return call_user_func($rec, $pipeline, $next);
                }

                //
                // At the end of the recursion, when the pipeline is empty,
                // return a function that resolves the first stage in the (reversed) pipeline.
                //
                // Note:
                //   The $previous function always has signature fn($val):$result,
                //   as it is provided by the next stage using static closure variables.
                //
//                return fn($value) => call_user_func($previous, $value); // PHP 7.4 onwards
                return function ($value) use ($previous) {
                    return call_user_func($previous, $value);
                };
            };

            // Initiate the recursive function.
            return call_user_func($rec, $reversedMiddlewareStack, null);
        }

        // In case the pipeline is empty, return an identity function f(x)=x right away.
        return static::identity();
    }

    /**
     * Returns an identity function:
     *   f(x)=x, for every x
     *
     * @return callable fn($x):$x
     */
    public static function identity(): callable
    {
        // return fn($v) => $v; // PHP 7.4 onwards
        return function ($v) {
            return $v;
        };
    }
}
