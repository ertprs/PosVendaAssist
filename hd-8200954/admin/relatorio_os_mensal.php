<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

#Para a rotina automatica - Gustavo - HD 89349
$gera_automatico = trim($_GET["gera_automatico"]);

if ($gera_automatico != 'automatico'){
	include "autentica_admin.php";
}

include "gera_relatorio_pararelo_include.php";

$title = "RELATÓRIO DE OS";
$layout_menu = "gerencia";

if (strlen($_POST["btn_acao"]) > 0 ) $btn_acao = strtoupper($_POST["btn_acao"]);
if (strlen($_GET["btn_acao"]) > 0 )  $btn_acao = strtoupper($_GET["btn_acao"]);

if (strlen($btn_acao) > 0) {

	if(strlen($_POST['mes'])>0) $mes = $_POST['mes'];
	else                        $mes = $_GET['mes'];

	if(strlen($_POST['ano'])>0) $ano = $_POST['ano'];
	else                        $ano = $_GET['ano'];

	if(strlen($mes)==0){
		$msg_erro .= "Informe o mês para pesquisa.";
	}

	if(strlen($ano)==0){
		$msg_erro .= "Informe o ano para pesquisa.";
	}

	if (strlen($mes) > 0 AND strlen($msg_erro)==0) {
		$xdata_inicial = date("Y-m-01 00:00:00", mktime(0, 0, 0, $mes, 1, $ano));
		$xdata_final   = date("Y-m-t 23:59:59", mktime(0, 0, 0, $mes, 1, $ano));
	}
}

$meses = array(1 => "Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro");

?>
<style type="text/css">
.table_line {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	border: 0px solid;
	background-color: #D9E2EF
}

.Erro{
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 12px;
	color:#CC3300;
	font-weight: bold;
	background-color:#FFFFFF;
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

.sucesso{
	width:600px;
	text-align:left;
	margin:0 auto;
	font-size:10px;
	background-color:#E3FBE4;
	border:1px solid #0F6A13;
	color:#07340A;
	padding:5px;
	font-size:13px;
}

</style>

<?

include "cabecalho.php";
?>

<script language="javascript" src="js/cal2.js"></script>
<script language="javascript" src="js/cal_conf2.js"></script>

<br>

<?
if (strlen($btn_acao) > 0 && strlen($msg_erro) == 0) {
	include "gera_relatorio_pararelo.php";
}

if ($gera_automatico != 'automatico' and strlen($msg_erro)==0){
	include "gera_relatorio_pararelo_verifica.php";
}
?>

<? if (strlen($msg_erro) > 0){ ?>
<table width="730" border="0" cellpadding="2" cellspacing="2" align='center'>
<tr>
	<td align="center" class='error'>
			<? echo $msg_erro ?>

	</td>
</tr>
</table>
<br>
<? } ?>

<br>
<form name="frm_pesquisa" method="post" action="<? echo $PHP_SELF ?>">
	<table width="400" align="center" border="0" cellspacing="0" cellpadding="2">
		<tr class="Titulo" height="30">
			<td align="center">Selecione os parâmetros para a pesquisa.</td>
		</tr>
	</table>

	<table width="400" align="center" border="0" cellspacing="0" cellpadding="0">
			<tr class="Conteudo" bgcolor="#D9E2EF" align='center'>
				<td>Mês</td>
				<td>Ano</td>
			</tr>
			<tr class="Conteudo" bgcolor="#D9E2EF" align='center'>
				<td>
					<select name="mes" size="1" class="frm">
					<option value=''></option>
					<?
					for ($i = 1 ; $i <= count($meses) ; $i++) {
						echo "<option value='$i'";
						if ($mes == $i) echo " selected";
						echo ">" . $meses[$i] . "</option>";
					}
					?>
					</select>
				</td>
				<td>
					<select name="ano" size="1" class="frm">
					<option value=''></option>
					<?
					for ($i = 2003 ; $i <= date("Y") ; $i++) {
						echo "<option value='$i'";
						if ($ano == $i) echo " selected";
						echo ">$i</option>";
					}
					?>
					</select>
				</td>
			</tr>
			<tr bgcolor="#D9E2EF">
				<td colspan="2"><br></td>
			</tr>
			<tr bgcolor="#D9E2EF">
				<td colspan="2">
				<input type='hidden' name='btn_acao' value='0'>
				<img src="imagens_admin/btn_pesquisar_400.gif" border="0" onclick="javascript: if ( document.frm_pesquisa.btn_acao.value == '0' ) { document.frm_pesquisa.btn_acao.value='1'; document.frm_pesquisa.submit() ; } else { alert ('aguarde submissão da os...'); }" style="cursor:pointer " alt='Clique aqui para pesquisar'></td>
			</tr>
	</table>
</form>

<?

if (strlen($btn_acao) > 0 AND strlen($msg_erro)==0) {
		$sql = "SELECT tbl_os_extra.os                                         ,
				tbl_extrato.extrato                                            ,
				to_char(tbl_extrato.data_geracao, 'dd/mm/yyyy') AS data_geracao
				INTO TEMP tmp_extrato_mensal_$login_admin
				FROM tbl_os_extra
				JOIN tbl_extrato ON tbl_extrato. extrato = tbl_os_extra.extrato AND tbl_extrato.fabrica = $login_fabrica
				WHERE tbl_extrato.data_geracao BETWEEN '$xdata_inicial' AND '$xdata_final'
				AND   tbl_os_extra.extrato IS NOT NULL;

				CREATE INDEX tmp_extrato_mensal_OS_$login_admin ON tmp_extrato_mensal_$login_admin(os);

				SELECT tbl_posto_fabrica.codigo_posto::text  AS posto_codigo          ,
				tbl_posto.nome                               AS posto_nome            ,
				tbl_os.sua_os                                                         ,
				to_char(tbl_os.data_digitacao,'DD/MM/YYYY')  AS data_digitacao        ,
				to_char(tbl_os.data_abertura,'DD/MM/YYYY')   AS data_abertura         ,
				to_char(tbl_os.data_fechamento,'DD/MM/YYYY') AS data_fechamento       ,
				to_char(tbl_os.finalizada,'DD/MM/YYYY')      AS finalizada            ,
				tbl_os.produto                                                        ,
				tbl_os.serie                                                          ,
				tbl_os.consumidor_nome                                                ,
				tbl_os.consumidor_fone                                                ,
				tbl_os.consumidor_cpf                                                 ,
				tbl_os.consumidor_endereco                                            ,
				tbl_os.consumidor_numero                                              ,
				tbl_os.consumidor_complemento                                         ,
				tbl_os.consumidor_bairro                                              ,
				tbl_os.consumidor_cep                                                 ,
				tbl_os.consumidor_cidade                                              ,
				tbl_os.consumidor_estado                                              ,
				tbl_os.consumidor_email                                               ,
				tbl_os.consumidor_revenda                                             ,
				tbl_os.defeito_reclamado_descricao           AS defeito_reclamado     ,
				tbl_defeito_constatado.descricao             AS defeito_constatado    ,
				tbl_os.solucao_os                                                     ,
				tbl_os.nota_fiscal                                                    ,
				to_char(tbl_os.data_nf,'DD/MM/YYYY')         AS data_nf               ,
				tbl_os.revenda_nome                                                   ,
				tbl_os.revenda_cnpj                                                   ,
				tbl_os.aparencia_produto                                              ,
				tbl_os.acessorios                                                     ,
				tmp_extrato_mensal_$login_admin.extrato                               ,
				tmp_extrato_mensal_$login_admin.data_geracao                          ,
				tbl_os_item.peca                                                      ,
				tbl_os_item.qtde                                  AS peca_qtde        ,
				to_char(tbl_os_item.digitacao_item, 'dd/mm/yyyy') AS digitacao_item   ,
				tbl_defeito.descricao                             AS defeito_descricao,
				tbl_servico_realizado.descricao                   AS servico_realizado
				INTO TEMP tmp_os_mensal_$login_admin
				FROM tbl_os
				JOIN tmp_extrato_mensal_$login_admin USING(os)
				JOIN tbl_posto         ON tbl_posto.posto         = tbl_os.posto
				JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_os.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
				JOIN tbl_defeito_constatado ON tbl_os.defeito_constatado = tbl_defeito_constatado.defeito_constatado AND tbl_defeito_constatado.fabrica = $login_fabrica
				LEFT JOIN tbl_os_produto ON tbl_os_produto.os      = tbl_os.os
				LEFT JOIN tbl_os_item    ON tbl_os_item.os_produto = tbl_os_produto.os_produto
				LEFT JOIN tbl_servico_realizado ON tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado AND tbl_servico_realizado.fabrica = $login_fabrica
				LEFT JOIN tbl_defeito ON tbl_defeito.defeito = tbl_os_item.defeito AND tbl_defeito.fabrica = $login_fabrica
				WHERE tbl_os.fabrica = $login_fabrica;

				CREATE INDEX tmp_os_mensal_PECA_$login_admin ON tmp_os_mensal_$login_admin(peca);

				CREATE INDEX tmp_os_mensal_PRODUTO_$login_admin ON tmp_os_mensal_$login_admin(produto);

				CREATE INDEX tmp_os_mensal_SOLUCAO_$login_admin ON tmp_os_mensal_$login_admin(solucao_os);

				SELECT posto_codigo                   ,
				posto_nome                            ,
				sua_os                                ,
				data_digitacao                        ,
				data_abertura                         ,
				data_fechamento                       ,
				finalizada                            ,
				tbl_produto.referencia                ,
				tbl_produto.descricao                 ,
				serie                                 ,
				consumidor_nome                       ,
				consumidor_fone                       ,
				consumidor_cpf                        ,
				consumidor_endereco                   ,
				consumidor_numero                     ,
				consumidor_complemento                ,
				consumidor_bairro                     ,
				consumidor_cep                        ,
				consumidor_cidade                     ,
				consumidor_estado                     ,
				consumidor_email                      ,
				consumidor_revenda                    ,
				defeito_reclamado                     ,
				defeito_constatado                    ,
				tbl_solucao.descricao AS solucao      ,
				nota_fiscal                           ,
				data_nf                               ,
				revenda_nome                          ,
				revenda_cnpj                          ,
				aparencia_produto                     ,
				acessorios                            ,
				extrato                               ,
				data_geracao                          ,
				tbl_peca.referencia AS peca_referencia,
				tbl_peca.descricao  AS peca_descricao ,
				peca_qtde                             ,
				digitacao_item                        ,
				defeito_descricao                     ,
				servico_realizado
				FROM tmp_os_mensal_$login_admin
				LEFT JOIN tbl_peca ON tbl_peca.peca = tmp_os_mensal_$login_admin.peca AND tbl_peca.fabrica = $login_fabrica
				LEFT JOIN tbl_produto ON tbl_produto.produto = tmp_os_mensal_$login_admin.produto
				LEFT JOIN tbl_solucao ON tbl_solucao.solucao = tmp_os_mensal_$login_admin.solucao_os AND tbl_solucao.fabrica = $login_fabrica";
		#echo nl2br($sql);
		$res = pg_exec($con, $sql);

		if(pg_numrows($res)>0){
			$data = date ("d-m-Y-H-i");

			$arquivo_nome     = "relatorio_os_mensal-$login_fabrica-$ano-$mes-$data.xls";
			$path             = "/www/assist/www/admin/xls/";
			$path_tmp         = "/tmp/assist/";

			$arquivo_completo     = $path.$arquivo_nome;
			$arquivo_completo_tmp = $path_tmp.$arquivo_nome;

			echo `rm $arquivo_completo_tmp `;
			echo `rm $arquivo_completo_tmp.zip `;
			echo `rm $arquivo_completo.zip `;
			echo `rm $arquivo_completo `;

			$fp = fopen ($arquivo_completo_tmp,"w");

			fputs ($fp, "Código Posto \t  Nome Posto \t OS \t Digitação \t Abertura \t  Fechamento \t Finalizada \t Produto Referência \t Produto Descrição \t Série \t Consumidor Nome \t Consumidor Fone \t Consumidor CPF \t  Consumidor Endereço \t Consumidor Número \t Consumidor Complemento \t Consumidor Bairro \t Consumidor Cep \t Consumidor Cidade \t Consumidor Estado \t Consumidor Email \t Defeito Reclamado \t Defeito Constatado \t Solução\t Nota Fiscal \t Data NF \t Revenda Nome \t Revenda CNPJ \t Aparência Produto \t Acessorios \t Peça \t Qtde \t Defeito \t Serviço Realizado \t Extrato \t Data Geração \t Consumidor Revenda \r\n");

			for($i=0; $i<pg_numrows($res); $i++){
				$posto_codigo           = pg_result($res, $i, posto_codigo);
				$posto_nome             = pg_result($res, $i, posto_nome);
				$sua_os                 = pg_result($res, $i, sua_os);
				$data_digitacao         = pg_result($res, $i, data_digitacao);
				$data_abertura          = pg_result($res, $i, data_abertura);
				$data_fechamento        = pg_result($res, $i, data_fechamento);
				$finalizada             = pg_result($res, $i, finalizada);
				$referencia             = pg_result($res, $i, referencia);
				$descricao              = pg_result($res, $i, descricao);
				$serie                  = pg_result($res, $i, serie);
				$consumidor_nome        = pg_result($res, $i, consumidor_nome);
				$consumidor_fone        = pg_result($res, $i, consumidor_fone);
				$consumidor_endereco    = pg_result($res, $i, consumidor_endereco);
				$consumidor_numero      = pg_result($res, $i, consumidor_numero);
				$consumidor_complemento = pg_result($res, $i, consumidor_complemento);
				$consumidor_bairro      = pg_result($res, $i, consumidor_bairro);
				$consumidor_cep         = pg_result($res, $i, consumidor_cep);
				$consumidor_cidade      = pg_result($res, $i, consumidor_cidade);
				$consumidor_estado      = pg_result($res, $i, consumidor_estado);
				$consumidor_email       = pg_result($res, $i, consumidor_email);
				$consumidor_revenda     = pg_result($res, $i, consumidor_revenda);
				$defeito_reclamado      = pg_result($res, $i, defeito_reclamado);
				$defeito_constatado     = pg_result($res, $i, defeito_constatado);
				$solucao                = pg_result($res, $i, solucao);
				$nota_fiscal            = pg_result($res, $i, nota_fiscal);
				$data_nf                = pg_result($res, $i, data_nf);
				$revenda_nome           = pg_result($res, $i, revenda_nome);
				$revenda_cnpj           = pg_result($res, $i, revenda_cnpj);
				$aparencia_produto      = pg_result($res, $i, aparencia_produto);
				$acessorios             = pg_result($res, $i, acessorios);
				$extrato                = pg_result($res, $i, extrato);
				$data_geracao           = pg_result($res, $i, data_geracao);
				$peca_referencia        = pg_result($res, $i, peca_referencia);
				$peca_descricao         = pg_result($res, $i, peca_descricao);
				$peca_qtde              = pg_result($res, $i, peca_qtde);
				$defeito_descricao      = pg_result($res, $i, defeito_descricao);
				$servico_realizado      = pg_result($res, $i, servico_realizado);

				if(strlen($revenda_cnpj)>0 AND strlen($revenda_cnpj)==14){
					$revenda_cnpj = substr($revenda_cnpj,0,2).".".substr($revenda_cnpj,2,3).".".substr($revenda_cnpj,5,3)."/".substr($revenda_cnpj,8,4)."-".substr($revenda_cnpj,12,2);
				}

				$posto_codigo = " ".$posto_codigo." ";

				fputs($fp,"$posto_codigo\t");
				fputs($fp,"$posto_nome\t");
				fputs($fp,"$sua_os\t");
				fputs($fp,"$data_digitacao\t");
				fputs($fp,"$data_abertura\t");
				fputs($fp,"$data_fechamento\t");
				fputs($fp,"$finalizada\t");
				fputs($fp,"$referencia\t");
				fputs($fp,"$descricao\t");
				fputs($fp,"$serie\t");
				fputs($fp,"$consumidor_nome\t");
				fputs($fp,"$consumidor_fone\t");
				fputs($fp,"$consumidor_cpf\t");
				fputs($fp,"$consumidor_endereco\t");
				fputs($fp,"$consumidor_numero\t");
				fputs($fp,"$consumidor_complemento\t");
				fputs($fp,"$consumidor_bairro\t");
				fputs($fp,"$consumidor_cep\t");
				fputs($fp,"$consumidor_cidade\t");
				fputs($fp,"$consumidor_estado\t");
				fputs($fp,"$consumidor_email\t");
				fputs($fp,"$defeito_reclamado\t");
				fputs($fp,"$defeito_constatado\t");
				fputs($fp,"$solucao\t");
				fputs($fp,"$nota_fiscal\t");
				fputs($fp,"$data_nf\t");
				fputs($fp,"$revenda_nome\t");
				fputs($fp,"$revenda_cnpj\t");
				fputs($fp,"$aparencia_produto\t");
				fputs($fp,"$acessorios\t");
				fputs($fp,"$peca_referencia - $peca_descricao\t");
				fputs($fp,"$peca_qtde\t");
				fputs($fp,"$defeito_descricao\t");
				fputs($fp,"$servico_realizado\t");
				fputs($fp,"$extrato\t");
				fputs($fp,"$data_geracao\t");
                fputs($fp,"$consumidor_revenda\t");
				fputs($fp,"\r\n");
			}
			fclose ($fp);
			flush();

			#system("mv $arquivo_completo_tmp $arquivo_completo");

			echo `cd $path_tmp; rm -rf $arquivo_nome.zip; zip -o $arquivo_nome.zip $arquivo_nome > /dev/null ; mv  $arquivo_nome.zip $path `;

			echo "<table width='600' border='0' cellspacing='2' cellpadding='2' align='center'>";
			echo "<tr>";
				echo "<td><br><br></td>";
			echo "</tr>";
			echo "<tr>";
			echo "<td align='center'<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Download em formato XLS (Excel)</font><br><a href='xls/$arquivo_nome.zip'><font face='Arial, Verdana, Times, Sans' size='2' color='#0000FF'>Clique aqui para fazer o download </font></a> </td>";
			echo "</tr>";
			echo "</table>";
		}else{
			echo "<table width='400' align='center' border='0' cellspacing='0' cellpadding='2'>";
				echo "<tr>";
					echo "<td><br><br></td>";
				echo "</tr>";
				echo "<tr>";
					echo "<td>Nenhum resultado encontrado.</td>";
				echo "</tr>";
			echo "</table>";
		}
}


?>

<? include "rodape.php" ?>
