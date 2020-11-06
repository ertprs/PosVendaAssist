<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';
include 'funcoes.php';


$valor = $_GET["valor"];
$opcao = $_GET["opcao"];


if($opcao == "ref"){
	$where = "AND     tbl_peca.referencia like '$valor%'";
}else if($opcao == "peca"){
    if($login_pais == "BR"){
        $where = "AND     tbl_peca.descricao ilike '%$valor%'";
    } else {
        $where = "AND     tbl_peca_idioma.descricao ilike '%$valor%'";
    }
}

$sqlV = "
        SELECT  tbl_vitrine.vitrine ,
                tbl_vitrine.peca    ,
                tbl_vitrine.valor,
                tbl_peca.referencia ,
                CASE WHEN '$login_pais' = 'BR'
                     THEN tbl_peca.descricao
                     ELSE tbl_peca_idioma.descricao
                END AS descricao    ,
                CASE WHEN x.qtde_venda IS NOT NULL
                     THEN tbl_vitrine.qtde - x.qtde_venda
                     ELSE tbl_vitrine.qtde
                END  AS qtde,
                tbl_vitrine.ativo   ,
                tbl_posto.nome      ,
                tbl_posto.posto     ,
                tbl_posto.email,
                tbl_posto_fabrica.contato_cidade,
                tbl_posto_fabrica.contato_estado
        FROM    tbl_vitrine
        
        JOIN    tbl_posto           USING(posto)
        JOIN    tbl_peca            USING(peca)
   LEFT JOIN    tbl_peca_idioma     ON  tbl_peca_idioma.peca        = tbl_peca.peca
                                    AND tbl_peca_idioma.idioma      = 'ES'
        JOIN    tbl_posto_fabrica   ON  tbl_posto_fabrica.posto     = tbl_vitrine.posto
                                    AND tbl_posto_fabrica.fabrica   = $login_fabrica
   LEFT JOIN    (
                    SELECT  tbl_vitrine_pedido.posto_venda                  ,
                            tbl_vitrine_pedido_item.peca                    ,
                            SUM(tbl_vitrine_pedido_item.qtde) AS qtde_venda
                    FROM    tbl_vitrine_pedido_item
                    JOIN    tbl_vitrine_pedido USING (vitrine_pedido)
                    WHERE   tbl_vitrine_pedido.status_pedido <> 3
              GROUP BY      tbl_vitrine_pedido.posto_venda      ,
                            tbl_vitrine_pedido_item.peca
                ) x ON  x.posto_venda          = tbl_posto.posto
                    AND x.peca                 = tbl_peca.peca
                    AND tbl_posto.posto        <> $login_posto
        WHERE   tbl_vitrine.posto   <> $login_posto
        AND     tbl_peca.fabrica    = $login_fabrica
        AND     tbl_vitrine.ativo   IS TRUE
        AND     tbl_posto.pais = '$login_pais'
           $where 
  ORDER BY      tbl_posto.nome      ,
                tbl_peca.descricao
";

$resV = pg_query($con,$sqlV);

?>

<table id="result" border="0" cellpadding="0" cellspacing="0" style=" text-align:center; background-color:#FFF;width:750px;">
    <thead>
        <tr>
            <th>Ref.</th>
            <th><?=traduz("descricao",$con)?></th>
            <th><?=traduz("posto",$con)?></th>
            <th><?=traduz("cidade",$con)."/".traduz("estado",$con)?></th>
            <th><?=traduz("qtde",$con)?></th>
            <th width="110">Valor</th>
            <th>&nbsp;</th>
        </tr>
    </thead>
    <tbody>
<?
    if(pg_numrows($resV) > 0){
        for($i=0;$i<pg_numrows($resV);$i++){
            
            $peca_qtde_vitrine          = pg_fetch_result($resV,$i,qtde);

            if($peca_qtde_vitrine > 0 ){//Solicitado pelo Paulo
                $peca_vitrine               = pg_fetch_result($resV,$i,vitrine);
                $peca                       = pg_fetch_result($resV,$i,peca);
                $peca_referencia_vitrine    = pg_fetch_result($resV,$i,referencia);
                $peca_descricao_vitrine     = pg_fetch_result($resV,$i,descricao);                
                $peca_ativo_vitrine         = pg_fetch_result($resV,$i,ativo);
                $posto_email                = pg_fetch_result($resV,$i,email);
                $posto_email                = pg_fetch_result($resV,$i,email);
                $posto_nome                 = pg_fetch_result($resV,$i,nome);
                $cidade                     = pg_fetch_result($resV,$i,contato_cidade);
                $estado                     = pg_fetch_result($resV,$i,contato_estado);
                $valor                      = number_format(pg_fetch_result($resV,$i,valor), 2, '.', '');

                $cidade_estado              = $cidade."/".$estado;

                $cor = ($i % 2 == 0) ? "background-color: #FFF" : "background-color: #FFC";
    ?>
            <tr class="vitrine_peca_<?=$peca?>" id="vitrine_<?=$peca_vitrine?>" style="<?=$cor?>">
                <td><?=$peca_referencia_vitrine ." - ". $peca_vitrine ?></td>
                <td><?=$peca_descricao_vitrine?></td>
                <td><?=$posto_nome?></td>
                <td><?=$cidade_estado?></td>
                <td id="qtde_<?=$peca_qtde_vitrine?>"><?=$peca_qtde_vitrine?></td>            
                <td><?=(strlen($valor) > 0) ? "$valor" : "---"?></td>
                <td style="margin-left:10px;">
                    <a class="lnk" onclick="javascript:confirmarCompra(<?=$peca_vitrine?>)" >comprar</a>
                </td>
            </tr>
            <tr id="<?=$peca_vitrine?>" style="display:none">
            </tr>
<?          }
        }
    }else{
?>
        <tr>
            <td colspan="4"><h6><?=traduz("nenhuma.peca.colocada.na.vitrine",$con)?></h6></td>
        </tr>
<?
    }
?>
    </tbody>
</table>

