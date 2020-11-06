<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "funcoes.php";
$admin_privilegios="cadastros";
include 'autentica_admin.php';
$title       = "Controle de Auto-Credenciamento SALTON de Postos Autorizados";
$cabecalho   = "Controle de Auto-Credenciamento SALTON de Postos Autorizados";
$layout_menu = "cadastro";
include 'cabecalho.php';
?>
<style type="text/css">
.negrito {font-weight: bold;}
.azul {color:#596D9B}
.text_curto {
	text-align: center;
	font-weight: bold;
	color: #000;
	background-color: #FF6666;
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

table td,.Conteudo {
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
}
</style>
<?
$sql = "SELECT * from (
			SELECT
			tbl_posto.posto                          as posto    ,
			tbl_posto.cnpj                            ,
			tbl_posto.contato                         ,
			tbl_posto.ie                              ,
			tbl_posto.cidade                         as cidade        ,
			tbl_posto.estado                         as estado       ,
			tbl_posto_fabrica.contato_endereco       AS endereco,
			tbl_posto_fabrica.contato_numero         AS numero,
			tbl_posto_fabrica.contato_complemento    AS complemento,
			tbl_posto_fabrica.contato_bairro         AS bairro,
			tbl_posto_fabrica.contato_cep            AS cep,
			tbl_posto_fabrica.contato_email          AS email,
			tbl_posto.fone                           AS fone,
			tbl_posto_fabrica.contato_fax            AS fax,
			tbl_posto.nome                            ,
			tbl_posto.pais                            ,
			tbl_posto_fabrica.codigo_posto            ,
			tbl_tipo_posto.descricao                  ,
			tbl_posto_fabrica.pedido_faturado         ,
			tbl_posto_fabrica.pedido_em_garantia      ,
			tbl_posto_fabrica.coleta_peca             ,
			tbl_posto_fabrica.reembolso_peca_estoque  ,
			tbl_posto_fabrica.digita_os               ,
			tbl_posto_fabrica.prestacao_servico       ,
			tbl_posto_fabrica.prestacao_servico_sem_mo,
			tbl_posto_fabrica.pedido_via_distribuidor ,
			tbl_posto_fabrica.credenciamento          ,
			to_char(tbl_posto_fabrica.contrato,'DD/MM/YYYY HH24:MI') as contrato
			FROM    tmp_email_convite_salton
			LEFT JOIN tbl_posto using(posto)
			LEFT JOIN tbl_posto_fabrica on tbl_posto_fabrica.posto = tbl_posto.posto and tbl_posto_fabrica.fabrica = 81 
			LEFT JOIN tbl_tipo_posto ON tbl_posto_fabrica.tipo_posto = tbl_tipo_posto.tipo_posto
			LEFT JOIN tbl_empresa_cliente ON tbl_posto.posto = tbl_empresa_cliente.posto AND tbl_empresa_cliente.fabrica = tbl_posto_fabrica.fabrica
			UNION
			SELECT	tbl_posto.posto                  as posto             ,
			tbl_posto.cnpj                            ,
			tbl_posto.contato                         ,
			tbl_posto.ie                              ,
			tbl_posto.cidade                         as cidade        ,
			tbl_posto.estado                         as estado       ,
			tbl_posto_fabrica.contato_endereco       AS endereco,
			tbl_posto_fabrica.contato_numero         AS numero,
			tbl_posto_fabrica.contato_complemento    AS complemento,
			tbl_posto_fabrica.contato_bairro         AS bairro,
			tbl_posto_fabrica.contato_cep            AS cep,
			tbl_posto_fabrica.contato_email          AS email,
			tbl_posto.fone                           AS fone,
			tbl_posto_fabrica.contato_fax            AS fax,
			tbl_posto.nome                            ,
			tbl_posto.pais                            ,
			tbl_posto_fabrica.codigo_posto            ,
			tbl_tipo_posto.descricao                  ,
			tbl_posto_fabrica.pedido_faturado         ,
			tbl_posto_fabrica.pedido_em_garantia      ,
			tbl_posto_fabrica.coleta_peca             ,
			tbl_posto_fabrica.reembolso_peca_estoque  ,
			tbl_posto_fabrica.digita_os               ,
			tbl_posto_fabrica.prestacao_servico       ,
			tbl_posto_fabrica.prestacao_servico_sem_mo,
			tbl_posto_fabrica.pedido_via_distribuidor ,
			tbl_posto_fabrica.credenciamento          ,
			to_char(tbl_posto_fabrica.contrato,'DD/MM/YYYY HH24:MI') as contrato
			FROM    salton_postos
			LEFT JOIN tbl_posto using(posto)
			LEFT JOIN tbl_posto_fabrica on tbl_posto_fabrica.posto = tbl_posto.posto and tbl_posto_fabrica.fabrica = 81
			LEFT JOIN tbl_tipo_posto ON tbl_posto_fabrica.tipo_posto = tbl_tipo_posto.tipo_posto
			LEFT JOIN tbl_empresa_cliente ON tbl_posto.posto = tbl_empresa_cliente.posto AND tbl_empresa_cliente.fabrica = tbl_posto_fabrica.fabrica
			WHERE salton_postos.posto not in (select posto from tmp_email_convite_salton) 
			EXCEPT 
			SELECT tbl_posto.posto                   as posto             ,
			tbl_posto.cnpj                            ,
			tbl_posto.contato                         ,
			tbl_posto.ie                              ,
			tbl_posto.cidade                         as cidade        ,
			tbl_posto.estado                         as estado       ,
			tbl_posto_fabrica.contato_endereco       AS endereco,
			tbl_posto_fabrica.contato_numero         AS numero,
			tbl_posto_fabrica.contato_complemento    AS complemento,
			tbl_posto_fabrica.contato_bairro         AS bairro,
			tbl_posto_fabrica.contato_cep            AS cep,
			tbl_posto_fabrica.contato_email          AS email,
			tbl_posto.fone                           AS fone,
			tbl_posto_fabrica.contato_fax            AS fax,
			tbl_posto.nome                            ,
			tbl_posto.pais                            ,
			tbl_posto_fabrica.codigo_posto            ,
			tbl_tipo_posto.descricao                  ,
			tbl_posto_fabrica.pedido_faturado         ,
			tbl_posto_fabrica.pedido_em_garantia      ,
			tbl_posto_fabrica.coleta_peca             ,
			tbl_posto_fabrica.reembolso_peca_estoque  ,
			tbl_posto_fabrica.digita_os               ,
			tbl_posto_fabrica.prestacao_servico       ,
			tbl_posto_fabrica.prestacao_servico_sem_mo,
			tbl_posto_fabrica.pedido_via_distribuidor ,
			tbl_posto_fabrica.credenciamento          ,
			to_char(tbl_posto_fabrica.contrato,'DD/MM/YYYY HH24:MI') as contrato
			FROM tbl_posto_fabrica
			JOIN tbl_posto on tbl_posto.posto = tbl_posto_fabrica.posto and tbl_posto_fabrica.fabrica = 81
			LEFT JOIN tbl_tipo_posto ON tbl_posto_fabrica.tipo_posto = tbl_tipo_posto.tipo_posto
			WHERE tbl_posto_fabrica.credenciamento is not null
			) as x
			ORDER BY x.nome";
$res = pg_query($con,$sql);
$cont_tmp = 0;
$cont_posto = 0;
if(pg_num_rows($res) > 0){
	echo "<table border='1' cellpadding='3' cellspacing='0' style='border-collapse: collapse' bordercolor='#000000'>";
	for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
		$posto = pg_result($res,$i,posto);
		if(strlen(pg_result($res,$i,codigo_posto))>0){
			$cont_posto = $cont_posto + 1;
		}else{
			$cont_tmp = $cont_tmp + 1;
		}
		if ($i == 0) {
			flush();
			echo "<tr class='Titulo'>";
			echo "<td nowrap rowspan='2'>CIDADE</td>";
			echo "<td nowrap rowspan='2'>ESTADO</td>";
			echo "<td nowrap rowspan='2'>NOME</td>";
			echo "<td nowrap rowspan='2'>E-MAIL</td>";
			echo "<td nowrap rowspan='2'>FONE</td>";
			echo "<td nowrap rowspan='2'>CONTATO</td>";
			echo "<td nowrap rowspan='2'>CÓDIGO</td>";
			echo "<td nowrap rowspan='2'>TIPO</td>";
			echo "<td nowrap rowspan='2'>CREDENCIAMENTO</td>";
			echo "<td nowrap rowspan='2'>DATA CONTRATO</td>";
			echo "<td nowrap colspan='7'>POSTO PODE DIGITAR</td>";
			echo "</tr>";
			echo "<tr class='Titulo'>";
			echo "<td>PEDIDO FATURADO</td>";
			echo "<td>PEDIDO EM GARANTIA</td>";
			echo "<td>DIGITA OS</td>";
			echo "<td>PRESTAÇÃO DE SERVIÇO</td>";
			echo "<td>PEDIDO VIA DISTRIBUIDOR</td>";
			echo "</tr>";
		}
		$cor = ($i % 2 == 0) ? "#F1F4FA" : "#F7F5F0" ;

		echo "<tr class='Conteudo' bgcolor='$cor'>";
		echo "<td nowrap align='left'>" . pg_result($res,$i,cidade) . "</td>";
		echo "<td nowrap>" . pg_result($res,$i,estado) . "</td>";
		echo "<td nowrap align='left'><a href='posto_cadastro?posto=" . pg_result($res,$i,posto) . "'>" . pg_result($res,$i,nome) . "</a></td>";
		echo "<td nowrap>" . pg_result($res,$i,email) . "</td>";
		echo "<td nowrap>" . pg_result($res,$i,fone) . "</td>";
		echo "<td nowrap>" . pg_result($res,$i,contato) . "</td>";
		echo "<td nowrap>" . pg_result($res,$i,codigo_posto) . "</td>";
		echo "<td nowrap align='left'>" . pg_result($res,$i,descricao) . "</td>";
		echo "<td nowrap align='left'>" . pg_result($res,$i,credenciamento) . "</td>";
		echo "<td nowrap align='left'>" . pg_result($res,$i,contrato) . "</td>";
		echo "<td>";
		if (pg_result($res,$i,pedido_faturado) == "t") echo "<img border='0' src='imagens/img_ok.gif'>";
		echo "</td>";
		echo "<td>";
		if (pg_result($res,$i,pedido_em_garantia) == "t") echo "<img border='0' src='imagens/img_ok.gif'>";
		echo "</td>";
		echo "<td>";
		if (pg_result($res,$i,digita_os) == "t") echo "<img border='0' src='imagens/img_ok.gif'>";
		echo "</td>";
		echo "<td>";
		if (pg_result($res,$i,prestacao_servico) == "t") echo "<img border='0' src='imagens/img_ok.gif'>";
		echo "</td>";
		echo "<td>";
		if (pg_result($res,$i,pedido_via_distribuidor) == "t") echo "<img border='0' src='imagens/img_ok.gif'>";
		echo "</td>";
		echo "</tr>";
	}
	echo "</table>";
}
/*echo "<table><tr><td align='center'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Total de email sem confirmação: <span class='negrito'>$cont_tmp</span> ----- Total credenciado: <span class='negrito'>$cont_posto</span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td></tr></table>"; */

echo "<table><tr><td align='center'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Total de email sem confirmação: <span class='negrito'>$cont_tmp</span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td></tr></table>";


include('rodape.php');
exit;
