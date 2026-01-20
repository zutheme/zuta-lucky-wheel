jQuery(document).ready(function($) {
    
    // State flags
    var isGameLoaded = false;
    var isLoading = false;
    var fireworkInterval = null; 

    // ==================================================
    // 1. HELPER: LOAD SCRIPT
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
            script.onerror = function() { console.error('zutalw Error: Failed to load ' + url); reject(); };
            document.head.appendChild(script);
        });
    }

    // ==================================================
    // 2. CHECK LIMIT ON LOAD & AUTO SHOW POPUP
    // ==================================================
    function initAutoPopup() {
        
        // FIX: Prefer ZutalwConfig (Unified) but fallback to LazyAssets
        var config = (typeof ZutalwConfig !== 'undefined') ? ZutalwConfig : 
                     ((typeof zutalw_Lazy_Assets !== 'undefined') ? zutalw_Lazy_Assets.config_data : null);

        // Script list usually stays in LazyAssets
        var scripts = (typeof zutalw_Lazy_Assets !== 'undefined') ? zutalw_Lazy_Assets.scripts : {};

        if (!config) { console.warn("zutalw: Config missing"); return; }

        // Find elements (Don't cache $gift here, find it later to be safe)
        var $spinsLeft = $('#zutalw-spins-left');

        // A. Load Fingerprint Library First
        // Only load if not already loaded
        var pFingerprint = (window.FingerprintJS) ? Promise.resolve() : loadScript(scripts.fingerprint);

        pFingerprint.then(function() {
            // B. Get Device ID
            return new Promise(function(resolve) {
                if (window.FingerprintJS) {
                    window.FingerprintJS.load().then(fp => fp.get()).then(result => {
                        window.zutalw_visitor_id = result.visitorId;
                        resolve(result.visitorId);
                    });
                } else {
                    window.zutalw_visitor_id = 'unknown_device';
                    resolve('unknown_device');
                }
            });
        }).then(function(deviceId) {
            // C. Check Limit with Server
            console.log("zutalw Debug: Checking limit for ID:", deviceId);

            $.ajax({
                url: config.ajax_url,
                type: 'POST',
                data: {
                    action: 'zutalw_check_limit',
                    security: config.nonce, // Ensure this matches PHP 'zutalw-nonce'
                    device_id: deviceId,
                    mode: 'check' 
                },
                success: function(response) {
                    // Re-query gift element inside callback to ensure DOM readiness
                    var $gift = $('#zutalw-gift-trigger');

                    if (response.success) {
                        // UPDATE SPIN COUNT UI
                        if ($spinsLeft.length > 0 && response.data && typeof response.data.spins_left !== 'undefined') {
                            $spinsLeft.text(response.data.spins_left);
                        }

                        // D. User has spins left -> Show Gift after Delay
                        var delayMs = parseInt(config.popup_delay) || 0;
                        console.log("zutalw Debug: User has spins (" + response.data.spins_left + "). Showing gift in " + delayMs + "ms");
                        
                        setTimeout(function() {
                            if ($gift.length > 0) {
                                // Force high Z-index and Flex display
                                $gift.css({
                                    'display': 'flex',
                                    'z-index': '999999' 
                                }).hide().fadeIn(400);
                                
                                startFireworks(); 
                            } else {
                                console.warn("zutalw Debug: Gift element #zutalw-gift-trigger not found in DOM.");
                            }
                        }, delayMs);

                    } else {
                        // E. Limit reached -> Keep Gift Hidden
                        console.log("zutalw Debug: User out of spins. Gift removed.");
                        $gift.remove(); 
                    }
                },
                error: function(err) {
                    console.error("zutalw Error: Check limit failed.", err);
                    // Fallback: Show gift anyway if check fails (user experience priority)
                    var $gift = $('#zutalw-gift-trigger');
                    $gift.fadeIn(); 
                }
            });
        });
    }

    // Run Init
    initAutoPopup();

    // ==================================================
    // 3. CLICK EVENT HANDLER (GIFT & LINK)
    // ==================================================
    $(document).on('click', '.zutalw-gift-link, .zutalw-card-btn, a[href*="lucky_spin"]', function(e) {
        
        e.preventDefault();
        console.log("zutalw Debug: Trigger link clicked.");

        var $wrapper = $('#zutalw-popup-wrapper');
        var $gift = $('#zutalw-gift-trigger');

        // 3.1 HIDE GIFT & STOP FIREWORKS
        if ($gift.length > 0) {
            $gift.fadeOut();
            stopFireworks();
        }

        // 3.2 SHOW GAME
        $wrapper.fadeIn();

        if (isGameLoaded) return;
        if (isLoading) return;

        isLoading = true;
        $('#zutalw-loading').show(); 

        // Use LazyAssets script list
        var assets = (typeof zutalw_Lazy_Assets !== 'undefined') ? zutalw_Lazy_Assets.scripts : {};
        var config = (typeof ZutalwConfig !== 'undefined') ? ZutalwConfig : {};

        // --- WATERFALL LOADING ---
        Promise.all([
            loadScript(assets.p5),
            loadScript(assets.matter),
            // Fingerprint might already be loaded, but safe to call again
            (window.FingerprintJS ? Promise.resolve() : loadScript(assets.fingerprint))
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
            // Ensure ID exists
            if (window.zutalw_visitor_id) return Promise.resolve();
            return new Promise(function(resolve) {
                    if (window.FingerprintJS) {
                    window.FingerprintJS.load().then(fp => fp.get()).then(result => {
                        window.zutalw_visitor_id = result.visitorId;
                        resolve();
                    });
                } else {
                    window.zutalw_visitor_id = 'unknown_device';
                    resolve();
                }
            });
        })
        .then(function() {
            // Update Global Config for Sketch.js
            window.ZutalwConfig = {
                ajax_url:   config.ajax_url,
                home_url:   config.home_url,
                assets_url: config.assets_url,
                plugin_url: config.plugin_url,
                mode_admin: config.mode_admin,
                getConfig:  config.getConfig,
                nonce:      config.nonce,
                i18n:       config.i18n,
                max_spins:  config.max_spins,
                device_id:  window.zutalw_visitor_id,
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
            $('#zutalw-loading').hide();
            if (typeof p5 !== 'undefined') new p5(); 
        })
        .catch(function(err) {
            console.error('zutalw: Error during loading:', err);
            $('#zutalw-loading').hide();
            isLoading = false; 
        });
    });
    
    // Close Game Popup Handler
    $(document).on('click', '.zutalw-close-game', function(e) {
        if (e.target.id === 'zutalw-popup-wrapper' || $(e.target).hasClass('zutalw-close-game')) {
            $('#zutalw-popup-wrapper').fadeOut();
        }
    });

    // Close Gift Handler
    // FIX: Added .zutalw-card-close to match HTML structure
    $(document).on('click', '.zutalw-gift-close, .zutalw-card-close, .zutalw-card-note', function(e) {
        e.preventDefault(); 
        $('#zutalw-gift-trigger').fadeOut();
        stopFireworks();
    });

    // ==================================================
    // 4. FIREWORKS EFFECT (CENTERED)
    // ==================================================
    function createFirework() {
        const $container = $('#zutalw-gift-trigger');
        
        // Only create if visible
        if ($container.is(':hidden') || $container.length === 0) return;

        const $firework = $('<div class="zutalw-firework"></div>');
        
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
    
    function stopFireworks() {
        if (fireworkInterval) {
            clearInterval(fireworkInterval);
            fireworkInterval = null;
        }
        $('.zutalw-firework').remove();
    }
});