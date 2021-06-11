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
	public function retrieve($msg, $code, array $aAdditional = []): string
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
	public function success($msg, array $aAdditional = []): string
	{
		return '%SC%' . json_encode($this->handleSuccess($msg, $aAdditional)) . '%SC%';
	}

	/**
	 * @param $msg
	 * @param $code
	 *
	 * @return string
	 */
	public function error($msg, $code, array $aAdditional = []): string
	{
		return '%SC%' . json_encode($this->handleError($msg, $code, $aAdditional)) . '%SC%';
	}
}
