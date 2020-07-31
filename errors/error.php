<?php 
$error_code = null;
$error_code = (isset($_GET['e'])) ? strip_tags($_GET['e']) : null;
if(isset($error_code)) 
{ 
	if(!is_numeric($error_code)) 
	{ 
		$error_code = null; 
	} 
	else if( trim($error_code)!="404" && trim($error_code)!="403" && trim($error_code)!="500" ) 
	{ 
		$error_code = null; 
	} 
}

if(!empty($error_code)) 
{	
	if(file_exists(trim($error_code).'.php'))
	{ include_once(trim($error_code).'.php'); }
	else if(file_exists(dirname(__FILE__).'../maintenance/maintenance_page.php'))
	{ include_once('../maintenance/maintenance_page.php'); }
	else
	{ exit('Error '.trim($error_code)); }
}
else 
{
	if(file_exists('../maintenance/maintenance_page.php'))
	{ include_once('../maintenance/maintenance_page.php'); }
	else exit('Unknown error');
}
?>