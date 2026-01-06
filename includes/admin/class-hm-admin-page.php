<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class HM_MC_Admin_Page {

    const MENU_SLUG = 'hm-menu-controller';

    public static function register_menu() : void {
        add_menu_page(
            __( 'HM Menu Controller', 'hm-menu-controller' ),
            __( 'HM Menu Controller', 'hm-menu-controller' ),
            'manage_options',
            self::MENU_SLUG,
            array( __CLASS__, 'render_page' ),
            'dashicons-filter',
            81
        );
    }

    public static function handle_post() : void {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        if ( empty( $_POST['hm_mc_action'] ) ) {
            return;
        }

        check_admin_referer( 'hm_mc_admin_page' );

        $action = sanitize_text_field( wp_unslash( $_POST['hm_mc_action'] ) );

        if ( 'add_user' === $action ) {
            $email = '';
            if ( isset( $_POST['hm_mc_email'] ) ) {
                $email = sanitize_email( wp_unslash( $_POST['hm_mc_email'] ) );
            }

            if ( empty( $email ) ) {
                self::redirect_with_notice( 'email_empty' );
            }

            $user_id = HM_MC_Settings::get_user_id_by_email( $email );
            if ( $user_id <= 0 ) {
                self::redirect_with_notice( 'user_not_found' );
            }

            HM_MC_Settings::add_restricted_user_id( (int) $user_id );
            self::redirect_with_notice( 'user_added' );
        }

        if ( 'remove_user' === $action ) {
            $user_id = 0;
            if ( isset( $_POST['hm_mc_user_id'] ) ) {
                $user_id = absint( wp_unslash( $_POST['hm_mc_user_id'] ) );
            }

            if ( $user_id > 0 ) {
                HM_MC_Settings::remove_restricted_user_id( (int) $user_id );
                self::redirect_with_notice( 'user_removed' );
            }

            self::redirect_with_notice( 'invalid_request' );
        }
    }

    private static function redirect_with_notice( string $notice ) : void {
        $url = add_query_arg(
            array(
                'page'         => self::MENU_SLUG,
                'hm_mc_notice' => rawurlencode( $notice ),
            ),
            admin_url( 'admin.php' )
        );

        wp_safe_redirect( $url );
        exit;
    }

    public static function render_page() : void {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have permission to access this page.', 'hm-menu-controller' ) );
        }

        $restricted_ids = HM_MC_Settings::get_restricted_user_ids();

        $notice = '';
        if ( isset( $_GET['hm_mc_notice'] ) ) {
            $notice = sanitize_text_field( wp_unslash( $_GET['hm_mc_notice'] ) );
        }

        ?>
        <div class="wrap">
            <h1><?php echo esc_html__( 'HM Menu Controller', 'hm-menu-controller' ); ?></h1>

            <?php self::render_notice( $notice ); ?>

            <h2><?php echo esc_html__( 'Restricted Users (UI-only)', 'hm-menu-controller' ); ?></h2>
            <p><?php echo esc_html__( 'Add admin users here to apply menu visibility settings. This plugin does not restrict access; it only changes what appears in the admin UI.', 'hm-menu-controller' ); ?></p>

            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-top: 12px;">
                <?php wp_nonce_field( 'hm_mc_admin_page' ); ?>
                <input type="hidden" name="action" value="hm_mc_admin_page" />
                <input type="hidden" name="hm_mc_action" value="add_user" />

                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row">
                            <label for="hm_mc_email"><?php echo esc_html__( 'User email', 'hm-menu-controller' ); ?></label>
                        </th>
                        <td>
                            <input
                                type="email"
                                id="hm_mc_email"
                                name="hm_mc_email"
                                class="regular-text"
                                placeholder="musteri@gmail.com"
                                required
                            />
                            <p class="description"><?php echo esc_html__( 'We store user IDs, not emails, for stability.', 'hm-menu-controller' ); ?></p>
                        </td>
                    </tr>
                </table>

                <?php submit_button( __( 'Add restricted user', 'hm-menu-controller' ) ); ?>
            </form>

            <hr />

            <h2><?php echo esc_html__( 'Current restricted users', 'hm-menu-controller' ); ?></h2>

            <?php if ( empty( $restricted_ids ) ) : ?>
                <p><?php echo esc_html__( 'No restricted users yet.', 'hm-menu-controller' ); ?></p>
            <?php else : ?>
                <table class="widefat striped">
                    <thead>
                        <tr>
                            <th><?php echo esc_html__( 'User', 'hm-menu-controller' ); ?></th>
                            <th><?php echo esc_html__( 'Email', 'hm-menu-controller' ); ?></th>
                            <th><?php echo esc_html__( 'Role', 'hm-menu-controller' ); ?></th>
                            <th style="width: 140px;">&thinsp;<?php echo esc_html__( 'Actions', 'hm-menu-controller' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $restricted_ids as $user_id ) : ?>
                            <?php
                            $user = get_user_by( 'id', (int) $user_id );
                            if ( ! $user ) {
                                continue;
                            }
                            $roles = ! empty( $user->roles ) ? implode( ', ', array_map( 'sanitize_text_field', $user->roles ) ) : '';
                            ?>
                            <tr>
                                <td>
                                    <?php echo esc_html( $user->display_name ); ?>
                                    <?php echo ' '; ?>
                                    <code>#<?php echo esc_html( (string) $user->ID ); ?></code>
                                </td>
                                <td><?php echo esc_html( $user->user_email ); ?></td>
                                <td><?php echo esc_html( $roles ); ?></td>
                                <td>
                                    <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                                        <?php wp_nonce_field( 'hm_mc_admin_page' ); ?>
                                        <input type="hidden" name="action" value="hm_mc_admin_page" />
                                        <input type="hidden" name="hm_mc_action" value="remove_user" />
                                        <input type="hidden" name="hm_mc_user_id" value="<?php echo esc_attr( (string) $user->ID ); ?>" />
                                        <?php submit_button( __( 'Remove', 'hm-menu-controller' ), 'delete', 'submit', false ); ?>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>

        </div>
        <?php
    }

    private static function render_notice( string $notice ) : void {
        if ( empty( $notice ) ) {
            return;
        }

        $map = array(
            'email_empty'     => array( 'error', __( 'Please enter an email.', 'hm-menu-controller' ) ),
            'user_not_found'  => array( 'error', __( 'No user found for that email.', 'hm-menu-controller' ) ),
            'user_added'      => array( 'success', __( 'User added to restricted list.', 'hm-menu-controller' ) ),
            'user_removed'    => array( 'success', __( 'User removed from restricted list.', 'hm-menu-controller' ) ),
            'invalid_request' => array( 'error', __( 'Invalid request.', 'hm-menu-controller' ) ),
        );

        if ( ! isset( $map[ $notice ] ) ) {
            return;
        }

        list( $type, $message ) = $map[ $notice ];

        printf(
            '<div class="notice notice-%1$s is-dismissible"><p>%2$s</p></div>',
            esc_attr( $type ),
            esc_html( $message )
        );
    }
}
