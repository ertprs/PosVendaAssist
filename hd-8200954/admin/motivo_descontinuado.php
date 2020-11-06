<?php 

include "dbconfig.php";
include "includes/dbconnect-inc.php";
if (preg_match("/\/admin\//", $_SERVER["PHP_SELF"])) {
	include 'autentica_admin.php';
} else {
	include 'autentica_usuario.php';
}

?>
<!DOCTYPE html />
<html>
	<head>
		<meta http-equiv=pragma content=no-cache>
		<link href="bootstrap/css/bootstrap.css" type="text/css" rel="stylesheet" media="screen" />
    	<link href="bootstrap/css/extra.css" type="text/css" rel="stylesheet" media="screen" />
    	<link href="css/tc_css.css" type="text/css" rel="stylesheet" media="screen" />
    	<link href="bootstrap/css/ajuste.css" type="text/css" rel="stylesheet" media="screen" />
		<link href="plugins/dataTable.css" type="text/css" rel="stylesheet" />
		<link href="plugins/font_awesome/css/font-awesome.css" type="text/css" rel="stylesheet" />
		<link href='plugins/select2/select2.css' type='text/css' rel='stylesheet' />

		<script src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
		<script src="bootstrap/js/bootstrap.js"></script>
		<script src="plugins/dataTable.js"></script>
	</head>
	<body>
		<table class="table table-hover table-fixed">
            <thead  class="titulo_coluna">
                <tr>
                    <th colspan="2">Produto Descontinuado</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                	<td>
                		<p style='color:#ff0000; text-align: center'>
                			Esse produto tem mais de 3 anos de descontinuado.
                		</p>
                	</td>
                </tr>
                <tr>
                    <td>
                    	<label>Informe o motivo da liberação:</label>
                    	<textarea style='width: 100%; height: 100px' id='motivo'></textarea>
                    </td>
                </tr>
                <tr>
                	<td style="text-align: center">
                		<button type="button" class="btn btn-primary" onclick="window.parent.gravaMotivo(document.getElementById('motivo').value); window.parent.Shadowbox.close();">Gravar</button>
                	</td>
                </tr>
            </tbody>
        </table>		
	</body>
</html>

