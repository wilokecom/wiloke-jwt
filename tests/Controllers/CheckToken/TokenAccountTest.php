<?php

namespace WilokeJWTTest\Controllers\CheckToken;

use WilokeJWT\Controllers\GenerateTokenController;
use WilokeJWT\Core\Core;
use WilokeJWT\Helpers\Option;
use WilokeJWTTest\CommonController;

class TokenAccountTest extends CommonController
{
    //Bật Test Mode 10 s
    public array $aDataUser
        = [
            'username' => 'test',
            'password' => 'test'
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

    public function testTokenAfterRegisterAccount()//Test Khi Tài Khoản Đăng ký Thành Công Có Tạo Ra Token Đúng Không
    {
        $userId = $this->createUserDatabase();
        $accessToken = Option::getUserAccessToken($userId);
        $aResponse = apply_filters('wiloke-jwt/filter/verify-token',
            [],
            $accessToken);
        $this->assertIsArray($aResponse);
        $this->assertEquals(200, $aResponse['code']);
        $this->assertEquals($userId, $aResponse['userID']);
        return $userId;
    }
    /**
     * @depends testTokenAfterRegisterAccount
     */
    public function testRenewToken($userId)
    {
        sleep(15);
        $accessToken = Option::getUserAccessToken($userId);
        $aResponse = apply_filters('wiloke-jwt/filter/verify-token', [], $accessToken);
        $this->assertEquals('Expired token', $aResponse['msg']);
        $refreshToken = Option::getUserRefreshToken($userId);
        $aResponse = apply_filters('wiloke/filter/renew-access-token', [], $refreshToken,'');
        $this->assertEquals(200, $aResponse['code']);
        $this->assertEquals($userId, $aResponse['userID']);

    }
    public function testBlacklistToken()//Kiểm Tra Khi Cho 1 User Vào Danh Sách Đen Có Đúng Hay K
    {
        $userId=$this->createUserDatabase();
        $accessToken=Option::getUserAccessToken($userId);
        $aResponse = apply_filters('wiloke/filter/set-black-list-access-token', [],$userId,$accessToken);
        $this->assertEquals('success',$aResponse['status']);
        $this->assertEquals(true,(array_search($accessToken,$aResponse['data'])!==false));
        $aResponse = apply_filters('wiloke/filter/is-black-list-access-token', [],$userId,$accessToken);
        $this->assertEquals('success',$aResponse['status']);
    }
}