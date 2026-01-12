jQuery(document).ready(function($) {
    
    // State flags
    var isGameLoaded = false;
    var isLoading = false;
    var fireworkInterval = null; // Store interval to clear it later

    // ==================================================
    // 1. HELPER: LOAD SCRIPT (Promise)
    // ==================================================
    function loadScript(url) {
        return new Promise(function(resolve, reject) {
            if (document.querySelector('script[src="' + url + '"]')) {
                resolve();
                return;
            }
            var script = document.createElement('script');
            script.src = url;
            script.onload = function() { resolve(); };
            script.onerror = function() { console.error('LTW Error: Failed to load ' + url); reject(); };
            document.head.appendChild(script);
        });
    }

    // ==================================================
    // 2. CHECK LIMIT ON LOAD & AUTO SHOW POPUP
    // ==================================================
    function initAutoPopup() {
        if (typeof LTW_Lazy_Assets === 'undefined' || !LTW_Lazy_Assets.config_data) return;

        var config = LTW_Lazy_Assets.config_data;
        var scripts = LTW_Lazy_Assets.scripts;
        var $gift = $('#ltw-gift-trigger');
        var $spinsLeft = $('#ltw-spins-left');

        if ($gift.length === 0) return;

        // A. Load Fingerprint Library First
        loadScript(scripts.fingerprint).then(function() {
            // B. Get Device ID
            return new Promise(function(resolve) {
                if (window.FingerprintJS) {
                    window.FingerprintJS.load().then(fp => fp.get()).then(result => {
                        window.ltw_visitor_id = result.visitorId;
                        resolve(result.visitorId);
                    });
                } else {
                    window.ltw_visitor_id = 'unknown_device';
                    resolve('unknown_device');
                }
            });
        }).then(function(deviceId) {
            // C. Check Limit with Server (Mode: CHECK)
            console.log("LTW Debug: Checking limit for ID:", deviceId);

            $.ajax({
                url: config.ajax_url,
                type: 'POST',
                data: {
                    action: 'ltw_check_limit',
                    security: config.nonce,
                    device_id: deviceId,
                    mode: 'check' 
                },
                success: function(response) {
                    if (response.success) {
                        // UPDATE SPIN COUNT UI
                        if ($spinsLeft.length > 0 && response.data && typeof response.data.spins_left !== 'undefined') {
                            $spinsLeft.text(response.data.spins_left);
                        }

                        // D. User has spins left -> Show Gift after Delay
                        console.log("LTW Debug: User has spins (" + response.data.spins_left + "). Showing gift in " + config.popup_delay + "ms");
                        
                        setTimeout(function() {
                            $gift.fadeIn();
                            startFireworks(); // Start FIREWORKS effect
                        }, parseInt(config.popup_delay) || 0);

                    } else {
                        // E. Limit reached -> Keep Gift Hidden
                        console.log("LTW Debug: User out of spins. Gift remains hidden.");
                        $gift.remove(); 
                    }
                },
                error: function() {
                    console.log("LTW Error: Check limit failed. Showing gift by default.");
                    $gift.fadeIn(); 
                }
            });
        });
    }

    // Run Init
    initAutoPopup();

    // ==================================================
    // 3. CLICK EVENT HANDLER (GIFT & LINK) - UPDATED
    // ==================================================
    // Thay đổi selector từ 'a' sang '.ltw-gift-link' để bắt chính xác hơn
    $(document).on('click', '.ltw-gift-link, a[href*="lucky_spin"]', function(e) {
        
        e.preventDefault();
        console.log("LTW Debug: Trigger link clicked.");

        var $wrapper = $('#ltw-popup-wrapper');
        var $gift = $('#ltw-gift-trigger');

        if ($wrapper.length === 0) { alert('Error: Popup HTML not found.'); return; }

        // 3.1 HIDE GIFT & STOP FIREWORKS
        if ($gift.length > 0) {
            $gift.fadeOut(); // Ẩn hộp quà
            
            // Dừng hiệu ứng pháo hoa để nhẹ máy
            if (fireworkInterval) {
                clearInterval(fireworkInterval);
                fireworkInterval = null;
            }
            // Xóa các hạt pháo hoa còn sót lại
            $('.ltw-firework').remove();
        }

        // 3.2 SHOW GAME
        $wrapper.fadeIn();

        if (isGameLoaded) return;
        if (isLoading) return;

        isLoading = true;
        $('#ltw-loading').show(); 

        var assets = LTW_Lazy_Assets.scripts;
        var config = LTW_Lazy_Assets.config_data;

        // --- WATERFALL LOADING ---
        Promise.all([
            loadScript(assets.p5),
            loadScript(assets.matter),
            loadScript(assets.fingerprint)
        ])
        .then(function() {
            return Promise.all([
                loadScript(assets.p5_dom),
                loadScript(assets.p5_sound),
                loadScript(assets.decomp),
                loadScript(assets.pathseg),
                loadScript(assets.arcshape),
                loadScript(assets.particle),
                loadScript(assets.boundary)
            ]);
        })
        .then(function() {
            if (window.ltw_visitor_id) return Promise.resolve();
            return new Promise(function(resolve) {
                    if (window.FingerprintJS) {
                    window.FingerprintJS.load().then(fp => fp.get()).then(result => {
                        window.ltw_visitor_id = result.visitorId;
                        resolve();
                    });
                } else {
                    window.ltw_visitor_id = 'unknown_device';
                    resolve();
                }
            });
        })
        .then(function() {
            window.LuckyWheelFront = {
                ajax_url:   config.ajax_url,
                home_url:   config.home_url,
                assets_url: config.assets_url,
                plugin_url: config.plugin_url,
                mode_admin: config.mode_admin,
                getConfig:  config.getConfig,
                nonce:      config.nonce,
                i18n:       config.i18n,
                max_spins:  config.max_spins,
                device_id:  window.ltw_visitor_id,
                recaptcha_site_key: config.recaptcha_site_key 
            };
            return loadScript(assets.sketch);
        })
        .then(function() {
            return loadScript(assets.custom);
        })
        .then(function() {
            isGameLoaded = true;
            isLoading = false;
            $('#ltw-loading').hide();
            if (typeof p5 !== 'undefined') new p5(); 
        })
        .catch(function(err) {
            console.error('LTW: Error during loading:', err);
            $('#ltw-loading').hide();
            alert('Connection error.');
            isLoading = false; 
        });
    });
    
    // Close Popup Handler
    $(document).on('click', '.ltw-close-game', function(e) {
        if (e.target.id === 'ltw-popup-wrapper' || $(e.target).hasClass('ltw-close-game')) {
            $('#ltw-popup-wrapper').fadeOut();
            
            // Show gift again if desired (and restart fireworks)
            /*var $gift = $('#ltw-gift-trigger');
            if ($gift.length > 0) {
                $gift.fadeIn();
                startFireworks();
            }*/
        }
    });

    // Close Gift Handler
    $(document).on('click', '.ltw-gift-close', function(e) {
        e.preventDefault(); 
        $('#ltw-gift-trigger').fadeOut();
        if (fireworkInterval) clearInterval(fireworkInterval);
    });

    // ==================================================
    // 4. FIREWORKS EFFECT (CENTERED)
    // ==================================================
    function createFirework() {
        const $container = jQuery('#ltw-gift-trigger');
        
        // Only create if visible
        if ($container.is(':hidden') || $container.length === 0) return;

        const $firework = jQuery('<div class="ltw-firework"></div>');
        
        // Random Position (NEAR CENTER)
        const topPos = Math.random() * 40 + 30 + '%'; 
        const leftPos = Math.random() * 40 + 30 + '%';  
        
        const hue = Math.floor(Math.random() * 360);
        const scale = Math.random() * 1 + 0.5;

        $firework.css({
            'top': topPos,
            'left': leftPos,
            'filter': 'hue-rotate(' + hue + 'deg)',
            'transform': 'scale(' + scale + ')'
        });

        $container.append($firework);

        // Remove after animation (1s)
        setTimeout(function() {
            $firework.remove();
        }, 1000);
    }

    function startFireworks() {
        if (fireworkInterval) clearInterval(fireworkInterval);
        fireworkInterval = setInterval(createFirework, 600);
    }
});

jQuery(document).on('click', '.ltw-card-close, .ltw-card-note', function() {
    jQuery('#ltw-gift-trigger').fadeOut();
});