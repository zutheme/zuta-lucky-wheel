=== Zuta Lucky Wheel – Spin to Win & Lead Generation ===
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

**Zuta Lucky Wheel** is a powerful marketing tool designed to turn visitors into subscribers and customers. It adds a "Spin to Win" wheel popup to your WordPress site, allowing users to enter their information for a chance to win prizes such as coupons, discounts, or free gifts.

Unlike other heavy plugins, **Zuta Lucky Wheel** is optimized for performance, using **Matter.js** and **p5.js** for smooth, realistic physics animations without slowing down your site.

### 🚀 Key Features

* **Realistic Physics:** Smooth spinning animation based on real physics (Matter.js engine).
* **Fully Customizable Design:**
    * Change colors for every slice (background, text).
    * Customize the spin button and popup background.
    * Edit the "Gift Box" trigger icon and winning messages.
* **Flexible Winning Logic:**
    * **Weighted Probability:** Control exactly how often each prize is won (e.g., Prize A: 10%, Prize B: 0.1%).
    * **Random Mode:** Let fate decide with pure 100% random outcomes.
* **Security & Anti-Cheat:**
    * **Google reCAPTCHA v3 Integration:** Protect your wheel from bots and spam without annoying user challenges.
    * **Smart Limits:** Limit spins per device/IP address.
    * **Reset Interval:** Allow users to spin again after X days.
* **Data Collection:** Capture customer names, emails, and phone numbers before they spin.
* **Mobile Friendly:** 100% responsive design that looks great on iPhone, Android, and Tablets.
* **Lightweight:** Assets are loaded only when needed to keep your site fast.

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