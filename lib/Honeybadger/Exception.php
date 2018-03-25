<?php

namespace Honeybadger;

/**
 * Based on [Kohana's exception
 * handler](https://github.com/kohana/core/blob/3.4/develop/classes/Kohana/Kohana/Exception.php#L102:L130).
 *
 * @package  Honeybadger
 */

class Exception
{

    /**
     * @var
     */
    private static $previous_handler;

    /**
     *
     * @return void
     */
    public static function register_handler()
    {
        self::$previous_handler = set_exception_handler(
            [
                __CLASS__, 'handle',
            ]
        );
    }

    /**
     * Restores the previous handler
     *
     * @return  void
     */
    public static function restore_handler()
    {
        set_exception_handler(self::$previous_handler);
    }

    /**
     * @param \Exception $e
     *
     * @return mixed
     */
    public static function handle(\Exception $e)
    {
        try {
            // Attempt to send this exception to Honeybadger.
            Honeybadger::notifyOrIgnore($e);
        } catch (\Exception $e) {
            if (is_callable(self::$previous_handler)) {
                return call_user_func(self::$previous_handler, $e);
            } else {
                // Clean the output buffer if one exists.
                ob_get_level() and ob_clean();

                // Set the Status code to 500, and Content-Type to text/plain.
                header('Content-Type: text/plain; charset=utf-8', true, 500);

                echo 'Something went terribly wrong.';

                // Exit with a non-zero status.
                exit(1);
            }
        }

        if (is_callable(self::$previous_handler)) {
            return call_user_func(self::$previous_handler, $e);
        }

        return null;
    }
} // End Exception
