<?php
/*
Plugin Name: BuddyPress Admin Notifications
Plugin URI: http://dev.benoitgreant.be/2009/11/14/buddypress-admin-notifications
Description: This plugin adds a checkbox in the post/page admin (for the admins and editors) to tell members (notification & email) that an important post has been published.
Version: 0.1
Revision Date: november 14, 2009
Requires at least: WPMU 2.8.5, BuddyPress 1.3
Tested up to: WPMU 2.8.5, BuddyPress 1.3
License: (GNU General Public License 2.0 (GPL)
Author: G.Breant
Author URI: http://dev.benoitgreant.be
Site Wide Only: true
*/

/* Define a constant that can be checked to see if the component is installed or not. */
define ( 'BP_ADMIN_NOTIFICATIONS_IS_INSTALLED', 1 );

/* Define a constant that will hold the current version number of the component */
define ( 'BP_ADMIN_NOTIFICATIONS_VERSION', '0.1' );

/* Define the slug for the component */
if ( !defined( 'BP_ADMIN_NOTIFICATIONS' ) )
	define ( 'BP_ADMIN_NOTIFICATIONS', 'admin_notifications' );

if ( file_exists( WP_PLUGIN_DIR . '/bp-admin-notifications/languages/' . get_locale() . '.mo' ) )
	load_textdomain( 'bp-admin-notifications', WP_PLUGIN_DIR . '/bp-admin-notifications/languages/' . get_locale() . '.mo' );
	
	
function bp_admin_notifications_setup_globals() {
	global $bp, $wpdb;

	/* For internal identification */
	$bp->admin_notifications->id = 'admin_notifications';
	
	/* Register this in the active components array */
	$bp->admin_notifications->format_notification_function = 'bp_admin_notifications_format';
	$bp->admin_notifications->slug = BP_ADMIN_NOTIFICATIONS;


	/* Register this in the active components array */
	$bp->active_components[$bp->admin_notifications->slug] = $bp->admin_notifications->id;

}
add_action( 'plugins_loaded', 'bp_admin_notifications_setup_globals', 6 );
add_action( 'admin_menu', 'bp_admin_notifications_setup_globals', 6 );


/*Notifications Settings*/
function bp_admin_notifications_settings() {
	global $bp;
	global $current_user; ?>
	<table class="notification-settings" id="bp-admin-notifications-settings">
		<tr>
			<th class="icon"></th>
			<th class="title"><?php _e( 'New admin post', 'bp_admin_notifications' ) ?></th>
			<th class="yes"><?php _e( 'Yes', 'buddypress' ) ?></th>
			<th class="no"><?php _e( 'No', 'buddypress' )?></th>
		</tr>
		<tr>
			<td></td>
			<td><?php _e( 'The administrator posted an important information', 'bp_admin_notifications' ) ?></td>
			<td class="yes"><input type="radio" name="notifications[notification_admin_new_post]" value="yes" <?php if ( !get_usermeta( $current_user->id, 'notification_admin_new_post') || 'yes' == get_usermeta( $current_user->id, 'notification_admin_new_post') ) { ?>checked="checked" <?php } ?>/></td>
			<td class="no"><input type="radio" name="notifications[notification_admin_new_post]" value="no" <?php if ( 'no' == get_usermeta( $current_user->id, 'notification_admin_new_post') ) { ?>checked="checked" <?php } ?>/></td>
		</tr>

		<?php do_action( 'bp_admin_notifications_settings' ); ?>
	</table>
<?php	
}
add_action( 'bp_notification_settings', 'bp_admin_notifications_settings' );
	


//Send the notifications if the post is published and that the notification has not been sent before
function bp_admin_notifications_handle($post_id) {

	// origination and intention
	if (!wp_verify_nonce($_POST['bp_admin_notifications_verify'], 'bp_admin_notifications'))
		return $post_id;

	//send notification
	if(isset($_POST['bp_admin_notifications_check']) and $_POST['bp_admin_notifications_check'] == "post_notification" ) {
		$status = $_POST['post_status'];
		$password = $_POST['post_password'];
		$meta = get_post_meta($_POST['ID'],'bp_admin_notifications_sent',true);
		
		//do not send it if it has been ever done | if not published | protected
		
		if (($meta) || ($status!='publish') || (!empty($password))) return false;

		if (bp_admin_notifications_send($_POST['ID'])) {
			add_post_meta($_POST['ID'],'bp_admin_notifications_sent', 1,true);
		}
	}
}

//Add Metabox
function bp_admin_notifications_metabox() {
    if ( function_exists('add_meta_box') ) {
        add_meta_box('bp-admin-notifications-settings',__('Send Notification to the members','bp-admin-notifications'),'bp_admin_notifications_metabox_content','post','normal');
        add_meta_box('bp-admin-notifications-settings',__('Send Notification to the members','bp-admin-notifications'),'bp_admin_notifications_metabox_content','page','normal');
    }
}

//Metabox content
function bp_admin_notifications_metabox_content() {
	global $post, $current_user;
	global $wpdb;
	
	if(isset($post->ID))
		$post_id = $post->ID;
	
	$done = get_post_meta($post_id,'bp_admin_notifications_sent',true);
	?>
	<table id="post_notification_table">
		<tr>
			<th><label for="notify"><?php _e( 'Notify the blog members', 'bp-admin-notifications' );?></label></th>
			<td><input type="checkbox" name="bp_admin_notifications_check" value="post_notification"<?php if($done){echo" DISABLED CHECKED";}?>/></td>
			<input type="hidden" name="bp_admin_notifications_verify" id="bp_admin_notifications_verify" value="<?php echo wp_create_nonce('bp_admin_notifications');?>" />
		</tr>
		  </table>
	<?php
}

//Get users ids
function bp_admin_notifications_get_users() {
	global $wpdb;
	global $bp;
	
	$results = $wpdb->get_col("SELECT ID FROM $wpdb->users");
	
	if (!$results) return false;
	
	$users_ids=array();
	
	//remove poster ID
	foreach ($results as $user_id) {
		if ($user_id==$bp->loggedin_user->id) continue;
		$users_ids[]=$user_id;
	}
	
	
	return $users_ids;

}

//Get post info
function bp_admin_notifications_post_info($post_id) {
	$result = query_posts('p='.$post_id);
	
	if (!$result) return false;
		
	$post = $result[0];
	
	$item['guid']=$post->guid;
	$item['title']=$post->post_title;
	
	if ($post->post_excerpt) {	
		$item['excerpt']=$post->post_excerpt;
	}else {
		$item['excerpt']=bp_create_excerpt($post->post_content,200);
	}
	return $item;
}

//Format notifications
function bp_admin_notifications_format($action,$item_id) {
	global $bp;
	
	$post = bp_admin_notifications_post_info($item_id);

	if ( 'new_admin_notification' == $action ) {
		return apply_filters( 'bp_admin_new_notifications', '<a href="' . $post['guid']. '" title="' . $post['title'] . '">' . __('There is an important information to read', 'bp_admin_notifications' ) . '</a>');
	}
	
	do_action( 'bp_admin_notifications_format', $item_id, $post['guid'], $post['title']);

}

//Send notifications & emails
function bp_admin_notifications_send($post_id) {
	global $bp, $wpdb;

	$users_ids = bp_admin_notifications_get_users();
	$post = bp_admin_notifications_post_info($item_id);
	
	foreach ( (array)$users_ids  as $user_id ) {
	
		$ud = get_userdata( $user_id );
	
		//Screen Notification
		bp_core_add_notification( $post_id, $user_id, $bp->admin_notifications->id, 'new_admin_notification' );
		
		if ( 'no' != get_usermeta( $user_id, 'notification_admin_new_post' ) ) {
			
			$user_name = bp_core_get_userlink( $user_id, true, false, true );
			$user_link = bp_core_get_user_domain( $user_id );
			
			$settings_link = $user_name . 'settings/notifications/';

			// Set up and send the message
			$to = $ud->user_email;
			$blogname = get_blog_option( BP_ROOT_BLOG, 'blogname' );

			$subject = '[' . $blogname . '] ' . __( 'New important information !', 'bp_admin_notifications' );

			$message = sprintf( __( 
		'A new important information has been published on "%s" : 
		
		%s
		
		You can read the full post "%s" here : %s


		---------------------
		', 'bp_admin_notifications' ), $blogname, $post['excerpt'], $post['title'], $post['guid'] );

			$message .= sprintf( __( 'To disable these notifications please log in and go to: %s', 'buddypress' ), $settings_link );

			// Send it
			$sent_mail = wp_mail( $to, $subject, $message );
			
		}
	}

	return true;
}

//Remove notification if the post has been viewed
function bp_admin_notifications_remove() {
	global $bp;
	global $post;

		bp_core_delete_notifications_for_user_by_item_id( $bp->loggedin_user->id, $post->ID, $bp->admin_notifications->slug, 'new_admin_notification');
		do_action( 'bp_admin_notifications_remove' );	
}
add_action('bp_before_blog_single_post','bp_admin_notifications_remove');
add_action('bp_before_blog_page','bp_admin_notifications_remove');

/*********************************************************************************/

function bp_admin_notifications_init() {
	
	//Not for everybody
	if (!current_user_can('edit_others_posts')) return false;

	//Add Metabox
	add_action('admin_menu', 'bp_admin_notifications_metabox');
	
	//Send Notification
	add_action('publish_post', 'bp_admin_notifications_handle');
	add_action('publish_page', 'bp_admin_notifications_handle');
	add_action('edit_post', 'bp_admin_notifications_handle');
}

//init functions for admin
add_action('plugins_loaded','bp_admin_notifications_init');
?>