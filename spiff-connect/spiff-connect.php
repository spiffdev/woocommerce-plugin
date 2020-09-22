<?php
   /*
   Plugin Name: Spiff Connect
   Plugin URI: http://spiff3d.com
   Description: Connect your WooCommerce store to Spiff and allow customers access to Spiff Workflows to personalize their products.
   Version: 2.1
   Author: Spiff Pty. Ltd.
   License: GPL3
   */

/*
 * Create admin menu.
 */
add_action('admin_menu', 'spiff_create_admin_menu');
function spiff_create_admin_menu() {
    add_menu_page('Spiff Connect', 'Spiff Connect', 'administrator', 'spiff-connect', 'spiff_admin_menu_html');
    add_action( 'admin_init', 'spiff_register_admin_settings' );
}
function spiff_register_admin_settings() {
    register_setting( 'spiff-settings-group', 'spiff_api_key' );
    register_setting( 'spiff-settings-group', 'spiff_api_secret' );
}
function spiff_admin_menu_html() {
?>
<div class="wrap">
    <h1>Spiff Connect</h1>
    <p>Enter your integration's access key and secret here.</p>
    <p>Your integration's key and secret may be found on your integration's page in the Spiff Hub.</p>

    <form autocomplete="off" method="post" action="options.php">
        <?php settings_fields( 'spiff-settings-group' ); ?>
        <?php do_settings_sections( 'spiff-settings-group' ); ?>
        <table class="form-table">
            <tr valign="top">
            <th scope="row">Access Key</th>
            <td><input autocomplete=off type="text" name="spiff_api_key" value="<?php echo esc_attr( get_option('spiff_api_key') ); ?>" /></td>
            </tr>

            <tr valign="top">
            <th scope="row">Secret</th>
            <td><input autocomplete=off type="password" name="spiff_api_secret" value="<?php echo esc_attr( get_option('spiff_api_secret') ); ?>" /></td>
            </tr>
        </table>
        <?php submit_button(); ?>
    </form>
</div>
<?php
}
