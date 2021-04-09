<?php

#namespace WilokeTest;

/**
 * Class AjaxMessage
 * @package HSBlogCore\Helpers
 */
class NormalMessage extends AbstractMessage
{
    /**
     * @param       $msg
     * @param       $code
     * @param array $aAdditional
     *
     * @return array
     */
    public function retrieve($msg, $code, $aAdditional = [])
    {
        if ($code == 200) {
            return $this->success($msg, $aAdditional);
        } else {
            return $this->error($msg, $code, $aAdditional);
        }
    }

    /**
     * @param       $msg
     * @param array $aAdditional
     *
     * @return array
     */
    public function success($msg, $aAdditional = [])
    {
	    $aData = [
		    'msg'    => $msg,
		    'status' => 'success'
	    ];

        return array_merge($aAdditional, $aData);
    }

    /**
     * @param       $msg
     * @param       $code
     * @param array $aAdditional
     *
     * @return array
     */
    public function error($msg, $code, $aAdditional = [])
    {
        $aData = [
            'msg'    => $msg,
            'code'   => $code,
            'status' => 'error'
        ];

        return array_merge($aData, $aAdditional);
    }
}
