<?php
/**
 * Part of the Inforex project
 * Copyright (C) 2013 Michał Marcińczuk, Jan Kocoń, Marcin Ptak
 * Wrocław University of Technology
 * See LICENCE 
 */
 
class Page_administration_wsd_schema extends CPageAdministration {

	function execute(){
		$sens = DbSens::getSenseList();

		foreach($sens as $key => $value){
			$sens[$key]['annotation_name'] = substr($sens[$key]['annotation_name'], 4);
		}
		$this->set("sensList", $sens);
	}
}

?>