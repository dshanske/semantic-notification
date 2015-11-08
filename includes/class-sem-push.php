<?php

add_action( 'init' , array( 'sem_push', 'init' ) );

// The class sets up push notifications for Semantic Linkbacks
class sem_push {
	public static function init() {
		// If Pushover for WordPress is installed, take over comment notifications
		if ( function_exists( 'ckpn_new_comment' ) ) {
			remove_action( 'comment_post', 'ckpn_new_comment', 10, 2 );
			add_action( 'comment_post', array( 'sem_push', 'ckpn_new_comment' ), 11, 2 );
			// For debugging purposes you can enable notifications on edit
			// add_action( 'edit_comment', array('sem_push', 'ckpn_new_comment'), 11, 2);
		}
		// If Pushbullet for WordPress is installed, take over comment notification
		if ( function_exists( 'fnpn_new_comment' ) ) {
			remove_action( 'comment_post', 'fnpn_new_comment', 10, 2 );
			add_action( 'comment_post', array( 'sem_push', 'fnpn_new_comment' ), 11, 2 );
			// For debugging purposes you can enable notifications on edit
			// add_action( 'edit_comment', array('sem_push', 'fnpn_new_comment'), 11, 2);
		}

	}

	/**
	 * Returns an array of comment notification titles to their translated and pretty display versions
	 *
	 * @return array The array of translated post format excerpts.
	 */
	public static function get_notification_titles() {
		$strings = array(
			// special case. any value that evals to false will be considered standard
			'mention'		=> __( '%1$s linked to %2$s',	'semantic_notifications' ),
			'reply'			=> __( '%1$s commented on %2$s',	'semantic_notifications' ),
			'repost'		=> __( '%1$s reposted %2$',		'semantic_notifications' ),
			'like'			=> __( '%1$s liked %2$s',		'semantic_notifications' ),
			'favorite'		=> __( '%1$s favorited %2$s',	'semantic_notifications' ),
			'tag'			=> __( '%1$s tagged %2$s',		'semantic_notifications' ),
			'bookmark'		=> __( '%1$s bookmarked %2$s',	'semantic_notifications' ),
			'rsvp:yes'		=> __( '%1$s is attending %2$s',				'semantic_notifications' ),
			'rsvp:no'		=> __( '%1$s cannot attend %2$s',			'semantic_notifications' ),
			'rsvp:maybe'	=> __( '%1$s may attend %2$s',		'semantic_notifications' ),
			'rsvp:invited'	=> __( '%1$s was invited to %2$s',					'semantic_notifications' ),
			'rsvp:tracking'	=> __( '%1$s is tracking %2$s',			'semantic_notifications' ),
		);
		return $strings;
	}


	/**
	 * Send Notifications for new comments
	 * @param  int $comment_id The ID of the newly submitted comment
	 * @return void
	 */
	public static function ckpn_new_comment( $comment_id ) {
		$options      = ckpn_get_options();
		$args = array();
		$args = self::generate_args( $comment_id );
		if ( ! $args ) {
			return;
		}
		if ( $options['multiple_keys'] ) {
			$args['token'] = ckpn_get_application_key_by_setting( 'new_comment' );
		}
		ckpn_send_notification( $args );

		// Check if we should notify the author as well
		if ( $options['notify_authors'] ) {
			$author_user_key = get_user_meta( $post_data->post_author, 'ckpn_user_key', true );
			if ( $author_user_key != '' && $author_user_key != $options['api_key'] ) { // Only send if the user has a key and it's not the same as the admin key
				// Notify the Author their post has a comment
				$args['user'] = $author_user_key;
				if ( $options['multiple_keys'] ) {
					$args['token'] = ckpn_get_application_key_by_setting( 'notify_authors' );
				}
				ckpn_send_notification( $args );
			}
		}
	}

	/**
	 * Send Notifications for new comments
	 * @param  int $comment_id The ID of the newly submitted comment
	 * @return void
	 */
	public static function fnpn_new_comment( $comment_id ) {
		$options      = fnpn_get_options();
		$args = array();
		$args = self::generate_args( $comment_id );
		if ( ! $args ) {
			return;
		}
		if ( $options['multiple_keys'] ) {
			$args['token'] = fnpn_get_application_key_by_setting( 'new_comment' );
		}
		fnpn_send_notification( $args );

		// Check if we should notify the author as well
		if ( $options['notify_authors'] ) {
			$author_user_key = get_user_meta( $post_data->post_author, 'fnpn_user_key', true );
			if ( $author_user_key != '' && $author_user_key != $options['api_key'] ) { // Only send if the user has a key and it's not the same as the admin key
				// Notify the Author their post has a comment
				$args['user'] = $author_user_key;
				if ( $options['multiple_keys'] ) {
					$args['token'] = fnpn_get_application_key_by_setting( 'notify_authors' );
				}
				fnpn_send_notification( $args );
			}
		}
	}



	public static function generate_args($comment_id) {
		$args = array();
		$comment_data = get_comment( $comment_id );
		// This is not the comment we're looking for. Move Along.
		if ( $comment_data->comment_approved != '1' ) {
			return false;
		}
		$post_title = get_the_title( $comment_data->comment_post_ID );
		$type = get_comment_meta( $comment_id, 'semantic_linkbacks_type', true );
		if ( ! $type ) {
			$type = get_comment_type( $comment_id );
			if ( 'comment' === $type ) {
				$type = 'reply';
			}
		}
		$strings = self::get_notification_titles();
		if ( 'reply' === $type ) {
			$args['message'] = wp_strip_all_tags( $comment_data->comment_content );
			$args['url'] = get_comment_link( $comment_data );
			$args['url_title'] = 'View';
		} else {
			$args['message'] = wp_strip_all_tags( SemanticLinkbacksPlugin::comment_text_excerpt( '', $comment_data ) );
		}
		$args['title'] = sprintf( $strings[$type], get_comment_author( $comment_id ), $post_title );

		$args['title'] = apply_filters( 'ckpn_newcomment_subject', $args['title'], $comment_id );
		$args['message']   = apply_filters( 'ckpn_newcomment_message', $args['message'], $comment_id );
		return $args;
	}



}
?>
