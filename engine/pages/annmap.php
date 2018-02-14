<?php
/**
 * Part of the Inforex project
 * Copyright (C) 2013 Michał Marcińczuk, Jan Kocoń, Marcin Ptak
 * Wrocław University of Technology
 * See LICENCE 
 */

class Page_annmap extends CPage{

	var $isSecure = true;
	var $roles = array("loggedin");
	
	function checkPermission(){
		return hasCorpusRole(CORPUS_ROLE_READ) 
			&& hasCorpusRole(CORPUS_ROLE_BROWSE_ANNOTATIONS);
	}

	function manageFilters(){
	    $filters = $_GET;

	    if(isset($filters['metadata'])){
            $_SESSION['annmap']['metadata'][$filters['metadata']] = $filters['value'];
        }

        if(isset($filters['status'])){
            $_SESSION['annmap']['status'] = $filters['status'];
        }
        if(isset($filters['subcorpus'])){
            $_SESSION['annmap']['subcorpus'] = $filters['subcorpus'];
        }

        if(isset($filters['flag'])){
            $_SESSION['annmap']['flags']['flag'] = $filters['flag'];
            $_SESSION['annmap']['flags']['flag_status'] = $filters['flag_status'];
        }

        ChromePhp::log($_SESSION);
    }
	
	function execute(){		
		global $corpus, $db;

		$this->manageFilters();

		$this->includeJs("js/c_autoresize.js");
		
		$corpus_id = $corpus['id'];
		$subcorpus = $_GET['subcorpus'];
		$status = intval($_GET['status']);
		$flag = $_GET['flag'];
		$flag_status = $_GET['flag_status'];
		$set_filters = array();
        $corpus_flags = DbCorporaFlag::getCorpusFlags($corpus_id);
        $flags = DbCorporaFlag::getFlags();
        $features = DbCorpus::getCorpusExtColumnsWithMetadata($corpus['ext']);
        //$formats = DbReport::getAllFormats();
				
		$ext_where = null;
		if ( count($set_filters) ){
			foreach ($set_filters as $k=>$v)
				$ext_where .= " AND re.$k='$v'";
		}

		$session_flag = $_SESSION['annmap']['flags']['flag'];
		$session_flag_status = $_SESSION['annmap']['flags']['flag_status'];

		if($session_flag != null && $session_flag_status != null && $session_flag != "-" && $session_flag_status != "-"){
            $this->set("flag_set", true);
        }
		
		$annmap = DbAnnotation::getAnnotationSetsWithCount($corpus_id, $_SESSION['annmap']);
		
		/* Fill template */		
		$this->set("filters", HelperDocumentFilter::getCorpusCustomFilters($corpus_id, $set_filters));													
		$this->set("sets", $annmap);
		$this->set("subcorpus", $subcorpus);
		$this->set("flags", $flags);
		$this->set("selected_flag", $flag);
		$this->set("flag_status", $flag_status);
		$this->set("corpus_flags", $corpus_flags);
		$this->set("subcorpora", DbCorpus::getCorpusSubcorpora($corpus_id));
		$this->set("status", $status);
		$this->set("statuses", $statuses = DbStatus::getAll());
		$this->set("features", $features);
		$this->set("selected_filters", $_SESSION['annmap']);
	}
}


?>
