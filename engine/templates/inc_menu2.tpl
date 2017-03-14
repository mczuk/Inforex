{*
 * Part of the Inforex project
 * Copyright (C) 2013 Michał Marcińczuk, Jan Kocoń, Marcin Ptak
 * Wrocław University of Technology
 * See LICENCE 
 *}

<div class="tnav">
	<nav class="navbar navbar-default">
		<div class="container-fluid">
			<div class="navbar-header">
				<button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar1">
					<span class="sr-only">Toggle navigation</span>
					<span class="icon-bar"></span>
					<span class="icon-bar"></span>
					<span class="icon-bar"></span>
				</button>
				<a class="navbar-brand" href="index.php"><img src="gfx/inforex_logo_small.jpg" alt="Inforex"></a>
			</div>
			<ul class="nav navbar-nav">
				<li class="{if $page=="home"} active{/if}">
					<a  href="index.php?page=home">Corpora</a>
				</li>
				{if $corpus.id && ( "read"|has_corpus_role_or_owner || "admin"|has_role || $corpus.public ) }
					<li class="active dropdown navbar-sub">
						<a class="dropdown-toggle" data-toggle="dropdown" href="index.php?page=start&amp;corpus={$corpus.id}"><b>{$corpus.name}</b>
							<span class="caret"></span></a>
						<ul class="dropdown-menu">
                            {foreach from=$corpus.user_corpus item=element}
								<li><a href="index.php?page={if $row.title}browse{else}{$page}{/if}&amp;corpus={$element.corpus_id}">{$element.name}</a></li>
                            {/foreach}
						</ul>
					</li>
				<li class="navbar-sub dropdown nav_corpus_pages" style="background: #eee">
					<a class="dropdown-toggle" data-toggle="dropdown" href="#">Corpus page<span class="caret"></span></a>
					<ul class="dropdown-menu">
						<li{if $page=="start"} class="active"{/if}><a href="index.php?page=start&amp;corpus={$corpus.id}">Start</a></li>
                        {if "admin"|has_role || "manager"|has_corpus_role_or_owner}
							<li{if $page=="corpus"} class="active"{/if}><a href="index.php?page=corpus&amp;corpus={$corpus.id}">Settings</a></li>
                        {/if}
						<li{if $page=="browse" || $page=="report"} class="active"{/if}><a href="index.php?page=browse&amp;corpus={$corpus.id}{if $report_id && $report_id>0}&amp;r={$report_id}{/if}">Documents</a></li>
                        {if "browse_annotations"|has_corpus_role_or_owner}
							<li{if $page=="annmap"} class="active"{/if}><a href="index.php?page=annmap&amp;corpus={$corpus.id}">Annotations</a></li>
							<li{if $page=="annotation_browser"} class="active"{/if}><a href="index.php?page=annotation_browser&amp;corpus={$corpus.id}">Annotation browser</a></li>
							<li{if $page=="annotation_frequency"} class="active"{/if}><a href="index.php?page=annotation_frequency&amp;corpus={$corpus.id}">Annotation frequency</a></li>
                        {/if}
                        {if "browse_relations"|has_corpus_role_or_owner}
							<li{if $page=="relations"} class="active"{/if}><a href="index.php?page=relations&amp;corpus={$corpus.id}">Relations</a></li>
                        {/if}
                        {if "run_tests"|has_corpus_role_or_owner}
							<li{if $page=="tests"} class="active"{/if}><a href="index.php?page=tests&amp;corpus={$corpus.id}">Tests</a></li>
                        {/if}
						<li{if $page=="stats"} class="active"{/if}><a href="index.php?page=stats&amp;corpus={$corpus.id}">Statistics</a></li>
                        {if "agreement_check"|has_corpus_role_or_owner}
							<li{if $page=="agreement_check"} class="active"{/if}><a href="index.php?page=agreement_check&amp;corpus={$corpus.id}">Agreement</a></li>
                        {/if}
                        {if $corpus.id == 3}
							<li{if $page=="lps_authors"} class="active"{/if}><a href="index.php?page=lps_authors&amp;corpus={$corpus.id}">Authors of letters</a></li>
							<li{if $page=="lps_stats"} class="active"{/if}><a href="index.php?page=lps_stats&amp;corpus={$corpus.id}">PCSN statistics</a></li>
							<li{if $page=="lps_metric"} class="active"{/if}><a href="index.php?page=lps_metric&amp;corpus={$corpus.id}">PCSN metrics</a></li>
                        {/if}
						<li{if $page=="word_frequency"} class="active"{/if}><a href="index.php?page=word_frequency&amp;corpus={$corpus.id}">Words frequency</a></li>
						<li{if $page=="wccl_match"} class="active"{/if}><a href="index.php?page=wccl_match&amp;corpus={$corpus.id}">Wccl Match</a></li>
                        {if $corpus.id == 1}
                            {if !$RELEASE && $user && false}
								<li{if $page=="list_total"} class="active"{/if}><a href="index.php?page=list_total">Postęp</a></li>
								<li{if $page=="titles"} class="active"{/if}><a href="index.php?page=titles">Nagłówki</a></li>
                            {/if}
							<li{if $page=="ontology"} class="active"{/if}><a href="index.php?page=ontology&amp;corpus={$corpus.id}">Ontology</a></li>
                        {/if}
                        {if "tasks"|has_corpus_role_or_owner}
							<li{if $page=="tasks" or $page=="task"} class="active"{/if}><a href="index.php?page=tasks&amp;corpus={$corpus.id}">Tasks</a></li>
                        {/if}
                        {if "export"|has_corpus_role_or_owner}
							<li{if $page=="export"} class="active"{/if}><a href="index.php?page=export&amp;corpus={$corpus.id}">Export</a></li>
                        {/if}
                        {if "add_documents"|has_corpus_role_or_owner || "admin"|has_role}
							<li{if $page=="document_edit"} class="active"{/if}><a href="index.php?page=document_edit&amp;corpus={$corpus.id}">Add document</a></li>
							<li{if $page=="upload"} class="active"{/if}><a href="index.php?page=upload&amp;corpus={$corpus.id}">Upload documents</a></li>
                        {/if}
					</ul>
				</li>
				<li class="navbar-sub nav_corpus_page"><a href="#"></a></li>
				{if $row.title}
				<li class="navbar-sub"><a href="#">{if $row.subcorpus_name} &raquo; <span>Subcorpus:</span> <b>{$row.subcorpus_name}</b> {/if} {if $row.title} &raquo; <span>Document:</span> <b>{$row.title}</b>{/if}</a></li>
                {/if}
                {/if}
				<li{if $page=="ner"} class="active"{/if}><a href="index.php?page=ner">Liner2</a></li>
				<li{if $page=="ccl_viewer"} class="active"{/if}><a href="index.php?page=ccl_viewer">CCL Viewer</a></li>
                {if $config->wccl_match_enable}
					<li{if $page=="wccl_match_tester"} class="active"{/if}><a href="index.php?page=wccl_match_tester">Wccl Match Tester</a></li>
                {/if}
                {if "admin"|has_role}
					<li{if in_array($page, array("annotation_edit","relation_edit","event_edit","sense_edit","user_admin")) } class="active"{/if}>
						<a href="index.php?page=annotation_edit">Administration</a></li>
                {/if}
				<li{if $page=="about"} class="active"{/if}><a href="index.php?page=about">About & citing</a></li>
			</ul>
			<ul class="nav navbar-nav navbar-right">
				{*<li><a href="#">About</a></li>*}
				{if $user}
				<li><a href="index.php?page=user_roles"><b>{$user.login} {if $user.screename}[{$user.screename}]{/if}</b></a></li>
				{/if}
				<li>
                    {if $user}
						 <a href="#" id="logout_link" style="color: red">logout</a>
                    {else}
						<a href="#" id="login_link" style="color: green">login</a>
                    {/if}
				</li>
			</ul>
		</div>
	</nav>
</div>
	
    {if $page=="report"}
        <div id="document_navigation">
            <span title="Liczba raportów znajdujących się przed aktualnym raportem"> ({$row_prev_c}) </span>     
            {if $row_first}<a href="index.php?page=report&amp;corpus={$corpus.id}&amp;id={$row_first}">|< pierwszy</a>{else}<span class="inactive">|< pierwszy</span>{/if} ,
            {if $row_prev_100}<a href="index.php?page=report&amp;corpus={$corpus.id}&amp;id={$row_prev_100}">-100</a>{else}<span class="inactive">-100</span>{/if} ,
            {if $row_prev_10}<a href="index.php?page=report&amp;corpus={$corpus.id}&amp;id={$row_prev_10}">-10</a> {else}<span class="inactive">-10</span>{/if} ,
            {if $row_prev}<a id="article_prev" href="index.php?page=report&amp;corpus={$corpus.id}&amp;id={$row_prev}">< poprzedni</a>{else}<span class="inactive">< poprzedni</span>{/if}
            | <span style="color: black"><b>{$row_number}</b> z <b>{$row_prev_c+$row_next_c+1}</b></span> |
            {if $row_next}<a id="article_next" href="index.php?page=report&amp;corpus={$corpus.id}&amp;id={$row_next}">następny ></a>{else}<span class="inactive">następny ></span>{/if} ,
            {if $row_next_10}<a href="index.php?page=report&amp;corpus={$corpus.id}&amp;id={$row_next_10}">+10</a> {else}<span class="inactive">+10</span>{/if} ,
            {if $row_next_100}<a href="index.php?page=report&amp;corpus={$corpus.id}&amp;id={$row_next_100}">+100</a>{else}<span class="inactive">+100</span>{/if} ,
            {if $row_last}<a href="index.php?page=report&amp;corpus={$corpus.id}&amp;id={$row_last}">ostatni >|</a>{else}<span class="inactive">ostatni >|</span>{/if}
            <span title"Liczba raportów znajdujących się po aktualnym raporcie">({$row_next_c})</span>
        </div>
    {/if}           
	
