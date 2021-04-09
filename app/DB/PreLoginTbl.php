<?php


namespace WilokeJWT\DB;


class PreLoginTbl
{
	public static string $tblName = 'wiloke_jwt_pre_login';
	public static string $version = '1.0';

	public function __construct()
	{
		$this->createTable();
	}

	public static function getTable(): string
	{
		global $wpdb;
		return $wpdb->prefix . self::$tblName;
	}

	public function createTable()
	{
		global $wpdb;
		$tblName = $wpdb->prefix . self::$tblName;
		$charsetCollate = $wpdb->get_charset_collate();
		$sql = "CREATE TABLE IF NOT EXISTS $tblName (
          ID bigint(20) NOT NULL AUTO_INCREMENT,
          code varchar (100) NOT NULL,
          created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          ip_address varchar(150) NOT NULL,
          client_session varchar(150) NOT NULL,
          PRIMARY KEY (ID)
        ) $charsetCollate";
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta($sql);

		update_option(self::$tblName, self::$version);
	}
}
