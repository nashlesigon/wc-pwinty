<?php
/**
 * Product Generation Functions for Pwinty Integration.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}	
function pw_load_scripts($hook) {	
$screen       = get_current_screen();
if ( in_array( $screen->id, array( 'product_page_product_attributes' ) ) ) {	
wp_enqueue_script( 'custom-js', plugins_url( 'includes/js/pwinty.js' , dirname(__FILE__) ) );
}
}
add_action('admin_enqueue_scripts', 'pw_load_scripts');

// Check that print_variations is registered in WooCommerce's custom attributes table and register it if not

function woo_print_variations_attribute_check(){
		
global $wpdb;
	
$attributes = wc_get_attribute_taxonomies();

$attributesArray =  json_decode(json_encode($attributes),TRUE);

$printVariationAttributeExists = false;	

foreach ($attributesArray as $attribute) {

if ( $attribute['attribute_name'] == 'print_variations' ) {
	$printVariationAttributeExists = true;		
									
}

}
	
if( !$printVariationAttributeExists ){

	$attributePrintVariation = array (
	'attribute_name' => 'print_variations', 
	'attribute_label' => 'Print Variations', 
	'attribute_type' => 'select', 
	'attribute_orderby' => 'menu_order'
	);
	
	$wpdb->insert( $wpdb->prefix . 'woocommerce_attribute_taxonomies', $attributePrintVariation );
	
    do_action( 'woocommerce_attribute_added', $wpdb->insert_id, $attributePrintVariation );


}
	
	flush_rewrite_rules();
	
	delete_transient( 'wc_attribute_taxonomies' );
	
	return true;
}	

add_action( 'woocommerce_register_taxonomy', 'woo_print_variations_attribute_check', 5 );


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

$uploadDir = wp_upload_dir();

$pwintyUploadDir = $uploadDir['baseurl'].'/'.$settings['pwinty_upload_dir'].'/';

$categories = wp_get_post_terms( $parentPost->ID, 'product_cat', array("fields" => "ids") );

$tags = wp_get_post_terms( $parentPost->ID, 'product_tag', array("fields" => "ids") );

$variationNames = wp_get_post_terms( $parentPost->ID, 'pwinty_print_variations', array("fields" => "names") );

$images = get_post_meta($parentPost->ID, '_wcpwinty_file_list', true);

error_log(print_r($images, true));

foreach ( $images as $imageId => $url ){
	
$image = wp_prepare_attachment_for_js($imageId);
	
$title = str_replace(array("-", "_"), " ", $image['title']);

$postExists = get_page_by_title($title, 'OBJECT', 'product');	

if ($postExists===NULL){
	
$fileName = basename(get_attached_file($image['id']));

$description = strip_shortcodes($parentPost->post_content);

$excerpt = $parentPost->post_excerpt;

$user_ID = get_current_user_id();
	
$newProduct = array(
  'post_status'           => 'publish', 
  'post_type'             => 'product',
  'post_title'            => $title,
  'post_excerpt'          => $excerpt,
  'post_author'           => $user_ID,
  'post_content'          => $description,
  'post_excerpt'          => '',
  'ping_status'           => 'open'
);
	
$newProduct_id = wp_insert_post( $newProduct );

wp_set_object_terms( $newProduct_id, 'variable', 'product_type' );
wp_set_object_terms( $newProduct_id, $categories, 'product_cat' );
update_post_meta( $newProduct_id, '_default_attributes', '' );
wp_set_object_terms( $newProduct_id, $tags, 'product_tag' );
$attribute = Array( 
        'pa_print_variations' => Array(
		  'name'=>'pa_print_variations',
		  'value'=>'',
		  'is_visible' => '0',
		  'position'=> '1', 
		  'is_variation' => '1',
		  'is_taxonomy' => '1'
        ) 
		);
update_post_meta( $newProduct_id, '_product_attributes', $attribute );
wp_set_object_terms( $newProduct_id, $variationNames, 'pa_print_variations' );
update_post_meta( $newProduct_id, '_visibility', 'visible' );
update_post_meta( $newProduct_id, '_stock_status', 'instock' );
update_post_meta( $newProduct_id, '_sku', $image['title'] );
update_post_meta( $newProduct_id, 'download_url', $pwintyUploadDir.$fileName );
update_post_meta( $newProduct_id, '_thumbnail_id', $image['id'] );
$imageMeta = wp_get_attachment_metadata( $image['id'], true );
$shutterSpeed = null;
if ( $imageMeta['image_meta']['shutter_speed'] ){
if ((1 / $imageMeta['image_meta']['shutter_speed']) > 1) {
$shutterSpeed = "1/";
if (number_format((1 / $imageMeta['image_meta']['shutter_speed']), 1) == number_format((1 / $imageMeta['image_meta']['shutter_speed']), 0)) {
$shutterSpeed .= number_format((1 / $imageMeta['image_meta']['shutter_speed']), 0, '.', '') . ' sec';
} else {
$shutterSpeed .= number_format((1 / $imageMeta['image_meta']['shutter_speed']), 1, '.', '') . ' sec';
}
} else { 
$shutterSpeed = $imageMeta['image_meta']['shutter_speed'].' sec';
}
update_post_meta( $newProduct_id, 'shutter_speed', $shutterSpeed );
}
if ( $imageMeta['image_meta']['aperture'] ){
update_post_meta( $newProduct_id, 'aperture', $imageMeta['image_meta']['aperture'] );
}
if ( $imageMeta['image_meta']['iso'] ){
update_post_meta( $newProduct_id, 'iso', $imageMeta['image_meta']['iso'] );
}
if ( $imageMeta['image_meta']['focal_length'] ){
update_post_meta( $newProduct_id, 'focal_length', $imageMeta['image_meta']['focal_length'] );
}
if ( $imageMeta['image_meta']['camera'] ){
update_post_meta( $newProduct_id, 'camera', $imageMeta['image_meta']['camera'] );
}
if ( $imageMeta['image_meta']['credit'] ){
update_post_meta( $newProduct_id, 'credit', $imageMeta['image_meta']['credit'] );
}
if ( $imageMeta['image_meta']['copyright'] ){
update_post_meta( $newProduct_id, 'copyright', $imageMeta['image_meta']['copyright'] );
}
if ( $imageMeta['image_meta']['created_timestamp'] ){
$dateTaken = date( 'g:ia l jS M Y', $imageMeta['image_meta']['created_timestamp']);
update_post_meta( $newProduct_id, 'time_date_taken', $dateTaken );
}


foreach ( $variations as $variation ) {

$newVariation = array(
      'post_title'    => $variation->name,
      'post_status'   => 'publish',
      'post_parent'   => $newProduct_id,
      'post_type'     => 'product_variation'
    );
	
$newVariation_id = wp_insert_post( $newVariation );

$price = Taxonomy_MetaData::get( 'pwinty_print_variations', $variation->term_id, 'print_price' );
update_post_meta( $newVariation_id, '_product_attributes', $attribute );
wp_set_object_terms( $newVariation_id, $variation->slug, 'pa_print_variations' );
update_post_meta( $newVariation_id, 'attribute_pa_print_variations', $variation->slug );
update_post_meta( $newVariation_id, '_visibility', 'visible' );
update_post_meta( $newVariation_id, '_stock_status', 'instock' );
update_post_meta( $newVariation_id, '_sku', $image['title'].'-'.$variation->slug );
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

add_action( 'save_post', 'create_product_from_image', 999  );

// Check pwinty uploads directory exists, create it if not


// Store a full size copy of the image before watermarking in a user defined directory

function pwinty_copy_original_image( $metadata, $attachment_id ) {
	
error_log('Attachment id: '.$attachment_id);

$settings = get_option('woocommerce_woocommerce_pwinty_integration_settings');

$uploadDir = wp_upload_dir();

$pwintyUploadDir = $uploadDir['basedir'].'/'.$settings['pwinty_upload_dir'].'/';

$pwintyUploadDirURL = $uploadDir['baseurl'].'/'.$settings['pwinty_upload_dir'].'/';

if ( !file_exists($pwintyUploadDir) ) {
mkdir( $pwintyUploadDir );
}

$filePath = get_attached_file( $attachment_id );

$fileName = basename($filePath);

error_log('FilePath: '.$filePath);

error_log('FileName: '.$fileName);

if (!file_exists($pwintyUploadDir.$fileName)) {
copy($filePath, $pwintyUploadDir.$fileName);
} 


/* Attempting to make pwinty directory version the full size - breaks thumbnail re-generating with below...

update_attached_file( $attachment_id, $pwintyUploadDir.$fileName );

update_post_meta( $attachment_id, 'guid', $pwintyUploadDir.$fileName );

$oldFile = $metadata["file"];

$metadata["file"] = $settings['pwinty_upload_dir'].'/'.$oldFile ;

unlink( $filePath );

*/

return $metadata;

}

// add_filter( 'wp_generate_attachment_metadata', 'pwinty_copy_original_image', 999, 2 );

// Delete the full size copy if the image is deleted via the media library

function pwinty_delete_original_image_copy( $post_id  ) {

$settings = get_option('woocommerce_woocommerce_pwinty_integration_settings');

$uploadDir = wp_upload_dir();

$pwintyUploadDir = $uploadDir['basedir'].'/'.$settings['pwinty_upload_dir'].'/';

$fileName = basename(get_attached_file($post->ID));

if (file_exists($pwintyUploadDir.$fileName)) {
	
unlink($pwintyUploadDir.$fileName);

} 
return $post_id;
}

add_action( 'delete_attachment', 'pwinty_delete_original_image_copy');

function my_handle_upload ( $params ) {
	
	error_log(print_r($params, true));
	
    $filePath = $params['file'];
	
	
$settings = get_option('woocommerce_woocommerce_pwinty_integration_settings');

$uploadDir = wp_upload_dir();

$pwintyUploadDir = $uploadDir['basedir'].'/'.$settings['pwinty_upload_dir'].'/';

$pwintyUploadDirURL = $uploadDir['baseurl'].'/'.$settings['pwinty_upload_dir'].'/';

if ( !file_exists($pwintyUploadDir) ) {
mkdir( $pwintyUploadDir );
}

$fileName = basename($filePath);

error_log('FilePath: '.$filePath);

error_log('FileName: '.$fileName);

if (!file_exists($pwintyUploadDir.$fileName)) {
copy($filePath, $pwintyUploadDir.$fileName);
} 

    if ( (!is_wp_error($params)) && file_exists($filePath) && in_array($params['type'], array('image/png','image/gif','image/jpeg')))
    {
        $quality                        = 75;
        list($largeWidth, $largeHeight) = array( 1920 , 1920 );
        list($oldWidth, $oldHeight)     = getimagesize( $filePath );
        list($newWidth, $newHeight)     = wp_constrain_dimensions( $oldWidth, $oldHeight, $largeWidth, $largeHeight );

        $resizeImageResult = image_resize( $filePath, $newWidth, $newHeight, false, null, null, $quality);

        unlink( $filePath );

        if ( !is_wp_error( $resizeImageResult ) )
        {
            $newFilePath = $resizeImageResult;
            rename( $newFilePath, $filePath );
        }
        else
        {
            $params = wp_handle_upload_error
            (
                $filePath,
                $resizeImageResult->get_error_message() 
            );
        }
    }

    return $params;
}
add_filter( 'wp_handle_upload', 'my_handle_upload' );