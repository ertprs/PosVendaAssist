<?php

include "dbconfig.php";
include "includes/dbconnect-inc.php";
include 'autentica_admin.php';

$os           = strtolower(trim($_REQUEST["os"]));
$tipo         = strtolower(trim($_REQUEST["tipo"]));


?>

<!DOCTYPE html>

<html>

	<head>

		<title>Pesquisa Posto</title>

		<meta name="Author" content="">
		<meta name="Keywords" content="">
		<meta name="Description" content="">
		<meta http-equiv=pragma content=no-cache>

		<link rel="stylesheet" type="text/css" href="../css/lupas/lupas.css">
		<style>
			body 
			{
				margin: 0;
				font-family: Arial, Verdana, Times, Sans;
				background: #fff;
			}
		</style>

		<script type="text/javascript" src="js/jquery-1.4.2.js"></script>
		<script>
			$(function(){

				$("input[name=codigo]").focus(function() {
					$("input[name=tipo]").val("codigo");
					$("input[name=nome]").val("");
				});

				$("input[name=nome]").focus(function() {
					$("input[name=tipo]").val("nome");
					$("input[name=codigo]").val("");
				});

			});
		</script>

	</head>

	<body>

		<div class="lp_header">
			<img src='css/modal/excluir.png' alt='Fechar' class='lp_btn_fechar'  style="cursor: pointer;" onclick="window.parent.Shadowbox.close();" />
		</div>
		<div class='lp_nova_pesquisa'>
			<form action='<?=$_SERVER["PHP_SELF"]?>' method='POST' name='nova_pesquisa'>
				<input type="hidden" name="tipo" value="<?=$tipo?>" />
				<input type="hidden" name="os" value="<?=$os?>">
				<table cellspacing='1' cellpadding='2' style="border: 0;">
					<tr>
						<td style="width: 200px;">
							<label>Código</label>
							<input type='text' name='codigo' value='<?=$codigo_posto?>' style='width: 150px;' />
						</td>
						<td style="width: 200px;">
							<label>Nome</label>
							<input type="text" name="nome" value="<?=$nome?>" style="width: 150px;" />
						</td>
						<td colspan='2' class='btn_acao' style="vertical-align: bottom; text-align: left;">
							<input type='submit' name='btn_acao' value='Pesquisar' />
						</td>
					</tr>
				</table>
			</form>
		</div>

		<?php

		if ($tipo == "nome") 
		{
			$nome = strtoupper(trim($_REQUEST["nome"]));
			if (empty($nome))
			{
				$nome = strtoupper(trim($_REQUEST["campo"]));
			}

			if (strlen($nome) > 2)
			{
				echo "<div class='lp_pesquisando_por'>Buscando por nome do posto: $nome</div>";

				$sql = "SELECT   
							tbl_posto.*, 
							tbl_posto_fabrica.codigo_posto,
							tbl_posto_fabrica.credenciamento
						FROM     
							tbl_posto
						JOIN     
							tbl_posto_fabrica 
							USING 
								(posto)
						WHERE    
							(tbl_posto.nome ILIKE '%$nome%' OR tbl_posto.nome_fantasia ILIKE '%$nome%')
							AND     
								tbl_posto_fabrica.fabrica = $login_fabrica
						ORDER BY 
							tbl_posto.nome";
				$res = pg_query($con,$sql);

				if (pg_num_rows($res) == 0) 
				{
					$msg_erro = "Posto '$nome' não encontrado";
				}
			}
			else
			{
				$msg_erro = "Informar toda ou parte da informação para realizar a pesquisa!";
			}
		}
		else if ($tipo == "codigo") 
		{
			$codigo_posto = strtoupper(trim($_REQUEST["codigo"]));
			if (empty($codigo_posto))
			{
				$codigo_posto = strtoupper(trim($_REQUEST["campo"]));
			}

			$codigo_posto = str_replace (".","",$codigo_posto);
			$codigo_posto = str_replace (",","",$codigo_posto);
			$codigo_posto = str_replace ("-","",$codigo_posto);
			$codigo_posto = str_replace ("/","",$codigo_posto);

			if (strlen($codigo_posto) > 0)
			{
				echo "<div class='lp_pesquisando_por'>Buscando por código do posto: $codigo_posto</div>";

				$sql = "SELECT   
							tbl_posto.*,
							tbl_posto_fabrica.codigo_posto,
							tbl_posto_fabrica.credenciamento
						FROM 
							tbl_posto
						JOIN 
							tbl_posto_fabrica 
							USING 
								(posto)
						WHERE   
							tbl_posto_fabrica.codigo_posto ILIKE '%$codigo_posto%'
							AND
								tbl_posto_fabrica.fabrica = $login_fabrica
						ORDER BY 
							tbl_posto.nome";
				$res = pg_query($con,$sql);

				if (pg_num_rows($res) == 0) {
					$msg_erro = "Posto '$codigo_posto' não encontrado";
				}
			}
			else
			{
				$msg_erro = "Informar toda ou parte da informação para realizar a pesquisa!";
			}
		}

		if (empty($msg_erro))
		{
			echo "<table cellspacing='1' cellspading='0' class='lp_tabela' style='border: 0; width: 100%;'>
						<tr style='cursor: default;'>
							<td>
								CNPJ
							</td>
							<td>
								Nome
							</td>
							<td>
								Cidade
							</td>
							<td>
								Estado
							</td>
						</tr>";

			for ( $i = 0 ; $i < pg_num_rows($res) ; $i++ ) 
			{
				$credenciamento = pg_result($res,$i,"credenciamento");
				$codigo_posto   = trim(pg_result($res,$i,"codigo_posto"));
				$posto          = trim(pg_result($res,$i,"posto"));
				$nome           = trim(pg_result($res,$i,"nome"));
				$nome           = str_replace ('"','',$nome);
				$cnpj           = trim(pg_result($res,$i,"cnpj"));
				$cnpj           = substr ($cnpj,0,2) . "." . substr ($cnpj,2,3) . "." . substr ($cnpj,5,3) . "/" . substr ($cnpj,8,4) . "-" . substr ($cnpj,12,2);
				$cidade         = trim(pg_result($res,$i,"cidade"));
				$estado         = trim(pg_result($res,$i,"estado"));
				$fone           = trim(pg_result($res,$i,"fone"));

				$cor = ($i % 2 <> 0) ? '#F7F5F0' : '#F1F4FA';

				if (($credenciamento <> 'DESCREDENCIADO' AND $login_fabrica == 3 AND $os == "t") OR $login_fabrica <> 3  OR $os <> "t") {
					$onclick = "window.parent.retorna_posto_call('$codigo_posto', '$nome', '$fone'); window.parent.Shadowbox.close();";
				}

				if ($credenciamento == 'DESCREDENCIADO' AND $login_fabrica == 3 AND $os == 't') {
					$descredenciado = "<b style='color: #ff0000;'>DESCREDENCIADO</b>";
				}

				echo "<tr style='background-color: $cor' onclick=\"$onclick\">
						<td>
							$cnpj
						</td>
						<td>
							$nome $descredenciado
						</td>
						<td>
							$cidade
						</td>
						<td>
							$estado
						</td>
					</tr>";
			}

			echo "</table>";
		}
		else
		{
			echo "<div class='lp_msg_erro'>$msg_erro</div>";
		}

		?>

	</body>

</html>