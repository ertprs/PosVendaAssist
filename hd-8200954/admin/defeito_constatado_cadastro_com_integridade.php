<?php
//liberado tela nova 17/10 takashi
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="cadastros";
include 'autentica_admin.php';
include 'funcoes.php';

$btn_acao = trim($_POST["btn_acao"]);
$defeito_constatado = trim($_GET["defeito_constatado"]);
if(strlen($defeito_constatado)>0){
	# hd 22332
	if ($login_fabrica == 30 or $login_fabrica == 43 or $login_fabrica == 94){
		# HD 23943 - Francisco Ambrozio (23/7/08) - adicionado campos "tabela de preço" e
		#   "versão de tabela de preço" para a Esmaltec
		$sql = "SELECT  tbl_defeito_constatado.codigo       ,
					tbl_defeito_constatado.ativo            ,
					tbl_defeito_constatado.mao_de_obra	    ,
					tbl_defeito_constatado.lista_garantia	,
					tbl_defeito_constatado.versao_lista	    ,
					tbl_defeito_constatado.descricao        ,
					tbl_defeito_constatado.lancar_peca,
						tbl_defeito_constatado.orientacao
			FROM    tbl_defeito_constatado
			WHERE   tbl_defeito_constatado.fabrica            = $login_fabrica
			AND     tbl_defeito_constatado.defeito_constatado = $defeito_constatado
			ORDER BY tbl_defeito_constatado.descricao";
	}else{
		$sql = "SELECT  tbl_defeito_constatado.codigo   ,
					tbl_defeito_constatado.ativo   ,
					tbl_defeito_constatado.descricao,
					tbl_defeito_constatado.lancar_peca ";
		if($login_fabrica==52){
			$sql .= ", defeito_constatado_grupo ";
		}
		if (in_array($login_fabrica,array(108,111))){
			$sql .= ", tbl_defeito_constatado.lancar_peca ";
		}
		$sql .= "	FROM    tbl_defeito_constatado
			WHERE   tbl_defeito_constatado.fabrica            = $login_fabrica
			AND     tbl_defeito_constatado.defeito_constatado = $defeito_constatado
			ORDER BY tbl_defeito_constatado.descricao";
	}
	$res = pg_exec ($con,$sql);
	if (pg_numrows($res) > 0) {
		$codigo    = trim(pg_result($res,0,codigo));
		$descricao = trim(pg_result($res,0,descricao));
		if($login_fabrica==52){
			$defeito_constatado_grupo = trim(pg_result($res,0,defeito_constatado_grupo));
		}
		if ($login_fabrica == 30 or $login_fabrica == 43 or $login_fabrica == 94){
			$mao_de_obra		  = trim(pg_result($res,0,mao_de_obra));
			$lista_garantia		  = trim(pg_result($res,$x,lista_garantia));
			$versao_lista		  = trim(pg_result($res,$x,versao_lista));
			$orientacao=			trim(pg_result($res,$x,'orientacao'));
		}
		
		if (in_array($login_fabrica,array(108,111))){
			$lancar_peca = trim(pg_result($res,0,'lancar_peca'));
		}
		$ativo     = trim(pg_result($res,0,ativo));
		$lancar_peca = trim(pg_result($res,0,lancar_peca));
	}
}

if(strlen($btn_acao)>0){
	 $defeito_constatado= $_POST["defeito_constatado"];
	 $orientacao = ( isset( $_POST['orientacao'] ) ) ? $_POST['orientacao'] : 'f';
	$codigo = trim($_POST["codigo"]);
	$defeito_constatado_grupo = trim($_POST["defeito_constatado_grupo"]);
	$item_servico = $_POST['item_servico'];

	if (strlen($defeito_constatado_grupo)==0) {
		$defeito_constatado_grupo = 'null';
	}
	if(strlen($codigo)==0) {
		if($login_fabrica == 35){
			$msg_erro = "Por favor insira o código do defeito constatado";
		}else{
			$codigo = "''";//{ $msg_erro ="Por favor insira o código do defeito constatado<BR>";}
		}
	} else {
		$codigo = "'".$codigo."'";
	}
	
	$descricao = trim($_POST["descricao"]);
	if(strlen($descricao)==0){ $msg_erro ="Por favor insira a descrição do defeito constatado<BR>";}
	# hd 22332
	if ($login_fabrica == 30 or $login_fabrica == 43 or $login_fabrica == 94){
		$mao_de_obra = trim($_POST["mao_de_obra"]);
		if (strlen($mao_de_obra)==0){ $msg_erro ="Insira o valor da mão-de-obra<BR>";}
		if (preg_match("/[a-zA-Z]/",$mao_de_obra)){ $msg_erro ="Verifique o valor da mão-de-obra<BR>";}
		if (preg_match("/[,]/",$mao_de_obra)){ $mao_de_obra = str_replace(",", ".", $mao_de_obra);}
	}
	$ativo = trim($_POST["ativo"]);
	
	if (in_array($login_fabrica,array(108,111))){ //HD 733415
		$lancar_peca = trim($_POST['lancar_peca']);
		
		$lancar_peca = ($lancar_peca == 't') ? "true" : "false";
	}
	
	if(strlen($ativo)==0){$ativo='f';}
	$lancar_peca = trim($_POST["lancar_peca"]);
	if(strlen($lancar_peca)==0){
		$lancar_peca='f';
	}
	if(($btn_acao=="gravar") AND (strlen($defeito_constatado)==0)){

		if ($login_fabrica == 157) {
			$sql = "SELECT defeito_constatado FROM tbl_defeito_constatado WHERE codigo='".trim($_POST["codigo"])."' and fabrica = $login_fabrica ";
			$res = pg_query($con,$sql);
			if(pg_num_rows($res) > 0){
				$msg_erro = "Código do Defeito Constatado, já cadastrado anteriormente.";
			}
		}

		if(strlen($msg_erro)==0){
			# hd 22332
			if ($login_fabrica == 30 or $login_fabrica == 43 or $login_fabrica == 94 ){
				$sql = "INSERT INTO tbl_defeito_constatado (
								descricao,
								codigo,
								ativo,
								lancar_peca,";
								if ($login_fabrica == 30) {
									$sql .= "mao_de_obra,
									lista_garantia,
									versao_lista,";
								}
								else if( $login_fabrica == 94) {
									$sql .="
										mao_de_obra,
										orientacao,";
								}
								$sql .="admin,
								data_atualizacao,
								fabrica";
								if ($login_fabrica == 30) {
									$sql .= ",esmaltec_item_servico";
								}
								
							$sql .="
							) VALUES (
								'$descricao',
								$codigo,
								'$ativo',
								'$lancar_peca',";
							if ($login_fabrica == 30) {
								$sql .= "$mao_de_obra,
								'$lista_garantia',
								'$versao_lista',";
							}
							else if( $login_fabrica == 94) {
								$sql .=	"$mao_de_obra,'$orientacao',";
							}
							$sql .="
								$login_admin,
								current_timestamp,
								$login_fabrica";
								if ($login_fabrica == 30) {
									$sql .= ",$item_servico";
								}
							$sql .=");";
			}else{
				$sql = "INSERT INTO tbl_defeito_constatado (
									descricao,
									codigo,
									ativo,
									lancar_peca,
									defeito_constatado_grupo,
									fabrica";
									if (in_array($login_fabrica,array(50,108,111))) { //HD 733415
										$sql .= ",lancar_peca";
									}
							$sql .="
								) VALUES (
									'$descricao',
									$codigo,
									'$ativo',
									'$lancar_peca',
									$defeito_constatado_grupo,
									$login_fabrica";
									if (in_array($login_fabrica,array(50,108,111))) { //HD 733415
										$sql .= ",$lancar_peca";
									}
							$sql .="
								);";
			}
			$res = pg_exec ($con,$sql);
			$msg_erro = pg_errormessage($con);
			//echo nl2br($sql);
 			if(strlen($msg_erro)==0){header ("Location: $PHP_SELF?msg=Gravado com Sucesso");}
		}
	}
	if(($btn_acao=="gravar") AND (strlen($defeito_constatado)>0)){
		# hd 22332
		if ($login_fabrica == 157) {
			$sql = "SELECT defeito_constatado FROM tbl_defeito_constatado WHERE codigo='".trim($_POST["codigo"])."' AND defeito_constatado <> {$defeito_constatado} and fabrica = $login_fabrica ";
			$res = pg_query($con,$sql);
			if(pg_num_rows($res) > 0){
				$msg_erro = "Código do Defeito Constatado, já cadastrado anteriormente.";
			}
		}
		if(strlen($msg_erro)==0){
			if ($login_fabrica == 30 or $login_fabrica == 43 or $login_fabrica == 94){
					$sql = "UPDATE tbl_defeito_constatado SET
						descricao= '$descricao',";
						if($login_fabrica != 94)
							$sql .= "codigo= $codigo,";
						if($login_fabrica == 30 && $_SERVER["SERVER_NAME"] != "conquistar.telecontrol.com.br"){
							$sql .="
							mao_de_obra= $mao_de_obra,
							lista_garantia='$lista_garantia',
							versao_lista='$versao_lista',";
						}
						else if( $login_fabrica == 94) {
							$sql .="
							mao_de_obra= $mao_de_obra,
							orientacao = '$orientacao',";
						}
						$sql .="
						admin=$login_admin,
						data_atualizacao=current_timestamp,
						ativo= '$ativo',
						lancar_peca='$lancar_peca'";
						if ($login_fabrica == 30) {
							$sql .= ",esmaltec_item_servico = $item_servico";
						}
				$sql .= "WHERE  tbl_defeito_constatado.fabrica            = $login_fabrica
				AND    tbl_defeito_constatado.defeito_constatado = $defeito_constatado";
			}else{
					$sql = "UPDATE tbl_defeito_constatado SET
						descricao= '$descricao',
						codigo= $codigo,
					ativo= '$ativo' ";
					if (in_array($login_fabrica,array(50,51,81,91,108,111,114))) { //HD 733415
						$sql .= ",lancar_peca = '$lancar_peca'";
					}
					if($login_fabrica == 52){
						$sql .= " ,defeito_constatado_grupo = $defeito_constatado_grupo ";
					}
				$sql .= " WHERE  tbl_defeito_constatado.fabrica            = $login_fabrica
				AND    tbl_defeito_constatado.defeito_constatado = $defeito_constatado";
			}

			$res = @pg_exec($con,$sql);
			$msg_erro = pg_errormessage($con);
			if( empty($msg_erro) )	
				header ("Location: $PHP_SELF?msg=Atualizado com Sucesso!");
	// 		if(strlen($msg_erro)==0){$msg_erro="Alterado com sucesso!";}
		}
	}
	if(($btn_acao=="deletar") AND (strlen($defeito_constatado)>0)){
		$sql = "DELETE FROM tbl_defeito_constatado
				WHERE  tbl_defeito_constatado.fabrica            = $login_fabrica
				AND    tbl_defeito_constatado.defeito_constatado = $defeito_constatado;";
		$res = @pg_exec ($con,$sql);
		$msg_erro = pg_errormessage($con);
		# HD 23943 - Francisco Ambrozio - comentei o Location para que seja exibido o erro
		//header ("Location: $PHP_SELF?msg_erro=$msg_erro");

		if (strpos ($msg_erro,'tbl_defeito_constatado') > 0) $msg_erro = "Este defeito constatado não pode ser excluido";
		if (strpos ($msg_erro,'defeito_constatado_fk') > 0) $msg_erro = "Este defeito constatado não pode ser excluido";


// 		if(strlen($msg_erro)==0){$msg_erro="Apagado com sucesso!";}
	}

}

$msg = $_GET['msg'];
$layout_menu = "cadastro";
$title = "CADASTRO DE DEFEITOS CONSTATADOS";
include 'cabecalho.php';
?>

<style type="text/css">

input {
background-color: #ededed;
font: 12px verdana;
color:#363738;
border:1px solid #969696;
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

.subtitulo{

color: #7092BE
}

table.tabela tr td{
font-family: verdana;
font-size: 11px;
border-collapse: collapse;
border:1px solid #596d9b;
}

.sucesso{
    background-color:#008000;
    font: bold 14px "Arial";
    color:#FFFFFF;
    text-align:center;
}

.texto_avulso{
    font: 14px Arial; color: rgb(89, 109, 155);
    background-color: #d9e2ef;
    text-align: center;
    width:700px;
    margin: 0 auto;
    border-collapse: collapse;
    border:1px solid #596d9b;
}
</style>
<?php 
	if( $login_fabrica == 101 ) { // HD 677430 
		$sql= "SELECT descricao, defeito_constatado
			   FROM tbl_defeito_constatado
			   WHERE fabrica = $login_fabrica
			   AND orientacao IS TRUE";
		$res = pg_query($con,$sql);
		if (pg_num_rows($res) ) {
			for($i=0;$i<pg_num_rows($res); $i++ ) {
				$defeitos_orientacao[] = pg_result($res,$i,0);
				$defeitos_orientacao_cons[] = pg_result($res,$i,1);
			}
			$defeitos_orientacao = implode (', ',$defeitos_orientacao);
?>
			<div class="texto_avulso">
				Para o(s) Defeito(s) Constatado(s) <b><?=$defeitos_orientacao ?></b> será utilizada Mão de Obra Diferenciada, conforme cadastrado no cadastro de Produtos
			</div>
<?php 
		}
	} //FIM HD 677430
?>
<?php
echo "<form name='frm_defeito_constatado' method='post' action='$PHP_SELF'><BR>";
echo "<input type='hidden' name='defeito_constatado' value='$defeito_constatado'>";
echo "<table width='700px' align='center' cellpadding='3' cellspacing='3' class='formulario'>";
if (strlen($msg_erro) > 0) {
	echo "<center><div class='msg_erro' style='width:700px;'>";
	echo $msg_erro;
	echo "</div></center>";
}

if (strlen($msg) > 0) {
	echo "<center><div class='sucesso' style='width:700px;'>";
	echo $msg;
	echo "</div></center>";
}

if (in_array($login_fabrica,array(50,51,81,91,94,108,111,114,123,125))){
	$colspan = '5';
}else{	
	$colspan = '4';
}

echo "<tr>";
echo "<td class='titulo_tabela' colspan='$colspan'>Cadastro</td>";
echo "</tr>";

echo "<tr>";
echo "<td width='10%'>&nbsp;</td>";
echo "<td align='left' width='120px'>"; if($login_fabrica==35) echo"*";
		echo "Código<br /><input type='text' name='codigo' value='$codigo' size='12' maxlength='20' />
	  </td>
	  <td width='215px' align='left'>
		Descrição * <br />
		<input type='text' name='descricao' value='$descricao' size='30' maxlength='100' />
	  </td>";
if ($login_fabrica == 101 && isset($defeitos_orientacao_cons) && in_array($defeito_constatado, $defeitos_orientacao_cons) ) {
	
	$disabled = "disabled";
	echo "<input type='hidden' class='frm' name='ativo' value='t' />";
}
else {
	echo "
	  <td align='left'>
		Ativo<br />
		<input type='checkbox' class='frm' name='ativo'"; if ($ativo == 't' ) echo " checked "; echo " value='t' /> 
	  </td>
	 ";
}
if ($login_fabrica == 94) {

	$orientacao_check = ($orientacao == 't') ? 'checked' : '';

	echo '<td>
			Orientação<br />
			<input type="checkbox" class="frm" name="orientacao" value="t" '.$orientacao_check.' />
		  </td>';

}
//733415
if (in_array($login_fabrica,array(50,51,81,91,108,111,114,123,125))){
	$title_lancar_peca = "Marque este para definir se para este defeito constatado irá ser obrigado lançar peça no cadastro de OS";
	$checked = ($lancar_peca == 't') ? 'CHECKED' : null;
	echo "
	  <td align='left'>
	  	<label title='$title_lancar_peca' for='lancar_peca'>
	  		Lançar peça
	  	</label>
	  	<br />
	  	<input type='checkbox' name='lancar_peca' id='lancar_peca' value='t' class='frm' title='$title_lancar_peca' $checked />
	  </td>
	
	";
	
}
if ($login_fabrica ==52) {

	echo "<tr><td>&nbsp;</td><td align='left'>Grupo*&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
}
if ($login_fabrica == 52) {
	echo "<select name='defeito_constatado_grupo' class='frm'>";
	echo "<option></option>";
	$sql = "SELECT grupo_codigo,descricao,defeito_constatado_grupo from tbl_defeito_constatado_grupo where fabrica = $login_fabrica";
	$res = pg_exec($con,$sql);
	unset($selected);
	for ($i=0;$i<pg_num_rows($res);$i++) {
		$grupo_codigo = pg_result($res,$i,grupo_codigo);
		$descricao = pg_result($res,$i,descricao);
		$xdefeito_constatado_grupo = pg_result($res,$i,defeito_constatado_grupo);

	echo "<option value='$xdefeito_constatado_grupo'"; if ($defeito_constatado_grupo == $xdefeito_constatado_grupo) {
		echo "SELECTED";
	}
	echo ">$grupo_codigo-$descricao</option>";
	}
	echo "</select></td></tr>";
}

echo "</tr>";

# HD 23943 - Francisco Ambrozio (23/7/08) - para Esmaltec, os campos "Mão de obra", "Tabela de Preços",
#   e "Versão da Tabela" devem ser exibidos
if ($login_fabrica == 43 or ($login_fabrica == 30 && $_SERVER["SERVER_NAME"] != "conquistar.telecontrol.com.br") or $login_fabrica == 94){
	echo "<tr>";
	echo "<td>&nbsp;</td>";
	echo "<td align='left'>
			Mão de Obra *<br />
			<input type='text' name='mao_de_obra' value='$mao_de_obra' size='12' maxlength='50'>
		 </td>";
	if($login_fabrica == 30) {
		echo "<td align='left' nowrap>&nbsp;&nbsp;&nbsp;&nbsp;Tabela de Preço</td>";
		echo "<td align='left'><input type='text' name='lista_garantia' value='$lista_garantia' size='3' maxlength='20'></td>";
		echo "<td align='left' nowrap>Versão da Tabela
		<input type='text' name='versao_lista' value='$versao_lista' size='3' maxlength='20'></td>";
	}
	echo "</tr>";
}

//HD 354959 Início
	if($login_fabrica == 30){
		echo "<tr>";
			echo "<td width='50'>&nbsp;</td>";
			echo "<td align='left'>";
				echo "Item de Serviço <br />";
				echo "<select name='item_servico' class='frm'>";
				$sqlItem = "SELECT esmaltec_item_servico, descricao FROM tbl_esmaltec_item_servico WHERE ativo = 't'";
				$resItem = pg_exec($con,$sqlItem);
				$total = pg_numrows($resItem);
				if($total == 0){
					echo "<option value=''>Não há Item Cadastrado</option>";
				}
				else{
					for($i = 0; $i < $total; $i++){
						$codigoItem     = pg_result($resItem,$i,esmaltec_item_servico);
						$descricaoItem  = pg_result($resItem,$i,descricao);

						echo "<option value='$codigoItem'";
						
						if($codigoItem == $item_servico) echo 'selected';
						
						echo ">$descricaoItem</option>";
					}
				}
				echo "</select>";
			echo "</td>";
		echo "</tr>";
	}
	//HD 354959 Fim

echo "<TR>";
?>
<TD align='center' colspan='6' style='padding:10px 0 10px 0;'>
<br />
<input type='hidden' name='btn_acao' value=''>
<input type='button' value='Gravar' border="0"  onclick="javascript: if (document.frm_defeito_constatado.btn_acao.value == '' ) { document.frm_defeito_constatado.btn_acao.value='gravar' ; document.frm_defeito_constatado.submit() } else { alert ('Aguarde submissão') }" alt="Gravar formulário" style="cursor: pointer;">
<input type='button' value='Apagar' <?=$disabled; ?> border="0"  onclick="javascript: if (document.frm_defeito_constatado.btn_acao.value == '' ) { document.frm_defeito_constatado.btn_acao.value='deletar' ; document.frm_defeito_constatado.submit() } else { alert ('Aguarde submissão') }" alt="Apagar Linha" style="cursor: pointer;">
<input type='button' value='Limpar' border="0"  onclick="javascript: if (document.frm_defeito_constatado.btn_acao.value == '' ) { document.frm_defeito_constatado.btn_acao.value='reset' ; window.location='<? echo $PHP_SELF ?>' } else { alert ('Aguarde submissão') }" alt="Limpar campos" style="cursor: pointer;">
</center>
</td>
<?
echo "</TR>";
echo "</TABLE>";
echo "</form>";
echo "<br>";
echo "<div class='texto_avulso'>Para efetuar alterações, clique na descrição do defeito constatado.</div>";
echo "<br>";
# hd 22332
if ($login_fabrica == 30 or $login_fabrica == 43 or $login_fabrica == 94){
	$sql = "SELECT  tbl_defeito_constatado.defeito_constatado,
				tbl_defeito_constatado.codigo           ,
				tbl_defeito_constatado.descricao        ,
				tbl_defeito_constatado.mao_de_obra		,
				tbl_defeito_constatado.lista_garantia	,
				tbl_defeito_constatado.versao_lista	    ,
				tbl_defeito_constatado.ativo            ,
				tbl_defeito_constatado.lancar_peca      ,
				tbl_esmaltec_item_servico.descricao as item_servico,
				CASE WHEN tbl_defeito_constatado.orientacao IS TRUE THEN 'Sim' ELSE 'Não' END AS orientacao
		FROM    tbl_defeito_constatado
		LEFT JOIN tbl_linha USING (linha)
		LEFT JOIN tbl_familia USING (familia)
		LEFT JOIN tbl_esmaltec_item_servico USING(esmaltec_item_servico)
		WHERE   tbl_defeito_constatado.fabrica = $login_fabrica
		ORDER BY tbl_defeito_constatado.linha, tbl_defeito_constatado.familia, tbl_defeito_constatado.descricao;";
}else{
	$sql = "SELECT  tbl_defeito_constatado.defeito_constatado,
				tbl_defeito_constatado.codigo           ,
				tbl_defeito_constatado.descricao        ,
				tbl_defeito_constatado_grupo.descricao  as grupo_descricao      ,
				tbl_defeito_constatado.lancar_peca      ,
				tbl_defeito_constatado.ativo
		FROM    tbl_defeito_constatado
		LEFT JOIN tbl_defeito_constatado_grupo USING(defeito_constatado_grupo)
		LEFT JOIN tbl_linha USING (linha)
		LEFT JOIN tbl_familia USING (familia)
		WHERE   tbl_defeito_constatado.fabrica = $login_fabrica";

	if($login_fabrica==52){
			$sql .= "ORDER BY tbl_defeito_constatado.linha, tbl_defeito_constatado.familia, tbl_defeito_constatado_grupo.descricao,tbl_defeito_constatado.descricao ;";
	}else if  (in_array($login_fabrica,array(108,111))){
		$sql .= "ORDER BY tbl_defeito_constatado.descricao;";
	}else{
		$sql .="ORDER BY tbl_defeito_constatado.linha, tbl_defeito_constatado.familia, tbl_defeito_constatado.descricao;";
	}
}

$res = pg_exec ($con,$sql);

if (pg_numrows($res) > 0) {
	
	if (in_array($login_fabrica,array(108,111))){
		$colspan = '5';
	}else{	
		$colspan = '4';
	}
	$colspan = ($login_fabrica==94) ? 6 : $colspan;	
	echo "<table align='center' width='700px' border='0' class='formulario' cellpadding='2' cellspacing='1'>";
	echo "<tr bgcolor='#D9E2EF'>";
	# HD 23943
	if ($login_fabrica == 30 or $login_fabrica == 43) echo "<td align='center' class='titulo_tabela' colspan='6'>Relação de Defeitos Constatados</td>";
	else echo "<td nowrap class='titulo_tabela' colspan='$colspan'>Relação de Defeitos Constatados</td>";
	echo "</tr>";
	echo "<tr class='titulo_coluna'>";
	echo "<td nowrap>Ativo</td>";
	echo "<td nowrap>Código</td>";
	echo "<td nowrap>Descrição</td>";
	if ($login_fabrica == 52) {
		echo "<td nowrap>GRUPO</td>";
	}
	if (in_array($login_fabrica,array(50,51,81,108,111,114,123,125))){
		echo "<td nowrap>Lança peça</td>";
	}
	if ($login_fabrica == 94) {
		echo '<td>Orientação</td>';
	}

	# hd 22332 - Mão de obra
	# HD 23943 - Tabela e Versão
	if ($login_fabrica == 30 or $login_fabrica == 43 or $login_fabrica == 94){
		echo "<td nowrap>Mão de Obra</td>";
		if($login_fabrica == 30) {
			echo "<td nowrap>Tabela de Preço</td>";
			echo "<td nowrap>Versão da Tabela</td>";
		}
	}

	//HD 354959 Início
	if ($login_fabrica == 30) {
		echo "<td>Item de Servico</td>";
	}
	//	HD 354959 Fim
	echo "</tr>";
	for ($x = 0 ; $x < pg_numrows($res) ; $x++){
		$defeito_constatado   = trim(pg_result($res,$x,defeito_constatado));
		$descricao            = trim(pg_result($res,$x,descricao));
		$grupo_descricao      = trim(@pg_result($res,$x,grupo_descricao));
		$codigo               = trim(pg_result($res,$x,codigo));
		$ativo                = trim(pg_result($res,$x,ativo));
		if (in_array($login_fabrica,array(50,51,81,108,111,114,123,125))){
			$lancar_peca  = pg_result($res,$x,'lancar_peca');
			
			$lancar_peca  = ($lancar_peca == 't') ? 'SIM' : 'NÃO'; 
		}
		$xlancar_peca          = trim(pg_result($res,$x,lancar_peca));
		# hd 22332 - Mão de obra
		# HD 23943 - Tabela e Versão
		if ($login_fabrica == 30 or $login_fabrica == 43 or $login_fabrica == 94){
			$mao_de_obra		  = trim(pg_result($res,$x,mao_de_obra));
			$lista_garantia		  = trim(pg_result($res,$x,lista_garantia));
			$versao_lista		  = trim(pg_result($res,$x,versao_lista));
			$item_servico		  = trim(pg_result($res,$x,item_servico));
		}
		if($ativo=='t'){ $ativo="Sim"; }else{$ativo="<font color='#660000'>Não</font>";}
		if($xlancar_peca=='t'){ $xlancar_peca="Sim"; }else{$xlancar_peca="<font color='#660000'>Não</font>";}
		$cor = ($x % 2 == 0) ? "#F7F5F0" : "#F1F4FA";
		echo "<tr bgcolor='$cor' align='center'>";
		echo "<td nowrap align='center'>$ativo</td>";
		echo "<td nowrap align='center'><a href='$PHP_SELF?defeito_constatado=$defeito_constatado'>$codigo</a></td>";
		echo "<td nowrap align='left'><a href='$PHP_SELF?defeito_constatado=$defeito_constatado'>$descricao</a></td>";
		if ($login_fabrica == 52) {
		echo "<td nowrap align='center'><a href='$PHP_SELF?defeito_constatado=$defeito_constatado'>$grupo_descricao</a></td>";
		}
		if (in_array($login_fabrica,array(50,51,81,108,111,114,123,125))){
			echo "<td nowrap align='center'>$lancar_peca</td>";
		}
		if ($login_fabrica == 94) {
			echo '<td>&nbsp; '.pg_result($res,$x,'orientacao').'</td>';
		}	
		# hd 22332 - Mão de obra
		# HD 23943 - Tabela e Versão
		if ($login_fabrica == 30 or $login_fabrica == 43 or $login_fabrica == 94){
			echo "<td nowrap align='center'><a href='$PHP_SELF?defeito_constatado=$defeito_constatado'>R$ ".number_format($mao_de_obra,2,',','.'). "</a></td>";
			if($login_fabrica == 30) {
				echo "<td nowrap align='center'><a href='$PHP_SELF?defeito_constatado=$defeito_constatado'>$lista_garantia</a></td>";
				echo "<td nowrap align='center'><a href='$PHP_SELF?defeito_constatado=$defeito_constatado'>$versao_lista</a></td>";
			}
		}

		//HD 354959 Início
		if ($login_fabrica == 30) {
			echo "<td>$item_servico</td>";
		}
		//	HD 354959 Fim
		echo "</tr>";
	}
	echo "</table>";
}

include "rodape.php";
?>
