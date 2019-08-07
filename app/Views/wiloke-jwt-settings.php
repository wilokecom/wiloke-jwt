<table class="form-table">
    <tbody>
        <tr class="wiloke-jwt-expiration-time">
            <th><label for="wiloke-jwt-yourtoken">Your token</label></th>
            <td><code><?php echo \WilokeJWT\Helpers\Option::getUserToken(); ?></code></td>
        </tr>
    </tbody>
</table>

<form method="POST" action="<?php echo esc_url(admin_url('admin.php?page='.$this->slug)); ?>">
    <?php wp_nonce_field('wiloke-jwt-action', 'wiloke-jwt-field'); ?>
    <table class="form-table">
        <tbody>
        <tr class="wiloke-jwt-expiration-time">
            <th><label for="wiloke-jwt-expiration">Token Expiry (Day)</label></th>
            <td><input type="text" name="wilokejwt[token_expiry]" id="wiloke-jwt-expiration" value="<?php echo
                esc_attr($this->aOptions['token_expiry']); ?>" class="regular-text"></td>
        </tr>

        <tr class="wiloke-jwt-key">
            <th><label for="wiloke-jwt-key">My Key</label></th>
            <td><input type="text" name="wilokejwt[key]" id="wiloke-jwt-key" value="<?php echo
                esc_attr($this->aOptions['key']); ?>" class="regular-text"></td>
        </tr>
        </tbody>
    </table>
    <button class="button button-primary" type="submit">Save Changes</button>
</form>
