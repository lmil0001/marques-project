<?php
/**
 * MARQUes - Maps Answering Research Questions
 *
 * @copyright     Copyright 2011, Flinders University (http://www.flinders.edu.au)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace app\controllers;

use lithium\security\Auth;
use lithium\storage\Session;

use app\models\FilmWeeklyCategories;

/**
 * Manage the Film Weekly Categories in the database
 */
class FilmWeeklyCategoriesController extends \lithium\action\Controller {

	/**
	 * list all of the categories
	 */
    public function index() {
        
        // get the list of categories
        $categories = FilmWeeklyCategories::all(array('order' => array('id' => 'ASC')));
        return compact('categories');
    }
    
    /**
     * add a new category to the database
     */
    public function add() {
    
    	// create a new category with the posted data
    	$category = FilmWeeklyCategories::create($this->request->data);
    	
    	// check to see if data as send and the save was successful
    	if(($this->request->data) && $category->save()) {
    		// redirect back to the main category page
    		Flash::write('message', 'Success: New record created');
    		return $this->redirect('FilmWeeklyCategories::index');
    	}
    	
    	// show the default category create form
    	return compact('category');
    	
    }
    
    /**
     * edit an existing category
     */
    public function edit($id = null) {
    
    	$id = (int)$id;
    	$category = FilmWeeklyCategories::find($id);
    	
    	if(empty($category)) {
    		return $this->redirect('FilmWeeklyCategories::index');
    	}
    	
    	if($this->request->data){
    		if($category->save($this->request->data)) {
    			Flash::write('message', 'Success: Record updated');
    			return $this->redirect('FilmWeeklyCategories::index');
    		} else {
    			Flash::write('message', 'Error: An error occurred please try again.');
    			return $this->redirect('FilmWeeklyCategories::index');
    		}
    	}
    	
    	return compact('category');
    }
    
    /**
     * delete an existing category
     */
    public function delete($id = null) {
        
        $id = (int)$id;
        
        FilmWeeklyCategories::remove(array('id' => $id));
        Flash::write('message', 'Success: Record deleted');
        return $this->redirect('FilmWeeklyCategories::index');    
    }
}
?>