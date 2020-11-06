<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';
include 'funcoes.php';

$layout_menu = "os";
$title = "Fechamento de OS REVENDA";
include "cabecalho.php";

if (strlen($_POST["btn_acao"]) > 0) $btn_acao = strtoupper($_POST["btn_acao"]);

if ($btn_acao){

	if (strlen($_POST["nota_fiscal_saida"]) > 0) $nota_fiscal_saida = trim($_POST["nota_fiscal_saida"]);
	if (strlen($_POST["data_nf_saida"]) > 0)     $data_nf_saida = trim($_POST["data_nf_saida"]);
	if (strlen($_POST["qtde_os"]) > 0)           $qtde_os           =($_POST["qtde_os"]);
	if (strlen($_POST["sua_os"]) > 0)            $sua_os            = trim($_POST["sua_os"]);
//	echo "==>> $nota_fiscal_saida<==========";
	if(strlen($nota_fiscal_saida)==0){/*HD 3176*/
		$msg_erro = "Digite a nota fiscal Lorenzetti ";
	}else{
		if(strlen($nota_fiscal_saida)<>6 and strlen($nota_fiscal_saida)<>7){
			$msg_erro = "Nota fiscal Lorenzetti inválida ";
		}
		if(!is_numeric($nota_fiscal_saida)){
			$msg_erro = "Nota fiscal Lorenzetti inválida";
		}
		# MOdificado por Fabio -19/10/2007- Chamado 6350
		// $nota_fiscal_saida <= 722594 or 
		if( $nota_fiscal_saida =="1111111" or
			$nota_fiscal_saida =="2222222" or
			$nota_fiscal_saida =="3333333" or
			$nota_fiscal_saida =="4444444" or
			$nota_fiscal_saida =="5555555" or
			$nota_fiscal_saida =="6666666" or
			$nota_fiscal_saida =="777777"  or
			$nota_fiscal_saida =="7777777" or
			$nota_fiscal_saida =="888888"  or
			$nota_fiscal_saida =="8888888" or
			$nota_fiscal_saida =="999999"  or
			$nota_fiscal_saida =="9999999"     ){
				$msg_erro = "Nota fiscal Lorenzetti inválida ";
		}
	//	if(strlen($msg_erro)==0)$msg_erro="adsas";
	}

	$X_sua_os = explode('-',$sua_os);
	$sua_os = $X_sua_os[0];
	
	if (strlen($data_nf_saida) == 0){ 
		$msg_erro = "Digite a data de fechamento.";
	}else{
		$data_nf_saida = fnc_formata_data_pg ($data_nf_saida);

		if($data_nf_saida > "'".date("Y-m-d")."'") $msg_erro = "Data de nota fiscal de saída maior que a data de hoje.";
	}
	if(strlen($msg_erro)==0){
		
		if($login_fabrica == 11){
			$msg_erro = "";
			$res = pg_exec ($con,"BEGIN TRANSACTION");
		}
		for ($i = 0 ; $i < $qtde_os ; $i++) {

			if($login_fabrica <> 11){
				$msg_erro = "";
				$res = pg_exec ($con,"BEGIN TRANSACTION");
			}

			if (strlen($_POST["os_"].$i) > 0) $os = trim($_POST["os_".$i]);

			if($login_fabrica == 11){ #HD 96191
				$sql_nf = "SELECT sua_os, finalizada FROM tbl_os
							WHERE fabrica           = $login_fabrica
							AND   posto             = $login_posto
							AND   nota_fiscal_saida = $nota_fiscal_saida
							ORDER BY finalizada";
				$res_nf = pg_exec($con,$sql_nf);
				if (pg_numrows($res_nf) > 0){
					$sua_os_nf = "";
					for ($i = 0 ; $i < pg_numrows($res_nf); $i++) {
						$sua_os_nf .= trim(pg_result($res_nf,$i,sua_os))." ";
						$finalizada_nf = trim(pg_result($res_nf,$i,finalizada));
					}
					$sql_nf = "SELECT CURRENT_TIMESTAMP - '$finalizada_nf'";
					$res_nf = pg_exec($con,$sql_nf);
					$tempo = pg_result($res_nf,0,0);
					if(substr($tempo,0,5)!="00:00"){
						$msg_erro.="Nota Fiscal já utilizada para devolução da OS ".$sua_os_nf;
					}
				}
			}

			if (strlen ($msg_erro) == 0) {
				$sql = "UPDATE tbl_os SET 
						defeito_constatado = 357                 ,
						tecnico_nome       = 'SAL'               ,
						nota_fiscal_saida  = $nota_fiscal_saida  ,
						data_nf_saida      = $data_nf_saida      ,
						data_fechamento    = CURRENT_TIMESTAMP
						WHERE os      = $os
						AND   fabrica = $login_fabrica";
				$res = pg_exec ($con,$sql);
				$msg_erro .= pg_errormessage($con) ;
			}
			if (strlen ($msg_erro) == 0) {
				$sql = "SELECT fn_finaliza_os($os, $login_fabrica)";
				$res = @pg_exec ($con,$sql);
				$msg_erro .= pg_errormessage($con) ;
			}
			if($login_fabrica <> 11){
				if (strlen ($msg_erro) == 0) {
					$res = pg_exec ($con,"COMMIT TRANSACTION");
					$msg_erro = "<center>OS Revenda <b>$sua_os</b> foi <b>FECHADA</b> com sucesso</center>";
				}else{
					$res = @pg_exec ($con,"ROLLBACK TRANSACTION");
				}
				flush();
			}
		}
		if($login_fabrica == 11){
			if (strlen ($msg_erro) == 0) {
				$res = pg_exec ($con,"COMMIT TRANSACTION");
				$msg_erro = "<center>OS Revenda <b>$sua_os</b> foi <b>FECHADA</b> com sucesso</center>";
			}else{
				$res = @pg_exec ($con,"ROLLBACK TRANSACTION");
			}
			flush();
		}
	}
		// retira palavra ERROR:
	if (strpos($msg_erro,"ERROR: ") !== false) {
		$erro = "Foi detectado o seguinte erro:<br>";
		$msg_erro = substr($msg_erro, 6);
	}

	// retira CONTEXT:
	if (strpos($msg_erro,"CONTEXT:")) {
		$x = explode('CONTEXT:',$msg_erro);
		$msg_erro = $x[0];
	}
	echo "<table bgcolor='#FF3333' width='100%'><tr><td align='center'><font color='#FFFFFF'><B>$msg_erro</b></font></td></tr></table>";
	echo "<br><a href='os_revenda_fechamento.php'><IMG SRC='imagens/btn_continuar.gif' ALT='Volta para fechamento'></a>";
	exit;
}

?>

<style type="text/css">
.Titulo {
	text-align: center;
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #485989;
}
.Conteudo {
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
}
</style>
<script language="JavaScript">
function formataData(entrada,form,saida){
	var mycnpj = '';
	mycnpj = mycnpj + entrada;
	myrecord = saida;
	myform = form;

	if (mycnpj.length == 2){
		mycnpj = mycnpj + '/';
		window.document.forms["" + myform + ""].elements[myrecord].value = mycnpj;
	}
	if (mycnpj.length == 5){
		mycnpj = mycnpj + '/';
		window.document.forms["" + myform + ""].elements[myrecord].value = mycnpj;
	}

}
</script>
<?

if($_GET['sua_os']) $os = trim($_GET['sua_os']);
if(strlen($sua_os)==0){
	$sql = "
		SELECT DISTINCT SUBSTR(tbl_os.sua_os,1,STRPOS (tbl_os.sua_os,'-')-1) AS sua_os
		INTO TEMP tmp_revenda_fechamento_$login_posto
		FROM tbl_os 
		WHERE fabrica = $login_fabrica
		AND posto     = $login_posto
		AND consumidor_revenda = 'R'
		AND finalizada IS NULL
		AND sua_os LIKE '%-%';

		CREATE INDEX tmp_revenda_fechamento_OS_$login_posto ON tmp_revenda_fechamento_$login_posto(sua_os);

		SELECT tbl_os.sua_os                                              ,
			tbl_os.os                                                         ,
			tbl_os.revenda_cnpj                                               ,
			tbl_os.revenda_nome                                               ,
			TO_CHAR(tbl_os.data_abertura,'DD/MM/YYYY')   AS abertura          
		FROM tbl_os
		JOIN tmp_revenda_fechamento_$login_posto os_unica ON tbl_os.sua_os = os_unica.sua_os || '-1'
		ORDER BY sua_os";
	//echo nl2br($sql);
	$res = pg_exec ($con,$sql);

	if (@pg_numrows($res) > 0) {
		echo "<table border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#d2e4fc' width='700' align='center'>";
		echo "<tr class='Titulo'>";
		echo "<td width='100'>OS Revenda</td>";
		echo "<td>Abertura</td>";
		echo "<td>Revenda</td>";
		echo "</tr>";

		for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
			$os                 = trim(pg_result($res,$i,os));
			$sua_os             = trim(pg_result($res,$i,sua_os));
	//		$digitacao          = trim(pg_result($res,$i,digitacao));
			$abertura           = trim(pg_result($res,$i,abertura));
			$revenda_nome       = trim(pg_result($res,$i,revenda_nome));
			$revenda_cnpj       = trim(pg_result($res,$i,revenda_cnpj));
			
			$X_sua_os = explode('-',$sua_os);
			$sua_os   = $X_sua_os[0];

			if ($i % 2 == 0) {
				$cor   = "#F1F4FA";
			}else{
				$cor   = "#F7F5F0";
			}
			echo "<tr class='Conteudo'align='center'>";
			echo "<td bgcolor='$cor' ><a href='os_revenda_fechamento.php?sua_os=$sua_os'>$sua_os</a></td>";
			echo "<td bgcolor='$cor' nowrap ><acronym title='Data Abertura: $abertura' style='cursor: help;'>" . substr($abertura,0,5) . "</acronym></td>";
			echo "<td bgcolor='$cor' align='left'>$revenda_cnpj - $revenda_nome</td>";
			echo "</tr>";
		}
		echo "</table>";
	}else{ echo "<br><b>Nenhuma OS Revenda em aberto</b>";}
}else{
	if (strlen($msg_erro) > 0) {
		echo "<font face='arial' size='+1' color='#FF6633'><b>$msg_erro</b></font>";
	}

	$sql = "SELECT  tbl_os.sua_os                                                    ,
					tbl_os.os                                                        ,
					tbl_os.posto                                                     ,
					TO_CHAR(tbl_os.data_abertura,'DD/MM/YYYY')   AS abertura         , 
					tbl_os.qtde_produtos                                             ,
					tbl_produto.descricao                                            ,
					tbl_produto.referencia                                           ,
					tbl_os.revenda_cnpj                                              ,
					tbl_os.revenda_nome                                              ,
					tbl_os.revenda_fone                                              ,
					tbl_os.nota_fiscal                                              ,
					TO_CHAR(tbl_os.data_nf,'DD/MM/YYYY')   AS data_nf     
			FROM tbl_os
			JOIN tbl_produto USING(produto)
			WHERE fabrica = $login_fabrica
			AND   posto   = $login_posto
			AND consumidor_revenda = 'R'
			AND finalizada IS NULL
			AND sua_os LIKE '$sua_os-%'";
	$res = pg_exec ($con,$sql);

	if (pg_numrows($res) > 0) {	
		$abertura           = trim(pg_result($res,0,abertura));
		$revenda_nome       = trim(pg_result($res,0,revenda_nome));
		$revenda_cnpj       = trim(pg_result($res,0,revenda_cnpj));
		$revenda_fone       = trim(pg_result($res,0,revenda_fone));
		$data_nf            = trim(pg_result($res,0,data_nf));
		$nota_fiscal        = trim(pg_result($res,0,nota_fiscal));

		echo "<table border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#d2e4fc' width='700' align='center' >";
		echo "<tr class='Titulo' align='center' bgcolor='#D9E2EF'>";
		echo "<td colspan='3' >INFORMAÇÕES DA OS REVENDA</td>";
		echo "</tr>";
		echo "<tr class='Conteudo' align='center' bgcolor='#D9E2EF'>";
		echo "<td>Revenda: <b>$revenda_nome</b></td>";
		echo "<td>CNPJ: <b>$revenda_cnpj</b></td>";
		echo "<td>Fone: <b>$revenda_fone</b></td>";
		echo "</tr>";
		echo "<tr class='Conteudo' align='center' bgcolor='#D9E2EF'>";
		echo "<td>Data Abertura: <b>$abertura</b></td>";
		echo "<td>Nota Fiscal: <b>$nota_fiscal</b></td>";
		echo "<td>Data Nota: <b>$data_nf</b></td>";
		echo "</tr>";
		echo "</table>";

		echo "<FORM METHOD='POST' ACTION='$PHP_SELF' name='frm_os_fechamento'>";
		echo "<table border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#d2e4fc' width='700' align='center'>";
		echo "<tr class='Titulo'>";
		echo "<td width='100'>OS Revenda</td>";
		echo "<td>Produto</td>";
		echo "<td>Quantidade de Produtos</td>";
		echo "</tr>";


		for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
			$os                 = trim(pg_result($res,$i,os));
			$sua_os             = trim(pg_result($res,$i,sua_os));
	//		$digitacao          = trim(pg_result($res,$i,digitacao));
			$descricao          = trim(pg_result($res,$i,descricao));
			$referencia         = trim(pg_result($res,$i,referencia));
			$qtde_produtos      = trim(pg_result($res,$i,qtde_produtos));
			
			$qtde_os = pg_numrows($res);

			if ($i % 2 == 0) {
				$cor   = "#F1F4FA";
			}else{
				$cor   = "#F7F5F0";
			}
			echo "<tr class='Conteudo'align='center'>";
			echo "<td bgcolor='$cor' >$sua_os</td>";
			echo "<td bgcolor='$cor' nowrap >$referencia -  $descricao</td>";
			echo "<td bgcolor='$cor'>$qtde_produtos</td>";
			echo "<INPUT TYPE='hidden' NAME='os_$i' VALUE='$os'>";
			echo "</tr>";

		}
		echo "</table>";
		echo "<table border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#d2e4fc' width='700' align='center' >";
		
		echo "<tr class='Titulo' align='center' bgcolor='#D9E2EF'>";
		echo "<td colspan='2' >PREENCHA OS CAMPOS ABAIXO PARA FECHAR AS OS'S DE REVENDA ACIMA</td>";
		echo "</tr>";
		echo "<tr class='Conteudo' align='center' bgcolor='#D9E2EF'>";
		echo"<td>N.F. Lorenzetti&nbsp;<INPUT TYPE='text' NAME='nota_fiscal_saida' size='8' maxlength='7'></td>";
		echo"<td>Data Fechamento&nbsp;<INPUT TYPE='text' NAME='data_nf_saida444' size='15' maxlength='10' onKeyUp=\"formataData(this.value,'frm_os_fechamento','data_nf_saida')\" value='"; 
		echo date("d/m/Y");
		echo "' disabled></td>";
		echo "<INPUT TYPE='hidden' NAME='data_nf_saida' value='";
		echo date("d/m/Y");
		echo "'>";
		echo "</tr>";
		echo "<tr align='center' bgcolor='#D9E2EF'>";
		echo "<td colspan='2' ><INPUT TYPE='submit' VALUE='FECHAR' name='btn_acao'></td>";
		echo "</tr>";
		echo "</table>";
		echo "<INPUT TYPE='hidden' NAME='qtde_os' VALUE='$qtde_os'>";
		echo "<INPUT TYPE='hidden' NAME='sua_os' VALUE='$sua_os'>";
		echo "</FORM>";

	}else{ echo "<br><b>Nenhuma OS Revenda em encontrada</b>";}

}
include "rodape.php";
?>