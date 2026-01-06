<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class HM_MC_Admin {

    private static $instance = null;

    public static function instance() : HM_MC_Admin {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {}

    public function init() : void {
        require_once HM_MC_PATH . 'includes/admin/class-hm-settings.php';

        // UI bir sonraki committe gelecek.
        // Şimdilik altyapı hazır: restricted user registry + helper'lar.
    }

    public static function current_user_is_restricted() : bool {
        $user_id = get_current_user_id();
        if ( $user_id <= 0 ) {
            return false;
        }

        require_once HM_MC_PATH . 'includes/admin/class-hm-settings.php';
        return HM_MC_Settings::is_user_restricted( (int) $user_id );
    }

    private function __clone() {}
    public function __wakeup() {}
}
