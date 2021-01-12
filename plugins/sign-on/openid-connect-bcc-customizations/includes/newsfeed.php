<?php
/**
 * This file contains all the logic related to the newsfeed, i.e:
 * Creating a private newsfeed link (with a GUID)
 * Disabling all the other newfeed links
 * Unprotecting the private url
 * Adding the featured image of articles to the RSS feed
 */

/**
 * Computes the private feed link
 */
function get_private_link_feed(){
	return '/feed?id=' . get_option('private_newsfeed_link');
 }

/**
 * Checks if the private link feed is registered within this Wordpress instance
 */
function private_link_feed() {
	global $wp;
	$url = home_url(add_query_arg(array($_GET), $wp->request));
	if($url != home_url(get_private_link_feed())){
		wp_die(__('No feed available'));  
	}
}

/**
 * Adds feed if private newsfeeds is checked
 */
if (get_option('private_newsfeeds') == 1) {
	add_action('do_feed', 'private_link_feed', 1);
	add_action('do_feed_rdf', 'private_link_feed', 1);
	add_action('do_feed_rss', 'private_link_feed', 1);
	add_action('do_feed_rss2', 'private_link_feed', 1);
	add_action('do_feed_atom', 'private_link_feed', 1);

	// Remove feed links from header
	remove_action( 'wp_head', 'feed_links_extra', 3 ); 
	remove_action( 'wp_head', 'feed_links', 2 );

	// Unprotect the endpoint
	add_filter('bcc_unprotected_urls', function($urls){
		$urls[] = home_url( get_private_link_feed());
		return $urls;
	});
}

/**
 * Adds the featured image to the RSS feed
 */
function featuredImagetoRSS($content) {
	global $post;
	if ( has_post_thumbnail( $post->ID ) )
		$content = '<div>' . get_the_post_thumbnail( $post->ID, 'medium') . '</div>' . $content;
	return $content;
}
add_filter('the_excerpt_rss', 'featuredImagetoRSS');

?>