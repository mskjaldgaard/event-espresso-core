<?php
if ( !defined( 'EVENT_ESPRESSO_VERSION' ) ) {
	exit( 'No direct script access allowed' );
}

/**
 *
 * EE_Restriction_Generator_Protected
 *
 * @package			Event Espresso
 * @subpackage
 * @author				Mike Nelson
 *
 */
class EE_Restriction_Generator_Protected extends EE_Restriction_Generator_Base{

	public static function generate_restrictions( $model, $action ) {

		//if there are no standard caps for this model, then for now all we know
		//if they need the default cap to access this
		if( ! $model->cap_slug() ) {
			return array(
				self::get_default_restrictions_cap() => new EE_Return_None_Where_Conditions()
			);
		}

		$restrictions = array();

		//does the basic cap exist? (eg 'ee_read_registrations')
		if( self::is_cap($model, $action) ) {
			$restrictions[ self::get_cap_name($model, $action) ] = new EE_Return_None_Where_Conditions();
			//does the others cap exist? (eg 'ee_read_others_registrations')
			if( self::is_cap($model, $action . '_others' ) ) {//both caps exist
				$restrictions[ self::get_cap_name($model, $action . '_others' ) ] = new EE_Owner_Only_Where_Conditions();
			}
			//does the private cap exist (eg 'ee_read_others_private_events')
			if( self::is_cap( $model, $action . '_private' ) && $model instanceof EEM_CPT_Base ){
				//if they have basic and others, but not private, restrict them to see theirs and others' that aren't private
				$restrictions[ self::get_cap_name($model, $action . '_private' ) ] = new EE_Default_Where_Conditions( array(
						'OR*private' => array(
							$model->get_foreign_key_to( 'WP_User' )->get_name() => get_current_user_id(),
							'status' => array( 'IN', array( 'publish', 'private' ) ) )
						)
				);
			}
		}else{
			//there is no basic cap. So they can only access this if they have the default admin cap
			$restrictions[ self::get_default_restrictions_cap() ] = new EE_Return_None_Where_Conditions();
		}
		return $restrictions;
	}
}

// End of file EE_Restriction_Generator_Protected.strategy.php