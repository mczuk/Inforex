<?php

ini_set("error_reporting", E_ALL & ~E_NOTICE);
ini_set("display_errors", 0);
ini_set("output_buffering", 1);

ob_start();

// Czy strona jest wersją publiczną
define(IS_RELEASE, false);

// Ustaw domyślne kodowanie podczas przetwarzania tekstu
mb_internal_encoding("UTF-8");
		
/********************************************************************8
 * Dołącz pliki.
 */
// Wczytanie konfiguracji skryptu
require_once("config.php");


// Dołączenie bibliotek
ini_set("include_path", ini_get("include_path").":/home/czuk/PEAR");
require_once($conf_global_path . '/include.php');

/********************************************************************8
 * Wczytaj parametry z URL
 */
$corpora = isset($_GET['corpora']) ? $_GET['corpora'] : 1; 


/********************************************************************8
 * Połączenie z bazą danych
 */
$options = array(
    'debug' => 2,
    'result_buffering' => false,
);

$mdb2 =& MDB2::singleton($dsn, $options);

if (PEAR::isError($mdb2)) {
    die($mdb2->getMessage());
}
$mdb2->loadModule('Extended');
$mdb2->loadModule('TableBrowser');
if (PEAR::isError($r = $mdb2->query("SET CHARACTER SET 'utf8'")))
	die("<pre>[init.php] {$r->getUserInfo()}</pre>");


/********************************************************************8
 * Aktywuj FireBug-a
 */
//FB::setEnabled(true);

/********************************************************************8
 * Rozpocznij sesję
 */
HTTP_Session2::useCookies(true);
HTTP_Session2::start('gpw');
HTTP_Session2::setExpire(time() + 60 * 60 * 24 * 356 * 2);

/********************************************************************8
 * Autoryzacja użytkownika
 */
$params = array(
            "dsn" => $dsn,
            "table" => "users",
            "usernamecol" => "login",
            "passwordcol" => "password",
            "db_fields" => array("screename")
            );
$auth = new Auth("MDB2", $params, null, false);

if ($_POST['logout']=="1")
	$auth->logout();
else
	$auth->start(); 

/********************************************************************8
 * Wykonaj akcje
 */
$action = $_REQUEST['action'];
if ($action && file_exists("$conf_global_path/actions/a_{$action}.php")){
	include("$conf_global_path/actions/a_{$action}.php");
	$class_name = "Action_{$action}";
	$o = new $class_name();
	$page = $o->execute();	
}else{
	$page = $_GET['page'];
}

$page = $corpora ? ( $page ? $page : 'browse') : 'home';

/********************************************************************8
 * Wygeneruj stronę lub żądanie AJAX
 */
$ajax = $_REQUEST['ajax'];
if ($ajax){
	include("$conf_global_path/ajax/a_{$ajax}.php");
	$class_name = "Ajax_{$ajax}";
	$o = new $class_name();
	$page = $o->execute();	
	
//	echo json_encode(array("error"=>"Ta funkcjonalność wymaga logowania"));
}elseif (file_exists("$conf_global_path/pages/{$page}.php")){
	include("$conf_global_path/pages/{$page}.php");
	$class_name = "Page_{$page}";	
	$o = new $class_name();
	
	if ($o->isSecure && !$auth->getAuth()){
		include("$conf_global_path/pages/login.php");
		$o = new Page_login();
		$o->display("login");
	}
	else{
		$o->execute();
		$o->set('user', $auth->getAuthData());
		$o->set('page', $page);
		$o->set('release', RELEASE);
		$o->set('corpora', $corpora);
		$o->display($page);
	}	
}else{
	die("File not found: $conf_global_path/pages/{$page}.php");
}

ob_flush();

?>
