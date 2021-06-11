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
	public function retrieve($msg, $code, array $aAdditional = [])
	{
		if ($code == 200) {
			$this->success($msg, $aAdditional);
		} else {
			$this->error($msg, $code, $aAdditional);
		}
	}

	private function sendJson(array $aMessage, $statusCode)
	{
		if (!headers_sent()) {
			header('Content-Type: application/json; charset=' . get_option('blog_charset'));
			if (null !== $statusCode) {
				status_header($statusCode);
			}
		}

		echo wp_json_encode($aMessage);

		die;
	}

	/**
	 * @param       $msg
	 * @param array $aAdditional
	 *
	 * @return void
	 */
	public function success($msg, array $aAdditional = [])
	{
		$this->sendJson($this->handleSuccess($msg, $aAdditional), 200);
	}

	/**
	 * @param       $msg
	 * @param array $aAdditional
	 * @param       $code
	 *
	 * @return void
	 */
	public function error($msg, $code, array $aAdditional = [])
	{
		$this->sendJson($this->handleError($msg, $code, $aAdditional), $code);
	}
}
