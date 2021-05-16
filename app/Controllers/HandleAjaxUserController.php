<?php


namespace WilokeJWT\Controllers;


use WilokeJWT\Helpers\Option;
use WilokeJWT\Helpers\Session;

class HandleAjaxUserController
{
    public function __construct()
    {
        add_action('wp_ajax_wiloke-enable-seen-access-token-user', [$this, 'handleAjaxSeenAcToken']);
        add_action('wp_ajax_wiloke-enable-seen-refresh-token-user', [$this, 'handleAjaxSeenRfToken']);
        add_action('wp_ajax_wiloke-renew-token-user', [$this, 'handleAjaxRenewToken']);
    }

    public function handleAjaxSeenAcToken()
    {
        if ($_POST['Password'] ?? '') {
            $oUser = get_userdata(get_current_user_id());
            if (wp_check_password($_POST['Password'], $oUser->data->user_pass, $oUser->ID)) {
                Session::sessionStart();
                setcookie('enableAcTokenUser' . $_POST['userID'], true, time() + 36000);
                $aResponse = [
                    'status' => 'success',
                    'msg'    => esc_html__('enable token success', 'wiloke-jwt')
                ];
            } else {
                $aResponse = [
                    'status' => 'error',
                    'msg'    => esc_html__('The password incorrect', 'wiloke-jwt')
                ];
            }
        } else {
            $aResponse = [
                'status' => 'error',
                'msg'    => esc_html__('Please enter a password', 'wiloke-jwt')
            ];
        }
        echo json_encode($aResponse);
        die();
    }

    public function handleAjaxSeenRfToken()
    {
        if ($_POST['Password'] ?? '') {
            $oUser = get_userdata(get_current_user_id());
            if (wp_check_password($_POST['Password'], $oUser->data->user_pass, $oUser->ID)) {
                Session::sessionStart();
                setcookie('enableRfTokenUser' . $_POST['userID'], true, time() + 36000);
                $aResponse = [
                    'status' => 'success',
                    'msg'    => esc_html__('enable token success', 'wiloke-jwt')
                ];
            } else {
                $aResponse = [
                    'status' => 'error',
                    'msg'    => esc_html__('The password incorrect', 'wiloke-jwt')
                ];
            }
        } else {
            $aResponse = [
                'status' => 'error',
                'msg'    => esc_html__('Please enter a password', 'wiloke-jwt')
            ];
        }
        echo json_encode($aResponse);
        die();
    }

    public function handleAjaxRenewToken()
    {
        if ($_POST['Password'] ?? '') {
            $oUser = get_userdata(get_current_user_id());
            if (wp_check_password($_POST['Password'], $oUser->data->user_pass, $oUser->ID)) {
                if ((get_current_user_id() == $_POST['userId']) || in_array('administrator', (array)$oUser->roles)) {
                    $aResponse = apply_filters('wiloke/filter/create-access-token-and-refresh-token',
                        get_userdata($_POST['userId']));
                    if ($aResponse['code'] == 200) {
                        Option::saveUserToken($aResponse['accessToken'], $_POST['userId']);
                        Option::saveUserRefreshToken($aResponse['refreshToken'], $_POST['userId']);
                        $aResponse = [
                            'status' => 'success',
                            'msg'    => esc_html__('renew access token success', 'wiloke-jwt')
                        ];
                    }
                } else {
                    $aResponse = [
                        'status' => 'error',
                        'msg'    => esc_html__('The account is not administrator or the current visitor is not a logged in user',
                            'wiloke-jwt')
                    ];
                }

            } else {
                $aResponse = [
                    'status' => 'error',
                    'msg'    => esc_html__('The password incorrect', 'wiloke-jwt')
                ];
            }
        } else {
            $aResponse = [
                'status' => 'error',
                'msg'    => esc_html__('Please enter a password', 'wiloke-jwt')
            ];
        }
        echo json_encode($aResponse);
        die();
    }
}