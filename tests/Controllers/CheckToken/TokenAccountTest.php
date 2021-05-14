<?php

namespace WilokeJWTTest\Controllers\CheckToken;
use WilokeJWT\Controllers\GenerateTokenController;
use WilokeJWT\Helpers\Option;
use WilokeJWTTest\CommonController;

class TokenAccountTest extends CommonController
{
    public array $aDataUser =[
        'username'=>'test',
        'password'=>'test'
    ];
    public function createUserDatabase() {
        if ( username_exists( $this->aDataUser['username'] ) ) {
            return get_user_by( 'login', $this->aDataUser['username'] )->ID;
        } else {
            return $this->userId = wp_create_user( $this->aDataUser['username'], $this->aDataUser['password'] );
        }
    }
    public function testTokenAfterRegisterAccount(){
        $userId=$this->createUserDatabase();
       // var_dump($userId);die();
//        $accessToken=Option::getUserAccessToken($userId);
//        var_dump($userId);
        $aResponse=(new GenerateTokenController())->createRefreshTokenAfterUserRegisteredAccount($userId);
       var_dump($aResponse);die();
    }
}