<?php

/**
 * File holding the SemanticGlossaryBackend class
 *
 * @author Stephan Gambke
 * @file
 * @ingroup SemanticGlossary
 */
if ( !defined( 'SG_VERSION' ) ) {
	die( 'This file is part of the SemanticGlossary extension, it is not a valid entry point.' );
}

/**
 * The SemanticGlossaryBackend class.
 *
 * @ingroup SemanticGlossary
 */
class SemanticGlossaryBackend extends LingoBackend {

	//array of SMWDIWikiPage
	protected $mQueryResults;

	protected $mDiTerm;
	protected $mDiDefinition;
	protected $mDiLink;
	protected $mDiStyle;

	protected $mDvTerm;
	protected $mDvDefinition;
	protected $mDvLink;
	protected $mDvStyle;

	protected $mStore;

	public function __construct( LingoMessageLog &$messages = null ) {

		parent::__construct( $messages );

		// get the store
		$this->mStore = smwfGetStore();

		// build term data item and data value for later use
		$this->mDiTerm = new SMWDIProperty( '___glt' );
		$this->mDvTerm = new SMWStringValue( '_str' );
		$this->mDvTerm->setProperty( $this->mDiTerm );

		$pvTerm = new SMWPropertyValue( '__pro' );
		$pvTerm->setDataItem( $this->mDiTerm );
		$prTerm = new SMWPrintRequest( SMWPrintRequest::PRINT_PROP, null, $pvTerm );

		// build definition data item and data value for later use
		$this->mDiDefinition = new SMWDIProperty( '___gld' );
		$this->mDvDefinition = new SMWStringValue( '_txt' );
		$this->mDvDefinition->setProperty( $this->mDiDefinition );

		$pvDefinition = new SMWPropertyValue( '__pro' );
		$pvDefinition->setDataItem( $this->mDiDefinition );
		$prDefinition = new SMWPrintRequest( SMWPrintRequest::PRINT_PROP, null, $pvDefinition );

		// build link data item and data value for later use
		$this->mDiLink = new SMWDIProperty( '___gll' );
		$this->mDvLink = new SMWStringValue( '_str' );
		$this->mDvLink->setProperty( $this->mDiLink );

		$pvLink = new SMWPropertyValue( '__pro' );
		$pvLink->setDataItem( $this->mDiLink );
		$prLink = new SMWPrintRequest( SMWPrintRequest::PRINT_PROP, null, $pvLink );

    // build style data item and data value for later use
    $this->mDiStyle = new SMWDIProperty( '___gls' );
		$this->mDvStyle = new SMWStringValue( '_txt' );
		$this->mDvStyle->setProperty( $this->mDiStyle );

		$pvStyle = new SMWPropertyValue( '__pro' );
		$pvStyle->setDataItem( $this->mDiStyle );
		$prStyle = new SMWPrintRequest( SMWPrintRequest::PRINT_PROP, null, $pvStyle );

		// Create query
		$desc = new SMWSomeProperty( new SMWDIProperty( '___glt' ), new SMWThingDescription() );
		$desc->addPrintRequest( $prTerm );
		$desc->addPrintRequest( $prDefinition );
		$desc->addPrintRequest( $prLink );
		$desc->addPrintRequest( $prStyle );

		$query = new SMWQuery( $desc, false, false );
		$query->sort = true;
		$query->sortkeys['___glt'] = 'ASC';

		// get the query result
		$this->mQueryResults = $this->mStore->getQueryResult( $query )->getResults();
	}

	/**
	 * This function returns the next element. The element is an array of four
	 * strings: Term, Definition, Link, Source, Style. If there is no next element 
	 * the function returns null.
	 *
	 * @return the next element or null
	 */
	public function next() {

		wfProfileIn( __METHOD__ );
		static $ret = array();

		// find next line
		$page = current( $this->mQueryResults );

		if ( $page && count( $ret ) == 0 ) {

			next( $this->mQueryResults );

			// Try cache first
			global $wgexLingoCacheType;
			$cache = ($wgexLingoCacheType !== null) ? wfGetCache( $wgexLingoCacheType ) : wfGetMainCache();
			$cachekey = wfMemcKey( 'ext', 'semanticglossary', $page->getSerialization() );
			$cachedResult = $cache->get( $cachekey );

			// cache hit?
			if ( $cachedResult !== false && $cachedResult !== null ) {

				wfDebug( "Cache hit: Got glossary entry $cachekey from cache.\n" );
				$ret = &$cachedResult;
			} else {

				wfDebug( "Cache miss: Glossary entry $cachekey not found in cache.\n" );

				$terms = $this->mStore->getPropertyValues( $page, $this->mDiTerm );
				$definitions = $this->mStore->getPropertyValues( $page, $this->mDiDefinition );
				$links = $this->mStore->getPropertyValues( $page, $this->mDiLink );
				$styles = $this->mStore->getPropertyValues( $page, $this->mDiStyle );

				if ( empty( $definitions ) ) {
					$definition = null;
				} else {
					$this->mDvDefinition->setDataItem( $definitions[0] );
					$definition = $this->mDvDefinition->getShortWikiText();
				}

				if ( empty( $links ) ) {
					$link = null;
				} else {
					$this->mDvLink->setDataItem( $links[0] );
					$link = $this->mDvLink->getShortWikiText();
				}

				if ( empty( $styles ) ) {
				  $style = null;
				} else {
				  $this->mDvStyle->setDataItem( $styles[0] );
				  $style = $this->mDvStyle->getShortWikiText();
				}

				$tmp_terms = array();

				if ( !empty( $terms ) ) {
					foreach ( $terms as $term ) {
						$this->mDvTerm->setDataItem( $term );
						$tmp_terms[] = $this->mDvTerm->getShortWikiText();
					}
				}
				
				foreach ( $tmp_terms as $tmp_term ) {
						$tmp_ret = array(
							LingoElement::ELEMENT_TERM => $tmp_term,
							LingoElement::ELEMENT_DEFINITION => $definition,
							LingoElement::ELEMENT_LINK => $link,
							LingoElement::ELEMENT_STYLE => $style,
							LingoElement::ELEMENT_SOURCE => $page
						);

						wfDebug( "Cached glossary entry $cachekey.\n" );
						$ret[] = $tmp_ret;
				}

				$cache->set( $cachekey, $ret );
			}
		}

		wfProfileOut( __METHOD__ );
		return array_pop($ret);
	}

	/**
	 * This backend is cache-enabled so this function returns true.
	 *
	 * Actual caching is done by the parser, the backend just calls
	 * LingoParser::purgeCache when necessary.
	 *
	 * @return boolean
	 */
	public function useCache() {
		return true;
	}
}
