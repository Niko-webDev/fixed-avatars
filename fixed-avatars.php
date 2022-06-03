<?php
/*
Plugin Name: Fixed avatars
Description: Disable gravatars and let users choose their pic profile from an admin predefined set hosted locally
Tags: gravatar, avatar, wordpress
Version: 1.0
Author: Nicolas Limet
License: GPL v2 or later
License URI: https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
*/

if ( ! class_exists( 'Fixed_Avatars' ) ) {

    class Fixed_Avatars {

        public static function init() {
            define( 'FA_DEFAULT_URL', plugins_url( '/base/', __FILE__ ) );
            define( 'FA_DEFAULT_DIR', plugin_dir_path( __FILE__ ) . 'base/' );
            define( 'FA_PRESET_URL',  plugins_url( '/selection/', __FILE__ ) );
            define( 'FA_PRESET_DIR', plugin_dir_path( __FILE__ ) . 'selection/' );
            define( 'FA_IMG_EXT', array( '.png', '.jpg', '.jpeg', '.svg', '.avi', '.webp' ) );
            define( 'FA_IMG_SEPARATOR', array( '-', '_' ) );

            add_filter( 'plugin_action_links_' . basename( plugin_dir_path( __FILE__ ) ) . '/fixed-avatars.php', 'Fixed_Avatars::readme', 10, 4 );

            add_filter('user_profile_picture_description', '__return_empty_string');
            add_filter('get_avatar', 'Fixed_Avatars::wp_avatar', 1000, 5);

            if ( is_admin() ) {
    			include('fixed-avatars-admin.php');
    		}

            // BuddyPress
            add_action( 'bp_before_member_avatar_upload_content', 'Fixed_Avatars::avatar_list' );
            add_filter('bp_core_fetch_avatar', 'Fixed_Avatars::bp_avatar', 1, 2);

            // Enqueue js ajax file for budypress
            add_action( 'wp_enqueue_scripts', function() {
                // Bail early if buddypress is not installed
                if ( ! function_exists( 'is_buddypress' ) ) return;
                // Enqueue script only on profile page
                if ( ! bp_is_current_component('profile') ) return;

                wp_enqueue_script( 'fa-avatar.js', plugins_url( 'fa-avatar.js', __FILE__ ), array( 'jquery' ), filemtime( plugin_dir_path( __FILE__ ) . 'fa-avatar.js' ), true );
                wp_add_inline_script( 'fa-avatar.js', 'const Favatar = ' .json_encode( array(
                    'ajaxurl'   => admin_url( 'admin-ajax.php' ),
                    'nonce'     => wp_create_nonce(  'fixed_avatar_nonce' ),
                    'ID'        => bp_displayed_user_id(),
                    )
                ), 'before' );
            });
         }



         public static function readme( $links, $file ) {
            return array_merge( $links, array( 'fa_readme' => '<a id="fa_readme" href="#">Imagenes</a>' ) );
         }


        public static function get_default() {
            $files = array_diff( scandir( FA_DEFAULT_DIR ), ['.', '..'] );
            usort( $files, 'strcasecmp' );
            return $files[0];
        }



        public static function wp_avatar( $content, $id_or_email, $size = '', $default = '' ) {

            $fa_default = FA_DEFAULT_URL . Fixed_Avatars::get_default();

            if ( preg_match( "/gravatar.com\/avatar/", $content ) ) {

                if ( is_numeric( $id_or_email ) ) {
                    $user = get_userdata( (int) $id_or_email );

                } elseif ( is_object( $id_or_email ) ) {

                    if ( ! empty( $id_or_email ) ) {
                        // Check if this is a registered user
                        $user = get_userdata( (int) $id_or_email->user_id );

                    } elseif ( ! empty( $id_or_email->post_author ) ) {
                        // Check if this is the post author
                        $user = get_user_by( 'id', (int) $id_or_email->post_author );

                    } elseif ( ! empty( $id_or_email->comment_author_email ) ) {
                        // Commenters not logged in get the default avatar
                        return preg_replace("/'(https?:)?\/\/.+?'/", $fa_default, $content);
                    }

                } else {
                    $user = get_user_by( 'email', $id_or_email );
                }

                if ( ! $user ) return preg_replace("/'(https?:)?\/\/.+?'/", $fa_default, $content);

                $user_avatar = get_user_meta( $user->ID, '_fixed_avatar', true );
                // return $user_avatar or default if not set
                if ( ! $user_avatar ) {
                    return preg_replace("/'(https?:)?\/\/.+?'/", $fa_default, $content);
                } else {
                    $url = FA_PRESET_URL . $user_avatar;
                    return preg_replace( "/'(https?:)?\/\/.+?'/", $url, $content );
                }
            }

            return $content;
        }


        public static function bp_avatar( $content, $params ) {
            $fa_default = FA_DEFAULT_URL . Fixed_Avatars::get_default();

            if ( is_array( $params ) && $params['object'] == 'user' ) {
                $user_avatar = get_user_meta( $params['item_id'], '_fixed_avatar', true );
                $url = $user_avatar ? FA_PRESET_URL . $user_avatar : $fa_default;
                return '<img src="' . $url . '" loading="lazy" class="avatar avatar-150 photo" width="150" height="150">';

            } else {
                return '<img src="' . $fa_default . '" loading="lazy" class="avatar avatar-150 photo" width="150" height="150">';
            }
        }


        public static function avatar_list() {

            $user_id = bp_displayed_user_id();
            $avatars = array_diff( scandir( FA_PRESET_DIR ), ['.', '..'] );
            $chosen = get_user_meta( $user_id, '_fixed_avatar', true );

            ob_start();
            echo basename( plugin_dir_path( __FILE__ ) ) . '/fixed-avatars.php';
            ?>
            <div class="overlay">
            </div>
            <div class="fixed_avatar_sel_wrap">
                <fieldset>
                    <h3 class="fixed_avatar_title">Choose your Avatar:</h3>
                    <legend class="screen-reader-text">
                        <span>Choose your Avatar</span>
                    </legend>

                    <?php
                    foreach( $avatars as $avatar ) {
                        ?>
                        <label class="fixed_avatar_label">
                            <input type="radio" class="fa_member_avatar" name="fa_member_avatar" value="<?php echo $avatar; ?>" <?php checked( $avatar, $chosen ); ?>>
                            <img src="<?php echo FA_PRESET_URL . $avatar; ?>" class="avatar" width="32" height="32">
                            <?php echo str_replace( array_merge( FA_IMG_EXT, FA_IMG_SEPARATOR ), ' ', $avatar ); ?>
                        </label>
                        <?php
                    }
                    ?>
                    <label class="fixed_avatar_label">
                        <input type="radio" class="fa_member_avatar" name="fa_member_avatar" value="_use_default" <?php checked( '', $chosen ); ?>>
                        <img src="<?php echo FA_DEFAULT_URL . Fixed_Avatars::get_default(); ?>" class="avatar" width="32" height="32">
                        <?php echo str_replace( array_merge( FA_IMG_EXT, FA_IMG_SEPARATOR ), ' ', Fixed_Avatars::get_default() ) . '(default)'; ?>
                    </label>
                </fieldset>

                <div class="fixed_avatar_sel_ajax">
                    <button type="button" id="fixed_avatar_ajax" class="elementor-button">Cambiar</button>
                    <span id="fa_avatar_notice"></span>
                </div>
            </div>
            <?php
            echo ob_get_clean();
        }
    }
}

Fixed_Avatars::init();
