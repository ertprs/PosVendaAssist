<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "funcoes.php";

$admin_privilegios = "cadastros";
include 'autentica_admin.php';

$msg_erro = "";

if(strlen($_GET['posto']) > 0){

	$sql = "SELECT tbl_posto_fabrica.codigo_posto,tbl_posto.nome
			FROM tbl_posto_fabrica
			JOIN tbl_posto USING(posto)
			WHERE tbl_posto_fabrica.posto = '$posto'
			AND tbl_posto_fabrica.fabrica = $login_fabrica";
	$res = pg_query($con,$sql);
	if(pg_num_rows($res)>0){
		$codigo_posto = pg_fetch_result($res,0,'codigo_posto');
		$posto_nome = pg_fetch_result($res,0,'nome');
	}

}

//AUTO COMPLETE DO BANCO
$q = strtolower($_GET["q"]);
if (isset($_GET["q"])){


	if (strlen($q)>1){
		
		$sql = "
			SELECT codigo,nome,banco 
			
			FROM tbl_banco 
			
			WHERE nome ilike '%$q%' 
		";
		$res = pg_query ($con,$sql);
		
		if (pg_num_rows($res)>0){
			for ($i = 0; $i < pg_num_rows($res); $i++)
			{
				$codigo = trim(pg_result($res,$i,'codigo'));
				$nome 	= trim(pg_result($res,$i,'nome'));
				$banco  = trim(pg_result($res,$i,'banco'));
				
				echo "$codigo|$nome|$banco\n";
			}
		}
		
	}
	
	exit;
}

$btn_acao = !empty($btn_acao) ? $btn_acao : $_GET['btn_acao'];
if ((strlen($btn_acao)>0 && $btn_acao != "gravar")){

	$codigo_posto = ($_POST['codigo_posto']) ? $_POST['codigo_posto'] : null;
	$posto_nome   = ($_POST['posto_nome']) ? $_POST['posto_nome'] : null;
	
	if(!$codigo_posto){
		$codigo_posto = ($_GET['codigo_posto']) ? $_GET['codigo_posto'] : null;
		$posto_nome = ' ';
	}

	if (strlen($codigo_posto)==0 || strlen($posto_nome)==0){	
		$msg_erro = "Escolha o Posto que deseja Pesquisar"; 
	}else{
		$sql = "SELECT tbl_posto_fabrica.posto,tbl_posto.nome
				FROM tbl_posto_fabrica
				JOIN tbl_posto USING(posto)
				WHERE tbl_posto_fabrica.codigo_posto = '$codigo_posto'
				AND tbl_posto_fabrica.fabrica = $login_fabrica";
		$res = pg_query($con,$sql);
		if(pg_num_rows($res)>0){
			$posto = pg_fetch_result($res,0,'posto');
			$posto_nome = pg_fetch_result($res,0,'nome');
		}else{
			$msg_erro = "Posto não encontrado";
		}
	
	}

}elseif(strlen($btn_acao)>0 && $btn_acao == "gravar"){

	$xagencia          = trim($_POST['agencia']);
	$xconta            = trim($_POST['conta']);
	$xfavorecido_conta = trim($_POST['favorecido_conta']);
	$xtipo_conta       = trim($_POST['tipo_conta']);
	$xbanco            = $_POST['banco'];
	$xbanco_nome       = trim($_POST['banco_nome']);
	$cpf_conta         = $_POST['cpf_conta'];
	$ativo             = $_POST['ativo'];
	
	$msg_erro = ( empty($cpf_conta) ) 			? "Preencha o campo 'CPF'" 				: $msg_erro ;
	$msg_erro = ( empty($xfavorecido_conta) ) 	? "Preencha o campo 'Nome Favorecido'" 	: $msg_erro ;
	$msg_erro = ( empty($xbanco) ) 				? "Preencha o campo 'Banco'" 			: $msg_erro ;
	$msg_erro = ( empty($xtipo_conta) ) 		? "Preencha o campo 'Tipo Conta'" 		: $msg_erro ;
	$msg_erro = ( empty($xagencia) ) 			? "Preencha o campo 'Agência'"		 	: $msg_erro ;
	$msg_erro = ( empty($xconta) ) 				? "Preencha o campo 'Conta'" 	 		: $msg_erro ;

	$res = pg_query($con,'BEGIN TRANSACTION');
	
	//confere se o banco digitado existe
	$sqlB = "
					
		SELECT banco 
		
		FROM tbl_banco
		
		where trim(nome) = trim('$xbanco_nome');
	";
	$resB = pg_query($con,$sqlB);
	
	if (pg_num_rows($resB)==0){
		$msg_erro = "O banco digitado não existe, pesquise novamente";
	}
	
	if ( $ativo == 's' and empty($msg_erro) ){
		
		$ativo = "true";
		
		$xagencia          = "'".$xagencia."'";
		$xconta            = "'".$xconta."'";
		$xfavorecido_conta = "'".$xfavorecido_conta."'";
		$xtipo_conta       = "'".$xtipo_conta."'";
		$xbanco            = "'".$xbanco."'";
		
		$cpf_conta = str_replace (".","",$cpf_conta);
		$cpf_conta = str_replace ("-","",$cpf_conta);
		$cpf_conta = str_replace (" ","",$cpf_conta);
		$cpf_conta = str_replace ("'","",$cpf_conta);
		
		$msg_erro = (strlen($cpf_conta) < 11) ? "CPF Inválido" : $msg_erro;
		
		$xcpf_conta = (strlen($cpf_conta) > 0) ? "'".$cpf_conta."'" : 'null';

		if(empty($msg_erro)) {
		
			$sql = "SELECT  posto, fabrica 

					FROM 	tbl_posto_fabrica_banco 
				
					WHERE 	fabrica = $login_fabrica 
					AND 	posto   = $posto";
			$res = pg_query($con,$sql);
			
			if (pg_num_rows($res)>0){

				$sql = "UPDATE tbl_posto_fabrica_banco 
				
						SET cpf_favorecido 	= $xcpf_conta,
							favorecido 		= $xfavorecido_conta,
							banco 			= $xbanco,
							agencia 		= $xagencia,
							conta 			= $xconta,
							tipo_conta 		= $xtipo_conta,
							ativo 			= $ativo
				
						WHERE 	fabrica = $login_fabrica
						AND 	posto = $posto";

				$res = pg_query ($con,$sql);

				$msg_erro = pg_errormessage($con);

			}else{
			
				$sql = "INSERT INTO tbl_posto_fabrica_banco ( 
						
							fabrica,
							posto,
							cpf_favorecido,
							favorecido,
							banco,
							agencia,
							conta,
							tipo_conta,
							ativo

						) VALUES (
						
							$login_fabrica,
							$posto,
							$xcpf_conta,
							$xfavorecido_conta,
							$xbanco,
							$xagencia,
							$xconta,
							$xtipo_conta,
							$ativo
					
						)";
				$res = pg_query($con,$sql);
				$msg_erro = pg_errormessage($con);
				
			}

		

		}
		
	}else if (empty($ativo) and empty($msg_erro)){
		
		
		
		$sql = "SELECT  posto, fabrica 

				FROM 	tbl_posto_fabrica_banco 
				
				WHERE 	fabrica = $login_fabrica 
				AND 	posto   = $posto";
		$res = pg_query($con,$sql);
		
		if (pg_num_rows($res)>0){
			
			$sql = "UPDATE tbl_posto_fabrica_banco 
				
					SET ativo = false
				
					WHERE 	fabrica = $login_fabrica
					AND 	posto = $posto";

			$res = pg_query ($con,$sql);

			$msg_erro = pg_errormessage($con);
			
		}else{
		
			$msg_erro = "Marque o campo Ativo para poder gravar o novo registro para este posto";
			
		}
		
	}
	
	if(empty($msg_erro)) {
	
		$res = pg_query($con,'COMMIT TRANSACTION');
		
		header ("Location: $PHP_SELF?ok=ok");
		
	}else{
	
		$res = pg_query($con,'ROLLBACK TRANSACTION');
		
	}
	
}
	
$title       = "CADASTRO DE CONTAS DE POSTOS PESSOAS FISICAS";
$cabecalho   = "CADASTRO DE CONTAS DE POSTOS PESSOAS FISICAS";
$layout_menu = "cadastro";
include 'cabecalho.php';
include "javascript_pesquisas.php"; 
?>
<script language='javascript' src='js/jquery.js'></script>
<script type='text/javascript' src='js/jquery.autocomplete.js'></script>
<link rel="stylesheet" type="text/css" href="js/jquery.autocomplete.css" />
<script type='text/javascript' src='js/jquery.maskedinput.js'></script>
<script language='javascript' src='js/jquery.alphanumeric.js'></script>
<script language='javascript' src='ajax.js'></script>
<script language='javascript' src='ajax_cep.js'></script>
<script type='text/javascript' src='js/jquery.bgiframe.min.js'></script>
<script type='text/javascript' src='js/dimensions.js'></script>
<script language="javascript" src="js/assist.js"></script>
<script language='javascript' src='ajax.js'></script>

<script language='javascript'>
	$(document).ready(function(){
		
		function formatItem(row) {
			return row[0] + " - " + row[1];
		}
		
		$("#banco_nome").autocomplete("<? echo $PHP_SELF.'?busca_banco=nome'; ?>", {
			minChars: 2,
			delay: 150,
			width: 350,
			matchContains: true,
			formatItem: formatItem,
			formatResult: function(row) {return row[0];}
		});

		$("#banco_nome").result(function(event, data, formatted) {
			$("#banco").val(data[2]) ;
			$("#banco_nome").val(data[1]) ;
		});
		
	});

</script>
<style type="text/css">
.text_curto {
	text-align: center;
	font-weight: bold;
	color: #000;
	background-color: #FF6666;
}


.msg_erro{
	background-color:#FF0000;
	font: bold 16px "Arial";
	color:#FFFFFF;
	text-align:center;
}

.sucesso{
    background-color:#008000;
    font: bold 14px "Arial";
    color:#FFFFFF;
    text-align:center;
}


.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	border: 1px solid;
	color:#596d9b;
	background-color: #d9e2ef
}

.border {
	border: 1px solid #ced7e7;
}

.table_line {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	border: 0px solid;
	background-color: #ffffff
}

input {
	font-size: 10px;
}

.top_list {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	color:#596d9b;
	background-color: #d9e2ef
}

.line_list {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: normal;
	color:#596d9b;
	background-color: #ffffff
}

.Titulo {
	text-align: center;
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #596D9B;
}

.Conteudo {
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
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

.msg_erro{
	background-color:#FF0000;
	font: bold 16px "Arial";
	color:#FFFFFF;
	text-align:center;
}

.formulario{
	background-color:#D9E2EF;
	font:11px Arial;
}

table.tabela tr td{
	font-family: verdana;
	font-size: 11px;
	border-collapse: collapse;
	border:1px solid #596d9b;
}

.msg_sucesso{
	background-color: green;
	font: bold 16px "Arial";
	color: #FFFFFF;
	text-align:center;
}

.texto_avulso{
	font: 14px Arial; color: rgb(89, 109, 155) !important;
	background-color: #d9e2ef;
	text-align: center;
	width:700px;
	margin: 0 auto;
	border:1px solid #596d9b;
}

</style>

<?php if(strlen($msg_erro)>0){ ?>
	<table class="msg_erro" align="center" width="700px">
		<tr>
			<td><?php echo $msg_erro; ?></td>
		</tr>
	</table>
<?php } ?>
<?php if(!empty($_GET['ok'])){ ?>
<table class="sucesso" align="center" width="700px">
	<tr>
		<td><?php echo "Gravado com Sucesso"; ?></td>
	</tr>
</table>
<?php } ?>

<form name="frm_posto" method="post" action="<? echo $PHP_SELF ?>">
	<input type="hidden" name="posto" value="<? echo $posto ?>">
	
	<table cellspacing='1' cellpadding='3' align='center' width='700px' class='formulario'>
		<tr>
			<td colspan='3' class='titulo_tabela'>Par&acirc;metros de Pesquisa</td>
		</tr>
		<tr><td>&nbsp;</td></tr>
		<tr>
			<td width='100px'>&nbsp;</td>
			<td width='180px' align="left">
				C&oacute;digo Posto <br />
				<input type='text' name='codigo_posto' size='8' value='<?=$codigo_posto;?>' class='frm'>
				<img border="0" src="imagens/lupa.png" style="cursor: pointer;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="fnc_pesquisa_posto (document.frm_posto.codigo_posto, document.frm_posto.posto_nome, 'codigo')">
			</td>
			<td align="left">
				Nome Posto <br />
				<input type='text' name='posto_nome' size='30' value='<?=$posto_nome;?>' class='frm'>
				<img border="0" src="imagens/lupa.png" style="cursor: pointer;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto (document.frm_posto.codigo_posto, document.frm_posto.posto_nome, 'nome')">
			</td>
		</tr>
		
		<tr><td>&nbsp;</td></tr>
		<tr>
			<td colspan='3' align='center'>
				<input type='submit' name='btn_acao' value='Pesquisar'>
			</td>
		</tr>
		<tr><td>&nbsp;</td></tr>
	</table>
	
</form>
<br>
<br>


<?php 

if($posto > 0){
	
	$sql = "SELECT  cpf_favorecido,
					favorecido,
					banco,
					agencia,
					conta,
					tipo_conta,
					ativo,
					tbl_banco.nome
			FROM tbl_posto_fabrica_banco 
			LEFT JOIN tbl_banco using(banco) 
			WHERE fabrica = $login_fabrica
			AND posto = $posto";

	$res = pg_query ($con,$sql);

	$num = pg_num_rows($res);
	
	if($num > 0){

		$cpf_conta     		= pg_fetch_result($res,0,'cpf_favorecido');
		$favorecido_conta  	= pg_fetch_result($res,0,'favorecido');
		$banco  			= pg_fetch_result($res,0,'banco');
		$agencia  			= pg_fetch_result($res,0,'agencia');
		$conta  			= pg_fetch_result($res,0,'conta');
		$tipo_conta  		= pg_fetch_result($res,0,'tipo_conta');
		$ativo              = pg_fetch_result($res,0,'ativo');
		$banco_nome         = pg_fetch_result($res,0,'nome');

	}		
		?>	
		<form name="frm_gravar" method="post" action="<? echo $PHP_SELF ?>?posto=<?=$posto;?>">
		<input type="hidden" name="posto" value="<?=$posto;?>">
		
		<table class="formulario" width='700' align='center' border='0' cellpadding="1" cellspacing="3">
			
			<tr>
				<td colspan='4' class='titulo_tabela'>

					Informa&ccedil;&otilde;es Banc&aacute;rias

				</td>
			</tr>
			<tr>
				<td colspan="4" class="texto_avulso"> Preecha o campo "Tipo de Conta" corretamente. <br /> "Física" ou "Poupança" </td>
			</tr>

			<tr>

				<td width="33%" align="left" colspan="4"> 
					Ativo &nbsp;

					<?
					$checked = ($ativo == 't') ? "CHECKED" : null;
					?>
					<input type="checkbox" name="ativo" value="s" <?echo $checked?> >
				</td>

			</tr>

			<tr align='left'>

				<td width = '33%'>CPF Favorecido</td>

				<td colspan=3>Nome Favorecido</td>

			</tr>

			<tr align='left'>

				<td width = '33%'>

					<input class='frm' type="text" name="cpf_conta" size="14" maxlength="14" value="<? echo $cpf_conta ?>">

				</td>

				<td colspan=3>

					<input class='frm' type="text" name="favorecido_conta" size="60" maxlength="50" value="<? echo $favorecido_conta ?>">

				</td>

			</tr>

			<tr  align='left'>

				<td colspan='4' width = '100%'>Banco</td>

			</tr>

			<tr align='left'>

				<td colspan='4'>
					<input type="hidden" name="banco" id="banco" value="<?echo $banco?>" />
					<input type="text" name="banco_nome" id="banco_nome" class="frm" value="<?=$banco_nome?>" style="width:75%" />
				</td>
			</tr>

			<tr  align='left'>
			
				<td width = '33%'>Tipo de Conta</td>

				<td width = '33%'>Ag&ecirc;ncia</td>

				<td width = '34%'>Conta</td>

			</tr>

			<tr align='left'>

				<td width='33%' align="left">

					<input  class='frm' type="text" name="tipo_conta" size="10" maxlength="10" value="<? echo $tipo_conta ?>">
					
				
				</td>
				
				<td width = '33%'>

					<input  class='frm' type="text" name="agencia" size="10" maxlength="10" value="<? echo $agencia ?>">

				</td>
				
				<td width = '34%'>
				
					<input class='frm' type="text" name="conta" size="15" maxlength="15" value="<? echo $conta ?>">
				
				</td>
				
			</tr>

			<tr>
			
				<td colspan="4">&nbsp;</td>
			
			</tr>
			
			<tr>
			
				<td colspan="4">
				
					<input type="hidden" name="btn_acao" value="" />

					<input type="button" value="Gravar" onclick="if(document.frm_gravar.btn_acao.value==''){document.frm_gravar.btn_acao.value='gravar';document.frm_gravar.submit()}else{alert('Aguarde submissão');}" alt="Gravar formulário" border='0'>

					<input type="button" value="Listar Todos os postos" onclick="window.location.href='<?=$PHP_SELF;?>'" alt="Listar Todos" border='0'>

				</td>

			</tr>

			<tr>
			
				<td colspan="4">&nbsp;</td>
			
			</tr>

		</table>

		</form>

	<br />

<?}else{?>

	<br>
	<table align="center" width="700" cellspacing="1" class="tabela">
		<?php 
		$sql = "SELECT tbl_posto_fabrica_banco.fabrica
				FROM tbl_posto_fabrica_banco 
				WHERE tbl_posto_fabrica_banco.fabrica = $login_fabrica";
		$res = pg_query ($con,$sql);
		$totalPostos = pg_numrows($res);
	
		if($totalPostos > 0){
			?>

			<tr class="titulo_coluna">
				<td>Nome Posto</td>
				<td>C&oacute;digo Posto</td>
				<td>Favorecido</td>
				<td>Banco</td>
				<td>Tipo Conta</td>
				<td>Ativo</td>
			
			</tr>
			<?php
			$sql2 = "SELECT tbl_posto_fabrica.posto,
							tbl_posto_fabrica.codigo_posto,
							tbl_posto.nome,
							tbl_banco.nome as nome_banco,
							tbl_banco.codigo,
							tbl_posto_fabrica_banco.tipo_conta,
							tbl_posto_fabrica_banco.ativo,
							tbl_posto_fabrica_banco.favorecido as favorecido_conta
					FROM tbl_posto_fabrica 
					JOIN tbl_posto USING(posto) 
					JOIN tbl_posto_fabrica_banco on (tbl_posto_fabrica.fabrica = tbl_posto_fabrica_banco.fabrica and tbl_posto_fabrica.posto = tbl_posto_fabrica_banco.posto )
					JOIN tbl_banco ON tbl_banco.banco = tbl_posto_fabrica_banco.banco
					WHERE tbl_posto_fabrica.fabrica = $login_fabrica 
					AND   (tbl_posto_fabrica.credenciamento = 'CREDENCIADO' OR tbl_posto_fabrica.credenciamento =  'EM DESCREDENCIAMENTO')";

			$res2 = pg_query ($con,$sql2);
		
			for($i=0;$i<pg_numrows($res2);$i++){
				
				$codigo 			= pg_fetch_result($res2,$i,'codigo_posto');
				$posto_id 			= pg_fetch_result($res2,$i,'posto');
				$nome 				= pg_fetch_result($res2,$i,'nome');
				$banco 				= pg_fetch_result($res2,$i,'nome_banco');
				$tipo_conta 		= pg_fetch_result($res2,$i,'tipo_conta');
				$favorecido_conta	= pg_fetch_result($res2,$i,'favorecido_conta');
				$ativo				= pg_fetch_result($res2,$i,'ativo');
				
				if ($ativo == 't'){
					$img_ativo_inativo = "<img src=\"imagens/ativo.png\" />";
				}else{
					$img_ativo_inativo = "<img src=\"imagens/inativo.png\" />";
				}
				
				$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";
				?>
				<tr bgcolor='<?=$cor;?>'>
				    <td><a href="<?=$PHP_SELF;?>?posto=<?=$posto_id;?>"><?=$nome;?></a></td>
				    <td><a href="<?=$PHP_SELF;?>?posto=<?=$posto_id;?>"><?=$codigo;?></a></td>
					<td><a href="<?=$PHP_SELF;?>?posto=<?=$posto_id;?>"><?=$favorecido_conta;?></a></td>
				    <td><a href="<?=$PHP_SELF;?>?posto=<?=$posto_id;?>"><?=$banco;?></a></td>
			    	<td><a href="<?=$PHP_SELF;?>?posto=<?=$posto_id;?>"><?=$tipo_conta;?></a></td>
			    	<td><a href="<?=$PHP_SELF;?>?posto=<?=$posto_id;?>"><?=$img_ativo_inativo;?></a></td>
				</tr>
				<?php
			}
			?>
		<?}else{?>
			<tr>
				<td>Nenhum posto cadastrado</td>
			</tr>
		<?}?>
	</table>
<?}

include "rodape.php"; 
?>
