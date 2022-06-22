<?php
/**
 * Plugin Name: Fixed avatars
 * Description: Fixed avatars deshabilita los gravatares y permite que los usuarios elijan su imagen de perfil de entre archivos predefinidos
 * Tags: gravatar, avatar, WordPress
 * Version: 1.2
 * Author: Nicolas Limet
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 */

if ( ! class_exists( 'Fixed_Avatars' ) ) {

	/**
	 * Main plugin class.
	 */
	class Fixed_Avatars {

		/**
		 * @var Fixed_Avatars The single instance of the class.
		 * @since 1.0
		 */
		protected static $_instance = null;



		/**
		 * Main Fixed_Avatars Instance.
		 *
		 * Ensures only one instance of Fixed_Avatars is loaded or can be loaded.
		 *
		 * @since 1.1
		 * @static
		 * @return Fixed_Avatars - Main instance
		 */
		public static function instance() {
			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self();
			}
			return self::$_instance;
		}



		/**
		 * Cloning is forbidden.
		 *
		 * @since 1.1
		 */
		public function __clone() {
			_doing_it_wrong( __FUNCTION__, esc_html__( 'Cheatin&#8217; huh?', 'sportspress' ), '1.1' );
		}



		/**
		 * Unserializing instances of this class is forbidden.
		 *
		 * @since 1.1
		 */
		public function __wakeup() {
			_doing_it_wrong( __FUNCTION__, esc_html__( 'Cheatin&#8217; huh?', 'sportspress' ), '1.1' );
		}



		/**
		 * Fixed_Avatars Constructor.
		 */
		private function __construct() {

			$this->define_constants();
			$this->includes();
			$this->init_hooks();
		}


		/**
		 * Plugin constants.
		 */
		private function define_constants() {
			define( 'FA_DEFAULT_URL', plugins_url( '/assets/images/default/', __FILE__ ) );
			define( 'FA_DEFAULT_DIR', plugin_dir_path( __FILE__ ) . '/assets/images/default/' );
			define( 'FA_PRESET_URL', plugins_url( '/assets/images/preset/', __FILE__ ) );
			define( 'FA_PRESET_DIR', plugin_dir_path( __FILE__ ) . 'assets/images/preset/' );
			define( 'FA_IMG_EXT', array( '.png', '.jpg', '.jpeg', '.svg', '.avi', '.webp' ) );
			define( 'FA_IMG_SEPARATOR', array( '-', '_' ) );
			define( 'FA_BASENAME', plugin_basename( __FILE__ ) );
		}



		/**
		 * Require files
		 */
		private function includes() {
			if ( is_admin() ) {
				include 'fixed-avatars-admin.php';
			}
		}



		/**
		 * Hook into actions and filters.
		 */
		private function init_hooks() {

			// Change avatar url in wp_avatar.
			add_filter( 'get_avatar', array( $this, 'wp_avatar' ), 1000, 5 );

			// BuddyPress.
			add_action( 'bp_before_member_avatar_upload_content', array( $this, 'output' ) );
			add_filter( 'bp_core_fetch_avatar', array( $this, 'bp_avatar' ), 1, 2 );

			// Directorist template override.
			if ( function_exists( 'ATBDP' ) ) {
				add_filter( 'directorist_template_file_path', array( $this, 'directorist_override' ), 999, 3 );
				add_action( 'directorist_after_dashboard_contents', array( $this, 'directorist_modal' ) );

			}

			// Enqueue files if BuddyPress and/or Directorist are active.
			add_action(
				'wp_enqueue_scripts',
				function() {

					if ( ( function_exists( 'is_buddypress' ) && bp_is_current_component( 'profile' ) ) || ( function_exists( 'ATBDP' ) && is_page( 'dashboard' ) ) ) :

						// CSS.
						wp_enqueue_style( 'wp-jquery-ui-dialog' );
						wp_enqueue_style( 'fa-avatar.css', plugins_url( 'assets/css/fa-avatar.css', __FILE__ ), array(), filemtime( plugin_dir_path( __FILE__ ) . 'assets/css/fa-avatar.css' ) );

						// JS.
						$user = wp_get_current_user();
						wp_enqueue_script( 'fa-avatar.js', plugins_url( 'assets/js/fa-avatar.js', __FILE__ ), array( 'jquery', 'jquery-ui-dialog' ), filemtime( plugin_dir_path( __FILE__ ) . 'assets/js/fa-avatar.js' ), true );
						wp_add_inline_script(
							'fa-avatar.js',
							'const Favatar = ' . json_encode(
								array(
									'ajaxurl' => admin_url( 'admin-ajax.php' ),
									'nonce'   => wp_create_nonce( 'fixed_avatar_nonce' ),
									'ID'      => $user->ID,
								)
							),
							'before'
						);

					endif;
				}
			);
		}



		/**
		 * Hooked to 'directorist_template_file_path'.
		 * Sets the profile-pic.php path.
		 *
		 * @since 1.2
		 * @return string profile-pic.php path
		 */
		public function directorist_override( $file_path, $template, $args ) {
			if ( 'dashboard/profile-pic' !== $template ) {
				return $file_path;

			} else {
				return $file_path = plugin_dir_path( __FILE__ ) . '/templates/profile-pic.php';
			}
		}



		/**
		 * Hooked to 'directorist_after_dashboard_contents'.
		 * Outputs the avatar modal in Directorost's dashboard.
		 *
		 * @since 1.2
		 */
		public function directorist_modal() {
			$user_id = wp_get_current_user()->ID;
			?>
			<div id="fixed_avatars_dash_modal"  style="display:none" class="fixed_avatar_sel_wrap">
				<fieldset>
					<h3 class="fixed_avatar_title"><?php echo esc_html__( 'Choose Your Avatar:' ); ?></h3>
					<legend class="screen-reader-text">
						<span><?php echo esc_html__( 'Choose Your Avatar' ); ?></span>
					</legend>

					<?php echo self::avatar_list( $user_id ); ?>

				</fieldset>

				<?php self::ajax_div(); ?>
			</div>
			<?php
		}


		/**
		 * Helper function to replace img files names white spaces for url use
		 *
		 * @since 1.1
		 * @return str img file name without white spaces
		 */
		public static function white_space( $file_name ) {
			return str_replace( ' ', '%20', $file_name );
		}

		/**
		 * static helper function to get the default avatar
		 *
		 * @since 1.0
		 * @return str full name of the first file in the "base" directory, sorted alphabetically
		 */
		public static function get_default() {
			$files = array_diff( scandir( FA_DEFAULT_DIR ), array( '.', '..' ) );
			usort( $files, 'strcasecmp' );
			return $files[0];
		}



		/**
		 * Static helper function to output the avatar list inputs on both front & back end.
		 *
		 * @since 1.1
		 * @return str avatar list markup
		 */
		public static function avatar_list( $user_id ) {
			$avatars = array_diff( scandir( FA_PRESET_DIR ), array( '.', '..' ) );
			$chosen  = get_user_meta( $user_id, '_fixed_avatar', true );

			ob_start();
			foreach ( $avatars as $avatar ) {
				?>
				<label class="fixed_avatar_label">
					<input type="radio" class="fa_member_avatar" name="fa_member_avatar" value="<?php echo esc_attr( $avatar ); ?>" <?php checked( $avatar, $chosen ); ?>>
					<img src="<?php echo esc_url( FA_PRESET_URL . self::white_space( $avatar ) ); ?>" class="avatar" width="32" height="32">
					<?php echo esc_html( str_replace( array_merge( FA_IMG_EXT, FA_IMG_SEPARATOR ), ' ', $avatar ) ); ?>
				</label>
				<?php
			}
			?>
			<label class="fixed_avatar_label">
				<input type="radio" class="fa_member_avatar" name="fa_member_avatar" value="_use_default" <?php checked( '', $chosen ); ?>>
				<img src="<?php echo esc_url( FA_DEFAULT_URL . self::white_space( self::get_default() ) ); ?>" class="avatar" width="32" height="32">
				<?php echo esc_html( str_replace( array_merge( FA_IMG_EXT, FA_IMG_SEPARATOR ), ' ', self::get_default() ) . '(defecto)' ); ?>
			</label>
			<?php
			return ob_get_clean();
		}



		/**
		 * Static helper function to output the submit avatar div on the front end.
		 *
		 * @since 1.2
		 */
		public static function ajax_div() {
			?>
			<div class="fixed_avatar_sel_ajax">
				<button type="button" id="fixed_avatar_ajax">Change</button>
				<span class="fa_spinner" style="background-image:url(<?php echo esc_url( admin_url( '/images/spinner-2x.gif' ) ); ?>)"></span>
				<span id="fa_avatar_notice"></span>
			</div>
			<?php
		}



		/**
		 * Hooked to get_avatar wp function
		 *
		 * @since 1.0
		 * @return string our own `<img>` tag for the user's avatar
		 */
		public function wp_avatar( $content, $id_or_email, $size = '', $default = '' ) {

			$fa_default = FA_DEFAULT_URL . self::white_space( self::get_default() );

			if ( preg_match( '/gravatar.com\/avatar/', $content ) ) {

				if ( is_numeric( $id_or_email ) ) {
					$user = get_userdata( (int) $id_or_email );

				} elseif ( is_object( $id_or_email ) ) {

					if ( ! empty( $id_or_email ) ) {
						// Check if this is a registered user.
						$user = get_userdata( (int) $id_or_email->user_id );

					} elseif ( ! empty( $id_or_email->post_author ) ) {
						// Check if this is the post author.
						$user = get_user_by( 'id', (int) $id_or_email->post_author );

					} elseif ( ! empty( $id_or_email->comment_author_email ) ) {
						// Commenters not logged in get the default avatar.
						return preg_replace( "/'(https?:)?\/\/.+?'/", $fa_default, $content );
					}
				} else {
					$user = get_user_by( 'email', $id_or_email );
				}

				if ( ! $user ) {
					return preg_replace( "/'(https?:)?\/\/.+?'/", $fa_default, $content );
				}

				$user_avatar = get_user_meta( $user->ID, '_fixed_avatar', true );
				// return $user_avatar or default if not set.
				if ( ! $user_avatar ) {
					return preg_replace( "/'(https?:)?\/\/.+?'/", $fa_default, $content );
				} else {
					$url = FA_PRESET_URL . self::white_space( $user_avatar );
					return preg_replace( "/'(https?:)?\/\/.+?'/", $url, $content );
				}
			}

			return $content;
		}


		/**
		 * Hooked to bp_core_fetch_avatar
		 *
		 * @since 1.0
		 * @return string our own `<img>` tag for the user's avatar
		 */
		public static function bp_avatar( $content, $params ) {
			$fa_default = FA_DEFAULT_URL . self::get_default();

			if ( is_array( $params ) && 'user' == $params['object'] ) {
				$user_avatar = get_user_meta( $params['item_id'], '_fixed_avatar', true );
				$url         = $user_avatar ? FA_PRESET_URL . $user_avatar : $fa_default;
				return '<img src="' . esc_url( $url ) . '" loading="lazy" class="avatar avatar-150 photo" width="150" height="150">';

			} else {
				return '<img src="' . esc_url( $fa_default ) . '" loading="lazy" class="avatar avatar-150 photo" width="150" height="150">';
			}
		}


		/**
		 * Hooked to bp_before_member_avatar_upload_content
		 *
		 * @since 1.0
		 */
		public function output() {

			$user_id = bp_displayed_user_id();
			?>
			<div class="overlay">
			</div>
			<div class="fixed_avatar_sel_wrap">
				<fieldset>
					<h3 class="fixed_avatar_title"><?php echo esc_html__( 'Choose Your Avatar:' ); ?></h3>
					<legend class="screen-reader-text">
						<span><?php echo esc_html__( 'Choose Your Avatar' ); ?></span>
					</legend>

					<?php echo self::avatar_list( $user_id ); ?>

				</fieldset>

				<?php self::ajax_div(); ?>
			</div>
			<?php
		}
	}
}

Fixed_Avatars::instance();
