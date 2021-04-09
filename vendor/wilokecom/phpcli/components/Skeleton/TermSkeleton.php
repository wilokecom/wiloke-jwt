<?php

#namespace WilokeTest;

use WP_Term;

class TermSkeleton extends Skeleton
{
	private static $_self = null;
	private        $oTerm = null;

	public function setObject(): Skeleton
	{
		$this->oInstance = self::$_self;
		return $this;
	}

	public function validate()
	{
		// TODO: Implement validate() method.
	}

	/**
	 * @param WP_Term $oTerm
	 * @return TermSkeleton
	 */
	public static function init(WP_Term $oTerm): TermSkeleton
	{
		if (self::$_self == null) {
			self::$_self = new TermSkeleton();
		}

		self::$_self->oTerm = $oTerm;
		return self::$_self;
	}

	public function setPluck($pluck): TermSkeleton
	{
		if (is_array($pluck)) {
			$this->aPluck = $pluck;
		} else {
			$this->aPluck = explode(',', $pluck);
		}

		return $this;
	}
}
