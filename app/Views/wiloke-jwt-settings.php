<table class="form-table">
    <tbody>
        <tr class="wiloke-jwt-expiration-time">
            <th><label for="wiloke-jwt-yourtoken">Your Access Token</label></th>
            <td><textarea cols="60" rows="10"
                          id="wiloke-jwt-yourtoken"><?php echo \WilokeJWT\Helpers\Option::getUserToken(); ?></textarea></td>
        </tr>
        
        <tr class="wiloke-jwt-expiration-time">
            <th><label for="wiloke-jwt-yourrefreshtoken">Your Refresh Token</label></th>
            <td><textarea cols="60" rows="10"
                          id="wiloke-jwt-yourrefreshtoken"><?php echo \WilokeJWT\Helpers\Option::getUserRefreshToken(); ?></textarea>
            </td>
        </tr>
    </tbody>
</table>
<hr />
<?php
$actionURL =
    is_network_admin() ? network_admin_url('admin.php?page='.$this->slug) : admin_url('admin.php?page='.$this->slug);
?>
<form method="POST" action="<?php echo esc_url($actionURL); ?>">
    <?php wp_nonce_field('wiloke-jwt-action', 'wiloke-jwt-field'); ?>
    <table class="form-table">
        <thead>
            <tr>
                <th>Wiloke JWT Settings</th>
            </tr>
        </thead>
        <tbody>
        <tr class="wiloke-jwt-expiration-time">
            <th><label for="wiloke-jwt-expiration">Token Expiry (Hour)</label></th>
            <td>
                <input type="text" name="wilokejwt[token_expiry]" id="wiloke-jwt-expiration" value="<?php echo
                esc_attr($this->aOptions['token_expiry']); ?>" class="regular-text"/>
                <p><?php esc_html_e('Set a long expiration time for Access Token. A token will be added to blacklist after X hours. We highly recommend allowing maximum 10 hours.',
                        'wiloke-jwt'); ?></p>
            </td>
        </tr>
        <tr class="wiloke-jwt-key">
            <th><label for="wiloke-jwt-key">My Key</label></th>
            <td><input type="text" name="wilokejwt[key]" id="wiloke-jwt-key" value="<?php echo
                esc_attr($this->aOptions['key']); ?>" class="regular-text">
                <p><?php esc_html_e('Set a secret key for token. If your website has been attacked, You can change the key to protect your customers account. Once they key has been changed, all Token that created before will be added to blacklist.',
                        'wiloke-jwt'); ?></p>
            </td>
        </tr>
        <tr class="wiloke-jwt-is-test-mode">
            <th><label for="wiloke-jwt-is-test-mode">Is Test Mode</label></th>
            <td>
                <select type="text" name="wilokejwt[is_test_mode]" id="wiloke-jwt-is-test-mode">
                    <option value="no" <?php selected('no', $this->aOptions['is_test_mode']); ?>>No</option>
                    <option value="yes" <?php selected('yes', $this->aOptions['is_test_mode']); ?>>Yes</option>
                </select>
                <p><?php esc_html_e('If Test Mode is enabling, the Test Token Expired will be used instead of Token Expired.',
                        'wiloke-jwt'); ?></p>
            </td>
        </tr>
        <tr class="wiloke-jwt-token-expired">
            <th><label for="wiloke-jwt-token-expired">Test Token Expired (Second)</label></th>
            <td><input type="text" name="wilokejwt[test_token_expired]" id="wiloke-jwt-token-expired" value="<?php echo
                esc_attr($this->aOptions['test_token_expired']); ?>" class="regular-text"></td>
        </tr>
        </tbody>
    </table>
    <button class="button button-primary" type="submit">Save Changes</button>
</form>
