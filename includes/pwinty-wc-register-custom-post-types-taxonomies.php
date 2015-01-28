<?php
/**
 * Taxonomy, Post Type and Status registration related functions.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

// Register the Pwinty Album Custom Post Type
function pwinty_album_post_type() {

	$labels = array(
		'name'                => _x( 'Pwinty Albums', 'Post Type General Name', 'pwinty_text_domain' ),
		'singular_name'       => _x( 'Pwinty Album', 'Post Type Singular Name', 'pwinty_text_domain' ),
		'menu_name'           => __( 'Pwinty Albums', 'pwinty_text_domain' ),
		'parent_item_colon'   => __( 'Parent Album', 'pwinty_text_domain' ),
		'all_items'           => __( 'All Pwinty Albums', 'pwinty_text_domain' ),
		'view_item'           => __( 'View Album', 'pwinty_text_domain' ),
		'add_new_item'        => __( 'Add New Album', 'pwinty_text_domain' ),
		'add_new'             => __( 'Add New', 'pwinty_text_domain' ),
		'edit_item'           => __( 'Edit Album', 'pwinty_text_domain' ),
		'update_item'         => __( 'Update Album', 'pwinty_text_domain' ),
		'search_items'        => __( 'Search Albums', 'pwinty_text_domain' ),
		'not_found'           => __( 'Not found', 'pwinty_text_domain' ),
		'not_found_in_trash'  => __( 'Not found in Trash', 'pwinty_text_domain' ),
	);
	$args = array(
		'label'               => __( 'pwinty_album', 'pwinty_text_domain' ),
		'description'         => __( 'Pwinty Album', 'pwinty_text_domain' ),
		'labels'              => $labels,
		'supports'            => array( 'title', 'editor', 'excerpt', 'custom-fields', ),
		'hierarchical'        => false,
		'public'              => true,
		'show_ui'             => true,
		'show_in_menu'        => true,
		'show_in_nav_menus'   => false,
		'show_in_admin_bar'   => true,
		'menu_position'       => 56,
		'menu_icon'           => 'dashicons-book-alt',
		'can_export'          => true,
		'has_archive'         => false,
		'exclude_from_search' => true,
		'publicly_queryable'  => false,
		'capability_type'     => 'post',
		'taxonomies' => array('pwinty_print_variations')
	);
	register_post_type( 'pwinty_album', $args );
	

}

function add_wc_category_tags_to_pwinty_album(){
register_taxonomy_for_object_type( 'product_cat', 'pwinty_album' );
register_taxonomy_for_object_type( 'product_tag', 'pwinty_album' );
}

// Register the Print Variations taxonomy
 function pwinty_print_variations_taxonomy() {

	$labels = array(
		'name'                       => _x( 'Print Variations', 'Taxonomy General Name', 'pwinty_text_domain' ),
		'singular_name'              => _x( 'Print Variation', 'Taxonomy Singular Name', 'pwinty_text_domain' ),
		'menu_name'                  => __( 'Print Variations', 'pwinty_text_domain' ),
		'all_items'                  => __( 'All Print Variations', 'pwinty_text_domain' ),
		'parent_item'                => __( 'Parent Print Variation', 'pwinty_text_domain' ),
		'parent_item_colon'          => __( 'Parent Print Variation:', 'pwinty_text_domain' ),
		'new_item_name'              => __( 'New Print Variation', 'pwinty_text_domain' ),
		'add_new_item'               => __( 'Add New Print Variation', 'pwinty_text_domain' ),
		'edit_item'                  => __( 'Edit Print Variation', 'pwinty_text_domain' ),
		'update_item'                => __( 'Update Print Variation', 'pwinty_text_domain' ),
		'separate_items_with_commas' => __( 'Separate items with commas', 'pwinty_text_domain' ),
		'search_items'               => __( 'Search Print Variations', 'pwinty_text_domain' ),
		'add_or_remove_items'        => __( 'Add or remove Print Variations', 'pwinty_text_domain' ),
		'choose_from_most_used'      => __( 'Choose from the most used items', 'pwinty_text_domain' ),
		'not_found'                  => __( 'Not Found', 'pwinty_text_domain' ),
	);
	$args = array(
		'labels'                     => $labels,
		'hierarchical'               => true,
		'public'                     => true,
		'show_ui'                    => true,
		'show_admin_column'          => false,
		'show_in_nav_menus'          => false,
		'show_tagcloud'              => false,
	);
	register_taxonomy( 'pwinty_print_variations', array('pwinty_album'), $args );

}




// Register submitted status

function register_submitted_status() {
    register_post_status( 'wc-submitted', array(
        'label'                     => 'Submitted',
        'public'                    => true,
        'exclude_from_search'       => false,
        'show_in_admin_all_list'    => true,
        'show_in_admin_status_list' => true,
        'label_count'               => _n_noop( 'Submitted <span class="count">(%s)</span>', 'Submitted <span class="count">(%s)</span>' )
    ) );
}


// Add submitted to internal list of WC Order statuses
function add_submitted_to_order_statuses( $order_statuses ) {

    $new_order_statuses = array();

    // add new order status after processing
    foreach ( $order_statuses as $key => $status ) {

        $new_order_statuses[ $key ] = $status;

        if ( 'wc-processing' === $key ) {
            $new_order_statuses['wc-submitted'] = 'Submitted';
        }
    }

    return $new_order_statuses;
}

// Add custom order submit action button and remove complete button
function add_submit_to_order_admin_actions($actions) {
			
	  global $post;
	  global $the_order;  // globals maybe not ideal but unsure how to do better, only way I could get $the_order->has_status and $post->ID populated
	  
	  $actions = array();
	  if ( $the_order->has_status( array( 'pending', 'on-hold' ) ) ) {
		  $actions['processing'] = array(
			  'url'       => wp_nonce_url( admin_url( 'admin-ajax.php?action=woocommerce_mark_order_status&status=processing&order_id=' . $post->ID ), 'woocommerce-mark-order-status' ),
			  'name'      => __( 'Processing', 'woocommerce' ),
			  'action'    => "processing"
		  );
	  }
	  if ( $the_order->has_status( 'processing' ) ) {
		  $actions['submitted'] = array(
			  'url'       => wp_nonce_url( admin_url( 'admin-ajax.php?action=woocommerce_mark_order_status&status=submitted&order_id=' . $post->ID ), 'woocommerce-mark-order-status' ),
			  'name'      => __( 'Submit for Printing ', 'woocommerce' ),
			  'action'    => "submitted"
		  );
	  }

	  unset($actions['complete']);

	  $actions['view'] = array(
		  'url'       => admin_url( 'post.php?post=' . $post->ID . '&action=edit' ),
		  'name'      => __( 'View', 'woocommerce' ),
		  'action'    => "view"
	  );
	  
	  return $actions;
}
	

// Remove links to categories and tags from Pwinty Albums sub menu	
function pwinty_album_remove_woo_menu_pages() {

remove_submenu_page( 'edit.php?post_type=pwinty_album', 'edit-tags.php?taxonomy=product_cat&amp;post_type=pwinty_album' );

remove_submenu_page( 'edit.php?post_type=pwinty_album', 'edit-tags.php?taxonomy=product_tag&amp;post_type=pwinty_album' );

}

// Add price column to display of Print Variations taxonomy terms admin table
function add_price_column($defaults){
    $defaults['price'] = 'Price';
    return $defaults;
}

function add_price_column_content($value, $column_name, $id){
	
$price = get_tax_meta($id,'print_variation_price');
$priceFormatted = number_format($price, 2, '.', '');
$symbol = get_woocommerce_currency_symbol();
return $symbol.$priceFormatted;	
    
}



