<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios = "auditoria,gerencia";
include 'autentica_admin.php';

# Pesquisa pelo AutoComplete AJAX
$q = strtolower($_GET["q"]);

if (isset($_GET["q"])) {

    $tipo_busca = $_GET["busca"];
    
    if (strlen($q) > 3) {

        $sql = "SELECT tbl_posto.cnpj, tbl_posto.nome, tbl_posto_fabrica.codigo_posto
                FROM tbl_posto
                JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
                WHERE tbl_posto_fabrica.fabrica = $login_fabrica ";

        if ($tipo_busca == "codigo") {
            $sql .= " AND tbl_posto_fabrica.codigo_posto = '$q' ";
        } else {
            $sql .= " AND UPPER(tbl_posto.nome) like UPPER('%$q%') ";
        }

        $sql .= " LIMIT 50 ";
        $res  = pg_exec($con,$sql);

        if (pg_numrows ($res) > 0) {

            for ($i = 0; $i < pg_numrows ($res); $i++) {
                $cnpj = trim(pg_result($res,$i,'cnpj'));
                $nome = trim(pg_result($res,$i,'nome'));
                $codigo_posto = trim(pg_result($res,$i,'codigo_posto'));
                echo "$codigo_posto|$nome|$cnpj";
                echo "\n";
            }

        }
    }
    exit;
}

$layout_menu = "gerencia";
$title = "Gerência -  Relatório de peças trocadas";

include 'cabecalho.php';

$referencia   = $_POST['referencia'];
$descricao    = $_POST['descricao'];
$data_inicial = $_POST['data_inicial_01'];
$data_final   = $_POST['data_final_01'];
$linha        = $_POST['linha'];
$familia      = $_POST['familia'];
$tipo_troca   = $_POST['tipo_troca'];
$tipo_data    = $_POST['tipo_data'];
$codigo_posto = $_POST['codigo_posto'];
$posto_nome   = $_POST['posto_nome'];
$estado       = $_POST["estado"];

?>

<style type="text/css">
    .Titulo {
        text-align: center;
        font-family: Arial;
        font-size: 10px;
        font-weight: bold;
        color: #FFFFFF;
        background-color: #485989;
    }
    .Conteudo {
        font-family: Arial;
        font-size: 11px;
        font-weight: normal;
    }
    .ConteudoBranco {
        font-family: Arial;
        font-size: 11px;
        color:#FFFFFF;
        font-weight: normal;
    }
    .Mes{
        font-size: 8px;
    }
</style>

<?php

include "javascript_pesquisas.php";
include "javascript_calendario.php";

?>

<script type="text/javascript" src="js/jquery.tablesorter.pack.js"></script> 
<script>
    $(document).ready(function() {
        $.tablesorter.defaults.widgets = ['zebra'];
        $("#relatorio").tablesorter();

    });
</script>

<script language="JavaScript">

    function fnc_pesquisa_peca (campo, campo2, tipo) {

        if (tipo == "referencia" ) {
            var xcampo = campo;
        }

        if (tipo == "descricao" ) {
            var xcampo = campo2;
        }

        if (xcampo.value != "") {
            var url = "";
            url = "peca_pesquisa.php?forma=&campo=" + xcampo.value + "&tipo=" + tipo ;
            janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=500, height=400, top=0, left=0");
            janela.retorno = "<? echo $PHP_SELF ?>";
            janela.referencia= campo;
            janela.descricao= campo2;
            janela.focus();
        }

    }

</script>

<script type="text/javascript" charset="utf-8">
    $(function(){
        $('#data_inicial_01').datePicker({startDate:'01/01/2000'});
        $('#data_final_01').datePicker({startDate:'01/01/2000'});
        $("#data_inicial_01").maskedinput("99/99/9999");
        $("#data_final_01").maskedinput("99/99/9999");
    });
</script>

<script type='text/javascript' src='js/jquery.autocomplete.js'></script>
<link rel="stylesheet" type="text/css" href="js/jquery.autocomplete.css" />
<script type='text/javascript' src='js/jquery.bgiframe.min.js'></script>
<script type='text/javascript' src='js/dimensions.js'></script>

<script language="JavaScript">
    $().ready(function() {

        function formatItem(row) {
            return row[0] + " - " + row[1];
        }
        
        function formatResult(row) {
            return row[0];
        }

        /* Busca pelo Código */
        $("#codigo_posto").autocomplete("<?echo $PHP_SELF.'?busca=codigo'; ?>", {
            minChars: 5,
            delay: 150,
            width: 350,
            matchContains: true,
            formatItem: formatItem,
            formatResult: function(row) {return row[0];}
        });

        $("#codigo_posto").result(function(event, data, formatted) {
            $("#posto_nome").val(data[1]) ;
        });

        /* Busca pelo Nome */
        $("#posto_nome").autocomplete("<?echo $PHP_SELF.'?busca=nome'; ?>", {
            minChars: 5,
            delay: 150,
            width: 350,
            matchContains: true,
            formatItem: formatItem,
            formatResult: function(row) {return row[1];}
        });

        $("#posto_nome").result(function(event, data, formatted) {
            $("#codigo_posto").val(data[0]) ;
            //alert(data[2]);
        });

    });
</script>
<script language='javascript' src='ajax.js'></script>

<form name="frm_pesquisa" method="POST" action="<?=$PHP_SELF?>">
    <input type="hidden" name="acao" />
    <table width="500px" align="center" border="0" cellspacing="0" cellpadding="2">
        <tr class="Titulo">
            <td colspan="4">Preencha os campos para realizar a pesquisa.</td>
        </tr>
        <tr class="Conteudo" bgcolor="#D9E2EF">
            <td  align='left'  colspan="2">
                Finalizada<input type='radio' name='tipo_data' value='finalizada'  <? if ($tipo_data == 'finalizada') { echo "checked" ;} ?>>
            </td>
            <td  align='left'  colspan="2">
                Abertura<input type='radio' name='tipo_data' value='abertura' <? if (strlen($tipo_data)==0 or $tipo_data == 'abertura') { echo "checked" ;} ?>>
            </td>
        </tr>
        <tr class="Conteudo" bgcolor="#D9E2EF">
            <td  align='left'  colspan="2">Data Inicial</td>
            <td  align='left'  colspan="2">Data Final</td>
        </tr>
        <tr class="Conteudo" bgcolor="#D9E2EF">
            <TD  colspan="2"><INPUT size="12" maxlength="10" TYPE="text" class='frm' NAME="data_inicial_01" id="data_inicial_01" value="<? if (strlen($data_inicial) > 0) echo $data_inicial; ?>"></TD>
            <TD colspan="2"><INPUT size="12" maxlength="10" TYPE="text" class='frm'  NAME="data_final_01" id="data_final_01" value="<? if (strlen($data_final) > 0) echo $data_final; ?>"></TD>
        </tr>

        <tr class="Conteudo" bgcolor="#D9E2EF">
            <td colspan="2">Código Peça</td>
            <td colspan="2">Descrição Peça</td>
        </tr>
        <tr class="Conteudo" bgcolor="#D9E2EF">
            <td colspan="2"><input class='frm' type="text" name="referencia" value="<? echo $referencia ?>" size="20" maxlength="20"><a href="javascript: fnc_pesquisa_peca (document.frm_pesquisa.referencia,document.frm_pesquisa.descricao,'referencia')"><IMG SRC="imagens/lupa.png" ></a></td>
            <td colspan="2"><input class='frm' type="text" name="descricao" value="<? echo $descricao ?>" size="30" maxlength="50"><a href="javascript: fnc_pesquisa_peca (document.frm_pesquisa.referencia,document.frm_pesquisa.descricao,'descricao')"><IMG SRC="imagens/lupa.png" ></a></td>
        </tr>
        <tr class="Conteudo" bgcolor="#D9E2EF">
            <td colspan="2">Código Posto</td>
            <td colspan="2">Nome Posto</td>
        </tr>
        <tr class="Conteudo" bgcolor="#D9E2EF">
            <td colspan="2">
                <input type='text' name='codigo_posto' id='codigo_posto' size='20' value='<? echo $codigo_posto ?>' class='frm'>
                <img border="0" src="imagens/lupa.png" style="cursor: hand;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto (document.frm_pesquisa.codigo_posto, document.frm_pesquisa.posto_nome, 'codigo')">
            </td>
            <td colspan="2">
                <input type='text' name='posto_nome' id='posto_nome' size='30' value='<? echo $posto_nome ?>' class='frm'>
                <img border="0" src="imagens/lupa.png" style="cursor: hand;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto (document.frm_pesquisa.codigo_posto, document.frm_pesquisa.posto_nome, 'nome')">
            </td>
        </tr>
        <tr class="Conteudo" bgcolor="#D9E2EF">
            <td colspan="2" align="center">
                Linha
            </td>
            <td colspan="2" align="center">
                Familia
            </td>
        </tr>
        <tr bgcolor="#D9E2EF">
            <td colspan="2" align="center">
                <select name='linha' size='1' class='frm'>
                    <option value='' ></option><?php
                    $sql = "SELECT linha, nome 
                            FROM tbl_linha
                            WHERE fabrica = $login_fabrica
                            AND  ativo is true";
                    $res = pg_exec($con,$sql);
                    if (pg_numrows($res) > 0) {
                        for ($x = 0; pg_numrows($res) > $x; $x++) {
                            $xlinha = pg_result($res,$x,'linha');
                            $descricao = pg_result($res,$x,'nome');
                            echo "<option value='$xlinha'"; if ($xlinha == $linha) { echo "SELECTED" ;} echo ">$descricao</option>";
                        }
                    }?>
                </select>
            </td>
            <td colspan="2" align="center">
                <select name='familia' size='1' class='frm'>
                    <option value='' ></option><?php
                    $sql = "SELECT familia, descricao 
                            FROM tbl_familia
                            WHERE fabrica = $login_fabrica
                            AND  ativo is true";
                    $res = pg_exec($con,$sql);
                    if (pg_numrows($res) > 0) {
                        for ($x = 0; pg_numrows($res) > $x; $x++) {
                            $xfamilia  = pg_result($res,$x,'familia');
                            $descricao = pg_result($res,$x,'descricao');
                            echo "<option value='$xfamilia' "; if ($xfamilia == $familia) { echo "SELECTED" ;} echo " >$descricao</option>";
                        }
                    }?>
                </select>
            </td>
        </tr>
        <tr class="Conteudo" bgcolor="#D9E2EF">
            <td colspan="3" align="center">
                Tipo de troca
            </td>
            <td>Região</td>
        </tr>
        <tr bgcolor="#D9E2EF">
            <td colspan="3" align="center">
                <select name='tipo_troca' size='1' style='width:250px' class='frm'>
                    <option value=''></option><?php
                    $sql = "SELECT servico_realizado, descricao 
                              FROM tbl_servico_realizado
                             WHERE fabrica = $login_fabrica
                               AND troca_de_peca is true
                               AND ativo is true";
                    $res = pg_exec($con,$sql);
                    if (pg_numrows($res) > 0) {
                        for ($x = 0; pg_numrows($res) > $x; $x++) {
                            $servico_realizado           = pg_result($res,$x,'servico_realizado');
                            $servico_realizado_descricao = pg_result($res,$x,'descricao');
                            echo "<option value='$servico_realizado'"; if ($tipo_troca == $servico_realizado) { echo "SELECTED" ;} echo " >$servico_realizado_descricao</option>";
                        }
                    }?>
                </select>
            </td>
            <td>
                <select name="estado" class='frm'>
                    <option value=""             <? if (strlen($estado) == 0) echo " selected "; ?>>TODOS OS ESTADOS</option>
                    <option value="centro-oeste" <? if ($estado == "centro-oeste") echo " selected "; ?>>Região Centro-Oeste (GO,MT,MS,DF)</option>
                    <option value="nordeste"     <? if ($estado == "nordeste")     echo " selected "; ?>>Região Nordeste (MA,PI,CE,RN,PB,PE,AL,SE,BA)</option>
                    <option value="norte"        <? if ($estado == "norte")        echo " selected "; ?>>Região Norte (AC,AM,RR,RO,PA,AP,TO)</option>
                    <option value="sudeste"      <? if ($estado == "sudeste")      echo " selected "; ?>>Região Sudeste (MG,ES,RJ,SP)</option>
                    <option value="sul"          <? if ($estado == "sul")          echo " selected "; ?>>Região Sul (PR,SC,RS)</option>
                    <option value="AC" <? if ($estado == "AC") echo " selected "; ?>>AC - Acre</option>
                    <option value="AL" <? if ($estado == "AL") echo " selected "; ?>>AL - Alagoas</option>
                    <option value="AM" <? if ($estado == "AM") echo " selected "; ?>>AM - Amazonas</option>
                    <option value="AP" <? if ($estado == "AP") echo " selected "; ?>>AP - Amapá</option>
                    <option value="BA" <? if ($estado == "BA") echo " selected "; ?>>BA - Bahia</option>
                    <option value="CE" <? if ($estado == "CE") echo " selected "; ?>>CE - Ceará</option>
                    <option value="DF" <? if ($estado == "DF") echo " selected "; ?>>DF - Distrito Federal</option>
                    <option value="ES" <? if ($estado == "ES") echo " selected "; ?>>ES - Espírito Santo</option>
                    <option value="GO" <? if ($estado == "GO") echo " selected "; ?>>GO - Goiás</option>
                    <option value="MA" <? if ($estado == "MA") echo " selected "; ?>>MA - Maranhão</option>
                    <option value="MG" <? if ($estado == "MG") echo " selected "; ?>>MG - Minas Gerais</option>
                    <option value="MS" <? if ($estado == "MS") echo " selected "; ?>>MS - Mato Grosso do Sul</option>
                    <option value="MT" <? if ($estado == "MT") echo " selected "; ?>>MT - Mato Grosso</option>
                    <option value="PA" <? if ($estado == "PA") echo " selected "; ?>>PA - Pará</option>
                    <option value="PB" <? if ($estado == "PB") echo " selected "; ?>>PB - Paraíba</option>
                    <option value="PE" <? if ($estado == "PE") echo " selected "; ?>>PE - Pernambuco</option>
                    <option value="PI" <? if ($estado == "PI") echo " selected "; ?>>PI - Piauí</option>
                    <option value="PR" <? if ($estado == "PR") echo " selected "; ?>>PR - Paraná</option>
                    <option value="RJ" <? if ($estado == "RJ") echo " selected "; ?>>RJ - Rio de Janeiro</option>
                    <option value="RN" <? if ($estado == "RN") echo " selected "; ?>>RN - Rio Grande do Norte</option>
                    <option value="RO" <? if ($estado == "RO") echo " selected "; ?>>RO - Rondônia</option>
                    <option value="RR" <? if ($estado == "RR") echo " selected "; ?>>RR - Roraima</option>
                    <option value="RS" <? if ($estado == "RS") echo " selected "; ?>>RS - Rio Grande do Sul</option>
                    <option value="SC" <? if ($estado == "SC") echo " selected "; ?>>SC - Santa Catarina</option>
                    <option value="SE" <? if ($estado == "SE") echo " selected "; ?>>SE - Sergipe</option>
                    <option value="SP" <? if ($estado == "SP") echo " selected "; ?>>SP - São Paulo</option>
                    <option value="TO" <? if ($estado == "TO") echo " selected "; ?>>TO - Tocantins</option>
                </select>
            </TD>
        </tr>
        <tr class="Conteudo" bgcolor="#D9E2EF">
            <td>Marca</td>   
            <td colspan="4"></td>         
        </tr>
        <tr bgcolor="#D9E2EF">
            <td>
                <?
                $sql_fricon = "SELECT marca, nome
                                FROM tbl_marca
                                WHERE tbl_marca.fabrica = $login_fabrica
                                ORDER BY tbl_marca.nome ";
                        
                                $res_fricon = pg_query($con, $sql_fricon); ?>
                            
                <select name='marca_logo' id='marca_logo' class="frm"> 
                <?
                if (pg_numrows($res_fricon) > 0) { ?>
                    <option value=''>ESCOLHA</option> <?
                    for ($x = 0 ; $x < pg_numrows($res_fricon) ; $x++){
                        $marca_aux = trim(pg_result($res_fricon, $x, marca));
                        $nome_aux = trim(pg_result($res_fricon, $x, nome));
                
                        if ($marca_logo == $marca_aux) {
                            $selected = "SELECTED";
                        }else {
                            $selected = "";
                        }?>
                        <option value='<?=$marca_aux?>' <?=$selected?>><?=$nome_aux?></option> <?
                    }
                }else { ?>
                    <option value=''>Não existem linhas cadastradas</option><?
                } 
                ?>
                </select>                 
            </td>
            <td colspan="4"></td>
        </tr>
        <tr bgcolor="#D9E2EF">
            <td colspan="4" align="center"><img border="0" src="imagens/btn_pesquisar_400.gif" onclick="document.frm_pesquisa.acao.value='PESQUISAR'; document.frm_pesquisa.submit();" style="cursor: hand;" alt="Preencha as opções e clique aqui para pesquisar"></td>
        </tr>
    </table><?php

    $btn_acao = $_POST['acao'];

    if (strlen($btn_acao) > 0) {

        $referencia   = $_POST['referencia'];
        $descricao    = $_POST['descricao'];

        flush();

        if (strlen($erro) == 0) {

            switch ($tipo_data) {
                case 'abertura': $aux_tipo_data = ' tbl_os.data_abertura ';
                break;
                case 'finalizada': $aux_tipo_data = 'tbl_os.finalizada ';
                break;
            }

            if (strlen($data_inicial) == 0 or $data_inicial == 'dd/mm/aaaa') {
                $erro .= "Favor informar a data inicial para pesquisa<br>";
            }
            
            if (strlen($erro) == 0) {
                $fnc = @pg_exec($con,"SELECT fnc_formata_data('$data_inicial')");
                if (strlen(pg_errormessage($con)) > 0) {
                    $erro = pg_errormessage($con);
                }
                if (strlen($erro) == 0) $aux_data_inicial = @pg_result ($fnc,0,0);

            }

        }

        if (strlen($erro) == 0) {

            if (strlen($data_final) == 0 or $data_final == 'dd/mm/aaaa') {
                $erro .= "Favor informar a data final para pesquisa<br>";
            }

            if (strlen($erro) == 0) {
                $fnc = @pg_exec($con,"SELECT fnc_formata_data('$data_final')");
                if (strlen(pg_errormessage($con)) > 0) {
                    $erro = pg_errormessage($con);
                }
                if (strlen($erro) == 0) $aux_data_final = @pg_result($fnc,0,0);
            }

        }

        $cond_1 = " 1 = 1 ";
        $cond_2 = " 1 = 1 ";
        $cond_3 = " 1 = 1 ";

        if (strlen($_GET["estado"]) > 0 or strlen($_POST["estado"]) > 0) {

            $sql_estado = "AND X.posto IN (SELECT posto FROM tbl_posto JOIN tbl_posto_fabrica USING(posto) WHERE ";

            if ($estado == "centro-oeste") $sql_estado .= " tbl_posto.estado in ('GO','MT','MS','DF') ";
            if ($estado == "nordeste")     $sql_estado .= " tbl_posto.estado in ('MA','PI','CE','RN','PB','PE','AL','SE','BA') ";
            if ($estado == "norte")        $sql_estado .= " tbl_posto.estado in ('AC','AM','RR','RO','PA','AP','TO') ";
            if ($estado == "sudeste")      $sql_estado .= " tbl_posto.estado in ('MG','ES','RJ','SP') ";
            if ($estado == "sul")          $sql_estado .= " tbl_posto.estado in ('PR','SC','RS') ";
            if (strlen($estado) == 2)      $sql_estado .= " tbl_posto.estado = '$estado' ";
            $sql_estado .= "  and fabrica = $login_fabrica)";

        }

        if (strlen($linha) > 0) {
            $sql_linha = " AND tbl_produto.linha = $linha";
        }

        if (strlen($familia) > 0) {
            $sql_familia = " AND tbl_produto.familia = $familia";
        }

        if ($marca_logo > 0) {
            $sql_marca = " AND tbl_os.marca = $marca_logo";
            $cond_marca = " AND X.marca = $marca_logo";
        }

        if (strlen($tipo_troca) > 0) {
            $cond_2 = " tbl_servico_realizado.servico_realizado = $tipo_troca ";
        }

        if (strlen($codigo_posto) > 0) {
            $sql = "SELECT posto from tbl_posto_fabrica where codigo_posto = '$codigo_posto' and fabrica = $login_fabrica";
            $res = pg_exec($con,$sql);
            if (pg_numrows($res) > 0) {
                $posto = pg_result($res,0,posto);
                $cond_3 = " X.posto = $posto ";
            }
        }

        if (strlen($referencia) > 0) {
            $sql = "select peca from tbl_peca where referencia='$referencia' and fabrica = $login_fabrica";
            $res = pg_exec($con,$sql);
            if (pg_numrows($res) > 0) {
                $peca = pg_result($res,0,0);
                $cond_1 = " tbl_os_item.peca = $peca ";
            }
        }

        if (strlen($erro) == 0) {

            $sql = "SELECT tbl_os.os,
                           tbl_os.marca,
                           tbl_produto.linha,
                           tbl_produto.familia,
                           tbl_os_produto.os_produto,
                           tbl_os.posto
                      INTO TEMP tmp_rtp_$login_admin
                      FROM tbl_os
                      JOIN tbl_os_produto ON tbl_os_produto.os      = tbl_os.os
                      JOIN tbl_produto    ON tbl_os_produto.produto = tbl_produto.produto
                     WHERE tbl_os.fabrica = $login_fabrica
                       AND $aux_tipo_data between '$aux_data_inicial 00:00:00' AND '$aux_data_final 23:59:59' 
                           $sql_familia
                           $sql_marca
                           $sql_linha;

                    CREATE INDEX tmp_rtp_OS_$login_admin        ON tmp_rtp_$login_admin(os);
                    CREATE INDEX tmp_rtp_POSTO_$login_admin     ON tmp_rtp_$login_admin(posto);
                    CREATE INDEX tmp_rtp_OSPRODUTO_$login_admin ON tmp_rtp_$login_admin(os_produto);

                    SELECT tbl_posto_fabrica.codigo_posto      ,
                           tbl_posto.nome as nome_posto           ,
                           tbl_peca.referencia as referencia_peca , 
                           tbl_peca.descricao  as descricao_peca  ,
                           X.marca                                ,
                           tbl_servico_realizado.descricao as servico_descricao,
                           tbl_linha.nome,
                           tbl_familia.descricao,
                           count(*) as qtde
                      FROM tmp_rtp_$login_admin X
                      JOIN tbl_linha             ON X.linha                       = tbl_linha.linha
                      JOIN tbl_familia           ON X.familia                     = tbl_familia.familia
                      JOIN tbl_os_item           ON X.os_produto                  = tbl_os_item.os_produto
                      JOIN tbl_servico_realizado ON tbl_os_item.servico_realizado = tbl_servico_realizado.servico_realizado AND $cond_2
                      JOIN tbl_peca              ON tbl_os_item.peca              = tbl_peca.peca                           AND tbl_peca.fabrica = $login_fabrica
                      JOIN tbl_posto             ON tbl_posto.posto               = X.posto
                      JOIN tbl_posto_fabrica     ON tbl_posto.posto               = tbl_posto_fabrica.posto                 AND tbl_posto_fabrica.fabrica = $login_fabrica
                     WHERE $cond_1
                       AND $cond_3
                           $cond_marca
                           $sql_estado
                     GROUP by tbl_posto_fabrica.codigo_posto  ,
                              tbl_posto.nome                  ,
                              tbl_peca.referencia             ,
                              tbl_peca.descricao              ,
                              X.marca                         ,
                              tbl_servico_realizado.descricao ,
                              tbl_linha.nome                  ,
                              tbl_familia.descricao
                    ORDER BY qtde desc";

            $res = pg_exec ($con,$sql);

            echo `rm /tmp/assist/relatorio-troca-peca-$login_fabrica.xls`;

            $fp = fopen("/tmp/assist/relatorio-troca-peca-$login_fabrica.html","w");
            $crlf   = "\r\n";

            $f_header = "<html>\n".
                        "<head>\n".
                        "    <title>RELATÓRIO DE CALLCENTER - $data_xls</title>\n".
                        "    <meta name='Author' content='TELECONTROL NETWORKING LTDA'>\n".
                        "</head>\n".
                        "<body>\n";
            fputs ($fp,$f_header);

            if (pg_numrows($res) > 0) {

                echo "<br /><br /><font size='1' face='verdana'>";
                echo $referencia;
                echo strlen($referencia) > 0 && strlen($descricao) > 0 ? ' - ' : '';
                echo $descricao;
                echo ' ';
                echo $data_inicial;
                echo " até ";
                echo $data_final;
                $excel = "</font><br /><table border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#d2e4fc' align='center' width='500'>";
                $excel .= "<tr class='Titulo'>";
                    $excel .=  "<td>Código</td>";
                    $excel .=  "<td>Posto</td>";
                    $excel .=  "<td>Peca</td>";
                    if ($login_fabrica == 52) {
                        $excel .=  "<td>Marca</td>";
                    }
                    $excel .=  "<td>Linha</td>";
                    $excel .=  "<td>Família</td>";
                    $excel .=  "<td>Serviço</td>";
                    $excel .=  "<td>Qtde</td>";
                $excel .=  "</tr>";

                echo $excel;
                fputs($fp,$excel);

                $total = pg_numrows($res);
                $total_pecas = 0;
                $excel = '';

                for ($i = 0; $i < pg_numrows($res); $i++) {

                    $nome                    = trim(pg_result($res,$i,'nome_posto'));
                    $codigo_posto            = trim(pg_result($res,$i,'codigo_posto'));
                    $qtde                    = trim(pg_result($res,$i,'qtde'));
                    $peca_referencia         = trim(pg_result($res,$i,'referencia_peca'));
                    $peca_descricao          = trim(pg_result($res,$i,'descricao_peca'));
                    $servico_descricao       = trim(pg_result($res,$i,'servico_descricao'));
                    $nome_linha              = trim(pg_result($res,$i,'nome'));
                    $nome_familia            = trim(pg_result($res,$i,'descricao'));
                    $consumidor_marca_logo  = trim(pg_result($res,$i,'marca'));

                    if ($cor == "#F1F4FA")$cor = '#F7F5F0';
                    else                  $cor = '#F1F4FA';

                    $total_pecas = $total_pecas + $qtde;

                    $excel .=  "<tr class='Conteudo'align='center'>";
                        $excel .=  "<td bgcolor='$cor' align='center' nowrap>$codigo_posto</td>";
                        $excel .=  "<td bgcolor='$cor' align='left' nowrap>$nome</td>";
                        $excel .=  "<td bgcolor='$cor' align='left' nowrap>$peca_referencia - $peca_descricao</td>";
                        if ($login_fabrica == 52) {
                            if ($consumidor_marca_logo > 0 ) {
                                    $sqlx="select nome from  tbl_marca where marca = $consumidor_marca_logo;";
                                    $resx=pg_exec($con,$sqlx);
                                    $marca_logo_nome         = pg_fetch_result($resx, 0, 'nome');
                                }else{
                                    $marca_logo_nome = '';
                                }
                            $excel .=  "<td bgcolor='$cor' align='left' nowrap>$marca_logo_nome</td>";    
                        }
                        $excel .=  "<td bgcolor='$cor' align='left' nowrap>$nome_linha</td>";
                        $excel .=  "<td bgcolor='$cor' align='left' nowrap>$nome_familia</td>";
                        $excel .=  "<td bgcolor='$cor' align='left' nowrap>$servico_descricao</td>";
                        $excel .=  "<td bgcolor='$cor' nowrap>$qtde</td>";
                    $excel .=  "</tr>";

                }

                $excel .=  "<tr class='Conteudo'>";
                    $excel .=  "<td colspan='7'><B>Total</b></td>";
                    $excel .=  "<td>$total_pecas</td>";
                $excel .=  "</tr>";
                $excel .=  "</table>";

                echo $excel;
                fputs($fp,$excel);

                $data_xls = date("Y-m-d_H-i-s");
                echo `htmldoc --webpage --size 297x210mm --fontsize 11 --left 8mm -f /www/assist/www/admin/xls/relatorio-troca-peca-$login_fabrica-$data_xls.xls /tmp/assist/relatorio-troca-peca-$login_fabrica.html`;
                echo"<table width='600' border='0' cellspacing='2' cellpadding='2' align='center'>";
                    echo"<tr>";
                        echo "<td align='center'<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'><BR>RELATÓRIO DE CALLCENTER<BR>Clique aqui para fazer o </font><a href='xls/relatorio-troca-peca-$login_fabrica-$data_xls.xls'><font face='Arial, Verdana, Times, Sans' size='2' color='#0000FF'>download do arquivo em EXCEL</font></a>.<br><font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Você pode ver, imprimir e salvar a tabela para consultas off-line.</font></td>";
                    echo "</tr>";
                echo "</table>";

            } else {
                echo "<br><center>Nenhum resultado encontrado</center>";
            }

        }

    }

if (strlen($erro) > 0) {?>
    <table width="730" border="0" cellpadding="2" cellspacing="2" align='center'>
        <tr>
            <td align="center" class='error'><?=$erro?></td>
        </tr>
    </table><?php
}?>

</form><?php

include "rodape.php" ;

?>
