<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios = "cadastros";

include 'autentica_admin.php';

include 'funcoes.php';

/**
 * Instacia a API
 */
$api = new Api();
$api->login_fabrica = $login_fabrica;


// seleciona as configurações da fábrica
$api->uri = 'fabricas/id/'.$login_fabrica;
$response = $api->GET();
$pedido_via_distribuidor = $response[0]->pedido_via_distribuidor;


$msg_erro = array();
$msg_ok   = array();

if (strlen($_GET["familia"]) > 0)    $familia   = trim($_GET["familia"]);
if (strlen($_POST["familia"]) > 0)   $familia   = trim($_POST["familia"]);
if (strlen($_POST["btnacao"]) > 0)   $btnacao   = trim($_POST["btnacao"]);
if (strlen($_POST["bosch_cfa"]) > 0) $bosch_cfa = trim($_POST["bosch_cfa"]);


if ($btnacao == "deletar" and strlen($familia) > 0) {
	// exclui a familia
	$api->uri = 'familias/id/'.$familia;
	$response = $api->DELETE();
	
	if (isset($response->error)) {
		$msg_erro[] = $response->error->message;
	}
	else {
		header ("Location: $PHP_SELF");
		exit;
	}
}

if ($btnacao == "gravar") {
	if (strlen($_POST["descricao"]) == 0) {
		$msg_erro[] = "Informe a descrição da familia.";
	}
	
	if(!empty($valor_mao_de_obra) AND !empty($hora_tecnica_pta)) {
		$msg_erro[] = "Digite o valor de hora técnica ou valor de M.O. (Um ou outro. Não é permitido valor para os dois campos simultaneamente).";
	}

	if(strlen($familia) == 0 and $codigo_familia <> 'null' and strlen($codigo_familia) > 0){
		// verifica se codigo_fabrica digitado já existe
		$api->uri = 'familias?codigo_familia='.$codigo_familia;
		$response = $api->GET();
		//$response = $api->HEAD();
		if($response->error){
			$msg_erro[] = "Código $codigo_familia já foi cadastrado anteriormente.";
		}
	}
	
	if(count($msg_erro) == 0) {
		$codigo_familia         			= $_POST["codigo_familia"];
		$descricao              			= $_POST["descricao"];
		$ativo              				= (!empty($_POST["ativo"])) ? 't' : 'f';
		$mao_de_obra_adicional_distribuidor = (!empty($_POST["mao_de_obra_adicional_distribuidor"])) ? fnc_limpa_moeda($_POST["mao_de_obra_adicional_distribuidor"]) : NULL;
		$paga_km                  			= (!empty($_POST["paga_km"]))  ? $_POST["paga_km"] : NULL; //HD 275256 - gabrielSilva

		$api->dados = array(
			'descricao'								=> $descricao,
			'codigo_familia'						=> $codigo_familia,
			'ativo'									=> $ativo,
			'mao_de_obra_adicional_distribuidor'	=> $mao_de_obra_adicional_distribuidor,
			'paga_km'								=> $paga_km
		);
		
		if (strlen($familia) == 0) {
			// salva os dados da familia
			$api->uri = 'familias';
			$response = $api->POST();
		
			if($response->error){
				$msg_erro[] = "Não foi possível cadastrar a família. Verifique os erros:";
				$msg_erro[] = $response->error;
			} 
			else {
				$familia = $response->id;
				$msg_ok[] = "Família cadastrada com sucesso!";
			}
		}
		else {
			// atualiza dados da familia
			$api->uri = 'familias/id/'.$familia;
			$response = $api->PUT();
print_r($response); exit;
			if($response->error){
				$msg_erro[] = "Não foi possível alterar a família. Verifique os erros:";
				$msg_erro[] = $response->error;
			}
			else {
				$msg_ok[] = "Família alterada com sucesso!";
			}
		}
		
		if ($login_fabrica == 7 AND count($msg_erro) == 0) {
			// seleciona dados dos valores da familia 
			$api->uri = 'familia_valores/id/'.$familia;
			$response = $api->GET();
			//$response = $api->HEAD();
			
			$taxa_visita              = (!empty($_POST["taxa_visita"])) 				? fnc_limpa_moeda($_POST["taxa_visita"]) 				: NULL;
			$hora_tecnica             = (!empty($_POST["hora_tecnica"])) 				? fnc_limpa_moeda($_POST["hora_tecnica"]) 				: NULL;
			$hora_tecnica_pta         = (!empty($_POST["hora_tecnica_pta"])) 			? fnc_limpa_moeda($_POST["hora_tecnica_pta"]) 			: NULL;
			$valor_diaria             = (!empty($_POST["valor_diaria"])) 				? fnc_limpa_moeda($_POST["valor_diaria"]) 				: NULL;
			$valor_por_km_caminhao    = (!empty($_POST["valor_por_km_caminhao"])) 		? fnc_limpa_moeda($_POST["valor_por_km_caminhao"]) 		: NULL;
			$valor_por_km_carro       = (!empty($_POST["valor_por_km_carro"])) 			? fnc_limpa_moeda($_POST["valor_por_km_carro"]) 		: NULL;
			$regulagem_peso_padrao    = (!empty($_POST["regulagem_peso_padrao"])) 		? fnc_limpa_moeda($_POST["regulagem_peso_padrao"]) 		: NULL;
			$certificado_conformidade = (!empty($_POST["certificado_conformidade"])) 	? fnc_limpa_moeda($_POST["certificado_conformidade"]) 	: NULL;
			$valor_mao_de_obra        = (!empty($_POST["valor_mao_de_obra"])) 			? fnc_limpa_moeda($_POST["valor_mao_de_obra"]) 			: NULL;

			$api->dados = array(
					'taxa_visita'             	=> $taxa_visita,
					'hora_tecnica'            	=> $hora_tecnica,
					'hora_tecnica_pta'        	=> $hora_tecnica_pta,
					'valor_diaria'            	=> $valor_diaria,
					'valor_por_km_caminhao'   	=> $valor_por_km_caminhao,
					'valor_por_km_carro'      	=> $valor_por_km_carro,
					'regulagem_peso_padrao'   	=> $regulagem_peso_padrao,
					'certificado_conformidade'	=> $certificado_conformidade,
					'valor_mao_de_obra'       	=> $valor_mao_de_obra       
			);
			
			if($response->existe) {
				// altera dados de valores
				$response = $api->PUT();
				
				if($response->error) {
					$msg_erro[] = "Não foi possível alterar os valores da família. Verifique os erros:";
					$msg_erro[] = $response->error;
				}
				else {
					$msg_ok[] = "Valores da família alterados com sucesso!";
				}
			} 
			else {
				// grava novos valores
				$api->uri = 'familia_valores/id/'.$familia;
				$response = $api->POST();
				
				if($response->error){
					$msg_erro[] = "Não foi possível cadastrar os valores da família. Verifique os erros:";
					$msg_erro[] = $response->error;
				} 
				else {
					$familia = $response->id;
					$msg_ok[] = "Valores da família cadastrados com sucesso!";
				}
			}
		}
		
		$qtde_item = $_POST['qtde_item'];
		
		for ($i=0; $i < $qtde_item; $i++) {
			$defeito_constatado         = $_POST['defeito_constatado_' . $i];
			$familia_defeito_constatado = $_POST['familia_defeito_constatado_' . $i];

			if(strlen($familia_defeito_constatado) > 0 AND strlen($defeito_constatado) == 0) {
				// exclui relacionamento entre defeito constatado e familia
				$api->uri = 'familia_defeito_constatados/id/'.$familia_defeito_constatado;
				$response = $api->DELETE();

				if($response->error){
					$msg_erro[] = "Não foi possível excluir a relação entre o defeito constatado e a família.";
					$msg_erro[] = $response->error;
				}
			}

			if (count($msg_erro) == 0 AND strlen($defeito_constatado) > 0) {

				if (strlen($familia_defeito_constatado) == 0) {
					// salva a relacão
					$api->dados = array(
							'defeito_constatado'    => $defeito_constatado,
							'familia'            	=> $familia
					);
					$api->uri = 'familia_defeito_constatados';
					$response = $api->POST();
					
					if($response->error){
						$msg_erro[] = "Não foi possível relacionar o defeito constatado à família.";
						$msg_erro[] = $response->error;
					}
				}
			}
		}
	}

	if (count($msg_erro) == 0) {
		header ("Location: $PHP_SELF");
		exit;
	}
	else {
		$codigo_familia    					= $_POST["codigo_familia"];
		$descricao         					= $_POST["descricao"];
		$ativo             					= $_POST["ativo"];
		$mao_de_obra_adicional_distribuidor = $_POST['mao_de_obra_adicional_distribuidor'];
	}
}


###CARREGA REGISTRO
if (strlen($familia) > 0) {
	// dados da familia
	$api->uri = 'familias/id/'.$familia;
	$responseFamilia = $api->GET();

	if($responseFamilia->error){
			$msg_erro[] = "Família não encontrada.";
	}
	else {
		$codigo_familia 					= $responseFamilia[0]->codigo_familia;
		$descricao 							= $responseFamilia[0]->descricao;
		$ativo 								= $responseFamilia[0]->ativo;
		$mao_de_obra_adicional_distribuidor	= $responseFamilia[0]->mao_de_obra_adicional_distribuidor;

		// valores da familia (fabrica = 7)
		$api->uri = 'familia_valores/id/'.$familia;
		$responseFamiliaValores = $api->GET();

		// relação entre família e defeitos constatados
		$api->uri = 'familia_defeitos_constatados?familia='.$familia;
		$responseFamiliaDefeitosConstatados = $api->GET();
		
		// produtos que pertencem à família
		//$api->uri = 'produtos?familia='.$familia;
		//$responseProdutoFamilia = $api->GET();
		$responseProdutoFamilia = null;
	}
}

// familias da fábrica
$api->uri = 'familias';
$responseFamilias = $api->GET();

// defeitos constatados da fábrica
$api->uri = 'defeito_constatados';
$responseDefeitosConstatados = $api->GET();

$layout_menu = "cadastro";
$title 		 = "CADASTRO DE FAMÍLIAS DOS PRODUTOS";

if(!isset($semcab))
	include 'cabecalho.php';
?>

<style type="text/css">
body{
	font-size: 11px;
}
.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 11px;
	font-weight: bold;
	border: 1px solid;
	color:#596d9b;
	background-color: #d9e2ef
}
.border {
	border: 1px solid #ced7e7;
}
.table_line {
	text-align: left;
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
	background-color: #d9e2ef;
}
.line_list {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: normal;
	color:#596d9b;
	background-color: #ffffff;
}
.Label{
	font-family: Verdana;
	font-size: 10px;
}
.Titulo{
	font-family: Verdana;
	font-size: 12px;
	font-weight: bold;
}
.Conteudo{
	font-family: Verdana;
	font-size: 10px;
}
.Erro{
	font-family: Verdana;
	font-size: 12px;
	color:#FFF;
	border:#485989 1px solid; background-color: #990000;
}
table.tabela tr td{
	font-family: verdana;
	font-size: 11px;
	border-collapse: collapse;
	border:1px solid #596d9b;
}
.titulo_coluna{
	background-color:#596d9b;
	font: bold 11px "Arial";
	color:#FFFFFF;
	text-align:center;
}
.titulo_tabela{
	background-color:#596d9b;
	font: bold 14px "Arial" !important; 
	color:#FFFFFF;
	text-align:center;
}
.formulario{
    background-color:#D9E2EF;
    font:11px Arial;
    text-align:left;
}
</style>

<script language='JavaScript'>
function limpa(){
	document.frm_familia.descricao.value = "";
	document.frm_familia.codigo_familia.value = "";
}
</script>

<body>

<form name="frm_familia" method="post" action="<? echo $PHP_SELF;if(isset($semcab))echo "?semcab=yes"; ?>">
<input type="hidden" name="familia" value="<? echo $familia ?>">

<?php echo $msg_debug; ?>

<?php if (count($msg_erro) > 0) { ?>
<div align='center' width='700'>
	<?php foreach($msg_erro as $erro) echo $erro.'<br />'; ?>
</div>
<?php } ?>

<table class='formulario' align='center' width='700' border='0' cellpadding="2" cellspacing="0">
	<tr bgcolor="#596D9B" style='font:bold 14px Arial; color:#ffffff;'>
		<td align='center' colspan='5'>Cadastro de Família</td>
	</tr>
	<tr><td>&nbsp;</td></tr>
	<tr class='Label'>
		<td width='40'>&nbsp;</td>
		<td align='right' >Código da Família</td>
		<td align='left'><input type="text" name="codigo_familia" class='frm' value="<? echo $codigo_familia ?>" size="10" maxlength="30"></td>
		<td align='right' >Descrição da Família</td>
		<td align='left'><input type="text" name="descricao" class='frm' value="<? echo $descricao ?>" size="30" maxlength="30"></td>
	</tr>

<?php
if ($pedido_via_distribuidor == 't') {
	echo "<tr>";
	echo "<td COLSPAN='3' ALIGN = 'LEFT'><b>Mão-de-Obra adicional para Distribuidor</b></td>";
	echo "<td COLSPAN='3' ALIGN='LEFT'>";
	echo "<input type='text' name='mao_de_obra_adicional_distribuidor' value='".$mao_de_obra_adicional_distribuidor."' size='10' maxlength='10'>";
	echo "</td>";
	echo "</tr>";
} 

if($login_fabrica == 20){
	echo "<tr>";
	echo "<TD COLSPAN='3' align='left' ><b>CFA</b></TD>";
	echo "<TD COLSPAN='3' align='left' ><input type='text' class='frm' name='bosch_cfa' value='".$bosch_cfa."' size='10' maxlength='10'></TD>";
	echo "</tr>";
}
?>

<tr class='Label'>
	<td>&nbsp;</td>
	<td align='right'>Ativo</td>
	<td colspan='4' align='left'><input type='checkbox' name='ativo' id='ativo' value='t' <?if($ativo == 't') echo "CHECKED";?>></td>
</tr>

<?php
if ($login_fabrica == 15) { //HD 275256 - gabrielSilva
?>
	<tr class='Label'>
		<td>&nbsp;</td>
		<td align='right'>Paga KM</td>
		<td colspan='4' align='left'><input type='checkbox' name='paga_km' id='paga_km' value='t' <?if($paga_km == 't') echo "CHECKED";?>></td>
	</tr>
<?php
}

if ($login_fabrica == 7) {
?>
	<tr>
		<td width='40'>&nbsp;</td>
		<td colspan='4'>
			<table border='0' cellspacing='3' width = '100%' cellpadding='1'  align='center' class='Conteudo'>
				<tr class='menu_top'>
					<TD colspan='7'>Valores</TD>
				</TR>
				<tr class='Label' align='left'>
					<td nowrap >Taxa de Visita</td>
					<td align='left'><input type="text" name="taxa_visita" class='frm' value="<? echo $responseFamiliaValores->taxa_visita ?>" size="10" maxlength="10"></td>
					<td nowrap >Diária</td>
					<td align='left'><input type="text" name="valor_diaria" class='frm' value="<? echo $responseFamiliaValores->valor_diaria ?>" size="10" maxlength="10"></td>
<?php 
$title = "Valor pago por hora para PTA por cada reparo por cada produto dessa família, Não é pago o valor de mão de obra.";
?>
					<td nowrap ><acronym title="<? echo $title; ?>">Hora Técnica PTA</acronym></td>
					<td align='left'><acronym title="<? echo $title; ?>"><input type="text" name="hora_tecnica_pta" class='frm' value="<? echo $responseFamiliaValores->hora_tecnica_pta ?>" size="10" maxlength="10"></acronym></td>
				</tr>
				<tr class='Label' align='left'>
					<td nowrap >Regulagem</td>
					<td align='left'><input type="text" name="regulagem_peso_padrao" class='frm' value="<? echo $responseFamiliaValores->regulagem_peso_padrao ?>" size="10" maxlength="10"></td>
					<td nowrap >Certificado</td>
					<td align='left'><input type="text" name="certificado_conformidade" class='frm' value="<? echo $responseFamiliaValores->certificado_conformidade ?>" size="10" maxlength="10"></td>
<?php 
$title = "Valor de mão de obra pago para PTA por cada reparo de produto da família, Não pagar por hora técnica.";
?>
					<td nowrap ><acronym title="<? echo $title; ?>">Valor M.O</acronym></td>
					<td align='left'><acronym title="<? echo $title; ?>"><input type="text" name="valor_mao_de_obra" class='frm' value="<? echo $responseFamiliaValores->valor_mao_de_obra ?>" size="10" maxlength="10"></acronym></td>
				</tr>
				<tr class='Label' align='left'>
					<td nowrap >Valor Por KM - Carro</td>
					<td align='left'><input type="text" name="valor_por_km_carro" class='frm' value="<? echo $responseFamiliaValores->valor_por_km_carro ?>" size="10" maxlength="10"></td>
					<td nowrap >Valor Por KM - Caminhão</td>
					<td align='left'><input type="text" name="valor_por_km_caminhao" class='frm' value="<? echo $responseFamiliaValores->valor_por_km_caminhao ?>" size="10" maxlength="10"></td>
<?php 
$title = "Hora Técnica cobrada do consumidor/cliente.";
?>
					<td nowrap ><acronym title="<? echo $title; ?>">Hora Técnica</acronym></td>
					<td align='left'><acronym title="<? echo $title; ?>"><input type="text" name="hora_tecnica" class='frm' value="<? echo $responseFamiliaValores->hora_tecnica ?>" size="10" maxlength="10"></acronym></td>
				</tr>
			</table>
		</td>
	</tr>
<?php
}
?>

<tr>
	<td colspan='7'>
		<P>
<?php
if($responseProdutoFamilia) {
?>
		<table border='0' cellspacing='1' width='650' cellpadding='1' align='center' class='tabela'>
			<tr>
				<TD class='titulo_tabela' colspan=2>Produtos na Família</TD>
			</tr>
			<tr class='titulo_coluna'>
<?php
	if($login_fabrica == 96 || $login_fabrica == 15) {
		echo "			<td width='40'>Referência</td>";
		echo "			<td>Nome Comercial</td>";
	}
?>
			</tr>
<?php
	$i = 1;
	foreach($responseProdutoFamilia AS $produto) {
		$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";
		$i++;
?>
			<TR bgcolor='<?php echo $cor; ?>'>
				<TD align='left'><font size='1'><?php echo $produto->referencia; ?></font></TD>
<?php
		if($login_fabrica == 96)
			echo "				<td align='left' style='padding-left:20px;'>$produto->referencia_fabrica</td>";
?>
				<TD align = 'left' ><font size='1'><a href='produto_cadastro.php?produto=<?php echo $produto->produto; ?>'><?php echo $produto->descricao; ?></a></font></TD>
			</TR>
<?php
	}
?>
		</table>
<?php
}
else {
		echo "<font size='2' face='verdana' color='#63798D'><b>ESTA FAMÍLIA NÃO POSSUI PRODUTOS CADASTRADOS</b></font>";
}
?>

	<P>

<? //chamado 2977 - HD 82470
$arrayFabrica = array(1, 2, 5, 8, 10, 14, 16, 20, 66);

if (in_array($login_fabrica, $arrayFabrica)) {
?>
	<table border='0' cellspacing='1' width = '700' cellpadding='1' align='center'>
		<tr >
			<td COLSPAN='7'>&nbsp;</td>
		</tr>
		<tr class='titulo_tabela'>
			<td COLSPAN='7'><B>SELECIONE OS DEFEITOS CONSTATADOS DA FAMÍLIA</B></td>
		</tr>
		<tr>
			<td align='left'>
<?php
	$i = 0;

	foreach ($responseDefeitosConstatados as $defeito_constatado) {
		$resto = $i % 2;
		$i++;

		$familia_defeito_constatado = $responseFamiliaDefeitosConstatados->defeito_constatado[$defeito_constatado->defeito_constatado];

		$check = ($familia_defeito_constatado) ? 'checked' : '';

		echo "<input type='hidden' name='familia_defeito_constatado_".$i."' value='".$defeito_constatado->defeito_constatado."'>\n";
		echo "<input type='checkbox' name='defeito_constatado_".$i."' value='".$defeito_constatado->defeito_constatado."' ".$check."></TD>\n";
		echo "<TD align='left'>".$defeito_constatado->codigo."</TD>\n";
		echo "<TD align='left'>".$defeito_constatado->descricao."</td>";

		if($resto == 0) {
			echo "					</tr>\n";
			echo "					<tr><td align='left'>\n";
		}
		else {
			echo "					<td align='left'>\n";
		}
	}

	echo "<input type='hidden' name='qtde_item' value='".$i."'>\n";
	echo "</table>";
}
?>
	</td>
</tr>
<tr>
	<td>&nbsp;</td>
</tr>
	<tr>
		<td colspan='5' align='center'>
			<input type='hidden' name='btnacao' value=''>
			<input type="button" value="Gravar" ONCLICK="javascript: if (document.frm_familia.btnacao.value == '' ) { document.frm_familia.btnacao.value='gravar' ; document.frm_familia.submit() } else { alert ('Aguarde submissão') }" ALT="Gravar formulário" border='0' > &nbsp;
			<input type="button" value="Apagar" ONCLICK="javascript: if (document.frm_familia.btnacao.value == '' ) { document.frm_familia.btnacao.value='deletar' ; document.frm_familia.submit() } else { alert ('Aguarde submissão') }" ALT="Apagar familia" border='0' > &nbsp;
			<a href="#"><input type="button" value="Limpar" ONCLICK="limpa()" ALT="Limpar campos" border='0' ></a>
		</td>
	</tr>
</table>

</form>
</div>

<p>

<table width='700' border='0' cellpadding='2' cellspacing='1' class='tabela' align='center'>
	<tr class='titulo_tabela'>
		<td colspan='4'>RELAÇÃO DAS FAMÍLIAS CADASTRADAS</td>
	</tr>
	<tr class='titulo_coluna'>
		<td>Código</td>
		<td>Descrição</td>
		<td>Status</td>
		<?php if ($pedido_via_distribuidor == 't') echo "<td>MO distrib.</td>"; ?>
		<?php if ($login_fabrica == 15) echo "<td>Paga KM</td>"; ?>
	</tr>

<?php
$x = 0;
foreach ($responseFamilias as $familias){
	$cor = ($x % 2 == 0) ? "#F7F5F0" : "#F1F4FA";
	$x++;
	
	if($familias->ativo == 't')
		$ativo = "<img src='imagens/status_verde.gif'> Ativo";
	else
		$ativo = "<img src='imagens/status_vermelho.gif'> Inativo";
	
	if($familias->paga_km == 't')
		$paga_km = "<img src='imagens/status_verde.gif'> Sim";
	else
		$paga_km = "<img src='imagens/status_vermelho.gif'> Não";
	
	echo "<tr bgcolor='$cor' class='Label'>";
	echo "<td align='left'>".$familias->codigo_familia."&nbsp;</td>\n";
	echo "<td align='left'><a href='$PHP_SELF?familia=".$familias->familia."";if(isset($semcab))echo "&semcab=yes";echo "'>".$familias->descricao."</a></td>\n";
	echo "<td align='left'>".$ativo."</td>\n";

	if ($pedido_via_distribuidor == 't') {
		echo "<td align='right'>".$familias->mao_de_obra_adicional_distribuidor."</td>\n";
	}
	
	if ($login_fabrica == 15){
		echo "<td align='left'>".$paga_km."</td>\n";
	}

	echo "</tr>\n";
}
echo "</table>\n";

if(!isset($semcab))include "rodape.php";
?>