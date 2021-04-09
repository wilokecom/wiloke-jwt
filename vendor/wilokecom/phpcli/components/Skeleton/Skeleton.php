<?php

#namespace WilokeTest;


#use WilokeOriginalNamespace\Helpers\FunctionHelper;

abstract class Skeleton
{
	protected $aPluck    = [];
	protected $aCurrentResponseInLoop;
	protected $aResponse = [];
	protected $oInstance;
	protected $aAdditionalArgs
	                     = [
			'thumbnail_size' => 'large'
		];

	public function setPluck($pluck): Skeleton
	{
		if (is_array($pluck)) {
			$this->aPluck = $pluck;
		} else {
			$this->aPluck = explode(',', $pluck);
		}

		$this->aPluck = array_map(function ($item) {
			return trim($item);
		}, $this->aPluck);

		return $this;
	}

	public abstract function setObject(): Skeleton;

	public abstract function validate();

	protected function convertToIconFormat($aRawIcon): array
	{
		if ($aRawIcon['type'] == 'icon') {
			return [
				'icon'    => $aRawIcon['icon'],
				'variant' => 'icon',
				'color'   => $aRawIcon['iconColor']
			];
		}

		return [
			'variant' => 'image',
			'icon'    => $aRawIcon['url']
		];
	}

	public function getItem($pluck): array
	{
		if (is_array($pluck)) {
			array_map([$this, 'getItem'], $pluck);
		} else {
			$pluck = trim($pluck);
			$method = FunctionHelper::makeFunc($pluck);
			if (method_exists($this->oInstance, $method)) {
				$this->aCurrentResponseInLoop[$pluck] = $this->oInstance->{$method}();
			} else {
				throw new \Exception(sprintf(esc_html__('The method %s does not exist',
					'wilcity-core'),
					$method));
			}
		}

		return $this->aCurrentResponseInLoop;
	}

	/**
	 * @return array
	 * @throws \Exception
	 */
	public function get(): array
	{
		$this->setObject();
		$this->oInstance->validate();

		if (empty($this->aPluck)) {
			throw new \Exception('The pluck data is required: Using setPluck method to add your pluck');
		}

		$this->aResponse = [];

		foreach ($this->aPluck as $key => $val) {
			$this->aCurrentResponseInLoop = [];

			if (is_array($val)) {
				$this->aResponse[$key] = $this->getItem($val);
			} else {
				$this->aResponse = array_merge($this->aResponse, $this->getItem($val));
			}
		}

		return $this->aResponse;
	}
}
