<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

if ($areaAdmin === true) {
    $admin_privilegios = "call_center"; 
    include __DIR__.'/admin/autentica_admin.php';
    include_once('../class/tdocs.class.php');
} else {
    include __DIR__.'/autentica_usuario.php';
    include_once('class/tdocs.class.php');
}    

$os = $_GET["os"];

$sql = "SELECT sua_os,
			   tbl_os.serie,
			   tbl_produto.referencia,
			   tbl_produto.descricao,
			   tbl_posto.nome
		FROM tbl_os
		JOIN tbl_produto USING(produto)
		JOIN tbl_posto USING(posto)
		WHERE os = {$os}";
$res = pg_query($con, $sql);

$referencia = pg_fetch_result($res, 0, "referencia");
$descricao  = pg_fetch_result($res, 0, "descricao");
$serie      = pg_fetch_result($res, 0, "serie");
$sua_os     = pg_fetch_result($res, 0, "sua_os");
$posto_nome = pg_fetch_result($res, 0, "nome");

include 'funcoes.php';
?>
<style type="text/css">
	tr {
		text-align-last: center;
	}
</style>

<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css" integrity="sha384-MCw98/SFnGE8fJT3GXwEOngsV7Zt27NXFoaoApmYm81iuXoPkFOJwJ8ERdknLPMO" crossorigin="anonymous">
<script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.3/umd/popper.min.js" integrity="sha384-ZMP7rVo3mIykV+2+9J3UJ46jBk0WLaUAdn689aCwoqbBJiSnjAK/l8WvCWPIPm49" crossorigin="anonymous"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/js/bootstrap.min.js" integrity="sha384-ChfqqxuZUCnJSK3+MXmPNIyE6ZbWh2IMqE241rYiqJxyMiZ6OW/JmZQ5stwEULTy" crossorigin="anonymous"></script>
<style type="text/css">
        body {
            margin: 1em;
        }
        .titulo {
            font-family: bold Verdana, Geneva, Arial, Helvetica, sans-serif;
            text-align: left;
            color: #000000;
            background: #D0D0D0;
            border-bottom: dotted 1px #a0a0a0;
            border-right: dotted 1px #a0a0a0;
            border-left: dotted 1px #a0a0a0;
            padding: 1px,1px,1px,1px;
            font-size: 07px;
        }
        .titulo_destaque{
            font-size: 9px;
            text-align: left;
            color: #000000;
            background: #D0D0D0;
            border-bottom: dotted 1px #a0a0a0;
            border-right: dotted 1px #a0a0a0;
            border-left: dotted 1px #a0a0a0;
            padding: 1px,1px,1px,1px;
        }
        .texto{
            font-size: 12px;
            font: arial;
            background: #ffffff;
            padding: 1px,1px,1px,1px;
            text-align: justify;
        }
        .assinatura{
            border: 1px solid;
            width: 200px;
            text-align: left;
            display: inline-block;
        }

        .data_entrada{
            border: 1px solid;
            width: 100px;
            text-align: left;
            display: inline-block;
        }
        .texto_termos{
            width: 600px;
            margin-top:3px;

        }

        .texto_termos p{
            font: 7px 'Arial' !important;
            text-align: justify;
            margin: 0 0 5px 0;
        }

        .conteudo {
          	font: 8px Arial;
            text-align: left;
            background: #ffffff;
            border-right: dotted 1px #a0a0a0;
            border-left: dotted 1px #a0a0a0;
        }

        .conteudo_destaque {
            font-size: 9px;
            text-align: left;
            background: #ffffff;
            border-right: dotted 1px #a0a0a0;
            border-left: dotted 1px #a0a0a0;
            padding: 1px,1px,1px,1px;
        }

        td.conteudo ul li {
            list-style: square inside;
        }

        .conteudo2 {
            font-size: 8px;
            font-family: Arial;
        }

        .titulo2{
            font-size: 8px;
            font-weight: bold;
            font-family: Arial;
            border-bottom: 1px solid #000000;
            text-align:center;
            background-color: #cccccc;
        }

        .borda {
            border: solid 1px #c0c0c0;
        }

        .etiqueta {
            font: 100% Tahoma, Verdana, Arial, Helvetica, Sans-Serif;
            color: #000000;
            text-align: center;
        }

/*        @media print {
            
            html {
                -moz-transform       : scale( 0.8, 0.8);
                -moz-transform-origin: top left;
            }
               
        }*/

        h2 {
            font: 60% Tahoma, Verdana, Arial, Helvetica, Sans-Serif;
            color: #000000
        }

            }
    </style>
<table border="1" cellspacing="0" cellpadding="0">
    <tbody>
    	<tr>                 
    		<td class="etiqueta"><font size="2px"><b>OS <?= $sua_os ?></b></font><br>Ref. <?= $referencia ?>  <br> <?= $descricao ?> . <br>N.Série <?= $serie ?><br><?= $posto_nome ?>         
    		</td>
    	</tr>
    </tbody>
</table>

<script type="text/javascript">
	window.print();
</script>
