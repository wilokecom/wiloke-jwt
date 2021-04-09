<?php

#namespace WilokeTest;

/**
 * Class AjaxMessage
 * @package HSBlogCore\Helpers
 */
class ShortcodeMessage extends AbstractMessage
{
    /**
     * @param       $msg
     * @param       $code
     * @param array $aAdditional
     *
     * @return string
     */
    public function retrieve($msg, $code, $aAdditional = [])
    {
        if ($code == 200) {
            return $this->success($msg, $aAdditional);
        } else {
            return $this->error($msg, $code);
        }
    }

    /**
     * @param       $msg
     * @param array $aAdditional
     *
     * @return string
     */
    public function success($msg, $aAdditional = [])
    {
        $aData = [
            'data'   => $msg,
            'status' => 'success'
        ];

        $aData = array_merge($aData, $aAdditional);

        return '%SC%'.json_encode($aData).'%SC%';
    }

    /**
     * @param $msg
     * @param $code
     *
     * @return string
     */
    public function error($msg, $code)
    {
        return '%SC%'.json_encode([
                'error'  => $msg,
                'code'   => $code,
                'status' => 'error'
            ]).'%SC%';
    }
}
