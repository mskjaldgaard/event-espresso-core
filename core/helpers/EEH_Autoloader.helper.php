<?php
if (!defined('EVENT_ESPRESSO_VERSION') )
	exit('NO direct script access allowed');

/**
 * EEH_Autoloader
 *
 * This is a helper utility class for setting up autoloaders.
 *
 * @package		Event Espresso
 * @subpackage	/helpers/EEH_Autoloader.helper.php
 * @author		Darren Ethier
 *
 * ------------------------------------------------------------------------
 */
class EEH_Autoloader extends EEH_Base {

	/**
	* 	$_autoloaders
	* 	@var array $_autoloaders
	* 	@access 	private
	*/
	private static $_autoloaders;



	/**
	 *    class constructor
	 *
	 * @access    public
	 * @return \EEH_Autoloader
	 */
	public function __construct() {
		if ( self::$_autoloaders === null ) {
			self::$_autoloaders = array();
			$this->_register_custom_autoloaders();
			spl_autoload_register( array( $this, 'espresso_autoloader' ) );
		}
	}



	/**
	 * @access public
	 * @return EEH_Autoloader
	 */
	public static function instance() {
		//return EE_Registry::instance()->load_helper( __CLASS__ );
	}



	/**
	 *    espresso_autoloader
	 *
	 * @access    public
	 * @param 	$class_name
	 * @internal  param $className
	 * @internal  param string $class_name - simple class name ie: session
	 * @return 	void
	 */
	public static function espresso_autoloader( $class_name ) {
		if ( isset( self::$_autoloaders[ $class_name ] ) ) {
			require_once( self::$_autoloaders[ $class_name ] );
		}
	}



	/**
	 *    register_autoloader
	 *
	 * @access    public
	 * @param array | string $class_paths - array of key => value pairings between class names and paths
	 * @param bool           $read_check true if we need to check whether the file is readable or not.
	 * @param bool           $debug - set to true to display autoloader class => path mappings
	 * @return void
	 * @throws \EE_Error
	 */
	public static function register_autoloader( $class_paths, $read_check = true, $debug = false ) {
		$class_paths = is_array( $class_paths ) ? $class_paths : array( $class_paths );
		foreach ( $class_paths as $class => $path ) {
			// don't give up! you gotta...
			// get some class
			if ( empty( $class )) {
				throw new EE_Error ( sprintf( __( 'No Class name was specified while registering an autoloader for the following path: %s.','event_espresso' ), $path ));
			}
			// one day you will find the path young grasshopper
			if ( empty( $path )) {
				throw new EE_Error ( sprintf( __( 'No path was specified while registering an autoloader for the %s class.','event_espresso' ), $class ));
			}
			// is file readable ?
			if ( $read_check && ! is_readable( $path )) {
				throw new EE_Error ( sprintf( __( 'The file for the %s class could not be found or is not readable due to file permissions. Please ensure the following path is correct: %s','event_espresso' ), $class, $path ));
			}
			if ( ! isset( self::$_autoloaders[ $class ] )) {
				self::$_autoloaders[ $class ] = str_replace( array( '/', '\\' ), DS, $path );
				if ( WP_DEBUG && $debug ) {
					EEH_Debug_Tools::printr( $class, '$class', __FILE__, __LINE__ );
					EEH_Debug_Tools::printr( self::$_autoloaders[ $class ], $class, __FILE__, __LINE__ );
				}
			}
		}
	}




	/**
	 * 	get_autoloaders
	 *
	 * 	@access public
	 * 	@return array
	 */
	public static function get_autoloaders() {
		return self::$_autoloaders;
	}




	/**
	 * 	register core, model and class 'autoloaders'
	 *
	 * 	@access private
	 * 	@return void
	 */
	private function _register_custom_autoloaders() {
		EEH_Autoloader::register_autoloaders_for_each_file_in_folder( EE_CORE . 'interfaces' );
		EEH_Autoloader::register_autoloaders_for_each_file_in_folder( EE_CORE );
		EEH_Autoloader::register_autoloaders_for_each_file_in_folder( EE_INTERFACES, true );
		EEH_Autoloader::register_autoloaders_for_each_file_in_folder( EE_MODELS, true );
		EEH_Autoloader::register_autoloaders_for_each_file_in_folder( EE_CLASSES );
		EEH_Autoloader::register_autoloaders_for_each_file_in_folder( EE_FORM_SECTIONS, true );
	}




	/**
	 * 	register core, model and class 'autoloaders'
	 *
	 * 	@access public
	 * 	@return void
	 */
	public static function register_form_sections_autoloaders() {
		//EEH_Autoloader::register_autoloaders_for_each_file_in_folder( EE_FORM_SECTIONS, true );
	}




	/**
	 * 	register core, model and class 'autoloaders'
	 *
	 * 	@access public
	 * 	@return void
	 */
	public static function register_line_item_display_autoloaders() {
		EEH_Autoloader::register_autoloaders_for_each_file_in_folder(  EE_LIBRARIES . 'line_item_display' , true );
	}




	/**
	 * 	register core, model and class 'autoloaders'
	 *
	 * 	@access public
	 * 	@return void
	 */
	public static function register_line_item_filter_autoloaders() {
		EEH_Autoloader::register_autoloaders_for_each_file_in_folder(  EE_LIBRARIES . 'line_item_filters' , true );
	}



	/**
	 * Assumes all the files in this folder have the normal naming scheme (namely that their classname
	 * is the file's name, plus ".whatever.php".) and adds each of them to the autoloader list.
	 * If that's not the case, you'll need to improve this function or just use EEH_File::get_classname_from_filepath_with_standard_filename() directly.
	 * Yes this has to scan the directory for files, but it only does it once -- not on EACH
	 * time the autoloader is used
	 *
	 * @param string $folder name, with or without trailing /, doesn't matter
	 * @param bool   $recursive
	 * @param bool   $debug - set to true to display autoloader class => path mappings
	 * @return void
	 * @throws \EE_Error
	 */
	public static function register_autoloaders_for_each_file_in_folder( $folder, $recursive = false, $debug = false ){
		// make sure last char is a /
		$folder .= $folder[strlen($folder)-1] != DS ? DS : '';
		$class_to_filepath_map = array();
		$exclude = array( 'index' );
		//get all the files in that folder that end in php
		$filepaths = glob( $folder.'*');

		if ( empty( $filepaths ) ) {
			return;
		}

		foreach( $filepaths as $filepath ) {
			if ( substr( $filepath, -4, 4 ) == '.php' ) {
				$class_name = EEH_File::get_classname_from_filepath_with_standard_filename( $filepath );
				if ( ! in_array( $class_name, $exclude )) {
					$class_to_filepath_map [ $class_name ] = $filepath;
				}
			} else if ( $recursive ) {
				EEH_Autoloader::register_autoloaders_for_each_file_in_folder( $filepath, $recursive );
			}
		}
		// we remove the necessity to do a is_readable() check via the $read_check flag because glob by nature will not return non_readable files/directories.
		self::register_autoloader( $class_to_filepath_map, false, $debug );
	}





}
