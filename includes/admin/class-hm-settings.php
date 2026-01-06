<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class HM_MC_Settings {

    const OPTION_RESTRICTED_USERS = 'hm_mc_restricted_users'; // array of user_ids

    public static function get_restricted_user_ids() : array {
        $ids = get_option( self::OPTION_RESTRICTED_USERS, array() );

        if ( ! is_array( $ids ) ) {
            return array();
        }

        $ids = array_map( 'absint', $ids );
        $ids = array_filter( $ids );

        return array_values( array_unique( $ids ) );
    }

    public static function add_restricted_user_id( int $user_id ) : void {
        if ( $user_id <= 0 ) {
            return;
        }

        $ids   = self::get_restricted_user_ids();
        $ids[] = $user_id;

        update_option( self::OPTION_RESTRICTED_USERS, array_values( array_unique( $ids ) ), false );
    }

    public static function remove_restricted_user_id( int $user_id ) : void {
        if ( $user_id <= 0 ) {
            return;
        }

        $ids = self::get_restricted_user_ids();
        $ids = array_values(
            array_filter(
                $ids,
                static function ( $id ) use ( $user_id ) {
                    return (int) $id !== (int) $user_id;
                }
            )
        );

        update_option( self::OPTION_RESTRICTED_USERS, $ids, false );
    }

    public static function is_user_restricted( int $user_id ) : bool {
        if ( $user_id <= 0 ) {
            return false;
        }

        $ids = self::get_restricted_user_ids();
        return in_array( (int) $user_id, $ids, true );
    }

    public static function get_user_id_by_email( string $email ) : int {
        $email = sanitize_email( $email );
        if ( empty( $email ) ) {
            return 0;
        }

        $user = get_user_by( 'email', $email );
        if ( ! $user || empty( $user->ID ) ) {
            return 0;
        }

        return (int) $user->ID;
    }

    public static function get_hidden_menu_slugs( int $user_id ) : array {
        if ( $user_id <= 0 ) {
            return array();
        }

        $slugs = get_user_meta( $user_id, 'hm_mc_hidden_menu_slugs', true );

        if ( ! is_array( $slugs ) ) {
            return array();
        }

        $slugs = array_map(
            static function ( $s ) {
                $s = (string) $s;
                $s = trim( $s );
                return $s;
            },
            $slugs
        );

        $slugs = array_filter( $slugs );
        return array_values( array_unique( $slugs ) );
    }

    public static function save_hidden_menu_slugs( int $user_id, array $slugs ) : void {
        if ( $user_id <= 0 ) {
            return;
        }

        $slugs = array_map(
            static function ( $s ) {
                $s = (string) $s;
                $s = trim( $s );
                return $s;
            },
            $slugs
        );

        $slugs = array_filter( $slugs );

        update_user_meta( $user_id, 'hm_mc_hidden_menu_slugs', array_values( array_unique( $slugs ) ) );
    }
}
