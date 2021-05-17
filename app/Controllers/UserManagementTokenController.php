<?php


namespace WilokeJWT\Controllers;


use WilokeJWT\Helpers\Option;
use WilokeJWT\Helpers\Session;
use WilokeJWT\Illuminate\Message\MessageFactory;

class UserManagementTokenController
{
    public function __construct()
    {
        add_action('show_user_profile', [$this, 'handleRenewAccessToken']);
        add_action('edit_user_profile', [$this, 'handleRenewAccessToken']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueScripts']);
        add_action('wp_ajax_wiloke-enable-seen-access-token-user', [$this, 'handleAjaxSeenAcToken']);
        add_action('wp_ajax_wiloke-enable-seen-refresh-token-user', [$this, 'handleAjaxSeenRfToken']);
        add_action('wp_ajax_wiloke-renew-token-user', [$this, 'handleAjaxRenewToken']);
    }

    public function handleRenewAccessToken()
    {
        $userId = $_GET['user_id'] ?? get_current_user_id();
        $accessToken = Option::getUserAccessToken($userId);
        $refreshToken = Option::getUserRefreshToken($userId);
        ?>
        <div class="application-passwords hide-if-no-js" id="customerDomain-section">
            <h2><?php echo esc_html__('Management Token', 'wiloke-jwt') ?></h2>
            <div class="application-passwords-list-table-wrapper">
                <table class="wp-list-table widefat fixed striped table-view-list application-passwords-user">
                    <thead>
                    <tr>
                        <th scope="col" id="id" class="manage-column column-name column-primary"><?php esc_html_e('ID',
                                'wiloke-jwt'); ?></th>
                        <th scope="col" id="acToken"
                            class="manage-column column-created"><?php esc_html_e('Access Token',
                                'wiloke-jwt'); ?></th>
                        <th scope="col" id="rfToken"
                            class="manage-column column-created"><?php esc_html_e('Refresh Token',
                                'wiloke-jwt'); ?></th>
                        <th scope="col" id="action" class="manage-column column-revoke"
                            style="text-align: center"><?php esc_html_e('Action',
                                'wiloke-jwt'); ?></th>
                    </tr>
                    </thead>

                    <tbody id="the-list">
                    <?php $i = 1;
                    ?>
                    <tr>
                        <td class="name column-name has-row-actions column-primary"
                            data-colname="id"><?php echo $i ?></td>
                        <td class="last_used column-last_used"
                            data-colname="token">
                            <?php echo (isset($_COOKIE['enableAcTokenUser' . $userId]))
                                ? $accessToken :
                                "<input type='submit' value='*****' id='wilokeSeenAcTokenUser' data-UserID="
                                . $userId . ">" ?></td>
                        <td class="last_used column-last_used"
                            data-colname="token">
                            <?php echo (isset($_COOKIE['enableRfTokenUser' . $userId]))
                                ? $refreshToken :
                                "<input type='submit' value='*****' id='wilokeSeenRfTokenUser' data-UserID=" .
                                $userId . ">" ?>
                        </td>
                        <td class="revoke column-revoke" data-colname="action" style="text-align: center">
                                    <span>
                                        <input type="button"
                                               class="button button-primary"
                                               value="<?php echo esc_html_e('Renew Token', 'wiloke-jwt'); ?>"
                                               id="wilokeRenewAcToken"
                                               data-userID="<?php echo esc_attr($userId); ?>">
                                    </span>
                        </td>
                    </tr>
                    </tbody>

                    <tfoot>
                    <tr>
                        <th scope="col" class="manage-column column-name column-primary"><?php esc_html_e('ID',
                                'wiloke-jwt'); ?></th>
                        <th scope="col" class="manage-column column-created"><?php esc_html_e('Access Token',
                                'wiloke-jwt'); ?></th>
                        <th scope="col" class="manage-column column-created"><?php esc_html_e('Refresh Token',
                                'wiloke-jwt'); ?></th>
                        <th scope="col" class="manage-column column-revoke"
                            style="text-align: center"><?php esc_html_e('Acton',
                                'wiloke-jwt'); ?></th>
                    </tr>
                    </tfoot>

                </table>
            </div>
        </div>
        <?php
    }

    public function enqueueScripts($hook)
    {
        if (in_array($hook, ['profile.php', 'user-edit.php'])) {
            wp_enqueue_script('ManagementToken',
                WILOKE_JWT_URL . 'dist/script.js',
                ['jquery'],
                WILOKE_JWT_VERSION,
                true);
            wp_localize_script('jquery', 'WILOKE_JWT', [
                'ajaxurl' => admin_url('admin-ajax.php')
            ]);
        }
    }

    public function handleAjaxSeenAcToken()
    {
        if (current_user_can('administrator') || (get_current_user_id() == ($_GET['user_id'] ?? $_POST['userID']))) {
            if ($_POST['password'] ?? '') {
                $oUser = get_userdata(get_current_user_id());
                if (wp_check_password($_POST['password'], $oUser->data->user_pass, $oUser->ID)) {
                    Session::sessionStart();
                    setcookie('enableAcTokenUser' . $_POST['userID'], true, time() + 36000);
                    return MessageFactory::factory('ajax')->success('The token is enable success', []);
                } else {
                    return MessageFactory::factory('ajax')->error('The password incorrect', 401);
                }
            } else {
                return MessageFactory::factory('ajax')->error('Please enter a password', 401);
            }
        }else{
            return MessageFactory::factory('ajax')
                ->error('The account is not administrator or the current visitor is not a logged in user', 401);
        }
        die();
    }

    public function handleAjaxSeenRfToken()
    {
        if (current_user_can('administrator') || (get_current_user_id() == ($_GET['user_id'] ?? $_POST['userID']))) {
            if ($_POST['password'] ?? '') {
                $oUser = get_userdata(get_current_user_id());
                if (wp_check_password($_POST['password'], $oUser->data->user_pass, $oUser->ID)) {
                    Session::sessionStart();
                    setcookie('enableRfTokenUser' . $_POST['userID'], true, time() + 36000);
                    return MessageFactory::factory('ajax')->success('The token is enable success');
                } else {
                    return MessageFactory::factory('ajax')->error('The password incorrect', 401);
                }
            } else {
                return MessageFactory::factory('ajax')->error('Please enter a password', 401);
            }
        }else{
            return MessageFactory::factory('ajax')
                ->error('The account is not administrator or the current visitor is not a logged in user', 401);
        }
        die();
    }

    public function handleAjaxRenewToken()
    {
        if ($_POST['password'] ?? '') {
            $oUser = get_userdata(get_current_user_id());
            if (wp_check_password($_POST['password'], $oUser->data->user_pass, $oUser->ID)) {
                if ((get_current_user_id() == $_POST['userId']) || current_user_can('administrator')) {
                    $aResponse = apply_filters('wiloke/filter/create-access-token-and-refresh-token',
                        get_userdata($_POST['userId']));
                    if ($aResponse['code'] == 200) {
                        Option::saveUserToken($aResponse['accessToken'], $_POST['userId']);
                        Option::saveUserRefreshToken($aResponse['refreshToken'], $_POST['userId']);
                        return MessageFactory::factory('ajax')->success('The token is renew success');
                    }
                } else {
                    return MessageFactory::factory('ajax')
                        ->error('The account is not administrator or the current visitor is not a logged in user', 401);
                }

            } else {
                return MessageFactory::factory('ajax')->error('The password incorrect', 401);
            }
        } else {
            return MessageFactory::factory('ajax')->error('Please enter a password', 401);
        }
        die();
    }
}