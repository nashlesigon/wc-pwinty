<?php
if ( file_exists( dirname( __FILE__ ) . '/cmb2/init.php' ) ) {
	require_once dirname( __FILE__ ) . '/cmb2/init.php';
} elseif ( file_exists( dirname( __FILE__ ) . '/CMB2/init.php' ) ) {
	require_once dirname( __FILE__ ) . '/CMB2/init.php';
}

add_action( 'cmb2_init', 'wcpwinty_register_metabox' );

function wcpwinty_register_metabox() {
	
	$prefix = '_wcpwinty_';
	
	$cmbPwinty = new_cmb2_box( array(
		'id'            => $prefix . 'metabox',
		'title'         => __( 'Product Generator', 'cmb2' ),
		'object_types'  => array( 'pwinty_album', )
	) );

	$cmbPwinty->add_field( array(
		'name'         => __( 'Album Images', 'cmb2' ),
		'desc'         => __( 'Upload/Manage images', 'cmb2' ),
		'id'           => $prefix . 'file_list',
		'type'         => 'file_list',
		'preview_size' => array( 150, 150 )
	) );
	
	$cmbPwinty->add_field( array(
		'name' => __( 'Add Exif Data as Custom Fields', 'cmb2' ),
		'id'   => $prefix . 'checkbox',
		'type' => 'checkbox',
	) );

}