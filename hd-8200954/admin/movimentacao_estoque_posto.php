<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
include 'funcoes.php';

$layout_menu = "cadastro";
$titulo = "MANUTENÇÃO DE ESTOQUE DO POSTO";
$title = "MANUTENÇÃO DE ESTOQUE DO POSTO";
include 'cabecalho.php';
include "javascript_pesquisas.php"; 

$btn_acao = $_POST['btn_acao'];
if(!empty($btn_acao)){
	$posto1 = $_POST['posto_codigo1'];
	$posto_nome1 = $_POST['posto_nome1'];
	$posto2 = $_POST['posto_codigo2'];
	$posto_nome2 = $_POST['posto_nome2'];

	if(empty($posto1) OR empty($posto2)){
		$msg_erro = "Informe os dois Postos Autorizado";
	}

	if(empty($msg_erro)){
		$sql = "SELECT posto FROM tbl_posto_fabrica WHERE fabrica = $login_fabrica AND codigo_posto = '$posto2'";
		$res = pg_query($con,$sql);
	
		if(pg_numrows($res) > 0){
			$posto = pg_result($res,0,posto);

			$sql = "SELECT tbl_estoque_posto.posto,peca, qtde 
					FROM tbl_estoque_posto 
					JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_estoque_posto.posto AND tbl_posto_fabrica.fabrica = $login_fabrica 
					WHERE tbl_estoque_posto.fabrica = $login_fabrica 
					AND tbl_posto_fabrica.codigo_posto = '$posto1'";
			$res = pg_query($con,$sql);
			
			if(pg_numrows($res) > 0){
				for($i = 0; $i < pg_numrows($res); $i++){
					$peca = pg_result($res,$i,peca);
					$qtde = pg_result($res,$i,qtde);
					$posto_aux = pg_result($res,$i,posto);
					
					if($qtde > 0){
						$sqlS = "SELECT peca FROM tbl_estoque_posto WHERE fabrica = $login_fabrica AND posto = $posto AND peca = $peca";
						$resS = pg_query($con,$sqlS);
						
						if(pg_numrows($resS) > 0){
							$sqlU = "UPDATE tbl_estoque_posto SET qtde = qtde + $qtde 
										WHERE fabrica = $login_fabrica 
										AND posto = $posto 
										AND peca = $peca";
						} else {
							$sqlU = "INSERT INTO tbl_estoque_posto(
																	fabrica,
																	posto,
																	peca,
																	qtde
																	) VALUES (
																	$login_fabrica,
																	$posto,
																	$peca,
																	$qtde
																	)";
						}
						
						$resU = pg_query($con,$sqlU);

						$sqlI = "INSERT INTO tbl_estoque_posto_movimento(
																		fabrica,
																		posto, 
																		peca, 
																		data, 
																		qtde_entrada, 
																		admin, 
																		obs
																		) VALUES (
																		$login_fabrica,
																		$posto,
																		$peca,
																		current_date,
																		$qtde,
																		$login_admin,
																		'Saldo transferido do estoque do posto $posto1 - $posto_nome1'
																		)";
						$resI = pg_query($con,$sqlI);

						$sqlT = "UPDATE tbl_estoque_posto SET qtde = 0 WHERE posto = $posto_aux AND peca = $peca";
						$resT = pg_query($con,$sqlT);
					}
				}
			}
		}

		$msg_erro = pg_last_error($con);
		
	}

	if(empty($msg_erro)){
		$msg = "Transferido com Sucesso!";
	}
}

?>
<script language="javascript" src="js/assist.js"></script>
<script language='javascript' src='ajax.js'></script>
<script type="text/javascript" src="js/jquery.js"></script>
<link rel="stylesheet" href="js/thickbox.css" type="text/css" media="screen" />
<link rel="stylesheet" type="text/css" href="../plugins/shadowbox/shadowbox.css" media="all">
<script src="../plugins/shadowbox/shadowbox.js" type="text/javascript"></script>

<script type="text/javascript">

	$().ready(function(){
	
		Shadowbox.init();
		
	});
	
//PESQUISA POSTO - 

	function pesquisaPosto(campo,tipo,num_posto){
		var campo = campo.value;

		if (jQuery.trim(campo).length > 2){
			Shadowbox.open({
				content:	"posto_pesquisa_2_nv.php?"+tipo+"="+campo+"&tipo="+tipo+"&num_posto="+num_posto,
				player:	"iframe",
				title:		"Pesquisa Posto",
				width:	800,
				height:	500
			});
		}else
			alert("Informar toda ou parte da informação para realizar a pesquisa!");
	}
		
	function retorna_posto(codigo_posto,posto,nome,cnpj,cidade,estado,credenciamento,num_posto,cep,endereco,numero,bairro){
		if(num_posto == 1){
			gravaDados('posto_codigo1',codigo_posto);
			gravaDados('posto_nome1',nome);
		} else {
			gravaDados('posto_codigo2',codigo_posto);
			gravaDados('posto_nome2',nome);
		}
	}

	function gravaDados(name, valor){
		try{
			$("#"+name).val(valor);
		} catch(err){
			return false;
		}
	}

</script>

<style type="text/css">
.subtitulo{
	background-color: #7092BE;
	font:bold 11px Arial;
	color: #FFFFFF;
	text-align:center;
}

.titulo_tabela{
	background-color:#596d9b;
	font: bold 14px "Arial";
	color:#FFFFFF;
	text-align:center;
}

.sucesso{
	background-color:#008000;
	font: bold 14px "Arial";
	color:#FFFFFF;
	text-align:center;
}

.formulario{
	background-color:#D9E2EF;
	font:11px Arial;
	text-align:left;
}

.msg_erro{
	background-color:#FF0000;
	font: bold 16px "Arial";
	color:#FFFFFF;
	text-align:center;
}
</style>
<center>
<?php if(!empty($msg_erro)){ ?>
		<table width="700" align="center" class="msg_erro">
			<tr><td><?php echo $msg_erro; ?></td></tr>
		</table>
<?php  } ?>

<?php if(!empty($msg)){ ?>
		<table width="700" align="center" class="sucesso">
			<tr><td><?php echo $msg; ?></td></tr>
		</table>
<?php  } ?>

<form name="frm_consulta" method="post">
	<table width="700" align="center" class="formulario">
		<caption class="titulo_tabela">Manutenção de estoque DE -> PARA </caption>
		
		<tr><td colspan="3">&nbsp;</td></tr>
		
		<tr class="subtitulo">
			<td colspan="3">Posto Atual</td>
		</tr>

		<tr>
			<td width="50">&nbsp;</td>
			<td>
				Código Posto <br /> 
				<input type="text" name="posto_codigo1" id="posto_codigo1" size="15" class="frm" value="<?php echo $posto1;?>">
				<img border="0" src="imagens/lupa.png" style="cursor: pointer;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: pesquisaPosto (document.frm_consulta.posto_codigo1, 'codigo','1')">
			</td>
			<td>
				Nome Posto <br /> 
				<input type="text" name="posto_nome1" id="posto_nome1" size="45" class="frm" value="<?php echo $posto_nome1;?>">
				<img border="0" src="imagens/lupa.png" style="cursor: pointer;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: pesquisaPosto (document.frm_consulta.posto_nome1, 'nome', '1')">
			</td>
		</tr>

		<tr><td colspan="3">&nbsp;</td></tr>
		
		<tr class="subtitulo">
			<td colspan="3">Novo Posto</td>
		</tr>

		<tr>
			<td width="50">&nbsp;</td>
			<td>
				Código Posto <br /> 
				<input type="text" name="posto_codigo2" id="posto_codigo2" size="15" class="frm" value="<?php echo $posto2;?>">
				<img border="0" src="imagens/lupa.png" style="cursor: pointer;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: pesquisaPosto (document.frm_consulta.posto_codigo2, 'codigo','2')">
			</td>
			<td>
				Nome Posto <br /> 
				<input type="text" name="posto_nome2" id="posto_nome2" size="45" class="frm" value="<?php echo $posto_nome2;?>">
				<img border="0" src="imagens/lupa.png" style="cursor: pointer;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: pesquisaPosto (document.frm_consulta.posto_nome2, 'nome', '2')">
			</td>
		</tr>
		
		<tr><td colspan="3">&nbsp;</td></tr>

		<tr>
			<td colspan='3' align='center'>
				<input type="hidden" name="btn_acao" value="transferir">
				<input type="button" value="Transferir" onclick="javascript: if(confirm('Deseja Transferir o estoque do posto atual para o novo posto?')){document.frm_consulta.submit();}">
			</td>
		</tr>

		<tr><td colspan="3">&nbsp;</td></tr>
	</table>
</form>

<?php
	include "rodape.php";
?>
