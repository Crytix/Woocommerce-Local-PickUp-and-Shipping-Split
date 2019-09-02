<?php
/**
 * This function loops over cart items, and moves any item with shipping class 'special-class' into a new package. 
 * The new package only takes local pickup.
 */
function split_special_shipping_class_items( $packages ) {
	$found_item                     = false;
	$special_class                  = 'special-class'; // edit this with the slug of your local pickup shippig class
	$new_package                    = current( $packages );
	$new_package['contents']        = array();
	$new_package['contents_cost']   = 0;
	$new_package['applied_coupons'] = array();
	$new_package['ship_via']        = array( 'local_pickup' ); // Only allow local pickup for items in special class
	
	foreach ( WC()->cart->get_cart() as $item_key => $item ) {
		// Is the product in the special class?
		if ( $item['data']->needs_shipping() && $special_class === $item['data']->get_shipping_class() ) {
			$new_package['name'] = 'PickUp'; // your custom label for this package
			$found_item                            = true;
			$new_package['contents'][ $item_key ]  = $item;
			$new_package['contents_cost']         += $item['line_total'];

			// Remove from original package
			$packages[0]['contents_cost'] = $packages[0]['contents_cost'] - $item['line_total'];
			unset( $packages[0]['contents'][ $item_key ] );

			// If there are no items left in the previous package, remove it completely.
			if ( empty( $packages[0]['contents'] ) ) {
				unset( $packages[0] );
			}
		}
	}
	if ( $found_item ) {
	   $packages[] = $new_package;
	}
	return $packages;
}
 
function rename_custom_package( $package_name, $i, $package ) {
 
    if ( ! empty( $package['name'] ) ) {
        $package_name = $package['name'];
    }
 
    return $package_name;
}


function hide_shipping_method_based_on_shipping_class( $rates, $package )
{
    if ( is_admin() && ! defined( 'DOING_AJAX' ) )
        return;

    // HERE define your shipping class to find
    $class = 77;

    // HERE define the shipping methods you want to hide
    $method_key_ids = array('local_pickup:8', 'local_pickup:9');

    // Checking in cart items
    foreach( $package['contents'] as $item ) {
        // If we find the shipping class
        if( $item['data']->get_shipping_class_id() == $class ){
            foreach( $method_key_ids as $method_key_id ){
                unset($rates[$method_key_id]); // Remove the targeted methods
            }
            break; // Stop the loop
        }
    }
    return $rates;
}

// estiminate label and options
function sv_shipping_method_estimate_label( $label, $method ) {
	$label .= '<br /><small>';
	switch ( $method->method_id ) {
		case 'flat_rate':
			$label .= 'Est delivery: 3-5 days';
			break;
		case 'free_shipping':
			$label .= 'Est delivery: 4-7 days';
			break;
		case 'international_delivery':
			$label .= 'Est delivery: 7-10 days';
			break;
		default: 
			$label .= 'No shipping';
	}
	
	$label .= '</small>';
	return $label;
}

// notice local pickup
function syndicate_notice_shipping() { // edit text and color here
echo '<p style="color:#02bf02;"><b>If you have purchased items with the note "Local PickUp" please contact one of the Syndicate Team to discuss the delivery location. Normally the items will be handed over personally to an event. If this is not possible, shipping costs may apply.</b></p>';
}

// hook into shipping packages filter
add_filter( 'woocommerce_cart_shipping_packages', 'split_special_shipping_class_items' );

// output the new package name
add_filter( 'woocommerce_shipping_package_name', 'rename_custom_package', 10, 3 );

// hide local pickup on dropshipping items
add_filter( 'woocommerce_package_rates', 'hide_shipping_method_based_on_shipping_class', 10, 2 );

// output estimate label
add_filter( 'woocommerce_cart_shipping_method_full_label', 'sv_shipping_method_estimate_label', 10, 2 );

// add notice local pickup
add_action( 'woocommerce_after_order_notes', 'syndicate_notice_shipping' );
