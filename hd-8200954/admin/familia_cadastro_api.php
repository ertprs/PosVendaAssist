<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';


$admin_privilegios="cadastros";
include 'autentica_admin.php';


include 'funcoes.php';


#$res = pg_exec ($con,"SELECT pedido_via_distribuidor FROM tbl_fabrica WHERE fabrica = $login_fabrica");
$pedido_via_distribuidor = false;

#buscar essa configuração metdo que o Ricardo está desenvolvendo


if (strlen($_GET["familia"]) > 0) {
	$familia = trim($_GET["familia"]);
}

if (strlen($_POST["familia"]) > 0) {
	$familia = trim($_POST["familia"]);
}

if (strlen($_POST["btnacao"]) > 0) {
	$btnacao = trim($_POST["btnacao"]);
}

if (strlen($_POST["bosch_cfa"]) > 0) {
	$bosch_cfa = trim($_POST["bosch_cfa"]);
}



if ($btnacao == "deletar" and strlen($familia) > 0 ) {

	
	#chamada da API 
	$res = pg_exec ($con,"BEGIN TRANSACTION");
	#$CAMINHO = "http://api.telecontrol.com.br/familia/id/$familia
	$sql = "DELETE FROM tbl_familia_defeito_constatado
			WHERE       tbl_familia_defeito_constatado.familia = $familia";
	$res = pg_exec ($con,$sql);
	$msg_erro = pg_errormessage($con);
	
	if (strlen ($msg_erro) == 0) {
		$sql = "DELETE FROM tbl_familia
				WHERE  tbl_familia.fabrica = $login_fabrica
				AND    tbl_familia.familia = $familia";
		$res = @pg_exec ($con,$sql);
		$msg_erro = pg_errormessage($con);
		if(strlen($msg_erro)>0)
			$msg_erro = "Esta Família não pode ser excluída porque está em uso em outras partes do sistema.";

		if (strpos ($msg_erro,'tbl_diagnostico') > 0)
			$msg_erro = "Esta familia já possui 'Relacionamento de Integridade' cadastrada, e não pode ser excluida";

		if (strpos ($msg_erro,'tbl_defeito_reclamado') > 0)
			$msg_erro = "Esta família já possui 'Defeitos Reclamados' cadastrada, e não pode ser excluída";

		if (strpos ($msg_erro,'tbl_defeito_constatado') > 0)
			$msg_erro = "Esta família já possui ‘Defeitos Constatados’ cadastrada, e não pode ser excluída";

		if (strpos ($msg_erro,'update or delete on table "tbl_tipo_cliente" violates foreign key constraint $1 on table "tbl_cliente"') > 0)
			$msg_erro = "Esta família já possui produtos cadastrados, e não pode ser excluída ";

		if (strpos ($msg_erro,'familia_fk') > 0)
			$msg_erro = "Esta familia já possui produtos cadastrados, e não pode ser excluida";

		if (strlen ($msg_erro) == 0) {
			###CONCLUI OPERAÇÃO DE INCLUSÃO/EXLUSÃO/ALTERAÇÃO E SUBMETE
			$res = pg_exec ($con,"COMMIT TRANSACTION");

			header ("Location: $PHP_SELF");
			exit;
		}else{
			###ABORTA OPERAÇÃO DE INCLUSÃO/EXLUSÃO/ALTERAÇÃO E RECARREGA CAMPOS

			$familia   = $_POST["familia"];
			$codigo_familia   = $_POST["codigo_familia"];
			$descricao = $_POST["descricao"];

			$res = pg_exec ($con,"ROLLBACK TRANSACTION");
		}
	}
}

if ($btnacao == "gravar") {
	$codigo_familia = trim($_POST["codigo_familia"]);

	if (strlen($_POST["descricao"]) > 0) $aux_descricao  = "'". trim($_POST["descricao"]) ."'";
	else                                 $msg_erro       = "Favor informar a descrição da familia.";
	if($login_fabrica <> 10){
		if (strlen($codigo_familia)==0)      $codigo_familia = '';
		else                                 $codigo_familia = $codigo_familia;
	}
	if (strlen($bosch_cfa)==0)           $aux_bosch_cfa  = 'null';
	else                                 $aux_bosch_cfa  = "'" . $bosch_cfa . "'";
	if (strlen($ativo)==0)               $aux_ativo      = "'f'";
	else                                 $aux_ativo      = "'t'";

	$taxa_visita              = trim($_POST["taxa_visita"]);
	$hora_tecnica             = trim($_POST["hora_tecnica"]);
	$hora_tecnica_pta         = trim($_POST["hora_tecnica_pta"]);
	$valor_diaria             = trim($_POST["valor_diaria"]);
	$valor_por_km_caminhao    = trim($_POST["valor_por_km_caminhao"]);
	$valor_por_km_carro       = trim($_POST["valor_por_km_carro"]);
	$regulagem_peso_padrao    = trim($_POST["regulagem_peso_padrao"]);
	$certificado_conformidade = trim($_POST["certificado_conformidade"]);
	$valor_mao_de_obra        = trim($_POST["valor_mao_de_obra"]);
	$paga_km                  = ($login_fabrica == 15) ? trim($_POST["paga_km"]) : null ; //HD 275256 - gabrielSilva
	
	$paga_km = ($paga_km == 'TRUE') ? 't' : 'f' ;
	
	if (strlen($taxa_visita)==0){
		$aux_taxa_visita = " null ";
	}else{
		$aux_taxa_visita = str_replace(",",".",$taxa_visita);
	}

	if (strlen($valor_diaria)==0){
		$aux_valor_diaria = " null ";
	}else{
		$aux_valor_diaria = str_replace(",",".",$valor_diaria);
	}

	if (strlen($valor_por_km_caminhao)==0){
		$aux_valor_por_km_caminhao = " null ";
	}else{
		$aux_valor_por_km_caminhao = str_replace(",",".",$valor_por_km_caminhao);
	}

	if (strlen($valor_por_km_carro)==0){
		$aux_valor_por_km_carro = " null ";
	}else{
		$aux_valor_por_km_carro = str_replace(",",".",$valor_por_km_carro);
	}

	if (strlen($regulagem_peso_padrao)==0){
		$aux_regulagem_peso_padrao = " null ";
	}else{
		$aux_regulagem_peso_padrao = str_replace(",",".",$regulagem_peso_padrao);
	}

	if (strlen($certificado_conformidade)==0){
		$aux_certificado_conformidade = " null ";
	}else{
		$aux_certificado_conformidade = str_replace(",",".",$certificado_conformidade);
	}

	if(strlen($valor_mao_de_obra)>0  AND strlen($hora_tecnica_pta)>0){
			$msg_erro = " Favor Digitar o valor de hora técnica ou valor de M.O ";
	}

	if (strlen($valor_mao_de_obra)==0){
		$aux_valor_mao_de_obra = " null ";
	}else{
		$aux_valor_mao_de_obra = str_replace(",",".",$valor_mao_de_obra);
	}

	if (strlen($hora_tecnica_pta)==0){
		$aux_hora_tecnica_pta = " null ";
	}else{
		$aux_hora_tecnica_pta = str_replace(",",".",$hora_tecnica_pta);
	}

	if (strlen($hora_tecnica)==0){
		$aux_hora_tecnica = " null ";
	}else{
		$aux_hora_tecnica = str_replace(",",".",$hora_tecnica);
	}

	if (strlen($msg_erro) == 0) {
		$mao_de_obra_adicional_distribuidor = trim ($_POST['mao_de_obra_adicional_distribuidor']);
		$aux_mao_de_obra_adicional_distribuidor = $mao_de_obra_adicional_distribuidor;
		if (strlen ($aux_mao_de_obra_adicional_distribuidor) == 0) {
			$aux_mao_de_obra_adicional_distribuidor = 0 ;
		}
		$aux_mao_de_obra_adicional_distribuidor = str_replace (",",".",$aux_mao_de_obra_adicional_distribuidor);

		$res = pg_exec ($con,"BEGIN TRANSACTION");
		if(strlen($familia) == 0 and $codigo_familia <> 'null' and strlen($codigo_familia) > 0){
			$sql = "SELECT codigo_familia FROM tbl_familia WHERE fabrica = $login_fabrica AND codigo_familia = '$codigo_familia';";
			$res = @pg_exec($con,$sql);

			if(@pg_numrows($res) > 0){
				$msg_erro = "Código $codigo_familia já existente. ";
				if(strlen($codigo_familia)==0){
					$msg_erro .= "Código da família não pode ser em branco.";
				}
			}else{
				$sql_familia = ",codigo_familia ";
				$var_familia = " ,'$codigo_familia' ";
			}
		}
		if(strlen($msg_erro) == 0){

			if (strlen($familia) == 0) {

				###INSERE NOVO REGISTRO
				$sql = "INSERT INTO tbl_familia (
							fabrica           ,
							descricao         ,
							bosch_cfa         ,
							ativo             ,"; 
				
/*HD 275256*/ if ($login_fabrica == 15){
				
				$sql .= "
							paga_km,
				";
				
              }
				
				$sql .="
							mao_de_obra_adicional_distribuidor
							$sql_familia
						) VALUES (
							$login_fabrica   ,
							$aux_descricao   ,
							$aux_bosch_cfa   ,
							$aux_ativo       ,"; 
				
/*HD 275256*/ if ($login_fabrica == 15){
				
				$sql .= "
							'$paga_km',
				";
				
              }
				
				$sql .="
							$aux_mao_de_obra_adicional_distribuidor
							$var_familia
						);";
				$res = @pg_exec ($con,$sql);
				$msg_erro = pg_errormessage($con);

				if (strlen($msg_erro) == 0){
					$res = @pg_exec ($con,"SELECT CURRVAL('seq_familia')");
					if (strlen (pg_errormessage ($con)) > 0) $msg_erro = pg_errormessage($con);
					else                                    $familia = pg_result($res,0,0);
				}

			}else{
				###ALTERA REGISTRO
				$sql = "UPDATE tbl_familia SET
						codigo_familia = '$codigo_familia',
						descricao      = $aux_descricao,
						bosch_cfa      = $aux_bosch_cfa,
						ativo          = $aux_ativo,"; 
				
/*HD 275256*/ if ($login_fabrica == 15){

				$sql .= "paga_km       = '$paga_km', ";

              }
				
				$sql .="
						mao_de_obra_adicional_distribuidor = $aux_mao_de_obra_adicional_distribuidor
					WHERE  tbl_familia.fabrica = $login_fabrica
					AND    tbl_familia.familia   = $familia;";
	//echo $sql;
				$res = @pg_exec ($con,$sql);
				$msg_erro = pg_errormessage($con);
			}
		}
		if(strpos ($msg_erro,'tbl_familia_unico') > 0) {
			$msg_erro= "Código $codigo_familia já existente";
			if(strlen($codigo_familia)==0){
				$msg_erro .= "<br>Código da família não pode ser em branco.";
			}
		}
		if ($login_fabrica == 7) {
			if(strlen($msg_erro) == 0){
				$sql = "SELECT familia
						FROM tbl_familia_valores
						WHERE familia = $familia ";
				$res = pg_exec($con,$sql);

				if(pg_numrows($res) > 0){
					$sql = "UPDATE tbl_familia_valores SET
								taxa_visita              = $aux_taxa_visita              ,
								hora_tecnica             = $aux_hora_tecnica             ,
								hora_tecnica_pta         = $aux_hora_tecnica_pta         ,
								valor_diaria             = $aux_valor_diaria             ,
								valor_por_km_caminhao    = $aux_valor_por_km_caminhao    ,
								valor_por_km_carro       = $aux_valor_por_km_carro       ,
								regulagem_peso_padrao    = $aux_regulagem_peso_padrao    ,
								certificado_conformidade = $aux_certificado_conformidade ,
								valor_mao_de_obra        = $aux_valor_mao_de_obra
							WHERE familia = $familia ";
					$res = @pg_exec ($con,$sql);
					$msg_erro = pg_errormessage($con);
				}else{
					$sql = "INSERT INTO tbl_familia_valores (
								familia                  ,
								taxa_visita              ,
								hora_tecnica             ,
								valor_diaria             ,
								valor_por_km_caminhao    ,
								valor_por_km_carro       ,
								regulagem_peso_padrao    ,
								certificado_conformidade ,
								valor_mao_de_obra        ,
								hora_tecnica_pta
							) VALUES (
								$familia                     ,
								$aux_taxa_visita             ,
								$aux_hora_tecnica            ,
								$aux_valor_diaria            ,
								$aux_valor_por_km_caminhao   ,
								$aux_valor_por_km_carro      ,
								$aux_regulagem_peso_padrao   ,
								$aux_certificado_conformidade,
								$aux_valor_mao_de_obra       ,
								$aux_hora_tecnica_pta
							);";
					$res = @pg_exec ($con,$sql);
					$msg_erro = pg_errormessage($con);
				}
			}
		}
	}

/////////////////////
	// grava familia_defeito_constatado
	if (strlen($msg_erro) == 0){

		for ($i = 0 ; $i < $qtde_item ; $i++) {
			$novo   = $_POST["novo_".$i];

			$defeito_constatado     = $_POST['defeito_constatado_' . $i];
			$aux_defeito_constatado = $_POST['aux_defeito_constatado_' . $i];

			if(strlen($aux_defeito_constatado) > 0 AND strlen($defeito_constatado) == 0) {
				if ($novo == 'f') {
					$sql = "DELETE FROM tbl_familia_defeito_constatado
							WHERE  defeito_constatado = $aux_defeito_constatado
							AND    familia            = $familia ";
					$res = @pg_exec($con,$sql);
					$msg_erro = pg_errormessage($con);
				}
			}

			if (strlen ($msg_erro) == 0 AND strlen($defeito_constatado) > 0) {
				if ($novo == 't'){
					$sql = "INSERT INTO tbl_familia_defeito_constatado (
								defeito_constatado,
								familia
							) VALUES (
								$defeito_constatado,
								$familia
							)";
				}else{
					$sql = "UPDATE tbl_familia_defeito_constatado SET
								defeito_constatado = $defeito_constatado,
								familia            = $familia
							WHERE  defeito_constatado = $defeito_constatado
							AND    familia            = $familia ";
				}

				$res = @pg_exec ($con,$sql);
				$msg_erro = pg_errormessage($con);
			}
		}
	}
/////////////////////

	if (strlen ($msg_erro) == 0) {
		###CONCLUI OPERAÇÃO DE INCLUSÃO/EXLUSÃO/ALTERAÇÃO E SUBMETE
		$res = pg_exec ($con,"COMMIT TRANSACTION");

		header ("Location: $PHP_SELF");
		exit;
	}else{
		###ABORTA OPERAÇÃO DE INCLUSÃO/EXLUSÃO/ALTERAÇÃO E RECARREGA CAMPOS

		$codigo_familia    = $POST["codigo_familia"];
		$descricao         = $POST["descricao"];
		$ativo             = $POST["ativo"];
		$res = pg_exec ($con,"ROLLBACK TRANSACTION");
	}
}


###CARREGA REGISTRO
if (strlen($familia) > 0) {
	$sql = "SELECT  tbl_familia.familia,
					tbl_familia.descricao,
					tbl_familia.codigo_familia,
					tbl_familia.bosch_cfa,
					tbl_familia.ativo,
					tbl_familia.paga_km,
					tbl_familia.mao_de_obra_adicional_distribuidor
			FROM    tbl_familia
			WHERE   tbl_familia.fabrica = $login_fabrica
			AND     tbl_familia.familia   = $familia;";
	$res = pg_exec ($con,$sql);
//	echo $sql;
	if (pg_numrows($res) > 0) {
		$familia           = trim(pg_result($res,0,familia));
		$codigo_familia    = trim(pg_result($res,0,codigo_familia));
		$bosch_cfa         = trim(pg_result($res,0,bosch_cfa));
		$descricao         = trim(pg_result($res,0,descricao));
		$ativo             = trim(pg_result($res,0,ativo));
		$paga_km           = trim(pg_result($res,0,paga_km));
		$mao_de_obra_adicional_distribuidor = trim(pg_result($res,0,mao_de_obra_adicional_distribuidor));

		$sql = "SELECT  taxa_visita              ,
						hora_tecnica             ,
						hora_tecnica_pta         ,
						valor_diaria             ,
						valor_por_km_caminhao    ,
						valor_por_km_carro       ,
						regulagem_peso_padrao    ,
						certificado_conformidade ,
						valor_mao_de_obra
				FROM    tbl_familia_valores
				WHERE   familia = $familia;";
		$res = pg_exec ($con,$sql);
		if (pg_numrows($res) > 0) {
			$taxa_visita              = trim(pg_result($res,0,taxa_visita));
			$hora_tecnica             = trim(pg_result($res,0,hora_tecnica));
			$hora_tecnica_pta         = trim(pg_result($res,0,hora_tecnica_pta));
			$valor_diaria             = trim(pg_result($res,0,valor_diaria));
			$valor_por_km_caminhao    = trim(pg_result($res,0,valor_por_km_caminhao));
			$valor_por_km_carro       = trim(pg_result($res,0,valor_por_km_carro));
			$regulagem_peso_padrao    = trim(pg_result($res,0,regulagem_peso_padrao));
			$certificado_conformidade = trim(pg_result($res,0,certificado_conformidade));
			$valor_mao_de_obra        = trim(pg_result($res,0,valor_mao_de_obra));
		}
	}
}
?>
<?
	$layout_menu = "cadastro";
	$title = "CADASTRO DE FAMÍLIAS DOS PRODUTOS";
	if(!isset($semcab))include 'cabecalho.php';
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
<?
 echo $msg_debug;
?>


<table class='formulario' align='center' width='700' border='0' cellpadding="2" cellspacing="0">
<? if (strlen($msg_erro) > 0) { ?>

<tr bgcolor='#ff0000' style='font:bold 16px Arial; color:#ffffff;'>
	<td colspan='5'><? echo $msg_erro; ?></td>
</tr>

<? } ?>

<tr  bgcolor="#596D9B" style='font:bold 14px Arial; color:#ffffff;' >
	
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
<?
if ($pedido_via_distribuidor == 't') {
	echo "<tr>";
	echo "<td COLSPAN='3' ALIGN = 'LEFT'><b>Mão-de-Obra adicional para Distribuidor</b></td>";
	echo "<td COLSPAN='3' ALIGN='LEFT'>";
	echo "<input type='text' name='mao_de_obra_adicional_distribuidor' value='$mao_de_obra_adicional_distribuidor' size='10' maxlength='10'>";
	echo "</td>";
	echo "</tr>";

} ?>
<?
	if($login_fabrica == 20){
	echo "<tr>";
	echo "<TD COLSPAN='3' align='left' ><b>CFA</b></TD>";
	echo "<TD COLSPAN='3' align='left' ><input type='text' class='frm' name='bosch_cfa' value='$bosch_cfa' size='10' maxlength='10'></TD>";
	echo "</tr>";
	} ?>

<tr class='Label'>
	<td>&nbsp;</td>
	<td align='right'>Ativo</td>
	<td colspan='4' align='left'><input type='checkbox' name='ativo' id='ativo' value='TRUE' <?if($ativo == 't') echo "CHECKED";?>></td>
</tr>

<?//HD 275256 - gabrielSilva
if ($login_fabrica == 15){?>
<tr class='Label'>
	<td>&nbsp;</td>
	<td align='right'>Paga KM</td>
	<td colspan='4' align='left'><input type='checkbox' name='paga_km' id='paga_km' value='TRUE' <?if($paga_km == 't') echo "CHECKED";?>></td>
</tr>
<?}?>


<? if ($login_fabrica == 7) { ?>
	<tr>
	<td width='40'>&nbsp;</td>
	<td colspan='4'>
		<table border='0' cellspacing='3' width = '100%' cellpadding='1'  align='center' class='Conteudo'>
		<tr class='menu_top' >
		<TD colspan='7'>Valores</TD>
		</TR>
		<tr class='Label' align='left'>

			<td nowrap >Taxa de Visita</td>
			<td align='left'><input type="text" name="taxa_visita" class='frm' value="<? echo $taxa_visita ?>" size="10" maxlength="10"></td>
			<td nowrap >Diária</td>
			<td align='left'><input type="text" name="valor_diaria" class='frm' value="<? echo $valor_diaria ?>" size="10" maxlength="10"></td>
			<? if($login_fabrica==7){//HD 30941
				$title = "Valor pago por hora para PTA por cada reparo por cada produto dessa família, Não é pago o valor de mão de obra."; ?>
				<td nowrap ><acronym title="<? echo $title; ?>">Hora Técnica PTA</acronym></td>
				<td align='left'><acronym title="<? echo $title; ?>"><input type="text" name="hora_tecnica_pta" class='frm' value="<? echo $hora_tecnica_pta ?>" size="10" maxlength="10"></acronym></td>
			<? } ?>
		</tr>
		<tr class='Label' align='left'>
			<td nowrap >Regulagem</td>
			<td align='left'><input type="text" name="regulagem_peso_padrao" class='frm' value="<? echo $regulagem_peso_padrao ?>" size="10" maxlength="10"></td>
			<td nowrap >Certificado</td>
			<td align='left'><input type="text" name="certificado_conformidade" class='frm' value="<? echo $certificado_conformidade ?>" size="10" maxlength="10"></td>
			<? if($login_fabrica==7){//HD 30941
				$title = "Valor de mão de obra pago para PTA por cada reparo de produto da família, Não pagar por hora técnica."; ?>
				<td nowrap ><acronym title="<? echo $title; ?>">Valor M.O</acronym></td>
				<td align='left'><acronym title="<? echo $title; ?>"><input type="text" name="valor_mao_de_obra" class='frm' value="<? echo $valor_mao_de_obra ?>" size="10" maxlength="10"></acronym></td>
			<?}?>
		</tr>
		<tr class='Label' align='left'>
			<td nowrap >Valor Por KM - Carro</td>
			<td align='left'><input type="text" name="valor_por_km_carro" class='frm' value="<? echo $valor_por_km_carro ?>" size="10" maxlength="10"></td>
			<td nowrap >Valor Por KM - Caminhão</td>
			<td align='left'><input type="text" name="valor_por_km_caminhao" class='frm' value="<? echo $valor_por_km_caminhao ?>" size="10" maxlength="10"></td>
			<? if($login_fabrica==7){//HD 30941
				$title = "Hora Técnica cobrada do consumidor/cliente."; ?>
				<td nowrap ><acronym title="<? echo $title; ?>">Hora Técnica</acronym></td>
				<td align='left'><acronym title="<? echo $title; ?>"><input type="text" name="hora_tecnica" class='frm' value="<? echo $hora_tecnica ?>" size="10" maxlength="10"></acronym></td>
			<?}?>
		</tr>
		</table>
	</td>
	</tr>

<? } ?>

<tr>
	<td colspan='7'>

<P>

<?
if(strlen($familia) > 0){
	 $sql = "SELECT    tbl_familia.descricao AS descricao_familia,
					  tbl_produto.produto                       ,";
	if($login_fabrica == 96){
		$sql .= "tbl_produto.referencia_fabrica,";
	}
	$sql .= " tbl_produto.referencia        ,
		      tbl_produto.descricao
			FROM      tbl_produto
			LEFT JOIN tbl_familia ON tbl_familia.familia = tbl_produto.familia
			WHERE     tbl_familia.fabrica = $login_fabrica
			AND       tbl_produto.familia = $familia";

	if ($login_fabrica == 14) $sql .= " AND tbl_produto.abre_os = true";

	$sql .= " ORDER BY  tbl_produto.descricao;";
	$res = @pg_exec ($con,$sql);

	if (pg_numrows($res) > 0){
		
		echo "<table border='0' cellspacing='1' width = '650' cellpadding='1'  align='center' class='tabela'>";
		echo "<tr >";
		echo "<TD class='titulo_tabela' colspan=2>Produtos na Família ".@pg_result($res,0,descricao_familia)."</TD>";
		echo "</tr>";
		echo "<tr class='titulo_coluna'>";
		if($login_fabrica == 96 || $login_fabrica == 15){
			echo "<td width='40'>Referência</td>";
			echo "<td>Nome Comercial</td>";
		}
		echo "</TR>";

		for ($i = 0 ; $i < @pg_numrows($res) ; $i++){
			$produto       = trim(@pg_result($res,$i,produto));
			if($login_fabrica == 96){
				$referencia_fabrica    = trim(@pg_result($res,$i,referencia_fabrica));
			}
			$referencia    = trim(@pg_result($res,$i,referencia));
			$descricao     = trim(@pg_result($res,$i,descricao));
			
			$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";
			
			echo "<TR bgcolor='$cor'>";
			echo "<TD align = 'left' ><font size='1'>$referencia &nbsp;</font></TD>";
			if($login_fabrica == 96){
				echo "<td align='left' style='padding-left:20px;'>$referencia_fabrica</td>";
			}
			echo "<TD align = 'left' ><font size='1'><a href='produto_cadastro.php?produto=$produto'>$descricao</a></font></TD>";
			echo "</TR>";
		}
		echo "</TABLE>";
		
	}else if (pg_numrows($res) == 0){
		echo "<font size='2' face='verdana' color='#63798D'><b>ESTA FAMÍLIA NÃO POSSUI PRODUTOS CADASTRADOS</b></font>";
	}
}
?>

<P>




<? //chamado 2977 - HD 82470
if ($login_fabrica==1 OR $login_fabrica==2 OR $login_fabrica==5 OR $login_fabrica==8 OR $login_fabrica==10 OR $login_fabrica==14 OR $login_fabrica==16 OR $login_fabrica==20 OR $login_fabrica==66) {
	echo "<table border='0' cellspacing='1' width = '700' cellpadding='1'  align='center'  >";
	echo "<tr >";
	echo "<td COLSPAN='7'>&nbsp;</td>";
	echo "</tr>";
	echo "<tr class='titulo_tabela' >";
	echo "<td COLSPAN='7'><B>SELECIONE OS DEFEITOS CONSTATADOS DA FAMÍLIA</B></td>";
	echo "</tr>";
	echo "<tr>";

	echo "<td align='left'>";

	$familia = $_GET['familia'];

	$sql = "SELECT *
			FROM  tbl_defeito_constatado
			WHERE fabrica = $login_fabrica
			ORDER BY LPAD(codigo,5,'0') ASC";
	$res = @pg_exec ($con,$sql);

	if (pg_numrows($res) > 0) {

		$y=1;

		for($i=0; $i<pg_numrows($res); $i++){
			$defeito_constatado = trim(pg_result($res,$i,defeito_constatado));
			$codigo             = trim(pg_result($res,$i,codigo));
			$descricao          = trim(pg_result($res,$i,descricao));

			if (strlen($familia) > 0) {
				$sql = "SELECT  tbl_familia_defeito_constatado.familia_defeito_constatado,
								tbl_familia_defeito_constatado.defeito_constatado
						FROM    tbl_familia_defeito_constatado
						WHERE   tbl_familia_defeito_constatado.defeito_constatado = $defeito_constatado
						AND     tbl_familia_defeito_constatado.familia            = $familia";
				$res2 = @pg_exec($con,$sql);

				if (pg_numrows($res2) > 0) {
					$novo                       = 'f';
					$familia_defeito_constatado = trim(pg_result($res2,0,familia_defeito_constatado));
					$xdefeito_constatado        = trim(pg_result($res2,0,defeito_constatado));
				}else{
					$novo                       = 't';
					$familia_defeito_constatado = "";
					$xdefeito_constatado         = "";
				}
			}else{
				$novo                       = 't';
				$familia_defeito_constatado = "";
				$xdefeito_constatado         = "";
			}

			$resto = $y % 2;
			$y++;

			if ($xdefeito_constatado == $defeito_constatado)
				$check = " checked ";
			else
				$check = "";

			echo "<input type='hidden' name='novo_$i' value='$novo'>\n";
			echo "<input type='hidden' name='aux_defeito_constatado_$i' value='$defeito_constatado'>\n";
			echo "<input type='checkbox' name='defeito_constatado_$i' value='$defeito_constatado' $check></TD>\n";
			echo "<TD align='left'>$codigo </TD>\n";
			echo "<TD align='left'>$descricao";

			if($resto == 0){
				echo "					</td></tr>\n";
				echo "					<tr><td align='left'>\n";
			}else{
				echo "					</td>\n";
				echo "					<td align='left'>\n";
			}
		}
	}

	echo "<input type='hidden' name='qtde_item' value='$i'>\n";
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
			
			<input type="button" value="Gravar" ONCLICK="javascript: if (document.frm_familia.btnacao.value == '' ) { document.frm_familia.btnacao.value='gravar' ; document.frm_familia.submit() } else { alert ('Aguarde submissão') }" ALT="Gravar formulário" border='0' >
			&nbsp;
			<input type="button" value="Apagar" ONCLICK="javascript: if (document.frm_familia.btnacao.value == '' ) { document.frm_familia.btnacao.value='deletar' ; document.frm_familia.submit() } else { alert ('Aguarde submissão') }" ALT="Apagar familia" border='0' >
			&nbsp;
			
			<a href="#"><input type="button" value="Limpar" ONCLICK="limpa()" ALT="Limpar campos" border='0' ></a>
		</td>
	</tr>
</table>

</form>
</div>
<p>

<?
echo "<table width='700' border='0' cellpadding='2' cellspacing='1' class='tabela' align='center' class='tabela'>";
echo "<tr class='titulo_tabela'>";
echo "<td colspan='4'>RELAÇÃO DAS FAMÍLIAS CADASTRADAS</td>";
echo "</tr>";
echo "<tr class='titulo_coluna'>";
echo "<td>Código</td>";
echo "<td>Descrição</td>";
echo "<td>Status</td>";
if ($login_fabrica == 15){
echo "<td>Paga KM</td>";
}
echo "</tr>";

$sql = "SELECT  tbl_familia.familia  ,
		tbl_familia.descricao        ,
		tbl_familia.mao_de_obra_adicional_distribuidor,
		tbl_familia.codigo_familia   ,
		tbl_familia.paga_km          ,
		tbl_familia.ativo
	FROM    tbl_familia
	WHERE   tbl_familia.fabrica = $login_fabrica
	AND       tbl_familia.familia not in (2615,2716,2711,2645,2640,2651,2614,2623,2648,2646,2625,2652,2635,2641,2658,2647,2656,2631,2621,2620,2626,2643,2616,2630,2629)
	ORDER BY tbl_familia.ativo DESC, tbl_familia.descricao;";
#$res = pg_exec ($con,$sql);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "http://api.telecontrol.com.br/posvenda/familias");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
curl_setopt($ch, CURLOPT_HEADER, FALSE);
curl_setopt($ch, CURLOPT_HTTPHEADER, array("fabrica: $login_fabrica"));
$response = curl_exec($ch);
curl_close($ch); 
var_dump($response);

for ($x = 0 ; $x < count($response) ; $x++){
	#$familia        = trim(pg_result($res,$x,familia));
	#$descricao      = trim(pg_result($res,$x,descricao));
	#$codigo_familia = trim(pg_result($res,$x,codigo_familia));
	#$ativo          = trim(pg_result($res,$x,ativo));
	#$mao_de_obra_adicional_distribuidor = trim(pg_result($res,$x,mao_de_obra_adicional_distribuidor));
	#$paga_km        = ($login_fabrica == 15) ? trim(pg_result($res,$x,paga_km)) : null;

	
	$cor = ($x % 2 == 0) ? "#F7F5F0" : "#F1F4FA";

	if($response->ativo=='t') $ativo = "<img src='imagens/status_verde.gif'> Ativo";
	else            $ativo = "<img src='imagens/status_vermelho.gif'> Inativo";
	
	if($response->paga_km=='t') $paga_km = "<img src='imagens/status_verde.gif'> Sim";
	else              $paga_km = "<img src='imagens/status_vermelho.gif'> Não";
	
	

	echo "<tr bgcolor='$cor' class='Label'>";
	echo "<td align='left'>$response->codigo_familia &nbsp;</td>\n";
	echo "<td align='left'><a href='$PHP_SELF?familia=$response->familia";if(isset($semcab))echo "&semcab=yes";echo "'>$response->descricao</a></td>\n";
	echo "<td align='left'>$ativo</td>\n";

	if ($pedido_via_distribuidor == 't') {
		echo "<td align='right'>";
		echo $response->mao_de_obra_adicional_distribuidor;
		echo "</td>\n";
	}
	
	if ($login_fabrica == 15){
		echo "<td align='left'>";
			echo $response->paga_km;
		echo "</td>\n";
	}

	echo "</tr>\n";
}
echo "</table>\n";

if(!isset($semcab))include "rodape.php";
?>
