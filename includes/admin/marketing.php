<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;


/**
 * Creates all marketing notices for Blox
 *
 * @since 1.0.0
 *
 * @package Blox
 * @author  Nicholas Diego
 */
class Blox_Marketing {
 
    /**
     * Holds the class object.
     *
     * @since 1.0.0
     *
     * @var object
     */
    public static $instance;


    /**
     * Path to the file.
     *
     * @since 1.0.0
     *
     * @var string
     */
    public $file = __FILE__;


    /**
     * Holds the base class object.
     *
     * @since 1.0.0
     *
     * @var object
     */
    public $base;


    /**
     * Primary class constructor.
     *
     * @since 1.0.0
     */
    public function __construct() {

        // Load the base class object.
        $this->base = Blox_Lite_Main::get_instance();
		
		add_filter( 'blox_settings_misc', array( $this, 'disable_marketing_notices' ) );

		add_action( 'blox_settings_form_top', array( $this, 'settings_upgrade_notice' ) );
		add_action( 'blox_tab_container_after', array( $this, 'settings_upgrade_notice' ) );
    }
    
    
    /**
     * Add setting option to disable all marketing notices
     *
     * @since 1.0.0
     */
    public function disable_marketing_notices( $misc_settings ) {
    
    	$misc_settings['disable_marketing_notices'] = array(
			'id'   => 'disable_marketing_notices',
			'name'  => __( 'Marketing Notices', 'blox' ),
			'label' => __( 'Check to disable all those annoying marketing notices!', 'blox' ),
			'desc'  => sprintf( __( 'But seriously though, the full version of Blox takes Blox Lite to the next level and comes with great support. %1$sLearn More%2$s.', 'blox' ), '<a href="https://www.bloxwp.com/?utm_source=blox-lite&utm_medium=plugin&utm_content=marketing-links&utm_campaign=Blox_Plugin_Links" target="_blank">', '</a>' ),
			'type'  => 'checkbox',
			'default' => false
		);
					
		return $misc_settings;
	}

    
    /**
     * Print upgrade notice on settings tabs
     *
     * @since 1.0.0
     */
    public function settings_upgrade_notice() {
    
    	$disable_notices = blox_get_option( 'disable_marketing_notices', '' );
    
    	if ( ! $disable_notices ) {
			?>
			<div class="blox-alert blox-alert-warning">
				<?php echo sprintf( __( 'Enjoying %1$sBlox Lite%2$s but looking for more content options, visibility settings, hooks control, priority support, frequent updates and more? Then you should consider %3$supgrading%4$s to %1$sBlox%2$s. Happy with the free version and have no need to upgrade? Then you might as well turn off these notifications in the plugin %5$ssettings%4$s.', 'blox' ), '<strong>', '</strong>', '<a href="https://www.bloxwp.com/?utm_source=blox-lite&utm_medium=plugin&utm_content=marketing-links&utm_campaign=Blox_Plugin_Links" target="_blank">', '</a>', '<a href="' . admin_url( 'edit.php?post_type=blox&page=blox-settings&tab=misc' ) . '">' ); ?>
			</div>
			<?php
		}
    }
    

    /**
     * Returns the singleton instance of the class.
     *
     * @since 1.0.0
     *
     * @return object The class object.
     */
    public static function get_instance() {

        if ( ! isset( self::$instance ) && ! ( self::$instance instanceof Blox_Marketing ) ) {
            self::$instance = new Blox_Marketing();
        }

        return self::$instance;
    }
} 
// Load the class.
$blox_marketing = Blox_Marketing::get_instance();
  