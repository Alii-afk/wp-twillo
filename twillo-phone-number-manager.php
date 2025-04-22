<?php
/*
 * Plugin Name: Twilio Phone Number Manager
 * Plugin URI: https://alihaiderhamadani.com/wp-twillo-phone-number-manager
 * Description: Adds Twilio phone number fields to user profiles, products, and checkout pages, and sends SMS notifications on order placement.
 * Version: 1.1
 * Author: Syed Ali Haider
 * Author URI: https://alihaiderhamadani.com/
 * License: GPL v2 or later
*/


if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

require_once plugin_dir_path( __FILE__ ) . 'vendor/autoload.php';

use Twilio\Rest\Client;

class TwilioPhoneManager {
    
    private $twilio_sid = "your_twilio_account_sid";
    private $twilio_token = "your_twilio_auth_token";
    private $twilio_number = "your_twilio_phone_number";
    
    public function __construct() {
        // User Meta
        add_action('show_user_profile', [$this, 'add_user_twilio_phone']);
        add_action('edit_user_profile', [$this, 'add_user_twilio_phone']);
        add_action('personal_options_update', [$this, 'save_user_twilio_phone']);
        add_action('edit_user_profile_update', [$this, 'save_user_twilio_phone']);
        
        // Product Meta
        add_action('woocommerce_product_options_general_product_data', [$this, 'add_product_twilio_phone']);
        add_action('woocommerce_process_product_meta', [$this, 'save_product_twilio_phone']);

        // Order Meta on Checkout
        add_action('woocommerce_after_order_notes', [$this, 'add_checkout_twilio_phone']);
        add_action('woocommerce_checkout_update_order_meta', [$this, 'save_checkout_twilio_phone']);
        add_action('woocommerce_admin_order_data_after_billing_address', [$this, 'display_order_twilio_phone']);
        
        // Send SMS after order is placed
        add_action('woocommerce_thankyou', [$this, 'send_twilio_sms_notification']);
    }

    // 1. User Meta
    public function add_user_twilio_phone($user) {
        $twilio_phone = get_user_meta($user->ID, 'twilio_phone', true);
        ?>
        <h3>Twilio Phone Number</h3>
        <table class="form-table">
            <tr>
                <th><label for="twilio_phone">Twilio Phone No.</label></th>
                <td><input type="text" name="twilio_phone" id="twilio_phone" value="<?php echo esc_attr($twilio_phone); ?>" class="regular-text"></td>
            </tr>
        </table>
        <?php
    }

    public function save_user_twilio_phone($user_id) {
        if (current_user_can('edit_user', $user_id) && isset($_POST['twilio_phone'])) {
            update_user_meta($user_id, 'twilio_phone', sanitize_text_field($_POST['twilio_phone']));
        }
    }

    // 2. Product Meta
    public function add_product_twilio_phone() {
        woocommerce_wp_text_input([
            'id' => '_twilio_phone',
            'label' => 'Seller Twilio Phone No.',
            'desc_tip' => true,
            'description' => 'Enter the Twilio phone number for the seller.'
        ]);
    }

    public function save_product_twilio_phone($post_id) {
        $twilio_phone = isset($_POST['_twilio_phone']) ? sanitize_text_field($_POST['_twilio_phone']) : '';
        update_post_meta($post_id, '_twilio_phone', $twilio_phone);
    }

    // 3. Checkout Field for Twilio Phone
    public function add_checkout_twilio_phone($checkout) {
        woocommerce_form_field('guest_twilio_phone', [
            'type' => 'text',
            'class' => ['form-row-wide'],
            'label' => 'Guest Twilio Phone No.',
            'placeholder' => 'Enter your Twilio phone number',
        ], $checkout->get_value('guest_twilio_phone'));
    }

    public function save_checkout_twilio_phone($order_id) {
        if (isset($_POST['guest_twilio_phone'])) {
            update_post_meta($order_id, 'guest_twilio_phone', sanitize_text_field($_POST['guest_twilio_phone']));
        }
    }

    public function display_order_twilio_phone($order) {
        $twilio_phone = get_post_meta($order->get_id(), 'guest_twilio_phone', true);
        if ($twilio_phone) {
            echo '<p><strong>Guest Twilio Phone No:</strong> ' . esc_html($twilio_phone) . '</p>';
        }
    }

    // 4. Send SMS on Order Placement
    public function send_twilio_sms_notification($order_id) {
        $order = wc_get_order($order_id);
        $client = new Client($this->twilio_sid, $this->twilio_token);

        $user_id = $order->get_user_id();
        $user_twilio_phone = get_user_meta($user_id, 'twilio_phone', true);

        // Get the product Twilio phone number from meta data
        $product_twilio_phone = '';
        foreach ($order->get_items() as $item) {
            $product_id = $item->get_product_id();
            $product_twilio_phone = get_post_meta($product_id, '_twilio_phone', true);
            if ($product_twilio_phone) break;
        }

        // Get the guest Twilio phone number from order meta
        $guest_twilio_phone = get_post_meta($order_id, 'guest_twilio_phone', true);

        // Construct the message
        $message = "Order #{$order->get_order_number()} has been placed.\nTotal: {$order->get_formatted_order_total()}";

        // Send SMS to user
        if ($user_twilio_phone) {
            $client->messages->create($user_twilio_phone, [
                'from' => $this->twilio_number,
                'body' => $message
            ]);
        }

        // Send SMS to seller
        if ($product_twilio_phone) {
            $client->messages->create($product_twilio_phone, [
                'from' => $this->twilio_number,
                'body' => $message
            ]);
        }

        // Send SMS to guest
        if ($guest_twilio_phone) {
            $client->messages->create($guest_twilio_phone, [
                'from' => $this->twilio_number,
                'body' => $message
            ]);
        }
    }
}

new TwilioPhoneManager();
