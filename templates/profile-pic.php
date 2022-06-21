<?php
/**
 * Profile pic override
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$uid         = $dashboard->id;
$fa_default  = FA_DEFAULT_URL . Fixed_Avatars::white_space( Fixed_Avatars::get_default() );
$user_avatar = get_user_meta( $uid, '_fixed_avatar', true );
$url         = $user_avatar ? FA_PRESET_URL . Fixed_Avatars::white_space( $user_avatar ) : $fa_default;
?>
<div class="overlay"></div>
<div id="fixed_avatars_image_profile_wrap" class="directorist-image-profile-wrap">

	<div class="ezmu__thumbnail-list-item_back">
		<img src="<?php echo esc_url( $url ); ?>" alt="image">
	</div>
	<button role="button" id="fixed_avatars_modal_open">Change</button>
</div>
<?php
