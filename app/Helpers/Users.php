<?php


namespace WilokeJWT\Helpers;


class Users {
	public static function generateUsername( $email ): string {
		global $wpdb;
		$username = '';
		if ( ! validate_username( $username ) ) {
			$username = '';
			// use email
			$aEmail = explode( '@', $email );
			if ( validate_username( $aEmail[0] ) ) {
				$username = self::cleanUsername( $aEmail[0] );
			}
		}
		// User name can't be on the blacklist or empty
		$illegal_names = get_site_option( 'illegal_names' );
		if ( empty( $username ) || in_array( $username, (array) $illegal_names ) ) {
			// we used all our options to generate a nice username. Use id instead
			$username = 'fbl_' . uniqid( 'wiloke_' );
		}
		// "generate" unique suffix
		$suffix = $wpdb->get_var( $wpdb->prepare(
			"SELECT 1 + SUBSTR(user_login, %d) FROM $wpdb->users WHERE user_login REGEXP %s ORDER BY 1 DESC LIMIT 1",
			strlen( $username ) + 2, '^' . $username . '(-[0-9]+)?$' ) );
		if ( ! empty( $suffix ) ) {
			$username .= "-{$suffix}";
		}

		return $username;
	}

	/**
	 * Simple pass sanitazing functions to a given string
	 *
	 * @param $username
	 *
	 * @return string
	 */
	private function cleanUsername( $username ): string {
		return sanitize_title( str_replace( '_', '-', sanitize_user( $username ) ) );
	}
}
