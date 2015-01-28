<?php
/**
 * Product Generation Functions for Pwinty Integration.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}		


// Check that print_variations is registered in woocommerce's custom attributes table
function woo_print_variations_attribute_check(){
	
$taxonomies = wc_get_attribute_taxonomies();

$taxonomiesArray = (array)$taxonomies;

$filteredArray = array_filter($taxonomiesArray, function($item){

    return $item->attribute_name == 'print_variations';

});

if( count($filteredArray) < 1 ){
	
	global $wpdb;
	
	$attribute = array (
	'attribute_name' => 'print_variations', 
	'attribute_label' => 'Print Variations', 
	'attribute_type' => 'select', 
	'attribute_orderby' => 'menu_order'
	);
	
	$wpdb->insert( $wpdb->prefix . 'woocommerce_attribute_taxonomies', $attribute );
	
    do_action( 'woocommerce_attribute_added', $wpdb->insert_id, $attribute );
	
	flush_rewrite_rules();
	
	delete_transient( 'wc_attribute_taxonomies' );
	
	return true;

}
	
}

// Product generator
function create_product_from_image(  $post_id  ) {

$parentPost = get_post($post_id);	
	
if ( 'pwinty_album' != $parentPost->post_type ) {
        return;
    }	
	
$variations = wp_get_post_terms( $parentPost->ID, 'pwinty_print_variations' );

foreach ( $variations as $variation ) {
	
$term = term_exists($variation->name, 'pa_print_variations');
if (!$term) {
wp_insert_term( $variation->name, 'pa_print_variations', $args = array( 'slug' => $variation->slug ) );
}
}

$settings = get_option('woocommerce_woocommerce_pwinty_integration_settings');

$pwintyUploadDir = trailingslashit(  SITE_URL ).trailingslashit( $settings['pwinty_upload_dir'] );

$categories = wp_get_post_terms( $parentPost->ID, 'product_cat', array("fields" => "ids") );

$tags = wp_get_post_terms( $parentPost->ID, 'product_tag', array("fields" => "ids") );

$variationNames = wp_get_post_terms( $parentPost->ID, 'pwinty_print_variations', array("fields" => "names") );

$images = get_attached_media( 'image', $parentPost->ID );
	

foreach ($images as $image){
	
$title = str_replace(array("-", "_"), " ", $image->post_title);

$postExists = get_page_by_title($title, 'OBJECT', 'product');	

if ($postExists===NULL){
	
$fileName = basename(get_attached_file($image->ID));

$description = strip_shortcodes($parentPost->post_content);

$user_ID = get_current_user_id();
	
$newProduct = array(
  'post_status'           => 'publish', 
  'post_type'             => 'product',
  'post_title'            => $title,
  'post_author'           => $user_ID,
  'post_content'          => $description,
  'post_excerpt'          => '',
  'ping_status'           => 'open'
);
	
$newProduct_id = wp_insert_post( $newProduct );

wp_set_object_terms ($newProduct_id, 'variable', 'product_type');
wp_set_object_terms ($newProduct_id, $categories, 'product_cat');
update_post_meta( $newProduct_id, '_default_attributes', '' );
wp_set_object_terms ($newProduct_id, $tags, 'product_tag');
$attribute = Array('pa_print_variations'=>Array(
        'name'=>'pa_print_variations',
        'value'=>'',
        'is_visible' => '0',
		'position'=> '1', 
        'is_variation' => '1',
        'is_taxonomy' => '1'
        ));
update_post_meta( $newProduct_id, '_product_attributes', $attribute );
wp_set_object_terms($newProduct_id, $variationNames, 'pa_print_variations');
update_post_meta( $newProduct_id, '_visibility', 'visible' );
update_post_meta( $newProduct_id, '_stock_status', 'instock' );
update_post_meta( $newProduct_id, '_sku', $image->post_title );
update_post_meta( $newProduct_id, 'download_url', $pwintyUploadDir.$fileName );
update_post_meta( $newProduct_id, '_thumbnail_id', $image->ID );

foreach ( $variations as $variation ) {

$price = get_tax_meta($variation->term_id,'print_variation_price');
 
$newVariation = array(
      'post_title'    => $variation->name,
      'post_status'   => 'publish',
      'post_parent'   => $newProduct_id,
      'post_type'     => 'product_variation'
    );
	
$newVariation_id = wp_insert_post( $newVariation );

$price = get_tax_meta($variation->term_id,'print_variation_price');
update_post_meta( $newVariation_id, '_product_attributes', $attribute );
wp_set_object_terms( $newVariation_id, $variation->slug, 'pa_print_variations' );
update_post_meta( $newVariation_id, 'attribute_pa_print_variations', $variation->slug );
update_post_meta( $newVariation_id, '_visibility', 'visible' );
update_post_meta( $newVariation_id, '_stock_status', 'instock' );
update_post_meta( $newVariation_id, '_sku', $image->post_title.'-'.$variation->slug );
update_post_meta( $newVariation_id, 'download_url', $pwintyUploadDir.$fileName );
update_post_meta( $newVariation_id, '_price', $price );
update_post_meta( $newVariation_id, '_regular_price', $price );
update_post_meta( $newVariation_id, '_manage_stock', 'no' );
update_post_meta( $newVariation_id, '_downloadable', 'no' );
update_post_meta( $newVariation_id, '_virtual', 'no' );
update_post_meta( $newVariation_id, '_thumbnail_id', 0 );

}	
}
} 
}

// Store a full size copy of the image before watermarking in a user defined directory
function pwinty_copy_original_image($attachment_ID) {
	
error_log($attachment_ID, 0);
	
$post = get_post($attachment_ID);

$test = print_r($post, true);

error_log($test, 0);

$parentPost = get_post($post->post_parent);
	
if ( 'pwinty_album' != $parentPost->post_type ) {
        return;
    }	
	
$settings = get_option('woocommerce_woocommerce_pwinty_integration_settings');

$pwintyUploadDir = trailingslashit(  ABSPATH ).trailingslashit( $settings['pwinty_upload_dir'] );

$contentDir = trailingslashit( content_url() );

if (!file_exists($pwintyUploadDir)) {
    mkdir($pwintyUploadDir, 0777, true);
	copy( plugin_dir_path( __FILE__ ).'index.php', $pwintyUploadDir.'index.php' );
} 

$filePath = get_attached_file($post->ID);
$fileName = basename(get_attached_file($post->ID));
error_log('File Path: '.$filePath, 0);
error_log('New File: '.$pwintyUploadDir.$fileName, 0);
if (!file_exists($pwintyUploadDir.$fileName)) {
copy($filePath, $pwintyUploadDir.$fileName);
} 
return $attachment_ID;
}


// Delete the full size copy if the image is deleted via the media library
function pwinty_delete_original_image_copy($attachment_ID) {
	
$post = get_post($attachment_ID);

$parentPost = get_post($post->post_parent);
	
if ( 'pwinty_album' != $parentPost->post_type ) {
        return;
    }	
	
$settings = get_option('woocommerce_woocommerce_pwinty_integration_settings');

$pwintyUploadDir = trailingslashit(  ABSPATH ).trailingslashit($settings['pwinty_upload_dir']);

$filePath = get_attached_file($post->ID);
$fileName = basename(get_attached_file($post->ID));
if (file_exists($pwintyUploadDir.$fileName)) {
unlink($pwintyUploadDir.$fileName);
} 
return $attachment_ID;
}

// Sync changes to print variation price to all existing variations
function sync_variation_prices( $term_id, $tt_id ){
	$term = get_term($term_id, 'pwinty_print_variations');
	$slug = $term->slug;
	$price = get_tax_meta($term_id,'print_variation_price');
	$variationProducts = get_posts(array(
    'numberposts' => -1,
    'post_type' => 'product_variation',
    'tax_query' => array(
        array(
        'taxonomy' => 'pa_print_variations',
        'field' => 'slug',
        'terms' => $slug )
		                 )
						       )
                            );
							
	foreach ( $variationProducts as $product ) {
		
		update_post_meta( $variationProducts->ID, '_price', $price );
		
	}
	
	
}




