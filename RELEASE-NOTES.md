This file contains the RELEASE-NOTES of the Semantic Glossary (a.k.a. SG) extension.

### SG 2.0.0 (2016-03-09)

* New minimum required versions:
  * MediaWiki 1.26
  * Semantic MediaWiki 2.3
  * Lingo 2.0
* Use the new extension registration mechanism introduced in MediwWiki 1.25
* Rework registration of properties and MW hooks
* Use autoloader provided by Composer (PSR-4)

### SG 1.1.2 (2015-09-26)

* Use `QueryResult::getCountValue` where available to make it compliant with Semantic Mediawiki 2.3
* Localisation updates

### SG 1.1.1 (2014-10-15)

* Improved bootstrap test autoloader

### SG 1.1.0 (2014-08-10)

* Added support for Semantic Mediawiki 2.0
* Added maintenance script "rebuildGlossaryCache.php" to rebuild glossary cache and update pages that contain a glossary term annotation
* Extended refactoring of the codebase
* I18n-system migrated from php- to json-files (by Siebrand Mazeland)


### SG 1.0.0 (2014-03-04)

* Started using semantic versioning
* Added caching
* Support setting per-term CSS styles added (by Nathan Douglas)
* Support synonyms introduced (by Yevheniy Vlasenko during Google Summer of Code 2013)
* Support the Composer dependency manager for PHP

### SG 0.1 (2011-10-30)

* Initial release
