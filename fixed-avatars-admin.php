<?php
/**
 * Fixed_Avatar Admin
 */

if ( ! class_exists( 'Fixed_Avatars_Admin' ) ) {

	/**
	 * Fixed_Avatars_Admin class
	 */
	class Fixed_Avatars_Admin {


		/**
		 * Constructor
		 */
		public function __construct() {
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
			$this->init_hooks();
		}



		/**
		 * Enqueue scripts for the plugin instructions modal
		 */
		public function enqueue( $hook ) {

			// Bail early if not on plugins screen.
			if ( 'plugins.php' !== $hook ) {
				return;
			}
			// wp_enqueue_script( 'jquery-ui-dialog' ); // jquery and jquery-ui should be dependencies, didn't check though...
			wp_enqueue_style( 'wp-jquery-ui-dialog' );
			wp_enqueue_script( 'fa-popup', plugins_url( 'assets/js/fa-popup.js', __FILE__ ), array( 'jquery', 'jquery-ui-dialog' ), filemtime( plugin_dir_path( __FILE__ ) . 'assets/js/fa-popup.js' ), true );
		}



		/**
		 * Hook into actions and filters.
		 */
		private function init_hooks() {

			// Add link to the readme modal.
			add_filter( 'plugin_action_links_' . FA_BASENAME, array( $this, 'readme' ), 10, 2 );

			// Add the plugin instructions modal mark-up in plugins.php footer.
			add_action( 'in_admin_footer', array( $this, 'modal' ), 1 );

			// Show disabled avatar defaults warning in settings.
			add_filter( 'default_avatar_select', array( $this, 'default_avatar_select' ) );

			// Prevent users to use their gravatar.
			add_filter( 'user_profile_picture_description', '__return_empty_string' );

			// Avatar selection mark up in user profile.
			add_action( 'show_user_profile', array( $this, 'output' ) );
			add_action( 'edit_user_profile', array( $this, 'output' ) );

			// Save selected avatar from dashboard.
			add_action( 'personal_options_update', array( $this, 'save_avatar' ) );
			add_action( 'edit_user_profile_update', array( $this, 'save_avatar' ) );

			// Ajax hook for buddypress front-end.
			if ( function_exists( 'is_buddypress' ) || function_exists( 'ATBDP' ) ) {
				add_action( 'wp_ajax_fa_front_save_avatar', array( $this, 'ajax' ) );
			}
		}



		/**
		 * Add an action link to display plugin instructions
		 *
		 * @return array $links
		 */
		public function readme( $links, $file ) {
			return array_merge( $links, array( 'fa_readme' => '<a id="fa_readme" href="#">Imagenes</a>' ) );
		}



		/**
		 * Outputs the instructions modal html in admin footer.
		 */
		public function modal() {
			global $pagenow;
			if ( 'plugins.php' !== $pagenow ) {
				return;
			}
			?>
			<div id="fa_plugin_modal" style="display:none">
				<p>== Image Files Locations ==</p>
				<p>The default avatar must be uploaded to the "assets/images/default" sub-directory. The first file in alphabetical order will be used as default.</p>
				<p>The avatars to choose from must be uploaded to the "assets/images/preset" sub-directory.</p>
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



		/**
		 * Show disabled avatar defaults warning in settings.
		 */
		public function default_avatar_select( $avatar_list ) {
			return '<p class="description" style="color:#cc0000;font-size:18px">Los gravatars están deshabilitados.</p>';
		}



		/**
		 * Outputs avatar list in user-edit.php
		 */
		public function output( $user ) {
			if ( ! current_user_can( 'edit_user', $user->ID ) ) {
				return;
			}
			?>
			<table class="form-table" role="presentation" style="margin-top:2rem">
				<tbody>
					<tr class="avatar-settings">
						<th scope="row"><?php echo esc_html__( 'Choose Your Avatar:' ); ?></th>
						<td class="defaultavatarpicker">
							<fieldset>
								<legend class="screen-reader-text">
									<span><?php echo esc_html__( 'Choose Your Avatar' ); ?></span>
								</legend>
								<?php echo Fixed_Avatars::avatar_list( $user->ID ); ?>
							</fieldset>
						</td>
					</tr>
				</tbody>
			</table>
			<style>.form-table td fieldset .fixed_avatar_label{display:block;margin:2rem 0!important}</style>
			<?php
		}



		/**
		 * Save the avatar choice in user-edit.php
		 */
		public function save_avatar( $user_id ) {

			if ( ! current_user_can( 'edit_user', $user_id ) ) {
				return;
			}
			if ( ! isset( $_POST['fa_member_avatar'] ) || empty( $_POST['fa_member_avatar'] ) ) {
				return;
			}

			$auth_avatars = array_diff( scandir( FA_PRESET_DIR ), array( '.', '..' ) );

			if ( in_array( $_POST['fa_member_avatar'], $auth_avatars ) ) {
				update_user_meta( $user_id, '_fixed_avatar', sanitize_text_field( $_POST['fa_member_avatar'] ) );
			}

			if ( '_use_default' === $_POST['fa_member_avatar'] ) {
				delete_user_meta( $user_id, '_fixed_avatar' );
			}
		}



		/**
		 * Processes the ajax request from frontend.
		 */
		public function ajax() {

			check_ajax_referer( 'fixed_avatar_nonce' );

			if ( ! isset( $_POST['id'] ) || empty( $_POST['id'] ) || ! isset( $_POST['avatar_preset'] ) || empty( $_POST['avatar_preset'] ) ) {
				wp_send_json( 'Silence is golden' );
				exit;
			}

			$user_id = intval( $_POST['id'] );

			if ( ! user_can( $user_id, 'edit_user', $user_id ) ) {
				wp_send_json( 'unauthorized' );
				exit;
			}

			$auth_avatars = array_diff( scandir( FA_PRESET_DIR ), array( '.', '..' ) );
			$user_id      = intval( $_POST['id'] );

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
}
return new Fixed_Avatars_Admin();
