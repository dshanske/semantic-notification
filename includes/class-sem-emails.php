<?php

add_action( 'init' , array( 'sem_emails', 'init' ) );

// The class sets up emails for Semantic Linkbacks
class sem_emails {
	public static function init() {
		add_filter( 'comment_notify_text', array( 'sem_emails', 'semantic_notify_text' ), 10, 2 );
	}

	/**
	 * Filters the text used to display comments in notifications.
	 *
	 * @since 4.x
	 *
	 * @param int $comment_id The Comment ID.
	 * @return string Comment Text.
	 */
	public static function semantic_notify_text( $notify_text, $comment_id ) {
		$type = get_comment_type( $comment_id );
		if ( in_array( $type, array( 'pingback', 'trackback', 'webmention' ) ) ) {
			/* translators: 1: website name, 2: website IP, 3: website hostname */
			if ( ! get_comment_meta( $comment_id, 'semantic_linkbacks_type', true ) ) {
				$notify_text = sprintf( __( 'Semantic Type: %s' ), get_comment_meta( $comment_id, 'semantic_linkbacks_type', true ) ) . "\r\n";
				$notify_text .= sprintf( __( 'Author: %1s(%2s) ' ), get_comment_author( $comment_id ), get_comment_meta( $comment_id, 'semantic_linkbacks_author_url', true ) ) . "\r\n";
				if ( ! get_comment_meta( $comment_id, 'semantic_linkbacks_canonical', true ) ) {
					$notify_text .= sprintf( __( 'Website: %s ' ), get_comment_meta( $comment_id, 'semantic_linkbacks_canonical', true ) ) . "\r\n";
				}
				$notify_text .= sprintf( __( 'Excerpt: %s' ), "\r\n" . get_comment_text( $comment_id ) ) . "\r\n\r\n";
			}
		}
		return $notify_text;
	}

}
?>
