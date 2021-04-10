<?php


namespace WilokeJWT\Models;


use WilokeJWT\DB\PreLoginTbl;

class PreLoginModel
{
    public static function isMatchedCode($code, $clientIp): bool
    {
        global $wpdb;

        return (bool)$wpdb->get_var(
            $wpdb->prepare(
                "SELECT ID FROM " . PreLoginTbl::getTable() .
                " WHERE code=%s AND ip_address=%s AND DATE(created_at) >= (CURDATE() - INTERVAL 5 MINUTE)",
                $code, $clientIp
            )
        );
    }

    public static function createCode($clientSession, $ipAddress): int
    {
        global $wpdb;

        $wpdb->insert(
            PreLoginTbl::getTable(),
            [
                'code'           => md5(uniqid('code')),
                'client_session' => $clientSession,
                'ip_address'     => $ipAddress
            ],
            [
                '%s',
                '%s',
                '%s'
            ]
        );

        return (int)$wpdb->insert_id;
    }

    public static function getCode(int $ID): string
    {
        return self::getField('code', $ID);
    }

    public static function getField($field, int $ID)
    {
        global $wpdb;

        $field = $wpdb->_real_escape($field);
        return $wpdb->get_var(
            $wpdb->prepare(
                "SELECT $field FROM " . PreLoginTbl::getTable() . " WHERE ID=%d",
                $ID
            )
        );
    }

    public static function deleteByCode($code): bool
    {
        global $wpdb;
        return (bool)$wpdb->delete(
            PreLoginTbl::getTable(),
            [
                'code' => $code
            ],
            [
                '%s'
            ]
        );
    }

    public static function delete(int $id): bool
    {
        global $wpdb;
        return (bool)$wpdb->delete(
            PreLoginTbl::getTable(),
            [
                'ID' => $id
            ],
            [
                '%d'
            ]
        );
    }

    public static function isAppIdExist(string $appId): bool
    {
        global $wpdb;
        $metaId = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT meta_id FROM $wpdb->postmeta  WHERE meta_value=%s",
                $appId
            )
        );
        return !empty($metaId);
    }

    public static function isAppSecretExist(string $appSecret): bool
    {
        global $wpdb;
        $meta_id = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT meta_id FROM $wpdb->postmeta  WHERE meta_value=%s",
                $appSecret
            )
        );
        return !empty($meta_id);
    }

    public static function getPostIDByAppId(string $appId): int
    {
        global $wpdb;
        $post_id = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT post_id FROM $wpdb->postmeta  WHERE meta_value=%s",
                $appId
            )
        );
        return $post_id ?? 0;
    }
}
