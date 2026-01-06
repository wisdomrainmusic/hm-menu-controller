<?php
/**
 * Plugin Name: HM Menu Controller
 * Plugin URI:  https://github.com/wisdomrainmusic/hm-menu-controller
 * Description: Admin sidebar menu visibility controller (UI-only; no access restriction).
 * Version:     0.1.0
 * Author:      Wisdom Rain Music
 * Author URI:  https://wisdomrainmusic.com
 * License:     GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: hm-menu-controller
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Plugin constants
 */
define( 'HM_MC_VERSION', '0.1.0' );
define( 'HM_MC_FILE', __FILE__ );
define( 'HM_MC_PATH', plugin_dir_path( __FILE__ ) );
define( 'HM_MC_URL', plugin_dir_url( __FILE__ ) );

require_once HM_MC_PATH . 'includes/class-hm-loader.php';

final class HM_Menu_Controller {

    private static $instance = null;

    public static function instance() : HM_Menu_Controller {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'plugins_loaded', array( $this, 'init' ) );
    }

    public function init() : void {
        load_plugin_textdomain(
            'hm-menu-controller',
            false,
            dirname( plugin_basename( HM_MC_FILE ) ) . '/languages'
        );

        HM_MC_Loader::instance()->init();
    }

    private function __clone() {}
    public function __wakeup() {}
}

HM_Menu_Controller::instance();
