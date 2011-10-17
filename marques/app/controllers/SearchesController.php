<?php
/**
 * MARQUes - Maps Answering Research Questions
 *
 * @copyright     Copyright 2011, Flinders University (http://www.flinders.edu.au)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace app\controllers;

use lithium\data\Connections;

use app\models\FilmWeeklyCinemas;
use app\models\FilmWeeklyMarkers;

/**
 * Search for Film Weekly Cinemas in the database
 */
class SearchesController extends \lithium\action\Controller {

	// list actions that can be undertaken without authentication
	public $publicActions = array('index', 'advanced');
	
	// enable content negotiation so AJAX data can be returned
	protected function _init() {
        $this->_render['negotiate'] = true;
        parent::_init();
    }

	/**
	 * list all of the records
	 */
    public function index() {
        
        if ($this->request->data) {
        	
        	// get the search terms from the post data
        	$search = $this->request->data['search'];
        	
        	// get a connection to the database
        	$db = Connections::get('default');
        	
        	$results = array();
        	
        	$results = array_merge($results, $this->getFilmWeekly($search, $db));
        	        	
     		return compact('results');
        }
    }
    
    /**
     * function to get the list of film weekly cinemas as search results
     */
    private function getFilmWeekly($search, $db) {
    
    	$markers = $this->getFilmWeeklyMarkers();
    
    	$results = array();
    
    	// build a query to search for film_weekly_cinema_id using data from the film_weekly_table
    	$sql = "SELECT DISTINCT film_weekly_cinemas_id 
    		    FROM film_weekly_searches 
    		    WHERE MATCH (fwc_street, fwc_suburb, fwc_cinema_name, fwc_exhibitor_name)
				AGAINST ({:search} IN NATURAL LANGUAGE MODE)";
    	
    	// execute the query
    	$batch = $db->read($sql, 
    		array(
    			'return' => 'array',
    			'search' => $search
    		)
    	);
    	
    	//populate an array of ids
    	$cinemas = array();
    	
    	foreach($batch as $item) {
    	
    		$cinemas[] = $item['film_weekly_cinemas_id'];
    	}
    	
    	// build a query to search for film_weekl_cinema_id using data from the film_weekly_archaeology_table
    	$sql = "SELECT DISTINCT film_weekly_archaeologies.film_weekly_cinemas_id
				FROM film_weekly_searches, film_weekly_archaeologies
				WHERE MATCH (fwa_cinema_name, fwa_exhibitor_name) AGAINST ({:search} IN NATURAL LANGUAGE MODE)
				AND film_weekly_searches.film_weekly_archaeologies_id = film_weekly_archaeologies.id";
    	
    	// execute the data
    	$batch = $db->read($sql, 
    		array(
    			'return' => 'array',
    			'search' => $search
    		)
    	);
    	
    	// add ids to the array
		foreach($batch as $item) {
    	
    		$cinemas[] = $item['film_weekly_cinemas_id'];
    	}
    	
    	// remove any duplicates
    	$cinemas = array_unique($cinemas, SORT_NUMERIC);
    	
    	return $this->getFilmWeeklyCinemas($cinemas, $markers);
    }
    
    /**
     * function to undertake advanced searches
     */
    public function advanced() {
    
    	if ($this->request->data) {
        	
        	// get the search terms from the post data
        	$search = $this->request->data['search'];
        	
        	// get a connection to the database
        	$db = Connections::get('default');
        	
        	$results = array();
        	
        	$results = array_merge($results, $this->getFilmWeeklyAdvanced($search, $db));
        	        	
     		return compact('results');
        }
    
    }
    
    /**
     * function to get the list of film weekly cinemas as search results
     */
    private function getFilmWeeklyAdvanced($search, $db) {
    
    	$markers = $this->getFilmWeeklyMarkers();
    
    	$results = array();
    
    	// build a query to search for film_weekly_cinema_id using data from the film_weekly_table
    	$sql = "SELECT DISTINCT film_weekly_cinemas_id 
    		    FROM film_weekly_searches 
    		    WHERE MATCH (fwc_street, fwc_suburb, fwc_cinema_name, fwc_exhibitor_name)
				AGAINST ({:search} IN BOOLEAN MODE)";
    	
    	// execute the query
    	$batch = $db->read($sql, 
    		array(
    			'return' => 'array',
    			'search' => $search
    		)
    	);
    	
    	//populate an array of ids
    	$cinemas = array();
    	
    	foreach($batch as $item) {
    	
    		$cinemas[] = $item['film_weekly_cinemas_id'];
    	}
    	
    	// build a query to search for film_weekl_cinema_id using data from the film_weekly_archaeology_table
    	$sql = "SELECT DISTINCT film_weekly_archaeologies.film_weekly_cinemas_id
				FROM film_weekly_searches, film_weekly_archaeologies
				WHERE MATCH (fwa_cinema_name, fwa_exhibitor_name) AGAINST ({:search} IN BOOLEAN MODE)
				AND film_weekly_searches.film_weekly_archaeologies_id = film_weekly_archaeologies.id";
    	
    	// execute the data
    	$batch = $db->read($sql, 
    		array(
    			'return' => 'array',
    			'search' => $search
    		)
    	);
    	
    	// add ids to the array
		foreach($batch as $item) {
    	
    		$cinemas[] = $item['film_weekly_cinemas_id'];
    	}
    	
    	// remove any duplicates
    	$cinemas = array_unique($cinemas, SORT_NUMERIC);
    	
    	return $this->getFilmWeeklyCinemas($cinemas, $markers);
    }
    
    /*
     * private function to get Film Weekly Cinema data
     */
    private function getFilmWeeklyCinemas($cinemas, $markers) {
    
    	// loop through getting all of the cinemas one at a time
    	// TODO: yes I know this is inefficient, need to revisit this if it becomes an issue
    	
    	$records = array();
    	
    	foreach($cinemas as $cinema) {
    	
    		$record = FilmWeeklyCinemas::find(
    			'first',
    			array(
    				'with' => 'AustralianStates',
    				'conditions' => array('FilmWeeklyCinemas.id' => $cinema)
    			)
    		);
    		
    		$records[] = $record;
    	
    	}
    	
    	// loop through the list of cinemas building up the search results
    	foreach($records as $record) {
    		
    		$results[] = array(
    			'id'     => $record->id,
    			'type'   => 'film_weekly_cinema',
     			'result' => $record->search_result_ajax(),
     			'coords' => $record->latitude . ',' . $record->longitude,
     			'title'  => $record->cinema_name,
     			'state'  => $record->australian_state->shortname,
     			'icon' => $markers[$record->film_weekly_cinema_types_id][$record->locality_types_id],
     			'cinema_type' => $record->film_weekly_cinema_types_id,
     			'locality_type' => $record->locality_types_id
    		);
    	}
    	
    	return $results;
    }
    
    /*
     * private function to get the list of markers
     */
    private function getFilmWeeklyMarkers() {
    
    	$markers = array();
    	
    	$records = FilmWeeklyMarkers::all();
    	
    	foreach($records as $record) {
    	
    		if(empty($markers[$record->film_weekly_cinema_types_id])) {
    		
    			$markers[$record->film_weekly_cinema_types_id] = array(
    				$record->locality_types_id => $record->marker_url
    			);
    		} else {
    			$markers[$record->film_weekly_cinema_types_id][$record->locality_types_id] = $record->marker_url;
    		}
    	}
    	
    	return $markers;
    
    }
}
?>