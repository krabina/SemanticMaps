<?php

/**
 * Initialization file for query printer functionality in the Semantic Maps extension
 *
 * @file SM_QueryPrinters.php
 * @ingroup SemanticMaps
 *
 * @author Jeroen De Dauw
 */

if ( !defined( 'MEDIAWIKI' ) ) {
	die( 'Not an entry point.' );
}

$wgAutoloadClasses['SMQueryPrinters'] = __FILE__;

$wgHooks['MappingFeatureLoad'][] = 'SMQueryPrinters::initialize';

final class SMQueryPrinters {
	
	public static $parameters = array();
	
	/**
	 * Initialization function for Maps query printer functionality.
	 */
	public static function initialize() {
		global $smgDir, $wgAutoloadClasses;
		
		$wgAutoloadClasses['SMMapper'] 	= dirname( __FILE__ ) . '/SM_Mapper.php';
		$wgAutoloadClasses['SMMapPrinter'] 	= dirname( __FILE__ ) . '/SM_MapPrinter.php';
		
		self::initializeParams();
		
		$hasQueryPrinters = false;

		foreach ( MapsMappingServices::getServiceIdentifiers() as $serviceIdentifier ) {
			$service = MapsMappingServices::getServiceInstance( $serviceIdentifier );	
				
			// Check if the service has a query printer.
			$QPClass = $service->getFeature( 'qp' );
			
			// If the service has no QP, skipt it and continue with the next one.
			if ( $QPClass === false ) continue;
			
			// At least one query printer will be enabled when this point is reached.
			$hasQueryPrinters = true;
			
			// Initiate the format.
			$aliases = $service->getAliases();
			self::initFormat( $service->getName(), $QPClass, $aliases );
		}

		// Add the 'map' result format if there are mapping services that have QP's loaded.
		if ( $hasQueryPrinters ) self::initFormat( 'map', 'SMMapper' );
		
		return true;
	}
	
	// TODO
	private static function initializeParams() {
		global $egMapsDefaultServices, $egMapsAvailableGeoServices, $egMapsDefaultGeoService, $egMapsMapWidth, $egMapsMapHeight;
		global $smgQPForceShow, $smgQPShowTitle, $smgQPTemplate;

		// TODO
		//$params['geoservice']->setDefault( $egMapsDefaultGeoService );
		//$params['mappingservice']->setDefault( $egMapsDefaultServices['qp'] );		
		
		$params = array();
		
		$params['centre'] = new Parameter(
			'centre',
			Parameter::TYPE_STRING,
			'', // TODO
			array( 'center' ),
			array(
				new CriterionIsLocation(),
			)			
		);
		
		$params['centre']->lowerCaseValue = false;			
		
		$params['icon'] = new Parameter(
			'icon',
			Parameter::TYPE_STRING,
			'', // TODO
			array(),
			array(
				New CriterionNotEmpty()
			)
		);	
		
		$params['icon']->lowerCaseValue = false;
		
		$params['forceshow'] = new Parameter(
			'forceshow',
			Parameter::TYPE_BOOLEAN,
			$smgQPForceShow,
			array( 'force show' )
		);
		
		// TODO
		$params['forceshow']->outputTypes = array( 'boolean' => array( 'boolean' ) );		

		$params['showtitle'] = new Parameter(
			'showtitle',
			Parameter::TYPE_BOOLEAN,
			$smgQPShowTitle,
			array( 'show title' )
		);
		
		// TODO
		$params['showtitle']->outputTypes = array( 'boolean' => array( 'boolean' ) );		
		
		$params['template'] = new Parameter(
			'template',
			Parameter::TYPE_STRING,
			$smgQPTemplate,
			array(),
			array(
				New CriterionNotEmpty()
			)
		);
		
		$params['template']->lowerCaseValue = false;
		
		self::$parameters = $params;
	}
	
	/**
	 * Add the result format for a mapping service or alias.
	 *
	 * @param string $format
	 * @param string $formatClass
	 * @param array $aliases
	 */
	private static function initFormat( $format, $formatClass, array $aliases = array() ) {
		global $wgAutoloadClasses, $smgDir, $smwgResultAliases;

		// Add the QP to SMW.
		self::addFormatQP( $format, $formatClass );

		// If SMW supports aliasing, add the aliases to $smwgResultAliases.
		if ( isset( $smwgResultAliases ) ) {
			$smwgResultAliases[$format] = $aliases;
		}
		else { // If SMW does not support aliasing, add every alias as a format.
			foreach ( $aliases as $alias ) self::addFormatQP( $alias, $formatClass );
		}
	}

	/**
	 * Adds a QP to SMW's $smwgResultFormats array or SMWQueryProcessor
	 * depending on if SMW supports $smwgResultFormats.
	 * 
	 * @param string $format
	 * @param string $class
	 */
	private static function addFormatQP( $format, $class ) {
		global $smwgResultFormats;
		
		if ( isset( $smwgResultFormats ) ) {
			$smwgResultFormats[$format] = $class;
		}
		else {
			SMWQueryProcessor::$formats[$format] = $class;
		}
	}
	
}