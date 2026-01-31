jQuery(document).ready(function($) {
    
    // ==================================================
    // 0. INIT & CONFIG GUARD
    // ==================================================
    var isGameLoaded = false;
    var isLoading = false;
    var fireworkInterval = null; 

    // Try to get config from multiple sources
    function getGlobalConfig() {
        if (typeof ZutalwConfig !== 'undefined') return ZutalwConfig;
        if (typeof zutalw_Lazy_Assets !== 'undefined' && zutalw_Lazy_Assets.config_data) return zutalw_Lazy_Assets.config_data;
        return null;
    }

    function getScripts() {
        if (typeof zutalw_Lazy_Assets !== 'undefined') return zutalw_Lazy_Assets.scripts;
        // Fallback for click handler if lazy assets missing
        if (typeof zutalw_Lazy_Assets !== 'undefined' && window.zutalw_Lazy_Assets && window.zutalw_Lazy_Assets.scripts) {
             return window.zutalw_Lazy_Assets.scripts;
        }
        return {};
    }

    var config = getGlobalConfig();
    var scripts = getScripts();

    if (!config) {
        console.error("zutalw: Critical Error - Configuration not found. Check wp_localize_script.");
        return; // Stop execution
    }

    // ==================================================
    // 1. HELPER: LOAD SCRIPT (ROBUST)
    // ==================================================
    function loadScript(url) {
        return new Promise(function(resolve, reject) {
            if (!url) {
                // If url is empty, just resolve to keep chain moving
                resolve(); 
                return;
            }
            if (document.querySelector('script[src="' + url + '"]')) {
                resolve();
                return;
            }
            var script = document.createElement('script');
            script.src = url;
            script.onload = function() { resolve(); };
            script.onerror = function() { 
                console.warn('zutalw Warning: Failed to load ' + url + '. Continuing anyway...');
                resolve(); // Resolve anyway to not break the chain (Soft Fail)
            };
            document.head.appendChild(script);
        });
    }

    // ==================================================
    // 2. CHECK LIMIT ON LOAD & AUTO SHOW POPUP
    // ==================================================
    function initAutoPopup() {
        var $spinsLeft = $('#zutalw-spins-left');

        // A. Load Fingerprint Library (Soft loading)
        var pFingerprint = Promise.resolve();
        
        if (!window.FingerprintJS && scripts.fingerprint) {
            pFingerprint = loadScript(scripts.fingerprint);
        }

        pFingerprint.then(function() {
            // B. Get Device ID
            return new Promise(function(resolve) {
                if (window.FingerprintJS) {
                    try {
                        window.FingerprintJS.load().then(fp => fp.get()).then(result => {
                            window.zutalw_visitor_id = result.visitorId;
                            resolve(result.visitorId);
                        }).catch(err => {
                            console.warn("zutalw: FPJS Error", err);
                            resolve('unknown_device_err');
                        });
                    } catch(e) {
                         resolve('unknown_device_try');
                    }
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
                    security: config.nonce,
                    device_id: deviceId,
                    mode: 'check' 
                },
                success: function(response) {
                    var $gift = $('#zutalw-gift-trigger');

                    if (response.success) {
                        // UPDATE UI
                        if ($spinsLeft.length > 0 && response.data && typeof response.data.spins_left !== 'undefined') {
                            $spinsLeft.text(response.data.spins_left);
                        }

                        // D. User has spins -> Show Gift
                        var delayMs = parseInt(config.popup_delay) || 0;
                        console.log("zutalw Debug: User has spins (" + response.data.spins_left + "). Showing gift in " + delayMs + "ms");
                        
                        setTimeout(function() {
                            if ($gift.length > 0) {
                                $gift.css({ 'display': 'flex', 'z-index': '999999' }).hide().fadeIn(400);
                                startFireworks(); 
                            } else {
                                console.warn("zutalw: Gift DOM element not found.");
                            }
                        }, delayMs);

                    } else {
                        // E. Limit reached
                        console.log("zutalw: User out of spins. Gift removed.");
                        $gift.remove(); 
                    }
                },
                error: function(err) {
                    console.error("zutalw Error: Check limit failed.", err);
                    // Fallback: Show gift on error to avoid blocking user
                    $('#zutalw-gift-trigger').fadeIn(); 
                }
            });
        });
    }

    // Run Init
    initAutoPopup();

    // ==================================================
    // 3. CLICK EVENT HANDLER (GIFT & LINK)
    // ==================================================
    // [FIXED] Updated selector to include 'zutalw_lucky_spin'
    $(document).on('click', '.zutalw-gift-link, .zutalw-card-btn, a[href*="zutalw_lucky_spin"], a[href*="lucky_spin"], #zutalw-gift-trigger', function(e) {
        
        // If clicked on close button inside gift, ignore
        if ($(e.target).hasClass('zutalw-card-close') || $(e.target).hasClass('zutalw-gift-close')) return;

        e.preventDefault();
        console.log("zutalw Debug: Trigger link clicked.");

        var $wrapper = $('#zutalw-popup-wrapper');
        var $gift = $('#zutalw-gift-trigger');

        // 3.1 HIDE GIFT & STOP FIREWORKS
        if ($gift.length > 0) {
            $gift.fadeOut();
            stopFireworks();
        }

        // 3.2 SHOW GAME WRAPPER
        $wrapper.fadeIn();

        if (isGameLoaded) return;
        if (isLoading) return;

        isLoading = true;
        $('#zutalw-loading').show(); 

        // Reload assets list in case it wasn't ready before
        scripts = getScripts();

        // --- WATERFALL LOADING ---
        Promise.all([
            loadScript(scripts.p5),
            loadScript(scripts.matter),
            (window.FingerprintJS ? Promise.resolve() : loadScript(scripts.fingerprint))
        ])
        .then(function() {
            return Promise.all([
                loadScript(scripts.p5_dom),
                loadScript(scripts.p5_sound),
                loadScript(scripts.decomp),
                loadScript(scripts.pathseg),
                loadScript(scripts.arcshape),
                loadScript(scripts.particle),
                loadScript(scripts.boundary)
            ]);
        })
        .then(function() {
            // Ensure ID exists (Double check)
            if (window.zutalw_visitor_id) return Promise.resolve();
            return new Promise(function(resolve) {
                    if (window.FingerprintJS) {
                    window.FingerprintJS.load().then(fp => fp.get()).then(result => {
                        window.zutalw_visitor_id = result.visitorId;
                        resolve();
                    }).catch(() => resolve());
                } else {
                    window.zutalw_visitor_id = 'unknown_device';
                    resolve();
                }
            });
        })
        .then(function() {
            // Update Global Config for Sketch.js
            // MERGE with existing config instead of overwriting entirely
            window.ZutalwConfig = $.extend({}, config, {
                device_id: window.zutalw_visitor_id
            });
            return loadScript(scripts.sketch);
        })
        .then(function() {
            return loadScript(scripts.custom);
        })
        .then(function() {
            
            console.log("zutalw: Game assets loaded. Initializing P5...");
            isGameLoaded = true;
            isLoading = false;
            $('#zutalw-loading').hide();
            
            // Init P5 if not auto-started
            if (typeof window.setup === 'function') {
                    new p5(); 
                    console.log("zutalw: new p5() called manually.");
                } else {
                    console.error("zutalw: window.setup not found in sketch.js");
                }
        })
        .catch(function(err) {
            console.error('zutalw: Error during loading assets:', err);
            $('#zutalw-loading').hide();
            isLoading = false; 
        });
    });
    
    // Close Game Popup Handler
    $(document).on('click', '.zutalw-close-game', function(e) {
         $('#zutalw-popup-wrapper').fadeOut();
    });
    // Close on background click
    $(document).on('click', '#zutalw-popup-wrapper', function(e) {
        if (e.target.id === 'zutalw-popup-wrapper') {
            $(this).fadeOut();
        }
    });

    // Close Gift Handler
    $(document).on('click', '.zutalw-gift-close, .zutalw-card-close', function(e) {
        e.preventDefault(); 
        e.stopPropagation(); // Stop bubbling to trigger
        $('#zutalw-gift-trigger').fadeOut();
        stopFireworks();
    });

    // ==================================================
    // 4. FIREWORKS EFFECT (CENTERED)
    // ==================================================
    function createFirework() {
        const $container = $('#zutalw-gift-trigger');
        if ($container.is(':hidden') || $container.length === 0) return;

        const $firework = $('<div class="zutalw-firework"></div>');
        const topPos = Math.random() * 40 + 30 + '%'; 
        const leftPos = Math.random() * 40 + 30 + '%';  
        const hue = Math.floor(Math.random() * 360);
        const scale = Math.random() * 1 + 0.5;

        $firework.css({
            'top': topPos, 'left': leftPos,
            'filter': 'hue-rotate(' + hue + 'deg)',
            'transform': 'scale(' + scale + ')'
        });
        $container.append($firework);
        setTimeout(function() { $firework.remove(); }, 1000);
    }

    function startFireworks() {
        if (fireworkInterval) clearInterval(fireworkInterval);
        fireworkInterval = setInterval(createFirework, 600);
    }
    
    function stopFireworks() {
        if (fireworkInterval) { clearInterval(fireworkInterval); fireworkInterval = null; }
        $('.zutalw-firework').remove();
    }
});