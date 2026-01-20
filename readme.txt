=== Zuta Lucky Wheel ===
Contributors: hatazuwp
Tags: lucky wheel, spin to win, popup, marketing, woocommerce
Requires at least: 6.2
Tested up to: 6.9
Stable tag: 1.0.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Turn visitors into subscribers with a professional, realistic Lucky Wheel popup. Capture leads and boost engagement with gamification.

== Description ==

**Zuta Lucky Wheel** is a marketing tool designed to turn visitors into subscribers and customers. It adds a "Spin to Win" wheel popup to your WordPress site, allowing users to enter their information for a chance to win prizes such as coupons, discounts, or free gifts.

**Zuta Lucky Wheel** utilizes **Matter.js** and **p5.js** libraries to create smooth, realistic physics animations for the spinning effect.

### 🚀 Key Features

* **Realistic Physics:** Smooth spinning animation based on real physics (Matter.js engine).
* **Fully Customizable Design:**
    * Change colors for every slice (background, text).
    * Customize the spin button and popup background.
    * Edit the "Gift Box" trigger icon and winning messages.
* **Flexible Winning Logic:**
    * **Weighted Probability:** Control exactly how often each prize is won (e.g., Prize A: 10%, Prize B: 0.1%).
    * **Random Mode:** Let fate decide with random outcomes.
* **Security & Anti-Cheat:**
    * **Google reCAPTCHA v3 Integration:** Protect your wheel from bots and spam.
    * **Smart Limits:** Limit spins per device/IP address.
    * **Reset Interval:** Allow users to spin again after X days.
* **Data Collection:** Capture customer names, emails, and phone numbers before they spin.
* **Mobile Friendly:** Responsive design that adapts to iPhone, Android, and Tablets.
* **Performance:** Assets are loaded conditionally only when needed.

== External Services ==

This plugin relies on the following third-party services to function properly:

1. **Google reCAPTCHA v3**
   * **Used for:** Protecting the spin form from spam and bot abuse.
   * **Data Sent:** Hardware and software information, such as device and application data, is sent to Google for analysis.
   * **Privacy Policy:** https://policies.google.com/privacy
   * **Terms of Service:** https://policies.google.com/terms

2. **CDN Usage (jsDelivr / DataTables)**
   * **Used for:** Loading necessary library assets (e.g., DataTables assets, p5.js translations) to ensure functionality.
   * **Privacy Policy:** https://www.jsdelivr.com/terms/privacy-policy-jsdelivr-net

== Third-Party Software ==

This plugin utilizes the following third-party libraries:

* **p5.js** - https://p5js.org/
  License: LGPL 2.1 (https://www.gnu.org/licenses/old-licenses/lgpl-2.1.html)
  Purpose: High-performance canvas rendering and graphics.

* **Matter.js** - https://brm.io/matter-js/
  License: MIT (https://opensource.org/licenses/MIT)
  Purpose: 2D rigid body physics engine for realistic spin effects.

* **FingerprintJS** - https://fingerprint.com/
  License: MIT (https://opensource.org/licenses/MIT)
  Purpose: Device identification to prevent fraudulent spins and limit participation.

== Installation ==

1. Upload the `zuta-lucky-wheel` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Go to **Lucky Wheel** in the admin sidebar to configure your prizes and design.
4. (Optional) Go to **Display Rules** to set up reCAPTCHA keys and spin limits.

== Frequently Asked Questions ==

= How do I change the winning probability? =
Go to **Lucky Wheel > Design Setup**. Next to each prize slice, you will see a "Probability" field. Enter a number (weight). The higher the number compared to others, the higher the chance of winning.

= Can I limit how many times a user can spin? =
Yes. Go to **Lucky Wheel > Display Rules**. You can set "Max Spins per Device" and the "Reset Limit After (Days)".

= Does this plugin support Google reCAPTCHA? =
Yes. We support Google reCAPTCHA v3 (invisible). You just need to enter your Site Key and Secret Key in the **Display Rules** tab.

== Screenshots ==

1. **Frontend Interface:** The engaging Lucky Wheel popup appearing on the website with a realistic spinning effect.
2. **Design Setup:** Easily customize colors, labels, and probability for each wheel segment in the Admin Dashboard.
3. **Display Rules:** Configure popup delay, spin limits, and Google reCAPTCHA v3 security settings.

== Changelog ==

= 1.0.0 =
* Initial release.