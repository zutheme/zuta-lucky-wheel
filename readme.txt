=== Zuta Lucky Wheel – Spin to Win & Lead Generation ===
Contributors: zutatheme
Tags: lucky wheel, spin to win, lead generation, marketing, rewards, gamification, popup, giveaways, wheel of fortune, interactive
Requires at least: 5.8
Tested up to: 6.4
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Boost your website's marketing engagement with a realistic, physics-based Lucky Wheel. Collect leads, offer rewards, and increase conversions with interactive spin-to-win campaigns.

== Description ==

**Zuta Lucky Wheel** is a lightweight, high-performance marketing tool designed to turn visitors into leads through gamification. Unlike other basic spinners, Zuta Lucky Wheel uses a realistic physics engine (Matter.js) to provide a smooth and professional user experience.

Whether you want to offer discount codes, free gifts, or simply engage your audience, this plugin provides all the tools you need to manage rewards and collect customer data securely on your server.

### Key Features:
* **Realistic Physics Engine:** Powered by Matter.js and p5.js for a natural and exciting spinning experience.
* **Weighted Probability:** Take full control of your rewards. Set specific win rates for each prize to manage your inventory effectively.
* **Lead Generation Ready:** Built-in customer data collection (Name, Email, Phone) stored directly in your WordPress database.
* **Device-Based Spin Limits:** Prevent abuse with advanced tracking that limits spins per device/IP.
* **Speed Optimized:** Assets only load when the wheel is triggered, ensuring your site remains fast.
* **Manual Triggering:** Launch the wheel popup from any menu item, button, or image using a simple link hash (`#lucky_spin_license=0`).
* **Fully Customizable:** Easily change colors, font sizes, prize labels, and button styles to match your brand.
* **Secure AJAX Processing:** All winning calculations happen on the server to prevent front-end manipulation.

== Installation ==

1.  Upload the `zuta-lucky-wheel` folder to the `/wp-content/plugins/` directory.
2.  Activate the plugin through the 'Plugins' menu in WordPress.
3.  Navigate to **Lucky Wheel > Design Setup** to customize your wheel.
4.  Configure your display rules or use the manual trigger link to start engaging your visitors.

== Frequently Asked Questions ==

= Can I set different win rates for each prize? =
Yes! In the Design Setup tab, you can choose "Weighted Probability" mode and enter a "Weight" for each segment. Higher numbers increase the chance of winning that specific prize.

= How do I open the wheel via a button? =
Simply set the URL of your button or link to `#lucky_spin_license=0`. The plugin will automatically detect this and open the popup.

= Is it mobile-friendly? =
Absolutely. The wheel is responsive and works perfectly on smartphones and tablets.

== Screenshots ==

1. The interactive physics-based lucky wheel in action.
2. Admin Design Setup: Easily customize colors, segments, and win rates.
3. Customer Dashboard: View and manage leads collected from the wheel.

== Changelog ==

= 1.0.0 =
* Refactored plugin structure for better performance.
* Added "Weighted Probability" and "Pure Random" game modes.
* Enhanced security with server-side spin calculations.
* Integrated device-based spin limiting.
* Added Manual Trigger functionality.

== Upgrade to Pro ==

Looking for more power? The Pro version (coming soon) will include:
* Multi-Campaign Management.
* Unique Phone Number Validation.
* Advanced Referral & Referrer Tracking.
* Professional Design Templates.
* Export Customer Data to CSV.