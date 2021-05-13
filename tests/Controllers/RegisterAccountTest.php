<?php


namespace WilokeJWTTest\Controllers;


use WilokeJWT\Helpers\ClientIP;
use WilokeJWTTest\CommonController;

class RegisterAccountTest extends CommonController
{
    use ClientIP;
    private array $aConfigData =[
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
            'email' => 'adbccc1@gmail.com'
        ]);
        $this->assertIsArray($aResponse);
        $this->assertEquals('Congrats, You have registered successfully',$aResponse['msg'],'returning equal customers');
        $this->assertArrayHasKey('accessToken', $aResponse);
        return $aResponse;
    }

    /**
     * @depends testRegister
     */
    public function testLogin($aToken)
    {
        $aResponse = $this->restPOST('sign-in-with-wilcity', [
            'accessToken'  => $aToken['accessToken'],
            'refreshToken' => $aToken['refreshToken']
        ]);
        $this->assertIsArray($aResponse);
        $this->assertEquals('Congrats, You have logged in successfully', $aResponse['msg'],
            'returning equal customers');
        $this->assertEquals('success', $aResponse['status']);
        return $aToken;
    }
    /**
     * @depends testLogin
     */
    public function testRefreshToken($aToken)
    {
        sleep(20);
        $aResponse = $this->restPOST('renew-token', [
            'accessToken'  => $aToken['accessToken'],
            'refreshToken' => $aToken['refreshToken']
        ]);
        $this->assertIsArray($aResponse);
        $this->assertEquals('Assess token created new successfully', $aResponse['msg'],
            'returning equal customers');
        $this->assertEquals('success', $aResponse['status']);
        return $aToken;
    }
}
