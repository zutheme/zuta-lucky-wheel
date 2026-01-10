/**
 * setupgame.js — Admin config editor (CLEAN VERSION)
 */

let objconfig = [];

/* ---------------- LOAD CONFIG ---------------- */
if (typeof LuckyWheelFront !== "undefined" && LuckyWheelFront.getConfig) {
    try {
        objconfig = JSON.parse(LuckyWheelFront.getConfig);
    } catch (e) {
        console.error("Config JSON parse error:", e);
        objconfig = [];
    }
} else {
    console.warn("LuckyWheelFront.getConfig missing");
}

/* Avoid conflict with sketch.js */
var ltwLoader = document.querySelector(".ltw-popup-processing") || null;


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
    if (!objconfig[row]) return;

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
    // LuckyWheelFront.nonce was defined in class-ltw-admin.php
    let url = LuckyWheelFront.ajax_url + "?action=UpdateConfig&security=" + LuckyWheelFront.nonce;
    let params = JSON.stringify({
        dataconfig: objconfig,
        idconfiggame: id,
        nameconfig: "config",
        level: 1,
        license: 0
    });

    if (ltwLoader) ltwLoader.style.display = "block";

    http.open("POST", url, true);
    http.setRequestHeader("Accept", "application/json");
    http.setRequestHeader("Content-Type", "application/json; charset=utf-8");

    http.onreadystatechange = function(){
        if (http.readyState === 4){

            if (ltwLoader) ltwLoader.style.display = "none";

            if (http.status === 200){
                console.log("Config saved");

                /* Reload preview */
                try {
                    initial = false;
                    speed = 0;
                    mode_admin = true;
                    loadconfig = false;

                    if (typeof getdatascore === "function") {
                        getdatascore(restart_game);
                    }
                } catch(e){
                    console.warn("Preview reload error:", e);
                }

            } else {
                console.error("Save error:", http.status, http.responseText);
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
