<?php
function taxonomy_meta_initiate() {

    require_once( 'Taxonomy_MetaData/Taxonomy_MetaData.php' );

    /**
     * Instantiate our taxonomy meta class
     */
    $printPrices = new Taxonomy_MetaData( 'pwinty_print_variations', array(
        // Term option key
        'print_price' => array(
            // Field label
            'label'    => __( 'Set Global Price for Variation', 'taxonomy-metadata' ),
            // Render callback
            'render_cb' => 'taxonomy_metadata_price',
        ),
    ), __( 'Price for Variation', 'taxonomy-metadata' ) /* Settings heading */ );


}
taxonomy_meta_initiate();

/**
 * Textarea callback for Taxonomy_MetaData
 * @param  array  $field Field config
 * @param  mixed  $value Field value
 */
function taxonomy_metadata_textarea( $field, $value ) {
    echo '<textarea class="taxonomy-metadata-textarea" name="'. $field->id .'" id="'. $field->id .'">'. esc_textarea( $value ) .'</textarea>';
    if ( isset( $field->desc ) && $field->desc )
        echo "\n<p class=\"description\">{$field->desc}</p>\n";

}


// Add price column to display of Print Variations taxonomy terms admin table

function add_price_column($defaults){
    $defaults['print_price'] = 'Price';
    return $defaults;
}

add_filter( 'manage_edit-pwinty_print_variations_columns', 'add_price_column', 5);

function add_price_column_content($value, $column_name, $id){
	
$price = Taxonomy_MetaData::get( 'pwinty_print_variations', $id, 'print_price' );
$priceFormatted = number_format($price, 2, '.', '');
$symbol = get_woocommerce_currency_symbol();
return $symbol.$priceFormatted;	
    
}

add_action( 'manage_pwinty_print_variations_custom_column', 'add_price_column_content', 5, 3 );

// Sync changes to print variation price to all existing variations

function sync_variation_prices( $option, $old_value, $value ){

	$isTaxMeta = strpos( $option, 'axonomy_metadata_pwinty_print_variations_' );

	if ( $isTaxMeta = 1 ){
	
	$term_id_text = str_replace("taxonomy_metadata_pwinty_print_variations_", "", $option);
	$term_id = (int) $term_id_text;
	$term = get_term( $term_id, 'pwinty_print_variations' );
	error_log(print_r($term, true));
	$termSlug = (string)$term->slug;
	$newPrice = Taxonomy_MetaData::get( 'pwinty_print_variations', $term_id, 'print_price' );

	$variationProducts = get_posts(array(
    'numberposts' => -1,
    'post_type' => 'product_variation',
    'tax_query' => array(
        array(
        'taxonomy' => 'pa_print_variations', 
        'field' => 'slug',
        'terms' => $termSlug )
		                 )
						       )
                            );
							
	foreach ( $variationProducts as $product ) {
		update_post_meta( $product->ID, '_price', $newPrice );
		update_post_meta( $product->ID, '_regular_price', $newPrice );
		wc_delete_product_transients( $product->ID );
		wc_delete_product_transients( $product->post_parent );
		delete_post_meta( $product->post_parent, '_min_price_variation_id' );
	}
	}
	
}

add_action( 'updated_option', 'sync_variation_prices', 99, 3 );