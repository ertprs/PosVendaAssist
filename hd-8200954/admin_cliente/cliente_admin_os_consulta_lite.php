<?php
include "dbconfig.php";
include "dbconnect-inc.php";

$admin_privilegios = "call_center";

include "autentica_admin.php";
include_once '../helpdesk/mlg_funciones.php';

$layout_menu = "callcenter";
$title       = "Menu Call-Center";

$erro = array(
    "msg"    => array(),
    "campos" => array()
);

if ($_POST) {
    $data_inicial       = $_POST["data_inicial"];
    $data_final         = $_POST["data_final"];
    $status             = $_POST["status"];
    $os                 = trim($_POST["os"]);
    $hd                 = trim($_POST["hd"]);
    $serie              = trim($_POST["serie"]);
    $nf                 = trim($_POST["nf"]);
    $consumidor_cpf     = $_POST["consumidor_cpf"];
    $consumidor_nome    = trim($_POST["consumidor_nome"]);
    $familia            = $_POST["familia"];
    $produto_referencia = strtoupper(trim($_POST["produto_referencia"]));
    $produto_descricao  = strtoupper(trim($_POST["produto_descricao"]));

    if (empty($os) && empty($hd)) {
        if (empty($data_inicial) || empty($data_final)) {
            $erro["msg"][]    = "Informe a data inicial e final para realizar a pesquisa";
            $erro["campos"][] = "data";
        } else {
            list($dia, $mes, $ano) = explode("/", $data_inicial);
            $data_inicial = "$ano-$mes-$dia";

            list($dia, $mes, $ano) = explode("/", $data_final);
            $data_final = "$ano-$mes-$dia";

            if (!strtotime($data_inicial) || !strtotime($data_final)) {
                $erro["msg"][]    = "Data inválida";
                $erro["campos"][] = "data";
            } else if (strtotime($data_final) < strtotime($data_final)) {
                $erro["msg"][]    = "Data final não pode ser inferior a Data inicial";
                $erro["campos"][] = "data";
            }
        }
    }

    if (!empty($produto_referencia) || !empty($produto_descricao)) {
        $sqlProduto = "
            SELECT produto
            FROM tbl_produto
            WHERE fabrica_i = {$login_fabrica}
            AND (
                (UPPER(referencia) = '{$produto_referencia}')
                OR
                (UPPER(descricao) = '{$produto_descricao}')
            )
        ";
        $qryProduto = pg_query($con, $sqlProduto);

        if (!pg_num_rows($qryProduto)) {
            $erro["msg"][]    = "Produto não encontrado";
            $erro["campos"][] = "produto";
        } else {
            $produto = pg_fetch_result($qryProduto, 0, "produto");
        }
    }

    if (empty($erro["msg"])) {
        if (empty($os) && empty($hd)) {
            $whereData = "AND ((hc.data BETWEEN '{$data_inicial}' AND '{$data_final}') OR (o.data_digitacao BETWEEN '{$data_inicial} 00:00:00' AND '{$data_final} 23:59:59'))";
        } else if (!empty($os)) {
            $whereOs = "AND o.os = {$os}";
        } else if (!empty($hd)) {
            $whereHd = "AND hc.hd_chamado = {$hd}";
        }

        if (!empty($serie)) {
            $whereSerie = "AND hce.serie = '{$serie}'";
        }

        if (!empty($nf)) {
            $whereNf = "AND hce.nota_fiscal = '{$nf}'";
        }

        if (!empty($consumidor_cpf)) {
            $whereConsumidorCpf = "AND hce.cpf = '{$consumidor_cpf}'";
        }

        if (!empty($consumidor_nome)) {
            $whereConsumidorNome = "AND hce.nome ILIKE '%{$consumidor_nome}%'";
        }

        if (!empty($familia)) {
            $whereFamilia = "AND f.familia = {$familia}";
        }

        if (!empty($produto)) {
            $whereProduto = "AND p.produto = {$produto}";
        }

        if (!empty($status)) {
            $whereStatus = "AND hc.status = '{$status}'";
        }

        $sqlOs = "
            SELECT
                hc.hd_chamado,
                TO_CHAR(hc.data, 'DD/MM/YYYY') AS data,
                hc.status,
                o.os,
                hce.serie,
                hce.nome AS consumidor_nome,
                hce.cpf AS consumidor_cpf,
                c.estado AS consumidor_estado,
                c.nome AS consumidor_cidade,
                hce.nota_fiscal,
                p.referencia AS produto_referencia,
                p.descricao AS produto_descricao,
                f.descricao AS familia
            FROM tbl_hd_chamado hc
            INNER JOIN tbl_hd_chamado_extra hce ON hce.hd_chamado = hc.hd_chamado
            INNER JOIN tbl_cidade c ON c.cidade = hce.cidade
            LEFT JOIN tbl_os o ON (o.hd_chamado = hc.hd_chamado OR hce.os = o.os)
                              AND o.fabrica     = {$login_fabrica}
                              AND o.excluida   IS NOT TRUE
            INNER JOIN tbl_produto p ON p.produto         = hce.produto
                                    AND p.fabrica_i       = {$login_fabrica}
            INNER JOIN tbl_familia f ON f.familia         = p.familia
                                    AND f.fabrica         = {$login_fabrica}
            INNER JOIN tbl_posto_fabrica pf ON pf.posto   = o.posto
                                           AND pf.fabrica = {$login_fabrica}
            INNER JOIN tbl_posto po ON po.posto = pf.posto
            WHERE hc.fabrica = {$login_fabrica}
            AND hc.cliente_admin = {$login_cliente_admin}
            {$whereData}
            {$whereHd}
            {$whereOs}
            {$whereSerie}
            {$whereNf}
            {$whereConsumidorCpf}
            {$whereConsumidorNome}
            {$whereFamilia}
            {$whereProduto}
            {$whereStatus}
        ";
        $qryOs = pg_query($con, $sqlOs);

        if (!pg_num_rows($qryOs)) {
            $erro["msg"][] = "Nenhuma OS encontrada";
        }
    }
}

include "cabecalho.php";

include "../admin/javascript_pesquisas_novo.php";

?>

<link rel="stylesheet" type="text/css" href="plugins/shadowbox/shadowbox.css" />
<link type="text/css" href="plugins/jquery/datepick/telecontrol.datepick.css" rel="stylesheet" />
<style>

form, table.form-pesquisa {
    background-color: #D9E2EF;
    width: 700px;
    margin: 0 auto;
}

table.form-pesquisa,
table.form-pesquisa tr,
table.form-pesquisa td,
table.form-pesquisa th {
    border: 0px;
}

table.form-pesquisa tbody td {
  width: 30%;
}

table.form-pesquisa th {
    padding: 3px;
}

.titulo-tabela {
    background-color: #596d9b;
    color: #FFF;
}

.text-left {
    text-align: left;
}

.text-right {
    text-align: right;
}

.text-center {
    text-align: center;
}

tr.titulo td {
    padding-top: 20px;
}

.lupa {
    cursor: pointer;
}

th.erro {
    background-color: #F50C0C;
    color: #FFF;
}

table.resultado {
    margin: 0 auto;
    border-collapse: collapse;
}

table.resultado > thead > tr.titulo-tabela > th {
    padding: 7px 14px 7px 14px;
    border: 1px solid #FFF;
}

table.resultado > tbody > tr > td {
    padding: 7px 14px 7px 14px;
    border: 1px solid #000;
}

</style>

<br />

<form method="post" >

    <table class="form-pesquisa formulario">
        <thead>
            <?php
            if (count($erro["msg"]) > 0) {
            ?>
                <tr>
                    <th class="erro" colspan="4" ><?=implode("<br />", $erro["msg"])?></th>
                </tr>
            <?php
            }
            ?>
            <tr>
                <th class="text-right" colspan="4" style="font-size: 12px; color: #F50C0C;" >* Obrigatório</th>
            </tr>
            <tr>
                <th class="titulo-tabela" colspan="4" >PARÂMETROS DE PESQUISA</th>
            </tr>
        </thead>
        <tbody>
            <tr class="titulo" >
                <td>Data Inicial</td>
                <td>Data Final</td>
                <td>Status</td>
            </tr>
            <tr>
                <td><input type="text" class="data" name="data_inicial" value="<?=$_POST['data_inicial']?>" /></td>
                <td><input type="text" class="data" name="data_final" value="<?=$_POST['data_final']?>" /></td>
                <td>
                <?=array2select(
                    'status', 'status',
                    pg_fetch_pairs(
                        $con,
                        "SELECT status FROM tbl_hd_status WHERE fabrica = $login_fabrica"
                    ), $_POST['status'],
                    "class='frm' style='width: 135px'", ' ',
                    false
                );?>
                </td>
            </tr>
            <tr class="titulo" >
                <td>Número do Protocolo</td>
                <td>Número da OS</td>
                <td>Número de Série</td>
            </tr>
            <tr>
                <td><input type="text" id="hd" name="hd" value="<?=$_POST['hd']?>" /></td>
                <td><input type="text" id="os" name="os" value="<?=$_POST['os']?>" /></td>
                <td><input type="text" name="serie" value="<?=$_POST['serie']?>" /></td>
            </tr>
            <tr class="titulo" >
                <td>NF Compra</td>
                <td>CPF/CNPJ Consumidor</td>
                <td>Nome Consumidor</td>
            </tr>
            <tr>
                <td><input type="text" name="nf" value="<?=$_POST['nf']?>" /></td>
                <td><input type="text" class="cpf" name="consumidor_cpf" value="<?=$_POST['consumidor_cpf']?>" /></td>
                <td><input type="text" name="consumidor_nome" value="<?=$_POST['consumidor_nome']?>" /></td>
            </tr>
            <tr class="titulo" >
                <td>Família</td>
                <td>Referência Produto</td>
                <td>Descrição Produto</td>
            </tr>
            <tr>
                <td>
                    <?php
                    echo array2select(
                        'familia', 'familia',
                        pg_fetch_pairs($con, "SELECT familia, descricao FROM tbl_familia WHERE fabrica = $login_fabrica AND ativo ORDER BY familia"),
                        $_POST['familia'],
                        'class="frm"', ' ', true
                    );?>
                </td>
                <td>
                    <input type="text" name="produto_referencia" value="<?=$_POST['produto_referencia']?>" />
                    <span class="lupa" ><img src="../imagens/lupa.png" /></span>
                </td>
                <td>
                    <input type="text" name="produto_descricao" value="<?=$_POST['produto_descricao']?>" />
                    <span class="lupa" ><img src="../imagens/lupa.png" /></span>
                </td>
            </tr>
        </tbody>
    </table>

    <br />

    <p class="text-center" >
        <button type="submit" name="pesquisar" >Pesquisar</button>
    </p>

    <br />

</form>

<?php
if (isset($qryOs) && pg_num_rows($qryOs) > 0) {
    $csv = "../admin/xls/ca-relatorio-oss-".date("YmdHisu").".csv";

    system("touch {$csv}");

    file_put_contents($csv, "Protocolo;Data Abertura;Status;OS;Consumidor Nome;Consumidor CPF;Consumidor Estado;Consumidor Cidade;Nota Fiscal;Produto Referência;Produto Nome;Série;Família\n", FILE_APPEND);

    while ($row = pg_fetch_object($qryOs)) {
        file_put_contents($csv, "{$row->hd_chamado};{$row->data};{$row->status};{$row->os};{$row->consumidor_nome};{$row->consumidor_cpf};{$row->consumidor_estado};{$row->consumidor_cidade};{$row->nota_fiscal};{$row->produto_referencia};{$row->produto_descricao};{$row->serie};{$row->familia}\n", FILE_APPEND);
    }

    pg_result_seek($qryOs, 0);
    ?>
    <br />

    <table class="resultado" >
        <thead>
            <tr>
                <th class="text-left" colspan="13" ><a href="<?=$csv?>" target="_blank" >Download CSV</a></th>
            </tr>
            <tr class="titulo-tabela" >
                <th>Protocolo</th>
                <th>Data Abertura</th>
                <th>Status</th>
                <th>OS</th>
                <th>Consumidor Nome</th>
                <th>Consumidor CPF</th>
                <th>Consumidor Estado</th>
                <th>Consumidor Cidade</th>
                <th>Nota Fiscal</th>
                <th>Produto Referência</th>
                <th>Produto Nome</th>
                <th>Série</th>
                <th>Família</th>
            </tr>
        </thead>
        <tbody>
            <?php
            while ($row = pg_fetch_object($qryOs)) {
                echo "
                    <tr>
                        <td>{$row->hd_chamado}</td>
                        <td>{$row->data}</td>
                        <td>{$row->status}</td>
                        <td>{$row->os}</td>
                        <td>{$row->consumidor_nome}</td>
                        <td>{$row->consumidor_cpf}</td>
                        <td>{$row->consumidor_estado}</td>
                        <td>{$row->consumidor_cidade}</td>
                        <td>{$row->nota_fiscal}</td>
                        <td>{$row->produto_referencia}</td>
                        <td>{$row->produto_descricao}</td>
                        <td>{$row->serie}</td>
                        <td>{$row->familia}</td>
                    </tr>
                ";
            }
            ?>
        </tbody>
    </table>

    <br />
<?php
}
?>

<script src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js" ></script>
<script src="plugins/jquery.alphanumeric.js" ></script>
<script src="plugins/jquery.mask.js" ></script>
<script src="plugins/jquery/datepick/jquery.datepick.js" ></script>
<script src="plugins/jquery/datepick/jquery.datepick-pt-BR.js" ></script>
<script src="plugins/shadowbox/shadowbox.js" ></script>
<script>

$("#os, #hd").numeric();

$(".form-pesquisa select,.form-pesquisa input").addClass('frm');
$(".form-pesquisa tbody td").attr({align:'left'});
$(".form-pesquisa tbody tr").prepend('<td style="width:15px">&nbsp;</td>');

Shadowbox.init();

var referencia = $("input[name=produto_referencia]");
var descricao  = $("input[name=produto_descricao]");

$("span.lupa").click(function() {
    let ref  = $(referencia).val();
    let desc = $(descricao).val();

    if (ref.length > 2 || desc.length > 2) {
        Shadowbox.open({
            content: "produto_pesquisa_2_nv.php?descricao="+desc+"&referencia="+ref,
            player: "iframe",
            title: "Pesquisa Produto",
            width: 800,
            height: 500
        });
    } else {
        alert("Informe toda ou uma parte da descrição para pesquisar");
    }
});

function retorna_dados_produto(produto, linha, nome_comercial, voltagem, ref, desc, referencia_fabrica, garantia, ativo, valor_troca, troca_garantia, troca_faturada, mobra, off_line, capacidade, ipi, troca_obrigatoria, posicao, origem)  {
    $(referencia).val(ref);
    $(descricao).val(desc);
}

$("input.data").datepick();
$("input.data").mask("99/99/9999");
$("input.cpf").numeric();

</script>

<?php

include "../admin/rodape.php";

