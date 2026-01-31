<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class ZUTALW_Shortcode {

    public function __construct() {
         add_shortcode( 'zutalw_lucky_spin', array( $this, 'shortcode_output' ) );
    }

    public function shortcode_output( $atts = array(), $content = '' ) {
        $a = shortcode_atts( array(
            'id' => '1',
            'license' => '0',
        ), $atts );

        return '<div id="area-game" data-id="' . esc_attr( $a['id'] ) . '" data-license="' . esc_attr( $a['license'] ) . '"></div>';
    }
}