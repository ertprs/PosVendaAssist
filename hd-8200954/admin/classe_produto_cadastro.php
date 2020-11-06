<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="cadastros";
include 'autentica_admin.php';
include 'funcoes.php';
include_once '../class/fn_sql_cmd.php';

function listaClasseMO($filtro=[]) {
    $filtros = array_merge(
        ['fabrica' => $GLOBALS['login_fabrica']],
        $filtro
    );
    $sql = sql_cmd('tbl_classe', '*', $filtros) . ' ORDER BY nome';

    $retorno         = '';
    $res             = pg_query($GLOBALS['con'], $sql);
    $total_registros = pg_num_rows($res);

    if ($total_registros > 0) {
        $retorno = "
            <table width='700' align='center' class='tabela'>
                <thead>
                    <caption class='titulo_tabela'>Classes de Produtos</caption>
                    <tr class='titulo_coluna'>
                        <th>Descrição</th>
                        <th>Mão de Obra</th>
                        <th>Mão de Obra Top</th>
                        <th>Mão de Obra Garantia</th>
                        <th>Entrega Técnica</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>";
        for ($i = 0; $i < $total_registros; $i++) {
            $nome            = pg_fetch_result($res, $i, 'nome');
            $mao_obra        = pg_fetch_result($res, $i, 'mao_de_obra');
            $mao_obra_top    = pg_fetch_result($res, $i, 'mao_de_obra_top');
            $mao_obra_gar    = pg_fetch_result($res, $i, 'mao_de_obra_garantia');
            $classe          = pg_fetch_result($res, $i, 'classe');
            $entrega         = pg_fetch_result($res, $i, 'entrega_tecnica') == 't' ? 't' : 'f';
            $entrega_tecnica = pg_fetch_result($res, $i, 'entrega_tecnica') == 't'
                ? 'imagens/status_verde.png'
                : 'imagens/status_vermelho.png';

            $cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";
            $retorno .= "
                    <tr bgcolor='$cor' id='$classe'>
                        <td>$nome</td>
                        <td>".number_format($mao_obra,     2, ',', '.')."</td>
                        <td>".number_format($mao_obra_top, 2, ',', '.')."</td>
                        <td>".number_format($mao_obra_gar, 2, ',', '.')."</td>
                        <td><img src='$entrega_tecnica' /></td>
                        <td>
                            <button type='button' onclick=\"alteraLinha(" .
                            "'$classe','$nome','$mao_obra','$mao_obra_top','$mao_obra_gar','$entrega','atualizar'" .
                            ");\">Alterar</button>
                            <button type='button' onclick='deleteLinha(\"$classe\",\"deletar\");'>Excluir</button>
                        </td>
                    </tr>";
        }

        $retorno .="
                </tbody>
                <tfoot>
                    <tr class='titulo_coluna'>
                        <td align='center' colspan='100%'>
                            Total de registros : $total_registros
                        </td>
                    </tr>
                </tfoot>
            </table>";
    }
    return $retorno;
}

if($_GET['acao']){
    $acao            = $_GET['acao'];
    $classe          = $_GET['classe'];
    $nome            = $_GET['nome'];
    $mao_obra        = $_GET['mao_obra'];
    $mao_obra_gar    = $_GET['mao_obra_gar'];
    $mao_obra_top    = $_GET['mao_obra_top'];
    $entrega_tecnica = $_GET['entrega_tecnica'] == 't' ? 'TRUE' : 'FALSE';

    switch($acao){
        case 'deletar':
            $sql = "DELETE FROM tbl_classe_produto WHERE classe = $classe";
            $res = pg_query($con,$sql);

            if (strlen(pg_last_error($con)) == 0) {
                $sql = "DELETE FROM tbl_classe WHERE classe = $classe";
                $res = pg_query($con,$sql);

                if (strlen(pg_last_error($con)) == 0) {
                    $msg = "Registro excluído com sucesso";
                    $ok = "1";
                } else {
                    $msg = "Erro ao excluir registro";
                    $ok = "0";
                }
            }else {
                $msg = "Erro ao excluir registro";
                $ok = "0";
            }
            echo "$acao|$msg|$ok";
        break;

        case 'cadastrar':
            $sql = "INSERT INTO tbl_classe(
                fabrica,nome,mao_de_obra,mao_de_obra_top,mao_de_obra_garantia,entrega_tecnica
            ) VALUES (
                $login_fabrica,'$nome',$mao_obra, $mao_obra_top, $mao_obra_gar, '$entrega_tecnica'
           )";

            $res = pg_query($con,$sql);
            if(strlen(pg_last_error($con)) == 0){
                $msg = "Registro inserido com sucesso";
                $ok = "1";
            }else{
                $msg = "Erro ao inserir registro";
                $ok = "0";
            }
        break;

        case 'atualizar':
            $sql = "
              UPDATE tbl_classe
                 SET fabrica              = $login_fabrica,
                     nome                 = '$nome',
                     mao_de_obra          = $mao_obra,
                     mao_de_obra_top      = $mao_obra_top,
                     mao_de_obra_garantia = $mao_obra_gar,
                     entrega_tecnica      = $entrega_tecnica
               WHERE classe = $classe
                 AND fabrica = $login_fabrica";
            $res = pg_query($con,$sql);

            if(strlen(pg_last_error($con)) == 0){
                $msg = "Registro atualizado com sucesso";
                $ok = "1";
            }else {
                $msg = "Erro ao atualizar registro! " . pg_last_error($con);
                $ok = "0";
            }
        break;
    }

    if ($acao == "cadastrar" or $acao == "atualizar") {
        $retorno = listaClasseMO();
        echo "$acao|$retorno|$msg|$ok";
    }
    exit;
}

$layout_menu = "cadastro";
$title       = "Cadastro de Classes de Produtos";
include 'cabecalho.php';
?>
    <style type="text/css">
    table.tabela tr td{
        font-family: verdana;
        font-size: 11px;
        border-collapse: collapse;
        border:1px solid #596d9b;
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

    .formulario{
        background-color:#D9E2EF;
        font:11px Arial;
        text-align:left;
    }

    .esquerda{
        padding-left:50px;
    }

#msg{
        margin:auto;
        width:700px;
        font: bold 14px "Arial";
        color:#FFFFFF;
        text-align:center;
        display:none;

    }
    </style>

    <script type="text/javascript" src="js/jquery-1.8.3.min.js"></script>
    <script type="text/javascript" src="js/jquery.maskmoney.js"></script>
    <script type="text/javascript">
        $(function(){
            $("input[name^=mao_obra]").maskMoney({
                symbol:    "",
                decimal:   ".",
                thousands: '',
                precision: 2,
                maxlength: 10
            });

            $("#saveBtn").click(function() {
                if (document.frm_cadastro.acao.value=='') {
                    if (document.frm_cadastro.classe.value=='') {
                        document.frm_cadastro.acao.value = 'cadastrar';
                    }else{
                        document.frm_cadastro.acao.value = 'atualizar';
                    }
                }
                crudClasse(document.frm_cadastro.classe.value);
            });
        });

        function crudClasse(classe){
            var url  = document.location.pathname;
            var acao = $('input[name=acao]').val();
            url += '?' + $("form").serialize().replace('descricao', 'nome');

            $.ajax({
                url: url,
                cache: false,
                beforeSend: function() {
                    if(acao != 'deletar'){
                        $('#resultados').html("<img src='imagens_admin/carregando_callcenter.gif' border='0'><font size='1' face='verdana'>Aguarde..</font>");
                    }
                },
                success: function(data) {
                    retorno = data.split('|');

                    if (retorno[0]=="deletar") {
                        if(retorno[2] == 1){
                            $('#'+classe).remove();
                            $('#msg').html(retorno[1]);
                            $('#msg').attr('style','display:block;background-color:#008000;');
                            $('#msg').fadeIn('slow');
                            $('#msg').delay(3000).fadeOut('slow');
                        }else{
                            $('#msg').attr('style','background-color:#FF0000;');
                            $('#msg').html(retorno[1]);
                            $('#msg').fadeIn('slow');
                            $('#msg').delay(3000).fadeOut('slow');
                        }
                    }

                    if (retorno[0]=="cadastrar" || retorno[0]=="atualizar") {
                        if(retorno[3] == 1){
                            $('#resultados').html(retorno[1]);
                            $('#msg').attr('style','display:block;background-color:#008000;');
                            $('#msg').fadeIn('slow');
                            $('form input[text]').val('');
                            $('form input[type=checkbox]').removeAttr('checked');
                            $('#msg').html(retorno[2]);
                            $('#msg').delay(3000).fadeOut('slow');
                        } else {
                            $('#resultados').html(retorno[1]);
                            $('#msg').html(retorno[2]);
                            $('#msg').attr('style','background-color:#FF0000;');
                            $('#msg').fadeIn('slow');
                            $('#msg').delay(3000).fadeOut('slow');
                        }
                    }

                }

            });
        }

        function alteraLinha(classe,nome,mao_obra,mao_obra_top,mao_obra_gar,entrega_tecnica,acao){
            $('input[name=classe]').val(classe);
            $('input[name=descricao]').val(nome);
            $('input[name=mao_obra]').val(mao_obra);
            $('input[name=mao_obra_top]').val(mao_obra_top);
            $('input[name=mao_obra_gar]').val(mao_obra_gar);
            $('input[name=acao]').val(acao);

            if (entrega_tecnica == 't') {
                $('input[name=entrega_tecnica]').attr('checked', true);
            }
            $('form')[0].scrollIntoView();
        }

        function deleteLinha(classe,acao){
            if(confirm('Deseja excluir o registro?')){
                $('input[name=acao]').val(acao);
                crudClasse(classe);
            }
        }

        function ocultaMsg(){
            $('#msg').fadeOut();
        }

    </script>

    <div id="msg"></div>

    <form name="frm_cadastro" method="POST">
        <table align="center" width="700" class="formulario" border='0'>
            <caption class="titulo_tabela">Cadastro</caption>
            <tr><td>&nbsp;</td></tr>
            <tr>
                <td colspan="4" class="esquerda" width="380">Descrição <br> <input type="text" name="descricao" size="50" class="frm"></td>
            </tr>
            <tr>
                <td class="esquerda">Valor M.O. <br> <input type="text" name="mao_obra" size="10" class="frm"></td>
                <td>Valor M.O. Top<br> <input type="text" name="mao_obra_top" size="10" class="frm"></td>
                <td>Valor M.O. Garantia<br> <input type="text" name="mao_obra_gar" size="10" class="frm"></td>
                <td>Entrega Técnica<br> <input type="checkbox" name="entrega_tecnica" value="t" class="frm"></td>
            </tr>
            <tr><td>&nbsp;</td></tr>
            <tr>
                <td colspan="3" align="center">
                    <input type="hidden" name="acao">
                    <input type="hidden" name="classe">
                    <input type="button" value="Gravar" id="saveBtn">
                </td>
            </tr>
            <tr><td>&nbsp;</td></tr>
        </table>
    </form>

    <br>
    <div id="resultados">
    <?=listaClasseMO()?>
    </div>
<?php include "rodape.php";

