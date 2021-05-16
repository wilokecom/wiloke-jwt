<?php


namespace WilokeJWT\Controllers;


use WilokeJWT\Helpers\Option;

class UserManagementTokenController
{
    public function __construct()
    {
        add_action('show_user_profile', [$this, 'handleRenewAccessToken']);
        add_action('edit_user_profile', [$this, 'handleRenewAccessToken']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueScripts']);
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
                                               value="<?php echo esc_html_e('Renew Token', 'wiloke-jwt'); ?>"
                                               id="wilokeRenewAcToken"
                                               data-userID="<?php echo esc_attr($userId); ?>"
                                               style="font-size:20px">
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

    public function enqueueScripts()
    {
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