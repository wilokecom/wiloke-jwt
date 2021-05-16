<?php

namespace WilokeJWTTest\Controllers\CheckToken;

use WilokeJWT\Controllers\GenerateTokenController;
use WilokeJWT\Core\Core;
use WilokeJWT\Helpers\Option;
use WilokeJWTTest\CommonController;

class TokenAccountTest extends CommonController {
	public array $aDataUser
		= [
			'username' => 'test123456789',
			'password' => 'test123456'
		];

	public static int $testUserId = 0;

	/**
	 * Neu la tai khoan tao moi, bien nhan gia tri false
	 * @var bool
	 */
	public static bool $isTestAccountExistedBefore = false;

	public function createUserDatabase()//tạo user
	{
		if ( username_exists( $this->aDataUser['username'] ) ) {
			$oUser = get_user_by( 'login', $this->aDataUser['username'] );
			wp_set_password( $this->aDataUser['password'], $oUser->ID );
			wp_signon( $this->aDataUser['login'], $this->aDataUser['password'] );
			self::$isTestAccountExistedBefore = true;

			return $oUser->ID;
		} else {
			return self::$testUserId = wp_create_user( $this->aDataUser['username'], $this->aDataUser['password'] );
		}
	}

	public function createNewAccount() {
		return self::$testUserId = wp_create_user( uniqid( $this->aDataUser['username'] ),
			$this->aDataUser['password'] );
	}

	private function deleteUser( $userId ) {
		require_once( ABSPATH . 'wp-admin/includes/user.php' );
		wp_delete_user( $userId );
	}

	public function testTokenAfterRegisterAccount()//Test Khi Tài Khoản Đăng ký Thành Công Có Tạo Ra Token Đúng Không
	{
		$userId      = $this->createNewAccount();
		$accessToken = Option::getUserAccessToken( $userId );
		$aResponse   = apply_filters( 'wiloke-jwt/filter/verify-token',
			[],
			$accessToken
		);
		$this->assertIsArray( $aResponse );
		$this->assertEquals( 200, $aResponse['code'] );
		$this->assertEquals( $userId, $aResponse['userID'] );
		$this->deleteUser( $userId );

		return $userId;
	}

	/**
	 * @depends testTokenAfterRegisterAccount
	 */
	public function testRenewToken()// kiem tra lấy lại accessToken hết hạn,có lấy lại dc k
	{
		/**** Bật chế độ Test Mode *****/
		$aOptionSettings                       = Option::getJWTSettings( true );
		$aOptionSettings['is_test_mode']       = 'yes';
		$aOptionSettings['test_token_expired'] = 5;
		Option::saveJWTSettings( $aOptionSettings );

		/**** Login vào tài khoản ****/
		$userId       = $this->createUserDatabase();
		$accessToken  = Option::getUserAccessToken( $userId );
		$refreshToken = Option::getUserRefreshToken( $userId );

		/**** Kiem tra Token co dung hay khong. ****/
		$aResponse = apply_filters( 'wiloke-jwt/filter/verify-token',
			[],
			$accessToken
		);
		$this->assertEquals( 200, $aResponse['code'] );// kiem tra đúng hết hạn chưa

		/**** Thu refresh token, no KHONG NEN duoc refresh. No nen bi đóng băng ****/
		$aResponse = apply_filters( 'wiloke/filter/renew-access-token', [], $refreshToken, '' );
		$this->assertEquals( 403, $aResponse['code'] );

		/**** Tam dung 7 giay ****/
		sleep( 7 );

		/**** Token phai het han sau do. ****/
		$aResponse = apply_filters( 'wiloke-jwt/filter/verify-token', [], $accessToken );
		$this->assertEquals( 401, $aResponse['code'] );// kiem tra đúng hết hạn chưa

		/**** Refresh Token ****/
		$aResponse = apply_filters( 'wiloke/filter/renew-access-token', [], $refreshToken, '' );//lấy lại accessToken
		$this->assertEquals( 200, $aResponse['code'] );
		$this->assertEquals( $userId, $aResponse['userID'] );

		return $aResponse['userID'];
	}

	/**
	 * @depends testRenewToken
	 */
	public function testBlacklistToken( $userId ) {
		/*** Xet token vao black list ***/
		$accessToken = Option::getUserAccessToken( $userId );
		$aResponse   = apply_filters( 'wiloke/filter/set-black-list-access-token', [], $userId, $accessToken );//
		$this->assertEquals( 'success', $aResponse['status'] );

		/*** Kiem tra xem token co trong black list hay khong ***/
		$aResponse = apply_filters( 'wiloke/filter/is-black-list-access-token', [], $userId, $accessToken );
		$this->assertEquals( 'success', $aResponse['status'] );

		/*** Mot lan nua kiem tra lai token, no nen la sai ***/
		$aResponse = apply_filters( 'wiloke-jwt/filter/verify-token',
			[],
			$accessToken
		);

		$this->assertEquals( 401, $aResponse['code'] );
	}

	/**
	 * @depends testBlacklistToken
	 */
	public function testAccountAfterChangingSignature() {
		$userId       = $this->createUserDatabase();
		$refreshToken = Option::getUserRefreshToken( $userId );

		/**** Renew Token *****/
		$aResponse = apply_filters( 'wiloke/filter/renew-access-token', [], $refreshToken, '' );
		$this->assertEquals( 200, $aResponse['code'] );
		$this->assertEquals( $userId, $aResponse['userID'] );

		/**** Thay doi chu ky *****/
		$aOptionSettings        = Option::getJWTSettings( true );
		$aOptionSettings['key'] = uniqid( $aOptionSettings['key'] );
		Option::saveJWTSettings( $aOptionSettings );


		/**** Token va refresh token ne bi sai *****/
		$accessToken = Option::getUserAccessToken( $userId );
		$aResponse   = apply_filters( 'wiloke-jwt/filter/verify-token',
			[],
			$accessToken
		);
		$this->assertEquals( 401, $aResponse['code'] );
		$this->assertEquals( true, strpos( $aResponse['msg'], 'Signature' ) !== false );

		/*** Xoa tai khoan test neu tao moi ***/
		if ( self::$isTestAccountExistedBefore ) {
			$this->deleteUser( $userId );
		} else {
			apply_filters( 'wiloke/filter/renew-access-token', [], $refreshToken, '' );
		}
	}
}
