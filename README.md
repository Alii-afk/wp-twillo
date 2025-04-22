# Twilio Phone Number Manager for WooCommerce

A WordPress plugin that extends WooCommerce by adding Twilio phone number fields for users, sellers (products), and guests. Automatically sends SMS notifications when an order is placed.

---

## Features

- Add a Twilio phone number field to user profiles
- Add a seller-specific Twilio number to each product
- Allow guests to provide their Twilio number during checkout
- Send SMS to user, seller, and guest on order completion
- Uses Twilio PHP SDK via Composer (`twilio/sdk`)

---

## 🔧 Installation

1. Upload the plugin to `/wp-content/plugins/twilio-phone-manager/`
2. Run `composer install` inside the plugin directory to install Twilio SDK
3. Activate the plugin via the WordPress Admin
4. Configure your Twilio SID, Auth Token, and From Number in the class file (or enhance with a settings page)

---

## 🛠 Short Technical Summary

- Hooks into:
  - `show_user_profile`, `edit_user_profile` for user meta
  - `woocommerce_product_options_general_product_data` for product meta
  - `woocommerce_after_order_notes` for guest checkout field
  - `woocommerce_thankyou` to trigger SMS
- Uses `Twilio\\Rest\\Client` for sending messages

