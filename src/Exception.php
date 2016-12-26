<?php

// vim:ts=4:sw=4:et:fdm=marker

namespace atk4\core;

/**
 * All exceptions generated by Agile Core will use this class.
 *
 * @license MIT
 * @copyright Agile Toolkit (c) http://agiletoolkit.org/
 */
class Exception extends \Exception
{
    /**
     * Most exceptions would be a cause by some other exception, Agile
     * Core will encapsulate them and allow you to access them anyway.
     */
    private $params = [];

    public $trace2; // because PHP's use of final() sucks!

    /**
     * Constructor.
     *
     * @param string     $message
     * @param int        $code
     * @param \Exception $previous
     */
    public function __construct(
        $message = '',
        $code = 0,
        \Exception $previous = null
    ) {
        if (is_array($message)) {
            // message contain additional parameters
            $this->params = $message;
            $message = array_shift($this->params);
        }

        parent::__construct($message, $code, $previous);
        $this->trace2 = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT);
    }

    public function getMyTrace()
    {
        return $this->trace2;
    }

    /**
     * Output exception message using color sequences.
     *
     * <exception name>: <string>
     * <info>
     *
     * trace
     *
     * --
     * <triggered by>
     *
     * @return string
     */
    public function getColorfulText()
    {
        $output = "\033[1;31m--[ Agile Toolkit Exception ]---------------------------\n";
        $output .= get_class($this).": \033[47m".$this->getMessage()."\033[0;31m".
            ($this->getCode() ? ' [code: '.$this->getCode().']' : '');

        foreach ($this->params as $key => $val) {
            $key = str_pad($key, 19, ' ', STR_PAD_LEFT);
            $output .= "\n".$key.': '.$this->toString($val);
        }

        $output .= "\n\033[0mStack Trace: ";

        $in_atk = true;
        $escape_frame = false;

        foreach ($this->getTrace() as $i => $call) {
            if (!isset($call['file'])) {
                $call['file'] = '';
            } elseif (
                $in_atk &&
                strpos($call['file'], '/data/src/') === false &&
                strpos($call['file'], '/core/src/') === false &&
                strpos($call['file'], '/dsql/src/') === false
            ) {
                $escape_frame = true;
                $in_atk = false;
            }

            $file = str_pad(substr($call['file'], -40), 40, ' ', STR_PAD_LEFT);

            $line = str_pad(@$call['line'], 4, ' ', STR_PAD_LEFT);

            $output .= "\n\033[0;34m".$file."\033[0m";
            $output .= ":\033[0;31m".$line."\033[0m";

            if (isset($call['object'])) {
                $name = (!isset($call['object']->name)) ? get_class($call['object']) : $call['object']->name;
                $output .= " - \033[0;32m".$name."\033[0m";
            }

            $output .= " \033[0;32m";

            if (isset($call['class'])) {
                $output .= $call['class'].'::';
            }

            if ($escape_frame) {
                $output .= "\033[0,31m".$call['function'];
                $escape_frame = false;

                $args = [];
                foreach ($call['args'] as $arg) {
                    $args[] = $this->toString($arg);
                }

                $output .= "\n".str_repeat(' ', 20)."\033[0,31m(".implode(', ', $args);
            } else {
                $output .= "\033[0,33m".$call['function'].'(';
            }

            $output .= ')';
        }

        if ($p = $this->getPrevious()) {
            $output .= "\n\033[0mCaused by Previous Exception:\n";
            $output .= "\033[1;31m".get_class($p).': '.$p->getMessage()."\033[0;31m".
                ($p->getCode() ? ' [code: '.$p->getCode().']' : '');
        }

        // next print params

        $output .= "\n\033[1;31m--------------------------------------------------------\n";

        return $output."\033[0m";
    }

    /**
     * Safely converts some value to string.
     *
     * @param mixed $val
     *
     * @return string
     */
    public function toString($val)
    {
        if (is_object($val) && !$val instanceof \Closure) {
            if (isset($val->_trackableTrait)) {
                return get_class($val).' ('.$val->name.')';
            }

            return 'Object '.get_class($val);
        }

        return json_encode($val);
    }

    /**
     * Follow the getter-style of PHP Exception.
     *
     * @return array
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * Augment existing exception with more info.
     *
     * @param string $param
     * @param mixed  $value
     *
     * @return $this
     */
    public function addMoreInfo($param, $value)
    {
        $this->params[$param] = $value;

        return $this;
    }
}
