<?php

include "dbconfig.php";
include "includes/dbconnect-inc.php";
include 'autentica_admin.php';

header("Expires: 0");
header("Cache-Control: no-cache, public, must-revalidate, post-check=0, pre-check=0");
header("Pragma: no-cache, public");
?>

<!-- AQUI COMEÇA O HTML DO MENU -->
<head>
	<link type="text/css" rel="stylesheet" href="css/css.css">
</head>

<!doctype html public "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
<title> Pesquisa Postos... </title>
<meta name="Author" content="">
<meta name="Keywords" content="">
<meta name="Description" content="">
<meta http-equiv=pragma content=no-cache>


	<link href="css/estilo_cad_prod.css" rel="stylesheet" type="text/css" />
	<link href="css/posicionamento.css" rel="stylesheet" type="text/css" />

 <style type="text/css">
.titulo_tabela{
	background-color:#596d9b;
	font: bold 14px "Arial";
	color:#FFFFFF;
	text-align:center;
}


.titulo_coluna{
	background-color:#596d9b;
	font: bold 11px "Arial";
	color:#FFFFFF;
	text-align:center;
}

table.tabela tr td{
font-family: verdana;
font: bold 11px "Arial";
border-collapse: collapse;
border:1px solid #596d9b;
}

.color_b {
	background: white !important;
}
</style>
</head>

<body class='color_b' topmargin='0' leftmargin='0' marginwidth='0' marginheight='0'>
  	<div id="menu">
		<img src='imagens/lupas/bg_lupa.jpg'>
  	</div>

<br>

<?php
	$usa_rev_fabrica = in_array($login_fabrica, array(184,191,200)); 

	$tipo = trim ($_GET['tipo']);
	$valor = trim ($_GET['campo']);

	if ($tipo == 'nome'){

		if ($usa_rev_fabrica) {
			$whereAdc = " WHERE contato_razao_social ~* '$valor' ";
		} else {
			$whereAdc = " WHERE tbl_revenda.nome ~* '^$valor' ";
		}

	}else if ($tipo == 'cnpj'){
		$valor = preg_replace("/\D/", "", $valor);
		if ($usa_rev_fabrica) {
			$whereAdc = " WHERE cnpj ~* '^$valor' ";
		} else {
			$whereAdc = " WHERE tbl_revenda.cnpj ~* '^$valor' ";
		}

	}

	if (strlen(trim($tipo)) > 0){
		$sql = "
			SELECT  tbl_revenda.revenda,
					tbl_revenda.nome,
					tbl_revenda.cnpj,
	                tbl_cidade.estado,
				    tbl_cidade.nome AS cidade_nome,
				    tbl_cidade.cidade AS cidade,
				    tbl_revenda.fone
			FROM     tbl_revenda
			LEFT JOIN     tbl_cidade USING(cidade)
			$whereAdc
			AND tbl_revenda.cnpj IS NOT NULL
			AND tbl_revenda.cnpj_validado IS TRUE
			ORDER BY tbl_revenda.nome";

			if ($usa_rev_fabrica) {
		    	$sql = "SELECT
							tbl_revenda_fabrica.revenda,
							tbl_revenda_fabrica.contato_razao_social AS nome,
							tbl_revenda_fabrica.cnpj                ,
						    tbl_cidade.estado				        ,
						    tbl_cidade.nome AS cidade_nome           ,
						    tbl_cidade.cidade AS cidade 			,
						    tbl_revenda_fabrica.contato_fone AS fone 
					FROM tbl_revenda_fabrica
					LEFT JOIN tbl_cidade ON tbl_cidade.cidade = tbl_revenda_fabrica.cidade
					$whereAdc
					AND tbl_revenda_fabrica.fabrica = $login_fabrica
					ORDER BY tbl_cidade.estado, tbl_cidade.cidade, contato_razao_social";
			}


		$res = pg_query($con, $sql);

		if (pg_num_rows($res) > 0){
		?>
		<table width='100%' border='0' cellspacing='1' class='tabela'>
			<tr class='titulo_tabela'>
				<td colspan='5'>
					<?php if ($tipo == "nome"){ ?>
					<font style='font-size:14px;'>Pesquisando por <b>Nome da Revenda</b>: <i><?=$valor?></i></font>
					<?php }else if ($tipo == "cnpj"){?>
					<font style='font-size:14px;'>Pesquisando por <b>CNPJ da Revenda</b>: <i><?=$valor?></i></font>
					<?php } ?>
				</td>
			</tr>
			<tr class='titulo_coluna'>
				<td>CNPJ</td>
				<td>Nome</td>
				<td>Cidade</td>
				<td>UF</td>
			</tr>
		<?php

		for ( $i = 0 ; $i < pg_numrows ($res) ; $i++ ) {
			$revenda 		= trim(pg_result($res,$i,revenda));
			$nome       	= trim(pg_result($res,$i,nome));
			$cidade     	= trim(pg_result($res,$i,cidade_nome));
			$estado     	= trim(pg_result($res,$i,estado));
			$xcnpj       	= trim(pg_result($res,$i,cnpj)); 
			$cnpj 			= substr ($xcnpj,0,2) . "." . substr ($xcnpj,2,3) . "." . substr ($xcnpj,5,3) . "/" . substr ($xcnpj,8,4) . "-" . substr ($xcnpj,12,2);

			if($i%2==0) $cor = "#F7F5F0"; else $cor = "#F1F4FA";
		?>
			<tr bgcolor="<?=$cor?>" style="cursor: pointer;" onclick='window.parent.retorna_lupa_callcenter_revenda("<?=$revenda?>","<?=$nome?>", "<?=$cnpj?>"); window.parent.Shadowbox.close();' >
				<td><?=$cnpj?></td>
				<td><?=$nome?></td>
				<td><?=$cidade?></td>
				<td><?=$estado?></td>
			</tr>
		<?php } ?>

		</table>
<?php
		}else{
			echo "Nenhum resultado encontrado";
		}
	}
?>
</body>
</html>
