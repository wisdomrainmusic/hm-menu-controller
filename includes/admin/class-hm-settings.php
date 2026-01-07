<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class HM_MC_Settings {

    const OPTION_RESTRICTED_USERS = 'hm_mc_restricted_users'; // array of user_ids
    const OPTION_PRESETS          = 'hm_mc_presets'; // array of presets keyed by preset_key
    const USER_META_PRESET_KEY    = 'hm_mc_preset_key'; // string preset key per user

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

    public static function get_presets() : array {
        $presets = get_option( self::OPTION_PRESETS, array() );

        if ( ! is_array( $presets ) ) {
            return array();
        }

        // Normalize
        foreach ( $presets as $key => $preset ) {
            if ( ! is_array( $preset ) ) {
                unset( $presets[ $key ] );
                continue;
            }

            $name = isset( $preset['name'] ) ? (string) $preset['name'] : '';
            $name = trim( $name );

            $hidden = isset( $preset['hidden_slugs'] ) && is_array( $preset['hidden_slugs'] ) ? $preset['hidden_slugs'] : array();
            $hidden = array_map(
                static function ( $s ) {
                    return sanitize_text_field( (string) $s );
                },
                $hidden
            );
            $hidden = array_values( array_unique( array_filter( $hidden ) ) );

            $presets[ (string) $key ] = array(
                'name'         => $name,
                'hidden_slugs' => $hidden,
            );
        }

        return $presets;
    }

    public static function get_preset( string $preset_key ) : array {
        $preset_key = sanitize_key( $preset_key );
        if ( '' === $preset_key ) {
            return array();
        }

        $presets = self::get_presets();
        return isset( $presets[ $preset_key ] ) && is_array( $presets[ $preset_key ] )
            ? $presets[ $preset_key ]
            : array();
    }

    public static function save_preset( string $preset_key, string $name, array $hidden_slugs ) : bool {
        $preset_key = sanitize_key( $preset_key );
        $name       = sanitize_text_field( $name );

        if ( '' === $preset_key || '' === $name ) {
            return false;
        }

        $hidden_slugs = array_map(
            static function ( $s ) {
                return sanitize_text_field( (string) $s );
            },
            $hidden_slugs
        );
        $hidden_slugs = array_values( array_unique( array_filter( $hidden_slugs ) ) );

        $presets                = self::get_presets();
        $presets[ $preset_key ] = array(
            'name'         => $name,
            'hidden_slugs' => $hidden_slugs,
        );

        update_option( self::OPTION_PRESETS, $presets, false );
        return true;
    }

    public static function delete_preset( string $preset_key ) : void {
        $preset_key = sanitize_key( $preset_key );
        if ( '' === $preset_key ) {
            return;
        }

        $presets = self::get_presets();
        if ( isset( $presets[ $preset_key ] ) ) {
            unset( $presets[ $preset_key ] );
            update_option( self::OPTION_PRESETS, $presets, false );
        }
    }

    public static function get_user_preset_key( int $user_id ) : string {
        if ( $user_id <= 0 ) {
            return '';
        }

        $key = get_user_meta( $user_id, self::USER_META_PRESET_KEY, true );
        $key = sanitize_key( (string) $key );

        return $key;
    }

    public static function set_user_preset_key( int $user_id, string $preset_key ) : void {
        if ( $user_id <= 0 ) {
            return;
        }

        $preset_key = sanitize_key( $preset_key );

        if ( '' === $preset_key ) {
            delete_user_meta( $user_id, self::USER_META_PRESET_KEY );
            return;
        }

        update_user_meta( $user_id, self::USER_META_PRESET_KEY, $preset_key );
    }

    /**
     * Effective hidden slugs:
     * - If a preset is assigned to user -> preset hidden slugs
     * - Else -> fallback to user-specific hidden slugs (legacy)
     */
    public static function get_effective_hidden_menu_slugs( int $user_id ) : array {
        if ( $user_id <= 0 ) {
            return array();
        }

        $preset_key = self::get_user_preset_key( $user_id );
        if ( '' !== $preset_key ) {
            $preset = self::get_preset( $preset_key );
            if ( ! empty( $preset['hidden_slugs'] ) && is_array( $preset['hidden_slugs'] ) ) {
                return array_values( array_unique( array_filter( $preset['hidden_slugs'] ) ) );
            }
        }

        // Fallback: legacy per-user storage
        return self::get_hidden_menu_slugs( $user_id );
    }
}
