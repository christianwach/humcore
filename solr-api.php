<?php
/**
 * API to access SOLR.
 *
 * @package HumCORE
 * @subpackage Deposits
 */

/**
 * Class using Solarium to access the SOLR API.
 */
class Humcore_Deposit_Solr_Api {

	protected $config;
	public $client;
	public $select_query;
	/* getting removed
	public $servername_hash;
	*/
	public $service_status;
	public $namespace;
	public $tempDir;

	/**
	 * Initialize SOLR API settings.
	 */
	public function __construct() {

		$path = dirname( __FILE__ ) . '/vendor/autoload.php';
		if ( is_file($path) ) {
			require_once $path;
		}

		$humcoreSettings = get_option( 'humcore-deposits-humcore-settings' );
		/* getting removed
                if ( defined( 'CORE_SOLR_HOST' ) && ! empty( CORE_SOLR_HOST ) ) { // Better have a value if defined.
                        $this->servername_hash = md5( $humcoreSettings['servername'] );
                } else {
                        $this->servername_hash = $humcoreSettings['servername_hash'];
                }
		*/

                $this->service_status = $humcoreSettings['service_status'];
                
                if ( defined( 'CORE_HUMCORE_NAMESPACE' ) && ! empty( CORE_HUMCORE_NAMESPACE ) ) {
                        $this->namespace = CORE_HUMCORE_NAMESPACE;
                } else {
                        $this->namespace = $humcoreSettings['namespace'];
                }
                if ( defined( 'CORE_HUMCORE_TEMP_DIR' ) && ! empty( CORE_HUMCORE_TEMP_DIR ) ) { 
                        $this->tempDir = CORE_HUMCORE_TEMP_DIR;
                } else {
                        $this->tempDir = $humcoreSettings['tempdir'];
                }

		$solrSettings = get_option( 'humcore-deposits-solr-settings' );

                if ( defined( 'CORE_SOLR_PROTOCOL' ) ) { 
                        $solrSettings['scheme'] = preg_replace( '~://$~', '', CORE_SOLR_PROTOCOL );
                }
                if ( defined( 'CORE_SOLR_HOST' ) ) { 
                        $solrSettings['host'] = CORE_SOLR_HOST;
                }
                if ( defined( 'CORE_SOLR_PORT' ) ) { 
                        $solrSettings['port'] = CORE_SOLR_PORT;
                }
                if ( defined( 'CORE_SOLR_PATH' ) ) { 
                        $solrSettings['path'] = CORE_SOLR_PATH;
                }
                if ( defined( 'CORE_SOLR_CORE' ) ) { 
                        $solrSettings['core'] = CORE_SOLR_CORE;
                }
		$config = array(
			'endpoint' => array(
				'solrhost' => array(
					'scheme' => preg_replace( '~://$~', '', $solrSettings['scheme'] ),
					'host' => $solrSettings['host'],
					'port' => $solrSettings['port'],
					'path' => $solrSettings['path'] . '/',
					'core' => $solrSettings['core'],
					'timeout' => 60,
				),
			),
		);

		/* getting removed
		// Prevent copying prod config data to dev.
		if ( ! empty( $this->servername_hash ) && $this->servername_hash != md5( $_SERVER['SERVER_NAME'] ) ) {
			$config['endpoint']['solrhost']['host'] = '';
		}
		*/

		$this->client = new Solarium\Client( $config );

	}

	/**
	 * Get the server status.
	 */
	public function get_solr_status() {

		$client = $this->client;
		$ping = $client->createPing();

		try {
			$result = $client->ping( $ping );

			// Begin debug.
			$query_info = $result->getQuery();
			$endpoint = $client->getEndpoint( 'solrhost' );

			$info = array(
				'url'             => $endpoint->getBaseUri(),
				'handler'         => $query_info->getHandler(),
				'status'          => $result->getStatus(),
				'response'        => $result->getData(),
			);
			if ( defined( 'CORE_HTTP_DEBUG' ) && 'true' === CORE_HTTP_DEBUG && defined( 'CORE_ERROR_LOG' ) && '' != CORE_ERROR_LOG ) {
			        humcore_write_error_log( 'info', 'solr debug', $info );
			}
			// End of debug.
			$res = $result->getData();
			if ( 'OK' !== $res['status'] ) {
				return new WP_Error( 'solrServerError', 'Solr server is not okay.', var_export( $res, true ) );
			}
			return $res;
		} catch ( Exception $e ) {

			// Begin debug.
			$endpoint = $client->getEndpoint( 'solrhost' );

			$info = array(
				'url'             => $endpoint->getBaseUri(),
				'handler'         => 'admin/ping',
				'status'          => $e->getCode(),
				'response'        => $e->getMessage(),
			);
			if ( defined( 'CORE_HTTP_DEBUG' ) && 'true' === CORE_HTTP_DEBUG && defined( 'CORE_ERROR_LOG' ) && '' != CORE_ERROR_LOG ) {
			        humcore_write_error_log( 'info', 'solr debug', $info );
			}
			// End of debug.
			return new WP_Error( 'solrServerError', $e->getStatusMessage(), $e->getMessage() );
		}

	}

	/**
	 * Create a select object.
	 */
	public function create_select( $select ) {

		$client = $this->client;

		try {
			$query = $client->createSelect( $select );
			return $query;
		} catch ( Exception $e ) {
			return 1;
		}

	}

	/**
	 * Perform a select.
	 */
	public function select( $query ) {

		$client = $this->client;

		try {
			$results = $client->select( $query );
			return $results;
		} catch ( Exception $e ) {
			return 1;
		}

	}

	/**
	 * Create a realtime get object.
	 */
	public function create_realtime_get( $get ) {

		$client = $this->client;

		try {
			$query = $client->createRealtimeGet( $get );
			return $query;
		} catch ( Exception $e ) {
			return 1;
		}

	}

	/**
	 * Perform a realtime get.
	 */
	public function realtime_get( $query ) {

		$client = $this->client;

		try {
			$results = $client->realtimeGet( $query );
			return $results;
		} catch ( Exception $e ) {
			return 1;
		}

	}

	/**
	 * Retrieve a specific document.
	 */
	public function get_humcore_document( $id ) {

		$client = $this->client;

		// Get a realtime query instance and add settings.
		$get = $client->createRealtimeGet();
		$get->addId( $id );

		try {
			$result = $client->realtimeGet( $get );
			if ( 1 == $result->getNumFound() ) {
				$document = $result->getDocument();
				$search_result = array();
				$record = array();
				$record['id'] = $document->id;
				$record['pid'] = $document->pid;
				$record['title'] = $document->title_display;
				$record['title_unchanged'] = $document->title_unchanged;
				if ( '' == $document->title_unchanged ) {
					$record['title_unchanged'] = $document->title_display;
				}
				$record['abstract'] = $document->abstract;
				$record['abstract_unchanged'] = $document->abstract_unchanged;
				if ( '' == $document->abstract_unchanged ) {
					$record['abstract_unchanged'] = $document->abstract;
				}
				$record['date'] = $document->pub_date_facet[0];
				$record['authors'] = $document->author_facet;
				$record['author_info'] = $document->author_info;
				$record['group'] = $document->group_facet;
				$record['society_id'] = $document->society_facet;
				$record['language'] = $document->language_facet;
				$record['type_of_license'] = $document->_facet;
				$record['organization'] = $document->organization_facet;
				$record['subject'] = $document->subject_facet;
				$record['keyword'] = $document->keyword_search;
				$record['keyword_display'] = $document->keyword_display;
				$record['handle'] = $document->handle;
				$record['genre'] = $document->genre_facet[0];
				if ( ! empty( (array)$document->notes ) ) {
					$record['notes'] = implode( ' ', (array)$document->notes );
				}
				if ( ! empty( (array)$document->notes_unchanged ) ) {
					$record['notes_unchanged'] = implode( ' ', (array)$document->notes_unchanged );
				}
				if ( empty( (array)$document->notes_unchanged ) && ! empty( (array)$document->notes ) ) {
					$record['notes_unchanged'] = implode( ' ', (array)$document->notes );
				}
				$record['book_journal_title'] = $document->book_journal_title;
				$record['book_author'] = $document->book_author[0];
				$record['publisher'] = $document->publisher;
				$record['isbn'] = $document->isbn;
				$record['issn'] = $document->issn;
				$record['doi'] = $document->doi;
				$record['volume'] = $document->volume;
				$record['date_issued'] = $document->date_issued;
				$record['issue'] = $document->issue;
				$record['chapter'] = $document->book_chapter;
				$record['start_page'] = $document->start_page;
				$record['end_page'] = $document->end_page;
				$record['institution'] = $document->institution;
				$record['conference_title'] = $document->conference_title;
				$record['conference_organization'] = $document->conference_organization;
				$record['conference_location'] = $document->conference_location;
				$record['conference_date'] = $document->conference_date;
				$record['meeting_title'] = $document->meeting_title;
				$record['meeting_organization'] = $document->meeting_organization;
				$record['meeting_location'] = $document->meeting_location;
				$record['meeting_date'] = $document->meeting_date;
				$record['publication_type'] = $document->publication_type;
				$record['language'] = $document->language; //TODO convert solr contents to language_facet
				$record['type_of_resource'] = $document->type_of_resource_facet[0];
				$record['record_content_source'] = $document->record_content_source;
				$record['record_creation_date'] = $document->record_creation_date;
				$record['record_change_date'] = $document->record_change_date;
				$record['record_identifier'] = $document->record_identifier;
				$record['member_of'] = $document->member_of;
				$record['embargo_end_date'] = $document->free_to_read_start_date;
				$search_result['documents'][] = $record ;
				$search_result['total'] = 1;

				return $search_result;
			}
			return 1;
		} catch ( Exception $e ) {
			return 1;
		}
	}

	/**
	 * Create a document with full text extract.
	 */
	public function create_humcore_extract( $file, $metadata ) {

		$client = $this->client;

		// Get an extract query instance and add settings.
		$query = $client->createExtract();
		// Is this field mapping needed??
		$query->addFieldMapping( 'content', 'text' );
		$query->setUprefix( 'ignored_' );
		$query->setFile( $file );
		$query->setOmitHeader( false );
		$query->setCommit( true );
		// Add document.
		$doc = $query->createDocument();
		$doc->id = $metadata['id'];
		$doc->pid = $metadata['pid'];
		$doc->language = $metadata['language']; //TODO convert solr docs to user language_facet
		$doc->title_display = $metadata['title'];
		$doc->title_search = $metadata['title'];
		$doc->title_unchanged = $metadata['title_unchanged'];
		$author_uni = array();
		$author_fullname = array();
		foreach ( $metadata['authors'] as $author ) {
			$author_uni[] = $author['uni'];
			$author_fullname[] = $author['fullname'];
		}
		$doc->author_uni = array_filter( $author_uni );
		$doc->author_search = array_filter( $author_fullname );
		$doc->author_facet = array_filter( $author_fullname );
		$doc->author_display = implode( ', ', array_filter( $author_fullname ) );
		$doc->author_info = $metadata['author_info'];
		if ( ! empty( $metadata['organization'] ) ) {
			$doc->organization_facet = array( $metadata['organization'] );
		}
		// Genre is not an array in MODS record.
		if ( ! empty( $metadata['genre'] ) ) {
			$doc->genre_facet = array( $metadata['genre'] );
			$doc->genre_search = array( $metadata['genre'] );
		}
		$doc->group_facet = $metadata['group'];
		if ( ! empty( $metadata['society_id'] ) ) {
			$doc->society_facet = $metadata['society_id'];
		}
		$doc->language_facet = $metadata['language'];
		$doc->license_facet = $metadata['type_of_license'];
		if ( ! empty( $metadata['subject'] ) ) {
			$doc->subject_facet = $metadata['subject'];
			$doc->subject_search = $metadata['subject'];
		}
		if ( ! empty( $metadata['keyword'] ) ) {
			$doc->keyword_display = implode( ', ', $metadata['keyword'] );
			$doc->keyword_search = array_map( 'strtolower', $metadata['keyword'] );
		}
		$doc->abstract = $metadata['abstract'];
		$doc->abstract_unchanged = $metadata['abstract_unchanged'];
		$doc->handle = $metadata['handle'];
		$doc->notes = array( $metadata['notes'] );
		$doc->notes_unchanged = array( $metadata['notes_unchanged'] );
		if ( ! empty( $metadata['book_journal_title'] ) ) { $doc->book_journal_title = $metadata['book_journal_title']; }
		if ( ! empty( $metadata['book_author'] ) ) { $doc->book_author = array( $metadata['book_author'] ); }
		if ( ! empty( $metadata['publisher'] ) ) { $doc->publisher = $metadata['publisher']; }
		if ( ! empty( $metadata['isbn'] ) ) { $doc->isbn = $metadata['isbn']; }
		if ( ! empty( $metadata['issn'] ) ) { $doc->issn = $metadata['issn']; }
		if ( ! empty( $metadata['doi'] ) ) { $doc->doi = $metadata['doi']; }
		if ( ! empty( $metadata['volume'] ) ) { $doc->volume = $metadata['volume']; }
		if ( ! empty( $metadata['date'] ) ) { $doc->date = $metadata['date']; }
		if ( ! empty( $metadata['issue'] ) ) { $doc->issue = $metadata['issue']; }
		if ( ! empty( $metadata['chapter'] ) ) { $doc->book_chapter = $metadata['chapter']; }
		if ( ! empty( $metadata['start_page'] ) ) { $doc->start_page = $metadata['start_page']; }
		if ( ! empty( $metadata['end_page'] ) ) { $doc->end_page = $metadata['end_page']; }
		if ( ! empty( $metadata['institution'] ) ) { $doc->institution = $metadata['institution']; }
		if ( ! empty( $metadata['conference_title'] ) ) { $doc->conference_title = $metadata['conference_title']; }
		if ( ! empty( $metadata['conference_organization'] ) ) { $doc->conference_organization = $metadata['conference_organization']; }
		if ( ! empty( $metadata['conference_location'] ) ) { $doc->conference_location = $metadata['conference_location']; }
		if ( ! empty( $metadata['conference_date'] ) ) { $doc->conference_date = $metadata['conference_date']; }
		if ( ! empty( $metadata['meeting_title'] ) ) { $doc->meeting_title = $metadata['meeting_title']; }
		if ( ! empty( $metadata['meeting_organization'] ) ) { $doc->meeting_organization = $metadata['meeting_organization']; }
		if ( ! empty( $metadata['meeting_location'] ) ) { $doc->meeting_location = $metadata['meeting_location']; }
		if ( ! empty( $metadata['meeting_date'] ) ) { $doc->meeting_date = $metadata['meeting_date']; }
		if ( ! empty( $metadata['publication_type'] ) ) { $doc->publication_type = $metadata['publication_type']; }
		$doc->date_issued = $metadata['date_issued'];
		$doc->pub_date_facet = array( $metadata['date_issued'] );
		if ( ! empty( $metadata['type_of_resource'] ) ) {
			$doc->type_of_resource_facet = array( $metadata['type_of_resource'] );
		}
		$doc->record_content_source = $metadata['record_content_source'];
		$doc->record_creation_date = $metadata['record_creation_date'];
		$doc->record_change_date = $metadata['record_change_date'];
		$doc->record_identifier = $metadata['record_identifier'];
		$doc->member_of = $metadata['member_of'];
                if ( ! empty( $metadata['embargo_end_date'] ) ) {
                        $doc->free_to_read_start_date = $metadata['embargo_end_date'];
                }

		$query->setDocument( $doc );

		// This executes the query and returns the result.
		try {
			$result = $client->extract( $query );
		} catch ( Exception $e ) {
			humcore_write_error_log( 'error', '***Error trying to create Solr Document using Extract***' . var_export( $e->getMessage(), true ) );

			// Begin debug.
			$query_info = $result->getQuery();
			$endpoint = $client->getEndpoint( 'solrhost' );

			$info = array(
				'url'             => $endpoint->getBaseUri(),
				'handler'         => $query_info->getHandler(),
				'file'            => $query_info->getOption( 'file' ),
				'fields'          => $doc->getFields(),
				'status'          => $result->getStatus(),
				'time'            => $result->getQueryTime(),
			);
			if ( defined( 'CORE_HTTP_DEBUG' ) && 'true' === CORE_HTTP_DEBUG && defined( 'CORE_ERROR_LOG' ) && '' != CORE_ERROR_LOG ) {
			        humcore_write_error_log( 'info', 'solr debug', $info );
			}
			        humcore_write_error_log( 'info', 'solr debug', $info );
			// End of debug.
			throw $e;
		}

		humcore_write_error_log( 'info', 'Create Solr Document using Extract ', array( $result->getData() ) );

		// Begin debug.
		$query_info = $result->getQuery();
		$endpoint = $client->getEndpoint( 'solrhost' );

		$info = array(
			'url'             => $endpoint->getBaseUri(),
			'handler'         => $query_info->getHandler(),
			'file'            => $query_info->getOption( 'file' ),
			'fields'          => $doc->getFields(),
			'status'          => $result->getStatus(),
			'time'            => $result->getQueryTime(),
		);
		if ( defined( 'CORE_HTTP_DEBUG' ) && 'true' === CORE_HTTP_DEBUG && defined( 'CORE_ERROR_LOG' ) && '' != CORE_ERROR_LOG ) {
			humcore_write_error_log( 'info', 'solr debug', $info );
		}
		// End of debug.
		return true;

	}

	/**
	 * Create a document without text extract.
	 */
	public function create_humcore_document( $file, $metadata ) {

		$client = $this->client;

		// Get an update query instance.
		$query = $client->createUpdate();
		// Add document.
		$doc = $query->createDocument();
		$doc->id = $metadata['id'];
		$doc->pid = $metadata['pid'];
		if ( ! empty( $file ) ) {
			$doc->content = file_get_contents( $file );
		}
		$doc->language = $metadata['language']; //TODO convert solr docs to use language_facet
		$doc->title_display = $metadata['title'];
		$doc->title_search = $metadata['title'];
		$doc->title_unchanged = $metadata['title_unchanged'];
		$author_uni = array();
		$author_fullname = array();
		foreach ( $metadata['authors'] as $author ) {
			$author_uni[] = $author['uni'];
			$author_fullname[] = $author['fullname'];
		}
		$doc->author_uni = array_filter( $author_uni );
		$doc->author_search = array_filter( $author_fullname );
		$doc->author_facet = array_filter( $author_fullname );
		$doc->author_display = implode( ', ', array_filter( $author_fullname ) );
		$doc->author_info = $metadata['author_info'];
		$doc->organization_facet = array( $metadata['organization'] );
		// Genre is not an array in MODS record.
		if ( ! empty( $metadata['genre'] ) ) {
			$doc->genre_facet = array( $metadata['genre'] );
			$doc->genre_search = array( $metadata['genre'] );
		}
		$doc->group_facet = $metadata['group'];
		$doc->society_facet = $metadata['society_id'];
		$doc->language_facet = $metadata['language'];
		$doc->license_facet = $metadata['type_of_license'];
		if ( ! empty( $metadata['subject'] ) ) {
			$doc->subject_facet = $metadata['subject'];
			$doc->subject_search = $metadata['subject'];
		}
		if ( ! empty( $metadata['keyword'] ) ) {
			$doc->keyword_display = implode( ', ', $metadata['keyword'] );
			$doc->keyword_search = array_map( 'strtolower', $metadata['keyword'] );
		}
		$doc->abstract = $metadata['abstract'];
		$doc->abstract_unchanged = $metadata['abstract_unchanged'];
		$doc->handle = $metadata['handle'];
		$doc->notes = array( $metadata['notes'] );
		$doc->notes_unchanged = array( $metadata['notes_unchanged'] );
		if ( ! empty( $metadata['book_journal_title'] ) ) { $doc->book_journal_title = $metadata['book_journal_title']; }
		if ( ! empty( $metadata['book_author'] ) ) { $doc->book_author = array( $metadata['book_author'] ); }
		if ( ! empty( $metadata['publisher'] ) ) { $doc->publisher = $metadata['publisher']; }
		if ( ! empty( $metadata['isbn'] ) ) { $doc->isbn = $metadata['isbn']; }
		if ( ! empty( $metadata['issn'] ) ) { $doc->issn = $metadata['issn']; }
		if ( ! empty( $metadata['doi'] ) ) { $doc->doi = $metadata['doi']; }
		if ( ! empty( $metadata['volume'] ) ) { $doc->volume = $metadata['volume']; }
		if ( ! empty( $metadata['date'] ) ) { $doc->date = $metadata['date']; }
		if ( ! empty( $metadata['issue'] ) ) { $doc->issue = $metadata['issue']; }
		if ( ! empty( $metadata['chapter'] ) ) { $doc->book_chapter = $metadata['chapter']; }
		if ( ! empty( $metadata['start_page'] ) ) { $doc->start_page = $metadata['start_page']; }
		if ( ! empty( $metadata['end_page'] ) ) { $doc->end_page = $metadata['end_page']; }
		if ( ! empty( $metadata['institution'] ) ) { $doc->institution = $metadata['institution']; }
		if ( ! empty( $metadata['conference_title'] ) ) { $doc->conference_title = $metadata['conference_title']; }
		if ( ! empty( $metadata['conference_organization'] ) ) { $doc->conference_organization = $metadata['conference_organization']; }
		if ( ! empty( $metadata['conference_location'] ) ) { $doc->conference_location = $metadata['conference_location']; }
		if ( ! empty( $metadata['conference_date'] ) ) { $doc->conference_date = $metadata['conference_date']; }
		if ( ! empty( $metadata['meeting_title'] ) ) { $doc->meeting_title = $metadata['meeting_title']; }
		if ( ! empty( $metadata['meeting_organization'] ) ) { $doc->meeting_organization = $metadata['meeting_organization']; }
		if ( ! empty( $metadata['meeting_location'] ) ) { $doc->meeting_location = $metadata['meeting_location']; }
		if ( ! empty( $metadata['meeting_date'] ) ) { $doc->meeting_date = $metadata['meeting_date']; }
		if ( ! empty( $metadata['publication_type'] ) ) { $doc->publication_type = $metadata['publication_type']; }
		$doc->date_issued = $metadata['date_issued'];
		$doc->pub_date_facet = array( $metadata['date_issued'] );
		if ( ! empty( $metadata['type_of_resource'] ) ) {
			$doc->type_of_resource_facet = array( $metadata['type_of_resource'] );
		}
		$doc->record_content_source = $metadata['record_content_source'];
		$doc->record_creation_date = $metadata['record_creation_date'];
		$doc->record_change_date = $metadata['record_change_date'];
		$doc->record_identifier = $metadata['record_identifier'];
		$doc->member_of = $metadata['member_of'];
		if ( ! empty( $metadata['embargo_end_date'] ) ) {
			$doc->free_to_read_start_date = $metadata['embargo_end_date'];
		}
		$query->addDocuments( array( $doc ) );
		$query->addCommit();

		// This executes the query and returns the result.
		$result = $client->update( $query );

		humcore_write_error_log( 'info', 'Create Solr Document ', array( $result->getData() ) );

		// Begin debug.
		$query_info = $result->getQuery();
		$endpoint = $client->getEndpoint( 'solrhost' );

		$info = array(
			'url'             => $endpoint->getBaseUri(),
			'handler'         => $query_info->getHandler(),
			'file'            => $query_info->getOption( 'file' ),
			'fields'          => $doc->getFields(),
			'status'          => $result->getStatus(),
			'time'            => $result->getQueryTime(),
		);
		if ( defined( 'CORE_HTTP_DEBUG' ) && 'true' === CORE_HTTP_DEBUG && defined( 'CORE_ERROR_LOG' ) && '' != CORE_ERROR_LOG ) {
			humcore_write_error_log( 'info', 'solr debug', $info );
		}
		// End of debug.
		return true;
	}

	/**
	 * Delete an existing.
	 */
	public function delete_humcore_document( $id ) {

		$client = $this->client;
		$deleteQuery = $client->createUpdate();
		$deleteQuery->addDeleteById( $id );
		$deleteQuery->addCommit();
		$result = $client->update( $deleteQuery );
		$res = $result->getData();

		humcore_write_error_log( 'info', 'Delete Solr Document ', array( $result->getData() ) );

		return isset( $res['status'] ) ? $res['status'] : '';

	}

	/**
	 * Returns array of results.
	 *
	 * Result['spellchecker']= Spellchecker-Did you mean
	 * Result['facets']= Array of Facets
	 * Result['total']= No of documents found
	 * Result['documents']= Array of documents
	 * Result['info']=Result info
	 */
	public function get_search_results( $term, $facet_options, $start, $sort, $number_of_res = 10 ) {

		$search_result = array();
		$fac_count = -1; // All the facet values.
		$lucene_reserved_characters = preg_quote( '+-&|!(){}[]^"~*?:\\' );
		$facets_array = array(
			'author_facet', 'organization_facet', 'group_facet', 'society_facet', 'language_facet', 'license_facet', 'subject_facet',
			'genre_facet', 'pub_date_facet', 'type_of_resource_facet',
		);

		if ( ! empty( $facet_options ) && ! is_array( $facet_options ) ) {
			$facet_options_parsed = wp_parse_args( $facet_options );
			$facet_options = $facet_options_parsed['facets'];
		}

		$msg = '';
		$client = $this->client;
		$query = $client->createSelect();
		$edismax = $query->getEDisMax();
		$query->setQuery( $term );

		$query->setFields( array(
			'id', 'pid', 'title_display', 'title_unchanged', 'abstract', 'abstract_unchanged', 'pub_date_facet', 'date', 'author_display',
			'author_facet', 'author_uni', 'author_info', 'organization_facet', 'group_facet', 'society_facet', 'language_facet', 'license_facet',
			'subject_facet', 'keyword_search', 'keyword_display', 'handle', 'genre_facet', 'notes', 'notes_unchanged', 'book_journal_title', 'book_author',
			'publisher', 'isbn', 'issn', 'doi', 'volume', 'issue', 'book_chapter', 'start_page', 'end_page', 'language', 'institution',
			'conference_title', 'conference_organization', 'conference_location', 'conference_date', 'meeting_title', 'meeting_organization',
			'meeting_location', 'meeting_date', 'publication_type', 'date_issued', 'type_of_resource_facet', 'record_content_source',
			'record_creation_date', 'record_change_date', 'record_identifier', 'member_of', 'free_to_read_start_date', 'score',
		) );

		if ( null != $sort ) {
			if ( 'newest' == $sort ) {
				$sort_field = 'record_creation_date';
				$sort_value = $query::SORT_DESC;
			} else if ( 'author' == $sort ) {
				$sort_field = 'author_sort';
				$sort_value = $query::SORT_ASC;
			} else if ( 'alphabetical' == $sort ) {
				$sort_field = 'title_sort';
				$sort_value = $query::SORT_ASC;
			} else {
				$sort_field = 'record_creation_date';
				$sort_value = $query::SORT_DESC;
			}
		} else {
			$sort_field = 'record_creation_date';
			$sort_value = $query::SORT_DESC;
		}

		$query->addSort( $sort_field, $sort_value );
		$query->setQueryDefaultOperator( 'AND' );

		if ( 'NOTspellchecker' == 'spellchecker' ) { // Disabled for now.

			$spellChk = $query->getSpellcheck();
			$spellChk->setCount( 10 );
			$spellChk->setCollate( true );
			$spellChk->setExtendedResults( true );
			$spellChk->setCollateExtendedResults( true );
			$resultset = $client->select( $query );

			$spell_msg = '';
			$spellChkResult = $resultset->getSpellcheck();

			if ( ! $spellChkResult->getCorrectlySpelled() ) {
				$collations = $spellChkResult->getCollations();
				$term = '';
				foreach ( $collations as $collation ) {
					foreach ( $collation->getCorrections() as $input => $correction ) {
						$term .= $correction;
					}
				}
				if ( strlen( $term ) > 0 ) {
					$err_msg = 'Did you mean: <b>' . $term . '</b><br />';
					$query->setQuery( $term );
				}
				$search_result['spellchecker'] = $err_msg;
			} else {
				$search_result['spellchecker'] = '';
			}
		} else {
			$search_result['spellchecker'] = '';
		}

		if ( ! empty( $facets_array ) ) {

			$facetSet = $query->getFacetSet();
			$facetSet->setMinCount( 1 );
			foreach ( $facets_array as $facet ) {
				$facetSet->createFacetField( $facet )->setField( $facet )->setLimit( $fac_count );
			}
		}

		$bound = '';

		if ( ! empty( $facet_options ) ) {

			foreach ( $facet_options as $facet_key => $facet_values ) {

				$value_list = implode( ',', $facet_values );
				if ( ! empty( $facet_values ) ) {
					foreach ( $facet_values as $i => $facet_value ) {
						$escaped_facet_value = preg_replace_callback(
							'/([' . $lucene_reserved_characters . '])/',
							function($matches) {
								return '\\' . $matches[0];
							},
							trim( $facet_value )
						);
						$query->addFilterQuery( array(
							'key' => $facet_key . '-' . $i,
							'query' => $facet_key . ':' . str_replace( ' ', '\ ', $escaped_facet_value ),
						) );
					}
				}
			}
		}

		if ( 0 == $start || 1 == $start ) {
			$st = 0;
		} else {
			$st = ( ( $start - 1 ) * $number_of_res );
		}

		if ( '' != $bound && $bound < $number_of_res ) {
				 $query->setStart( $st )->setRows( $bound );
		} else {
			$query->setStart( $st )->setRows( $number_of_res );
		}

//echo "QUERY",var_export($query,true),"<p>";
		$resultset = $client->select( $query );

		if ( ! empty( $facets_array ) ) {
			$output = array();
			foreach ( $facets_array as $facet ) {
				$facet_results = $resultset->getFacetSet()->getFacet( $facet );
				foreach ( $facet_results as $value => $count ) {
					$output[ $facet ]['counts'][] = array( $value, $count );
				}
			}
			$search_result['facets'] = $output;
		} else {
			$search_result['facets'] = '';
		}

		$found = $resultset->getNumFound();

		if ( '' != $bound ) {
			$search_result['total'] = $bound;
		} else {
			$search_result['total'] = $found;
		}

		$hl = $query->getHighlighting();
		$hl->getField( 'title_display' )->setSimplePrefix( '<b>' )->setSimplePostfix( '</b>' );
		$hl->getField( 'abstract' )->setSimplePrefix( '<b>' )->setSimplePostfix( '</b>' );
		$resultSet = '';
		$resultSet = $client->select( $query );
		$results = array();
		$highlighting = $resultSet->getHighlighting();

		$i = 1;
		$cat_arr = array();

		foreach ( $resultSet as $document ) {
			$record = array();
			$record['id'] = $document->id;
			$record['pid'] = $document->pid;
			$record['title'] = $document->title_display;
			$record['title_unchanged'] = $document->title_unchanged;
			if ( '' == $document->title_unchanged ) {
				$record['title_unchanged'] = $document->title_display;
			}
			$record['abstract'] = $document->abstract;
			$record['abstract_unchanged'] = $document->abstract_unchanged;
			if ( '' == $document->abstract_unchanged ) {
				$record['abstract_unchanged'] = $document->abstract;
			}
			$record['date'] = $document->pub_date_facet[0];
			$record['authors'] = $document->author_facet;
			$record['author_info'] = $document->author_info;
			$record['organization'] = $document->organization_facet;
			$record['group'] = $document->group_facet;
			$record['society_id'] = $document->society_facet;
			$record['language'] = $document->language_facet;
			$record['type_of_license'] = $document->license_facet;
			$record['subject'] = $document->subject_facet;
			$record['keyword'] = $document->keyword_search;
			$record['keyword_display'] = $document->keyword_display;
			$record['handle'] = $document->handle;
			$record['genre'] = $document->genre_facet[0];
			if ( ! empty( (array)$document->notes ) ) {
				$record['notes'] = implode( ' ', (array)$document->notes );
			}
			if ( ! empty( (array)$document->notes_unchanged ) ) {
				$record['notes_unchanged'] = implode( ' ', (array)$document->notes_unchanged );
			}
			if ( empty( (array)$document->notes_unchanged ) && ! empty( (array)$document->notes ) ) {
				$record['notes_unchanged'] = implode( ' ', (array)$document->notes );
			}
			$record['book_journal_title'] = $document->book_journal_title;
			$record['book_author'] = $document->book_author[0];
			$record['publisher'] = $document->publisher;
			$record['isbn'] = $document->isbn;
			$record['issn'] = $document->issn;
			$record['doi'] = $document->doi;
			$record['volume'] = $document->volume;
			$record['date_issued'] = $document->date_issued;
			$record['issue'] = $document->issue;
			$record['chapter'] = $document->book_chapter;
			$record['start_page'] = $document->start_page;
			$record['end_page'] = $document->end_page;
			$record['institution'] = $document->institution;
			$record['conference_title'] = $document->conference_title;
			$record['conference_organization'] = $document->conference_organization;
			$record['conference_location'] = $document->conference_location;
			$record['conference_date'] = $document->conference_date;
			$record['meeting_title'] = $document->meeting_title;
			$record['meeting_organization'] = $document->meeting_organization;
			$record['meeting_location'] = $document->meeting_location;
			$record['meeting_date'] = $document->meeting_date;
			$record['publication_type'] = $document->publication_type;
			$record['language'] = $document->language; //TODO convert solr contents to language_facet
			$record['record_content_source'] = $document->record_content_source;
			$record['record_content_source'] = $document->record_content_source;
			$record['record_creation_date'] = $document->record_creation_date;
			$record['record_change_date'] = $document->record_change_date;
			$record['record_identifier'] = $document->record_identifier;
			$record['member_of'] = $document->member_of;
			$record['embargo_end_date'] = $document->free_to_read_start_date;
			array_push( $results, $record );
			$i = $i + 1;
		}

		if ( count( $results ) < 0 ) {
			$search_result['documents'] = '';
		} else {
			$search_result['documents'] = $results;
		}

		$first = $st + 1;
		$last = $st + $number_of_res;

		if ( $last > $found ) {
			$last = $found;
		} else {
			$last = $st + $number_of_res;
		}

		$search_result['info'] = "<span class='infor'>Showing $first to $last results out of $found</span>";
		return $search_result;

	}

	/**
	 * Call the autosuggester (not implemented).
	 */
	public function auto_complete_suggestions( $input ) {

		$res = array();
		$client = $this->client;
		$suggestqry = $client->createSuggester();
		$suggestqry->setHandler( 'suggest' );
		$suggestqry->setDictionary( 'suggest' );
		$suggestqry->setQuery( $input );
		$suggestqry->setCount( 5 );
		$suggestqry->setCollate( true );
		$suggestqry->setOnlyMorePopular( true );

		$resultset = $client->suggester( $suggestqry );

		foreach ( $resultset as $term => $termResult ) {

			// $msg.='<strong>' . $term . '</strong><br/>';
			foreach ( $termResult as $result ) {
				array_push( $res, $wd );
			}
		}

		$result = json_encode( $res );
		return $result;
	}

}
