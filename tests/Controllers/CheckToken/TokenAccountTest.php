<?php

namespace WilokeJWTTest\Controllers\CheckToken;

use WilokeJWT\Controllers\GenerateTokenController;
use WilokeJWT\Core\Core;
use WilokeJWT\Helpers\Option;
use WilokeJWTTest\CommonController;

class TokenAccountTest extends CommonController
{
    public array $aDataUser
        = [
            'username' => 'test1323',
            'password' => 'test1233'
        ];

    public function createRandomUser()
    {
        return wp_create_user(uniqid($this->aDataUser['username']), $this->aDataUser['password']);
    }

    public function createUserDatabase()
    {
        if (username_exists($this->aDataUser['username'])) {
            return get_user_by('login', $this->aDataUser['username'])->ID;
        } else {
            return $this->userId = wp_create_user($this->aDataUser['username'], $this->aDataUser['password']);
        }
    }

    public function testTokenAfterRegisterAccount()
    {
        $userId = $this->createUserDatabase();

        $accessToken = Option::getUserAccessToken($userId);
        var_dump( Option::getUserRefreshToken($userId));
        $aResponse = apply_filters('wiloke-jwt/filter/verify-token',
            [],
            $accessToken);
        $this->assertIsArray($aResponse);
//        $this->assertEquals(200, $aResponse['code']);
//        $this->assertEquals($userId, $aResponse['userID']);
        return $userId;
    }

    /**
     * @depends testTokenAfterRegisterAccount
     */
    public function testSetTime($userId)
    {
        $status = Option::testModeToken();
        $this->assertEquals(true, $status);

        return $userId;
    }

    /**
     * @depends testSetTime
     */
    public function testRenewToken($userId)
    {
        (new GenerateTokenController())->handleTokenAfterUserSignedIn('', get_user_by('ID', $userId));
        sleep(30);
        $accessToken = Option::getUserAccessToken($userId);
        var_dump($accessToken);
        $aResponse = apply_filters('wiloke-jwt/filter/verify-token', [], $accessToken);
        $this->assertEquals('Expired token', $aResponse['msg']);
        $refreshToken = Option::getUserRefreshToken($userId);
        $aResponse = apply_filters('wiloke/filter/renew-access-token', [], $refreshToken,$accessToken);

        var_dump($aResponse);die();

    }
    public function testBlacklistToken(){
        $userId=$this->createRandomUser();
        $accessToken=Option::getUserAccessToken($userId);
        $aResponse = apply_filters('wiloke/filter/set-black-list-access-token', [],$userId,$accessToken);

        $this->assertEquals('success',$aResponse['status']);
        $this->assertEquals(true,(array_search($userId,$aResponse['data'])!==false));
        $aResponse = apply_filters('wiloke/filter/is-black-list-access-token', [],$userId,$accessToken);
        var_dump($aResponse);die();
    }
}