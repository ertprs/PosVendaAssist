<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

include_once '../class/AuditorLog.php';

if(isset($_POST["ajax_peca"])){
    $peca = $_POST["peca"];
    if (!empty($peca)) {
    $sql = "SELECT 
                    tbl_peca.parametros_adicionais,
                    tbl_peca.peso,
                    tbl_peca.informacoes
            FROM tbl_peca
            WHERE tbl_peca.peca = $peca";

    $res = pg_query($con, $sql);        
    
    $parametros_adicionais = pg_fetch_result($res, 0, "parametros_adicionais");
    $parametros_adicionais_array = json_decode($parametros_adicionais);

    $peso = pg_fetch_result($res, 0, "peso");
    $info = pg_fetch_result($res, 0, "informacoes");
    $altura = $parametros_adicionais_array->altura;
    $comprimento = $parametros_adicionais_array->comprimento;
    $largura = $parametros_adicionais_array->largura;

    $return = array("peso" => $peso, "info" => $info,"altura" => $altura,"comprimento" => $comprimento,"largura" => $largura);

    exit(json_encode($return));

    } else {
        exit;
    }       
}

?>
<html>
<head>
<title>Cadastro informações adicionais</title>

<script src="../admin/plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
<script src="../admin/plugins/posvenda_jquery_ui/js/jquery-ui-1.10.3.custom.js"></script>
<script src="../admin/plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.core.min.js"></script>
<script src="../admin/plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.widget.min.js"></script>
<script src="../admin/plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.effect.min.js"></script>
<script src="../admin/plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.tabs.min.js"></script>
<script src="../admin/bootstrap/js/bootstrap.js"></script>
<script type="text/javascript" src="../admin/js/jquery.maskmoney.js"></script>
<link type="text/css" rel="stylesheet" href="css/css.css">
<link type="text/css" rel="stylesheet" media="screen" href="../admin/bootstrap/css/bootstrap.css" />
<link type="text/css" rel="stylesheet" media="screen" href="../admin/bootstrap/css/extra.css" />
<link type="text/css" rel="stylesheet" media="screen" href="../admin/css/tc_css.css" />
<link type="text/css" rel="stylesheet" media="screen" href="../admin/css/tooltips.css" />
<link type="text/css" rel="stylesheet" media="screen" href="../admin/plugins/posvenda_jquery_ui/css/posvenda_ui/jquery-ui-1.10.3.custom.css">
<link type="text/css" rel="stylesheet" media="screen" href="../admin/bootstrap/css/ajuste.css" />
<script src='../admin/plugins/shadowbox_lupa/shadowbox.js'></script>
<link rel='stylesheet' type='text/css' href='../admin/plugins/shadowbox_lupa/shadowbox.css' /><script src='../admin/plugins/jquery.alphanumeric.js'></script><script src='../admin/plugins/price_format/jquery.price_format.1.7.min.js'></script><script src='../admin/plugins/price_format/config.js'></script><script src='../admin/plugins/price_format/accounting.js'></script>

<script src='../js/jquery.numeric.js'></script>

<script type="text/javascript">
    $(function(){
        $("#coluna").removeAttr("style","");
        Shadowbox.init();

        $(".numeric").numeric();

        $(document).on("click","#alterar_peca",function(){
            var posicao = $(this).attr("posicao");

            $("#referencia_"+posicao).prop("disabled", false);
            $("#referencia_"+posicao).val("");

            $("#descricao_"+posicao).prop("disabled", false);
            $("#descricao_"+posicao).val("");

            $("#peca_"+posicao).val("");
            $("#peso_"+posicao).val("");
            $("#comprimento_"+posicao).val("");
            $("#altura_"+posicao).val("");
            $("#largura_"+posicao).val("");
            $("#info_adicional_"+posicao).val("");
        });

        $(document).on("click",".lupa",function(){
            var posicao = $(this).attr("posicao");
            var tipo    = $(this).attr("tipo");

            if (tipo == "referencia") {
                var peca = $("#referencia_"+posicao).val();
            } else {
                var peca = $("#descricao_"+posicao).val();
            }

            pesquisaPeca(peca,posicao,tipo);

        });

        $("#btn_linha").click(function(){
            var linha = $("#linha_modelo").clone();
            var posicao = $("#qtd_linhas").val();
            var cor = $("#qtd_linhas").attr("cor");

            if (cor == '#FFEECC') {
                cor = '#FFFBF0';
            } else {
                cor = '#FFEECC'
            }

            $(linha).find("td").each(function() {
                $(this).attr("bgcolor",cor);
            });

            $(linha).attr("id","");
            $(linha).find("#referencia").attr("name","campos[referencia]["+posicao+"]");
            $(linha).find("#descricao").attr("name","campos[descricao]["+posicao+"]");
            $(linha).find("#peso").attr("name","campos[peso]["+posicao+"]");
            $(linha).find("#comprimento").attr("name","campos[comprimento]["+posicao+"]");
            $(linha).find("#altura").attr("name","campos[altura]["+posicao+"]");
            $(linha).find("#largura").attr("name","campos[largura]["+posicao+"]");
            $(linha).find("#info_adicional").attr("name","campos[info_adicional]["+posicao+"]");
            $(linha).find("#peca").attr("name","campos[peca]["+posicao+"]");

            $(linha).find("input[id^=referencia]").attr("id","referencia_"+posicao);
            $(linha).find("input[id^=descricao]").attr("id","descricao_"+posicao);
            $(linha).find("input[id^=peso]").attr("id","peso_"+posicao);
            $(linha).find("input[id^=comprimento]").attr("id","comprimento_"+posicao);
            $(linha).find("input[id^=altura]").attr("id","altura_"+posicao);
            $(linha).find("input[id^=largura]").attr("id","largura_"+posicao);
            $(linha).find("input[id^=peca]").attr("id","peca_"+posicao);
            $(linha).find("input[id=alterar_peca]").attr("posicao",posicao);
            $(linha).find("textarea[id^=info_adicional]").attr("id","info_adicional_"+posicao);

            $(linha).find(".lupa").attr("posicao", ""+posicao+"");

            $("#corpo_tabela").append(linha);

            $(linha).show();

            var int_posicao = parseInt(posicao);
            var qtde = int_posicao + 1;

            $("#qtd_linhas").val(qtde);
            $("#qtd_linhas").attr("cor", cor);

            $(".numeric").numeric();

        });
    }); 

    function pesquisaPeca(peca,posicao,tipo){
            Shadowbox.open({
                content:    "../peca_lupa_new.php?distrib=t&valor="+peca+"&parametro="+tipo+"&posicao="+posicao,
                player: "iframe",
                title:   "Peça",
                width:  800,
                height: 500
            });

    }

    function ajax_peca_info(peca,posicao){

            $.ajax({
                    type: 'POST',
                    url: 'cadastro_info_adicional_peca.php',
                    data: { ajax_peca:true, peca:peca },
            }).done(function(data) {
                data = JSON.parse(data);

                $("#peso_"+posicao).val(data.peso);
                $("#comprimento_"+posicao).val(data.comprimento);
                $("#altura_"+posicao).val(data.altura);
                $("#largura_"+posicao).val(data.largura);
                $("#info_adicional_"+posicao).val(data.info);
         
            });
    }

    function retorna_peca(retorno){
        $("#referencia_"+retorno.posicao).val(retorno.referencia);
        $("#descricao_"+retorno.posicao).val(retorno.descricao);
        $("#peca_"+retorno.posicao).val(retorno.peca);

        $("#referencia_"+retorno.posicao).prop("disabled",true);
        $("#descricao_"+retorno.posicao).prop("disabled",true);

        ajax_peca_info(retorno.peca,retorno.posicao);
    }
</script>
</head>

<body>
<? include 'menu.php'; 


if (!empty($_POST["btn_submit"])) {
    $campos = $_POST["campos"];

    for ($x=0;$x < count($campos["peca"]);$x++) {

        $peca = $campos["peca"][$x];

        if (!empty($peca)) {
            $comprimento = $campos["comprimento"][$x];
            $altura = $campos["altura"][$x];
            $largura = $campos["largura"][$x];
            $info = $campos["info_adicional"][$x];
            $peso = $campos["peso"][$x];

            $peso = str_replace(",",".",$peso);
            $altura = str_replace(",",".",$altura);
            $largura = str_replace(",",".",$largura);
            $comprimento = str_replace(",",".",$comprimento);

            $sql = "SELECT parametros_adicionais,fabrica 
                    FROM tbl_peca 
                    WHERE peca = $peca";
            $res = pg_query($con,$sql);

            $parametros_adicionais = pg_fetch_result($res,0,"parametros_adicionais");
            $fabrica_peca = pg_fetch_result($res,0,"fabrica");
            $parametros_adicionais_array = json_decode($parametros_adicionais);

            $parametros_adicionais_array->comprimento = $comprimento;
            $parametros_adicionais_array->altura      = $altura;
            $parametros_adicionais_array->largura     = $largura;

            $parametros_adicionais_json = json_encode($parametros_adicionais_array);

            unset($auditor);
            $auditor = new AuditorLog();
            $auditor->retornaDadosSelect("SELECT peca,informacoes,parametros_adicionais,peso 
                    FROM tbl_peca 
                    WHERE peca = $peca");
        
            $sql = "UPDATE tbl_peca 
                    SET parametros_adicionais = '$parametros_adicionais_json',informacoes = '$info', peso = $peso
                    WHERE peca = $peca";    
            pg_query($con,$sql);

            $msg_erro =  pg_last_error($con);
        
            if (empty($msg_erro)) {

                $auditor->retornaDadosSelect();
                $auditor->enviarLog('update', 'tbl_peca_adicionais', "$fabrica_peca*$peca");

                /* Dimensão Peça */

                $sql = "SELECT peca_dimensao FROM tbl_peca_dimensao WHERE peca = {$peca}";
                $res = pg_query($con, $sql);

                if(pg_num_rows($res) == 0){

                    $sql = "INSERT INTO tbl_peca_dimensao (peca, largura, altura, profundidade, peso) VALUES ({$peca}, {$largura}, {$altura}, {$comprimento}, {$peso})";

                }else{

                    $peca_dimensao = pg_fetch_result($res, 0, "peca_dimensao");

                    $sql = "UPDATE tbl_peca_dimensao SET 
                                largura      = {$largura}, 
                                altura       = {$altura}, 
                                profundidade = {$comprimento}, 
                                peso         = {$peso} 
                            WHERE peca_dimensao = {$peca_dimensao} 
                            AND peca = {$peca}";

                }

                $res = pg_query($con, $sql);

                /* Fim Dimensão Peça */

                $msg_success = "Cadastro efetuado com sucesso!";
                unset($_POST);
            } else {
                $msg_erro    = "Erro ao cadastrar a peça ".$campos["referencia"][$x].", verifique se as informações foram digitadas corretamente";
            }

        }
    }

}



?>
<style>
#tabela_peca {
    width: 80%;
}

.titulo_tab {
    height: 50px;
}

label {
  display: inline-block;
  width: 80px;
  text-align: left;
}

textarea {
    width: 75%;
    position: relative;
    left: 12.5%;
    margin-top: 5%;
}

.info_peca {
    position: relative;
    left: 10%;
    margin-top: 5%;
}

</style>
<center><h1>Informações Adicionais da Peça</h1></center>
<? if (!empty($msg_erro)) { ?>
    <div class="alert alert-danger"><h4><?= $msg_erro ?></h4></div>
<? } ?>
<? if (!empty($msg_success)) { ?>
    <div class="alert alert-success"><h4><?= $msg_success ?></h4></div>
<? } ?>
<form autocomplete="off" name="form_pecas" method="POST" action="cadastro_info_adicional_peca.php">
<table id="tabela_peca" class="table table-bordered" align="center" style="table-layout: fixed">
    <thead align="center" style="background: #F93;color:white; font-weight: bold">
        <tr class='titulo_tab'>
            <th style="font-size: 13pt;">Peça</th>
            <th style="font-size: 13pt;">Características</th>
            <th style="font-size: 13pt;">Informações Adicionais</th>
        </tr>
    </thead>
    <tbody id="corpo_tabela">
        <tr style="display: none;" id="linha_modelo">
                <td id="coluna" align="center" bgcolor='#FFEECC'>
                    <div class="info_peca">
                        <br />
                        <p>
                            <label>Referência: </label>
                            <input class="span2" type="text" id="referencia" name="">
                            <img class="lupa" tipo="referencia" posicao="" src="../admin/imagens/lupa.png" />
                        </p>
                        <p>
                            <label>Descrição: </label>
                            <input type="text" id="descricao" name="" maxlength="">
                            <img class="lupa" tipo="descricao" posicao="" src="../admin/imagens/lupa.png" />
                        </p>
                        <p class="tac">
                            <input type="button" value="Limpar Campos" class="btn btn-primary" id="alterar_peca" posicao="">
                        </p>
                        <input type="hidden" id="peca" name="" maxlength="">
                    </div>  
                </td>
                <td bgcolor='#FFEECC'>
                    <div class="info_peca">
                        <p>
                            <label>Peso (gramas): </label>
                            <input class="span2 numeric" type="text" id="peso" name="" maxlength="5" />
                        </p>
                        <p>
                            <label>Comprimento (cm): </label>
                            <input class="span2 numeric" type="text" id="comprimento" name="" maxlength="5" /><br />
                        </p>
                        <p>
                            <label>Altura (cm): </label>
                            <input class="span2 numeric" type="text" id="altura" name="" maxlength="5"/><br />
                        </p>
                        <p>
                            <label>Largura (cm): </label>
                            <input class="span2 numeric" type="text" id="largura" name="" maxlength="5" />
                        </p>
                    </div>
                </td>
                <td align="center" bgcolor='#FFEECC'>
                    <textarea rows="5" id="info_adicional" name=""></textarea>
                </td>
            </tr>
            
        <? 
        $qtd = (isset($_POST["qtd_linhas"])) ? $_POST["qtd_linhas"] : 3;
        for ($x=0;$x < $qtd;$x++) { 
                $cor = "#FFFBF0";
                if ($x % 2 == 0) $cor = "#FFEECC";
            ?>
            <tr>
                <td id="coluna" align="center" bgcolor='<?= $cor ?>'>
                    <div class="info_peca">
                        <br />
                        <p>
                            <label>Referência: </label>
                            <input class="span2" type="text" id="referencia_<?= $x ?>" name="campos[referencia][<?= $x ?>]" value="<?= $_POST["campos"]["referencia"][$x] ?>">
                            <img class="lupa" tipo="referencia" style="cursor:pointer;" posicao="<?= $x ?>" src="../admin/imagens/lupa.png" />
                        </p>
                        <p>
                            <label>Descrição: </label>
                            <input type="text" id="descricao_<?= $x ?>" name="campos[descricao][<?= $x ?>]" value="<?= $_POST["campos"]["descricao"][$x] ?>" maxlength="">
                            <img class="lupa" style="cursor:pointer;" tipo="descricao" posicao="<?= $x ?>" src="../admin/imagens/lupa.png" />
                        </p>
                        <p class="tac">
                            <input type="button" value="Limpar Campos" class="btn btn-primary" id="alterar_peca" posicao="<?= $x ?>">
                        </p>    
                        <input type="hidden" id="peca_<?= $x ?>" name="campos[peca][<?= $x ?>]" value="<?= $_POST["campos"]["peca"][$x] ?>" maxlength="">
                    </div>  
                </td>
                <td bgcolor='<?= $cor ?>'>
                    <div class="info_peca">
                        <p>
                            <label>Peso (gramas): </label>
                            <input value="<?= $_POST["campos"]["peso"][$x] ?>" class="span2 numeric" type="text" id="peso_<?= $x ?>" name="campos[peso][<?= $x ?>]" maxlength="5" />
                        </p>
                        <p>
                            <label>Comprimento (cm): </label>
                            <input value="<?= $_POST["campos"]["comprimento"][$x] ?>" class="span2 numeric" type="text" id="comprimento_<?= $x ?>" name="campos[comprimento][<?= $x ?>]" maxlength="5" /><br />
                        </p>
                        <p>
                            <label>Altura (cm): </label>
                            <input value="<?= $_POST["campos"]["altura"][$x] ?>" class="span2 numeric" type="text" id="altura_<?= $x ?>" name="campos[altura][<?= $x ?>]" maxlength="5" /><br />
                        </p>
                        <p>
                            <label>Largura (cm): </label>
                            <input value="<?= $_POST["campos"]["largura"][$x] ?>" class="span2 numeric" type="text" id="largura_<?= $x ?>" name="campos[largura][<?= $x ?>]" maxlength="5" />
                        </p>
                    </div>
                </td>
                <td align="center" bgcolor='<?= $cor ?>'>
                    <textarea rows="5" id="info_adicional_<?= $x ?>" name="campos[info_adicional][<?= $x ?>]"><?= $_POST["campos"]["info_adicional"][$x] ?></textarea>
                </td>
            </tr>
        <? } ?>
    </tbody>
    <tbody>
        <tr>
            <th colspan="3" bgcolor="#F93">
                <br />
                <input value="Finalizar Cadastro" name="btn_submit" type="submit" class="btn btn-large">
                <br /><br />
            </th>
        </tr>   
    </tbody>
</table>
<input type="hidden" cor="<?= $cor ?>" value="<?= $x ?>" id="qtd_linhas" name="qtd_linhas">
</form>
<button class="btn btn-info" id="btn_linha">+ Adicionar Nova Linha</button>
<? include "rodape.php"; ?>
</body>
</html>
