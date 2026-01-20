<?php
/**
 * zutalw Admin Upgrade Page
 * Displays comparison between Free and upcoming Pro features.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class zutalw_Admin_Upgrade {

    public function render() {
        ?>
        <div class="wrap zutalw-upgrade-wrapper" style="max-width: 1000px; margin: 20px auto; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;">
            
            <div style="text-align: center; padding: 50px 20px; background: linear-gradient(135deg, #23282d 0%, #32373c 100%); color: #fff; border-radius: 12px; margin-bottom: 30px; box-shadow: 0 10px 30px rgba(0,0,0,0.15);">
                <h1 style="font-size: 3em; color: #fff; margin: 0 0 15px 0;">Zuta Lucky Wheel <span style="color: #ffb900;">PRO</span></h1>
                <p style="font-size: 1.3em; opacity: 0.9; max-width: 700px; margin: 0 auto;">Unleash the full power of gamification marketing. Create seasonal campaigns, prevent fraud, and track every conversion.</p>
                <div style="margin-top: 30px;">
                    <span style="background: #ffb900; color: #000; padding: 8px 20px; border-radius: 50px; font-weight: bold; text-transform: uppercase; font-size: 14px;">Coming Soon Q1 2026</span>
                </div>
            </div>

            <div style="background: #fff; padding: 30px; border: 1px solid #ccd0d4; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
                <h2 style="margin-top: 0; margin-bottom: 25px; border-bottom: 1px solid #eee; padding-bottom: 15px;">Compare Editions</h2>
                
                <table class="widefat fixed striped" style="border: none;">
                    <thead>
                        <tr>
                            <th style="width: 50%; font-size: 16px; padding: 15px;">Feature Description</th>
                            <th style="text-align: center; font-size: 16px;">Free</th>
                            <th style="text-align: center; font-size: 16px; color: #2271b1;">Pro</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong>Physics-based Spin Engine (Matter.js)</strong></td>
                            <td style="text-align: center;"><span class="dashicons dashicons-yes" style="color: green;"></span></td>
                            <td style="text-align: center;"><span class="dashicons dashicons-yes" style="color: green;"></span></td>
                        </tr>
                        <tr>
                            <td><strong>Google reCAPTCHA v3 Support</strong></td>
                            <td style="text-align: center;"><span class="dashicons dashicons-yes" style="color: green;"></span></td>
                            <td style="text-align: center;"><span class="dashicons dashicons-yes" style="color: green;"></span></td>
                        </tr>
                        
                        <tr>
                            <td><strong>Campaign Management</strong><br><small>Create specific wheels for Christmas, Lunar New Year, Black Friday, etc.</small></td>
                            <td style="text-align: center;"><span class="dashicons dashicons-no-alt" style="color: #ccc;"></span></td>
                            <td style="text-align: center;"><span class="dashicons dashicons-yes" style="color: #2271b1;"></span></td>
                        </tr>
                        
                        <tr>
                            <td><strong>Advanced Fraud Prevention</strong><br><small>Limit participation by Phone Number, Email, and Device ID simultaneously.</small></td>
                            <td style="text-align: center;"><span class="dashicons dashicons-no-alt" style="color: #ccc;"></span></td>
                            <td style="text-align: center;"><span class="dashicons dashicons-yes" style="color: #2271b1;"></span></td>
                        </tr>

                        <tr>
                            <td><strong>Visual Prize Slices</strong><br><small>Add custom images/icons directly onto the wheel segments.</small></td>
                            <td style="text-align: center;"><span class="dashicons dashicons-no-alt" style="color: #ccc;"></span></td>
                            <td style="text-align: center;"><span class="dashicons dashicons-yes" style="color: #2271b1;"></span></td>
                        </tr>

                        <tr>
                            <td><strong>Automatic Email Notifications</strong><br><small>Instantly send winning details and coupons to customers via email.</small></td>
                            <td style="text-align: center;"><span class="dashicons dashicons-no-alt" style="color: #ccc;"></span></td>
                            <td style="text-align: center;"><span class="dashicons dashicons-yes" style="color: #2271b1;"></span></td>
                        </tr>

                        <tr>
                            <td><strong>Source & URL Tracking</strong><br><small>Identify exactly which URL or marketing source the user came from.</small></td>
                            <td style="text-align: center;"><span class="dashicons dashicons-no-alt" style="color: #ccc;"></span></td>
                            <td style="text-align: center;"><span class="dashicons dashicons-yes" style="color: #2271b1;"></span></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div style="margin-top: 30px; padding: 25px; background: #f0f6fb; border-left: 4px solid #2271b1; border-radius: 4px;">
                <h3 style="margin-top: 0;">Why wait for Pro?</h3>
                <p>The Pro version is being designed to provide a comprehensive marketing ecosystem. From seasonal events to deep analytics, we help you understand your customers better while protecting your business from spam.</p>
                <p><strong>Stay tuned!</strong> We are working hard to bring these features to you as soon as possible.</p>
            </div>

        </div>
        <?php
    }
}