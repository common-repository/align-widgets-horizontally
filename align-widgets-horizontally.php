<?php
/**
 * Plugin Name: Align widgets horizontally
 * Plugin URI: https://12net.jp/plugins/align-widgets-horizontally.html
 * Description: Arrange the widgets on the dashboard horizontally.
 * Author: tmatsuur
 * Author URI: https://12net.jp/tmatsuur.html
 * Version: 1.0.1
 * License: GPLv2 or later
 */

namespace jp12net;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

use jp12net\align_widgets_horizontally;

$plugin_align_widgets_horizontally = new align_widgets_horizontally();

class align_widgets_horizontally {
    const DEFAULT_SETTINGS = array(
        'upper_grow_number'       => 5,
        'gap_number'              => 20,
        'unit_gap_number'         => 'px',
        'height_empty_container'  => 300, // px
        'upper_height_helper'     => 300, // px
        'gray_border_color'       => '#c3c4c7',
        'dark_border_color'       => '#646970',
        'prefix_text_grow'        => '\02715',
        'always_show_emptystring' => true,
        'emphasize_placeholders'  => true,
    );

    /**
     * Initialize the plugin.
     */
    public function __construct() {
		add_action( 'admin_footer', [ $this, 'admin_footer' ] );
        add_action( 'wp_ajax_save_widgets_grow_number', [ $this, 'ajax_save_widgets_grow_number' ], 10 );
	}

    /**
     * Creates a suffix for nonce value.
     * 
     * @return string
     */
    private function nonce_action_suffix() {
        return date( 'His-Ymd', filemtime( __FILE__ ) );
    }

    /**
     * Creates a nonce value.
     * 
     * @param int|string $action
     * @return string
     */
    private function create_nonce( $action ) {
        return wp_create_nonce( $action . $this->nonce_action_suffix() );
    }

    /**
     * Update 'grow_numer_dashboar' in user meta data.
     * 
     * @see wp_ajax_save_widgets_grow_number action.
     */
    public function ajax_save_widgets_grow_number() {
        check_ajax_referer( 'save_widgets_grow_number' . $this->nonce_action_suffix() );

        $data = array();
        if ( isset( $_POST['numbers'] ) && is_array( $_POST['numbers'] ) ) {
            $data['numbers'] = array_map( 'intval', $_POST['numbers'] );
            $user = wp_get_current_user();
            update_user_meta( $user->ID, 'grow_number_dashboard', $data['numbers'] );
        }
        wp_send_json_success( $data );
    }

    /**
     * Sanitize each setting value.
     * 
     * @param array $settings
     * @return array
     */
    private function sanitize_settings( $settings ) {
        if ( is_int( $settings['upper_grow_number'] ) ) {
            if ( 1 > $settings['upper_grow_number'] ) {
                $settings['upper_grow_number'] = 1;
            } elseif ( 10 < $settings['upper_grow_number'] ) {
                $settings['upper_grow_number'] = 10;
            }
        } else {
            $settings['upper_grow_number'] = self::DEFAULT_SETTINGS['upper_grow_number'];
        }

        if ( is_int( $settings['gap_number'] ) || is_float( $settings['gap_number'] ) ) {
            if ( 0 > $settings['gap_number'] ) {
                $settings['gap_number'] = 0;
            }
        } else {
            $settings['gap_number'] = self::DEFAULT_SETTINGS['gap_number'];
        }

        if ( ! is_string( $settings['unit_gap_number'] ) ||
            ! in_array( $settings['unit_gap_number'], array( 'px', 'em', 'rem', '%' ) ) ) {
            $settings['unit_gap_number'] = self::DEFAULT_SETTINGS['unit_gap_number'];
        }

        if ( is_int( $settings['height_empty_container'] ) ) {
            if ( 50 > $settings['height_empty_container'] ) {
                $settings['height_empty_container'] = 50;
            } elseif ( 500 < $settings['height_empty_container'] ) {
                $settings['height_empty_container'] = 500;
            }
        } else {
            $settings['height_empty_container'] = self::DEFAULT_SETTINGS['height_empty_container'];
        }

        if ( is_int( $settings['upper_height_helper'] ) ) {
            if ( 50 > $settings['upper_height_helper'] ) {
                $settings['upper_height_helper'] = 50;
            } elseif ( 500 < $settings['upper_height_helper'] ) {
                $settings['upper_height_helper'] = 500;
            }
        } else {
            $settings['upper_height_helper'] = self::DEFAULT_SETTINGS['upper_height_helper'];
        }

        if ( ! is_string( $settings['gray_border_color'] ) ||
            empty( sanitize_hex_color( $settings['gray_border_color'] ) ) ) {
            $settings['gray_border_color'] = self::DEFAULT_SETTINGS['gray_border_color'];
        }

        if ( ! is_string( $settings['dark_border_color'] ) ||
            empty( sanitize_hex_color( $settings['dark_border_color'] ) ) ) {
            $settings['dark_border_color'] = self::DEFAULT_SETTINGS['dark_border_color'];
        }

        if ( ! is_string( $settings['prefix_text_grow'] ) ) {
            $settings['prefix_text_grow'] = self::DEFAULT_SETTINGS['prefix_text_grow'];
        }

        if ( ! is_bool( $settings['always_show_emptystring'] ) ) {
            $settings['always_show_emptystring'] = self::DEFAULT_SETTINGS['always_show_emptystring'];
        }

        if ( ! is_bool( $settings['emphasize_placeholders'] ) ) {
            $settings['emphasize_placeholders'] = self::DEFAULT_SETTINGS['emphasize_placeholders'];
        }
        return $settings;
    }

    /**
     * Minified script.
     * 
     * @param string $text
     * @return string
     */
    private function minified_script( $text ) {
        if ( ! ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ) {
            $text = implode( '', array_map( 'trim', explode( "\n", $text ) ) ) . "\n";
        }
        return $text;
    }

    /**
     * Displays gap number with unit.
     * 
     * @param float|int|string $number
     * @param string $unit
     * @param int $multiplier
     */
    private function print_gap_number( $number, $unit, $multiplier = 1 ) {
        $out = '';
        $unit = esc_attr( $unit );
        if ( ! is_int( $multiplier ) ) {
            $multiplier = 1;
        }
        if ( is_float( $number ) ) {
            $out = sprintf( '%.3f%s', $number * $multiplier, $unit );
        } elseif ( is_int( $number ) ) {
            $out = sprintf( '%d%s', $number * $multiplier , $unit );
        } else {
            $out = self::DEFAULT_SETTINGS['gap_number'] * $multiplier . 'px';
        }
        echo esc_js( $out );
    }

    /**
     * Output css and javascript only for dashboard.
     * 
     * @see admin_footer action.
     */
    public function admin_footer() {
		if ( 'index.php' === $GLOBALS['pagenow'] ) {
            $user    = wp_get_current_user();
            $numbers = get_user_meta( $user->ID, 'grow_number_dashboard', true );

            $settings = array();
            /**
             * Filters the settings.
             * 
             * @since 1.0.0
             * 
             * @param array $settings {
             *     The array of settings.
             * 
             *     @type int    $upper_grow_number       Default 5.
             *     @type int    $gap_number              Default 20.
             *     @type string $unit_gap_number         Default `px`.
             *     @type int    $height_empty_container  Default 300.
             *     @type int    $upper_height_helper     Default 300.
             *     @type string $gray_border_color       Default `#c3c4c7`.
             *     @type string $dark_border_color       Default `#646970`.
             *     @type string $prefix_text_grow        Default `\02715`.
             *     @type bool   $always_show_emptystring Default true.
             *     @type bool   $emphasize_placeholders  Default true.
             * }
             */
            $settings = apply_filters( __CLASS__ . '\settings', $settings );
            if ( ! is_array( $settings ) ) {
                $settings = array();
            }
            $settings = wp_parse_args( $settings, self::DEFAULT_SETTINGS );
            $settings = $this->sanitize_settings( $settings );

            ob_start();
?>
<style id="align-widgets-horizontally-css">
:root {
    --gray-border-color: <?php echo esc_js( $settings['gray_border_color'] ); ?>;
    --dark-border-color: <?php echo esc_js( $settings['dark_border_color'] ); ?>;
}
#dashboard-widgets .postbox-container {
	width: 100% !important;
}
#dashboard-widgets .postbox-container .empty-container {
    height: <?php echo esc_js( $settings['height_empty_container'] ); ?>px !important;
    margin-bottom: <?php $this->print_gap_number( $settings['gap_number'], $settings['unit_gap_number'] ); ?> !important;
}
.is-dragging-metaboxes #dashboard-widgets .postbox-container .meta-box-sortables {
    outline: 3px dashed var(--dark-border-color) !important;
}
#dashboard-widgets .postbox-container .empty-container {
    outline: 3px dashed var(--gray-border-color) !important;
}
<?php if ( $settings['always_show_emptystring'] ) { ?>
#dashboard-widgets .postbox-container .empty-container:after {
    display: block !important;
}
<?php } ?>
#dashboard-widgets .postbox-container .meta-box-sortables {
	display: flex !important;
    gap: <?php $this->print_gap_number( $settings['gap_number'], $settings['unit_gap_number'] ); ?>;
    flex-direction: row;
    flex-wrap: wrap;
}
#dashboard-widgets .meta-box-sortables {
    min-height: 38px;
    margin: 0 8px <?php $this->print_gap_number( $settings['gap_number'], $settings['unit_gap_number'] ); ?>;
}
.postbox,
.sortable-placeholder {
	flex-grow: 1;
	flex-shrink: 1;
	flex-basis: 0;
	margin-bottom: 0;
}
<?php if ( $settings['emphasize_placeholders'] ) { ?>
.sortable-placeholder {
    background: linear-gradient( 90deg, rgba( 0, 0, 0, .05 ) 50%, transparent 50% ), linear-gradient( rgba( 0, 0, 0, .05 ) 50%, transparent 50% );
    background-size: 40px 40px;
}
<?php } ?>
<?php for ( $grow = 2; $grow <= $settings['upper_grow_number']; $grow++ ) { ?>
[data-grow="<?php echo esc_js( $grow ); ?>"].postbox,
[data-grow="<?php echo esc_js( $grow ); ?>"].sortable-placeholder {
	flex-grow: <?php echo esc_js( $grow ); ?>;
    flex-basis: <?php $this->print_gap_number( $settings['gap_number'], $settings['unit_gap_number'], ( $grow - 1 ) ); ?>;
}
<?php } ?>
.ui-sortable-helper {
    overflow: hidden;
}
.postbox.closed {
	border-left: 1px solid transparent;
	border-right: 1px solid transparent;
	background-color: transparent;
}
.postbox.closed .postbox-header {
	background-color: #fff;
	border-left: 1px solid var(--gray-border-color);
	border-right: 1px solid var(--gray-border-color);
}
.handle-actions .handle-order-lower,
.handle-actions .handle-order-higher {
	transform: rotate( -90deg );
}
.metabox-prefs label {
    line-height: 2.75;
}
.metabox-prefs label span.prefix-grow {
	position: relative;
	display: inline-block;
	margin: 0 0 0 .5em;
}
.metabox-prefs label span.prefix-grow::before {
	content: '<?php echo esc_js( $settings['prefix_text_grow'] ) ?>';
	position: absolute;
	left: .5em;
	top: 0;
}
.metabox-prefs label input.widget-grow {
	width: 4em;
	text-align: right;
}
</style>
<?php
            echo wp_kses( $this->minified_script( ob_get_clean() ), array(
                'style' => array(
                    'id' => array()
                )
            ) );
            ob_start();
?>
<script id="align-widgets-horizontally-js">
const init_grow_number = { <?php
    if ( is_array( $numbers ) ) {
        foreach ( $numbers as $key => $value ) {
            printf( "'%s': %d,", esc_js( $key ), esc_js( $value ) );
        }
    }
?> };
document.querySelectorAll( '.metabox-prefs-container > label' ).forEach( ( widget ) => {
	let widget_name = widget.getAttribute( 'for' ).replace( '-hide', '' );
	if ( 'wp_welcome_panel' !== widget_name ) {
        const grow_number = init_grow_number[widget_name] && 0 < init_grow_number[widget_name] ?
            init_grow_number[widget_name]: 1;
        if ( 1 < grow_number ) {
            document.getElementById( widget_name ).dataset.grow = grow_number;
        }
		const grow = document.createElement( 'input' );
		grow.setAttribute( 'type', 'number' );
		grow.setAttribute( 'min', 1 );
		grow.setAttribute( 'max', <?php echo esc_js( $settings['upper_grow_number'] ); ?> );
		grow.setAttribute( 'value', grow_number );
		grow.classList.add( 'widget-grow' );
		grow.dataset.widget = widget_name;
		const span = document.createElement( 'span' );
		span.classList.add( 'prefix-grow' );
		span.append( grow );
		widget.append( span );
		grow.addEventListener( 'change', ( event ) => {
			document.getElementById( event.target.dataset.widget ).dataset.grow = event.target.value;
            save_widgets_grow_number();
		} );
	}
} );
let timeoutID = null;
function save_widgets_grow_number() {
    if ( null !== timeoutID ) {
        clearTimeout( timeoutID );
    }
    timeoutID = setTimeout( () => {
        timeoutID = null;
        const params = new URLSearchParams();
        params.append( 'action', 'save_widgets_grow_number' );
        params.append( '_ajax_nonce', '<?php echo esc_js( $this->create_nonce( 'save_widgets_grow_number' ) ); ?>' );
        document.querySelectorAll( '.widget-grow' ).forEach( ( grow ) => {
            params.append( 'numbers[' + grow.dataset.widget + ']', grow.value );
        } );
        fetch( ajaxurl, {
            method:      'POST',
            cache:       'no-cache',
            credentials: 'same-origin',
            body:        params
        } )
        .then( ( response ) => response.json() )
        .then( ( data ) => {} );
    }, 1000 );
}
jQuery( function ($) {
    $(window).on( 'load', function () {
        let currentLeft = null;
        $( '.meta-box-sortables' ).sortable( {
            start: function ( event, ui ) {
                $( 'body' ).addClass( 'is-dragging-metaboxes' );
                if ( ui.helper.width() != ui.item.width() || ui.helper.height() != ui.item.height() ) {
                    const xDiff = ui.helper.width() - ui.placeholder.width();
                    ui.helper.width( ui.placeholder.width() );

                    let yDiff = 0;
                    if ( ui.helper.height() > ui.placeholder.height() ) {
                        const helperHeight = Math.min( ui.item.height(), <?php echo esc_js( $settings['upper_height_helper'] ); ?> );
                        yDiff = ui.helper.height() - helperHeight;
                        ui.helper.height( helperHeight );
                    }

                    if ( ui.placeholder && ui.item[0].dataset.grow ) {
                        ui.placeholder[0].dataset.grow = ui.item[0].dataset.grow;
                    }
                    currentLeft = null;

                    const props = Object.entries( $(this)[0] );
                    props.forEach( ( prop ) => {
                        if ( prop[1].uiSortable ) {
                            let uiSortable = prop[1].uiSortable;
                            uiSortable.containment[2] += xDiff;
                            uiSortable.containment[3] += yDiff;
                        }
                    } );
                }
            }
        } );
        $( '.meta-box-sortables' ).sortable( {
            sort: function ( event, ui ) {
                if ( ui.helper[0] ) {
                    if ( null === currentLeft ) {
                        currentLeft = event.screenX - $(ui.placeholder[0]).offset().left;
                    }
                    ui.helper[0].style.left = ( event.screenX - currentLeft - $(this).offset().left ) + 'px';
                }
            }
        } );
    } );
} );
</script>
<?php
             echo $this->minified_script( ob_get_clean() );
		}
	}
}