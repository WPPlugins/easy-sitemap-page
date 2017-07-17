<?php
/*
Plugin Name: Easy Sitemap Page
Plugin URI: http://www.northernhouseinspections.com.au/
Description: Add responsive sitemap in page using simple shortcode. No any extra setup required. Easy to customize.
Version: 1.0
Author: Northern House Inspections
Author URI: http://www.northernhouseinspections.com.au/
License: GNU General Public License v3 or later
License URI: http://www.gnu.org/licenses/gpl-3.0.html
*/

add_shortcode( 'easy-sitemap-page', 'easy_sitemap_page' );
add_shortcode( 'easy-sitemap-page-group', 'easy_sitemap_page_group' );
add_action( 'admin_init', 'esp_init' );
add_action( 'admin_menu', 'esp_add_options_page' );
add_filter( 'plugin_action_links', 'esp_plugin_settings_link', 10, 2 );
add_filter( 'widget_text', 'do_shortcode' ); // make sitemap shortcode work in text widgets
add_action( 'plugins_loaded', 'esp_localize_plugin' );


/* Init plugin options to white list our options. */
function esp_init() {
	register_setting( 'esp_plugin_options', 'wpss_options', 'esp_validate_options' );
}

/* Add menu page. */
function esp_add_options_page() {
	add_options_page( __( 'Easy Sitemap Page Options Page', 'easy-sitemap-page' ), __( 'Easy Sitemap Page', 'easy-sitemap-page' ), 'manage_options', __FILE__, 'easy_sitemap_page_form' );
}

/* Draw the menu page itself. */
function easy_sitemap_page_form() {
?>
	<div class="wrap">
		<h2><?php _e( 'Easy Sitemap Page', 'easy-sitemap-page' ); ?></h2>

		<div style="background:#fff;border: 1px dashed #ccc;font-size: 13px;margin: 20px 0 10px 0;padding: 5px 0 5px 8px;">
			<?php printf( __( 'To display the Easy Sitemap Page on a post, page, or sidebar (via a Text widget), enter the following shortcode:<br><br>', 'easy-sitemap-page' ) ); ?> <code>[easy-sitemap-page]</code><br><br>
		</div>

		<h2><?php _e( 'Choose your Post Types to Display in Sitemap', 'easy_sitemap_page' ); ?></h2>

		<div style="background:#fff;border: 1px dashed #ccc;font-size: 13px;margin: 20px 0 10px 0;padding: 5px 0 5px 8px;">
			<?php printf( __( 'Specify post types and order.<br>', 'easy_sitemap_page' ) ); ?>
			<br><code>e.g. [easy-sitemap-page types="post, page, testimonial, download"]</code><br><br>
			<b>default: types="post, page"</b>
			<br><br><?php printf( __( 'Choose from any of the following registered post types currently available:<br><br>', 'easy_sitemap_page' ) ); ?>
			<?php
			$esp_registered_post_types = get_post_types();
			$esp_registered_post_types_str = implode(', ', $esp_registered_post_types);
			echo '<code>' . $esp_registered_post_types_str . '</code><br><br>';
			
			printf( __( 'Here all post type & custom post types shortcode:<br><br>', 'easy_sitemap_page' ) ); ?>
			<code>[easy-sitemap-page types="<?php echo $esp_registered_post_types_str; ?>"]</code><br><br>
						
		</div>

		<h2><?php _e( 'Format Your Sitemap Output', 'easy_sitemap_page' ); ?></h2>

		<div style="background:#fff;border: 1px dashed #ccc;font-size: 13px;margin: 20px 0 10px 0;padding: 5px 0 5px 8px;">
			
			<br><code>e.g. [easy-sitemap-page-group show_label="true" links="true" page_depth="1" order="asc" orderby="title" exclude="1,2,3"]</code>
			<br><br><b>defaults:<br>
			show_label="true"<br>
			links="true"<br>
			page_depth="0"<br>
			order="asc"<br>
			orderby="title"<br>
			exclude=""<br><br></b>
		</div>
	</div>
<?php
}

/* Shortcode generate. */
function easy_sitemap_page($args) {

	/* Get attributes from the shortcode. */
	extract( shortcode_atts( array(
		'types' => 'page',
		'show_excerpt' => 'false',
		'title_tag' => '',
		'excerpt_tag' => 'div',
		'esp_post_type_tag' => 'h2',
		'show_label' => 'true',
		'links' => 'true',
		'page_depth' => 0,
		'order' => 'asc',
		'orderby' => 'title',
		'exclude' => ''
	), $args ) );

	// escape tag names
	$esp_title_tag = tag_escape( $esp_title_tag );
	$esp_excerpt_tag = tag_escape( $esp_excerpt_tag );
	$esp_post_type_tag = tag_escape( $esp_post_type_tag );

	$esp_page_depth = intval( $esp_page_depth );
	$esp_post_types = $types; // allows the use of the shorter 'types' rather than 'post_types' in the shortcode

	ob_start();

	// *************
	// CONTENT START
	// *************

	$esp_post_types = array_map( 'trim', explode( ',', $esp_post_types ) ); // convert comma separated string to array
	$esp_exclude = array_map( 'trim', explode( ',', $esp_exclude) ); // must be array to work in the post query
	$esp_registered_post_types = get_post_types();

	foreach( $esp_post_types as $esp_post_type ) :

		// generate <ul> element class
		$ul_class = 'easy_sitemap_page-' . $esp_post_type;

		// bail if post type isn't valid
		if( !array_key_exists( $esp_post_type, $esp_registered_post_types ) ) {
			break;
		}

		// set opening and closing title tag
		if( !empty($esp_title_tag) ) {
			$title_open = '<' . $esp_title_tag . '>';
			$title_close = '</' . $esp_title_tag . '>';
		}
		else {
			$title_open = $title_close = '';
		}

		// conditionally show label for each post type
		if( $show_label == 'true' ) {
			$post_type_obj  = get_post_type_object( $esp_post_type );
			$post_type_name = $post_type_obj->labels->name;
			echo '<' . $esp_post_type_tag . '>' . esc_html($post_type_name) . '</' . $esp_post_type_tag . '>';
		}

		$query_args = array(
			'posts_per_page' => -1,
			'post_type' => $esp_post_type,
			'order' => $order,
			'orderby' => $orderby,
			'post__not_in' => $esp_exclude
		);

		// use custom rendering for 'page' post type to properly render sub pages
		if( $esp_post_type == 'page' ) {
			$arr = array(
				'title_tag' => $esp_title_tag,
				'links' => $links,
				'title_open' => $title_open,
				'title_close' => $title_close,
				'page_depth' => $esp_page_depth,
				'exclude' => $esp_exclude
			);
			echo '<ul class="' . esc_attr($ul_class) . '">';
			esp_list_pages($arr, $query_args);
			echo '</ul>';
			continue;
		}

		//post query
		$esp_sitemap_query = new WP_Query( $query_args );

		if ( $esp_sitemap_query->have_posts() ) :

			echo '<ul class="' . esc_attr($ul_class) . '">';

			// start of the loop
			while ( $esp_sitemap_query->have_posts() ) : $esp_sitemap_query->the_post();

				// title
				$title_text = get_the_title();

				if( !empty( $title_text ) ) {
					if ( $links == 'true' ) {
						$title = $title_open . '<a href="' . esc_url(get_permalink()) . '">' . esc_html($title_text) . '</a>' . $title_close;
					} else {
						$title = $title_open . esc_html($title_text) . $title_close;
					}
				}
				else {
					if ( $links == 'true' ) {
						$title = $title_open . '<a href="' . esc_url(get_permalink()) . '">' . '(no title)' . '</a>' . $title_close;
					} else {
						$title = $title_open . '(no title)' . $title_close;
					}
				}

				// excerpt
				$excerpt = $show_excerpt == 'true' ? '<' . $esp_excerpt_tag . '>' . esc_html(get_the_excerpt()) . '</' . $esp_excerpt_tag . '>' : '';

				// render list item
				echo '<li>';
				echo $title;
				echo $excerpt;
				echo '</li>';

			endwhile; // end of post loop -->

			echo '</ul>';

			// put pagination functions here
			wp_reset_postdata();

		else:

			echo '<p>' . __( 'Sorry, no posts matched your criteria.', 'easy-sitemap-page' ) . '</p>';

		endif;

	endforeach;

	// ***********
	// CONTENT END
	// ***********

	$sitemap = ob_get_contents();
	ob_end_clean();

	return wp_kses_post($sitemap);
}

/* Shortcode function. */
function easy_sitemap_page_group($args) {

	/* Get slider attributes from the shortcode. */
	extract( shortcode_atts( array(
		'tax' => 'category', // single taxonomy
		'term_order' => 'asc',
		'term_orderby' => 'name',
		'show_excerpt' => 'false',
		'title_tag' => '',
		'excerpt_tag' => 'div',
		'esp_post_type_tag' => 'h2',
		'show_label' => 'true',
		'links' => 'true',
		'page_depth' => 0,
		'order' => 'asc',
		'orderby' => 'title',
		'exclude' => ''
	), $args ) );

	// escape tag names
	$esp_title_tag = tag_escape( $esp_title_tag );
	$esp_excerpt_tag = tag_escape( $esp_excerpt_tag );
	$esp_post_type_tag = tag_escape( $esp_post_type_tag );

	$esp_page_depth = intval( $esp_page_depth );
	$esp_post_type = 'post';

	// Start output caching (so that existing content in the [easy_sitemap_page] post doesn't get shoved to the bottom of the post
	ob_start();

	// *************
	// CONTENT START
	// *************

	$esp_exclude = array_map( 'trim', explode( ',', $esp_exclude) ); // must be array to work in the post query
	$esp_registered_post_types = get_post_types();

	$esp_taxonomy_arr = get_object_taxonomies( $esp_post_type );

	// sort via specified taxonomy
	if ( !empty($tax) && in_array( $tax, $esp_taxonomy_arr ) ) {

		// conditionally show label for each post type
		if( $show_label == 'true' ) {
			$post_type_obj  = get_post_type_object( $esp_post_type );
			$post_type_name = $post_type_obj->labels->name;
			echo '<' . $esp_post_type_tag . '>' . esc_html($post_type_name) . '</' . $esp_post_type_tag . '>';
		}

		$term_attr = array(
			'orderby'           => $term_orderby,
			'order'             => $term_order
		);
		$terms = get_terms( $tax, $term_attr );

		foreach($terms as $term) {

			// generate <ul> element class
			$ul_class = 'easy_sitemap_page-' . $esp_post_type;

			// bail if post type isn't valid
			if( !array_key_exists( $esp_post_type, $esp_registered_post_types ) ) {
				break;
			}

			// set opening and closing title tag
			if( !empty($esp_title_tag) ) {
				$title_open = '<' . $esp_title_tag . '>';
				$title_close = '</' . $esp_title_tag . '>';
			}
			else {
				$title_open = $title_close = '';
			}

			$query_args = array(
				'posts_per_page' => -1,
				'post_type' => $esp_post_type,
				'order' => $order,
				'orderby' => $orderby,
				'post__not_in' => $esp_exclude,
				'tax_query' => array(
					array(
						'taxonomy' => $tax,
						'field' => 'slug',
						'terms' => $term
					)
				)
			);

			echo '<h4>' . $term->name . '</h4>';

			//post query
			$esp_sitemap_query = new WP_Query( $query_args );

			if ( $esp_sitemap_query->have_posts() ) :

				echo '<ul class="' . esc_attr($ul_class) . '">';

				// start of the loop
				while ( $esp_sitemap_query->have_posts() ) : $esp_sitemap_query->the_post();

					// title
					$title_text = get_the_title();

					if( !empty( $title_text ) ) {
						if ( $links == 'true' ) {
							$title = $title_open . '<a href="' . esc_url(get_permalink()) . '">' . esc_html($title_text) . '</a>' . $title_close;
						} else {
							$title = $title_open . esc_html($title_text) . $title_close;
						}
					}
					else {
						if ( $links == 'true' ) {
							$title = $title_open . '<a href="' . esc_url(get_permalink()) . '">' . '(no title)' . '</a>' . $title_close;
						} else {
							$title = $title_open . '(no title)' . $title_close;
						}
					}

					// excerpt
					$excerpt = $show_excerpt == 'true' ? '<' . $esp_excerpt_tag . '>' . esc_html(get_the_excerpt()) . '</' . $esp_excerpt_tag . '>' : '';

					// render list item
					echo '<li>';
					echo $title;
					echo $excerpt;
					echo '</li>';

				endwhile; // end of post loop -->

				echo '</ul>';

				// put pagination functions here
				wp_reset_postdata();

			else:

				echo '<p>' . __( 'Sorry, no posts matched your criteria.', 'easy-sitemap-page' ) . '</p>';

			endif;
		}
	}
	else {
		echo "No posts found.";
	}

	// ***********
	// CONTENT END
	// ***********

	$sitemap = ob_get_contents();
	ob_end_clean();

	return wp_kses_post($sitemap);
}

function esp_list_pages( $arr, $query_args ) {

	$map_args = array(
		'title' => 'post_title',
		'date' => 'post_date',
		'author' => 'post_author',
		'modified' => 'post_modified'
	);

	// modify the query args for get_pages() if necessary
	$orderby = array_key_exists( $query_args['orderby'], $map_args ) ? $map_args[$query_args['orderby']] : $query_args['orderby'];

	$r = array(
		'depth' => $arr['page_depth'],
		'show_date' => '',
		'date_format' => get_option( 'date_format' ),
		'child_of' => 0,
		'exclude' => $arr['exclude'],
		'echo' => 1,
		'authors' => '',
		'sort_column' => $orderby,
		'sort_order' => $query_args['order'],
		'link_before' => '',
		'link_after' => '',
        'item_spacing' => '',
		//'walker' => '',
	);

	$output = '';
	$current_page = 0;
	$r['exclude'] = preg_replace( '/[^0-9,]/', '', $r['exclude'] ); // sanitize, mostly to keep spaces out

	// Query pages.
	$r['hierarchical'] = 0;
	$pages = get_pages( $r );

	if ( ! empty( $pages ) ) {
		global $wp_query;
		if ( is_page() || is_attachment() || $wp_query->is_posts_page ) {
			$current_page = get_queried_object_id();
		} elseif ( is_singular() ) {
			$queried_object = get_queried_object();
			if ( is_post_type_hierarchical( $queried_object->post_type ) ) {
				$current_page = $queried_object->ID;
			}
		}

		$output .= walk_page_tree( $pages, $r['depth'], $current_page, $r );
	}

	// remove links
	if( $arr['links'] != 'true' )
		$output = preg_replace('/<a href=\"(.*?)\">(.*?)<\/a>/', "\\2", $output);

	if ( $r['echo'] ) {
		echo $output;
	} else {
		return $output;
	}
}

// Display a Settings link on the main Plugins page
function esp_plugin_settings_link( $links, $file ) {

	if ( $file == plugin_basename( __FILE__ ) ) {
		$posk_links = '<a style="color:limegreen;" href="http://www.northernhouseinspections.com.au/" target="_blank" title="Easy Sitemap Page"><span class="dashicons dashicons-awards"></span></a> | ';
		$posk_links .= '<a href="' . esc_url(get_admin_url() . 'options-general.php?page=easy-sitemap-page/easy-sitemap-page' ) . '">' . __( 'Settings', 'easy-sitemap-page' ) . '</a>';
		// make the 'Settings' link appear first
		array_unshift( $links, $posk_links );
	}

	return $links;
}

/* Sanitize and validate input. Accepts an array, return a sanitized array. */
function esp_validate_options( $input ) {
	// Strip html from textboxes
	// e.g. $input['textbox'] =  wp_filter_nohtml_kses($input['textbox']);

	$input['txt_page_ids'] = sanitize_text_field( $input['txt_page_ids'] );

	return $input;
}

/**
 * Add Plugin localization support.
 */
function esp_localize_plugin() {

	load_plugin_textdomain( 'easy-sitemap-page', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}