<?php

class Fixed_Avatars_Admin {

    public static function init() {
        add_action('admin_init', 'Fixed_Avatars_Admin::admin_init');
    }

    public static function admin_init() {

        // Enqueue scripts for the plugin instructions modal
        add_action( 'admin_enqueue_scripts', function( $hook ) {
            if ( 'plugins.php' !== $hook ) return;
            // wp_enqueue_script( 'jquery-ui-dialog' ); // jquery and jquery-ui should be dependencies, didn't check though...
            wp_enqueue_style( 'wp-jquery-ui-dialog' );
            wp_enqueue_script( 'fa-popup', plugins_url( 'fa-popup.js', __FILE__ ), array( 'jquery', 'jquery-ui-dialog' ), filemtime( plugin_dir_path( __FILE__ ) . 'fa-popup.js' ), true );

        });

        // Add the plugin instructions modal mark-up in plugins.php footer
        add_action( 'in_admin_footer', 'Fixed_Avatars_Admin::modal', 1 );

        // Show disabled avatar defaults warning
        add_filter( 'default_avatar_select',  'Fixed_Avatars_Admin::default_avatar_select' );

        // Show avatars selection
        add_action( 'show_user_profile', 'Fixed_Avatars_Admin::choose_avatar' );
        add_action( 'edit_user_profile', 'Fixed_Avatars_Admin::choose_avatar' );

        // Save selected avatar from dashboard
        add_action( 'personal_options_update', 'Fixed_Avatars_Admin::save_avatar' );
        add_action( 'edit_user_profile_update', 'Fixed_Avatars_Admin::save_avatar' );

        // Save selected avatar from buddypress front-end
        if ( function_exists( 'is_buddypress' ) ) {
            add_action( 'wp_ajax_fa_front_save_avatar', 'Fixed_Avatars_Admin::ajax' );
        }
    }


    public static function modal() {
        global $pagenow;
        if ( 'plugins.php' !== $pagenow ) return;
        ?>
        <div id="fa_plugin_modal" style="display:none">
            <p>== Image Files Locations ==</p>
            <p>The default avatar must be uploaded to the "base" sub-directory. The first file in alphabetical order will be used as default.</p>
            <p>The avatars to choose from must be uploaded to the "selection" sub-directory.</p>
            <p class="fa_big_margin_top">== Avatar Names ==</p>
             The file names will be displayed as avatar names. For example,
            "my-avatar.png" or "my_avatar.jpg" will show as "my avatar" across the site.</p>
            <p>The base avatar name will be followed by "(default)".</p>
            <p class="fa_big_margin_top">== File Extension and Separator Removal ==</p>
            <p>The file extensions are defined by the "FA_IMG_EXT" constant. Current extensions are: '.png', '.jpg', '.jpeg', '.svg', '.avi', '.webp'.</p>
            <p>The file separators are defined by the "FA_IMG_SEPARATOR" constant. Current separators are '-' and '_'.</p>
            <p class="fa_big_margin_top">== Image Files Sizes ==</p>
            <p>There are no cropping utilities provided yet.<strong> You should use a 1:1 ratio.</strong></p>
        </div>
        <style>
            .ui-dialog, .ui-dialog-titlebar {
                background: rgba(0,0,0, .8);
            }

            .ui-dialog-titlebar span {
                color: #135e96
            }

            #fa_plugin_modal p {
                font-size: 1.2rem;
                color: white;
                color: #3e83b6;
                margin: 1em;
            }

            #fa_plugin_modal .fa_big_margin_top {
                margin-top: 2em;
            }
        </style>
        <?php
    }


    public static function default_avatar_select( $avatar_list ) {
        return '<p class="description" style="color:#cc0000;font-size:18px">Default gravatars are disabled.</p>';

    }


    public static function choose_avatar( $user ) {

        $avatars = array_diff( scandir( FA_PRESET_DIR ), ['.', '..'] );
        $chosen = get_user_meta( $user->ID, '_fixed_avatar', true );

        ?>
        <table class="form-table" role="presentation" style="margin-top:2rem">
            <tbody>
                <tr class="avatar-settings">
                    <th scope="row">Seleccione su Avatar</th>
                    <td class="defaultavatarpicker">
                        <fieldset>
                            <legend class="screen-reader-text">
                                <span>Seleccione su Avatar</span>
                            </legend>
                            <?php
                            foreach( $avatars as $avatar ) {
                                ?>
                                <label style="display:block;margin:2rem 0!important">
                                    <input type="radio" name="avatar_preset" value="<?php echo $avatar; ?>" <?php checked( $avatar, $chosen ); ?>>
                                    <img src="<?php echo FA_PRESET_URL . $avatar; ?>" class="avatar" width="32" height="32" loading="lazy">
                                    <?php echo str_replace( array_merge( FA_IMG_EXT, FA_IMG_SEPARATOR ), ' ', $avatar ); ?>
                                </label>
                                <?php
                            }
                            ?>
                            <label style="display:block;margin:2rem 0!important">
                                <input type="radio" name="avatar_preset" value="_use_default" <?php checked( '', $chosen ); ?>>
                                <img src="<?php echo FA_DEFAULT_URL . Fixed_Avatars::get_default(); ?>" class="avatar" width="32" height="32" loading="lazy">
                                <?php
                                echo str_replace( array_merge( FA_IMG_EXT, FA_IMG_SEPARATOR ), ' ', Fixed_Avatars::get_default() ) . '(default)';
                                ?>
                            </label>
                        </fieldset>
                    </td>
                </tr>
            </tbody>
        </table>
            <?php
    }


    public static function save_avatar( $user_id ) {

        if ( current_user_can( 'edit_user', $user_id ) ) {
            $auth_avatars = array_diff( scandir( FA_PRESET_DIR ), ['.', '..'] );

            if ( in_array( $_POST['avatar_preset'], $auth_avatars ) ) {
                update_user_meta( $user_id, '_fixed_avatar', sanitize_text_field( $_POST['avatar_preset'] ) );
            }

            if ( '_use_default' === $_POST['avatar_preset'] ) {
                delete_user_meta( $user_id, '_fixed_avatar' );
            }
        }
    }

    public static function ajax() {

        check_ajax_referer( 'fixed_avatar_nonce' );

        if ( ! bp_attachments_current_user_can( 'edit_avatar') ) {
            wp_send_json( 'unauthorized' );
            exit;
        }

        if ( ! isset( $_POST['id'] ) || empty( $_POST['id'] ) || bp_displayed_user_id() != $_POST['id'] ) {
            wp_send_json( 'unauthorized' );
            exit;
        }

        $auth_avatars = array_diff( scandir( FA_PRESET_DIR ), ['.', '..'] );
        $user_id = intval( $_POST['id'] );

        if ( '_use_default' === $_POST['avatar_preset'] ) {
            delete_user_meta( $user_id, '_fixed_avatar' );
            wp_send_json( 'success' );
            exit;
        }

        if ( in_array( $_POST['avatar_preset'], $auth_avatars ) ) {
            update_user_meta( $user_id, '_fixed_avatar', sanitize_text_field( $_POST['avatar_preset'] ) );
            wp_send_json( 'success' );

        } else {
            wp_send_json( 'not found' );
        }
    }
}

Fixed_Avatars_Admin::init();
