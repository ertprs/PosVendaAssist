<?php 
/**
 * Tela de detalhes de pesquisa de satisfacao suggar.
 * Relatorio com os clientes que responderam determinada questao.
 * HD 102091
 *
 * @author Augusto Pascutti <augusto.hp@gmail.com>
 */
include 'dbconfig.php';
include "includes/dbconnect-inc.php";
include "autentica_admin.php";
include 'funcoes.php';
/**
 * Array de clientes a serem exibidos
 */
$aClientes = array();

/**
 * Tratando dados recebidos via GET
 */
$detalhe         = ( isset($_GET['detalhe']) && !empty($_GET['detalhe']) ) ? $_GET['detalhe'] : null ;
$resposta        = (boolean) ( isset($_GET['resposta']) && !empty($_GET['resposta']) && $_GET['resposta'] != 'nao' ) ? $_GET['resposta'] : null ;
$data_ini        = ( isset($_GET['data_ini']) && !empty($_GET['data_ini']) ) ? $_GET['data_ini'] : null ;
$data_fim        = ( isset($_GET['data_fim']) && !empty($_GET['data_fim']) ) ? $_GET['data_fim'] : null ;
$filtrar        = ( isset($_GET['filtrar']) && !empty($_GET['filtrar']) ) ? $_GET['filtrar'] : null ;
$cond_os_produto = ( isset($_GET['prod']) && !empty($_GET['prod']) ) ? "o.produto = ".$_GET['prod'] : '1=1' ;

/**
 * Buscando informacoes para detalhamento da questao selecionada
 */
switch ($detalhe) {
    case 'preco':
    case 'qualidade':
    case 'design':
    case 'tradicao':
    case 'indicacao':
    case 'capacidade':
    case 'inovacao':
    case 'satisfeito':
    case 'satisfeito_modo_usar':
    case 'satisfeito_manual':
    case 'satisfeito_energia':
    case 'satisfeito_barulho':
    case 'satisfeito_cor':
    case 'insatisfeito_modo_usar':
    case 'insatisfeito_manual':
    case 'insatisfeito_energia':
    case 'insatisfeito_barulho':
    case 'insatisfeito_cor':
    case 'insatisfeito_quebra_uso':
    case 'atendimento_rapido':
    case 'atendimento_rapido':
    case 'confianca':
    case 'problema_resolvido':
        $where_d         = ( $resposta ) ? 'true' : 'false' ; // condicao 'where' da sql
        
        $data_ini        = fnc_formata_data_pg(pg_escape_string($data_ini));
	    $data_fim        = fnc_formata_data_pg(pg_escape_string($data_fim));
	   
        $data_ini = str_replace("'","",$data_ini);
	    $data_fim = str_replace("'","",$data_fim);
		
		if(strlen($data_ini)==0 or strlen($data_fim)==0 or $data_ini == "null" or $data_fim == "null"){
			$cond_data = " AND 1=1 ";
		}else{
            if ($filtrar == 'os') {
                $cond_data = " AND o.data_abertura between '$data_ini 00:00:00' and '$data_fim 23:59:59' ";
            }else{
                //$cond_data       = " AND q.data BETWEEN '$data_ini 00:00:00' AND '$data_fim 23:59:59'";
                $cond_data = " AND q.data_input between '$data_ini 00:00:00' and '$data_fim 23:59:59' ";
            }   
        }

        $cond_os_produto = pg_escape_string($cond_os_produto);
        $sql_cli = "SELECT q.os, p.referencia as produto_referencia,
                           o.consumidor_nome, o.consumidor_cidade, o.consumidor_estado, o.consumidor_fone
                    FROM tbl_suggar_questionario q
                    INNER JOIN tbl_os o USING (os)
                    INNER JOIN tbl_produto p USING (produto)
                    WHERE 1=1
                    AND o.fabrica = $login_fabrica
                    $cond_data
                    AND $cond_os_produto
                    AND q.$detalhe is $where_d
                    AND q.$detalhe is not null";
        $res     = pg_exec($sql_cli);
        //echo nl2br($sql_cli);
        $total   = pg_num_rows($res);
        for ($i=0;$i<$total;$i++) {
            $aTmp                       = array();
            $aTmp['os']                 = pg_result($res,$i,'os');
            $aTmp['produto_referencia'] = pg_result($res,$i,'produto_referencia');
            $aTmp['consumidor_nome']    = pg_result($res,$i,'consumidor_nome');
            $aTmp['consumidor_cidade']  = pg_result($res,$i,'consumidor_cidade');
            $aTmp['consumidor_estado']  = pg_result($res,$i,'consumidor_estado');
            $aTmp['consumidor_fone']    = pg_result($res,$i,'consumidor_fone');
            $aClientes[]                = $aTmp;
        }
        unset($aTmp,$sql_cli,$where_d,$data_ini,$data_fim,$res,$i);
        break;
}
/**
 * Questao
 */
$questao_base_escolha      = "O que o levou a escolher os produtos da Suggar ? ";
$questao_base_satisfeito   = 'Sua satisfação é com relação ? '; 
$questao_base_insatisfeito = 'Sua insatisfação é com relação ? '; 
switch ($detalhe) {
    case 'preco':
        $questao  = $questao_base_escolha;
        $questao .= ($resposta) ? "(Pre&ccedil;o)" : "(N&atilde;o foi o pre&ccedil;o)";
        break;
    case 'qualidade':
        $questao  = $questao_base_escolha;
        $questao .= ($resposta) ? '(Qualidade)' : '(N&atilde;o foi a qualidade)' ;
        break;
    case 'design':
        $questao  = $questao_base_escolha;
        $questao .= ($resposta) ? '(Design)' : '(N&atilde;o foi o design)' ;
        break;
    case 'tradicao':
        $questao  = $questao_base_escolha;
        $questao .= ($resposta) ? '(Marca)' : '(N&atilde;o foi a marca)' ;
        break;
    case 'indicacao':
        $questao  = $questao_base_escolha;
        $questao .= ($resposta) ? '(Indica&ccedil;&atilde;o)' : '(N&atilde;o foi indica&ccedil;&atilde;o)' ;
        break;
    case 'capacidade':
        $questao  = $questao_base_escolha;
        $questao .= ($resposta) ? '(Pela capacidade)' : '(N&atilde;o foi pela capacidade)' ;
        break;
    case 'inovacao':
        $questao  = $questao_base_escolha;
        $questao .= ($resposta) ? '(Pela inova&ccedil;&atilde;o)' : '(N&atilde;o foi pela inova&ccedil;&atilde;o)' ;
        break;
    case 'satisfeito':
        $questao  = 'Com rela&ccedil;&atilde;o ao produto da Suggar ? ';
        $questao .= ($resposta) ? '(Satisfeito)' : '(Insatisfeito)' ;
        break;
    case 'satisfeito_modo_usar':
        $questao  = $questao_base_satisfeito;
        $questao .= ($resposta) ? '(Satisfeito com o Modo de usar)' : '(Insatisfeito com o Modo de Usar)' ;
        break;
    case 'satisfeito_manual':
        $questao  = $questao_base_satisfeito;
        $questao .= ($resposta) ? '(Satisfeito com o manual)' : '(Insatisfeito com o manual)' ;
        break;
    case 'satisfeito_energia':
        $questao  = $questao_base_satisfeito;
        $questao .= ($resposta) ? '(Satisfeito com o consumo de energia)' : '(Insatisfeitos com o consumo de energia)' ;
        break;
    case 'satisfeito_barulho':
        $questao  = $questao_base_satisfeito;
        $questao .= ($resposta) ? '(Satisfeito com o nível de ruído)' : '(Insatisfeito com o nível de ruído)' ;
        break;
    case 'satisfeito_cor':
        $questao  = $questao_base_satisfeito;
        $questao .= ($resposta) ? '(Satisfeito com a cor do produto)' : '(Insatisfeito com a cor do produto)' ;
        break;
    case 'insatisfeito_modo_usar':
        $questao  = $questao_base_insatisfeito;
        $questao .= ($resposta) ? '(Modo de usar o produto)' : '(Não está insatisfeito com o modo de usar o produto)' ;
        break;
    case 'insatisfeito_manual':
        $questao  = $questao_base_insatisfeito;
        $questao .= ($resposta) ? '(Manual)' : '(Não está insatisfeito com o manual)' ;
        break;
    case 'insatisfeito_energia':
        $questao  = $questao_base_insatisfeito;
        $questao .= ($resposta) ? '(Consumo de energia)' : '(Não está insatisfeito com o consumo de energia)' ;
        break;
    case 'insatisfeito_barulho':
        $questao  = $questao_base_insatisfeito;
        $questao .= ($resposta) ? '(Nível de ruído)' : '(Não está insatisfeito com o nível de ruído)' ;
        break;
    case 'insatisfeito_cor':
        $questao  = $questao_base_insatisfeito;
        $questao .= ($resposta) ? '(Cor do produto)' : '(Não está instisfeito com a cor do produto)' ;
        break;
    case 'insatisfeito_quebra_uso':
        $questao = ($resposta) ? 'Insatisfeito pois o produto quebrou com pouco uso' : 'O produto não quebrou com pouco uso' ;
        break;
    case 'atendimento_rapido':
        $questao = ($resposta) ? 'O atendimento da autorizada foi rápido' : 'O atendimento da autorizada NÃO foi rápido' ;
        break;
    case 'confianca':
        $questao = ($resposta) ? 'O aspecto da loja autorizada gerou confiança' : 'O aspecto da loja autorizada NÃO gerou confiança' ;
        break;
    case 'problema_resolvido':
        $questao = ($resposta) ? 'O problema foi resolvido pela autorizada' : 'O problema NÃO foi resolvido pela autorizada' ;
        break;
    default:
        $questao = "";
        break;
}
?>
<style type="text/css" rel="stylesheet">
    TABLE.tabela {
        width: 700px;
        font-family: verdana; 
        font-size: 11px;
        
    }
    TABLE.tabela THEAD TD,TH {
        background-color: #596d9b;
        text-align: center;
    }
    
    TABLE.tabela TR.impar TD {
        background-color: #d2d7e1;
    }
    
    TABLE.tablea TR.par TD {
        background-color: #ffffff;
    }
</style>

<table class="tabela" align="center" cellspacing="1" cellpadding="4" border="0">
    <thead>
        <tr>
            <th colspan="6">
                <?php echo $questao; ?>
            </th>
        </tr>
        <tr>
            <th colspan="6">
                Respostas: <?php echo $total; ?>
            </th>
        </tr>
        <tr>
            <th> OS </th>
            <th> Consumidor </th>
            <th> Produto </th>
            <th> Cidade </th>
            <th> Estado </th>
            <th> Telefone </th>
        </tr>
    </thead>
    <tbody>
        <?php if ( count($aClientes) == 0 ): ?>
            <tr>
                <td colspan="6">
                    N&atilde;o existem clientes para exibir.
                </td>
            </tr>
        <?php else: ?>
            <?php foreach ($aClientes as $linha=>$cliente): ?>
            <tr class="<?php echo ($linha%2)?'impar':'par'; ?>">
                <td> <?php echo $cliente['os']; ?> </td>
                <td> <?php echo $cliente['consumidor_nome']; ?> </td>
                <td> <?php echo $cliente['produto_referencia']; ?> </td>
                <td> <?php echo $cliente['consumidor_cidade']; ?> </td>
                <td> <?php echo $cliente['consumidor_estado']; ?> </td>
                <td> <?php echo $cliente['consumidor_fone']; ?> </td>
            </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>
