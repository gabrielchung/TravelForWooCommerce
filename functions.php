<?php

/**
 * Register the custom product type after init
 */
function register_tour_product_type() {

	/**
	 * This should be in its own separate file.
	 */
	class WC_Product_Tour extends WC_Product {

		public function __construct( $product ) {

			$this->product_type = 'tour';
			$this->supports[]   = 'ajax_add_to_cart';
			
			parent::__construct( $product );

		}

		/**
		* Get the add to url used mainly in loops.
		*
		* @return string
		*/
		public function add_to_cart_url() {
			$url = $this->is_purchasable() && $this->is_in_stock() ? remove_query_arg( 'added-to-cart', add_query_arg( 'add-to-cart', $this->id ) ) : get_permalink( $this->id );

			return apply_filters( 'woocommerce_product_add_to_cart_url', $url, $this );
		}

		/**
		* Get the add to cart button text.
		*
		* @return string
		*/
		public function add_to_cart_text() {
			$text = $this->is_purchasable() && $this->is_in_stock() ? __( 'Add to cart', 'woocommerce' ) : __( 'Read more', 'woocommerce' );

			return apply_filters( 'woocommerce_product_add_to_cart_text', $text, $this );
		}

	}

}

add_action( 'init', 'register_tour_product_type' );

function add_travel_custom_products( $types ){

	$types[ 'tour' ] = __( 'Tour' );

	return $types;

}

add_filter( 'product_type_selector', 'add_travel_custom_products' );

function set_default_product() {

    return 'tour';
}

add_filter( 'default_product_type', 'set_default_product');

function add_my_custom_product_data_tab( $product_data_tabs ) {

    return $product_data_tabs;

}

add_filter( 'woocommerce_product_data_tabs', 'add_my_custom_product_data_tab' , 99 , 1 );

function add_tour_tabs($tabs){

	$tabs['shipping']['class'][] = 'hide_if_tour';

	$tabs['advanced']['class'][] = 'hide_if_tour';

	$tabs['linked_product']['class'][] = 'hide_if_tour';

	$tabs['attribute']['class'][] = 'hide_if_tour';

    return($tabs);

}

add_filter('woocommerce_product_data_tabs', 'add_tour_tabs', 10, 1);


function custom_product_tabs( $tabs ) {
	$tabs['tour'] = array(
		'label'		=> __( 'Tour', 'woocommerce' ),
		'target'	=> 'tour_options',
		'class'		=> array( 'show_if_tour' )
	);
	return $tabs;
}
add_filter( 'woocommerce_product_data_tabs', 'custom_product_tabs' );


function tour_options_product_tab_content() {
	global $post;
	?><div id='tour_options' class='panel woocommerce_options_panel'><?php
		?><div class='options_group'><?php
			woocommerce_wp_text_input( array( 'id' => '_regular_price', 'label' => __( 'Regular price', 'woocommerce' ) . ' (' . get_woocommerce_currency_symbol() . ')', 'data_type' => 'price' ) );
			woocommerce_wp_text_input( array(
				'id'			=> '_start_date',
				'label'			=> __( 'Start Date', 'woocommerce' ),
				'desc_tip'		=> 'true',
				'description'	=> __( 'A handy description field', 'woocommerce' ),
				'type' 			=> 'text',
			) );
			//Duration
			woocommerce_wp_text_input(  array( 'id' => '_duration', 'label' => __( 'Duration', 'woocommerce' ), 'desc_tip' => 'true', 'description' => __( 'Custom ordering position.', 'woocommerce' ), 'type' => 'number', 'custom_attributes' => array(
				'step' 	=> '1'
			)  ) );
			woocommerce_wp_text_input( array(
				'id'			=> '_location',
				'label'			=> __( 'Location', 'woocommerce' ),
				'desc_tip'		=> 'true',
				'description'	=> __( 'A handy description field', 'woocommerce' ),
				'type' 			=> 'text',
			) );
		?>
		</div>

	</div><?php
}
add_action( 'woocommerce_product_data_panels', 'tour_options_product_tab_content' );

function save_tour_meta($post_id){

	if(isset($_POST['_start_date'])){
		update_post_meta($post_id, '_start_date',
			strip_tags($_POST['_start_date']));
	}

	if(isset($_POST['_duration'])){
		update_post_meta($post_id, '_duration',
			strip_tags($_POST['_duration']));
	}

	if(isset($_POST['_location'])){
		update_post_meta($post_id, '_location',
			strip_tags($_POST['_location']));
	}

}

add_action('save_post', 'save_tour_meta');


function admin_inline_js(){
	echo "<script type='text/javascript'>\n";
	echo "jQuery('#_start_date').datepicker();";
	echo "\n</script>";
}
add_action( 'admin_print_footer_scripts', 'admin_inline_js' );


function custom_product_info() {

	global $product;

	$startDate = get_post_meta($product->id, '_start_date', true);
	$duration = get_post_meta($product->id, '_duration', true);
	$location = get_post_meta($product->id, '_location', true);

	if ( ! empty($duration) ) {

		$date = date_create_from_format('F j, Y', $startDate);

		$date->add(new DateInterval('P'.(intval($duration)-1).'D'));

		$dtEndDate = $date;
		
	}

	if ( ! empty($location) ) {
		echo '<div style="color:#43454b">';
		echo '<i class="fa fa-map-marker" aria-hidden="true"></i> ';
		echo $location;
		echo '</div>';
	}

	if ( ( ! empty($startDate) ) && ( ! empty($dtEndDate) ) ) {
		echo '<div style="color:#43454b">';
		echo '<i class="fa fa-calendar" aria-hidden="true"></i> ';
		
		$dtStartDate = date_create_from_format('F j, Y', $startDate);

		if ($dtStartDate->format('Y') == $dtEndDate->format('Y')) {
			$sameYear = true;
		} else {
			$sameYear = false;
		}

		if ($dtStartDate->format('m') == $dtEndDate->format('m')) {
			$sameMonth = true;
		} else {
			$sameMonth = false;
		}

		if ($dtStartDate->format('d') == $dtEndDate->format('d')) {
			$sameDay = true;
		} else {
			$sameDay = false;
		}

		$startDateToBeDisplayed = '';
		$endDateToBeDisplayed = '';

		// Year

		if ( $sameYear ) {
			//do nothing
		} else {
			$startDateToBeDisplayed = $dtStartDate->format('Y');
		}

		$endDateToBeDisplayed = $dtEndDate->format('Y');

		// Month

		if ( $sameMonth && $sameYear ) {
			//do nothing
		} else {
			$startDateToBeDisplayed = $dtStartDate->format('M') . ' ' . $startDateToBeDisplayed;
		}

		$endDateToBeDisplayed = $dtEndDate->format('M') . ' ' . $endDateToBeDisplayed;

		// Day

		if ( $sameDay && $sameMonth && $sameYear ) {
			//do nothing
		} else {
			$startDateToBeDisplayed = $dtStartDate->format('j') . ' ' . $startDateToBeDisplayed;
		}

		$endDateToBeDisplayed = $dtEndDate->format('j') . ' ' . $endDateToBeDisplayed;

		if ( empty($startDateToBeDisplayed) ) {
			echo $endDateToBeDisplayed;
		} else {
			echo $startDateToBeDisplayed . ' - ' . $endDateToBeDisplayed;
		}

		echo '</div>';
	}

	if ( ! empty($duration) ) {
		echo '<div style="color:#43454b; margin-bottom: 20px;">';
		echo '<i class="fa fa-clock-o" aria-hidden="true"></i> ';
		echo $duration . ' days';
		echo '</div>';
	}

}

add_action( 'woocommerce_after_shop_loop_item_title', 'custom_product_info', 10, 2 );


function custom_single_product_info( $woocommerce_template_single_title, $int ) {
	
	global $product;

	$startDate = get_post_meta($product->id, '_start_date', true);
	$duration = get_post_meta($product->id, '_duration', true);
	$location = get_post_meta($product->id, '_location', true);

	if ( ! empty($duration) ) {

		$date = date_create_from_format('F j, Y', $startDate);

		$date->add(new DateInterval('P'.$duration.'D'));

		$endDate = $date->format('F j, Y');

	}

	if ( ! empty($location) ) {
		echo '<div>';
		echo '<i class="fa fa-map-marker" aria-hidden="true"></i> ';
		echo $location . '<br />';
		echo '</div>';
	}

	if ( ( ! empty($startDate) ) && ( ! empty($endDate) ) ) {
		echo '<div>';
		echo '<i class="fa fa-calendar" aria-hidden="true"></i> ';
		echo $startDate . ' - ' . $endDate . '<br />';
		echo '</div>';
	}

	if ( ! empty($duration) ) {
		echo '<div style="margin-bottom: 20px;">';
		echo '<i class="fa fa-clock-o" aria-hidden="true"></i> ';
		echo 'Duration: ' . $duration . ' days<br />';
		echo '</div>';
	}

	echo '<div style="margin-bottom: 20px;">';
	woocommerce_template_loop_add_to_cart();
	echo '</div>';
	
}

add_action( 'woocommerce_single_product_summary', 'custom_single_product_info', 10, 2 );

?>