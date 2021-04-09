<?php


namespace WilokeJWTTest\Controllers;


use WilokeJWT\Helpers\ClientIP;
use WilokeJWT\Models\PreLoginModel;
use WilokeJWTTest\CommonController;

class LoginControllerTest extends CommonController
{
	use ClientIP;
	public function testGetCode() {
		$aResponse = $this->restPOST('register-code');
		$this->assertArrayHasKey('error', $aResponse);

		$aResponse = $this->restPOST('register-code', [
			'client_session' => uniqid('oke')
		]);

		$this->assertNotEmpty($aResponse['code']);
		$this->assertIsString($aResponse['code']);

		return $aResponse['code'];
	}

	/**
	 * @depends testGetCode
	 */
	public function testMatched($code) {
		$status = PreLoginModel::isMatchedCode($code, '::1');
		$this->assertTrue($status);
	}
}
