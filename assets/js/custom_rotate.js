/**
 * zutalw Custom Rotate Logic
 * Handles user interactions, form submissions, and notification rendering.
 * Final Version: Includes Ghost Click Protection & Unified Config.
 */

jQuery(document).ready(function($) {

    // ==============================================================
    // GLOBAL FLAGS
    // ==============================================================
    
    window.isPopupOpen = false;

    // Ensure Global Config Exists (fallback)
    if (typeof window.ZutalwConfig === 'undefined') {
        window.ZutalwConfig = {
            ajax_url: '/wp-admin/admin-ajax.php',
            nonce: '',
            i18n: {}
        };
    }

    // ==============================================================
    // 1. CLOSE POPUP HELPERS
    // ==============================================================

    /**
     * Closes the notification overlay.
     */
    window.close_notify = function(elem, event) {
        // 1. STOP EVENT PROPAGATION (Critical)
        if (event) {
            event.preventDefault();
            event.stopPropagation();
        }

        // 2. Hide the popup UI immediately
        if (typeof jQuery !== 'undefined' && elem instanceof jQuery) {
            elem.closest('#zutalw-popup-notify, .zutalw-notification-overlay').fadeOut();
        } else if (elem) {
            let parent = elem.closest('#zutalw-popup-notify');
            if (!parent) parent = elem.closest('.zutalw-notification-overlay');
            
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
        window.isGameLocked = true;

        setTimeout(function() {
            // --- RESET GAME STATE ---
            if (typeof s_rotated !== 'undefined') s_rotated = false;
            if (typeof winnerFound !== 'undefined') winnerFound = false;
            
            // Unlock game
            window.isGameLocked = false;
        }, 800); 
    };

    /**
     * Closes the input form popup.
     */
    window.close_popup = function(elem) {
        let formWrapper = elem.closest(".zutalw-popup-form");
        if (formWrapper) {
            formWrapper.remove(); 
        } else {
            let overlay = elem.closest(".zutalw-notification-overlay");
            if (overlay) overlay.remove();
        }
        
        window.isPopupOpen = false;
        window.isGameLocked = true;
        
        setTimeout(function() {
            if (typeof s_rotated !== 'undefined') s_rotated = false;
            if (typeof winnerFound !== 'undefined') winnerFound = false;
            window.isGameLocked = false;
        }, 800);
    };

    // ==============================================================
    // 2. CUSTOM NOTIFICATION (DOM METHOD)
    // ==============================================================

    window.zutalw_show_notice = function(message) {
        if (window.isPopupOpen === true) return; 
        window.isPopupOpen = true;

        let oldNotice = document.getElementById('zutalw-notification-overlay');
        if (oldNotice) oldNotice.remove();

        // FIX: Use ZutalwConfig
        let i18n = (typeof ZutalwConfig !== 'undefined' && ZutalwConfig.i18n) ? ZutalwConfig.i18n : {};
        let title = i18n.notice_title || 'Notification';
        let btnText = i18n.close || 'Close';

        let wrapper = document.createElement('div');
        wrapper.id = 'zutalw-notification-overlay';
        wrapper.className = 'zutalw-notification-overlay';
        
        Object.assign(wrapper.style, {
            display: 'flex', position: 'fixed', top: '0', left: '0', width: '100vw', height: '100vh',
            zIndex: '2147483640', background: 'rgba(0,0,0,0.85)',
            alignItems: 'center', justifyContent: 'center', opacity: '0', transition: 'opacity 0.3s ease'
        });

        wrapper.innerHTML = `
            <div class="zutalw-form" style="position:relative; background:#fff; width:90%; max-width:400px; border-radius:12px; box-shadow:0 10px 25px rgba(0,0,0,0.5); overflow:hidden;">
                <div class="head-congrate" style="background:#f8f9fa; padding:15px; border-bottom:1px solid #eee; text-align:center;">
                    <h4 style="margin:0; font-size:18px; color:#333; font-weight:bold; text-transform:uppercase; font-family:sans-serif;">${title}</h4>
                </div>
                <div class="form-content" style="padding:30px 20px; text-align:center; font-size:16px; color:#333; line-height:1.5; font-family:sans-serif;">
                    ${message}
                </div>
                <div class="form-footer" style="padding:0 0 25px 0; text-align:center;">
                    <button type="button" id="zutalw-btn-close-notice" style="background:#007bff; color:#fff; border:none; padding:10px 35px; font-size:16px; border-radius:5px; cursor:pointer; font-weight:bold;">${btnText}</button>
                </div>
            </div>
        `;

        document.body.appendChild(wrapper);

        let btnClose = wrapper.querySelector('#zutalw-btn-close-notice');
        if(btnClose) {
            btnClose.onclick = function(e) { 
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

        let i18n = (typeof ZutalwConfig !== 'undefined' && ZutalwConfig.i18n) ? ZutalwConfig.i18n : {};
        let txt_err_name  = i18n.err_name || "Please enter your full name";
        let txt_err_phone = i18n.err_phone || "Please enter your phone number";
        let txt_success   = i18n.success || "Congratulations! Information sent successfully!";
        let txt_error     = i18n.error || "Error sending information. Please try again!";

        if (!fullname){ window.isPopupOpen = false; window.zutalw_show_notice(txt_err_name); return; }
        if (!phone){ window.isPopupOpen = false; window.zutalw_show_notice(txt_err_phone); return; }

        let url  = ZutalwConfig.ajax_url + "?action=zutalw_InsCustomer&security=" + ZutalwConfig.nonce;
        
        // [MODIFIED] Use URLSearchParams for Form Data instead of JSON
        let params = new URLSearchParams();
        params.append('fullname', fullname);
        params.append('phone', phone);
        params.append('email', email);
        params.append('getgift', (typeof _getgift !== 'undefined') ? _getgift : '');
        params.append('license', (typeof _license !== 'undefined') ? _license : 0);
        params.append('security', ZutalwConfig.nonce);

        elem.disabled = true;
        let originalText = elem.innerText;
        elem.innerText = "...";

        let http = new XMLHttpRequest();
        http.open("POST", url, true);
        http.setRequestHeader("Accept", "application/json");
        
        // [MODIFIED] Correct Header for POST data
        http.setRequestHeader("Content-type", "application/x-www-form-urlencoded; charset=UTF-8");

        http.onreadystatechange = function(){
            if (http.readyState === 4){
                elem.disabled = false;
                elem.innerText = originalText;
                
                if (http.status === 200){
                    try {
                        window.close_popup(elem);
                        window.zutalw_show_notice(txt_success);
                    } catch(e) {
                        window.close_popup(elem);
                        window.zutalw_show_notice(txt_success);
                    }
                } else {
                    window.zutalw_show_notice(txt_error);
                }
            }
        };
        // [MODIFIED] Send stringified params
        http.send(params.toString());
    };

    // ==============================================================
    // 4. POPUP WIN & GLOBAL BRIDGE
    // ==============================================================

    window.zutalw_show_win = function(namegift, url_img, idcampain) {
        
        window.isPopupOpen = true;

        if (typeof idcampain === 'undefined' || idcampain === '') {
             idcampain = (typeof _idcampain !== 'undefined') ? _idcampain : 1;
        }

        let url = ZutalwConfig.ajax_url + "?action=zutalw_popup&security=" + ZutalwConfig.nonce;
        
        // [MODIFIED] Use URLSearchParams
        let params = new URLSearchParams();
        params.append('name_gif', namegift);
        params.append('url_img', url_img);
        params.append('idcampain', idcampain);

        let http = new XMLHttpRequest();
        http.open("POST", url, true);
        http.setRequestHeader("Accept", "application/json");
        // [MODIFIED] Correct Header
        http.setRequestHeader("Content-type", "application/x-www-form-urlencoded; charset=UTF-8");

        http.onreadystatechange = function(){
            if (http.readyState === 4){
                if (http.status === 200){
                    let html = http.responseText;
                    document.body.insertAdjacentHTML('beforeend', html);
                    
                    let insertedPopup = document.querySelector(".zutalw-popup-form");
                    if(insertedPopup) insertedPopup.style.display = 'flex'; 
                } else {
                     console.error("zutalw Error: " + http.status);
                     window.isPopupOpen = false;
                }
            }
        };
        // [MODIFIED] Send stringified params
        http.send(params.toString());
    }

});