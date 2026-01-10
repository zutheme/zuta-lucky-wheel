/**
 * LTW Custom Rotate Logic
 * Handles user interactions, form submissions, and notification rendering.
 * Final Version: Includes Ghost Click Protection (StopPropagation).
 */

jQuery(document).ready(function($) {

    // ==============================================================
    // GLOBAL FLAGS
    // ==============================================================
    
    // Flag to track if any popup (Form or Notification) is currently visible.
    window.isPopupOpen = false;

    // ==============================================================
    // 1. CLOSE POPUP HELPERS
    // ==============================================================

    /**
     * Closes the notification overlay.
     * FIX: Added event stopping and increased delay.
     */
    window.close_notify = function(elem, event) {
        // 1. STOP EVENT PROPAGATION (Critical for Ghost Clicks)
        if (event) {
            event.preventDefault();
            event.stopPropagation();
        }

        // 2. Hide the popup UI immediately
        if (typeof jQuery !== 'undefined' && elem instanceof jQuery) {
            elem.closest('#ltw-popup-notify, .ltw-notification-overlay').fadeOut();
        } else if (elem) {
            let parent = elem.closest('#ltw-popup-notify');
            if (!parent) parent = elem.closest('.ltw-notification-overlay');
            
            if (parent) {
                if (typeof jQuery !== 'undefined') {
                    jQuery(parent).fadeOut();
                } else {
                    parent.style.display = 'none';
                }
            }
        }
        
        // 3. MARK POPUP AS CLOSED
        window.isPopupOpen = false;

        // 4. LOCK GAME & DELAY RESET
        // Increased to 800ms to ensure no accidental double taps register
        window.isGameLocked = true;

        setTimeout(function() {
            // --- RESET GAME STATE HERE (AFTER DELAY) ---
            // Only reset if we actually want to allow a spin (checking logic usually handles this, 
            // but resetting ensures sketch.js isn't stuck).
            if (typeof s_rotated !== 'undefined') s_rotated = false;
            if (typeof winnerFound !== 'undefined') winnerFound = false;
            
            // Unlock game
            window.isGameLocked = false;
            console.log("LTW Debug: Game unlocked & Reset. Ready for next spin.");
        }, 800); 
    };

    /**
     * Closes the input form popup.
     */
    window.close_popup = function(elem) {
        // 1. Remove Form UI
        let formWrapper = elem.closest(".ltw-popup-form");
        if (formWrapper) {
            formWrapper.remove(); 
        } else {
            let overlay = elem.closest(".ltw-notification-overlay");
            if (overlay) overlay.remove();
        }
        
        // 2. Mark closed
        window.isPopupOpen = false;
        
        // 3. Lock & Delay Reset
        window.isGameLocked = true;
        
        setTimeout(function() {
            // Reset Game State
            if (typeof s_rotated !== 'undefined') s_rotated = false;
            if (typeof winnerFound !== 'undefined') winnerFound = false;

            window.isGameLocked = false;
            console.log("LTW Debug: Form closed. Game reset.");
        }, 800);
    };

    // ==============================================================
    // 2. CUSTOM NOTIFICATION (DOM METHOD)
    // ==============================================================

    window.ltw_show_notice = function(message) {
        // Force strict check: if popup is open, don't stack
        if (window.isPopupOpen === true) return; 
        window.isPopupOpen = true;

        let oldNotice = document.getElementById('ltw-notification-overlay');
        if (oldNotice) oldNotice.remove();

        let i18n = (typeof LuckyWheelFront !== 'undefined' && LuckyWheelFront.i18n) ? LuckyWheelFront.i18n : {};
        let title = i18n.notice_title || 'Notification';
        let btnText = i18n.close || 'Close';

        let wrapper = document.createElement('div');
        wrapper.id = 'ltw-notification-overlay';
        wrapper.className = 'ltw-notification-overlay';
        
        Object.assign(wrapper.style, {
            display: 'flex', position: 'fixed', top: '0', left: '0', width: '100vw', height: '100vh',
            zIndex: '2147483640', background: 'rgba(0,0,0,0.85)',
            alignItems: 'center', justifyContent: 'center', opacity: '0', transition: 'opacity 0.3s ease'
        });

        wrapper.innerHTML = `
            <div class="ltw-form" style="position:relative; background:#fff; width:90%; max-width:400px; border-radius:12px; box-shadow:0 10px 25px rgba(0,0,0,0.5); overflow:hidden;">
                <div class="head-congrate" style="background:#f8f9fa; padding:15px; border-bottom:1px solid #eee; text-align:center;">
                    <h4 style="margin:0; font-size:18px; color:#333; font-weight:bold; text-transform:uppercase; font-family:sans-serif;">${title}</h4>
                </div>
                <div class="form-content" style="padding:30px 20px; text-align:center; font-size:16px; color:#333; line-height:1.5; font-family:sans-serif;">
                    ${message}
                </div>
                <div class="form-footer" style="padding:0 0 25px 0; text-align:center;">
                    <button type="button" id="ltw-btn-close-notice" style="background:#007bff; color:#fff; border:none; padding:10px 35px; font-size:16px; border-radius:5px; cursor:pointer; font-weight:bold;">${btnText}</button>
                </div>
            </div>
        `;

        document.body.appendChild(wrapper);

        // Bind click event with Event Object capture
        let btnClose = wrapper.querySelector('#ltw-btn-close-notice');
        if(btnClose) {
            btnClose.onclick = function(e) { 
                // Pass event 'e' to stop propagation
                window.close_notify(this, e); 
            };
        }

        requestAnimationFrame(() => { wrapper.style.opacity = '1'; });
    };

    // ==============================================================
    // 3. SUBMIT CUSTOMER FORM
    // ==============================================================

    window.submit_customer = function(elem){
        let form = elem.closest("form");
        if (!form) return;

        let fullname = form.querySelector(".fullname").value.trim();
        let phone    = form.querySelector(".phone").value.trim();
        let email    = form.querySelector(".email").value.trim();

        let i18n = (typeof LuckyWheelFront !== 'undefined' && LuckyWheelFront.i18n) ? LuckyWheelFront.i18n : {};
        let txt_err_name  = i18n.err_name || "Please enter your full name";
        let txt_err_phone = i18n.err_phone || "Please enter your phone number";
        let txt_success   = i18n.success || "Congratulations! Information sent successfully!";
        let txt_error     = i18n.error || "Error sending information. Please try again!";

        if (!fullname){ window.isPopupOpen = false; window.ltw_show_notice(txt_err_name); return; }
        if (!phone){ window.isPopupOpen = false; window.ltw_show_notice(txt_err_phone); return; }

        let url  = LuckyWheelFront.ajax_url + "?action=InsCustomer&security=" + LuckyWheelFront.nonce;
        let params = JSON.stringify({
            fullname: fullname, phone: phone, email: email,
            getgift: (typeof _getgift !== 'undefined') ? _getgift : '',
            license: (typeof _license !== 'undefined') ? _license : 0,
            security: LuckyWheelFront.nonce
        });

        elem.disabled = true;
        let originalText = elem.innerText;
        elem.innerText = "...";

        let http = new XMLHttpRequest();
        http.open("POST", url, true);
        http.setRequestHeader("Accept", "application/json");
        http.setRequestHeader("Content-type", "application/json; charset=utf-8");

        http.onreadystatechange = function(){
            if (http.readyState === 4){
                elem.disabled = false;
                elem.innerText = originalText;
                
                if (http.status === 200){
                    try {
                        // Close Form
                        window.close_popup(elem);
                        // Show Success
                        window.ltw_show_notice(txt_success);
                    } catch(e) {
                        console.error("LTW Error parsing JSON:", e);
                        window.close_popup(elem);
                        window.ltw_show_notice(txt_success);
                    }
                } else {
                    window.ltw_show_notice(txt_error);
                }
            }
        };
        http.send(params);
    };

    // ==============================================================
    // 4. POPUP WIN & GLOBAL BRIDGE
    // ==============================================================

    window.ltw_show_win = function(namegift, url_img, idcampain) {
        
        window.isPopupOpen = true;

        if (typeof idcampain === 'undefined' || idcampain === '') {
             idcampain = (typeof _idcampain !== 'undefined') ? _idcampain : 1;
        }

        let url = LuckyWheelFront.ajax_url + "?action=popup&security=" + LuckyWheelFront.nonce;
        let params = JSON.stringify({ name_gif: namegift, url_img: url_img, idcampain: idcampain });

        let http = new XMLHttpRequest();
        http.open("POST", url, true);
        http.setRequestHeader("Accept", "application/json");
        http.setRequestHeader("Content-type", "application/json; charset=utf-8");

        http.onreadystatechange = function(){
            if (http.readyState === 4){
                if (http.status === 200){
                    let html = http.responseText;
                    document.body.insertAdjacentHTML('beforeend', html);
                    
                    let insertedPopup = document.querySelector(".ltw-popup-form");
                    if(insertedPopup) insertedPopup.style.display = 'flex'; 
                } else {
                     console.error("LTW Error: " + http.status);
                     window.isPopupOpen = false;
                }
            }
        };
        http.send(params);
    }

});