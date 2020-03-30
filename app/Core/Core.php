<?php


namespace WilokeJWT\Core;


use Exception;
use Firebase\JWT\JWT;
use WilokeJWT\Helpers\Option;
use WilokeJWT\Helpers\Session;
use WP_User;

class Core
{
    protected function getTokenExpired()
    {
        return Option::getAccessTokenExp();
    }

    /**
     * @param $userId
     * @return bool
     */
    protected function revokeAccessToken($userId)
    {
        $host = parse_url(home_url('/'), PHP_URL_HOST);
        setcookie(
            'wiloke_my_jwt',
            '',
            current_time('timestamp') - 10000000,
            '/',
            $host,
            is_ssl()
        );

        return Option::revokeAccessToken($userId);
    }

    /**
     * @param $userId
     * @return bool
     */
    protected function revokeRefreshAccessToken($userId)
    {
        self::revokeAccessToken($userId);
        return Option::revokeRefreshToken($userId);
    }

    /**
     * @param $token
     * @param string $type
     * @return mixed
     * @throws Exception
     */
    protected function verifyToken($token, $type = 'access_token')
    {
        $errMsg = '';
        $oUser = (object)[];
        try {
            if ($type === 'refresh_access_token') {
                $key = Option::getRefreshTokenKey();
            } else {
                $key = Option::getAccessTokenKey();
            }

            $oParse = JWT::decode($token, $key, ['HS256']);
            $oUser = json_decode($oParse->message);

            if (!isset($oUser->userID) || empty($oUser->userID)) {
                $errMsg = esc_html__('The user has been removed or does not exist', 'wiloke-jwt');
            } else {
                if ($type === 'refresh_access_token') {
                    $currentToken = Option::getRefreshUserToken($oUser->userID);
                } else {
                    $currentToken = Option::getUserToken($oUser->userID);
                }

                if ($token !== $currentToken) {
                    $errMsg = esc_html__('The token has been expired', 'wiloke-jwt');
                }
            }
        } catch (Exception $oE) {
            $errMsg = $oE->getMessage();
        }

        if (!empty($errMsg)) {
            throw new Exception($errMsg);
        }

        return $oUser;
    }

    /**
     * @param $refreshToken
     * @return string
     * @throws Exception
     */
    protected function renewAccessToken($refreshToken)
    {
        $oInfo = $this->verifyToken($refreshToken, 'refresh_access_token');
        $oUser = new WP_User($oInfo->userID);

        if (empty($oUser) || is_wp_error($oUser)) {
            throw new Exception(esc_html__('The user has been removed', 'wiloke-jwt'));
        }

        $this->revokeAccessToken($oUser->ID);
        return $this->generateToken($oUser->ID, $oUser);
    }

    /**
     * @param $oUser
     * @return false|string
     */
    private function generateTokenMsg($oUser)
    {
        return json_encode(
            [
                'userID' => $oUser->ID,
                'userName' => $oUser->user_login,
                'userEmail' => $oUser->user_email
            ]
        );
    }

    /**
     * @param WP_User $oUser
     * @param bool $ignoreSetCookie
     * @return mixed|string
     */
    protected function generateToken(WP_User $oUser, $ignoreSetCookie = false)
    {
        $token = Option::getUserToken($oUser->ID);

        if (!empty($token)) {
            try {
                $oUserInfo = $this->verifyToken($token);
            } catch (Exception $exception) {

            }
        }

        if (!isset($oUserInfo)) {
            $aPayload = [
                'message' => $this->generateTokenMsg($oUser),
                'exp' => $this->getTokenExpired()
            ];

            $token = JWT::encode($aPayload, Option::getAccessTokenKey());
            Option::saveUserToken($token, $oUser->ID);

            do_action('wiloke-jwt/created-access-token', $token, Option::getAccessTokenExp(), $ignoreSetCookie);
        } else {
            do_action('wiloke-jwt/created-access-token', $token, Option::getAccessTokenExp(), $ignoreSetCookie);
        }

        return $token;
    }


    /**
     * @param WP_User $oUser
     * @return string
     */
    protected function generateRefreshToken(WP_User $oUser)
    {
        $aPayload = [
            'message' => $this->generateTokenMsg($oUser),
            'exp' => Option::getRefreshAccessTokenExp()
        ];

        $encoded = JWT::encode($aPayload, Option::getRefreshTokenKey());
        Option::saveUserRefreshToken($encoded, $oUser->ID);

        return $encoded;
    }

    protected function isAccessTokenExpired($accessToken)
    {
        try {
            $oParse = JWT::decode($accessToken, Option::getAccessTokenKey(), ['HS256']);
            $oUserInfo = json_decode($oParse->message);

            $userAccessToken = Option::getUserToken($oUserInfo->userID);

            return $userAccessToken !== $accessToken;
        } catch (Exception $exception) {
            return false;
        }
    }
}