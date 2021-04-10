<?php


namespace WilokeJWT\Models;


class AppClientModel
{
    public static function getClientAppPostType(): string
    {
        $aConfigs = include WILOKE_JWT_PATH . 'configs/client-apps/post-type.php';

        return $aConfigs['post_type'];
    }

    public static function isValidApp(string $appId, string $appSecret): bool
    {
        return !empty(self::getPostIdByAppInfo($appId, $appSecret));
    }

    public static function getPostIdByAppInfo(string $appId, string $appSecret): int
    {
        global $wpdb;
        $select = "SELECT SQL_CALC_FOUND_ROWS wp_posts.ID FROM wp_posts";
        $join = "INNER JOIN wp_postmeta ON ( wp_posts.ID = wp_postmeta.post_id ) ";
        $join .= "INNER JOIN wp_postmeta AS mt1 ON ( wp_posts.ID = mt1.post_id )";
        $where = $wpdb->prepare("(wp_postmeta.meta_key = 'app_id' AND wp_postmeta.meta_value = %s)", $appId);
        $where .= " AND " . $wpdb->prepare("(mt1.meta_key = 'app_secret' AND mt1.meta_value = %s)", $appSecret);
        $where .= " AND wp_posts.post_status = 'publish'";
        $orderby = " ORDER BY wp_posts.ID DESC LIMIT 1";
        return (int)$wpdb->get_var(
            $select . " " . $join . " WHERE " . $where . $orderby
        );
    }
}
