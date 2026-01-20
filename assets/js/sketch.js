// =======================================================
//  SKETCH.JS - Final Version (Auto-Detect Path & AJAX Fix)
// =======================================================

// Matter.js bindings
const {
    Engine, Render, Runner,
    Composites, Common, MouseConstraint,
    Mouse, Composite, Bodies, Constraint,
    Events, World, Vertices, Body, Renderer
} = Matter;

const Vector = Matter.Vector;

if (Common && typeof Common.setDecomp === 'function' && typeof decomp !== 'undefined') {
    Common.setDecomp(decomp);
}

// Core variables
let bodies;
let acrshapes = [], particles = [], boundaries = [], buts = [];
let initsound = false;
const PARTICLE_SPEED = 0.01;

// Play state flag
let canplay = false; 

let w_arc = 450, h_arc = 450; 
let context;
let radius = 0;
let angle = 0;
let speed = 0.06;

let centerX = 300;
let centerY = 300;

let rp = 2;
let rbc = w_arc / 2;

// Win coordinates
let x_win0 = 0, y_win0 = 0;

// Flag to prevent double checking
let winnerFound = false; 

// ===================== GUARD & CONFIG (AUTO DETECT FIX) =====================

// 1. Normalize Global Config Object
if (typeof window.ZutalwConfig === 'undefined') {
    if (typeof window.zutalw_Lazy_Assets !== 'undefined' && window.zutalw_Lazy_Assets.config_data) {
         window.ZutalwConfig = window.zutalw_Lazy_Assets.config_data;
    } else {
         window.ZutalwConfig = { assets_url: '' }; // Don't set hardcoded ajax_url here yet
    }
}

// 2. Determine Plugin Directory (Smart Detection)
let _plugin_dir = '';

// Strategy A: From PHP Config (Best)
if (window.ZutalwConfig && window.ZutalwConfig.assets_url) {
    _plugin_dir = window.ZutalwConfig.assets_url;
} 
// Strategy B: Auto-detect from script tag
else {
    const scripts = document.getElementsByTagName('script');
    for (let i = 0; i < scripts.length; i++) {
        const src = scripts[i].src;
        if (src && src.indexOf('sketch.js') !== -1) {
            let cleanSrc = src.split('?')[0]; 
            if (cleanSrc.indexOf('js/sketch.js') !== -1) {
                 _plugin_dir = cleanSrc.substring(0, cleanSrc.indexOf('js/sketch.js'));
                 console.log("zutalw Debug: Auto-detected assets path: " + _plugin_dir);
                 break;
            }
        }
    }
}
// Fallback
if (!_plugin_dir) {
    _plugin_dir = window.location.origin + '/wp-content/plugins/zuta-lucky-wheel/assets/';
}
if (_plugin_dir && _plugin_dir.slice(-1) !== '/') _plugin_dir += '/';

console.log("zutalw Debug: Final Assets URL: ", _plugin_dir);

// 3. Determine AJAX URL (Smart Detection based on Assets URL)
if (!window.ZutalwConfig.ajax_url) {
    // Logic: If assets is at http://localhost/demoweb/wp-content/..., 
    // then AJAX is at http://localhost/demoweb/wp-admin/admin-ajax.php
    if (_plugin_dir.indexOf('wp-content') !== -1) {
        let siteRoot = _plugin_dir.split('wp-content')[0];
        window.ZutalwConfig.ajax_url = siteRoot + 'wp-admin/admin-ajax.php';
        console.log("zutalw Debug: Auto-detected AJAX URL: ", window.ZutalwConfig.ajax_url);
    } else {
        // Fallback relative (works if installed at root)
        window.ZutalwConfig.ajax_url = '/wp-admin/admin-ajax.php';
    }
}

// ===================== END CONFIG =====================

let config = window.ZutalwConfig && window.ZutalwConfig.getConfig ? window.ZutalwConfig.getConfig : [];
if (typeof config === 'string') {
    try { config = JSON.parse(config); } catch (e) { config = []; }
}

// sounds variables
let ding, touch, win, error, startsound, winsound;
let initial = false, s_rotated = false, valid = 1, _getgift = "";

// FIX: CSS Class prefix zutalw-
let load = document.getElementsByClassName("zutalw-popup-processing")[0];

let group = Body.nextGroup ? Body.nextGroup(true) : null;
let mode_admin = false;

// default colors
let colodd = "#eff4fb", colevent = "#0564b1", colwin = "#033b70",
    coltextwin = "#ffffff", coltextodd = "#000000", coltexteven = "#ffffff";
let colbut = "#007CBD", coltextbut = "#000000", sizetextbut = 16,
    textbut = "SPIN", colbutpress = "#03acf9", coltextpress = "#ffffff";

let _count = 0, _maxc = 3;

// ===================== SOUND SETUP =====================
let _startsound = _plugin_dir + "sound/spinning.mp3";
let _winsound = _plugin_dir + "sound/success.mp3";

if (Array.isArray(config) && config.length > 0) {
    if (config[0]) {
        _startsound = config[0].startsound || _startsound;
        _winsound = config[0].winsound || _winsound;
    }
} else if (typeof config === 'object' && config !== null) {
    _startsound = config.startsound || _startsound;
    _winsound = config.winsound || _winsound;
}

function makeSoundStub() {
    return { isPlaying: () => false, play: () => {}, stop: () => {}, setVolume: () => {}, getVolume: () => 1, jump: () => {} };
}

function preload() {
    try {
        if (typeof loadSound === 'function') {
            startsound = loadSound(_startsound, 
                () => { console.log("zutalw Sound: Spin sound loaded successfully."); }, 
                (err) => { console.warn("zutalw Sound: Failed to load spin sound: " + _startsound, err); startsound = makeSoundStub(); }
            );
            winsound = loadSound(_winsound, 
                () => { console.log("zutalw Sound: Win sound loaded successfully."); }, 
                (err) => { console.warn("zutalw Sound: Failed to load win sound: " + _winsound, err); winsound = makeSoundStub(); }
            );
        } else { startsound = makeSoundStub(); winsound = makeSoundStub(); }
    } catch (e) { startsound = makeSoundStub(); winsound = makeSoundStub(); }
}

// ===================== CAMPAIGN DETECT =====================
let l = new URL(window.location.href);
let _path = l.pathname;
let _idcampain = 1, _license = 0;

if (_path === '/wp-admin/options-general.php' && getQueryParam('page') === 'rotate-configuration' && getQueryParam('tab') === 'setup') {
    _idcampain = getQueryParam('idcampain') || 1;
} else {
    let e_game = document.getElementById("area-game");
    if (e_game) {
        _idcampain = e_game.dataset.id || 1;
        _license = e_game.dataset.license || 0;
    }
}

function getQueryParam(param) {
    let rx = new RegExp("[?&]" + param + "=([^&]+).*$");
    let m = window.location.search.match(rx);
    return m ? m[1] : "";
}

// ===================== MATTER ENGINE =====================
let engine = Engine.create();
let world = engine.world;
let xa = 250, ya = 250, distan = Math.PI/4, loadconfig = false;
let dataconfig = null; // Initialize as null

// ===================== P5 SETUP =====================
function setup() {
    console.log("zutalw Debug: Setup started");
    let container = document.getElementById("area-game");

    if (container) {
        canvas = createCanvas(w_default(), h_default());
        canvas.parent('area-game');
        clear();
        // Always fetch config on startup
        if (!loadconfig) {
            getdatascore(function(data) {
                console.log("zutalw Debug: Config loaded via AJAX");
                restart_game(data);
            });
            loadconfig = true;
        }
        
        if (typeof Runner !== 'undefined' && Runner.run) {
            Runner.run(engine);
        }
    }
}

function w_default() {
    let w_device = window.innerWidth > 0 ? window.innerWidth : screen.width;
    return (w_device < 768) ? 300 : 500;
}
function h_default() {
    let w_device = window.innerWidth > 0 ? window.innerWidth : screen.width;
    return (w_device < 768) ? 300 : 500;
}

// ===================== AJAX =====================
function getdatascore(callback) {
    let http = new XMLHttpRequest();
    
    // FIX: Get AJAX URL from our smart detected config
    let baseAjax = window.ZutalwConfig.ajax_url;
    
    // Safety Fallback if somehow still empty (should be caught by logic above)
    if (!baseAjax) baseAjax = '/wp-admin/admin-ajax.php';

    // FIX: Added 'zutalw_' prefix to action name
    let url = baseAjax + "?action=zutalw_getdataConfig";
    
    // Ensure we have IDs
    let id_c = _idcampain || 1;
    let lic = _license || 0;

    let params = JSON.stringify({ idcampain: id_c, license: lic });

    if (load) load.style.display = "block";
    
    http.open("POST", url, true);
    http.setRequestHeader("Accept", "application/json");
    http.setRequestHeader("Content-type", "charset=utf-8");
    
    http.onreadystatechange = function () {
        if (http.readyState === 4) {
            if (http.status === 200) {
                try {
                    let arr = JSON.parse(this.responseText);
                    // CRITICAL FIX: Assign global dataconfig
                    dataconfig = arr; 
                    callback(arr);
                } catch (e) { console.error('zutalw Error: Invalid JSON Config', e); }
            } else {
                console.error('zutalw Error: Failed to load config. Status:', http.status);
            }
            if (load) load.style.display = "none";
        }
    };
    http.send(params);
}

// ===================== RESTART GAME (CLEANUP & INIT) =====================
function restart_game(data) {
    console.log("zutalw Debug: restart_game called");

    // --- SOUND FIX: RESET VOLUME & STOP PREVIOUS ---
    try {
        if (typeof winsound !== 'undefined' && winsound) {
            if (typeof winsound.stop === 'function') winsound.stop();
        }
        if (typeof startsound !== 'undefined' && startsound) {
            if (typeof startsound.stop === 'function') startsound.stop();
            
            // FIX: Use setVolume(1.0, 0) to force INSTANT volume change (0 seconds ramp)
            if (typeof startsound.setVolume === 'function') startsound.setVolume(1.0, 0);
            
            console.log("zutalw Sound: Volume reset to 1.0 (Instant) in restart_game");
        }
    } catch(e) { console.warn("zutalw Sound Reset Error", e); }
    // ----------------------------------------------

    if (!data || !data.length) {
        if (typeof createCanvas === 'function') createCanvas(0, 0);
        return;
    }

    // Ensure global dataconfig is set just in case
    dataconfig = data;

    let response;
    try { response = JSON.parse(data[0].dataconfig); } catch (e) { console.error("zutalw Error parsing dataconfig inner JSON", e); return; }

    _maxc = 10;
    
    // Assign Colors
    colodd = (response[0] && response[0].colodd) ? response[0].colodd : colodd;
    colevent = (response[0] && response[0].colevent) ? response[0].colevent : colevent;
    colwin = (response[0] && response[0].colwin) ? response[0].colwin : colwin;
    coltextwin = (response[0] && response[0].coltextwin) ? response[0].coltextwin : coltextwin;
    coltextodd = (response[0] && response[0].coltextodd) ? response[0].coltextodd : coltextodd;
    coltexteven = (response[0] && response[0].coltexteven) ? response[0].coltexteven : coltexteven;
    colbut = (response[0] && response[0].colbut) ? response[0].colbut : colbut;
    coltextbut = (response[0] && response[0].coltextbut) ? response[0].coltextbut : coltextbut;
    sizetextbut = parseFloat((response[0] && response[0].sizetextbut) ? response[0].sizetextbut : sizetextbut);
    textbut = (response[0] && response[0].textbut) ? response[0].textbut : textbut;
    colbutpress = (response[0] && response[0].colbutpress) ? response[0].colbutpress : colbutpress;
    coltextpress = (response[0] && response[0].coltextpress) ? response[0].coltextpress : coltextpress;

    if (typeof clear === 'function') clear();

    // --- CLEANUP ---
    if (initial && !mode_admin) {
        // Deep Clean Matter.js Bodies
        if (acrshapes.length > 0) acrshapes.forEach(a => { if(a && a.body) World.remove(world, a.body); });
        if (boundaries.length > 0) boundaries.forEach(b => { if(b && b.body) World.remove(world, b.body); });
        if (particles.length > 0) particles.forEach(p => { if(p && p.body) World.remove(world, p.body); });
        if (engine) Events.off(engine);
        
        angle = 0;
        speed = random ? random(0.03, 0.09) : 0.05;
    } else {
        speed = 0;
    }

    acrshapes = []; boundaries = []; particles = []; buts = [];

    winnerFound = false;
    canplay = false;

    let currentW = w_default();
    let currentH = h_default();
    centerX = currentW / 2;
    centerY = currentH / 2;
    
    // Set Win Point to the Right Edge (3 o'clock)
    let effectiveRadius = (w_arc / 2) * Math.cos(PI / 8); 
    x_win0 = centerX + effectiveRadius; 
    y_win0 = centerY; 

    gravi = (typeof createVector === 'function') ? createVector(0, 0.2) : { x: 0, y: 0.2 };
    if (world && world.gravity) world.gravity.y = 0.98;

    let params = { isStatic: true };
    let start = 0, stop = PI/4;

    // Create slices
    for (let i = 0; i < 8; i++) {
        let angleBase = PI / 8 + i * distan;
        let xm0 = centerX + Math.cos(angleBase) * (w_arc / 2) * Math.cos(PI / 8);
        let ym0 = centerY + Math.sin(angleBase) * (w_arc / 2) * Math.cos(PI / 8);

        let background = (i % 2 === 0) ? (response[0] && response[0].backgroundodd ? response[0].backgroundodd : colodd) : (response[0] && response[0].backgroundeven ? response[0].backgroundeven : colevent);
        let txtcol = (i % 2 === 0) ? (response[0] && response[0].colorodd ? response[0].colorodd : coltextodd) : (response[0] && response[0].coloreven ? response[0].coloreven : coltexteven);
        let label = (response[i + 1] && response[i + 1].label) ? response[i + 1].label : ('Item ' + (i + 1));
        let fontsize = parseInt(response[0] && response[0].fontsize ? response[0].fontsize : 16);

        let arc = new Arcshape(
            xm0, ym0,
            10, w_arc, h_arc,
            start, stop,
            background, '',
            label, i, txtcol,
            '', 40, 25, fontsize
        );
        acrshapes.push(arc);
        start = stop;
        stop += distan;
    }

    // Create boundaries
    boundaries.push(new Boundary(0, currentH / 2, 0.1, currentH, params));
    boundaries.push(new Boundary(currentW, currentH / 2, 0.1, currentH, params));
    boundaries.push(new Boundary(currentW / 2, 0, currentW, 0.1, params));
    boundaries.push(new Boundary(currentW / 2, currentH, currentW, 0.1, params));

    if (valid > 0) {
        let b = new Particle(xa, ya, 60, "", "", "", colbut);
        buts.push(b);
    }
    initial = true;
}

// =======================================================
// MODIFIED: HANDLES SPIN LOGIC FROM SERVER (RANDOM/WEIGHTED)
// =======================================================

function mousePressed() {
    
    // 1. Security & State Checks
    if (window.isGameLocked === true) return;
    if (window.isPopupOpen === true) return;

    for (let bt of buts) {
        // Check if Spin button is clicked
        if (bt && typeof bt.clicked === 'function' && bt.clicked() && !s_rotated && valid > 0) {
            
            if (window.isCheckingServer) return;

            const runServerCheck = (recaptchaToken = '') => {
                
                console.log("zutalw Debug: Requesting spin result from Server...");
                window.isCheckingServer = true;
                document.body.style.cursor = 'wait';

                // FIX: Added 'zutalw_' prefix to action name
                // FIX: Use ZutalwConfig for global settings
                let data = {
                    action: 'zutalw_get_spin_result', 
                    security: window.ZutalwConfig.nonce,
                    device_id: window.ZutalwConfig.device_id || 'unknown',
                    recaptcha_token: recaptchaToken,
                    idcampain: _idcampain,
                    license: _license
                };

                jQuery.ajax({
                    url: window.ZutalwConfig.ajax_url, // Now uses the auto-detected URL
                    type: 'POST',
                    data: data,
                    success: function(response) {
                        window.isCheckingServer = false;
                        document.body.style.cursor = 'default';

                        if (response.success) {
                            // --- SUCCESS: SERVER RETURNED A WINNER ---
                            console.log("zutalw Debug: Server result received. Target Segment:", response.data.segment_id);
                            
                            // Start the physics simulation to land on this specific segment
                            start_physics_spin(response.data.segment_id);

                        } else {
                            // --- ERROR / DENIED ---
                            console.warn("zutalw Debug: Server Denied - " + (response.data ? response.data.message : 'Unknown error'));
                            let msg = (response.data && response.data.message) ? response.data.message : "Error processing spin.";
                            
                            // Check for new prefix function name zutalw_show_notice
                            if (typeof zutalw_show_notice === 'function') {
                                zutalw_show_notice(msg);
                            } else {
                                alert(msg);
                            }
                        }
                    },
                    error: function(err) {
                        window.isCheckingServer = false;
                        document.body.style.cursor = 'default';
                        console.error("zutalw Error: Could not connect to server", err);
                    }
                });
            };

            // Recaptcha handling (kept from your original code)
            const siteKey = window.ZutalwConfig.recaptcha_site_key;
            if (siteKey && typeof grecaptcha !== 'undefined') {
                grecaptcha.ready(function() {
                    grecaptcha.execute(siteKey, {action: 'spin_wheel'}).then(function(token) {
                        runServerCheck(token);
                    }, function(error) {
                        runServerCheck('');
                    });
                });
            } else {
                runServerCheck('');
            }
            return; 
        }
    }
}

/**
 * NEW FUNCTION: CALCULATE PHYSICS TO LAND ON TARGET
 * This bridges the Random/Weighted result from PHP to the Matter.js physics.
 */
function start_physics_spin(targetIndex) {
    
    // 1. Setup Game State
    if (!dataconfig) return; // Safety check
    restart_game(dataconfig); // Reset bodies
    s_rotated = true;
    canplay = true; 
    _count++;

    // 2. Play Sound
    try { 
        if (winsound && typeof winsound.stop === 'function') winsound.stop();
        if (startsound) {
            if (typeof startsound.stop === 'function') startsound.stop();
            if (typeof startsound.setVolume === 'function') startsound.setVolume(1.0, 0);
            if (typeof startsound.jump === 'function') startsound.jump(0);
            startsound.play();
        }
    } catch (e) { }

    // 3. PHYSICS CALCULATION (THE MAGIC PART)
    // We use the formula: v = sqrt(2 * a * d)
    // v = initial speed, a = friction, d = total distance (radians)

    const friction = 0.0001; // Must match the value in your draw() function: speed -= 0.0001
    const totalSegments = 8;
    const segmentAngle = (2 * Math.PI) / totalSegments; // 45 degrees in radians

    // Calculate the angle of the target segment
    // Note: This depends on where your pointer is. Assuming pointer is at 0 radians (Right side).
    // And assuming Segment 0 starts at 0.
    // We calculate how much we need to rotate so the Target center aligns with Pointer.
    
    // Ensure index is integer
    let idx = parseInt(targetIndex);
    
    // Base rounds (Spin at least 5 times)
    const minRounds = 5; 
    const baseDistance = minRounds * 2 * Math.PI;

    // Calculate offset to land specifically on the target
    // We want the wheel to STOP when the Target Segment is at the Winning Point.
    // If the wheel rotates clockwise, we need to calculate the inverse position.
    
    // Calculate the current center angle of the target segment (in initial state)
    // Example: Seg 0 is at PI/8 (22.5 deg). Seg 1 is at 3PI/8.
    let currentTargetAngle = (Math.PI / 8) + (idx * segmentAngle);
    
    // The pointer is at x_win0. In standard circle math, if x_win0 is right-center, that's angle 0 (or 2PI).
    // Let's assume the pointer is at Angle 0.
    // Distance needed = (2PI - currentTargetAngle) + Random noise (to land inside the segment, not just edge)
    
    // Add randomness within the segment (avoid landing on lines)
    // +/- 40% of the segment width
    let noise = (Math.random() * (segmentAngle * 0.8)) - (segmentAngle * 0.4); 
    
    let targetRotation = (2 * Math.PI) - currentTargetAngle + noise;
    
    // Total radians to travel
    let totalDistance = baseDistance + targetRotation;

    // Apply Physics Formula: v = sqrt(2 * a * s)
    // We add a tiny buffer because simulation isn't perfect float math
    let calculatedSpeed = Math.sqrt(2 * friction * totalDistance);

    // 4. Apply Speed
    speed = calculatedSpeed;
    
    console.log(`zutalw Physics: Target Index ${idx}, Distance ${totalDistance.toFixed(2)}, Speed set to ${speed.toFixed(5)}`);
}

// ===================== DRAW LOOP (FIXED VOLUME LOGIC) =====================
function draw() {
    if (!document.getElementById("area-game")) return false;
    clear();
    // --- DECELERATION & AUDIO FADE ---
    if (typeof frameCount !== 'undefined' && frameCount > 100) {
        if (speed < 0.0003) {
            if (speed !== 0) { 
                speed = 0; 
                // Stop sound volume only when totally stopped
                try { if (startsound) startsound.setVolume(0, 0.1); } catch (e) {}
            }
        } else {
            // --- SOUND FIX: SMART FADE ---
            try {
                // Only manage volume if we are actually in play mode
                if (startsound && canplay) { 
                    let vol = startsound.getVolume();
                    
                    // If speed is still high (> 0.02), FORCE volume max to prevent premature fading
                    if (speed > 0.02) {
                        if (vol < 1.0) startsound.setVolume(1.0, 0); // Instant back to 1
                    } else {
                        // Fade out slowly as it stops
                        let newVol = Math.max(0, vol - 0.005);
                        startsound.setVolume(newVol, 0); // Update volume
                    }
                }
            } catch (e) {}
            // -----------------------------
            speed -= 0.0001;
        }
    }

    // --- UPDATE OBJECTS ---
    acrshapes.forEach(a => {
        if (!a) return;
        if (typeof a.update === 'function') a.update();
        if (typeof a.show === 'function') a.show();
    });

    // --- FIND WINNER (DISTANCE SORTING) ---
    if (speed === 0 && !winnerFound && initial && canplay && acrshapes.length > 0) {
        
        let minDistance = 999999;
        let winningSlice = null;

        acrshapes.forEach(a => {
            let d = Math.sqrt( Math.pow(a.x - x_win0, 2) + Math.pow(a.y - y_win0, 2) );
            if (d < minDistance) {
                minDistance = d;
                winningSlice = a;
            }
        });

        if (winningSlice) {
            winnerFound = true; 
            canplay = false; 

            if (typeof winningSlice.triggerWin === 'function') {
                winningSlice.triggerWin();
            }
        }
    }

    boundaries.forEach(b => { if (b && typeof b.show === 'function') b.show(); });
    buts.forEach(bt => { if (bt && typeof bt.show === 'function') bt.show(); });

    try { Engine.update(engine); } catch (e) {}
}

// ===================== SAFE SOUND LOADING =====================
function loadsound() {
    try {
        if (typeof p5 !== 'undefined' && p5 && typeof p5.SoundFile === 'function') {
            try { 
                ding  = new p5.SoundFile(_plugin_dir + "sound/ding.mp3"); 
            } catch (e) { ding = makeSoundStub(); }
            
            try { 
                touch = new p5.SoundFile(_plugin_dir + "sound/touch1.mp3"); 
            } catch (e) { touch = makeSoundStub(); }
            
            try { 
                win = new p5.SoundFile(_plugin_dir + "sound/win1.mp3", 
                    () => console.log("zutalw Sound: Base Win sound loaded")
                ); 
            } catch (e) { win = makeSoundStub(); }
            
            try { 
                error = new p5.SoundFile(_plugin_dir + "sound/error.mp3"); 
            } catch (e) { error = makeSoundStub(); }
        } else { ding = touch = win = error = makeSoundStub(); }
    } catch (e) { ding = touch = win = error = makeSoundStub(); }
}
document.body.addEventListener("mouseover", function () { if (!initsound) { loadsound(); initsound = true; } }, { once: true });
document.body.addEventListener("touchstart", function () { if (!initsound) { loadsound(); initsound = true; } }, { once: true });