<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
include 'funcoes.php';


#------------ Le OS da Base de dados ------------#
$os = $HTTP_GET_VARS['os'];
if (strlen ($os) > 0) {
	$sql = "SELECT  tbl_os.sua_os                                                    ,
					to_char(tbl_os.data_abertura,'DD/MM/YYYY')   AS data_abertura    ,
					to_char(tbl_os.data_fechamento,'DD/MM/YYYY') AS data_fechamento  ,
					tbl_os.consumidor_nome                                           ,
					tbl_os.consumidor_cidade                                         ,
					tbl_os.consumidor_fone                                           ,
					tbl_os.consumidor_estado                                         ,
					tbl_os.revenda_nome                                              ,
					tbl_os.revenda_cnpj                                              ,
					tbl_os.nota_fiscal                                               ,
					to_char(tbl_os.data_nf,'DD/MM/YYYY')         AS data_nf          ,
					tbl_defeito_reclamado.descricao              AS defeito_reclamado,
					tbl_os.defeito_reclamado_descricao                               ,
					tbl_os.aparencia_produto                                         ,
					tbl_os.acessorios
			FROM    tbl_os
			LEFT JOIN    tbl_defeito_reclamado using (defeito_reclamado)
			WHERE   tbl_os.os    = $os
			AND     tbl_os.posto = $login_posto";
	$res = pg_exec ($con,$sql);

	if (pg_numrows ($res) == 1) {
		$sua_os				= pg_result ($res,0,sua_os);
		$data_abertura		= pg_result ($res,0,data_abertura);
		$data_fechamento	= pg_result ($res,0,data_fechamento);
		$consumidor_nome	= pg_result ($res,0,consumidor_nome);
		$consumidor_cidade	= pg_result ($res,0,consumidor_cidade);
		$consumidor_fone	= pg_result ($res,0,consumidor_fone);
		$consumidor_estado	= pg_result ($res,0,consumidor_estado);
		$revenda_cnpj		= pg_result ($res,0,revenda_cnpj);
		$revenda_nome		= pg_result ($res,0,revenda_nome);
		$nota_fiscal		= pg_result ($res,0,nota_fiscal);
		$data_nf			= pg_result ($res,0,data_nf);
		$defeito_reclamado	= pg_result ($res,0,defeito_reclamado);
		$aparencia_produto           = pg_result ($res,0,aparencia_produto);
		$acessorios                  = pg_result ($res,0,acessorios);
		$defeito_reclamado_descricao = pg_result ($res,0,defeito_reclamado_descricao);
	}
}


$title = "PEDIDO IMPRESSÃO";

$layout_menu = 'callcenter';
include "cabecalho.php";

?>


<Div id="container" style="width: 650px;">
	<div id="page"> <H1><? echo $login_fabrica_nome ?> - Depto Assistência Técnica - Ordem de Execução de Venda/Cotação</H1></div>

<div id="contentcenter">
	<div id="contentleft2" style="width: 50px;">Ped Filiz:</div>
	<div id="contentleft2" style="width: 210px;">0002658 </div>
	<div id="contentleft2" style="width: 50px;">Data...: </div>
	<div id="contentleft2" style="width: 150px;">16/03/2004</div>
	<div id="contentleft2" style="width: 50px;">Emissor: </div>
	<div id="contentleft2" style="width: 100px;">André</div>
</div>
<div id="contentcenter">
	<div id="contentleft2" style="width: 50px;">Nome:</div>
	<div id="contentleft2" style="width: 210px;">RF BALANÇAS E AUTOMAÇÃO LTDA </div>
	<div id="contentleft2" style="width: 50px;">Cidade: </div>
	<div id="contentleft2" style="width: 150px;">BELO HORIZONTE</div>
	<div id="contentleft2" style="width: 50px;">Estado: </div>
	<div id="contentleft2" style="width: 100px;">MG</div>
</div>
<div id="contentcenter">
	<div id="contentleft2" style="width: 50px;">Endereço:</div>
	<div id="contentleft2" style="width: 210px;">RUA STA TEREZINHA, 450 - BLOCO 54</div>
	<div id="contentleft2" style="width: 50px;">CNPJ Nº: </div>
	<div id="contentleft2" style="width: 150px;">01.001.001/0001-11</div>
	<div id="contentleft2" style="width: 50px;">Insc.Est.: </div>
	<div id="contentleft2" style="width: 100px;">287.296.025.0001.455</div>
</div>
<div id="contentcenter">
	<div id="contentleft2" style="width: 50px;">Docmto:</div>
	<div id="contentleft2" style="width: 210px;">30</div>
	<div id="contentleft2" style="width: 50px;">Pagmto: </div>
	<div id="contentleft2" style="width: 150px;">A30DDL</div>
	<div id="contentleft2" style="width: 50px;">Modalidade: </div>
	<div id="contentleft2" style="width: 100px;">FOB</div>
</div>
<div id="contentcenter">
	<div id="contentleft2" style="width: 50px;">Transp.:</div>
	<div id="contentleft2" style="width: 210px;">M-2000 2672</div>
	<div id="contentleft2" style="width: 50px;">Validade: </div>
	<div id="contentleft2" style="width: 150px;">15 dias</div>
	<div id="contentleft2" style="width: 50px;">Entrega: </div>
	<div id="contentleft2" style="width: 100px;">10 dias</div>
</div>
<div id="contentcenter">
	<div id="contentleft2" style="width: 50px;">Ped.Cli:</div>
	<div id="contentleft2" style="width: 210px;">109/2004</div>
	<div id="contentleft2" style="width: 50px;">Contato: </div>
	<div id="contentleft2" style="width: 150px;">ANTONIO / GERALDO</div>
	<div id="contentleft2" style="width: 50px;">Fone: </div>
	<div id="contentleft2" style="width: 100px;">(31) 3475 6026 / 5794</div>
</div>
<div id="contentcenter">
	<div id="contentleft2" style="width: 50px;">Classe:</div>
	<div id="contentleft2" style="width: 210px;">A</div>
	<div id="contentleft2" style="width: 50px;">FAX/CLI: </div>
	<div id="contentleft2" style="width: 150px;">(31) 3475 5794</div>
	<div id="contentleft2" style="width: 50px;">Total: </div>
	<div id="contentleft2" style="width: 100px;">1.425,25</div>
</div>
<div id="contentcenter">
	<div id="contentleft2" style="width: 50px;">Mensagem:</div>
	<div id="contentleft2" >TRANSPORTADOR URGENTE !!!!!!</div>
</div>

</div>

<br>
<TABLE style="width: 650px;">
<TR>
	<TD class="menu_top">IT</TD>
	<TD class="menu_top">Código</TD>
	<TD class="menu_top">Qte</TD>
	<TD class="menu_top">Qtl</TD>
	<TD class="menu_top">Qta</TD>
	<TD class="menu_top">Sd</TD>
	<TD class="menu_top">Descrição</TD>
	<TD class="menu_top">IPI</TD>
	<TD class="menu_top">C</TD>
	<TD class="menu_top">un/s IPI+desc</TD>
	<TD class="menu_top">Total</TD>
	<TD class="menu_top">Ordem de Execução - CPD</TD>
</TR>
<TR>
	<TD class="table_line">01</TD>
	<TD class="table_line">55.50.21-5</TD>
	<TD class="table_line">001</TD>
	<TD class="table_line" style="width: 30px;">&nbsp;</TD>
	<TD class="table_line">000</TD>
	<TD class="table_line">001</TD>
	<TD class="table_line">CELULA DE CARGA ALFA G-10</TD>
	<TD class="table_line">15</TD>
	<TD class="table_line">C</TD>
	<TD class="table_line" style="text-align: right;">91.262,54</TD>
	<TD class="table_line" style="text-align: right;">91.301,92</TD>
	<TD class="table_line">&nbsp;</TD>
</TR>
<TR>
	<TD class="table_line">01</TD>
	<TD class="table_line">55.50.21-5</TD>
	<TD class="table_line">001</TD>
	<TD class="table_line" style="width: 30px;">&nbsp;</TD>
	<TD class="table_line">000</TD>
	<TD class="table_line">001</TD>
	<TD class="table_line">CELULA DE CARGA ALFA G-10</TD>
	<TD class="table_line">15</TD>
	<TD class="table_line">C</TD>
	<TD class="table_line" style="text-align: right;">91.262,54</TD>
	<TD class="table_line" style="text-align: right;">91.301,92</TD>
	<TD class="table_line">&nbsp;</TD>
</TR>
<TR>
	<TD class="table_line">01</TD>
	<TD class="table_line">55.50.21-5</TD>
	<TD class="table_line">001</TD>
	<TD class="table_line" style="width: 30px;">&nbsp;</TD>
	<TD class="table_line">000</TD>
	<TD class="table_line">001</TD>
	<TD class="table_line">CELULA DE CARGA ALFA G-10</TD>
	<TD class="table_line">15</TD>
	<TD class="table_line">C</TD>
	<TD class="table_line" style="text-align: right;">91.262,54</TD>
	<TD class="table_line" style="text-align: right;">91.301,92</TD>
	<TD class="table_line">&nbsp;</TD>
</TR>
<TR>
	<TD class="table_line">01</TD>
	<TD class="table_line">55.50.21-5</TD>
	<TD class="table_line">001</TD>
	<TD class="table_line" style="width: 30px;">&nbsp;</TD>
	<TD class="table_line">000</TD>
	<TD class="table_line">001</TD>
	<TD class="table_line">CELULA DE CARGA ALFA G-10</TD>
	<TD class="table_line">15</TD>
	<TD class="table_line">C</TD>
	<TD class="table_line" style="text-align: right;">91.262,54</TD>
	<TD class="table_line" style="text-align: right;">91.301,92</TD>
	<TD class="table_line">&nbsp;</TD>
</TR>
<TR>
	<TD class="table_line">01</TD>
	<TD class="table_line">55.50.21-5</TD>
	<TD class="table_line">001</TD>
	<TD class="table_line" style="width: 30px;">&nbsp;</TD>
	<TD class="table_line">000</TD>
	<TD class="table_line">001</TD>
	<TD class="table_line">CELULA DE CARGA ALFA G-10</TD>
	<TD class="table_line">15</TD>
	<TD class="table_line">C</TD>
	<TD class="table_line" style="text-align: right;">91.262,54</TD>
	<TD class="table_line" style="text-align: right;">91.301,92</TD>
	<TD class="table_line">&nbsp;</TD>
</TR>
<TR>
	<TD class="table_line">01</TD>
	<TD class="table_line">55.50.21-5</TD>
	<TD class="table_line">001</TD>
	<TD class="table_line" style="width: 30px;">&nbsp;</TD>
	<TD class="table_line">000</TD>
	<TD class="table_line">001</TD>
	<TD class="table_line">CELULA DE CARGA ALFA G-10</TD>
	<TD class="table_line">15</TD>
	<TD class="table_line">C</TD>
	<TD class="table_line" style="text-align: right;">91.262,54</TD>
	<TD class="table_line" style="text-align: right;">91.301,92</TD>
	<TD class="table_line">&nbsp;</TD>
</TR>
<TR>
	<TD class="table_line">01</TD>
	<TD class="table_line">55.50.21-5</TD>
	<TD class="table_line">001</TD>
	<TD class="table_line" style="width: 30px;">&nbsp;</TD>
	<TD class="table_line">000</TD>
	<TD class="table_line">001</TD>
	<TD class="table_line">CELULA DE CARGA ALFA G-10</TD>
	<TD class="table_line">15</TD>
	<TD class="table_line">C</TD>
	<TD class="table_line" style="text-align: right;">91.262,54</TD>
	<TD class="table_line" style="text-align: right;">91.301,92</TD>
	<TD class="table_line">&nbsp;</TD>
</TR>
<TR>
	<TD class="table_line">01</TD>
	<TD class="table_line">55.50.21-5</TD>
	<TD class="table_line">001</TD>
	<TD class="table_line" style="width: 30px;">&nbsp;</TD>
	<TD class="table_line">000</TD>
	<TD class="table_line">001</TD>
	<TD class="table_line">CELULA DE CARGA ALFA G-10</TD>
	<TD class="table_line">15</TD>
	<TD class="table_line">C</TD>
	<TD class="table_line" style="text-align: right;">91.262,54</TD>
	<TD class="table_line" style="text-align: right;">91.301,92</TD>
	<TD class="table_line">&nbsp;</TD>
</TR>
<TR>
	<TD class="table_line">01</TD>
	<TD class="table_line">55.50.21-5</TD>
	<TD class="table_line">001</TD>
	<TD class="table_line" style="width: 30px;">&nbsp;</TD>
	<TD class="table_line">000</TD>
	<TD class="table_line">001</TD>
	<TD class="table_line">CELULA DE CARGA ALFA G-10</TD>
	<TD class="table_line">15</TD>
	<TD class="table_line">C</TD>
	<TD class="table_line" style="text-align: right;">91.262,54</TD>
	<TD class="table_line" style="text-align: right;">91.301,92</TD>
	<TD class="table_line">&nbsp;</TD>
</TR>
<TR>
	<TD class="table_line">01</TD>
	<TD class="table_line">55.50.21-5</TD>
	<TD class="table_line">001</TD>
	<TD class="table_line" style="width: 30px;">&nbsp;</TD>
	<TD class="table_line">000</TD>
	<TD class="table_line">001</TD>
	<TD class="table_line">CELULA DE CARGA ALFA G-10</TD>
	<TD class="table_line">15</TD>
	<TD class="table_line">C</TD>
	<TD class="table_line" style="text-align: right;">91.262,54</TD>
	<TD class="table_line" style="text-align: right;">91.301,92</TD>
	<TD class="table_line">&nbsp;</TD>
</TR>
<TR>
	<TD class="table_line">01</TD>
	<TD class="table_line">55.50.21-5</TD>
	<TD class="table_line">001</TD>
	<TD class="table_line" style="width: 30px;">&nbsp;</TD>
	<TD class="table_line">000</TD>
	<TD class="table_line">001</TD>
	<TD class="table_line">CELULA DE CARGA ALFA G-10</TD>
	<TD class="table_line">15</TD>
	<TD class="table_line">C</TD>
	<TD class="table_line" style="text-align: right;">91.262,54</TD>
	<TD class="table_line" style="text-align: right;">91.301,92</TD>
	<TD class="table_line">&nbsp;</TD>
</TR>
<TR>
	<TD colspan="10" class="table_line">Valor total do saldo com IPI, em R$, com desconto de 30% </TD>
	<TD class="table_line" style="text-align: right;">91.262,54</TD>
</TR>
<TR>
	<TD colspan="10" class="table_line">Peso total estimado do saldo: </TD>
	<TD class="table_line" style="text-align: right;">0,840 kgs</TD>
</TR>

</TABLE>

<br>

<TABLE style="width: 650px;">
<TR>
	<TD class="menu_top">Nota Fiscal</TD>
	<TD class="menu_top">Série</TD>
	<TD class="menu_top">Valor</TD>
	<TD class="menu_top">Data</TD>
	<TD class="menu_top">Caixas</TD>
	<TD class="menu_top">Peso</TD>
</TR>
<TR>
	<TD class="table_line">&nbsp;</TD>
	<TD class="table_line">&nbsp;</TD>
	<TD class="table_line">&nbsp;</TD>
	<TD class="table_line">&nbsp;</TD>
	<TD class="table_line">&nbsp;</TD>
	<TD class="table_line">&nbsp;</TD>
</TR>
<TR>
	<TD class="table_line">&nbsp;</TD>
	<TD class="table_line">&nbsp;</TD>
	<TD class="table_line">&nbsp;</TD>
	<TD class="table_line">&nbsp;</TD>
	<TD class="table_line">&nbsp;</TD>
	<TD class="table_line">&nbsp;</TD>
</TR>
</TABLE>

<br>

<TABLE style="width: 650px;">
<TR>
	<TD class="table_line" style="width: 150px;">POSIÇÃO DO PEDIDO...:</TD>
	<TD class="table_line" style="width: 250px;">### COM SALDO EM ABERTO ###</TD>
	<TD class="table_line" style="width: 100px;">SEPARADO POR...:</TD>
	<TD class="table_line" style="width: 150px;">&nbsp;</TD>
</TR>
</TABLE>
<? include "rodape.php"; ?>

</div>
<BODY>
