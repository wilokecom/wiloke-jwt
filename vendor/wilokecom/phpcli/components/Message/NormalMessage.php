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
	public function retrieve($msg, $code, array $aAdditional = []): array
	{
		if ($code == 200) {
			return $this->success($msg, $aAdditional);
		} else {
			return $this->error($msg, $code, $aAdditional);
		}
	}

	public function success($msg, array $aAdditional = [])
	{
		return $this->handleSuccess($msg, $aAdditional);
	}

	public function error($msg, $code, array $aAdditional = [])
	{
		return $this->handleError($msg, $code, $aAdditional);
	}
}
