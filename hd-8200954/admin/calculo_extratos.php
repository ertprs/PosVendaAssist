<?
	include "dbconfig.php";
	include "includes/dbconnect-inc.php";
	include 'autentica_admin.php';

	if(isset($_POST["ajax_extrato"]) && $_POST["ajax_extrato"]){
		$extratos	= trim($_REQUEST["extrato"]);

		$sql = "SELECT 
				SUM(tbl_os.pecas + tbl_os.mao_de_obra) AS valor_total
			FROM tbl_extrato
			 	JOIN tbl_os_extra ON tbl_os_extra.extrato = tbl_extrato.extrato AND tbl_extrato.fabrica = $login_fabrica
			 	JOIN tbl_os ON tbl_os.os = tbl_os_extra.os AND tbl_os.fabrica = $login_fabrica
			WHERE tbl_extrato.extrato IN ($extratos);";
		$res = pg_query($con,$sql);

		if(pg_num_rows($res) > 0){
			$valor_total = pg_fetch_result($res,0,valor_total);

			$sqlE = "SELECT tbl_linha.nome,
					tbl_familia.descricao,
					COUNT(tbl_os_extra.os) AS qtde,
					SUM(tbl_os.pecas + tbl_os.mao_de_obra) AS valor
				FROM tbl_extrato
					JOIN tbl_os_extra ON tbl_os_extra.extrato = tbl_extrato.extrato AND tbl_extrato.fabrica = $login_fabrica
					JOIN tbl_os ON tbl_os.os = tbl_os_extra.os AND tbl_os.fabrica = $login_fabrica
					JOIN tbl_produto ON tbl_produto.produto = tbl_os.produto AND tbl_produto.fabrica_i = $login_fabrica
					JOIN tbl_linha ON tbl_linha.linha = tbl_produto.linha AND tbl_linha.fabrica = $login_fabrica
					JOIN tbl_familia ON tbl_familia.familia = tbl_produto.familia AND tbl_familia.fabrica = $login_fabrica
				WHERE tbl_extrato.extrato IN ($extratos)
				GROUP BY tbl_linha.nome,tbl_familia.descricao";
			$resE = pg_query($con,$sqlE);

			if(pg_num_rows($resE) > 0){
				$i     = 0;
				$linha = "";

				while($objeto_extrato = pg_fetch_object($resE)){
					$i++;
					$qtde    = $objeto_extrato->qtde;
					$valor   = $objeto_extrato->valor;

					$porcentagem = ($valor * 100) / $valor_total;

					$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";
					$linha .= "<tr bgcolor='".$cor."'>";
					$linha .= "<td>".utf8_encode($objeto_extrato->nome)."</td>";
					$linha .= "<td>".utf8_encode($objeto_extrato->descricao)."</td>";
					$linha .= "<td align='center'>".$qtde."</td>";
					$linha .= "<td align='right'>".number_format($valor,2,',','.')."</td>";
					$linha .= "<td align='right'>".number_format($porcentagem,2,',','.')."</td>";
					$linha .= "</tr>";

				}

				if(empty($linha)){
					$resultado = array(
						"success" => false,
						"mensagem" => utf8_encode("Erro ao calcular o valor do extrato ".$extratos)
					);
				}else{
					$resultado = array(
						"success" => true,
						"extrato" => $linha
					);
				}
			}else{
				$resultado = array(
					"success"  => false,
					"mensagem" => utf8_encode("Erro ao buscar informações do extrato ".$extratos)
				);
			}
		}else{
			$resultado = array(
				"success"  => false,
				"mensagem" => utf8_encode("Não foi possível calcular o valor total do extrato ".$extratos)
			);
		}

		echo json_encode($resultado); exit;
	}
?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
	<head>
		<meta http-equiv=pragma content=no-cache>
		<style type="text/css" media="all">
			body {
				margin: 0;
				font-family: Arial, Verdana, Times, Sans;
				background: #fff;
			}

			table.tabela tr td{
				font-family: verdana;
				font-size: 11px;
				border-collapse: collapse;
				border:1px solid #596d9b;
			}

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
		</style>

	</head>
	<link href="../bootstrap/css/bootstrap.css" type="text/css" rel="stylesheet" />
	<link href="../bootstrap/css/extra.css" type="text/css" rel="stylesheet" />
	<link href="../css/tc_css.css" type="text/css" rel="stylesheet" />
	<link href="../plugins/posvenda_jquery_ui/css/posvenda_ui/jquery-ui-1.10.3.custom.css" type="text/css" rel="stylesheet" >
	<link href="../bootstrap/css/ajuste.css" type="text/css" rel="stylesheet" />

	<script src="../plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
	<script src="../plugins/posvenda_jquery_ui/js/jquery-ui-1.10.3.custom.js"></script>
	<script src="../bootstrap/js/bootstrap.js"></script>
	<script type="text/javascript">
		$(document).ready(function(){
			var extrato = window.parent.getExtrato();

			extrato = extrato.split(",");
			$("#mensagem").removeClass("alert-info alert-error").html("");
			$("#mensagem_success").hide();
			$("#mensagem").html("");
			
			var erro = false;

			$.each(extrato,function(key, value){
				$("#mensagem").addClass("alert-info").html("<h4>Carregando...</h4>");
				$.ajax({
	                url:"calculo_extratos.php",
	                type:"POST",
	                dataType:"json",
	                async: false,
	                data:{
	                    extrato 	 : value,
	                    ajax_extrato : true
	                }
	            })
	            .done(function(data){
	                if(data.success){
	                	$("#tabela_calcula_extrato").show();
	                	$("#tabela_calcula_extrato").append(data.extrato);
	                }else{
	                	$("#mensagem").removeClass("alert-info");
	                	$("#mensagem").addClass("alert-error");
	                    $("#mensagem").html('<h4>'+data.mensagem+'</h4>');
	                    erro = true;
	                }
	            });

	            if(erro){
	            	return false;
	            }
			});

			if(!erro){
				$("#mensagem").hide();
				$("#mensagem_success").show();
			}
		});
	</script>
	<body>
		</br>
		<div id="mensagem"></div>
		<div id="mensagem_success" class="alert-success" style="display:none"><h4>Concluído</h4></div>
		</br>
		<table align="center" width="700" class="tabela" id="tabela_calcula_extrato" cellpadding='2' cellspacing='1' style="display:none">
			<caption class="titulo_coluna">Cálculo das OS dos Extratos Selecionados</caption>
			<tr class="titulo_coluna">
				<th>Linha</th>
				<th>Família</th>
				<th>Qtde. OS</th>
				<th>Valor</th>
				<th>%</th>
			</tr>
		</table>
		</br>
	</body>
</html>