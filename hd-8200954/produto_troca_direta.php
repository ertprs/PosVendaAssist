<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

$layout_menu = 'tecnica';
$title = "Produtos com Troca Direta";

if($_POST['ajax']){
   /**
    * - AJAX para montagem da lista de peças
    * 1ª Condição: Se o produto estiver configurado para não mostrar lista básica
    * Solução    : Mostrar uma frase que o produto é troca direta e não mostrar as peças.
    *
    * 2ª Condição: Se o produto estiver configurado para mostrar lista básica
    * Solução    : Mostrart uma frase que o produto é troca direta e mostrar as peças
    *
    * @param Integer produto
    * @return Json
    */

    $produto = $_POST['produto'];

    $sqlVerifica = "
        SELECT  inibir_lista_basica
        FROM    tbl_produto
        WHERE   fabrica_i   = $login_fabrica
        AND     produto     = $produto
    ";
    $resVerifica = pg_query($con,$sqlVerifica);
    $inibir_lista_basica = pg_fetch_result($resVerifica,0,inibir_lista_basica);

    if($inibir_lista_basica == 't'){
        $retorno['inibir']  = "true";
        $retorno['frase']   = utf8_encode("O produto está cadastrado como troca direta, gentileza seguir com a OS de troca.");
    }else{

        $sqlLista = "
            SELECT  DISTINCT
                    tbl_peca.peca                                           ,
                    CASE WHEN peca_para.referencia IS NULL
                         THEN tbl_peca.referencia
                         ELSE peca_para.referencia
                    END                                 AS peca_referencia  ,
                    CASE WHEN peca_para.descricao IS NULL
                         THEN tbl_peca.descricao
                         ELSE peca_para.descricao
                    END                                 AS peca_descricao
            FROM    tbl_lista_basica
            JOIN    tbl_peca USING (peca)
       LEFT JOIN    tbl_peca_alternativa ON tbl_peca.referencia = tbl_peca_alternativa.de   AND tbl_peca_alternativa.fabrica = $login_fabrica
       LEFT JOIN    tbl_depara           ON tbl_peca.referencia = tbl_depara.de             AND tbl_depara.fabrica           = $login_fabrica
       LEFT JOIN    tbl_peca peca_para   ON tbl_depara.para = peca_para.referencia          AND peca_para.fabrica            = tbl_depara.fabrica
            WHERE   tbl_lista_basica.fabrica = $login_fabrica
            AND     tbl_lista_basica.produto = $produto
            AND     tbl_peca.produto_acabado IS NOT TRUE
      ORDER BY      peca_descricao
        ";
//         echo $sqlLista;
        $resLista = pg_query($con,$sqlLista);

        if(pg_numrows($resLista) == 0){
            $retorno['inibir']  = "true";
            $retorno['frase']   = utf8_encode("O produto está cadastrado como troca direta, mas não há peças na lista básica.");
        }else{
            $retorno['inibir']  = "false";
            $retorno['frase']   = utf8_encode("O produto está cadastrado como troca direta, porém dispomos de alguma(s) peça(s).");

            $resultado = pg_fetch_all($resLista);

            $retorno['resultado'] = $resultado;
        }
    }

    echo json_encode($retorno);
    exit;
}

include "cabecalho.php";
?>
<style>
    .titulo {
        font-family: Arial;
        font-size: 9pt;
        text-align: center;
        font-weight: bold;
        color: #FFFFFF;
        background: #408BF2;
    }
    .titulo2 {
        font-family: Arial;
        font-size: 12pt;
        text-align: center;
        font-weight: bold;
        color: #FFFFFF;
        background: #408BF2;
    }

    .conteudo {
        font-family: Arial;
        FONT-SIZE: 8pt;
        text-align: left;
    }
    .Tabela{
        border:1px solid #485989;

    }
    a{
        cursor:pointer;
    }

    img{
        border: 0px;
    }
</style>
<script type="text/javascript" src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>

<!-- FUNCIONAMENTO JQUERY -->
<script type="text/javascript">
$( "a[]" ).click(function( event ) {
    event.preventDefault();
});
function carregaPecas(produto){
    var carregaEsconde = $("#produto_"+produto+" td:contains([+])").length;
    var conteudo = $("#produto_"+produto+" td").text();

    if(carregaEsconde == 1){
        var divide = conteudo.split("[+]");
        $.ajax({
            type:"POST",
            dataType:"json",
            data:{
                ajax:true,
                produto:produto,
            },
            beforeSend:function(){
                $("#produto_"+produto+" td").html("<a  onclick='javascript:carregaPecas("+produto+");'>&nbsp;&nbsp;&nbsp;&nbsp;[-]"+divide[1]+"</a>");
            }
        })
        .done(function(data){
            if(data["inibir"] == "false"){
                $("#esconde_"+produto).html("<td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"+data['frase']+"</td>");
                var tabela = "<ul>";
                $.each(data["resultado"],function(index,value){
                    tabela += "<li>"+value['peca_referencia']+" - "+value['peca_descricao']+"</li>";
                });
                tabela += "</ul>";
                $("#esconde_"+produto+" td").append(tabela);
                $("#esconde_"+produto).css({
                    "display":"table-row",
                    "font-family": "Arial",
                    "font-size": "8pt"
                });
            }else{
                $("#esconde_"+produto).html("<td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"+data['frase']+"</td>")
                .css({
                    "display":"table-row",
                    "font-family": "Arial",
                    "font-size": "8pt"
                });
            }
        })
        .fail(function(){
            alert("Não foi possível localizar as peças");
        });
    }else{
        var divide = conteudo.split("[-]");
        $("#produto_"+produto+" td").html("<a  onclick='javascript:carregaPecas("+produto+");'>&nbsp;&nbsp;&nbsp;&nbsp;[+]"+divide[1]+"</a>");
        $("#esconde_"+produto).css("display","none");
    }
}

</script>
<!-- FIM - FUNCIONAMENTO JQUERY -->

<table width='700' align='center' class='Tabela' cellspacing='0' cellpadding='3' border='1' style='border-collapse: collapse' bordercolor='#d2e4fc'>
    <thead>
        <tr class='titulo2'>
            <th>Linhas</th>
        </tr>
    </thead>
    <tbody>
<?

$sqlLinha = "
    SELECT  DISTINCT
            tbl_linha.linha,
            tbl_linha.nome
    FROM    tbl_linha
    JOIN    tbl_produto ON  tbl_produto.linha = tbl_linha.linha
                        AND tbl_produto.troca_faturada IS TRUE
                        AND tbl_produto.troca_garantia IS TRUE
    WHERE   tbl_linha.fabrica = $login_fabrica
";
$resLinha = pg_query($con,$sqlLinha);
$contaLinhas = pg_numrows($resLinha);
for($i=0;$i<$contaLinhas;$i++){
    $linha      = pg_fetch_result($resLinha,$i,linha);
    $linha_nome = pg_fetch_result($resLinha,$i,nome);
?>
        <tr class="conteudo" id="linha_<?=$linha?>">
            <td><?=$linha_nome?></td>
        </tr>
        <tr>
            <td>
                <table border="0" cellspacing="0" cellpadding="3" >
<?
    $sqlProdutos = "
        SELECT  tbl_produto.produto,
                tbl_produto.referencia,
                tbl_produto.descricao
        FROM    tbl_produto
        WHERE   tbl_produto.linha = $linha
        AND     tbl_produto.troca_faturada IS TRUE
        AND     tbl_produto.troca_garantia IS TRUE
  ORDER BY      tbl_produto.referencia
    ";
    $resProduto = pg_query($con,$sqlProdutos);
    $contaProdutos = pg_numrows($resProduto);
    for($j=0;$j<$contaProdutos;$j++){
        $produto            = pg_fetch_result($resProduto,$j,produto);
        $produto_referencia = pg_fetch_result($resProduto,$j,referencia);
        $produto_descricao  = pg_fetch_result($resProduto,$j,descricao);
?>
                    <tr class="conteudo" id="produto_<?=$produto?>">
                        <td><a  onclick="javascript:carregaPecas('<?=$produto?>');">&nbsp;&nbsp;&nbsp;&nbsp;[+]<?=$produto_referencia. " - ".$produto_descricao?></a></td>
                    </tr>
                    <tr id="esconde_<?=$produto?>" style="display:none">
                        <td></td>
                    </tr>
<?
    }
    $contaProdutos = 0;
?>
                </table>
            </td>
        </tr>
<?
}
?>
    </tbody>
    <tfoot>
        <tr>
        </tr>
    </tfoot>
</table>

<? include "rodape.php"; ?>