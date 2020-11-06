<?php
##################################################################################
#
# File				: Demo file for advaned Upload Class.
# Class Title		: advancedUploadClass
# Class Description	: This class is used to handle the uploading of files.
#					  With advanced feature such as checking file size, checking
#					  file type, etc.
# Class Notes		: Please let me know if you have any questions / problems 
#					  / suggestions relating to this class.
# Copyright			: 2007
# Licence			: http://www.gnu.org/copyleft/gpl.html GNU var License
# Author 			: Mark Davidson <design@fluxnetworks.co.uk> 
#					  <http://design.fluxnetworks.co.uk>
# Created Date     	: 05/03/2007
# Last Modified    	: 10/03/2007
#
##################################################################################
$action = $_GET['action'];
require_once('auc.main.class.inc.php');

$auc = new auc();

if ($action == 'uploadfile') {
	$auc = new auc();
	//Comment: $auc->upload_dir("directory name", "create dir if it does not exist, false by default or true");
	//$auc->upload_dir("/path/to/uploads/folder/with/trailing/slash/", false);
	//Comment: $auc->make_safe = true || false (default); make the file name safe
	//$auc->make_safe = true;
	//Comment: $auc->max_file_size = size in bytes (1MB default) || false; set max file size in bytes or false not to check size
	//$auc->max_file_size = 1048576;
	//Comment: $auc->overwrite = true || false (default); overwrite if file exists
	//$auc->overwrite = true;
	//Comment: $auc->check_file_type = false (default) || allowed || denied; 
	//$auc->check_file_type = 'allowed';	
	$result = $auc->upload("file");
	if (is_array($result)) {
		echo 'Something Went Wrong';
		echo '<pre>';
		var_dump($result);
		echo '</pre>';
	} else {
		echo 'All OK';
	}
} else {
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<title>advanced Upload Class - Demo</title>
</head>
<body>
<form action="auc.demo.php?action=uploadfile" method="post" enctype="multipart/form-data">
  <input name="file[]" type="file" /><br />
  <input name="file[]" type="file" /><br />
  <input name="upload" type="submit" value="Upload File" />
</form>
</body>
</html>
<?php  } ?>
