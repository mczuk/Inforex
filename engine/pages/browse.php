<?php

class Page_browse extends CPage{

	var $isSecure = true;
	var $roles = array();
	var $filter_attributes = array("text", "base","year","month","type","annotation","status", "subcorpus", 
									"flag_Anaphora", "flag_Chunk_Rel", "flag_Chunks", "flag_Clean", "flag_Names", "flag_Names_Rel", 
									"flag_Nazwy", "flag_PN", "flag_Tokens", "flag_Transkrypcja", "flag_WSD"
									);
	
	function checkPermission(){
		global $corpus;
		return hasCorpusRole('read') && !hasCorpusRole('read_limited') || $corpus['public'];
	}
	
	function execute(){
		global $mdb2, $corpus, $db;
				
		if (!$corpus){
			$this->redirect("index.php?page=home");
		}
		$cid = $corpus['id'];
		// Wczytaj wszystkie flagi dla korpusu
		$flags_names = DbCorpus::getCorpusFlags($cid);
		
										
		// Przygotuj parametry filtrowania raportów
		// ******************************************************************************
		$reset = array_key_exists('reset', $_GET) ? intval($_GET['reset']) : false;
		$p = intval($_GET['p']);	
		$prevReport = intval($_GET['r']);	
		$status	= array_key_exists('status', $_GET) ? $_GET['status'] : ($reset ? "" : $_COOKIE["{$cid}_".'status']);
		$type 	= array_key_exists('type', $_GET) ? $_GET['type'] : ($reset ? "" : $_COOKIE["{$cid}_".'type']);
		$year 	= array_key_exists('year', $_GET) ? $_GET['year'] : ($reset ? "" : $_COOKIE["{$cid}_".'year']);
		$month 	= array_key_exists('month', $_GET) ? $_GET['month'] : ($reset ? "" : $_COOKIE["{$cid}_".'month']);
		$search	= array_key_exists('search', $_GET) ? $_GET['search'] : ($reset ? "" : $_COOKIE["{$cid}_".'search']);
		$search_field= array_key_exists('search_field', $_GET) ? $_GET['search_field'] : ($reset ? "" : explode("|", $_COOKIE["{$cid}_".'search_field']));
		$annotation	= array_key_exists('annotation', $_GET) ? $_GET['annotation'] : ($reset ? "" : $_COOKIE["{$cid}_".'annotation']);
		$subcorpus	= array_key_exists('subcorpus', $_GET) ? $_GET['subcorpus'] : ($reset ? "" : $_COOKIE["{$cid}_".'subcorpus']);
		$flag_array = array();
		foreach($flags_names as $key => $flag_name){
			$flag_name_str = str_replace(' ', '_', $flag_name['short']);
			$flag_name_str = 'flag_' . $flag_name_str;
			$flag_array[$key]['flag_name'] = $flag_name['short'];
			$flag_array[$key]['no_space_flag_name'] = $flag_name_str;
			$flag_array[$key]['value'] = array_key_exists($flag_name_str, $_GET) ? $_GET[$flag_name_str] : ($reset ? "" : $_COOKIE["{$cid}_".$flag_name_str]);			 
		}
		$filter_order = array_key_exists('filter_order', $_GET) ? $_GET['filter_order'] : ($reset ? "" : $_COOKIE["{$cid}_".'filter_order']);
		$base	= array_key_exists('base', $_GET) ? $_GET['base'] : ($reset ? "" : $_COOKIE["{$cid}_".'base']);
				
		$search = stripslashes($search);
				
		$statuses = array_filter(explode(",", $status), "intval");
		$types = array_filter(explode(",", $type), "intval");
		$years = array_filter(explode(",", $year), "intval");
		$months = array_filter(explode(",", $month), "intval");
		$subcorpuses = array_filter(explode(",", $subcorpus), "intval");
		foreach($flag_array as $key => $value){
			$flag_array[$key]['data'] = array_filter(explode(",", $flag_array[$key]['value']), "intval"); 
		}
		$search = strval($search);
		$annotations = ($annotation=="no_annotation") ? $annotation : array_diff(explode(",", $annotation), array(""));
		$search_field = is_array($search_field) ? $search_field : array('title');
		$filter_order = explode(",", $filter_order);		
		$filter_order = is_array($filter_order) ? $filter_order : array();

		if (count($statuses)==0){
			//$statuses = array(2);
		}
		
		if (defined(IS_RELEASE) && $cid==2){
			$years = array(2004);
			$statuses = array(2);
			$months = array();
		}

		// Zapisz parametry w sesjii
		// ******************************************************************************		
		setcookie("{$cid}_".'search', $search);
		setcookie("{$cid}_".'base', $base);
		setcookie("{$cid}_".'search_field', implode("|", $search_field));
		setcookie("{$cid}_".'type', implode(",",$types));
		setcookie("{$cid}_".'year', implode(",",$years));
		setcookie("{$cid}_".'month', implode(",",$months));
		setcookie("{$cid}_".'subcorpus', implode(",",$subcorpuses));
		foreach($flag_array as $key => $value){
			setcookie("{$cid}_".$flag_array[$key]['no_space_flag_name'], implode(",",$flag_array[$key]['data']));			
		}
		setcookie("{$cid}_".'status', implode(",",$statuses));
		setcookie("{$cid}_".'annotation', $annotations=="no_annotation" ? $annotations : implode(",",$annotations)); 
		setcookie("{$cid}_".'status', implode(",",$statuses));

		/*** 
		 * Parametry stronicowania
		 ******************************************************************************/		
		$limit = 100;
		$from = $limit * $p;
		
		/*** 
		 * Przygotuj warunki where dla zapytania SQL
		 ******************************************************************************/
		$		
		$where = array();
		$join = "";
		$select = "";
		$columns = array("lp"=>"Lp.", 
							"id"=>"Id", 
							"title"=>"Nazwa raportu", 
							"status_name"=>"Status"); // lista kolumna do wyświetlenia na stronie
		
		/// Fraza
		if (strval($search)){
			$where_fraza = array();
			if (in_array('title', $search_field))
				$where_fraza[] = "r.title LIKE '%$search%'";
			if (in_array('content', $search_field))
				$where_fraza[] = "r.content LIKE '%$search%'";
			if (count($where_fraza))
				$where['text'] = ' (' . implode(" OR ", $where_fraza) . ') ';
		}
		
		if ( $base ){
			$join = " JOIN tokens AS tokens ON (r.id=tokens.report_id) JOIN tokens_tags as tt USING(token_id) ";
			$where['base'] = " ( tt.base = '". mysql_real_escape_string($base) ."' COLLATE utf8_bin AND tt.disamb = 1) "; 
			$group['report_id'] = "r.id";
		}

		if (count($years)>0)	$where['year'] = where_or("YEAR(r.date)", $years);			
		if (count($months)>0)	$where['month'] = where_or("MONTH(r.date)", $months);
		if (count($types)>0)	$where['type'] = where_or("r.type", $types);
		if (count($statuses)>0)	$where['status'] = where_or("r.status", $statuses);
		if (count($subcorpuses)>0)	$where['subcorpus'] = where_or("r.subcorpus_id", $subcorpuses);
				
		/// Anotacje
		if ($annotations == "no_annotation"){
			$where['annotation'] = "a.id IS NULL";
			$join .= " LEFT JOIN reports_annotations a ON (r.id = a.report_id)";
		}elseif (is_array($annotations) && count($annotations)>0){
			$where['annotation'] = where_or("an.type", $annotations);			
			$join .= " INNER JOIN reports_annotations an ON ( an.report_id = r.id )";
			$group['report_id'] = "r.id";
		}
		
		/// Flagi
		$flags_count = array(); // Ilość aktywnych flag 
		$flag_not_ready = array(); // Dla przypadku filtrowania po fladze nie gotowy
		foreach($flag_array as $key => $value){
			if (count($flag_array[$key]['data'])){
				$flags_count[] = $key;
				if (in_array('-1', $flag_array[$key]['data'])){
					$flag_not_ready[] = $flag_array[$key];
				}							
			}	
								
		}
		$where_flags = array();
		if(count($flags_count)){ 
			$sql = "SELECT f.flag_id as id FROM flags f WHERE f.flag_id>0 ";  	
			$rows_flags = $db->fetch_rows($sql);
			foreach($rows_flags as $key => $row_flag){
				$rows_flags[$key] = $row_flag['id'];
			}				
			foreach($flags_count as $value){
				$where_data = array();
				if(in_array('-1', $flag_array[$value]['data'])){
					if(count($flag_array[$value]['data']) > 1){
						foreach($flag_array[$value]['data'] as $data)
							if($data != '-1')
								$where_data[] = $data;
						$where_flags[$flag_array[$value]['no_space_flag_name']] = ' AND ' . where_or("f.flag_id", $where_data) . ' AND cf.short=\''. $flag_array[$value]['flag_name'] .'\' ';
					}
					else{
						$where_flags[$flag_array[$value]['no_space_flag_name']] = ' AND ' . where_or("f.flag_id", array('-1')) . ' AND cf.short=\''. $flag_array[$value]['flag_name'] .'\' ';
					}
				}	 
				else{
					$where_flags[$flag_array[$value]['no_space_flag_name']] = ' AND ' . where_or("f.flag_id", $flag_array[$value]['data']) . ' AND cf.short=\''. $flag_array[$value]['flag_name'] .'\' ';
				}
			}
			$group['report_id'] = "r.id";
			$join .= " LEFT JOIN reports_flags rf ON rf.report_id=r.id ".
					" LEFT JOIN corpora_flags cf ON cf.corpora_flag_id=rf.corpora_flag_id ".
					" LEFT JOIN flags f ON f.flag_id=rf.flag_id ";
		}
		
		/// Kolejność
		$order = "r.id ASC";

		$columns["tokenization"] = "Tokenization";
		
		/// Wczytaj dodatkowe kolumny zależne od korpusu
		if ( $cid == 3 ){
			$join .= " LEFT JOIN reports_ext_3 ext ON (r.id = ext.id)";
			$select .= "ext.*, YEAR(r.date) as year, ";
			$columns["deceased_age"] = "Wiek";
			$columns["deceased_gender"] = "Płeć";
			$columns["deceased_maritial"] = "Status cywilny";
			$columns["source"] = "Sposób zapisu";
			$columns["year"] = "Rok";
			$columns["suicide_place"] = "Miejsce samobójstwa";
			
			$order = "r.title ASC";
		}else{
			$columns["bootstrapping"] = "PN to verify";
			
			$select .= " (SELECT COUNT(*) FROM reports_annotations WHERE report_id = r.id AND stage='new' AND source='bootstrapping') AS bootstrapping, ";
		}
		
		/* Format SQL statement elements */
		$group_sql = (count($group) == 0 ? "" : " GROUP BY " . implode(", ", array_values($group)) );
		$where_sql = ((count($where)>0) ? "AND " . implode(" AND ", array_values($where) ) : "");
		
		setcookie("{$cid}_".'sql_where', $where_sql);
		setcookie("{$cid}_".'sql_join', $join);
		setcookie("{$cid}_".'sql_group', $group_sql);
		setcookie("{$cid}_".'sql_order', $order);
		
		if ($prevReport){
			$sql = 	"SELECT count(r.id) as cnt" .
					" FROM reports r" .
					" LEFT JOIN reports_types rt ON ( r.type = rt.id )" .
					" LEFT JOIN reports_statuses rs ON ( r.status = rs.id )" .
					" LEFT JOIN users u USING (user_id)" .
					$join .
					" WHERE r.corpora = {$corpus['id']} " .
					" AND r.id<$prevReport ".
					$where_sql .
					$group_sql .
					" ORDER BY $order";	
			$prevCount = intval(db_fetch_one($sql));
			
			$p = (int)($prevCount/$limit);
			$from = $limit * $p;
		}
		
		$sql = 	"SELECT " .
				"	$select " .
				"	r.title, " .
				"	r.status, " .
				"	r.id, " .
				"	r.number, " .
				"	r.tokenization," .
				" 	rt.name AS type_name, " .
				"	rs.status AS status_name, " .
				"	u.screename" .
				" FROM reports r" .
				" LEFT JOIN reports_types rt ON ( r.type = rt.id )" .
				" LEFT JOIN reports_statuses rs ON ( r.status = rs.id )" .
				" LEFT JOIN users u USING (user_id)" .
				$join .
				" WHERE r.corpora = {$corpus['id']} ".
				$where_sql .
				$group_sql .
				" ORDER BY $order" .
				(count($flags_count) ? "" : " LIMIT {$from},{$limit}" );

		fb($sql);
		if (PEAR::isError($r = $mdb2->query($sql)))
			die("<pre>{$r->getUserInfo()}</pre>");
		$rows = $r->fetchAll(MDB2_FETCHMODE_ASSOC);

		$reportIds = array();
		foreach ($rows as $row){
			array_push($reportIds, $row['id']);
		}

		// Jeżeli są zaznaczone flagi to obcina listę wynikow
		$reports_ids_flag_not_ready = array();
		if(count($flags_count)){  
			$sql = "SELECT r.id AS id, cf.short as name ".
					"FROM reports r " .
  					"LEFT JOIN reports_flags rf ON rf.report_id=r.id " .
  					"LEFT JOIN corpora_flags cf ON cf.corpora_flag_id=rf.corpora_flag_id " .
    				"WHERE r.id IN  ('". implode("','",$reportIds) ."') ";// .
			$rows_flags_not_ready = $db->fetch_rows($sql);
  			$flags_not_ready_map = array();
			foreach ($rows_flags_not_ready as $row_flags_not_ready){
				$flags_not_ready_map[$row_flags_not_ready['name']][] = $row_flags_not_ready['id'];
			}
			
			foreach($flag_not_ready as $flag_not){
				$reports_ids_flag_not_ready[$flag_not['flag_name']] = array();
				foreach($reportIds as $repId){
					if(!in_array($repId,$flags_not_ready_map[$flag_not['flag_name']]))
						if(!in_array($repId,$reports_ids_flag_not_ready[$flag_not['flag_name']]))
							$reports_ids_flag_not_ready[$flag_not['flag_name']][] = $repId;
				}
			}
			foreach($flags_count as $flags_where){
				if(isset($reports_ids_flag_not_ready[$flag_array[$flags_where]['flag_name']])){
					foreach($reports_ids_flag_not_ready[$flag_array[$flags_where]['flag_name']] as $key => $flag_not_ready_rep){
						if(!in_array($flag_not_ready_rep,$reportIds))
							unset($reports_ids_flag_not_ready[$flag_array[$flags_where]['flag_name']][$key]);
					}
				}
				$sql = "SELECT r.id AS id  ".
	  					"FROM reports r " .
  						"LEFT JOIN reports_flags rf ON rf.report_id=r.id " .
  						"LEFT JOIN corpora_flags cf ON cf.corpora_flag_id=rf.corpora_flag_id " .
  						"LEFT JOIN flags f ON f.flag_id=rf.flag_id " .
	  					"WHERE r.id IN  ('". implode("','",$reportIds) ."') " .
	  					$where_flags[$flag_array[$flags_where]['no_space_flag_name']] .
  						" GROUP BY r.id " .
  						" ORDER BY r.id ASC " ;
				$rows_flags = $db->fetch_rows($sql);
				$reportIds = array();
				foreach ($rows_flags as $row){
					array_push($reportIds, $row['id']);				
				}
				if(isset($reports_ids_flag_not_ready[$flag_array[$flags_where]['flag_name']])){
					foreach($reports_ids_flag_not_ready[$flag_array[$flags_where]['flag_name']] as $flag_not_ready_rep){
						if(!in_array($flag_not_ready_rep,$reportIds))
							array_push($reportIds, $flag_not_ready_rep);
					}
				}
			}
			
			foreach ($rows as $key => $row){
				if(!in_array($row['id'], $reportIds)){
					unset($rows[$key]);
				}
			}
			$i = 0;
			$num = 0;
			foreach ($rows as $key => $row){
				unset($rows[$key]);
				if($i >= $from && $i < $from+$limit){
					$rows[$num] = $row;
					$num++;
				}
				$i++;
			}	
		}
		
		$sql = "SELECT * FROM corpora_flags WHERE corpora_id={$corpus['id']} ORDER BY sort";
		$corporaFlags = db_fetch_rows($sql);
		foreach ($corporaFlags as $corporaFlag){
			$columns["flag".$corporaFlag['corpora_flag_id']]=$corporaFlag;
		}
		
		$sql = "SELECT reports_flags.report_id, reports_flags.corpora_flag_id, reports_flags.flag_id, flags.name " .
				"FROM reports_flags " .
				"LEFT JOIN flags ON " .
					(count($reportIds)>0 ? 
				"report_id " .
					"IN ('".implode("','",$reportIds)."') " .
					"AND " : "") . "reports_flags.flag_id=flags.flag_id ";				
		$reportFlags = db_fetch_rows($sql);
		
		$reportFlagsMap = array();
		foreach ($reportFlags as $reportFlag){
			if ($reportFlagsMap[$reportFlag['report_id']]){
				$reportFlagsMap[$reportFlag['report_id']][$reportFlag['corpora_flag_id']]['name']=$reportFlag['short'];
				$reportFlagsMap[$reportFlag['report_id']][$reportFlag['corpora_flag_id']]['flag_id']=$reportFlag['flag_id'];
			}
			else {
				$reportFlagsMap[$reportFlag['report_id']]=array();
				$reportFlagsMap[$reportFlag['report_id']][$reportFlag['corpora_flag_id']]['name']=$reportFlag['short'];
				$reportFlagsMap[$reportFlag['report_id']][$reportFlag['corpora_flag_id']]['flag_id']=$reportFlag['flag_id'];
			}
		}		
		$count_rows = count($rows);
		for ($i=0; $i<$count_rows; $i++){
			foreach ($corporaFlags as $corporaFlag){
				$rows[$i]["flag".$corporaFlag['corpora_flag_id']]['name']="NIE GOTOWY";			
				$rows[$i]["flag".$corporaFlag['corpora_flag_id']]['flag_id']=-1;			
				if ($reportFlagsMap[$rows[$i]['id']] && $reportFlagsMap[$rows[$i]['id']][$corporaFlag['corpora_flag_id']]){
					$rows[$i]["flag".$corporaFlag['corpora_flag_id']]['name']=$reportFlagsMap[$rows[$i]['id']][$corporaFlag['corpora_flag_id']]['name'];								
					$rows[$i]["flag".$corporaFlag['corpora_flag_id']]['flag_id']=$reportFlagsMap[$rows[$i]['id']][$corporaFlag['corpora_flag_id']]['flag_id'];								
				};
			}
		}
		array_walk($rows, "array_walk_highlight", $search);
		// wszystkie wyniki
		if(count($flags_count)){  
			$sql = "SELECT r.id AS id FROM reports r $join WHERE r.corpora={$corpus['id']} $where_sql";
			$rows_count = $db->fetch_rows($sql);
			$reportIds = array();
			foreach ($rows_count as $row){
				array_push($reportIds, $row['id']);				
			}
			foreach($flags_count as $flags_where){
				$sql = "SELECT r.id AS id  ".
	  					"FROM reports r " .
  						"LEFT JOIN reports_flags rf ON rf.report_id=r.id " .
  						"LEFT JOIN corpora_flags cf ON cf.corpora_flag_id=rf.corpora_flag_id " .
  						"LEFT JOIN flags f ON f.flag_id=rf.flag_id " .
	  					"WHERE r.id IN  ('". implode("','",$reportIds) ."') " .
	  					$where_flags[$flag_array[$flags_where]['no_space_flag_name']] .
  						" GROUP BY r.id " .
  						" ORDER BY r.id ASC ";
  						  							
				$rows_count = $db->fetch_rows($sql);
				$reportIds = array();
				foreach ($rows_count as $row){
					array_push($reportIds, $row['id']);				
				}
				if(isset($reports_ids_flag_not_ready[$flag_array[$flags_where]['flag_name']])){
					foreach($reports_ids_flag_not_ready[$flag_array[$flags_where]['flag_name']] as $flag_not_ready_rep){
						if(!in_array($flag_not_ready_rep,$reportIds))
							array_push($reportIds, $flag_not_ready_rep);
					}
				}
			}
			$rows_all = count($reportIds);
		}
		else{
			$sql = "SELECT COUNT(DISTINCT r.id) FROM reports r $join WHERE r.corpora={$corpus['id']} $where_sql";
			if (PEAR::isError($r = $mdb2->query($sql))) 
				die("<pre>{$r->getUserInfo()}</pre>");
			$rows_all = $r->fetchOne();
		}

		// Usuń atrybuty z listy kolejności, dla których nie podano warunku.
		$where_keys = count($where) >0 ? array_keys($where) : array();
		if(count($flags_count)) // Jeżeli są zaznaczone flagi (więcej niż jedna) 
			foreach($flags_count as $flags_where)
				$where_keys[] = $flag_array[$flags_where]['no_space_flag_name'];
		$filter_order = array_intersect($filter_order, $where_keys);
		// Dodaj brakujące atrybuty do listy kolejności
		$filter_order = array_merge($filter_order, array_diff($where_keys, $filter_order) );
		
		
		$this->set('columns', $columns);
		$this->set('page_map', create_pagging($rows_all, $limit, $p));
		$this->set('status', $status);
		$this->set('rows', $rows);
		$this->set('p', $p);
		$this->set('base', $base);
		$this->set('total_count', number_format($rows_all, 0, ".", " "));
		$this->set('year', $year);
		$this->set('month', $month);
		$this->set('from', $from+1);
		$this->set('search', $search);
		$this->set('search_field_title', in_array('title', $search_field));
		$this->set('search_field_content', in_array('content', $search_field));
		$this->set('type', $type);
		$this->set('type_set', $type!="");
		$this->set('annotation_set', $annotations == "no_annotation");

		$corpus_flags_names = array();
		foreach($flag_array as $key => $value){
			$this->set($flag_array[$key]['no_space_flag_name'], $flag_array[$key]['data']);
			array_push($corpus_flags_names, $flag_array[$key]['no_space_flag_name']);  								
		}
		$this->set('flags_names', $corpus_flags_names);				
		$this->set('filter_order', $filter_order);
		$this->set('filter_notset', array_diff($this->filter_attributes, $filter_order));
		$this->set_filter_menu($search, $statuses, $types, $years, $months, $annotations, $filter_order, $subcorpuses, $flag_array, $rows_all);		
	}
	
	/**
	 * Ustawia parametry filtrów wg. atrybutów raportów.
	 */
	function set_filter_menu($search, $statuses, $types, $years, $months, $annotations, $filter_order, $subcorpuses, $flag_array, $rows_all){
		global $mdb2, $corpus, $db;
		
		$sql_where_parts = array();
		$sql_where_flag_name_parts = array(); 
		$sql_where_parts['text'] = "r.title LIKE '%$search%'";
		$sql_where_parts['type'] = where_or("r.type", $types);
		$sql_where_parts['year'] = where_or("YEAR(r.date)", $years);
		$sql_where_parts['month'] = where_or("MONTH(r.date)", $months);
		$sql_where_parts['status'] = where_or("r.status", $statuses);
		$sql_where_parts['annotation'] = where_or("an.type", $annotations);
		$sql_where_parts['subcorpus'] = where_or("r.subcorpus_id", $subcorpuses);

		$sql_where_filtered_general = implode(" AND ", array_intersect_key($sql_where_parts, array_fill_keys($filter_order, 1)));
		$sql_where_filtered_general = $sql_where_filtered_general ? " AND ".$sql_where_filtered_general : "";
		$sql_where_filtered = array();
		$filter_order_stack = array();
		foreach ($filter_order as $f){
			if ( isset($sql_where_parts[$f]) ){
				if (count($filter_order_stack)==0)
					$sql_where_filtered[$f] = "";
				else
					$sql_where_filtered[$f] = " AND ".implode(" AND ", array_intersect_key($sql_where_parts, array_fill_keys($filter_order_stack, 1)));
				$filter_order_stack[] = $f;
			}
		}
		
		fb($sql_where_parts);
		fb(array_fill_keys($filter_order_stack, 1));
		
		$sql_select = array();
		$sql_join = array();
		$sql_where = array();
		$sql_group_by = array();
		
		$sql_select['year'] = " YEAR(r.date) as id, YEAR(r.date) as name, COUNT(DISTINCT r.id) as count ";
		$sql_join['year'] = "";
		$sql_where['year'] = ( isset($sql_where_filtered['year']) ? $sql_where_filtered['year'] : $sql_where_filtered_general);
		$sql_group_by['year'] = " GROUP BY id ORDER BY id DESC ";
		$sql_select['subcorpus'] = " r.subcorpus_id as id, cs.name, COUNT(DISTINCT r.id) as count ";
		$sql_join['subcorpus'] = " LEFT JOIN corpus_subcorpora cs ON (r.subcorpus_id=cs.subcorpus_id) ";
		$sql_where['subcorpus'] = ( isset($sql_where_filtered['subcorpus']) ? $sql_where_filtered['subcorpus'] : $sql_where_filtered_general);
		$sql_group_by['subcorpus'] = " GROUP BY cs.name ORDER BY cs.name ASC ";			
		$sql_select['status'] = " s.id, s.status as name, COUNT(DISTINCT r.id) as count ";
		$sql_join['status'] = " LEFT JOIN reports_statuses s ON (s.id=r.status) ";
		$sql_where['status'] = ( isset($sql_where_filtered['status']) ? $sql_where_filtered['status'] : $sql_where_filtered_general);
		$sql_group_by['status'] = " GROUP BY r.status ORDER BY `s`.`order` ";
		$sql_select['type'] = " t.id, t.name, COUNT(DISTINCT r.id) as count ";
		$sql_join['type'] = " LEFT JOIN reports_types t ON (t.id=r.type) ";
		$sql_where['type'] = ( isset($sql_where_filtered['type']) ? $sql_where_filtered['type'] : $sql_where_filtered_general);
		$sql_group_by['type'] = " GROUP BY t.name ORDER BY t.name ASC ";
		$sql_select['annotation'] = " an.type as id, an.type as name, COUNT(DISTINCT r.id) as count ";
		$sql_join['annotation'] = "";
		$sql_where['annotation'] = ( isset($sql_where_filtered['annotation']) ? $sql_where_filtered['annotation'] : $sql_where_filtered_general);
		$sql_group_by['annotation'] = " GROUP BY name ORDER BY name ASC ";
		$sql_flag_select_parts = ' f.flag_id AS id, f.name AS name, COUNT(DISTINCT r.id) as count ';
		$sql_flag_group_by_parts = ' GROUP BY f.flag_id ORDER BY f.flag_id ASC ';
		
		$flag_count = 0;
		foreach($flag_array as $key => $value){
			if($flag_array[$key]['data'])
				$flag_count++;
		}
		$not_ready_flags = array();
		foreach($flag_array as $key => $value){
			$sql_where_flag_name_parts[$flag_array[$key]['no_space_flag_name']] = ' (cf.short=\'' . $flag_array[$key]['flag_name'] . '\') ';
			if($flag_array[$key]['data']){				
								
				if(in_array('-1', $flag_array[$key]['data'])){
					$not_ready_flags[] = $flag_array[$key]['no_space_flag_name'];
					if(count($flag_array[$key]['data']) > 1){
						$where_data = array();
						foreach($flag_array[$key]['data'] as $data)
							if($data != '-1')
								$where_data[] = $data;
						$sql_where_parts[$flag_array[$key]['no_space_flag_name']] = where_or("f.flag_id", $where_data);
					}
					else{
						$sql_where_parts[$flag_array[$key]['no_space_flag_name']] = where_or("f.flag_id", array('-1')); 
					}
				} 
				else
					$sql_where_parts[$flag_array[$key]['no_space_flag_name']] = where_or("f.flag_id", $flag_array[$key]['data']);
			}			
		}
		
		if($flag_count){ // w przypadku flag  
			$report_ids = array();
			$all_corpus_reports_ids = array(); 
			$rows = DbCorpus::getCorpusReports($corpus['id']);
			foreach($rows as $key => $value){
				$report_ids[] = $value['id'];
				$all_corpus_reports_ids[] = $value['id']; 				
			}
			
			$sql = "SELECT r.id AS id, cf.short as name ".
					"FROM reports r " .
  					"LEFT JOIN reports_flags rf ON rf.report_id=r.id " .
  					"LEFT JOIN corpora_flags cf ON cf.corpora_flag_id=rf.corpora_flag_id " .
    				"WHERE r.id IN  ('". implode("','",$report_ids) ."') ";// .
			$rows_flags_not_ready = $db->fetch_rows($sql);
  			$flags_not_ready_map = array();
			foreach ($rows_flags_not_ready as $row_flags_not_ready){
				$flags_not_ready_map[$row_flags_not_ready['name']][] = $row_flags_not_ready['id'];
			}
			
			foreach($filter_order as $level => $order){
					
				if(preg_match("/^flag_/",$order)){ // jeżeli filtrem jest flaga
					foreach($flag_array as $key => $value){
						if($flag_array[$key]['no_space_flag_name'] == $order){
							$rows = DbBrowse::getCorpusSelectedFilterData($report_ids,
								$sql_where_flag_name_parts[$flag_array[$key]['no_space_flag_name']]);
							
							$documents_sum = 0;
							foreach($rows as $row)
								$documents_sum += $row['count'];
							if($documents_sum < count($report_ids))
								array_unshift($rows, array("id" => "-1", "name" => "nie gotowy", "count" => count($report_ids)-$documents_sum));
					
							prepare_selection_and_links($rows, 'id', $flag_array[$key]['data'], $filter_order, $flag_array[$key]['no_space_flag_name']);
							$flag_array[$key]['data'] = $rows;
							$this->set($flag_array[$key]['no_space_flag_name'], $flag_array[$key]);
							$rows = DbBrowse::getCorpusReportsIdsForFlags($report_ids,
								$sql_where_parts[$flag_array[$key]['no_space_flag_name']],
								$sql_where_flag_name_parts[$flag_array[$key]['no_space_flag_name']]);
							$report_ids = array();
							foreach($rows as $value){
								$report_ids[] = $value['id'];				
							}
							if(in_array($flag_array[$key]['no_space_flag_name'],$not_ready_flags)){
								foreach($all_corpus_reports_ids as $rep_id){
								 	if(isset($flags_not_ready_map[$flag_array[$key]['flag_name']]) && !in_array($rep_id,$flags_not_ready_map[$flag_array[$key]['flag_name']])){
								 		if(!in_array($rep_id,$report_ids))
							 			array_push($report_ids, $rep_id);
									}
								}
							}
							$all_corpus_reports_ids = array();
							$all_corpus_reports_ids = $report_ids;					
						}
					}
				}
				else{ // jeżeli filtrem nie jest flaga
					$sql_where_indeks = '';
					if($order == 'year'){
						$rows = DbBrowse::getReportsFilterData($report_ids,$sql_select['year'],$sql_join['year'],$sql_where['year'],$sql_group_by['year']);
						$sql_where_indeks = $sql_where_parts['year'];
						prepare_selection_and_links($rows, 'id', $years, $filter_order, "year");
						$this->set("years", $rows);
					}
					if($order == 'subcorpus'){		
						$rows = DbBrowse::getReportsFilterData($report_ids,$sql_select['subcorpus'],$sql_join['subcorpus'],$sql_where['subcorpus'],$sql_group_by['subcorpus']);
						$sql_where_indeks = $sql_where_parts['subcorpus'];
						prepare_selection_and_links($rows, 'id', $subcorpuses, $filter_order, "subcorpus");
						$this->set("subcorpuses", $rows);
					}			
					if($order == 'status'){
						$rows = DbBrowse::getReportsFilterData($report_ids,$sql_select['status'],$sql_join['status'],$sql_where['status'],$sql_group_by['status']);
						$sql_where_indeks = $sql_where_parts['status'];
						prepare_selection_and_links($rows, 'id', $statuses, $filter_order, "status");
						$this->set("statuses", $rows);
					}			
					if($order == 'type'){
						$rows = DbBrowse::getReportsFilterData($report_ids,$sql_select['type'],$sql_join['type'],$sql_where['type'],$sql_group_by['type']);
						$sql_where_indeks = $sql_where_parts['type'];
						array_walk($rows, "array_map_replace_spaces");
						prepare_selection_and_links($rows, 'id', $types, $filter_order, "type");
						$this->set("types", $rows);	
					}			
					if($order == 'annotation'){
						$rows = DbBrowse::getReportsFilterData($report_ids,$sql_select['annotation'],$sql_join['annotation'],$sql_where['annotation'],$sql_group_by['annotation']);
						$sql_where_indeks = $sql_where_parts['annotation'];
						array_walk($rows, "array_map_replace_spaces");
						prepare_selection_and_links($rows, 'id', $annotations, $filter_order, "annotation");
						$this->set("annotations", $rows);	
					}		
					
					$rows = DbBrowse::getCorpusReportsIdsForFlags($report_ids,
							$sql_where_indeks);
					$report_ids = array();
					foreach($rows as $key => $value){
						$report_ids[] = $value['id'];				
					}			
					$all_corpus_reports_ids = array();
					$all_corpus_reports_ids = $report_ids;
				}			
			}
			// ustarwianie filtrów nie wybranych przez użytkownika
			//******************************************************************
			// Years
			if(!in_array('year',$filter_order)){
				$rows = DbBrowse::getReportsFilterData($report_ids,$sql_select['year'],$sql_join['year'],$sql_where['year'],$sql_group_by['year']);
				prepare_selection_and_links($rows, 'id', $years, $filter_order, "year");
				$this->set("years", $rows);
			}			
			//******************************************************************
			// Subcorpuses
			if(!in_array('subcorpus',$filter_order)){		
				$rows = DbBrowse::getReportsFilterData($report_ids,$sql_select['subcorpus'],$sql_join['subcorpus'],$sql_where['subcorpus'],$sql_group_by['subcorpus']);
				prepare_selection_and_links($rows, 'id', $subcorpuses, $filter_order, "subcorpus");
				$this->set("subcorpuses", $rows);
			}			
			//******************************************************************
			//// Statuses
			if(!in_array('status',$filter_order)){
				$rows = DbBrowse::getReportsFilterData($report_ids,$sql_select['status'],$sql_join['status'],$sql_where['status'],$sql_group_by['status']);
				prepare_selection_and_links($rows, 'id', $statuses, $filter_order, "status");
				$this->set("statuses", $rows);
			}			
			//******************************************************************
			//// Types
			if(!in_array('type',$filter_order)){
				$rows = DbBrowse::getReportsFilterData($report_ids,$sql_select['type'],$sql_join['type'],$sql_where['type'],$sql_group_by['type']);
				array_walk($rows, "array_map_replace_spaces");
				prepare_selection_and_links($rows, 'id', $types, $filter_order, "type");
				$this->set("types", $rows);	
			}			
			//******************************************************************
			//// Annotations	
			if(!in_array('annotation',$filter_order)){
				$rows = DbBrowse::getReportsFilterData($report_ids,$sql_select['annotation'],$sql_join['annotation'],$sql_where['annotation'],$sql_group_by['annotation']);
				array_walk($rows, "array_map_replace_spaces");
				prepare_selection_and_links($rows, 'id', $annotations, $filter_order, "annotation");
				$this->set("annotations", $rows);	
			}
			//******************************************************************
			//// Flags
			foreach($flag_array as $key => $value){
				if(!in_array($flag_array[$key]['no_space_flag_name'],$filter_order)){
					$rows = DbBrowse::getCorpusSelectedFilterData($report_ids,
								$sql_where_flag_name_parts[$flag_array[$key]['no_space_flag_name']]);
								
					if(count($rows) < $rows_all){
						$documents_sum = 0;
						foreach($rows as $row)
							$documents_sum += $row['count'];
						if($documents_sum < count($report_ids))
							array_unshift($rows, array("id" => "-1", "name" => "nie gotowy", "count" => $rows_all-$documents_sum));
					}
				
					prepare_selection_and_links($rows, 'id', $flag_array[$key]['data'], $filter_order, $flag_array[$key]['no_space_flag_name']);
					$flag_array[$key]['data'] = $rows;
					$this->set($flag_array[$key]['no_space_flag_name'], $flag_array[$key]);
				}
			}
			
		}else{ // gdy nie wybrane flagi
			//******************************************************************
			// Years
			$rows = DbBrowse::getCorpusFilterData($corpus['id'],$sql_select['year'],$sql_join['year'],$sql_where['year'],$sql_group_by['year']);
			prepare_selection_and_links($rows, 'id', $years, $filter_order, "year");
			$this->set("years", $rows);

			//******************************************************************
			// Subcorpuses		
			$rows = DbBrowse::getCorpusFilterData($corpus['id'],$sql_select['subcorpus'],$sql_join['subcorpus'],$sql_where['subcorpus'],$sql_group_by['subcorpus']);
			prepare_selection_and_links($rows, 'id', $subcorpuses, $filter_order, "subcorpus");
			$this->set("subcorpuses", $rows);
			
			//******************************************************************
			//// Statuses
			$rows = DbBrowse::getCorpusFilterData($corpus['id'],$sql_select['status'],$sql_join['status'],$sql_where['status'],$sql_group_by['status']);
			prepare_selection_and_links($rows, 'id', $statuses, $filter_order, "status");
			$this->set("statuses", $rows);
			
			//******************************************************************
			//// Types
			$rows = DbBrowse::getCorpusFilterData($corpus['id'],$sql_select['type'],$sql_join['type'],$sql_where['type'],$sql_group_by['type']);
			array_walk($rows, "array_map_replace_spaces");
			prepare_selection_and_links($rows, 'id', $types, $filter_order, "type");
			$this->set("types", $rows);		
			
			//******************************************************************
			//// Annotations	
			$rows = DbBrowse::getCorpusFilterData($corpus['id'],$sql_select['annotation'],$sql_join['annotation'],$sql_where['annotation'],$sql_group_by['annotation']);
			array_walk($rows, "array_map_replace_spaces");
			prepare_selection_and_links($rows, 'id', $annotations, $filter_order, "annotation");
			$this->set("annotations", $rows);
			
			//******************************************************************
			//// Flags			
			foreach($flag_array as $key => $value){
				$rows = DbBrowse::getCorpusFilterData($corpus['id'],
						$sql_flag_select_parts,
						'',
						$sql_where_filtered_general,
						$sql_flag_group_by_parts,
						$flag_array[$key]['flag_name']);
				if(count($rows) < $rows_all){
					$documents_sum = 0;
					foreach($rows as $row)
						$documents_sum += $row['count'];
					if($documents_sum < $rows_all)
						array_unshift($rows,array("id" => "-1", "name" => "nie gotowy", "count" => $rows_all-$documents_sum));
				}
					
				prepare_selection_and_links($rows, 'id', $flag_array[$key]['data'], $filter_order, $flag_array[$key]['no_space_flag_name']);
				$flag_array[$key]['data'] = $rows;
				$this->set($flag_array[$key]['no_space_flag_name'], $flag_array[$key]);
			}	
		}		

		//******************************************************************
		//// Treść
		$content = array();
		$content[] = array("name" => "bez treści", "link" => "no_content");
		$this->set("content", $content);		
	}
}

/**
 * Przygotuj dla każdej pozycji odpowiedni link i kolejność sortowania. 
 */
function prepare_selection_and_links(&$rows, $column, $values, $filter_order, $attribute_name=""){
	$filter_order = is_array($filter_order) ? $filter_order : array();
	// Policz, ile atrybutów jest aktywnych
	$selected_all = true;
	$selected_any = false;
	$selected_count = 0;
	foreach ($rows as $id=>$row){
		$rows[$id]['selected'] = in_array($row[$column], $values) || count($values)==0;
		
		$selected_all = $rows[$id]['selected'] && $selected_all;
		$selected_any = $rows[$id]['selected'] || $selected_any;
		$selected_count += $rows[$id]['selected'] ? 1 : 0;
	}
	//$rows[$id]['selected_count'] = $selected_count; 

	foreach ($rows as $id=>$row){
		if ($rows[$id]['selected']){
			if (count($values)==0)
				 $years_in_link = array($row[$column]);
			else
				$years_in_link = array_diff($values, array($row[$column]));

			// Kolejność sortowania
			if ($selected_count == 1) // tylko ta opcja jest zaznaczona
				$rows[$id]['filter_order'] = implode(",",$filter_order);
			elseif ($selected_all)
				$rows[$id]['filter_order'] = implode(",",array_filter(array_merge($filter_order, array($attribute_name)), "strval"));			
			else
				$rows[$id]['filter_order'] = implode(",",$filter_order);
			
		}else{
			$years_in_link = array_merge($values, array($row[$column]));
			if ($selected_any)
				$rows[$id]['filter_order'] = implode(",",$filter_order);
			else
				$rows[$id]['filter_order'] = implode(",",array_filter(array_merge($filter_order, array($attribute_name)), "strval"));
		}
		sort($years_in_link);
		$rows[$id]['link'] = implode(",",$years_in_link);   
	}
	
}

function array_map_replace_spaces(&$value){
	$value['name'] = str_replace(" ", "&nbsp;", $value['name']);
} 

function array_walk_highlight(&$value, $key, $phrase){
	$value['title'] = str_replace($phrase, "<em>$phrase</em>", $value['title']);
} 

function where_or($column, $values){
	$ors = array();
	foreach ($values as $value)
		$ors[] = "$column = '$value'";
	if (count($ors)>0)	
		return "(" . implode(" OR ", $ors) . ")";
	else
		return "";
}

/**
 * Tworzy stronicowanie.
 */
function create_pagging($rows_all, $limit, $p){
	// Przygotuj mapę podstron do szybkiej nawigacji
	$page_map = array();
	$pages = (int)floor(($rows_all+$limit-1)/$limit);
	$pi = 0;
	for ( $pi = 0;  $pi < 2 && $pi < $pages; $pi++ ) 
		$page_map[] = array('p'=>$pi, 'text'=>($pi+1), 'selected'=>$pi==$p);
	if ( $p-2 > 2+1 )
		$page_map[] = array('nolink'=>1, 'text'=>"...");
	for ( $pim = max($p-5, $pi); $pim < $p+5+1 && $pim < $pages; $pim++)
		$page_map[] = array('p'=>$pim, 'text'=>($pim+1), 'selected'=>$pim==$p);
	if ( $pages-2 > $p+5+1 )
		$page_map[] = array('nolink'=>1, 'text'=>"...");
	for ( $pi = max($pages-2, $p+5);  $pi < $pages; $pi++ ) 
		$page_map[] = array('p'=>$pi, 'text'=>($pi+1), 'selected'=>$pi==$p);
//		1:10
//		p-5:p+5
//		n-10:n
	return $page_map;	
}
?>
