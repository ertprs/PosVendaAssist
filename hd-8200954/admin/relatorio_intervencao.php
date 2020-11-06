<?php

include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "funcoes.php";
include "monitora.php";

$gera_automatico = trim($_GET["gera_automatico"]);

if ($gera_automatico != 'automatico'){
    include "autentica_admin.php";
}

//include "gera_relatorio_pararelo_include.php";

//Fabio - coloquei o email
if ($login_fabrica <> 11 and $login_fabrica <> 172 AND $login_fabrica <> 3 AND $login_fabrica <> 6 AND $login_fabrica <> 25) { //Samuel liberou para Erasmo...quando Tulio disse que não atendemos Lenoxx
    echo "<h1>Programa em Manutenção</h1>";
    exit;
}
#----------- Tulio - 30/11/2007 ------------

$msg_erro = "";

//$intervencao_finalizada = '1';
//$intervencao_pendente = '1';

if ($login_fabrica == 172) {
    $id_servico_realizado        = 11287;
    $id_servico_realizado_ajuste = 11283;
}
if ($login_fabrica == 11) {
    $id_servico_realizado        = 61;
    $id_servico_realizado_ajuste = 498;
}
if ($login_fabrica == 6) {
    $id_servico_realizado        = 1;
    $id_servico_realizado_ajuste = 35;
}
if ($login_fabrica == 3) {
    $id_servico_realizado        = 20;
    $id_servico_realizado_ajuste = 96;
}

if (strlen($id_servico_realizado) == 0) { # padrao BRITANIA
    $id_servico_realizado        = 20;
    $id_servico_realizado_ajuste = 96;
}

if (strlen($_POST["btn_acao"]) > 0 ) $btn_acao = strtoupper($_POST["btn_acao"]);
if (strlen($_GET["btn_acao"]) > 0 )  $btn_acao = strtoupper($_GET["btn_acao"]);

if (strlen($btn_acao) > 0) {

    if (strlen(trim($_GET["tipo_data"])) > 0)  $tipo_data = trim($_GET["tipo_data"]);
    if (strlen(trim($_POST["tipo_data"])) > 0) $tipo_data = trim($_POST["tipo_data"]);

    if (strlen(trim($_POST["data_inicial"])) > 0) $x_data_inicial = trim($_POST["data_inicial"]);
    if (strlen(trim($_GET["data_inicial"])) > 0)  $x_data_inicial = trim($_GET["data_inicial"]);

    if (strlen(trim($_POST["data_final"])) > 0) $x_data_final = trim($_POST["data_final"]);
    if (strlen(trim($_GET["data_final"])) > 0)  $x_data_final = trim($_GET["data_final"]);

    if(empty($x_data_inicial) OR empty($x_data_final)){
        $msg_erro = "Data Inválida";
    } 

	if(strlen($msg_erro)==0){
        list($di, $mi, $yi) = explode("/", $x_data_inicial);
        if(!checkdate($mi,$di,$yi)) 
            $msg_erro = "Data Inválida";
    }
    if(strlen($msg_erro)==0){
        list($df, $mf, $yf) = explode("/", $x_data_final);
        if(!checkdate($mf,$df,$yf)) 
            $msg_erro = "Data Inválida";
    }

    if(strlen($msg_erro)==0){
        $x_data_inicial = "$yi-$mi-$di";
        $x_data_final = "$yf-$mf-$df";
    }
    if(strlen($msg_erro)==0){
        if(strtotime($x_data_final) < strtotime($x_data_inicial)){
            $msg_erro = "Data Inválida.";
        }
    }
	
	if(strlen($msg_erro)==0){
		if (strtotime($x_data_inicial) < strtotime($x_data_final . ' -1 month')) {
			$msg_erro = 'O intervalo entre as datas não pode ser maior que 1 mês.';
		}
	 }


    if (strlen(trim($_POST["intervencao_filtro"])) > 0)     $intervencao_filtro     = trim($_POST["intervencao_filtro"]);
    if (strlen(trim($_GET["intervencao_filtro"])) > 0)      $intervencao_filtro     = trim($_GET["intervencao_filtro"]);
    
    if (strlen(trim($_POST["intervencao_finalizada"])) > 0) $intervencao_finalizada = trim($_POST["intervencao_finalizada"]);
    if (strlen(trim($_GET["intervencao_finalizada"])) > 0)  $intervencao_finalizada = trim($_GET["intervencao_finalizada"]);

    if (strlen(trim($_POST["intervencao_pendente"])) > 0)   $intervencao_pendente   = trim($_POST["intervencao_pendente"]);
    if (strlen(trim($_GET["intervencao_pendente"])) > 0)    $intervencao_pendente   = trim($_GET["intervencao_pendente"]);

    if (strlen(trim($_POST["codigo_posto"])) > 0)           $codi_posto             = trim($_POST["codigo_posto"]);
    if (strlen(trim($_GET["codigo_posto"])) > 0)            $codi_posto             = trim($_GET["codigo_posto"]);

    if (strlen(trim($_POST["peca_referencia"])) > 0)        $peca_referencia        = trim($_POST["peca_referencia"]);
    if (strlen(trim($_GET["peca_referencia"])) > 0)         $peca_referencia        = trim($_GET["peca_referencia"]);
    
    if (strlen(trim($_POST["peca_descricao"])) > 0)         $peca_descricao         = trim($_POST["peca_descricao"]);
    if (strlen(trim($_GET["peca_descricao"])) > 0)          $peca_descricao         = trim($_GET["peca_descricao"]);

    if (strlen($codi_posto) > 0) {

        $sql_adicional = " AND tbl_posto_fabrica.codigo_posto = '$codi_posto' ";

        $sql = "SELECT tbl_posto_fabrica.codigo_posto as cod ,
                       tbl_posto.nome                 as nome,
                       tbl_posto.posto                as posto
                  FROM tbl_posto JOIN tbl_posto_fabrica USING(posto)
                 WHERE tbl_posto_fabrica.fabrica = $login_fabrica
                    $sql_adicional";

        $res = pg_exec ($con,$sql);

        if (pg_numrows ($res) > 0) {

            $posto_codigo = pg_result($res, 0, 'cod');
            $posto_nome   = pg_result($res, 0, 'nome');
            $posto        = pg_result($res, 0, 'posto');

            $sql_adicional = " AND tbl_os.posto = $posto";
			$join_os = " join tbl_os using(os) ";

        }

    }

    if (strlen($peca_referencia) > 0) {

        $sql_adicional_2 = " AND tbl_peca.referencia = '$peca_referencia' ";

        $sql = "SELECT tbl_peca.referencia as ref,
                       tbl_peca.descricao  as desc,
                       tbl_peca.peca       as peca
                  FROM tbl_peca
                 WHERE tbl_peca.fabrica = $login_fabrica
                    $sql_adicional_2";

        $res = pg_exec ($con,$sql);

        if (pg_numrows ($res) > 0) {

            $peca_referencia = pg_result($res,0,'ref');
            $peca_descricao  = pg_result($res,0,'desc');
            $peca            = pg_result($res,0,'peca');

            $sql_adicional_2 = " AND tbl_peca.peca = $peca";

        }

    }

    if (strlen($x_data_inicial) > 0 AND $x_data_inicial != 'null' AND strlen($x_data_final) > 0 AND $x_data_final != 'null') {
        #$sql_adicional_3 = " AND tbl_os.data_digitacao BETWEEN '$x_data_inicial 00:00:00' AND '$x_data_final 23:59:59'"; HD-7743884
        $sql_adicional_3 = " AND tbl_os_item.digitacao_item BETWEEN '$x_data_inicial 00:00:00' AND '$x_data_final 23:59:59'";
    }

}

$layout_menu = "callcenter";
$title       = "Relatório de OS com intervenção";

include "cabecalho.php";

?>

<style type="text/css">
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

table.tabela tr td{
    font-family: verdana;
    font-size: 11px;
    border-collapse: collapse;
    border:1px solid #596d9b;
}

.espaco{
	padding:0 0 0 130px;
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
<script>

    function fnc_pesquisa_posto2 (campo, campo2, tipo) {
        if (tipo == "codigo" ) {
            var xcampo = campo;
        }

        if (tipo == "nome" ) {
            var xcampo = campo2;
        }

        if (xcampo.value != "") {
            var url = "";
            url = "posto_pesquisa_2.php?campo=" + xcampo.value + "&tipo=" + tipo ;
            janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=600, height=400, top=18, left=0");
            janela.codigo  = campo;
            janela.nome    = campo2;
            janela.focus();
        }
		else{
			alert('Informe toda ou parte da informação para realizar a pesquisa');
		}
    }

</script>

<?php include "../js/js_css.php"; ?>
<script type="text/javascript" charset="utf-8">
    $(function(){
        $('#data_inicial').datepick({startDate:'01/01/2000'});
        $('#data_final').datepick({startDate:'01/01/2000'});
        $("#data_inicial").mask("99/99/9999");
        $("#data_final").mask("99/99/9999");
    });
</script>

<script language="javascript">

function fnc_pesquisa_peca_lista(peca_referencia, peca_descricao, tipo) {
    var url = "";
    if (tipo == "referencia") {
        url = "peca_pesquisa_lista.php?peca=" + peca_referencia.value + "&tipo=" + tipo + "&exibe=/assist/admin/relatorio_intervencao.php";
    }

    if (tipo == "descricao") {
        url = "peca_pesquisa_lista.php?descricao=" + peca_descricao.value + "&tipo=" + tipo + "&exibe=/assist/admin/relatorio_intervencao.php";
    }
    if (peca_referencia.value.length >= 3 || peca_descricao.value.length >= 3) {
        janela = window.open(url, "janela", "toolbar=no, location=yes, status=yes, scrollbars=yes, directories=no, width=501, height=400, top=18, left=0");
        janela.referencia   = peca_referencia;
        janela.descricao    = peca_descricao;
        janela.preco        = document.frm_relatorio.preco_null;
        janela.focus();
    } else {
        alert("Digite pelo menos 3 caracteres!");
    }
}
</script>
<div class='texto_avulso'>
	A Pesquisa será realizada através da <b>Data de Digitação</b> da Ordem de Serviço.
</div>
<br /><?php

if (strlen($btn_acao) > 0 && strlen($msg_erro) == 0) {
    //include "gera_relatorio_pararelo.php";
}

if ($gera_automatico != 'automatico' and strlen($msg_erro) == 0) {
    //include "gera_relatorio_pararelo_verifica.php";
}

if (strlen($msg_erro) > 0) { ?>
    <table width="700" border="0" cellspacing="0" cellpadding="2" align="center" class="msg_erro">
        <tr>
            <td><?echo $msg_erro?></td>
        </tr>
    </table>
<? } ?>

<form name="frm_relatorio" method="post" action="<?echo $PHP_SELF?>">
<input type="hidden" name="btn_acao" />
<input type="hidden" name="preco_null" />
<table width='700' class='formulario' border='0' cellpadding='5' cellspacing='1' align='center'>
    <tr>
        <td class='titulo_tabela'>Parâmetros de Pesquisa</td>
    </tr>
    <tr>
        <td>
            <table width='100%' border='0' cellspacing='1' cellpadding='2' class='formulario'>
                <tr width='100%' >
                    <td colspan='2' align='left' class='espaco'>
						Código Posto <br />
                        <input class="frm" type="text" name="codigo_posto" size="10" value="<? echo $codi_posto ?>" <? if ($login_fabrica == 5) { ?> onblur="fnc_pesquisa_posto2 (document.frm.codigo_posto,document.frm.posto_nome,'codigo')" <? } ?>>&nbsp;<img src='imagens/lupa.png' border='0' align='absmiddle' style="cursor:pointer" onclick="javascript: fnc_pesquisa_posto2 (document.frm_relatorio.codigo_posto,document.frm_relatorio.posto_nome,'codigo')">
                    </td>
                    <td colspan='2' align='left'>
						Razão Social <br />
                        <input class="frm" type="text" name="posto_nome" size="35" value="<? echo $posto_nome ?>" <? if ($login_fabrica == 5) { ?> onblur="fnc_pesquisa_posto2 (document.frm.codigo_posto,document.frm.posto_nome,'nome')" <? } ?>>&nbsp;<img src='imagens/lupa.png' style="cursor:pointer" border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_posto2 (document.frm_relatorio.codigo_posto,document.frm_relatorio.posto_nome,'nome')" style="cursor:pointer;" />
                    </td>
                </tr>
                    <td colspan='2' align='left' class='espaco'>
						Data Inicial * <br />
                        <input class='frm' type="text" name="data_inicial" id="data_inicial" size="12" maxlength="10" value="<? if (strlen($data_final) > 0) echo $data_inicial;?>">
                        
                    </td>
               
                    <td colspan='2' align='left'>
						Data Final * <br />
                        <input class='frm' type="text" name="data_final" id="data_final" size="12" maxlength="10" value="<? if (strlen($data_final) > 0) echo $data_final;?>">
                    </td>
                </tr>
                <tr width='100%' >
                    <td colspan='2' align='right' height='20'>&nbsp;</td>
                    <td colspan='2' align='left'>(*) Data da Digitação</td>
                </tr>
                <tr>
                    <td colspan='3' align='left' class='espaco'>
						Intervenção <br />
                        <select name="intervencao_filtro" size="1" class="frm">
                            <option <?if ($intervencao_filtro == "assist") echo " selected ";?> value='assist'>Assistência Técnica</option><?php
                            if ($login_fabrica <> 6) {?>
                                <option <?if ($intervencao_filtro == "sap") echo " selected ";?> value='sap'>SAP</option><?php
                            }
                            if ($login_fabrica == 11 or $login_fabrica == 172) {?>
                                <option <?if ($intervencao_filtro == "suprimentos") echo " selected ";?> value='suprimentos'>Suprimentos</option><?php
                            }
                            if ($login_fabrica == 3) { /* Adicionado Intevenção de Carteira (116,117) - HD 40582 */ ?>
                                <option <?if ($intervencao_filtro == "carteira") echo " selected ";?> value='carteira'>Carteira</option><?php
                            }?>
                        </select>
                    </td>
				</tr>
				<tr>
                    <td align='left' class='espaco' colspan='2'>
						Ref. Peça <br />
                        <input class='frm' type="text" name="peca_referencia" value="<? echo $peca_referencia ?>" size="10" maxlength="20">
						<a href="javascript: fnc_pesquisa_peca_lista(window.document.frm_relatorio.peca_referencia, window.document.frm_relatorio.peca_descricao, 'referencia')">
							<img src="imagens/lupa.png" align="absmiddle" border="0" />
						</a>
					</td>
					<td>
						Descrição Peça <br />
                        <input class='frm' type="text" name="peca_descricao" value="<? echo $peca_descricao ?>" size="35" maxlength="50">
						<a href="javascript: fnc_pesquisa_peca_lista(window.document.frm_relatorio.peca_referencia, window.document.frm_relatorio.peca_descricao, 'descricao')">
							<img src="imagens/lupa.png" align="absmiddle" border="0" />
						</a>
                    </td>
                </tr>
                <tr width='100%'>
                    <td colspan='4' align='left' class='espaco'>
						Mostrar:&nbsp;
                        <input type="checkbox" name="intervencao_finalizada" value='1' <? if (strlen($intervencao_finalizada) > 0) echo 'checked';?> /> Intervenções Finalizadas&nbsp;&nbsp;&nbsp;&nbsp;
                        <input type="checkbox" name="intervencao_pendente" value='1' <? if (strlen($intervencao_pendente) > 0) echo 'checked';?> /> Intervenções Pendentes
                    </td>
                </tr>
                <tr>
                    <td colspan="4" align="center">
                        <br />
                        <input type='button' value='Pesquisar' onclick="if (document.frm_relatorio.btn_acao.value == 'PESQUISAR') alert('Aguarde submissão'); else{ document.frm_relatorio.btn_acao.value = 'PESQUISAR'; document.frm_relatorio.submit();}" style="cursor: pointer;" alt="Preencha as opções e clique aqui para pesquisar" />
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>

<br /><?php

if (strlen($btn_acao) > 0 && strlen($msg_erro) == 0) {

    $arquivo = "xls/relatorio_intervencao_" . $login_fabrica . ".xls";

    echo "<p align='center' id='id_aguardando'>Aguarde, processando...</p>";
    echo "<p align='center' id='id_download' style='display:none'><input type='button' value='Download em Excel' onclick=\"window.location='".$arquivo."'\"><br /></p>";

    flush();
	
	if ($intervencao_filtro == 'assist') {

        if (strlen($intervencao_finalizada) > 0 OR strlen($intervencao_pendente) > 0) {
            $tipo_intervencao = " AND tbl_os_status.status_os IN (62,64,65) ";
        }
	}

    if ($intervencao_filtro == 'sap') {

        if (strlen($intervencao_finalizada) > 0 OR strlen($intervencao_pendente) > 0) {
            $tipo_intervencao = " AND tbl_os_status.status_os IN (72,73) ";
        }
    }

    if ($intervencao_filtro == 'carteira') {

        if (strlen($intervencao_finalizada) > 0 OR strlen($intervencao_pendente) > 0) {
            $tipo_intervencao = " AND tbl_os_status.status_os IN (116,117) ";
        } 
    }

    if ($intervencao_filtro == 'suprimentos') {

        if (strlen($intervencao_finalizada) > 0 OR strlen($intervencao_pendente) > 0) {
            $tipo_intervencao = " AND tbl_os_status.status_os IN (87,88) ";
        } 
    }



    if ($intervencao_filtro == 'assist') {

        if (strlen($intervencao_finalizada) > 0 AND strlen($intervencao_pendente) > 0) {
            $sql_adicional_4 = " AND tbl_os_status.status_os IN (62,64,65) ";
        } else {

            if (strlen($intervencao_pendente) > 0) {
                $sql_adicional_4 = " AND tbl_os_status.status_os IN (62,65) ";
            } else {
                $sql_adicional_4 = " AND tbl_os_status.status_os IN (64) ";
            }

        }

    }

    if ($intervencao_filtro == 'sap') {

        if (strlen($intervencao_finalizada) > 0 AND strlen($intervencao_pendente) > 0) {
            $sql_adicional_4 = " AND tbl_os_status.status_os IN (72,73) ";
        } else {

            if (strlen($intervencao_pendente) > 0) {
                $sql_adicional_4 = " AND tbl_os_status.status_os IN (72) ";
            } else {
                $sql_adicional_4 = " AND tbl_os_status.status_os IN (73) ";
            }

        }

    }

    if ($intervencao_filtro == 'carteira') {

        if (strlen($intervencao_finalizada) > 0 AND strlen($intervencao_pendente) > 0) {
            $sql_adicional_4 = " AND tbl_os_status.status_os IN (116,117) ";
        } else {

            if (strlen($intervencao_pendente) > 0) {
                $sql_adicional_4 = " AND tbl_os_status.status_os IN (116) ";
            } else {
                $sql_adicional_4 = " AND tbl_os_status.status_os IN (117) ";
            }

        }

    }

    if ($intervencao_filtro == 'suprimentos') {

        if (strlen($intervencao_finalizada) > 0 AND strlen($intervencao_pendente) > 0) {
            $sql_adicional_4 = " AND tbl_os_status.status_os IN (87,88) ";
        } else {

            if (strlen($intervencao_pendente) > 0) {
                $sql_adicional_4 = " AND tbl_os_status.status_os IN (87) ";
            } else {
                $sql_adicional_4 = " AND tbl_os_status.status_os IN (88) ";
            }

        }

    }

    if (strlen($posto) > 0) $posto = " AND tbl_os.posto = $posto";

    $sql = " SELECT os,
                    MAX(os_status) AS os_status
               INTO TEMP TABLE tmp_os_intervencao
               FROM tbl_os_status
				$join_os
              WHERE fabrica_status = $login_fabrica
			  $tipo_intervencao
			  $sql_adicional_4
			  $sql_adicional
              GROUP BY os;

            CREATE INDEX tmp_os_intervencao_os ON tmp_os_intervencao(os);
            CREATE INDEX tmp_os_intervencao_os_status ON tmp_os_intervencao(os_status);

            SELECT tbl_os.os                                                         ,
                   tbl_os.sua_os                                                     ,
                   LPAD(tbl_os.sua_os,20,'0')                   AS ordem             ,
                   TO_CHAR(tbl_os.data_abertura,'DD/MM/YYYY')   AS abertura          ,
                   TO_CHAR(tbl_os.data_nf,'DD/MM/YYYY')         AS data_nf           ,
                   tbl_os.data_abertura                         AS abertura_os       ,
                   tbl_os.admin                                                      ,
                   tbl_posto_fabrica.codigo_posto                                    ,
                   tbl_posto.nome as posto_nome                                      ,
                   tbl_produto.referencia                      AS produto_referencia ,
                   tbl_produto.descricao                       AS produto_descricao  ,
                   tbl_peca.referencia as peca_referencia,
                   tbl_peca.descricao as peca_descricao,
                   tbl_peca.bloqueada_garantia,
                   tbl_peca.retorna_conserto,
                   tbl_os_item.servico_realizado,
                   tbl_os_item.os_item AS itemOS,
                   TO_CHAR(tbl_os_item.digitacao_item,'DD/MM/YYYY') AS digitacao_item,
                   tbl_os_item.digitacao_item - tbl_os.data_abertura AS dias_apos_abertura,
				   tbl_admin.login         ,
				   tbl_os_status.status_os ,
				   tbl_os_status.observacao,
				   tbl_os_status.admin     ,
				   TO_CHAR(tbl_os_status.data,'DD/MM/YYYY') as data
              FROM tmp_os_intervencao
              JOIN tbl_os            ON tmp_os_intervencao.os         = tbl_os.os AND tbl_os.fabrica = $login_fabrica
              JOIN tbl_posto         ON tbl_posto.posto               = tbl_os.posto
              JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto       = tbl_posto.posto AND tbl_posto_fabrica.fabrica = tbl_os.fabrica
              JOIN tbl_produto       ON tbl_produto.produto           = tbl_os.produto AND tbl_produto.fabrica_i = $login_fabrica
              JOIN tbl_os_produto    ON tbl_os_produto.os             = tbl_os.os
              JOIN tbl_os_item       ON tbl_os_item.os_produto        = tbl_os_produto.os_produto AND tbl_os_item.fabrica_i = $login_fabrica
              JOIN tbl_peca          ON tbl_peca.peca                 = tbl_os_item.peca AND tbl_peca.fabrica = $login_fabrica
			  JOIN tbl_os_status ON tbl_os_status.os_status = tmp_os_intervencao.os_status AND tbl_os_status.fabrica_status = $login_fabrica
			  LEFT JOIN tbl_admin ON tbl_admin.admin = tbl_os_status.admin AND tbl_admin.fabrica = $login_fabrica
            WHERE tbl_os.fabrica = $login_fabrica
               AND tbl_os.excluida IS NOT TRUE
               AND tbl_os.posto <> 6359
               $sql_adicional
               $sql_adicional_2
               $sql_adicional_3
               $sql_adicional_4;";
/*
            ORDER BY status_data2 DESC";
*/
    $res   = pg_exec($con,$sql);
    $total = pg_numrows($res);

    if ($total > 0) {

        $arquivo_conteudo  = '';
        $arquivo_conteudo .= "<table width='700' border='0' cellpadding='2' cellspacing='1' class='tabela' align='center'>";
        $arquivo_conteudo .= "<tr class='titulo_tabela'>";
        $arquivo_conteudo .= "<td colspan='14'>Relação de OS</td>";
        $arquivo_conteudo .= "</tr>";
        $arquivo_conteudo .= "<tr class='titulo_coluna'>";
        $arquivo_conteudo .= "<td>OS</td>";
        $arquivo_conteudo .= "<td>Abertura</td>";
        if ($login_fabrica == 3) $arquivo_conteudo .= "<td>Data NF</td>";
        $arquivo_conteudo .= "<td>Cod Posto</td>";
        $arquivo_conteudo .= "<td>Posto</td>";
        $arquivo_conteudo .= "<td>Produto</td>";
        $arquivo_conteudo .= "<td>Peça Referência</td>";
        $arquivo_conteudo .= "<td>Peça Descrição</td>";
        $arquivo_conteudo .= "<td>Data Pedido</td>";
        $arquivo_conteudo .= "<td>Situação</td>";
        $arquivo_conteudo .= "<td>Data Final Intervenção</td>";
        $arquivo_conteudo .= "<td>Admin</td>";
        if ($login_fabrica == 3) {
            $arquivo_conteudo .= "<td>Justificativa</td>";
            $arquivo_conteudo .= "<td>Motivo</td>";
        }
        $arquivo_conteudo .= "</tr>";

        for ($i = 0 ; $i < $total; $i++) {
            $os                         = trim(pg_result($res, $i, 'os'));
            $sua_os                     = trim(pg_result($res, $i, 'sua_os'));
            $data_abertura              = trim(pg_result($res, $i, 'abertura'));
            $data_nf                    = trim(pg_result($res, $i, 'data_nf'));
            $codigo_posto               = trim(pg_result($res, $i, 'codigo_posto'));
            $posto_nome                 = trim(pg_result($res, $i, 'posto_nome'));
            $produto_referencia         = trim(pg_result($res, $i, 'produto_referencia'));
            $produto_descricao          = trim(pg_result($res, $i, 'produto_descricao'));
            $peca_referencia            = trim(pg_result($res, $i, 'peca_referencia'));
            $peca_descricao             = trim(pg_result($res, $i, 'peca_descricao'));
            $retorna_conserto           = trim(pg_result($res, $i, 'retorna_conserto'));
            $bloqueada_garantia         = trim(pg_result($res, $i, 'bloqueada_garantia'));
            $digitacao_item             = trim(pg_result($res, $i, 'digitacao_item'));
            $servico_realizado          = trim(pg_result($res, $i, 'servico_realizado'));
            $servico_realizadoId        = $servico_realizado;
            $dias_apos_abertura         = trim(pg_result($res, $i, 'dias_apos_abertura'));
			$status_os                  = trim(pg_result($res, $i, 'status_os'));
            $status_observacao          = trim(pg_result($res, $i, 'observacao'));
            $status_data                = trim(pg_result($res, $i, 'data'));
            $admin_sap                  = trim(pg_result($res, $i, 'login'));
            $itemOS                     = trim(pg_result($res, $i, 'itemOS'));

            $sql_just = "SELECT tbl_os_status.observacao, max(os_status)
                           FROM tbl_os_status
                          WHERE tbl_os_status.fabrica_status = $login_fabrica
                            AND tbl_os_status.os = $os
                            AND tbl_os_status.admin IS not null
                            $tipo_intervencao
                            GROUP BY tbl_os_status.observacao;";

            $res_just = pg_exec($con, $sql_just);

            if (pg_numrows($res_just)) {
                $status_observacao_primeiro = trim(pg_result($res_just, 0, 'observacao'));
            }

            if ($status_os == 62 || $status_os == 72 || $status_os == 87 || $status_os == 116) {
                $servico_realizado = 'Pendente';
                $status_data       = "-";
            }
			if ($status_os == 64 || $status_os == 73 || $status_os == 88 || $status_os == 117) {
				$sql = "SELECT tbl_admin.login FROM tbl_os_troca JOIN tbl_admin ON tbl_os_troca.admin=tbl_admin.admin WHERE tbl_os_troca.os=$os"; //Esta consulta verifica se houve troca para a OS
				$res_troca = pg_query($con, $sql);

				if (pg_num_rows($res_troca)) {
					$servico_realizado = '<b style="font-weight:normal;color:blue">Autorizado</b>';
					if (strlen($admin_sap) == 0) {
						$admin_sap = pg_result($res_troca, 0, 'login');
					} // if (strlen($admin_sap) == 0)
				} // if (is_result($res_troca) && pg_num_rows($res_troca))
				else {

                    if ($login_fabrica == 3) {
                        if (!empty($itemOS)) {
                            $condItem = " AND os_item = $itemOS ";
                        }
                    }

					$sql = "SELECT servico_realizado FROM tbl_os_item JOIN tbl_os_produto ON tbl_os_item.os_produto=tbl_os_produto.os_produto WHERE os=$os AND servico_realizado=$id_servico_realizado $condItem"; //Esta consulta busca para ver se tem algum item da OS que seja pedido de peças. Como a OS já está liberada da intervenção, se tiver itens que gerem pedidos é porque foi autorizado pelo admin
					$res_servico = pg_query($con, $sql);

					if (pg_num_rows($res_servico)) {
						$servico_realizado = '<b style="font-weight:normal;color:blue">Autorizado</b>';
					} else {

                        $xobs_st = "";
                        $xobs_st = substr($status_observacao,0,13);
                        $servico_realizado = '<b style="font-weight:normal;color:red">Cancelado</b>';

                        if(($status_os == 73 && $login_fabrica == 3 && $servico_realizadoId != 96) || ($login_fabrica == 3 && $xobs_st != "Justificativa" && $status_os == 64)) {
                            $servico_realizado = '<b style="font-weight:normal;color:blue">Autorizado</b>';    
                        }
					}
				} // else do if (is_result($res_troca) && pg_num_rows($res_troca))
			}
			if (strlen($admin_sap) == 0) {
				$admin_sap = "<i><b>AUTOMÁTICO</b></i>";
			}

			if($status_os == 62 OR $status_os == 65 OR $status_os == 72 OR $status_os == 87 OR $status_os == 116){
				$admin_sap = "";
			}

            if ($status_os == 65) {
                $servico_realizado = '<b style="font-weight:normal;color:orange">Assistência</b>';
            }

            if ($status_os == 120) {//HD 308616
                $servico_realizado = '<b style="font-weight:normal;color:red">Bloqueada</b>';
            }

            if ($status_os == 122) {//HD 308616
                $servico_realizado = '<b style="font-weight:normal;color:#4D4D4D">Justificada</b>';
            }

            if ($status_os == 123) {//HD 308616
                $servico_realizado = '<b style="font-weight:normal;color:#CF9700">Alterada</b>';
            }

            if ($status_os == 140) {//HD 308616
                $servico_realizado = '<b style="font-weight:normal;color:red">Bloqueada</b>';
            }

            if ($status_os == 141) {//HD 308616
                $servico_realizado = '<b style="font-weight:normal;color:#4D4D4D">Justificada</b>';
            }

            if ($status_os == 142) {//HD 308616
                $servico_realizado = '<b style="font-weight:normal;color:#CF9700">Alterada</b>';
            }

            if ($status_os == 139) {//HD 308616
                $servico_realizado = '<b style="font-weight:normal;color:#green">Desmarcada</b>';
            }

           
            $cor = ($cor == "#F7F5F0") ? "#F1F4FA" : "#F7F5F0";
           

            $os_anterior = $os;

          
            $arquivo_conteudo .= "<tr bgcolor='$cor'>";
            $arquivo_conteudo .= "<td nowrap align='center'><a href='os_press.php?os=$os' target='_blank'>$sua_os</a></td>";
            $arquivo_conteudo .= "<td nowrap align='center'>".$data_abertura . "</td>";
            if ($login_fabrica == 3) $arquivo_conteudo .= "<td nowrap align='center'>".$data_nf . "</td>";
            $arquivo_conteudo .= "<td nowrap align='center'>".$codigo_posto . "</td>";
            $arquivo_conteudo .= "<td nowrap align='left'>" . $posto_nome . "</td>";
            $arquivo_conteudo .= "<td nowrap align='left'>" . $produto_referencia . "</td>";

            $arquivo_conteudo .= "<td nowrap align='center'>$peca_referencia</td>";

            if ($login_fabrica == 11 or $login_fabrica == 172) {
                $peca_descricao = substr($peca_descricao,0,20);
            }

            $arquivo_conteudo .= "<td nowrap align='left'>$peca_descricao</td>";
            $arquivo_conteudo .= "<td nowrap align='left'>" . $digitacao_item . "</td>";
            $arquivo_conteudo .= "<td nowrap align='center'>" . $servico_realizado . "</td>";
            $arquivo_conteudo .= "<td nowrap align='center'>" . $status_data . "</td>";
            $arquivo_conteudo .= "<td nowrap align='center'>" . $admin_sap . "</td>";

            if ($login_fabrica == 3) {

                $motivo = '';

                if ($status_os == 72 OR $status_os == 73) {
                    $motivo = "Intervenção SAP";
                }

                if ($status_os == 62 or $status_os == 64 or $status_os == 65) {
                    $motivo = "Intervenção técnica.";
                }

                if ($status_os == 87 or $status_os == 88) {
                    $motivo = "Intervenção de Suprimentos.";
                }

                if ($status_os == 116 or $status_os == 117) {
                    $motivo = "Intervenção de Carteira.";
                }

                if ($status_os == 120) {//HD 308616
                    $motivo = "Bloqueada 90 dias.";
                }

                if ($status_os == 122) {//HD 308616
                    $motivo = "Justificada 90 dias.";
                }

                if ($status_os == 140) {//HD 308616
                    $motivo = "Bloqueada 45 dias.";
                }

                if ($status_os == 141) {//HD 308616
                    $motivo = "Justificada 45 dias.";
                }

                if ($status_os == 123) {//HD 308616
                    $motivo = "Alterada 90 dias.";
                }

                if ($status_os == 142) {//HD 308616
                    $motivo = "Alterada 45 dias.";
                }

                if ($status_os == 139) {//HD 308616
                    $motivo = "Desmarcada Reincidência";
                }

                $justificativa_intervencao = trim($status_observacao_primeiro);
                $justificativa = str_replace('Peça da O.S. com intervenção da fábrica. Justificativa:','',$justificativa_intervencao);
                $justificativa = str_replace('Peça da O.S. com intervenção da fábrica.','',$justificativa);
                $justificativa = str_replace('Peça da OS bloqueada para garantia. Justificativa:','',$justificativa);
                $justificativa = str_replace('Peça da OS bloqueada para garantia.','',$justificativa);
                $justificativa = str_replace('Peça da OS bloqueada para garantia Justificativa:','',$justificativa);
                $justificativa = str_replace('Peça da OS bloqueada para garantia','',$justificativa);
                $justificativa = str_replace('Pedido de Peças a mais de 30 dias. Justificativa:','',$justificativa);
                $justificativa = str_replace('Pedido de Peças a mais de 30 dias.','',$justificativa);
                $justificativa = str_replace('OS com intervenção de suprimentos','',$justificativa);
                $justificativa = str_replace('Pedido de Peças Autorizado Pela Fábrica. Justificativa:','',$justificativa);//HD 308616

                if ($status_os == 64 or $status_os == 65 OR $status_os == 73 OR $status_os == 88) {

                    $status_anterior = 62;

                    if ($status_os == 73) {
                        $status_anterior = 72;
                    }
                    if ($status_os == 88) {
                        $status_anterior = 87;
                    }
                    if ($status_os == 117) {
                        $status_anterior = 116;
                    }

                }

                $encontrar_30_dias = 'Peças a mais de';

                if (($status_os == 72 OR $status_os == 73) AND !(strpos($justificativa_intervencao, $encontrar_30_dias) === false)) {
                    $motivo = "Pedido de peças em OSs acima de 30 dias.";
                }

                if (trim($justificativa_intervencao) == 'O.S. com intervenção da fábrica. OS aberta a mais de 7 dias.') {
                    $motivo = "Produto com 7 dias data abertura sem pedido de peças.";
                }

                $arquivo_conteudo .= "<td nowrap align='left'>".trim($justificativa)."</td>";
                $arquivo_conteudo .= "<td nowrap align='left'>".trim($motivo)."</td>";

            }

            $arquivo_conteudo .= "</tr>";

        }

        $arquivo_conteudo .= "</table>";

        echo "<br />";
        echo $arquivo_conteudo;
        echo "<br />";
        echo "<center><b class='Conteudo'>Total de registros: ".$total."</b></center>";

        $fp = fopen($arquivo, "w");
        fwrite($fp,$arquivo_conteudo);
        fclose($fp);

        echo "<script language='javascript'>document.getElementById('id_download').style.display='inline'</script>";

    } else {
        echo "<br /><br /><center><b class='Conteudo'>Nenhuma OS encontrada</b></center><br /><br />";
    }

    echo "<script language='javascript'>document.getElementById('id_aguardando').style.display='none'</script>";

}

include "rodape.php";

?>
