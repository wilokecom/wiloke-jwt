<?php

#namespace WilokeTest;

/**
 * Class AjaxMessage
 * @package HSBlogCore\Helpers
 */
class AjaxMessage extends AbstractMessage
{
    /**
     * @param       $msg
     * @param       $code
     * @param array $aAdditional
     *
     * @return void
     */
    public function retrieve($msg, $code, $aAdditional = [])
    {
        if ($code == 200) {
            $this->success($msg, $aAdditional);
        } else {
            $this->error($msg, $code, $aAdditional);
        }
    }

    /**
     * @param       $msg
     * @param array $aAdditional
     *
     * @return void
     */
    public function success($msg, $aAdditional = [])
    {
        $aData = [
            'data' => $msg
        ];

        $aData = array_merge($aData, $aAdditional);

        wp_send_json_success($aData);
    }

    /**
     * @param       $msg
     * @param array $aAdditional
     * @param       $code
     *
     * @return void
     */
    public function error($msg, $code, $aAdditional = [])
    {
	    $aData = [
		    'msg'    => $msg,
		    'status' => 'success'
	    ];

        $aData = array_merge($aData, $aAdditional);

        wp_send_json_error($aData);
    }
}
