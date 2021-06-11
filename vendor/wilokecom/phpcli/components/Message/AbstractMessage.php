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
	abstract public function retrieve($msg, $code, array $aAdditional = []);

	/**
	 * @param       $msg
	 * @param array $aAdditional
	 *
	 * @return mixed
	 */
	abstract public function success($msg, array $aAdditional = []);

	/**
	 * @param $msg
	 * @param $code
	 * @param array $aAdditional
	 * @return mixed
	 */
	abstract public function error($msg, $code, array $aAdditional = []);

	/**
	 * @param       $msg
	 * @param array $aAdditional
	 *
	 * @return array
	 */
	protected function handleSuccess($msg, array $aAdditional = []): array
	{
		$aData = [
			'message' => $msg,
			'status'  => 'success'
		];

		return array_merge(['data' => $aAdditional], $aData);
	}

	/**
	 * @param       $msg
	 * @param       $code
	 * @param array $aAdditional
	 *
	 * @return array
	 */
	protected function handleError($msg, $code, array $aAdditional = []): array
	{
		$aData = [
			'message' => $msg,
			'code'    => $code,
			'status'  => 'error'
		];

		if (!empty($aAdditional)) {
			return array_merge($aData, ['data' => $aAdditional]);
		}

		return $aData;
	}
}
