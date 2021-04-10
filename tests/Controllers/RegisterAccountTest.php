<?php


namespace WilokeJWTTest\Controllers;


use WilokeJWT\Helpers\ClientIP;
use WilokeJWTTest\CommonController;

class RegisterAccountTest extends CommonController
{
    use ClientIP;
    private $aConfigData=[
            'client_session' => '123443434',
            'app_id'         => 'app_id_606fd51714cd6',
            'app_secret'     => '11e45a2723eed54de25f7c194b58312f'
        ];
    public function testGetCode()
    {
        $aResponse = $this->restPOST('register-code', $this->aConfigData);
        $this->assertIsArray($aResponse);
        $this->assertEquals($this->aConfigData['client_session'],$aResponse['client_session'],'returning equal customers');
        $this->assertArrayHasKey('code', $aResponse);
        return $aResponse['code'];
    }
    /**
     * @depends testGetCode
     */
    public function testRegister($code){
        $aResponse = $this->restPOST('sign-up', [
            'code'  => $code,
            'email' => 'adb@gmail.com'
        ]);
        $this->assertIsArray($aResponse);
        $this->assertEquals('Congrats, You have registered successfully',$aResponse['msg'],'returning equal customers');
        $this->assertArrayHasKey('accessToken', $aResponse);
        return $aResponse;
    }
}