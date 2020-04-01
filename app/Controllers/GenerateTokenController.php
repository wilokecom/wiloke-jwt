<?php

namespace WilokeJWT\Controllers;

use Exception;
use Firebase\JWT\JWT;
use HSBlogCore\Helpers\Session;
use WilokeJWT\Core\Core;
use WilokeJWT\Helpers\Option;
use WP_User;

/**
 * Class GenerateTokenController
 * @package WilokeJWT\Controllers
 */
final class GenerateTokenController extends Core
{
    private $handlingUserLogin = false;

    /**
     * GenerateTokenController constructor.
     */
    public function __construct()
    {
        add_action('set_logged_in_cookie', [$this, 'handleTokenAfterUserSetLoggedInCookie'], 10, 4);
        add_action('wp_login', [$this, 'handleTokenAfterUserSignedIn'], 10, 2);
        add_action('admin_init', [$this, 'fixGenerateTokenIfUserLoggedIntoSiteBeforeInstallingMe']);
        add_action('wiloke-jwt/created-access-token', [$this, 'storeAccessTokenToCookie'], 10, 3);
        add_action('clear_auth_cookie', [$this, 'revokeAccessToken']);
        add_action('user_register', [$this, 'createRefreshTokenAfterUserRegisteredAccount']);
        add_filter('wiloke/filter/get-refresh-token', [$this, 'getUserRefreshToken']);
        add_filter('wiloke/filter/revoke-access-token', [$this, 'filterRevokeAccessToken'], 10, 2);
        add_filter('wiloke/filter/revoke-refresh-access-token', [$this, 'filterRevokeRefreshAccessToken'], 10, 2);
        add_filter('wiloke/filter/renew-access-token', [$this, 'filterRenewAccessToken'], 10, 2);
        add_filter('wiloke/filter/is-access-token-expired', [$this, 'filterIsTokenExpired'], 10, 2);
        add_action('clean_user_cache', [$this, 'maybeRevokeRefreshPasswordAfterUpdatingUser'], 10, 2);
        add_action('delete_user', [$this, 'deleteTokensBeforeDeletingUser'], 10);
    }

    public function filterIsTokenExpired($status, $accessToken)
    {
        return $this->isAccessTokenExpired($accessToken);
    }

    /**
     * @param $status
     * @param $userId
     * @return bool
     */
    public function filterRevokeAccessToken($status, $userId)
    {
        return $this->revokeAccessToken($userId);
    }

    /**
     * @param $userID
     */
    public function deleteTokensBeforeDeletingUser($userID)
    {
        $this->revokeRefreshAccessToken($userID);
    }

    /**
     * @param $userID
     * @param WP_User $oUser
     * @return array|bool
     */
    public function maybeRevokeRefreshPasswordAfterUpdatingUser($userID, WP_User $oUser)
    {
        $token = Option::getRefreshUserToken($oUser->ID);
        if (!empty($token)) {
            return $this->filterRevokeRefreshAccessToken(null, $token);
        }
    }

    /**
     * @param $status
     * @param $token
     * @return array|bool
     */
    public function filterRevokeRefreshAccessToken($status, $token)
    {
        try {
            $oUserInfo = $this->verifyToken($token, 'refresh_token');
            $this->revokeRefreshAccessToken($oUserInfo->userID);
            $oUser = new WP_User($oUserInfo->userID);
            $refreshToken = $this->generateRefreshToken($oUser);
            $accessToken = $this->generateToken($oUser);

            return [
                'data' => [
                    'refreshToken' => $refreshToken,
                    'accessToken' => $accessToken
                ]
            ];
        } catch (Exception $exception) {
            return [
                'error' => [
                    'message' => $exception->getMessage(),
                    'code' => 401
                ]
            ];
        }
    }

    /**
     * @param $token
     * @param $ignoreSetCookie
     *
     * @return bool
     */
    public function storeAccessTokenToCookie($token, $ignoreSetCookie = false)
    {
        if ($ignoreSetCookie) {
            return false;
        }

        $host = parse_url(home_url('/'), PHP_URL_HOST);

        setcookie(
            'wiloke_my_jwt',
            $token,
            $this->getTokenExpired(),
            '/',
            $host,
            is_ssl()
        );
    }

    public function fixGenerateTokenIfUserLoggedIntoSiteBeforeInstallingMe()
    {
        if (current_user_can('administrator')) {
            if (empty(Option::getUserToken())) {
                $oUser = new WP_User(get_current_user_id());
                $this->generateToken($oUser);
            }
        }
    }

    public function getUserRefreshToken($userId = '')
    {
        $userId = !empty($userId) ? $userId : get_current_user_id();
        return Option::getRefreshUserToken($userId);
    }

    public function createRefreshTokenAfterUserRegisteredAccount($userId)
    {
        if (empty($userId)) {
            return false;
        }

        $oUser = new WP_User($userId);

        if (empty($oUser) || is_wp_error($oUser)) {
            return $oUser;
        }

        $refreshToken = $this->generateRefreshToken($oUser);
        if (!empty($refreshToken)) {
            $this->renewAccessToken($refreshToken);
            do_action('wiloke-jwt/created-refresh-token', $refreshToken, $userId, $oUser);
        }
    }

    public function handleTokenAfterUserSetLoggedInCookie($logged_in_cookie, $expire, $expiration, $user_id)
    {
        if ($this->handlingUserLogin) {
            return false;
        }

        $oUser = new WP_User($user_id);
        $this->handleTokenAfterUserSignedIn($oUser->user_email, $oUser);
    }

    /**
     * @param $userEmail
     * @param $oUser
     * @return bool
     */
    public function handleTokenAfterUserSignedIn($userEmail, $oUser)
    {
        if ($this->handlingUserLogin) {
            return false;
        }
        $this->handlingUserLogin = true;
        $refreshToken = Option::getRefreshUserToken($oUser->ID);
        $accessToken = '';

        if (empty($refreshToken)) {
            $this->generateRefreshToken($oUser);
        } else {
            try {
                $this->verifyToken($refreshToken, 'refresh_token');
            } catch (Exception $exception) {
                $this->generateRefreshToken($oUser);
            }

            $accessToken = Option::getUserToken($oUser->ID);
        }

        try {
            $this->verifyToken($accessToken);
            return true;
        } catch (Exception $exception) {
            $refreshToken = Option::getRefreshUserToken($oUser->ID);

            if (!empty($refreshToken)) {
                try {
                    $accessToken = $this->renewAccessToken($refreshToken);
                    $this->storeAccessTokenToCookie($accessToken);
                } catch (Exception $exception) {
                    return false;
                }
            }
            return false;
        }
    }

    /**
     * @param $userID
     * @return mixed|string
     * @throws Exception
     */
    public function generateTokenAfterCreatingAccount($userID)
    {
        $oUser = new WP_User($userID);

        return $this->generateToken($oUser, true);
    }

    /**
     * @param $aStatus
     * @param $refreshToken
     * @return array
     */
    public function filterRenewAccessToken($aStatus, $refreshToken)
    {
        try {
            $oUserInfo = $this->verifyToken($refreshToken, 'refresh_token');
            $accessToken = Option::getUserToken($oUserInfo->userID);

            if ($this->isAccessTokenExpired($accessToken)) {
                return [
                    'accessToken' => $this->renewAccessToken($refreshToken)
                ];
            } else {
                return [
                    'error' => [
                        'message' => esc_html__('The renew token is freezed', 'hsblog-core'),
                        'code' => 403
                    ]
                ];
            }
        } catch (Exception $e) {
            return [
                'error' => [
                    'message' => $e->getMessage(),
                    'code' => 401
                ]
            ];
        }
    }
}
