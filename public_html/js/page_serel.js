var tagsToReplace = {
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;'
};

function replaceTag(tag) {
    return tagsToReplace[tag] || tag;
}

function safe_tags_replace(str) {
    return str.replace(/[&<>]/g, replaceTag);
}


function show_semquel_data(semquel_data){
	set_element_html($(".measure"), semquel_data.measure);
	set_element_html($(".relation_type"), semquel_data.relation_type);
	set_element_html($(".relation_subtype"), semquel_data.relation_subtype);
	set_element_html($(".type"), semquel_data.object_type);
	set_element_html($(".argument"), semquel_data.argument);
	set_element_html($(".question_description"), semquel_data.description);
	set_element_html($(".sql_code"), "<code style=\"white-space: pre\">"+semquel_data.semql+"</code>");
}

function set_element_html($element, html){
	$element.html(html);
}

/**
 * 
 */
function gui_start_processing(){
	$(".question").attr('disabled', 'disabled');
	$(".buttonRun").attr('disabled', 'disabled');
	$("#box-interpretation").hide();
	$("#box-answer").hide();
	$("#ajax-big").show();
	$("#box-context").hide();
	$("#box-error").remove();
}

/**
 * 
 */
function gui_end_processing(){
	$(".question").attr('disabled', '');
	$(".buttonRun").attr('disabled', '');
	$("#ajax-big").hide();	
}

/** 
 * Wstaw tekst od szablony HTML i wyświetl na stronie 
 **/
function display_question_answers(rows){
	$(".results_list").html("");
	$.each(rows, function(key, value){
		$(".results_list").append((key == 0 ? "" : ", ")+"<a href=\"#\" id=\""+value.relation_ids+"\">"+value.text+"</a> ("+value.relation_ids.split(',').length+")");
	});
	$("#box-answer").show();
}

function run_semql(question){
	$.ajax({
		url : "index.php",
		dataType : "json",
		type : "post",
		data : {
			ajax : "semquel_run",
			question : question
		},				
		success : function(data){
			$("#box-interpretation").show();
			if (data.output.length){
				var semquel_data = data.output[0];
				show_semquel_data(semquel_data);				
			
				ajaxErrorHandler(data,
					function(){						
						sql = semquel_data.semql;
						sql = sql.replace("SELECT ", "SELECT GROUP_CONCAT(r.relation_id) AS relation_ids, ");				
						get_sql_results(sql);					
					},
					function(){
						run_semql(question);
					}
				);
			}
			else{
				set_element_html($(".question_description"), "Brak dopasowania");
				$(".semquel_results td").html('');
				gui_end_processing();
			}
		},
		error : function(data){
			$("#box-question").after('<div id="box-error">'+data.responseText+'</div>');
			gui_end_processing();
		}
	});
}

function get_sql_results(semquel){
	$.ajax({
		url : "index.php",
		dataType : "json",
		type : "post",
		data : {
			ajax : "semquel_get_sql",
			semquel : semquel
		},				
		success : function(data){
			ajaxErrorHandler(data,
				function(){
					display_question_answers(data.output);
					gui_end_processing();
				},
				function(){
					get_sql_results(semquel);
				}
			);								
		}
	});
}

function get_result_descriptions(ids, result_name){
	$(".result_element_title").html("Szczegóły dla &raquo;<b>"+result_name+"</b>&laquo;");
	$.ajax({
		url : "index.php",
		dataType : "json",
		type : "post",
		data : {
			ajax : "semquel_get_result",
			id_list : ids
		},				
		success : function(data){
			ajaxErrorHandler(data,
				function(){	
					var html = "<ol class='answer-contexts'>";					
					$.each(data.output, function(key, value){
						html += "<li>"+ value +"</li>";
					});					
					html += "</ol>";
					$(".answer-context").html(html);
					$("#box-context").show();
					$(".answer-context").show();
					gui_end_processing();
				},
				function(){
					get_result_descriptions(ids, result_name);
				}
			);								
		}
	});
}

$(function(){
	$(".buttonRun").live("click",function(){
		var question = $(".question").val();
		gui_start_processing();		
		run_semql(question);		
	});

	$(".show_hide_semql").live({
		click: function(){
			if ($(this).hasClass("showItems")){
				$(this).removeClass("showItems");
				$(this).html("ukryj");
				$(".semquel_results").show();
			}
			else{  
				$(this).addClass("showItems");
				$(this).html("pokaż");
				$(".semquel_results").hide();
			}
			return false;
		}				
	});
	
	$("a[href=#]").live({
		click: function(){
			if ($(this).attr("id") != ''){
				$("a[href=#]").css("font-weight", "normal");
				$(this).css("font-weight", "bold");
				$("#ajax-big").show();
				$(".answer-context").hide();
				get_result_descriptions($(this).attr("id"), $(this).text());
			}
			return false;
		}				
	});
});
