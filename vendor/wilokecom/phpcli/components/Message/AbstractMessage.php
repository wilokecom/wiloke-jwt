<?php

#namespace WilokeTest;

/**
 * Class
 * @package HSBlogCore\Helpers
 */
abstract class AbstractMessage
{
    /**
     * @param       $msg
     * @param       $code
     * @param array $aAdditional
     *
     * @return mixed
     */
    abstract public function retrieve($msg, $code, $aAdditional = []);

    /**
     * @param       $msg
     * @param array $aAdditional
     *
     * @return mixed
     */
    abstract public function success($msg, $aAdditional = []);

    /**
     * @param $msg
     * @param $code
     *
     * @return mixed
     */
    abstract public function error($msg, $code);
}
