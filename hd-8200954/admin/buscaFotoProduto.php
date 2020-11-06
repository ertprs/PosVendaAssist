<?php
	$areaAdmin = preg_match('/\/admin\//',$_SERVER['PHP_SELF']) > 0 ? true : false;
	if ($areaAdmin === true) {
		include __DIR__.'/dbconfig.php';
		include __DIR__.'/includes/dbconnect-inc.php';
		include __DIR__.'/admin/autentica_admin.php';
		include_once "../class/aws/s3_config.php";
	} else {
		include __DIR__.'/dbconfig.php';
		include __DIR__.'/includes/dbconnect-inc.php';
		include __DIR__.'/autentica_usuario.php';
		include_once "class/aws/s3_config.php";
	}

	include_once S3CLASS;
	
	if(isset($_POST['pegaFotoProduto'])){

		$produto_id 		= (int)$_POST["produto_id"];
		$login_fabrica		= (int)$_POST["login_fabrica"];

		$objList 		= $produto_id.".";

		$objAmazonS3 	= new AmazonTC('produto', $login_fabrica);
		$arquivo 		= $objAmazonS3->getObjectList("$objList");

		$basename 		= basename($arquivo[0]);
		$basename_thumb = "thumb_".$basename;
      	
      	$full  = $objAmazonS3->getLink($basename, false, "", "");
		$thumb = $objAmazonS3->getLink($basename_thumb, false, "", "");

      	
		if (count($arquivo) > 0) {
      		echo "<div class='row-fluid'>";
				echo "<div class='span1'></div>";
				echo "<div class='span4'>";
					echo "<label class='control-label' for='produto_defeito_constatado'>Foto do Produto</label>";
					echo "<div>";
					echo "<a href='$full' target='_blank'> <img src='$thumb' class='anexo_thumb' style='width: 100px; height: 90px;'></a>";		
					echo "</div>";
				echo "</div>";
			echo "</div>";
		} else {
			echo "";
		}
	}
?>
