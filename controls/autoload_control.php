<?php namespace cahnrswp\cahnrs\categories;
/**
 * Load peripheral class(es)
 */

class autoload_control {


	public function __construct() {

		spl_autoload_register( array( $this, 'autoload' ) );

	}


	private function autoload( $class ) {

		if ( strpos( $class, __NAMESPACE__ ) !== 0 ) return;
		$class = ( strpos( $class, __NAMESPACE__ ) !== false ) ? str_replace( __NAMESPACE__, '', $class ) : $class;
		$class = str_replace( '\\', '', $class );
		if ( strpos( $class, '_model' ) !== false ) { include DIR . 'models/' . $class . '.php'; }
		else if ( strpos( $class, '_view' ) !== false ) { include DIR . 'views/' . $class . '.php'; }
		else if ( strpos( $class, '_control' ) !== false ) { include DIR . 'controls/' . $class . '.php'; }
		else if ( strpos( $class, '_app' ) !== false ) { include DIR . 'app/'.$class . '.php'; }
		else if ( strpos( $class, '_abstract' ) !== false ) { include DIR . 'abstract/' . $class . '.php'; }

	}


}
?>