<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios = "financeiro,gerencia,call_center";

include 'autentica_admin.php';
include 'funcoes.php';
include "monitora.php";

$layout_menu = "gerencia";
$title = "RELATÓRIO STATUS DE ORDEM DE SERVIÇO";

# Pesquisa pelo AutoComplete AJAX
$q = strtolower($_GET["q"]);
if (isset($_GET["q"])) {
    $tipo_busca = $_GET["busca"];
    if (strlen($q) > 2) {
        $sql = "SELECT
                    tbl_posto.cnpj,
                    tbl_posto.nome,
                    tbl_posto_fabrica.codigo_posto
                FROM tbl_posto
                    JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
                WHERE tbl_posto_fabrica.fabrica = $login_fabrica ";
        $sql .= ($tipo_busca == "codigo") ? " AND tbl_posto_fabrica.codigo_posto = '$q' " :  " AND UPPER(tbl_posto.nome) like UPPER('%$q%') ";
        $res = pg_exec($con,$sql);
        if (pg_numrows($res) > 0) {
            for ($i = 0; $i < pg_numrows($res); $i++) {
                $cnpj         = trim(pg_result($res,$i,cnpj));
                $nome         = trim(pg_result($res,$i,nome));
                $codigo_posto = trim(pg_result($res,$i,codigo_posto));
                echo "$codigo_posto|$nome|$cnpj";
                echo "\n";
            }
        }
    }
    exit;
}

include 'cabecalho.php';
include "javascript_calendario.php";?>

<script type="text/javascript" charset="utf-8">
    $(function(){
        $('#data_inicial').datePicker({startDate:'01/01/2000'});
        $('#data_final').datePicker({startDate:'01/01/2000'});
        $("#data_inicial").maskedinput("99/99/9999");
        $("#data_final").maskedinput("99/99/9999");
    });
</script>

<script language="JavaScript" src="js/qTip.js" type="text/JavaScript"></script>
<style type="text/css">
    
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
	text-align:left;
}

.subtitulo{

	background-color: #7092BE;
	font:bold 11px Arial;
	color: #FFFFFF;
}

table.tabela tr td{
	font-family: verdana;
	font-size: 11px;
	border-collapse: collapse;
	border:1px solid #596d9b;
}

    #tooltip{
        background: #F7F8A8; /* 25/08/2010 MLG - Original: #5D92B1;*/
        border:2px solid #FDC;
        border-radius: 4px;
        -moz-border-radius: 4px;
        box-shadow: 1px 1px 4px black;
        -moz-box-shadow: 1px 1px 4px black;
        -webkit-box-shadow: 1px 1px 4px black;
        display:none;
        padding: 2px 4px;
        color: #643;
        text-align: center;
        font-family: Arial;
        font-size: 11px;
        font-weight: normal;
        _width: 250px;
        max-width: 250px;
    }
</style>

<? include "javascript_pesquisas.php" ?>
<script>
    function abreOpcao(valor){
        if (valor == 'aberta'){
            $("#opcao_os_aberta").css('display','');
        }else{
            $("#opcao_os_aberta").css('display','none');
        }
    }
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
			alert("Informe toda ou parte da informação para realizar a pesquisa");
		}
    }
</script>

<link rel="stylesheet" type="text/css" href="js/jquery.autocomplete.css" />
<!--<script type="text/javascript" src="js/jquery.js"></script>-->
<script type="text/javascript" src="js/jquery.bgiframe.min.js"></script>
<script type="text/javascript" src="js/jquery.autocomplete.js"></script>
<script type="text/javascript" src="js/jquery.dimensions.js"></script>

<script language="javascript">
    var hora = new Date();
    var engana = hora.getTime();
    $().ready(function() {
        tooltip.init();

        $('#codigo_posto').autocomplete("autocomplete_posto_ajax.php?engana=" + engana,{
            minChars: 3,
            delay: 150,
            width: 450,
            scroll: true,
            scrollHeight: 200,
            matchContains: false,
            highlightItem: false,
            formatItem: function (row)   {return row[0]+"&nbsp;-&nbsp;"+row[1]},
            formatResult: function(row)  {return row[0];}
        });
        $('#codigo_posto').result(function(event, data, formatted) {
            $("#codigo_posto").val(data[0]);
            $("#descricao_posto").val(data[1]);
        });
        $('#descricao_posto').autocomplete("autocomplete_posto_ajax.php?engana=" + engana,{
            minChars: 3,
            delay: 150,
            width: 450,
            scroll: true,
            scrollHeight: 200,
            matchContains: false,
            highlightItem: false,
            formatItem: function (row)   {return row[0]+"&nbsp;-&nbsp;"+row[1]},
            formatResult: function(row)  {return row[0];}
        });
        $('#descricao_posto').result(function(event, data, formatted) {
            $("#codigo_posto").val(data[0]);
            $("#descricao_posto").val(data[1]);
        });
    })
</script><?php

flush();

if (strlen($_GET['data_inicial']) > 0)
    $data_inicial = $_GET['data_inicial'];
else
    $data_inicial = $_POST['data_inicial'];

if (strlen($_GET['data_final']) > 0)
    $data_final = $_GET['data_final'];
else
    $data_final = $_POST['data_final'];

if (strlen($_GET['status']) > 0)
    $status = $_GET['status'];
else
    $status = $_POST['status'];

if (strlen($_GET['estado']) > 0)
    $estado = $_GET['estado'];
else
    $estado = $_POST['estado'];

if (strlen($_GET['referencia']) > 0)
    $referencia = $_GET['referencia'];
else
    $referencia = $_POST['referencia'];

if (strlen($_GET['descricao']) > 0)
    $descricao = $_GET['descricao'];
else
    $descricao = $_POST['descricao'];

if (strlen($codigo_posto) > 0) {
    $sql = "SELECT posto
              FROM tbl_posto_fabrica
             WHERE codigo_posto = '$codigo_posto' AND fabrica = $login_fabrica";

    $res = @pg_exec($con,$sql);

    if (pg_num_rows($res) < 1) {
        $msg_erro .= " Selecione o Posto! ";
    } else {

        $posto = pg_result($res,0,0);

        if (strlen($posto) == 0) {
            $msg_erro   .= " Selecione o Posto! ";
            $cond_posto  = '';
        } else {
            $cond_posto = " AND tbl_os.posto = $posto";
        }

		
    }

}

if ($btn_acao == "Consultar") {
	if (strlen($data_inicial)> 0  AND strlen($data_final)==0){
			$msg_erro = "Data Inválida";
		}
	if (strlen($data_inicial)== 0  AND strlen($data_final)>0){
			$msg_erro = "Data Inválida";
	}
    if ((strlen($data_inicial) > 0 AND $data_inicial!="dd/mm/aaaa") AND (strlen($data_final) > 0 AND $data_final != "dd/mm/aaaa") AND strlen($msg_erro)==0) {

		$xdata_inicial = explode('/', $data_inicial);
		$xdata_inicial = $xdata_inicial[2] . '-' . $xdata_inicial[1] . '-' . $xdata_inicial[0];
		
		$xdata_final = explode('/', $data_final);
		$xdata_final = $xdata_final[2] . '-' . $xdata_final[1] . '-' . $xdata_final[0];

        $ver_data = "Select case when '$xdata_inicial' < '$xdata_final' then true else false end";

        $res = @pg_exec($con,$ver_data);
        $resposta = pg_result($res,0,0);		
		
        if ($resposta == 'f') {
            $msg_erro = "Data Inválida";
        }

        if (strlen($msg_erro) == 0) {
            $fnc = @pg_exec($con,"SELECT fnc_formata_data('$data_inicial')");
            if (strlen( pg_errormessage($con) ) > 0) {
                $msg_erro = pg_errormessage($con);
            }
            if (strlen($msg_erro) == 0)
                $aux_data_inicial = @pg_result($fnc,0,0);
			else
				$msg_erro = "Data Inválida";
        }

        if (strlen($msg_erro) == 0) {
            if (strlen($msg_erro) == 0) {
                $fnc = @pg_exec($con,"SELECT fnc_formata_data('$data_final')");
                if (strlen( pg_errormessage($con)) > 0) {
                    $erro = pg_errormessage($con);
                }
                if (strlen($msg_erro) == 0)
                    $aux_data_final = @pg_result($fnc,0,0);
				else
					$msg_erro = "Data Inválida";
            }
        }

        $cond_data = " AND tbl_os.data_abertura::date BETWEEN '$data_inicial' AND '$data_final' ";

    } else {
		//HD 282017 - INICIO
        if ($login_fabrica != 43 and $login_fabrica != 14 ) {
            $msg_erro = "Defina o Período da Pesquisa";
			$cond_data = " AND tbl_os.data_abertura::date BETWEEN '$data_inicial' AND '$data_final' ";
        }
		//HD 282017 - FIM
        $cond_data = '';

    }


}

if (strlen($msg_erro) > 0) {
    echo "<table width='700' border='0' cellpadding='5' cellspacing='1' align='center'>";
        echo "<tr>";
            echo "<td class='msg_erro'>$msg_erro</td>";
        echo "</tr>";
    echo "</table>";
}?>



<form name='frm_relatorio' METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center'>
    <table width='700' class='formulario'  border='0' cellpadding='5' cellspacing='1' align='center'>
        <tr>
            <td class='titulo_tabela'>
                Parâmetros de Pesquisa
            </td>
        </tr>
        <tr>
            <td>
                <table width='90%' border='0' cellspacing='1' cellpadding='2' class='Conteudo' align='center'>
                    <tr class="Conteudo" bgcolor="#D9E2EF">
                        <td>Data Inicial (abertura)</td>
                        <td>Data Final (abertura)</td>
                        <td>Status da OS</td>
                        <td>Estado</td>
                    </tr>
                    <tr>
                        <td>
                            <input type="text" style="width: 80px" name="data_inicial" id="data_inicial" size="12" maxlength="10" class='frm' value= "<?=$data_inicial?>">
                        </td>
                        <td>
                            <input type="text" style="width: 80px" name="data_final" id="data_final" size="12" maxlength="10" class='frm' value="<?=$data_final?>">
                        </td>
                        <td>
                            <select name="status" style="width: 130px" class="frm">
                                <option <?if ($status == "todos") echo " selected ";?> value="todos">TODOS STATUS</option>
                                <option <?if ($status == "01")    echo " selected ";?> value="01">Aguardando Análise</option>
                                <option <?if ($status == "02")    echo " selected ";?> value="02">Aguardando Peça</option>
                                <option <?if ($status == "03")    echo " selected ";?> value="03">Aguardando Conserto</option>
                                <option <?if ($status == "04")    echo " selected ";?> value="04">OS Consertada</option>
                            </select>
                        </td>
                        <td>
                            <select name="estado" class="frm">
                                <option value="">TODOS ESTADOS</option>
                                <option <?if ($estado == "AC") echo " selected ";?>value='AC'>ACRE</option>
                                <option <?if ($estado == "AL") echo " selected ";?>value='AL'>ALAGOAS</option>
                                <option <?if ($estado == "AP") echo " selected ";?>value='AP'>AMAPÁ</option>
                                <option <?if ($estado == "AM") echo " selected ";?>value='AM'>AMAZONAS</option>
                                <option <?if ($estado == "BA") echo " selected ";?>value='BA'>BAHIA</option>
                                <option <?if ($estado == "CE") echo " selected ";?>value='CE'>CEARÁ</option>
                                <option <?if ($estado == "DF") echo " selected ";?>value='DF'>DISTRITO FEDERAL</option>
                                <option <?if ($estado == "ES") echo " selected ";?>value='ES'>ESPIRITO SANTO</option>
                                <option <?if ($estado == "GO") echo " selected ";?>value='GO'>GOIÁS</option>
                                <option <?if ($estado == "MA") echo " selected ";?>value='MA'>MARANHÃO</option>
                                <option <?if ($estado == "MT") echo " selected ";?>value='MT'>MATO GROSSO</option>
                                <option <?if ($estado == "MS") echo " selected ";?>value='MS'>MATO GROSSO DO SUL</option>
                                <option <?if ($estado == "MG") echo " selected ";?>value='MG'>MINAS GERAIS</option>
                                <option <?if ($estado == "PA") echo " selected ";?>value='PA'>PARÁ</option>
                                <option <?if ($estado == "PB") echo " selected ";?>value='PB'>PARAÍBA</option>
                                <option <?if ($estado == "PR") echo " selected ";?>value='PR'>PARANÁ</option>
                                <option <?if ($estado == "PE") echo " selected ";?>value='PE'>PERNAMBUCO</option>
                                <option <?if ($estado == "PI") echo " selected ";?>value='PI'>PIAUÍ</option>
                                <option <?if ($estado == "RJ") echo " selected ";?>value='RJ'>RIO DE JANEIRO</option>
                                <option <?if ($estado == "RN") echo " selected ";?>value='RN'>RIO GRANDE DO NORTE</option>
                                <option <?if ($estado == "RS") echo " selected ";?>value='RS'>RIO GRANDE DO SUL</option>
                                <option <?if ($estado == "RO") echo " selected ";?>value='RO'>RONDONIA</option>
                                <option <?if ($estado == "RR") echo " selected ";?>value='RR'>RORAIMA</option>
                                <option <?if ($estado == "SC") echo " selected ";?>value='SC'>SANTA CATARINA</option>
                                <option <?if ($estado == "SP") echo " selected ";?>value='SP'>SÃO PAULO</option>
                                <option <?if ($estado == "SE") echo " selected ";?>value='SE'>SERGIPE</option>
                                <option <?if ($estado == "TO") echo " selected ";?>value='TO'>TOCANTINS</option>
                                <option <?if ($estado == "SP-capital") echo " selected ";?>value='SP-capital'>SÃO PAULO - CAPITAL</option>
                                <option <?if ($estado == "SP-interior") echo " selected ";?>value='SP-interior'>SÃO PAULO - INTERIOR</option>
                                <option <?if ($estado == "BR-CO") echo " selected ";?>value='BR-CO'>CENTRO-OESTE</option>
                                <option <?if ($estado == "BR-NE") echo " selected ";?>value='BR-NE'>NORDESTE</option>
                                <option <?if ($estado == "BR-N") echo " selected ";?>value='BR-N'>NORTE</option>
                                <option <?if ($estado == "SUL") echo " selected ";?>value='SUL'>SUL</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td align='left' height='20'>
                            Código do Posto&nbsp;
                        </td>
                        <td align='left' colspan="3">
                            Nome do Posto
                        </td>
                    </tr>
                    <tr>
                        <td align='left' nowrap>
                            <input class="frm" type="text" name="codigo_posto" id="codigo_posto" size="12" value="<? echo $codigo_posto ?>">&nbsp;
                            <img src='imagens/lupa.png' border='0' align='absmiddle' style="cursor:pointer" onclick="javascript: fnc_pesquisa_posto2 (document.frm_relatorio.codigo_posto,document.frm_relatorio.descricao_posto,'codigo')" >
                        </td>
                        <td align='left' nowrap colspan="3">
                            <input class="frm" type="text" name="descricao_posto" id="descricao_posto" size="45" value="<? echo $descricao_posto ?>">&nbsp;
                            <img src='imagens/lupa.png' style="cursor:pointer" border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_posto2 (document.frm_relatorio.codigo_posto,document.frm_relatorio.descricao_posto,'nome')" style="cursor:pointer;">
                        </td>
                    </tr>
                </table>
                <center>
                    <br />
					<!--HD 282017 - INICIO - Alteração de botão -->
                    <input type="button" style="cursor:pointer;" value="Pesquisar" onclick="document.frm_relatorio.btn_acao.value='Consultar'; document.frm_relatorio.submit();" alt="Preencha as opções e clique aqui para pesquisar" />
                    <input type='hidden' name='btn_acao' value='<?=$acao?>' />
					<!--HD 282017 - FIM -->
                </center>
            </td>
        </tr>
    </table>
</form>

<p><?php

    if ($btn_acao == "Consultar" AND strlen($msg_erro) == 0) {

        if (strlen($estado) > 0) {

            $cond_estado = '';

            if ($estado == "BR-CO") {
                $cond_estado = " AND tbl_posto.estado IN ('MT','MS','GO','DF','TO')";
            } else if ($estado == "BR-N") {
                $cond_estado = " AND tbl_posto.estado IN ('AM','AC','AP','PA','RO','RR')";
            } else if ($estado == "BR-NE") {
                $cond_estado = " AND tbl_posto.estado IN ('MA','CE','PI','RN','PB','PN','AL','SE','BA','PE')";
            } else if ($estado == "SUL") {
                $cond_estado = " AND tbl_posto.estado IN ('PR','SC','RS')";
            } else if ($estado == "SP-capital") {
                $cond_estado.= " AND tbl_posto.estado = 'SP'
                                 AND (tbl_posto.cidade ~* 's.o paulo'             OR
                                      tbl_posto.cidade ~* 's.o bernardo do campo' OR
                                      tbl_posto.cidade ~* 'S.o Caetano do Sul'    OR
                                      tbl_posto.cidade ~* 'Guarulhos'             OR
                                      tbl_posto.cidade ~* 'Santo Andr.')";
            } else if ($estado == "SP-interior") {
                $cond_estado.= " AND tbl_posto.estado = 'SP'
                                 AND tbl_posto.cidade !~* 's.o paulo'
                                 AND tbl_posto.cidade !~* 's.o bernardo do campo'
                                 AND tbl_posto.cidade !~* 'S.o Caetano do Sul'
                                 AND tbl_posto.cidade !~* 'Guarulhos'
                                 AND tbl_posto.cidade !~* 'Santo Andr.'";
            } else {
                $cond_estado = " AND tbl_posto.estado = '$estado'";
            }

        }

        if (strlen($status) > 0) {

//  25/08/2010 MLG - HD 285015 - Mostrar sua_os na tabela, não o tbl_os.os [alteradas as 5 queries]
            switch ($status) {
                case '01':// Aguardando Análise
                    $sql = "SELECT DISTINCT os                           AS os,
                                   tbl_os.sua_os                         AS sua_os,
                                   tbl_os.serie                          AS serie,
                                   tbl_os.nota_fiscal                    AS nota_fiscal,
								   tbl_os.data_abertura 				 AS data,
                                   to_char(data_abertura,'DD/MM/YYYY')   AS data_abertura,
                                   to_char(data_conserto,'DD/MM/YYYY')   AS data_conserto,
                                   to_char(data_fechamento,'DD/MM/YYYY') AS data_fechamento,
                                   tbl_os.defeito_constatado             AS defeito_constatado,
                                   tbl_os.solucao_os                     AS solucao_os,
                                   tbl_os.finalizada                     AS finalizada,
                                   tbl_os.excluida                       AS excluida,
                                   tbl_os.consumidor_nome                AS consumidor_nome,
                                   tbl_os.consumidor_email               AS consumidor_email,
                                   tbl_posto.posto                       AS posto_codigo,
                                   tbl_posto.nome                        AS posto_nome,
                                   tbl_posto.cidade                      AS posto_cidade,
                                   tbl_posto.estado                      AS posto_estado,
                                   tbl_posto.cnpj                        AS posto_cnpj,
                                   tbl_posto_fabrica.codigo_posto        AS codigo_posto,
                                   tbl_produto.linha                     AS linha,
                                   tbl_linha.nome                        AS linha_nome,
                                   tbl_produto.referencia                AS referencia,
                                   tbl_produto.descricao                 AS descricao
                              FROM tbl_os
                              JOIN tbl_os_extra                 USING(os)
                              JOIN tbl_produto                  USING(produto)
                              JOIN tbl_posto         ON tbl_posto.posto = tbl_os.posto
                              JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto   = tbl_os.posto
                                                    AND tbl_posto_fabrica.fabrica = tbl_os.fabrica
                              JOIN tbl_linha         ON tbl_linha.linha = tbl_produto.linha
                         LEFT JOIN tbl_marca         ON tbl_marca.marca = tbl_produto.marca
                             WHERE tbl_os.defeito_constatado    IS NULL
                               AND tbl_os.solucao_os            IS NULL
                               AND tbl_os.finalizada            IS NULL
                               AND tbl_os.data_conserto         IS NULL
                               AND tbl_os.fabrica               = $login_fabrica
                               AND (tbl_os.excluida IS NULL OR tbl_os.excluida = 'f')
                               $cond_data
                               $cond_posto
                               $cond_estado
							   ORDER BY data DESC;";

                    $res = pg_exec($con,$sql);

                    break;
                case '02':// Aguardando Peça
                    $sql = "SELECT DISTINCT os AS os
                              INTO TEMP tmp_mostra_analise_$fabrica
                              FROM tbl_os
                              JOIN tbl_os_extra                 USING(os)
                              JOIN tbl_produto                  USING(produto)
                              JOIN tbl_posto ON tbl_posto.posto = tbl_os.posto
                              JOIN tbl_linha ON tbl_linha.linha = tbl_produto.linha
                         LEFT JOIN tbl_marca ON tbl_marca.marca = tbl_produto.marca
                             WHERE tbl_os.defeito_constatado    IS NULL
                               AND tbl_os.solucao_os            IS NULL
                               AND tbl_os.finalizada            IS NULL
                               AND tbl_os.fabrica               = $login_fabrica
                               AND (tbl_os.excluida IS NULL OR tbl_os.excluida = 'f')
                               $cond_data
                               $cond_posto
                               $cond_estado;";

                    $res = pg_exec($con,$sql);

                    $sql = "SELECT DISTINCT os                           AS os,
                                   tbl_os.sua_os                         AS sua_os,
                                   tbl_os.serie                          AS serie,
                                   tbl_os.nota_fiscal                    AS nota_fiscal,
								   tbl_os.data_abertura 				 AS data,
                                   to_char(data_abertura,'DD/MM/YYYY')   AS data_abertura,
                                   to_char(data_conserto,'DD/MM/YYYY')   AS data_conserto,
                                   to_char(data_fechamento,'DD/MM/YYYY') AS data_fechamento,
                                   tbl_os.defeito_constatado             AS defeito_constatado,
                                   tbl_os.solucao_os                     AS solucao_os,
                                   tbl_os.finalizada                     AS finalizada,
                                   tbl_os.excluida                       AS excluida,
                                   tbl_os.consumidor_nome                AS consumidor_nome,
                                   tbl_os.consumidor_email               AS consumidor_email,
                                   tbl_posto.posto                       AS posto_codigo,
                                   tbl_posto.nome                        AS posto_nome,
                                   tbl_posto.cidade                      AS posto_cidade,
                                   tbl_posto.estado                      AS posto_estado,
                                   tbl_posto.cnpj                        AS posto_cnpj,
                                   tbl_posto_fabrica.codigo_posto        AS codigo_posto,
                                   tbl_produto.linha                     AS linha,
                                   tbl_linha.nome                        AS linha_nome,
                                   tbl_produto.referencia                AS referencia,
                                   tbl_produto.descricao                 AS descricao
                              FROM tbl_os
                              JOIN tbl_os_extra                                      USING(os)
                              JOIN tbl_os_produto                                    USING(os)
                              JOIN tbl_os_item                                       USING(os_produto)
                              JOIN tbl_peca                                          USING(peca)
                              JOIN tbl_produto           ON tbl_os.produto           = tbl_produto.produto
                              JOIN tbl_posto             ON tbl_posto.posto          = tbl_os.posto
                              JOIN tbl_posto_fabrica     ON tbl_posto_fabrica.posto  = tbl_os.posto
                                                        AND tbl_posto_fabrica.fabrica= tbl_os.fabrica
                              JOIN tbl_linha             ON tbl_linha.linha          = tbl_produto.linha
                         LEFT JOIN tbl_marca             ON tbl_marca.marca          = tbl_produto.marca
                         LEFT JOIN tbl_defeito                                       USING(defeito)
                         LEFT JOIN tbl_servico_realizado                             USING(servico_realizado)
                         LEFT JOIN tbl_os_item_nf        ON tbl_os_item.os_item      = tbl_os_item_nf.os_item
                         LEFT JOIN tbl_pedido            ON tbl_os_item.pedido       = tbl_pedido.pedido
                         LEFT JOIN tbl_pedido_item       ON tbl_pedido.pedido        = tbl_pedido_item.pedido
                         LEFT JOIN tbl_status_pedido     ON tbl_pedido.status_pedido = tbl_status_pedido.status_pedido
                             WHERE tbl_os.fabrica               = $login_fabrica
                               AND tbl_os.finalizada            IS NULL
                               AND tbl_os.data_conserto         IS NULL
                               AND (tbl_os.excluida             IS NULL OR tbl_os.excluida = 'f')
                               AND tbl_os_item.peca not in (SELECT peca
                                                              FROM tbl_faturamento_item
                                                             WHERE tbl_faturamento_item.pedido = tbl_os_item.pedido)
                               AND os not in (SELECT os FROM tmp_mostra_analise_$fabrica)
                               $cond_data
                               $cond_posto
                               $cond_estado 
							   ORDER BY data DESC;";

                    $res = pg_exec($con,$sql);

                    break;
                case '03':// Aguardando Conserto
                    $sql = "SELECT DISTINCT os AS os
                              INTO TEMP tmp_mostra_analise_$fabrica
                              FROM tbl_os
                              JOIN tbl_os_extra                 USING(os)
                              JOIN tbl_produto                  USING(produto)
                              JOIN tbl_posto ON tbl_posto.posto = tbl_os.posto
                              JOIN tbl_linha ON tbl_linha.linha = tbl_produto.linha
                         LEFT JOIN tbl_marca ON tbl_marca.marca = tbl_produto.marca
                             WHERE tbl_os.defeito_constatado    IS NULL
                               AND tbl_os.solucao_os            IS NULL
                               AND tbl_os.finalizada            IS NULL
                               AND tbl_os.fabrica               = $login_fabrica
                               AND (tbl_os.excluida IS NULL OR tbl_os.excluida = 'f')
                               $cond_data
                               $cond_posto
                               $cond_estado;";

                    $res = pg_exec($con,$sql);

                    $sql = "SELECT DISTINCT os
                              INTO TEMP tmp_mostra_aguardando_$fabrica
                              FROM tbl_os
                              JOIN tbl_os_extra                                  USING(os)
                              JOIN tbl_os_produto                                USING(os)
                              JOIN tbl_os_item                                   USING(os_produto)
                              JOIN tbl_peca                                      USING(peca)
                              JOIN tbl_posto         ON tbl_posto.posto          = tbl_os.posto
                              JOIN tbl_produto       ON tbl_produto.produto      = tbl_os.produto
                              JOIN tbl_linha         ON tbl_linha.linha          = tbl_produto.linha
                         LEFT JOIN tbl_marca         ON tbl_marca.marca          = tbl_produto.marca
                         LEFT JOIN tbl_defeito                                   USING(defeito)
                         LEFT JOIN tbl_servico_realizado                         USING(servico_realizado)
                         LEFT JOIN tbl_os_item_nf    ON tbl_os_item.os_item      = tbl_os_item_nf.os_item
                         LEFT JOIN tbl_pedido        ON tbl_os_item.pedido       = tbl_pedido.pedido
                         LEFT JOIN tbl_pedido_item   ON tbl_pedido.pedido        = tbl_pedido_item.pedido
                         LEFT JOIN tbl_status_pedido ON tbl_pedido.status_pedido = tbl_status_pedido.status_pedido
                             WHERE tbl_os.fabrica       = $login_fabrica
                               AND tbl_os.finalizada    IS NULL
                               AND tbl_os.data_conserto IS NULL
                               AND (tbl_os.excluida IS NULL OR tbl_os.excluida = 'f')
                               AND tbl_os_item.peca not in (SELECT peca
                                                              FROM tbl_faturamento_item
                                                             WHERE tbl_faturamento_item.pedido = tbl_os_item.pedido)
                               AND os not in (SELECT os FROM tmp_mostra_analise_$fabrica)
                               $cond_data
                               $cond_posto
                               $cond_estado;";

                    $res = pg_exec($con,$sql);

                    $sql = "SELECT DISTINCT os                           AS os,
                                   tbl_os.sua_os                         AS sua_os,
                                   tbl_os.serie                          AS serie,
                                   tbl_os.nota_fiscal                    AS nota_fiscal,
								   tbl_os.data_abertura 				 AS data,
                                   to_char(data_abertura,'DD/MM/YYYY')   AS data_abertura,
                                   to_char(data_conserto,'DD/MM/YYYY')   AS data_conserto,
                                   to_char(data_fechamento,'DD/MM/YYYY') AS data_fechamento,
                                   tbl_os.defeito_constatado             AS defeito_constatado,
                                   tbl_os.solucao_os                     AS solucao_os,
                                   tbl_os.finalizada                     AS finalizada,
                                   tbl_os.excluida                       AS excluida,
                                   tbl_os.consumidor_nome                AS consumidor_nome,
                                   tbl_os.consumidor_email               AS consumidor_email,
                                   tbl_posto.posto                       AS posto_codigo,
                                   tbl_posto.nome                        AS posto_nome,
                                   tbl_posto.cidade                      AS posto_cidade,
                                   tbl_posto.estado                      AS posto_estado,
                                   tbl_posto.cnpj                        AS posto_cnpj,
                                   tbl_posto_fabrica.codigo_posto        AS codigo_posto,
                                   tbl_produto.linha                     AS linha,
                                   tbl_linha.nome                        AS linha_nome,
                                   tbl_produto.referencia                AS referencia,
                                   tbl_produto.descricao                 AS descricao
                              FROM tbl_os
                              JOIN tbl_os_extra      USING(os)
                              JOIN tbl_produto       ON tbl_os.produto            = tbl_produto.produto
                              JOIN tbl_posto         ON tbl_posto.posto           = tbl_os.posto
                              JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto   = tbl_os.posto
                                                    AND tbl_posto_fabrica.fabrica = tbl_os.fabrica
                              JOIN tbl_linha         ON tbl_linha.linha           = tbl_produto.linha
                         LEFT JOIN tbl_marca         ON tbl_marca.marca           = tbl_produto.marca
                             WHERE tbl_os.fabrica = $login_fabrica
                               AND data_conserto  IS NULL
                               AND finalizada     IS NULL
                               AND (excluida IS NULL OR excluida = 'f')
                               AND os NOT IN (SELECT os FROM  tmp_mostra_aguardando_$fabrica)
                               AND os NOT IN (SELECT os FROM  tmp_mostra_analise_$fabrica)
                               $cond_data
                               $cond_posto
                               $cond_estado
							   ORDER BY data DESC;";

                    $res = pg_exec($con,$sql);

                    break;
                case '04'://OS Consertada
                    $sql = "SELECT os                                    AS os,
                                   tbl_os.sua_os                         AS sua_os,
                                   tbl_os.serie                          AS serie,
                                   tbl_os.nota_fiscal                    AS nota_fiscal,
								   tbl_os.data_abertura 				 AS data,								   
                                   to_char(data_abertura,'DD/MM/YYYY')   AS data_abertura,
                                   to_char(data_conserto,'DD/MM/YYYY')   AS data_conserto,
                                   to_char(data_fechamento,'DD/MM/YYYY') AS data_fechamento,
                                   tbl_os.defeito_constatado             AS defeito_constatado,
                                   tbl_os.solucao_os                     AS solucao_os,
                                   tbl_os.finalizada                     AS finalizada,
                                   tbl_os.excluida                       AS excluida,
                                   tbl_os.consumidor_nome                AS consumidor_nome,
                                   tbl_os.consumidor_email               AS consumidor_email,
                                   tbl_posto.posto                       AS posto_codigo,
                                   tbl_posto.nome                        AS posto_nome,
                                   tbl_posto.cidade                      AS posto_cidade,
                                   tbl_posto.estado                      AS posto_estado,
                                   tbl_posto.cnpj                        AS posto_cnpj,
                                   tbl_posto_fabrica.codigo_posto        AS codigo_posto,
                                   tbl_produto.linha                     AS linha,
                                   tbl_linha.nome                        AS linha_nome,
                                   tbl_produto.referencia                AS referencia,
                                   tbl_produto.descricao                 AS descricao
                              FROM tbl_os
                              JOIN tbl_os_extra      USING(os)
                              JOIN tbl_produto       ON tbl_os.produto            = tbl_produto.produto
                              JOIN tbl_posto         ON tbl_posto.posto           = tbl_os.posto
                              JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto   = tbl_os.posto
                                                    AND tbl_posto_fabrica.fabrica = tbl_os.fabrica
                              JOIN tbl_linha         ON tbl_linha.linha           = tbl_produto.linha
                         LEFT JOIN tbl_marca         ON tbl_marca.marca           = tbl_produto.marca
                             WHERE tbl_os.fabrica = $login_fabrica
                               AND data_conserto  IS NOT NULL
                               AND finalizada     IS NULL
                               AND (excluida IS NULL OR excluida = 'f')
                               $cond_data
                               $cond_posto
                               $cond_estado
							   ORDER BY data DESC;";

                    $res = pg_exec($con,$sql);

                break;
                case 'todos' :
                    $sql = "SELECT os                                    AS os,
                                   tbl_os.sua_os                         AS sua_os,
                                   tbl_os.serie                          AS serie,
                                   tbl_os.nota_fiscal                    AS nota_fiscal,
                                   to_char(data_abertura,'DD/MM/YYYY')   AS data_abertura,
                                   to_char(data_conserto,'DD/MM/YYYY')   AS data_conserto,
                                   to_char(data_fechamento,'DD/MM/YYYY') AS data_fechamento,
                                   tbl_os.defeito_constatado             AS defeito_constatado,
                                   tbl_os.solucao_os                     AS solucao_os,
                                   tbl_os.finalizada                     AS finalizada,
                                   tbl_os.excluida                       AS excluida,
                                   tbl_os.consumidor_nome                AS consumidor_nome,
                                   tbl_os.consumidor_email               AS consumidor_email,
                                   tbl_posto.posto                       AS posto_codigo,
                                   tbl_posto.nome                        AS posto_nome,
                                   tbl_posto.cidade                      AS posto_cidade,
                                   tbl_posto.estado                      AS posto_estado,
                                   tbl_posto.cnpj                        AS posto_cnpj,
                                   tbl_posto_fabrica.codigo_posto        AS codigo_posto,
                                   tbl_produto.linha                     AS linha,
                                   tbl_linha.nome                        AS linha_nome,
                                   tbl_produto.referencia                AS referencia,
                                   tbl_produto.descricao                 AS descricao
                              FROM tbl_os
                              JOIN tbl_produto       USING(produto)
                              JOIN tbl_posto         ON tbl_posto.posto           = tbl_os.posto
                              JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto   = tbl_os.posto
                                                    AND tbl_posto_fabrica.fabrica = tbl_os.fabrica
                              JOIN tbl_linha         ON tbl_linha.linha           = tbl_produto.linha
                             WHERE tbl_os.fabrica               = $login_fabrica
                               AND tbl_os.finalizada            IS NULL
                               AND (tbl_os.excluida IS NULL OR tbl_os.excluida = 'f')
                               $cond_data
                               $cond_estado
                               $cond_posto
							   ORDER BY tbl_os.data_abertura DESC;";

                    $res = pg_exec($con,$sql);

            }

            if (pg_numrows($res) > 0) {

                $excel  = "<br />";
                $excel .= "<table class='tabela' align='center' cellspacing='1'>";
					$excel .= "<tr class='titulo_tabela'><td colspan='13' style='font-size:14px;'>
									RELATÓRIO DE STATUS DE OS
									</td>
								</tr>";
                    $excel .= "<tr class='titulo_coluna' height='25'>";
                        $excel .= "<td width='64'>O.S.</td>";
                        $excel .= "<td width='120'>Série</td>";
                        $excel .= "<td width= '50'>N.F.</td>";
                        $excel .= "<td width= '70' title='Data de abertura' style='cursor:help;'>D.A.</td>";
                        $excel .= "<td width= '70' title='Data de conserto do produto' style='cursor:help;'>D.C.</td>";
                        $excel .= "<td width= '70' title='Data de fechamento' style='cursor:help;'>D.F.</td>";
                        $excel .= "<td width= '150'>Status</td>";
                        $excel .= "<td width= '50'>Posto</td>";
                        $excel .= "<td width='100'>CNPJ</td>";
                        $excel .= "<td width='80'>Cidade</td>";
                        $excel .= "<td width='30'>U.F.</td>";
                        $excel .= "<td width='58'>Linha</td>";
                        $excel .= "<td width='60'>Produto</td>";
                    $excel .=  "</tr>";

                    for ($i = 0; $i < pg_numrows($res); $i++) {

                        $os                 = trim(pg_fetch_result($res, $i, 'os'));
                        $sua_os             = trim(pg_fetch_result($res, $i, 'sua_os'));//  25/08/2010 MLG - HD 285015 - Mostrar sua_os na tabela, não o tbl_os.os
                        $serie              = trim(pg_fetch_result($res, $i, 'serie'));
                        $nota_fiscal        = trim(pg_fetch_result($res, $i, 'nota_fiscal'));
                        $data_abertura      = trim(pg_fetch_result($res, $i, 'data_abertura'));
                        $data_conserto      = trim(pg_fetch_result($res, $i, 'data_conserto'));
                        $data_fechamento    = trim(pg_fetch_result($res, $i, 'data_fechamento'));
                        $defeito_constatado = trim(pg_fetch_result($res, $i, 'defeito_constatado'));
                        $solucao_os         = trim(pg_fetch_result($res, $i, 'solucao_os'));
                        $finalizada         = trim(pg_fetch_result($res, $i, 'finalizada'));
                        $excluida           = trim(pg_fetch_result($res, $i, 'excluida'));
                        $consumidor_nome    = trim(pg_fetch_result($res, $i, 'consumidor_nome'));
                        $consumidor_email   = trim(pg_fetch_result($res, $i, 'consumidor_email'));
                        $posto_codigo       = trim(pg_fetch_result($res, $i, 'posto_codigo'));
                        $codigo_posto       = trim(pg_fetch_result($res, $i, 'codigo_posto'));// 25/08/2010 MLG Também estava mostrando o posto e não o codigo_posto
                        $posto_nome         = trim(pg_fetch_result($res, $i, 'posto_nome'));
                        $posto_cidade       = trim(pg_fetch_result($res, $i, 'posto_cidade'));
                        $posto_estado       = trim(pg_fetch_result($res, $i, 'posto_estado'));
                        $posto_cnpj         = trim(pg_fetch_result($res, $i, 'posto_cnpj'));
                        $linha              = trim(pg_fetch_result($res, $i, 'linha'));
                        $linha_nome         = trim(pg_fetch_result($res, $i, 'linha_nome'));
                        $referencia         = trim(pg_fetch_result($res, $i, 'referencia'));
                        $descricao          = trim(pg_fetch_result($res, $i, 'descricao'));

                        $cor = ($i % 2 == 0) ? '#F1F4FA' : "#F7F5F0";

                        if ($status == "01") $status_nome = 'Aguardando Análise';
                        if ($status == "02") $status_nome = 'Aguardando Peça';
                        if ($status == "03") $status_nome = 'Aguardando Conserto';
                        if ($status == "04") $status_nome = 'OS Consertada';

                        if ($status == "todos") {

                            //TODO - COLOCAR JOIN COM tbl_faturamento_item
                            $sql_peca = "SELECT tbl_os_item.peca
                                           FROM tbl_os_item
                                           JOIN tbl_os_produto  ON tbl_os_produto.os_produto = tbl_os_item.os_produto
                                           JOIN tbl_os          ON tbl_os.os                 = tbl_os_produto.os
                                      LEFT JOIN tbl_pedido      ON tbl_os_item.pedido        = tbl_pedido.pedido
                                      LEFT JOIN tbl_pedido_item ON tbl_pedido.pedido         = tbl_pedido_item.pedido
                                          WHERE tbl_os.os = $os
                                            AND tbl_os_item.peca not in (SELECT peca
                                                                           FROM tbl_faturamento_item
                                                                          WHERE tbl_faturamento_item.pedido = tbl_os_item.pedido)";

                            $res_peca   = pg_exec($con,$sql_peca);
                            $total_peca = pg_numrows($res_peca);

                            if ($defeito_constatado == null && $solucao_os == null && $finalizada == null && ($excluida == null || $excluida == 'f') && $data_conserto == null) {
                                $status_nome = 'Aguardando Análise';
                            } else if ($finalizada == null && $data_conserto == null && ($excluida == null || $excluida == 'f') && $total_peca > 0) {
                                $status_nome = 'Aguardando Peça';
                            } else if ($data_conserto != null && $finalizada == null && ($excluida == null || $excluida == 'f')) {
                                $status_nome = 'OS Consertada';
                            } else {
                                $status_nome = 'Aguardando Conserto';
                            }

                        }
                        $tip_cidade = (strlen($posto_cidade) > 12) ? " style='white-space:nowrap;overflow:hidden;text-overflow:ellipsis' title='$posto_cidade'" : '';
                        $excel .=  "<tr nowrap class='table_line' bgcolor='$cor'>";
//  25/08/2010 MLG - HD 285015 - Mostrar sua_os na tabela, não o tbl_os.os
                        $excel .=  "<td nowrap align='left'><a href='os_press.php?os=$os' target='_blank'>$sua_os</a></td>";
                        $excel .=  "<td nowrap>$serie</td>";
                        $excel .=  "<td nowrap align='left'>$nota_fiscal</td>";
                        $excel .=  "<td nowrap align='left'>$data_abertura</td>";
                        $excel .=  "<td nowrap align='left'>$data_conserto</td>";
                        $excel .=  "<td nowrap align='left'>$data_fechamento</td>";
                        $excel .=  "<td>$status_nome</td>";
                        $excel .=  "<td nowrap align='left' title='$posto_nome'>$codigo_posto</td>";
                        $excel .=  "<td nowrap align='left'>$posto_cnpj</td>";
                        $excel .=  "<td$tip_cidade>$posto_cidade</td>";
                        $excel .=  "<td nowrap>$posto_estado</td>";
                        $excel .=  "<td nowrap>$linha_nome</td>";
                        $excel .=  "<td nowrap align='left' title='$descricao'>$referencia</td>";
                        $excel .=   "</tr>";

                    }

                $excel .=   "</table>";

                echo $excel;

                $data_xls = date("Y-m-d_H-i-s");

                $arquivo_nome = "relatorio-status-os-$login_fabrica-$data_xls.xls";
                $path         = "/www/assist/www/admin/xls/";
                $path_tmp     = "/tmp/";

                $arquivo_completo     = $path.$arquivo_nome;
                $arquivo_completo_tmp = $path_tmp.$arquivo_nome;

                $fp = fopen($arquivo_completo, "w+");
                fputs($fp, $excel);
                fclose($fp);
				echo "<br>";
                echo "<table width='600' border='0' cellspacing='2' cellpadding='2' align='center'>";
                    echo "<tr>";
						echo "<td><input type='button' onclick=\"window.location='xls/$arquivo_nome' \" value='Download em Excel'></td>";
					echo "</tr>";
                echo "</table>";

            } else {
                echo "<p style='font-size: 12px; text-align=center;'>Nenhum resultado encontrado</p>";
            }

        }

    }

    include 'rodape.php';?>