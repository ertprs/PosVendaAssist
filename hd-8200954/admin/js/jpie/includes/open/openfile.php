<?php
	include('../configure.php');
	include('functions.php');
session_start();
error_reporting(1);


if (isset($_GET['open'])){
	if (!copy(IMAGE_PATH . $_GET['open'],TMP_IMAGE_PATH . $_GET['open'])) {
	    echo "failed to open $file...\n";
	}
	echo "<script>window.opener.openImage('".$_GET['open']."')</script>";
	exit(0);
}

if(isset($_GET['field_name'])) {
	$_SESSION['field_name'] = $_GET['field_name'];
}

if(isset($_GET['path'])) {
	$_SESSION['path'] = $_GET['path'];
} else {
 	if (!isset($_SESSION['path'])){
 		 $_SESSION['path']="";
 	}
}

// Load all files and directories in its arrays

$files = array();
$dirs = array();
$overwrite = 1;

//Operaciones con los fichero del directorio

  $dirpath= IMAGE_PATH . $_SESSION['path'];

// Remove directory
if (isset($_GET['delDir'])) {
	deleteDir($_SERVER["DOCUMENT_ROOT"] . $dirpath . "/" .$_GET['delDir']);
}

if (isset($_GET['delFile'])) {
	unlink($_SERVER["DOCUMENT_ROOT"] .$dirpath."/".$_GET['delFile']);
}

if (isset($_POST['newFolderField'])) {
 	echo $dirpath . $_POST['newFolderField'];
	mkdir($dirpath ."/". $_POST['newFolderField'] , 0777);
}

if($_FILES['file']) {
	$upload = true;
	if(!$overwrite) {
		if(file_exists($dirpath.'/'.$_FILES['file']['name'])) {
			$upload = false;
		}
	}

	if (isset($_POST['anchura'])) {
		$max_width = $_POST['anchura'];
	} else {
		$max_width = "";
	}

	if (isset($_POST['altura'])) {
		$max_height = $_POST['altura'];
	} else {
		$max_height= "";
	}

	$x = uploadFile($_FILES,$dirpath,$max_width,$max_height);
}

$file_type = $_GET['type'];


$dh = opendir($dirpath);
while (false !== ($file = readdir($dh))) { 
	// Comprobamos si es un directorio o un fichero
	if (is_dir($dirpath."/".$file)) { //es directorio		 	
		$dirs[]=$file; 
	} else { //es fichero
		$files[]=$file;
	}			
}

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
"http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<title>Image Browser</title>
<link rel="stylesheet" type="text/css" href="stylesheet.css">
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<style>
body {
	font-family:Arial;
	font-size:10pt;
}
</style>
</head>
<body> 
<div id="container"> 
<div id="fileList" style="overflow:auto;height:300px">
    <?php
	//Show all directories

	echo "<table>";
	echo "<tr><th>Filename</th><th>Size</th></tr>";
		
	for ($i=0;$i<count($dirs);$i++) {
	 	echo "<tr><td colspan=\"2\">";
	 	
	 	$dirOk = false;
		 if (($dirs[$i] != ".") && ($dirs[$i] != "..")) {
	 	 	$thisDir = $_SESSION['path']."/".$dirs[$i];
	 	 	$dirOk = true;
		} elseif ($dirs[$i] == "..") {
		 	$dirnames = split('/', $_SESSION['path']);
		 	$thisDir = "/";
		 	for($di=1; $di<(sizeof($dirnames)-1); $di++) {
                  $thisDir.=  $dirnames[$di] . '/';
            }
            $thisDir=rtrim($thisDir, "/");
			$dirOk = true;	 
		}
		
		if ($dirOk) { //El directorio es un directorio válido (no está en la lista de prohibidos)
			echo "<a href=\"".$_SERVER['PHP_SELF']."?path=".$thisDir."\">";
			echo "<img src=\"folder.gif\" border=\"0\">";
			echo "</a>";

			if (strlen($dirs[$i])<25) {
			 	echo $dirs[$i];
			} else {
				echo substr($dirs[$i], 0, 22) . "...";
			}
			echo "\n";
			if ($dirs[$i]!="..") {
				echo "<div><a href=\"javascript:eliminaDir('". urlencode($dirs[$i] ) ."')\" style=\"width:50px;\"><img src=\"fileManagerImages/delete.gif\" border=\"0\" alt=\"Eliminar la carpeta\"></a></div>";				
			}

		}
		echo "</td></tr>";
	}	 

	for ($i=0;$i<count($files);$i++) {
			echo "<tr><td>";
			echo "<img src=\"file.gif\" border=\"0\">";
			if (strlen($files[$i])<26) {
			 	echo "<a href=\"openfile.php?open=" . $files[$i] ."\">". $files[$i] ."</a>";
			} else {
				echo "<a href=\"openfile.php?open=" . $files[$i] ."\">". substr($files[$i], 0, 22) . "...</a>";
			}
			echo "</td><td>";
			$fileinfo = stat($dirpath.'/'.$files[$i]);   
			echo 	round( ( $fileinfo[ "size" ] / 1024 ), 1 )  . "kb";
			echo "</td></tr>";
	}
	echo "</table>";
	
	closedir($dh); 		
	?>
	</div> 
	<form name="form_file2upload" action="openfile.php" method="POST" enctype="multipart/form-data">
		<input type="file" name="file"><input type="submit" value="Upload File">
	</form>
  </div> 

</body>
</html>