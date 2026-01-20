/**
 * setupgame.js — Admin config editor (UNIFIED & CLEAN VERSION)
 */

let objconfig = [];

/* ---------------- LOAD CONFIG ---------------- */
// FIX: Use ZutalwConfig (unified name)
if (typeof ZutalwConfig !== "undefined" && ZutalwConfig.getConfig) {
    try {
        objconfig = JSON.parse(ZutalwConfig.getConfig);
    } catch (e) {
        console.error("Config JSON parse error:", e);
        objconfig = [];
    }
} else {
    console.warn("ZutalwConfig.getConfig missing");
    objconfig = []; // Init empty to prevent errors
}

/* Avoid conflict with sketch.js */
var zutalwLoader = document.querySelector(".zutalw-popup-processing") || null;


/* ---------------- INIT COLOR PICKER ---------------- */
jQuery(document).ready(function($){

    const options = {
        defaultColor: false,
        change: function(event, ui){
            let item = event.target.closest("li");
            let preview = item.querySelector(".wp-color-result");

            if (!preview) return;

            let rgb = preview.style.backgroundColor.match(/\d+/g);
            if (!rgb) return;

            let hex = rgbToHex(rgb[0], rgb[1], rgb[2]);

            updateObj(event.target.dataset.key, hex, event.target.dataset.row);
        },
        hide: true,
        palettes: true
    };

    $(".my-color-field").wpColorPicker(options);
});

/* ---------------- COLOR HELPERS ---------------- */
function toHex(c){
    let h = Number(c).toString(16);
    return h.length === 1 ? "0"+h : h;
}
function rgbToHex(r,g,b){
    return "#" + toHex(r)+toHex(g)+toHex(b);
}


/* ---------------- UPDATE LOCAL CONFIG ---------------- */
function updateObj(key, value, row){
    row = parseInt(row);
    if (!objconfig[row]) {
        // Init row if missing (e.g. adding new items dynamically in future)
        objconfig[row] = {}; 
    }

    objconfig[row][key] = value;
    saveConfig();
}

function change_content(el){
    updateObj(el.dataset.key, el.value, el.dataset.row);
}


/* ---------------- AJAX SAVE CONFIG ---------------- */
function saveConfig(){

    let id = getParam("config") || 1;

    let http = new XMLHttpRequest();
    
    // FIX: Use ZutalwConfig for URL and Nonce
    // FIX: Action name must match 'wp_ajax_zutalw_save_config' in class-zutalw-ajax.php
    let url = ZutalwConfig.ajax_url + "?action=zutalw_save_config&security=" + ZutalwConfig.nonce;
    
    let params = JSON.stringify({
        dataconfig: objconfig,
        idconfiggame: id,
        nameconfig: "config",
        level: 1,
        license: 0
    });

    if (zutalwLoader) zutalwLoader.style.display = "block";

    http.open("POST", url, true);
    http.setRequestHeader("Accept", "application/json");
    http.setRequestHeader("Content-Type", "application/json; charset=utf-8");

    http.onreadystatechange = function(){
        if (http.readyState === 4){

            if (zutalwLoader) zutalwLoader.style.display = "none";

            if (http.status === 200){
                console.log("Config saved successfully");

                /* Reload preview in sketch.js */
                try {
                    // Reset game state variables (global vars in sketch.js)
                    if(typeof initial !== 'undefined') initial = false;
                    if(typeof speed !== 'undefined') speed = 0;
                    if(typeof mode_admin !== 'undefined') mode_admin = true;
                    if(typeof loadconfig !== 'undefined') loadconfig = false;

                    // Trigger reload via sketch.js function
                    if (typeof getdatascore === "function") {
                        getdatascore(restart_game);
                    }
                } catch(e){
                    console.warn("Preview reload error:", e);
                }

            } else {
                console.error("Save error:", http.status, http.responseText);
                // Optional: Alert user if save failed
                if(http.status === 403) {
                     console.error("Nonce/Security check failed (403).");
                }
            }
        }
    };

    http.send(params);
}


/* ---------------- UTIL ---------------- */
function getParam(name){
    let m = window.location.search.match(new RegExp("[?&]"+name+"=([^&]+)"));
    return m ? m[1] : "";
}

function change_config(){
    let form = document.getElementById("form-config");
    if (form) form.submit();
}