<?php

#namespace WilokeTest;

/**
 * Class AjaxMessage
 * @package HSBlogCore\Helpers
 */
class RestMessage extends AbstractMessage
{
	/**
	 * @param       $msg
	 * @param       $code
	 * @param array $aAdditional
	 *
	 * @return \WP_REST_Response
	 */
	public function retrieve($msg, $code, array $aAdditional = []): WP_REST_Response
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
	 * @return \WP_REST_Response
	 */
	public function success($msg, array $aAdditional = []): WP_REST_Response
	{
		return (new \WP_REST_Response($this->handleSuccess($msg, $aAdditional), 200));
	}

	/**
	 * @param $msg
	 * @param $code
	 *
	 * @return \WP_REST_Response
	 */
	public function error($msg, $code, array $aAdditional = []): WP_REST_Response
	{
		return new \WP_REST_Response($this->handleError($msg, $code, $aAdditional));
	}
}
