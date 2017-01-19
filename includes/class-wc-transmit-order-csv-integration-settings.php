<?php
	
/**
 *
 * @package  WC_Transmit_Order_CSV_Settings
 * @category Integration
 * @author   WooThemes
 */

if ( ! class_exists( 'WC_Transmit_Order_CSV_Settings' ) && class_exists( 'WC_Integration' ) ) {

	class WC_Transmit_Order_CSV_Settings extends WC_Integration {
	
		/**
		 * Init and hook in the integration.
		 */
		public function __construct() {
	
			$this->id                 = 'transmit_order_csv';
			$this->method_title       = __( 'Transmit Order CSV', 'woocommerce' );
			$this->method_description = __( 'Sends a CSV to defined Email Addresses, FTP & sFTP locations', 'woocommerce' );
	
			// Load the settings.
			$this->init_form_fields();
			$this->init_settings();
	
			// Define user set variables.
			$this->api_key          = $this->get_option( 'api_key' );
			$this->debug            = $this->get_option( 'debug' );
	
			// Actions.
			add_action( 'woocommerce_update_options_integration_' .  $this->id, array( $this, 'process_admin_options' ) );
	
			// Filters.
			add_filter( 'woocommerce_settings_api_sanitized_fields_' . $this->id, array( $this, 'sanitize_settings' ) );
	
		}
	
	
		/**
		 * Initialize integration settings form fields.
		 *
		 * @return void
		 */
		public function init_form_fields() {
			
			$fields = array(
				'statuses' => array(
					'title' => __('Order Statuses', 'woocommerce'),
					'type' => 'text',
					'description' => __('Please enter the Order Statuses for which Order CSV to be transmitted', 'woocommerce'),
					'id' => 'csv_order_statuses',
				),
				'csv_recipients' => array(
					'title' => __('CSV Recipients', 'woocommerce'),
					'type' => 'text',
					'description' => __('Please enter the email addresses for CSV confirmations to go to', 'woocommerce'),
					'id' => 'csv_order_emails',
				)
			);
			
			$this->form_fields = apply_filters('woocoomerce_transmit_order_csv_integration_settings_fields', $fields);
			
		}
	
	
		/**
		 * Generate Button HTML.
		 */
		public function generate_button_html( $key, $data ) {
			
			$field    = $this->plugin_id . $this->id . '_' . $key;
			
			$defaults = array(
				'class'             => 'button-secondary',
				'css'               => '',
				'custom_attributes' => array(),
				'desc_tip'          => false,
				'description'       => '',
				'title'             => '',
			);
	
			$data = wp_parse_args( $data, $defaults );
	
			ob_start();
			
			?>
			
			<tr valign="top">
				
				<th scope="row" class="titledesc">
					
					<label for="<?php echo esc_attr( $field ); ?>"><?php echo wp_kses_post( $data['title'] ); ?></label>
					
					<?php echo $this->get_tooltip_html( $data ); ?>
					
				</th>
				
				<td class="forminp">
					
					<fieldset>
						
						<legend class="screen-reader-text"><span><?php echo wp_kses_post( $data['title'] ); ?></span></legend>
						
						<button class="<?php echo esc_attr( $data['class'] ); ?>" type="button" name="<?php echo esc_attr( $field ); ?>" id="<?php echo esc_attr( $field ); ?>" style="<?php echo esc_attr( $data['css'] ); ?>" <?php echo $this->get_custom_attribute_html( $data ); ?>><?php echo wp_kses_post( $data['title'] ); ?></button>
						
						<?php echo $this->get_description_html( $data ); ?>
						
					</fieldset>
					
				</td>
				
			</tr>
			
			<?php
				
			return ob_get_clean();
			
		}
	
	
		/**
		 * Santize our settings
		 * @see process_admin_options()
		 */
		public function sanitize_settings( $settings ) {
			
			// We're just going to make the api key all upper case characters since that's how our imaginary API works
			
			if ( isset( $settings ) && isset( $settings['api_key'] ) ) {
				
				$settings['api_key'] = strtoupper( $settings['api_key'] );
				
			}
			
			return $settings;
			
		}
	
	
		/**
		 * Validate the API key
		 * @see validate_settings_fields()
		 */
		public function validate_api_key_field( $key ) {
			
			// get the posted value
			$value = $_POST[ $this->plugin_id . $this->id . '_' . $key ];
	
			// check if the API key is longer than 20 characters. Our imaginary API doesn't create keys that large so something must be wrong. Throw an error which will prevent the user from saving.
			if ( isset( $value ) && 20 < strlen( $value ) ) {
				
				$this->errors[] = $key;
				
			}
			
			return $value;
		}
	
	
		/**
		 * Display errors by overriding the display_errors() method
		 * @see display_errors()
		 */
		public function display_errors( ) {
	
			// loop through each error and display it
			foreach ( $this->errors as $key => $value ) {
				
				?>
				
				<div class="error">
					
					<p><?php _e( 'Looks like you made a mistake with the ' . $value . ' field. Make sure it isn&apos;t longer than 20 characters', 'woocommerce' ); ?></p>
					
				</div>
				
				<?php
					
			}
			
		}
		
		public static function get_statuses() {
			
			$transmit_order_csv = get_option('woocommerce_transmit-order-csv_settings');
			
			return ! empty( $transmit_order_csv['statuses'] ) ? $transmit_order_csv['statuses'] : array( 'complete' );
			
		}
	
	}

}

?>