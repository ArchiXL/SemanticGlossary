<?php

namespace SG;

/**
 * @ingroup SG
 *
 * @licence GNU GPL v2+
 * @since 1.0
 *
 * @author Stephan Gambke
 */
class DataComparator {

	protected $store = null;
	protected $semanticData = null;

	/**
	 * @since 1.0
	 *
	 * @param SMWStore $store
	 * @param SMWSemanticData $semanticData
	 */
	public function __construct( \SMWStore $store, \SMWSemanticData $semanticData ) {
		$this->store = $store;
		$this->semanticData = $semanticData;
	}

	/**
	 * @since 1.0
	 *
	 * @param string $propertId
	 *
	 * @return boolean
	 */
	public function byPropertyId( $propertId ) {

		list( $newEntries, $oldEntries ) = $this->lookupPropertyValues( $propertId );

		if ( $this->hasNotSamePropertyValuesCount( $newEntries, $oldEntries ) ) {
			return true;
		}

		if ( $this->hasUnmatchPropertyValue( $newEntries, $oldEntries ) ) {
			return true;
		}

		return false;
	}

	protected function lookupPropertyValues( $propertId ) {

		$properties = $this->semanticData->getProperties();

		if ( array_key_exists( $propertId, $properties ) ) {

			$newEntries = $this->semanticData->getPropertyValues( $properties[$propertId] );
			$oldEntries = $this->store->getPropertyValues(
				$this->semanticData->getSubject(),
				$properties[$propertId]
			);

			return array(
				$newEntries,
				$oldEntries
			);
		}

		$newEntries = array();
		$oldEntries = $this->store->getPropertyValues(
			$this->semanticData->getSubject(),
			new \SMWDIProperty( $propertId )
		);

		return array(
			$newEntries,
			$oldEntries
		);
	}

	protected function hasNotSamePropertyValuesCount( $newEntries, $oldEntries ) {
		return count( $newEntries ) !== count( $oldEntries );
	}

	protected function hasUnmatchPropertyValue( $newEntries, $oldEntries ) {

		foreach ( $newEntries as $newDi ) {
			$found = false;
			foreach ( $oldEntries as $oldKey => $oldDi ) {
				if ( $newDi->getHash() === $oldDi->getHash() ) {
					$found = true;
					unset( $oldEntries[$oldKey] );
					break;
				}
			}

			// If no match was possible...
			if ( !$found ) {
				return true;
			}
		}

		// Are there unmatched old entries left?
		if ( count( $oldEntries ) > 0 ) {
			return true;
		}

		return false;
	}

}
