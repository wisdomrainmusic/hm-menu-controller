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
$email = isset( $_POST['hm_mc_email'] ) ? sanitize_email( wp_unslash( $_POST['hm_mc_email'] ) ) : '';
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
$user_id = isset( $_POST['hm_mc_user_id'] ) ? absint( wp_unslash( $_POST['hm_mc_user_id'] ) ) : 0;

if ( $user_id > 0 ) {
HM_MC_Settings::remove_restricted_user_id( (int) $user_id );
delete_user_meta( (int) $user_id, 'hm_mc_hidden_menu_slugs' ); // cleanup
self::redirect_with_notice( 'user_removed' );
}

self::redirect_with_notice( 'invalid_request' );
}

if ( 'save_menu_visibility' === $action ) {
$target_user_id = isset( $_POST['hm_mc_target_user_id'] ) ? absint( wp_unslash( $_POST['hm_mc_target_user_id'] ) ) : 0;

if ( $target_user_id <= 0 ) {
self::redirect_with_notice( 'invalid_request' );
}

// posted visible slugs -> we store hidden slugs
$visible = isset( $_POST['hm_mc_visible_slugs'] ) ? (array) wp_unslash( $_POST['hm_mc_visible_slugs'] ) : array();
$visible = array_map( 'sanitize_text_field', $visible );

$all_slugs = HM_MC_Menu_Snapshot::get_all_slugs_flat();
$hidden    = array_values( array_diff( $all_slugs, $visible ) );

HM_MC_Settings::save_hidden_menu_slugs( (int) $target_user_id, $hidden );

$url = add_query_arg(
array(
'page'                => self::MENU_SLUG,
'hm_mc_notice'        => rawurlencode( 'menu_saved' ),
'hm_mc_target_user_id'=> (int) $target_user_id,
),
admin_url( 'admin.php' )
);

wp_safe_redirect( $url );
exit;
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

$notice = isset( $_GET['hm_mc_notice'] ) ? sanitize_text_field( wp_unslash( $_GET['hm_mc_notice'] ) ) : '';

$target_user_id = isset( $_GET['hm_mc_target_user_id'] ) ? absint( wp_unslash( $_GET['hm_mc_target_user_id'] ) ) : 0;
if ( 0 === $target_user_id && ! empty( $restricted_ids ) ) {
$target_user_id = (int) $restricted_ids[0];
}

$menu_tree = HM_MC_Menu_Snapshot::get_tree();

$hidden_slugs = ( $target_user_id > 0 ) ? HM_MC_Settings::get_hidden_menu_slugs( (int) $target_user_id ) : array();

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
<input type="email" id="hm_mc_email" name="hm_mc_email" class="regular-text" placeholder="musteri@gmail.com" required />
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
<table class="widefat striped" style="max-width: 980px;">
<thead>
<tr>
<th><?php echo esc_html__( 'User', 'hm-menu-controller' ); ?></th>
<th><?php echo esc_html__( 'Email', 'hm-menu-controller' ); ?></th>
<th><?php echo esc_html__( 'Role', 'hm-menu-controller' ); ?></th>
<th style="width: 140px;">&nbsp;</th>
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

<hr />

<h2><?php echo esc_html__( 'Menu Visibility (Per User)', 'hm-menu-controller' ); ?></h2>
<p><?php echo esc_html__( 'Select a restricted user, then uncheck items you want to hide from their admin sidebar. This does not block direct URL access.', 'hm-menu-controller' ); ?></p>

<?php self::render_target_user_picker( $restricted_ids, $target_user_id ); ?>

<?php if ( $target_user_id > 0 ) : ?>
<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
<?php wp_nonce_field( 'hm_mc_admin_page' ); ?>
<input type="hidden" name="action" value="hm_mc_admin_page" />
<input type="hidden" name="hm_mc_action" value="save_menu_visibility" />
<input type="hidden" name="hm_mc_target_user_id" value="<?php echo esc_attr( (string) $target_user_id ); ?>" />

<?php self::render_menu_tabs_editor( $menu_tree, $hidden_slugs ); ?>

<?php submit_button( __( 'Save menu visibility', 'hm-menu-controller' ) ); ?>
</form>
<?php endif; ?>

</div>
<?php
}

private static function render_target_user_picker( array $restricted_ids, int $target_user_id ) : void {
if ( empty( $restricted_ids ) ) {
echo '<p>' . esc_html__( 'Add at least one restricted user to configure menu visibility.', 'hm-menu-controller' ) . '</p>';
return;
}

$url = admin_url( 'admin.php?page=' . self::MENU_SLUG );

echo '<div style="margin: 10px 0 16px;">';
echo '<label for="hm_mc_target_user_id" style="margin-right:10px;"><strong>' . esc_html__( 'Target user:', 'hm-menu-controller' ) . '</strong></label>';
echo '<select id="hm_mc_target_user_id" onchange="if(this.value){window.location=\'' . esc_url( $url ) . '&hm_mc_target_user_id='+this.value;}">';

foreach ( $restricted_ids as $uid ) {
$user = get_user_by( 'id', (int) $uid );
if ( ! $user ) {
continue;
}
printf(
'<option value="%1$d"%2$s>%3$s (%4$s)</option>',
(int) $user->ID,
selected( (int) $user->ID, $target_user_id, false ),
esc_html( $user->display_name ),
esc_html( $user->user_email )
);
}

echo '</select>';
echo '</div>';
}

private static function render_menu_tabs_editor( array $menu_tree, array $hidden_slugs ) : void {
if ( empty( $menu_tree ) ) {
echo '<p>' . esc_html__( 'Menu tree is empty.', 'hm-menu-controller' ) . '</p>';
return;
}

$active = isset( $_GET['hm_mc_tab'] ) ? sanitize_text_field( wp_unslash( $_GET['hm_mc_tab'] ) ) : '';
if ( '' === $active ) {
$active = (string) ( $menu_tree[0]['parent_slug'] ?? '' );
}

echo '<h2 class="nav-tab-wrapper" style="margin-top:12px;">';
foreach ( $menu_tree as $node ) {
$slug  = (string) ( $node['parent_slug'] ?? '' );
$label = (string) ( $node['label'] ?? $slug );
if ( '' === $slug ) {
continue;
}

$url = add_query_arg(
array(
'page'      => self::MENU_SLUG,
'hm_mc_tab' => rawurlencode( $slug ),
'hm_mc_target_user_id' => isset( $_GET['hm_mc_target_user_id'] ) ? absint( wp_unslash( $_GET['hm_mc_target_user_id'] ) ) : 0,
),
admin_url( 'admin.php' )
);

$is_active = ( $slug === $active ) ? ' nav-tab-active' : '';
printf(
'<a class="nav-tab%1$s" href="%2$s">%3$s</a>',
esc_attr( $is_active ),
esc_url( $url ),
esc_html( $label )
);
}
echo '</h2>';

foreach ( $menu_tree as $node ) {
$slug = (string) ( $node['parent_slug'] ?? '' );
if ( $slug !== $active ) {
continue;
}

$children = isset( $node['children'] ) && is_array( $node['children'] ) ? $node['children'] : array();

echo '<table class="widefat striped" style="max-width: 980px;">';
echo '<thead><tr>';
echo '<th style="width:80px;">' . esc_html__( 'Show', 'hm-menu-controller' ) . '</th>';
echo '<th>' . esc_html__( 'Menu Item', 'hm-menu-controller' ) . '</th>';
echo '<th style="width:420px;">' . esc_html__( 'Slug', 'hm-menu-controller' ) . '</th>';
echo '</tr></thead>';
echo '<tbody>';

foreach ( $children as $child ) {
$child_label = (string) ( $child['label'] ?? '' );
$child_slug  = (string) ( $child['slug'] ?? '' );
if ( '' === $child_slug ) {
continue;
}

$is_visible = ! in_array( $child_slug, $hidden_slugs, true );

echo '<tr>';
echo '<td><input type="checkbox" name="hm_mc_visible_slugs[]" value="' . esc_attr( $child_slug ) . '" ' . checked( $is_visible, true, false ) . ' /></td>';
echo '<td>' . esc_html( $child_label ) . '</td>';
echo '<td><code>' . esc_html( $child_slug ) . '</code></td>';
echo '</tr>';
}

echo '</tbody>';
echo '</table>';

break;
}
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
'menu_saved'      => array( 'success', __( 'Menu visibility saved for this user.', 'hm-menu-controller' ) ),
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
