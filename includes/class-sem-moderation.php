<?php

	add_filter( 'admin_init', array( 'sem_moderation', 'admin_init' ), 10, 4 );
	add_filter( 'semantic_linkbacks_commentdata', array( 'sem_moderation', 'moderate_linkbacks' ) );

class sem_moderation {

	/**
	 * Add Settings to the Discussions Page
	 */
	public static function admin_init() {
		register_setting(
			'discussion', // settings page
			'iwcfun_options' // option name
		);
		add_settings_field(
			'whitelist_keys', // id
			'Domain Whitelist for Linkbacks', // setting title
			array( 'sem_moderation', 'whitelist_input' ), // display callback
			'discussion', // settings page
			'default' // settings section
		);
	}
	/*
	* Display the WhiteList Field
	*
	*/
	public static function whitelist_input() {
		$options = get_option( 'iwcfun_options' );
		$name = 'whitelist_keys';
		echo "<p><label for='whitelist_keys'> One domain name per line.</label></p>";
		echo "<textarea name='iwcfun_options[$name]' rows='10' cols='50' class='large-text code'>";
		if ( ! empty( $options['whitelist_keys'] ) ) {echo $options['whitelist_keys']; }
		echo '</textarea>';
	}

	/**
	 *
	 * @param array $author_url
	 *
	 * @return boolean
	 */
	function whitelist_approved($url) {
		if ( empty( $url ) ) { return false; }
		$options = get_option( 'iwcfun_options' );
		$mod_keys = trim( $options['whitelist_keys'] );
		$host = parse_url( $url, PHP_URL_HOST );
		// strip leading www, if any
		$host = preg_replace( '/^www\./', '', $host );
		if ( '' == $mod_keys ) {
			return false;
		}
		$domains = explode( "\n", $mod_keys );
		foreach ( (array) $domains as $domain ) {
			$domain = trim( $domain );
			if ( empty( $domain ) ) { continue; }
			if ( strcasecmp( $domain, $host ) == 0 ) {
				return true;
			}
		}
		return false;
	}

	function moderate_linkbacks($commentdata, $target, $html) {
		if ( whitelist_approved( $commentdata['_canonical'] ) ) {
			$commentdata['comment_approved'] = 1;
		}
		 return $commentdata;
	}

}
