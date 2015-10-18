<?php
/**
 * Semantic Linkbacks Notifications
 *
 * @link    http://indiewebcamp.com/
 * @package Semantic Linkbacks Notifications
 * Plugin Name: Semantic Notifications
 * Plugin URI: https://github.com/dshanske/semantic-notifications
 * Description: Notifications Improvements for Semantic Linkbacks
 * Version: 0.0.1
 * Author: David Shanske
 * Author URI: https://david.shanske.com
 * Text Domain: Semantic Notifications
 */

load_plugin_textdomain( 'Semantic Notifications', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );




/**
 * Returns the text used to display comments in notifications.
 *
 * @since 4.x+ *
 * @param int $comment_id The Comment ID.
 * @return string Comment Text.
 */
function get_comment_notify_text( $comment_id ) {
		$comment_author_domain = @gethostbyaddr( $comment->comment_author_IP );
		$type = get_comment_type( $comment_id );
	if ( in_array( $type, array( 'pingback', 'trackback', 'webmention' ) ) ) {
		/* translators: 1: website name, 2: website IP, 3: website hostname */
			$notify_text = sprintf( __( 'Website: %1$s (IP: %2$s, %3$s)' ), get_comment_author( $comment_id ), get_comment_author_ip( $comment_id ), $comment_author_domain ) . "\r\n";
			$notify_text .= sprintf( __( 'URL: %s' ), get_comment_author_url( $comment_id ) ) . "\r\n";
			$notify_text .= sprintf( __( 'Excerpt: %s' ), "\r\n" . get_comment_text( $comment_id ) ) . "\r\n\r\n";
	} else {
		/* translators: 1: comment author, 2: author IP, 3: author domain */
			$notify_text = sprintf( __( 'Author: %1$s (IP: %2$s, %3$s)' ), get_comment_author( $comment_id ), get_comment_author_ip( $comment_id ), $comment_author_domain ) . "\r\n";
			$notify_text .= sprintf( __( 'E-mail: %s' ), get_comment_author_email( $comment_id ) ) . "\r\n";
			$notify_text .= sprintf( __( 'URL: %s' ), get_comment_author_url( $comment_id ) ) . "\r\n";
			$notify_text .= sprintf( __( 'Comment: %s' ), "\r\n" . get_comment_text( $comment_id ) ) . "\r\n\r\n";
	}
		/**
		 * Filter the comment text to be used for email notifications.
	*
	* This generates the display of a comment for notifications.
	*
	* @since 4.4
	*
	* @param string $notify_text The Comment Notify Text.
+         * @param int $comment_id The ID of the comment being displayed.
+             */
		return apply_filters( 'comment_notify_text', $notify_text, $comment_id );
}

/**
 * Filters the text used to display comments in notifications.
 *
 * @since 4.x
 *
 * @param int $comment_id The Comment ID.
 * @return string Comment Text.
 */
function semantic_notify_text( $notify_text, $comment_id ) {
	$type = get_comment_type( $comment_id );
	if ( in_array( $type, array( 'pingback', 'trackback', 'webmention' ) ) ) {
		/* translators: 1: website name, 2: website IP, 3: website hostname */
		if ( ! get_comment_meta( $comment_id, 'semantic_linkbacks_type', true) ) {
			$notify_text = sprintf( __( 'Semantic Type: %s' ), get_comment_meta( $comment_id, 'semantic_linkbacks_type', true)  ) . "\r\n";
			$notify_text .= sprintf( __( 'Author: %1s(%2s) ' ), get_comment_author( $comment_id ), get_comment_meta( $comment_id, 'semantic_linkbacks_author_url', true)  ) . "\r\n";
			if ( ! get_comment_meta( $comment_id, 'semantic_linkbacks_canonical', true) ) {
				$notify_text .= sprintf( __( 'Website: %s ' ), get_comment_meta( $comment_id, 'semantic_linkbacks_canonical', true)  ) . "\r\n";
			}
			$notify_text .= sprintf( __( 'Excerpt: %s' ), "\r\n" . get_comment_text( $comment_id ) ) . "\r\n\r\n";
		}
	}
	return $notify_text;
}

add_filter( 'comment_notify_text', 'semantic_notify_text', 10, 2 );


	/**
	 * Notify an author (and/or others) of a comment/trackback/pingback on a post.
	 *
	 * @since 1.0.0
	 *
	 * @param int|WP_Comment $comment_id Comment ID or WP_Comment object.
	 * @param string         $deprecated Not used
	 * @return bool True on completion. False if no email addresses were specified.
	 */
if ( ! function_exists('wp_notify_postauthor') ) :
function wp_notify_postauthor( $comment_id, $deprecated = null ) {
	if ( null !== $deprecated ) {
		_deprecated_argument( __FUNCTION__, '3.8' );
	}

	$comment = get_comment( $comment_id );
	if ( empty( $comment ) || empty( $comment->comment_post_ID ) ) {
		return false; }

	$post    = get_post( $comment->comment_post_ID );
	$author  = get_userdata( $post->post_author );

	// Who to notify? By default, just the post author, but others can be added.
	$emails = array();
	if ( $author ) {
		$emails[] = $author->user_email;
	}

	/**
	* Filter the list of email addresses to receive a comment notification.
	*
	* By default, only post authors are notified of comments. This filter allows
	* others to be added.
	*
	* @since 3.7.0
	*
	* @param array $emails     An array of email addresses to receive a comment notification.
	* @param int   $comment_id The comment ID.
	*/
	$emails = apply_filters( 'comment_notification_recipients', $emails, $comment->comment_ID );
	$emails = array_filter( $emails );

	// If there are no addresses to send the comment to, bail.
	if ( ! count( $emails ) ) {
		return false;
	}

	// Facilitate unsetting below without knowing the keys.
	$emails = array_flip( $emails );

	/**
	* Filter whether to notify comment authors of their comments on their own posts.
	*
	* By default, comment authors aren't notified of their comments on their own
	* posts. This filter allows you to override that.
	*
	* @since 3.8.0
	*
	* @param bool $notify     Whether to notify the post author of their own comment.
	*                         Default false.
	* @param int  $comment_id The comment ID.
	*/
	$notify_author = apply_filters( 'comment_notification_notify_author', false, $comment->comment_ID );

	// The comment was left by the author
	if ( $author && ! $notify_author && $comment->user_id == $post->post_author ) {
		unset( $emails[ $author->user_email ] );
	}

	// The author moderated a comment on their own post
	if ( $author && ! $notify_author && $post->post_author == get_current_user_id() ) {
		unset( $emails[ $author->user_email ] );
	}

	// The post author is no longer a member of the blog
	if ( $author && ! $notify_author && ! user_can( $post->post_author, 'read_post', $post->ID ) ) {
		unset( $emails[ $author->user_email ] );
	}

	// If there's no email to send the comment to, bail, otherwise flip array back around for use below
	if ( ! count( $emails ) ) {
		return false;
	} else {
		$emails = array_flip( $emails );
	}

	// The blogname option is escaped with esc_html on the way into the database in sanitize_option
	// we want to reverse this for the plain text arena of emails.
	$blogname = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );
	$notify_message  = sprintf( __( 'New response to your post "%s"' ), $post->post_title ) . "\r\n";
	$subject = sprintf( __( '[%1$s] Response: "%2$s"' ), $blogname, $post->post_title );
	/* translators: 1: blog name, 2: post title */
	$notify_message .= get_comment_notify_text( $comment_id );
	$notify_message .= __( 'You can see all responses to this post here: ' ) . "\r\n";
	$notify_message .= get_permalink( $comment->comment_post_ID ) . "#comments\r\n\r\n";
	$notify_message .= sprintf( __( 'Permalink: %s' ), get_comment_link( $comment ) ) . "\r\n";

	if ( user_can( $post->post_author, 'edit_comment', $comment->comment_ID ) ) {
		if ( EMPTY_TRASH_DAYS ) {
			$notify_message .= sprintf( __( 'Trash it: %s' ), admin_url( "comment.php?action=trash&c={$comment->comment_ID}" ) ) . "\r\n";
		} else {
			$notify_message .= sprintf( __( 'Delete it: %s' ), admin_url( "comment.php?action=delete&c={$comment->comment_ID}" ) ) . "\r\n";
		}
		$notify_message .= sprintf( __( 'Spam it: %s' ), admin_url( "comment.php?action=spam&c={$comment->comment_ID}" ) ) . "\r\n";
	}

	$wp_email = 'wordpress@' . preg_replace( '#^www\.#', '', strtolower( $_SERVER['SERVER_NAME'] ) );

	if ( '' == $comment->comment_author ) {
		$from = "From: \"$blogname\" <$wp_email>";
		if ( '' != $comment->comment_author_email ) {
			$reply_to = "Reply-To: $comment->comment_author_email"; }
	} else {
		$from = "From: \"$comment->comment_author\" <$wp_email>";
		if ( '' != $comment->comment_author_email ) {
			$reply_to = "Reply-To: \"$comment->comment_author_email\" <$comment->comment_author_email>"; }
	}

	$message_headers = "$from\n"
	. 'Content-Type: text/plain; charset="' . get_option( 'blog_charset' ) . "\"\n";

	if ( isset( $reply_to ) ) {
		$message_headers .= $reply_to . "\n"; }

	/**
	* Filter the comment notification email text.
	*
	* @since 1.5.2
	*
	* @param string $notify_message The comment notification email text.
	* @param int    $comment_id     Comment ID.
	*/
	$notify_message = apply_filters( 'comment_notification_text', $notify_message, $comment->comment_ID );

	/**
	* Filter the comment notification email subject.
	*
	* @since 1.5.2
	*
	* @param string $subject    The comment notification email subject.
	* @param int    $comment_id Comment ID.
	*/
	$subject = apply_filters( 'comment_notification_subject', $subject, $comment->comment_ID );

	/**
	* Filter the comment notification email headers.
	*
	* @since 1.5.2
	*
	* @param string $message_headers Headers for the comment notification email.
	* @param int    $comment_id      Comment ID.
	*/
	$message_headers = apply_filters( 'comment_notification_headers', $message_headers, $comment->comment_ID );

	foreach ( $emails as $email ) {
		@wp_mail( $email, wp_specialchars_decode( $subject ), $notify_message, $message_headers );
	}

	return true;
}
endif;

	/**
	 * Notifies the moderator of the blog about a new comment that is awaiting approval.
	 *
	 * @since 1.0.0
	 *
	 * @global wpdb $wpdb WordPress database abstraction object.
	 *
	 * @param int $comment_id Comment ID
	 * @return true Always returns true
	 */
if ( ! function_exists('wp_notify_moderator') ) :
function wp_notify_moderator($comment_id) {
	global $wpdb;

	if ( 0 == get_option( 'moderation_notify' ) ) {
		return true; }

	$comment = get_comment( $comment_id );
	$post = get_post( $comment->comment_post_ID );
	$user = get_userdata( $post->post_author );
	// Send to the administration and to the post author if the author can modify the comment.
	$emails = array( get_option( 'admin_email' ) );
	if ( $user && user_can( $user->ID, 'edit_comment', $comment_id ) && ! empty( $user->user_email ) ) {
		if ( 0 !== strcasecmp( $user->user_email, get_option( 'admin_email' ) ) ) {
			$emails[] = $user->user_email; }
	}

	$comments_waiting = $wpdb->get_var( "SELECT count(comment_ID) FROM $wpdb->comments WHERE comment_approved = '0'" );

	// The blogname option is escaped with esc_html on the way into the database in sanitize_option
	// we want to reverse this for the plain text arena of emails.
	$blogname = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );
	$notify_message  = sprintf( __( 'A new response to the post "%s" is waiting for your approval' ), $post->post_title ) . "\r\n";
	$notify_message .= get_comment_notify_text( $comment_id );

	$notify_message .= sprintf( __( 'Approve it: %s' ),  admin_url( "comment.php?action=approve&c=$comment_id" ) ) . "\r\n";
	if ( EMPTY_TRASH_DAYS ) {
		$notify_message .= sprintf( __( 'Trash it: %s' ), admin_url( "comment.php?action=trash&c=$comment_id" ) ) . "\r\n";
	} else { 	$notify_message .= sprintf( __( 'Delete it: %s' ), admin_url( "comment.php?action=delete&c=$comment_id" ) ) . "\r\n"; }
	$notify_message .= sprintf( __( 'Spam it: %s' ), admin_url( "comment.php?action=spam&c=$comment_id" ) ) . "\r\n";

	$notify_message .= sprintf( _n('Currently %s response is waiting for approval. Please visit the moderation panel:',
	'Currently %s responses are waiting for approval. Please visit the moderation panel:', $comments_waiting), number_format_i18n( $comments_waiting ) ) . "\r\n";
	$notify_message .= admin_url( 'edit-comments.php?comment_status=moderated' ) . "\r\n";

	$subject = sprintf( __( '[%1$s] Please moderate: "%2$s"' ), $blogname, $post->post_title );
	$message_headers = '';

	/**
	* Filter the list of recipients for comment moderation emails.
	*
	* @since 3.7.0
	*
	* @param array $emails     List of email addresses to notify for comment moderation.
	* @param int   $comment_id Comment ID.
	*/
	$emails = apply_filters( 'comment_moderation_recipients', $emails, $comment_id );

	/**
	* Filter the comment moderation email text.
	*
	* @since 1.5.2
	*
	* @param string $notify_message Text of the comment moderation email.
	* @param int    $comment_id     Comment ID.
	*/
	$notify_message = apply_filters( 'comment_moderation_text', $notify_message, $comment_id );

	/**
	* Filter the comment moderation email subject.
	*
	* @since 1.5.2
	*
	* @param string $subject    Subject of the comment moderation email.
	* @param int    $comment_id Comment ID.
	*/
	$subject = apply_filters( 'comment_moderation_subject', $subject, $comment_id );

	/**
	* Filter the comment moderation email headers.
	*
	* @since 2.8.0
	*
	* @param string $message_headers Headers for the comment moderation email.
	* @param int    $comment_id      Comment ID.
	*/
	$message_headers = apply_filters( 'comment_moderation_headers', $message_headers, $comment_id );

	foreach ( $emails as $email ) {
		@wp_mail( $email, wp_specialchars_decode( $subject ), $notify_message, $message_headers );
	}

	return true;
}
endif;
?>
