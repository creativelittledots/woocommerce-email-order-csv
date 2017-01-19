<?php
/*
* Plugin Name: WooCommerce Transmit Order CSV
* Description: Automatically Sends a CSV to defined Email Addresses, FTP & sFTP locations at defined Order Statuses
* Version: 1.0
* Author: Creative Little Dots
* Author URI: http://creativelittledots.co.uk
* Text Domain: woocommerce-transmit-order-csv
* Domain Path: /languages/
*
* Requires at least: 3.8
* Tested up to: 4.1.1
*
* Copyright: © 2009-2015 Creative Little Dots
* License: GNU General Public License v3.0
* License URI: http://www.gnu.org/licenses/gpl-3.0.html
*/

if ( ! class_exists( 'WC_Transmit_Order_CSV' ) && class_exists( 'WC_Integration' ) ) {

	class WC_Transmit_Order_CSV {
	
		/**
		* Construct the plugin.
		*/
		public function __construct() {
			
			add_action( 'plugins_loaded', array( $this, 'init' ) );
			
			add_action( 'admin_enqueue_scripts', array($this, 'enqueue_admin_scripts') );
			
			// Default Triggers
			
			add_action( 'init', array($this, 'trigger_transmission') );
			
			add_action( 'woocommerce_payment_complete', array( $this, 'send_csv' ) );
			
			// Add Actions List
			
			add_action( 'woocommerce_order_actions', array( $this, 'add_transmit_csv_action' ) );
			
			add_action( 'woocommerce_admin_order_actions', array($this, 'add_transmit_csv_ajax'), 4, 2);
			
			// Add Actions to Que
			
			add_action( 'woocommerce_order_action_transmit_order_csv', array( $this, 'transmit_csv_action' ) );
			
			add_action( 'wp_ajax_transmit_order_csv', array( $this, 'transmit_csv_ajax') );
			
			
			
		}
		
		public function plugin_url() {
			
			return plugins_url( basename( plugin_dir_path(__FILE__) ), basename( __FILE__ ) );
			
		}
	
		public function plugin_path() {
			
			return untrailingslashit( plugin_dir_path( __FILE__ ) );
			
		}
	
		/**
		* Initialize the plugin.
		*/
		public function init() {
	
			// Checks if WooCommerce is installed.
			if ( class_exists( 'WC_Transmit_Order_CSV' ) ) {
				
				// Include our integration class.
				include_once 'includes/class-wc-transmit-order-csv-integration-settings.php';
	
				// Register the integration.
				add_filter( 'woocommerce_integrations', array( $this, 'add_integration' ) );
				
			} else {
				
				// throw an admin error if you like
				
			}
			
		}
		
		public function enqueue_admin_scripts() {
			
			wp_register_style( 'transmit-order-csv', $this->plugin_url() . "/assets/css/transmit-order-csv.css", array(), '1.0', 'all'); 
			
			wp_enqueue_style( 'transmit-order-csv' );
			
		}
	
		/**
		 * Add a new integration to WooCommerce.
		 */
		public function add_integration( $integrations ) {
			
			$integrations[] = 'WC_Transmit_Order_CSV_Settings';
			
			return $integrations;
			
		}
		
		public function trigger_transmission() {
			
			foreach( WC_Transmit_Order_CSV_Settings::get_statuses() as $status ) {
				
				add_action( 'woocommerce_order_status_' . $status, array( $this, 'send_csv_as' ) );
				
			}
			
		}
		
		public function send_csv_as( $order ) {
			
			if( $order = is_object( $order ) ? $order : wc_get_order( $order ) ) {
				
				$status = $order->get_status();
			
				$property = '_toc_sent_as_' . $status;
				
				if( ! $order->$property ) {
					
					$this->send_csv( $order );
					
					update_post_meta($order->id, $property, 1);
					
				}
				
			}
			
		}
		
		public function send_csv( $order ) {
			
			$transmit_order_csv = get_option('woocommerce_transmit-order-csv_settings');
				
			$order = is_object( $order ) ? $order : wc_get_order( $order );
			
			$upload_dir = wp_upload_dir();
			
			$subject =  apply_filters( 'woocommerce_transmit_order_csv_subject', "Order {$order->get_order_number()}", $order );
			
			$message =  apply_filters( 'woocommerce_transmit_order_csv_message', "Confirmation CSV attached for order {$order->get_order_number()}", $order );
			
			$filename = apply_filters( 'woocommerce_transmit_order_csv_filename', "{$order->get_order_number()}.csv", $order );
			
			$filepath = $upload_dir['basedir'] . '/' . $filename;
			
			$csv = apply_filters( 'woocommerce_transmit_order_csv_array', array(), $order );
			
			file_put_contents( $filepath, implode(',', $csv ) );
			
			$attachments = array( $filepath );
			
			$headers = array();
			
			$headers[] = 'From: ' . get_option('woocommerce_email_from_name') . ' <' . get_option('woocommerce_email_from_address') . '>';
	
			if( ! empty( $transmit_order_csv['csv_recipients'] ) ) {
	
				if( $mail = wp_mail( $transmit_order_csv['csv_recipients'], $subject, $message, $headers, $attachments ) ) {
					
					$_SESSION[ 'toc_admin_notices' ][ 'toc_' . $order->id ] = array(
						'message' => "CSV was successfully transmitted for Order {$order->get_order_number()}.", 
						'type' => 'updated'
					);
					
					$order->add_order_note( "CSV successfully transmitted - " . $order->get_status(), false );
				
				}
				
				else {
					
					$_SESSION[ 'toc_admin_notices' ][ 'toc_' . $order->id ] = array(
						'message' => "There was an error when trying to transmit CSV for Order {$order->get_order_number()}, please try again.", 
						'type' => 'error'
					);
					
				}
				
			}
			
			unlink( $filename );
		
		}
		
		public function add_transmit_csv_action( $actions ) {
				
			$actions['transmit_order_csv'] = __( 'Transmit Order CSV', 'woocommerce' );
			
			return $actions;
			
		}
		
		public function transmit_csv_action( $order ) {
				
			if( $order = is_object( $order ) ? $order : wc_get_order( $order ) ) {
			
				$this->send_csv( $order );
				
			}
			
			wp_safe_redirect( wp_get_referer() );
			
		}
		
		public function transmit_csv_ajax() {
			
			if( $order = wc_get_order( ! empty( $_GET['order_id'] ) ? (int) $_GET['order_id'] : '' ) ) {
			
				$this->send_csv($order);
				
			}
			
			wp_safe_redirect( wp_get_referer() );
			
		}
		
		public function add_transmit_csv_ajax($actions, $order) {
				
			if( $order->has_status( WC_Transmit_Order_CSV_Settings::get_statuses() ) ) {
				
				$actions['transmit_order_csv'] = array(
					'url' 		=> wp_nonce_url( admin_url( 'admin-ajax.php?action=transmit_order_csv&order_id=' . $order->id ), 'transmit_order_csv' ),
					'name'      => __( 'Transmit Order CSV', 'woocommerce' ),
					'action'    => 'transmit-order-csv'
				);
			
			}
			
			return $actions;
			
		}
	
	}
	
	$WC_Transmit_Order_CSV = new WC_Transmit_Order_CSV( __FILE__ );

}

?>