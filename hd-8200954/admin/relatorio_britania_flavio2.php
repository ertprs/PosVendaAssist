<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="financeiro";
include 'autentica_admin.php';
include 'funcoes.php';

	$layout_menu = "financeiro";
	$title = "Cobrança";
	include 'cabecalho.php';


		

		// select para calcular valor vencido e buscar vencimento mais antigo por empresa


$sql = "SELECT distinct 
tbl_os.os							           as os                ,
tbl_os.sua_os                                  as sua_os            ,
tbl_os.consumidor_nome                         as consumidor_nome   ,
tbl_os.consumidor_revenda                      as consumidor_revenda,
tbl_os.consumidor_fone                         as fone              ,
tbl_os.serie                                   as serie             ,
tbl_os.revenda_nome                            as revenda_nome      ,
to_char (tbl_os.data_digitacao,'DD/MM/YYYY')  AS data_digitacao     ,
to_char (tbl_os.data_abertura,'DD/MM/YYYY')   AS data_abertura      ,
to_char (tbl_os.data_fechamento,'DD/MM/YYYY') AS data_fechamento    ,
to_char (tbl_os.finalizada,'DD/MM/YYYY')      AS data_finalizada    ,
to_char (tbl_os.data_conserto,'DD/MM/YYYY')   AS data_conserto      ,
to_char (tbl_os.data_nf,'DD/MM/YYYY')         AS data_nf            ,
data_abertura::date - tbl_os.data_nf::date    AS dias_uso           ,

tbl_marca.nome                                AS marca_nome			,

tbl_solucao.descricao                            as solucao           ,

					
tbl_servico_realizado.descricao               AS servico            ,

tbl_defeito_constatado.descricao              AS defeito_constatado ,
tbl_defeito_reclamado.descricao               AS defeito_reclamado  ,

TO_CHAR (tbl_os_item.digitacao_item,'DD/MM/YYYY')  AS data_digitacao_item,

tbl_faturamento_item.faturamento			as faturamento			,
tbl_faturamento.nota_fiscal					as nota					,
tbl_posto_fabrica.posto                     as posto                ,
tbl_posto_fabrica.codigo_posto              as codigo_posto         ,

tbl_posto.nome				                as nome_posto           ,    
tbl_posto.estado							as estado				, 
 
tbl_produto.referencia						as produto				, 
tbl_produto.descricao						as descricao			, 

tbl_linha.codigo_linha						as linha				, 

tbl_familia.descricao						as familia				, 

tbl_status_pedido.descricao                 AS status_pedido        ,

tbl_peca.peca                                 as peca               ,
tbl_peca.referencia                           AS peca_referencia    ,
tbl_peca.descricao                            AS peca_descricao     ,

(SELECT tbl_status_os.descricao FROM tbl_status_os 
join tbl_os_status on tbl_status_os.status_os = tbl_os_status.status_os WHERE tbl_os_status.os = tbl_os.os ORDER BY os_status DESC LIMIT 1) AS status_os  ,

tbl_pedido.pedido                            as pedido              ,
tbl_pedido.distribuidor						 as distribuidor		

FROM tbl_os

JOIN      tbl_posto              ON  tbl_posto.posto              = tbl_os.posto
JOIN      tbl_posto_fabrica      ON  tbl_posto.posto              = tbl_posto_fabrica.posto 
								 AND tbl_posto_fabrica.fabrica    = 3 

JOIN tbl_produto			 ON  tbl_produto.produto          =  tbl_os.produto 
JOIN tbl_linha				 ON  tbl_linha.linha			  =  tbl_produto.linha  and tbl_linha.fabrica=3
join  tbl_marca			 ON tbl_marca.marca				  = tbl_produto.marca 
LEFT JOIN tbl_defeito_reclamado  ON  tbl_os.defeito_reclamado     = tbl_defeito_reclamado.defeito_reclamado 
LEFT JOIN tbl_defeito_constatado ON  tbl_os.defeito_constatado    = tbl_defeito_constatado.defeito_constatado 
LEFT JOIN tbl_os_produto         ON  tbl_os_produto.os            =  tbl_os.os  
LEFT JOIN tbl_os_item            ON  tbl_os_produto.os_produto    = tbl_os_item.os_produto 
LEFT JOIN tbl_peca               ON  tbl_os_item.peca             = tbl_peca.peca 
LEFT JOIN tbl_servico_realizado  ON tbl_os_item.servico_realizado = tbl_servico_realizado.servico_realizado 
LEFT JOIN tbl_pedido             ON tbl_os_item.pedido            = tbl_pedido.pedido 
LEFT JOIN tbl_status_pedido      ON tbl_pedido.status_pedido      = tbl_status_pedido.status_pedido  
LEFT JOIN tbl_familia			 ON  tbl_familia.familia          =  tbl_produto.familia  and tbl_familia.fabrica=3
LEFT JOIN tbl_faturamento_item   ON  tbl_os.os					  = tbl_faturamento_item.os  
LEFT JOIN tbl_faturamento		 ON  tbl_faturamento_item.faturamento   = tbl_faturamento.faturamento
LEFT JOIN tbl_solucao			 ON  tbl_os.solucao_os			  = tbl_solucao.solucao and tbl_solucao.fabrica=3

WHERE tbl_os.data_digitacao BETWEEN '2009-04-01 00:00:00' AND '2009-04-30 23:59:59' and tbl_os.fabrica=3 and tbl_os.excluida IS FALSE order by tbl_os.sua_os limit 20 ";



?>
<table>
<tr>
<td>sua os</td>
<td>CONSUMIDOR_REVENDA</td>
<td>CONSUMIDOR</td>
<td>TELEFONE	</td>
<td>N SRIE			</td>
<td>SOLUCAO	</td>
<td>NOTA-FISCAL		</td>
<td>DIGITAO	</td>
<td>ABERTURA</td>
<td>FECHAMENTO</td>
<td>FINALIZADA</td>
<td>CONSERTO</td>
<td>DATA NF</td>
<td>DIAS EM USO</td>
<td>MARCA</td>
<td>PRODUTO REFERNCIA	</td>
<td>PRODUTO DESCRIO	</td>
<td>LINHA	</td>
<td>FAMILIA	</td>
<td>ESTADO POSTO	</td>
<td>PEA REFERNCIA</td>
<td>PEA DESCRIO	</td>
<td>DATA ITEM	</td>
<td>DEFEITO RECLAMADO	</td>
<td>DEFEITO CONSTATADO	</td>
<td>SERVIO REALIZADO	</td>
<td>CODIGO POSTO	</td>
<td>RAZO SOCIAL	</td>
<td>NOME DA REVENDA		</td>
<td>STATUS DO PEDIDO	</td>
<td>STATUS OS			</td>
<td>PEDIDO			</td>	</tr>

<?

		$res = pg_exec($con,$sql);
		if (pg_numrows($res) > 0) {
			for ($x = 0 ; $x < pg_numrows($res) ; $x++){


$sua_os              = pg_result($res,$x,sua_os);
$consumidor_nome     = pg_result($res,$x,consumidor_nome);
$consumidor_revenda	 = pg_result($res,$x,consumidor_revenda);
$fone                = pg_result($res,$x,fone);
$serie               = pg_result($res,$x,serie);
$revenda_nome        = pg_result($res,$x,revenda_nome);
$data_digitacao      = pg_result($res,$x,data_digitacao);
$data_abertura       = pg_result($res,$x,data_abertura);
$data_fechamento     = pg_result($res,$x,data_fechamento);
$data_finalizada     = pg_result($res,$x,data_finalizada);
$data_conserto       = pg_result($res,$x,data_conserto);
$data_nf             = pg_result($res,$x,data_nf);
$dias_uso            = pg_result($res,$x,dias_uso);
$marca_nome			 = pg_result($res,$x,marca_nome);
$peca				 = pg_result($res,$x,peca);
$peca_referencia     = pg_result($res,$x,peca_referencia);
$peca_descricao      = pg_result($res,$x,peca_descricao);
$servico             = pg_result($res,$x,servico);
$defeito_constatado  = pg_result($res,$x,defeito_constatado);
$defeito_reclamado   = pg_result($res,$x,defeito_reclamado);
$data_digitacao_item = pg_result($res,$x,data_digitacao_item);
$nota				 = pg_result($res,$x,nota);
$solucao			 = pg_result($res,$x,solucao);
$posto               = pg_result($res,$x,posto);
$codigo_posto        = pg_result($res,$x,codigo_posto);
$nome_posto          = pg_result($res,$x,nome_posto);
$status_pedido       = pg_result($res,$x,status_pedido);
$status_os			 = pg_result($res,$x,status_os);
$estado 			 = pg_result($res,$x,estado);
$produto			 = pg_result($res,$x,produto);
$descricao			 = pg_result($res,$x,descricao);
$linha				 = pg_result($res,$x,linha);
$familia			 = pg_result($res,$x,familia);
$pedido			 = pg_result($res,$x,pedido);
$os = pg_result($res,$x,os);


echo "<tr>";
echo "<td>".$sua_os."</td>";
echo "<td>".$consumidor_revenda."</td>";
echo "<td>".$consumidor_nome."</td>";
echo "<td>".$fone."</td>";
echo "<td>".$serie."</td>";
echo "<td>".$solucao."</td>";
echo "<td>".$nota."</td>";
echo "<td>".$data_digitacao ."</td>";
echo "<td>".$data_abertura   ."</td>";
echo "<td>".$data_fechamento  ."</td>";
echo "<td>".$data_finalizada."</td>";
echo "<td>".$data_conserto  ."</td>";
echo "<td>".$data_nf ."</td>";
echo "<td>".$dias_uso."</td>";
echo "<td>".$marca_nome."</td>";
echo "<td>".$produto."</td>";
echo "<td>".$descricao."</td>";
echo "<td>".$linha."</td>";
echo "<td>".$familia."</td>";
echo "<td>".$estado."</td>";
echo "<td>".$peca_referencia ."</td>";
echo "<td>".$peca_descricao ."</td>";
echo "<td>".$data_digitacao_item."</td>";
echo "<td>".$defeito_reclamado."</td>";
echo "<td>".$defeito_constatado ."</td>";
echo "<td>".$servico  ."</td>";
echo "<td>".$codigo_posto."</td>";
echo "<td>".$nome_posto  ."</td>";
echo "<td>".$revenda_nome ."</td>";
echo "<td>".$status_pedido."</td>";
echo "<td>".$status_os."</td>";
echo "<td>".$pedido."</td></tr>";				

}
		}
echo "</table>";


include 'rodape.php';
?>

