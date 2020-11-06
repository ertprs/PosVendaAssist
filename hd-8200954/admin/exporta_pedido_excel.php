<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
include 'funcoes.php';

$ano = date ("Y");
$mes = date ("m");

if(strlen($_POST['btn_acao']) > 0)
	$btn_acao = $_POST['btn_acao'];

$pedido = $_GET['pedido'];

if(strlen($_POST['pedido']) > 0)
	$pedido = $_POST['pedido'];

if (strlen ($pedido) > 0) {
	$sql = "
		SELECT
			tbl_pedido.pedido                          AS pedido,
			to_char(tbl_pedido.data,'DD/MM/YYYY')      AS data,
			tbl_pedido.tipo_frete                      AS tipo_frete,
			tbl_pedido.pedido_cliente                  AS pedido_cliente,
			tbl_pedido.validade                        AS validade,
			tbl_pedido.entrega                         AS entrega,
			tbl_pedido.obs                             AS obs,
			tbl_posto.nome                             AS nome,
			tbl_posto.cnpj                             AS cnpj,
			tbl_posto.ie                               AS ie,
			tbl_posto.cidade                           AS cidade,
			tbl_posto.estado                           AS estado,
			tbl_posto.endereco                         AS endereco,
			tbl_posto.numero                           AS numero,
			tbl_posto.complemento                      AS complemento,
			tbl_posto.fone                             AS fone,
			tbl_posto.fax                              AS fax,
			tbl_posto.contato                          AS contato,
			tbl_pedido.tabela                          AS tabela,
			tbl_tabela.sigla_tabela                    AS siga_tabela,
			tbl_condicao.descricao                     AS condicao,
			tbl_admin.login                            AS login,
			tbl_posto_fabrica.desconto                 As desconto,
			tbl_tipo_posto.codigo                      AS codigo_tipo_posto,
			tbl_posto_fabrica.transportadora_nome      AS transportadora_nome,
			tbl_linha.nome                             AS linha_nome
		FROM
			tbl_pedido
			JOIN       tbl_posto          USING (posto)
			JOIN       tbl_posto_fabrica  USING (posto)
			LEFT JOIN  tbl_condicao       USING (condicao)
			LEFT JOIN  tbl_tabela         ON   tbl_pedido.tabela            = tbl_tabela.tabela
			LEFT JOIN  tbl_tipo_posto     ON   tbl_posto_fabrica.tipo_posto = tbl_tipo_posto.tipo_posto
			LEFT JOIN  tbl_admin          ON   tbl_admin.admin              = tbl_pedido.admin
			LEFT JOIN  tbl_linha          ON   tbl_linha.linha              = tbl_pedido.linha
		WHERE     tbl_pedido.pedido  = $pedido
		AND         tbl_pedido.fabrica = $login_fabrica";
	$res      = pg_exec ($con,$sql);
	$pedido   = pg_result ($res,0,pedido);
	$tabela   = pg_result ($res,0,tabela);
	$desconto = pg_result ($res,0,desconto);
	$obs_pa   = pg_result ($res,0,obs);
	$data	  = pg_result ($res,0,data);
	if (strlen ($tabela) == 0) $tabela = "null";
	$sql = "SELECT  to_char (tbl_pedido_item.qtde,'000')  AS qtde ,
					tbl_tabela_item.preco                 AS preco,
					tbl_peca.descricao                    AS descricao,
					tbl_peca.referencia                   AS referencia,
					tbl_peca.unidade                      AS unidade,
					tbl_peca.ipi                          AS ipi,
					tbl_peca.origem                       AS origem,
					tbl_peca.peso                         AS peso,
					tbl_peca.classificacao_fiscal         AS classificacao_fiscal
			FROM
				tbl_pedido_item
				JOIN      tbl_peca        ON tbl_pedido_item.peca = tbl_peca.peca
				LEFT JOIN tbl_tabela_item ON 
					(tbl_tabela_item.tabela = $tabela 
					AND tbl_tabela_item.peca = tbl_pedido_item.peca)
			WHERE
				tbl_pedido_item.pedido = $pedido
			ORDER BY  tbl_pedido_item.pedido_item";
	flush();
	$resI = pg_exec ($con,$sql);
	$total = 0;
	$login                        = pg_result ($res,0,login);
	$data                         = pg_result ($res,0,data);
	$pedido                       = pg_result ($res,0,pedido);
	$pedido_cliente               = pg_result ($res,0,pedido_cliente);
	$nome                         = pg_result ($res,0,nome);
	$contato                      = pg_result ($res,0,contato);
	$fone                         = pg_result ($res,0,fone);
	$endereco                     = pg_result ($res,0,endereco);
	$numero                    	  = pg_result ($res,0,'numero');
	$complemento                  = pg_result ($res,0,'complemento');
	$cidade                       = pg_result ($res,0,cidade);
	$estado                       = pg_result ($res,0,estado);
	$fax                          = pg_result ($res,0,fax);
	$cnpj                         = pg_result ($res,0,cnpj);
	$ie                           = pg_result ($res,0,ie);
	$linha_nome                   = pg_result ($res,0,linha_nome);
	$transportadora_nome          = pg_result ($res,0,transportadora_nome);
	$condicao                     = pg_result ($res,0,condicao);
	$tipo_frete                   = pg_result ($res,0,tipo_frete);
	$validade                     = pg_result ($res,0,validade);
	$entrega                      = pg_result ($res,0,entrega);
	$codigo_tipo_posto            = pg_result ($res,0,codigo_tipo_posto);
	$obs                          = pg_result ($res,0,obs);
	$desconto                     = pg_result ($res,0,desconto);
}
// INÍCIO DO PROCESSO DE GERAÇÃO DE ARQUIVO
if (pg_numrows($res) > 0) {
	$data                 = date ("d-m-Y-H-i");
	$arquivo_nome         = "exporta_pedido_excel-$login_fabrica-$ano-$mes-$data.xls";
	$path                 = "/www/assist/www/admin/xls/";
	$path_tmp             = "/tmp/assist/";
	$arquivo_completo     = $path.$arquivo_nome;
	$arquivo_completo_tmp = $path_tmp.$arquivo_nome;
	echo `rm $arquivo_completo_tmp `;
	echo `rm $arquivo_completo `;
	$fp = fopen ($arquivo_completo_tmp,"w");
	
	if (empty($entrega)){
		$entrega = null;
	}
	
	if (empty($codigo_tipo_posto)){
		$codigo_tipo_posto = null;
	}
	
	// PRIMEIRO CABEÇALHO
	fputs ($fp, "EMISSOR \t DATA \t PEDIDO \t PEDIDO CLIENTE \t NOME \t CONTATO \t FONE \t ENDEREÇO \t CIDADE \t ESTADO \t FAX \t CNPJ \t I.E. \t Linha \t TRANS. \t PAGAMENTO \t MODALIDADE \t TOTAL \t VALIDADE \t ENTREGA \t CLASSE \t MENSAGEM \r\n");
		// DADOS DO PRIMEIRO CABEÇALHO
		fputs($fp,"$login\t");
		fputs($fp,"$data\t");
		fputs($fp,"$pedido\t");
		fputs($fp,"$pedido_cliente\t");
		fputs($fp,"$nome\t");
		fputs($fp,"$contato\t");
		fputs($fp,"$fone\t");
		fputs($fp,"$endereco - $numero - $complemento\t");
		fputs($fp,"$cidade\t");
		fputs($fp,"$estado\t");
		fputs($fp,"$fax\t");
		$cnpj = trim (pg_result ($res,0,cnpj));
		if (strlen ($cnpj) == 14 ) {
			$cnpj = substr ($cnpj,0,2) . "." . substr ($cnpj,2,3) . "." . substr ($cnpj,5,3) . "/" . substr ($cnpj,8,4) . "-" . substr ($cnpj,12,2);
		}
		if (strlen ($cnpj) == 11 ) {
			$cnpj = substr ($cnpj,0,3) . "." . substr ($cnpj,3,3) . "." . substr ($cnpj,6,3) . "-" . substr ($cnpj,9,2);
		}
		for ($i=0; $i<pg_numrows($resI);$i++) {
			$ipi   = trim(pg_result ($resI,$i,ipi));
			$preco = pg_result ($resI,$i,qtde) * pg_result ($resI,$i,preco) ;
			$preco = $preco + ($preco * $ipi / 100);
			$total += $preco;
		}
		fputs($fp,"$cnpj\t");
		fputs($fp,"$ie\t");
		fputs($fp,"$linha_nome\t");
		fputs($fp,"$transportadora_nome\t");
		fputs($fp,"$condicao\t");
		fputs($fp,"$tipo_frete\t");
		fputs($fp,$total = ($total - ($total * $desconto / 100)));
		fputs($fp,"$validade\t");
		fputs($fp,"$entrega\t");
		fputs($fp,"$codigo_tipo_posto\t");
		fputs($fp,"\t");
		fputs($fp,"$obs\t");
		fputs($fp,"\r\n");
		fputs($fp,"\r\n");
		fputs($fp,"\r\n");
		fputs($fp,"\r\n");
		// SEGUNDO CABEÇALHO
	// DADOS DO SEGUNDO CABEÇALHO
		fputs ($fp, "IT \t Código \t Qtde \t Atend. \t Descrição \t IPI \t C \t un/s IPI+desc \t Total \t PCP \r\n");
			for ($i=0; $i<pg_numrows($resI);$i++) {
				$descricao                   = str_replace ('"','',pg_result ($resI,$i,descricao));
				$class_fiscal                = trim(pg_result ($resI,$i,classificacao_fiscal));
				$origem                      = trim(pg_result ($resI,$i,origem));
				$ipi                         = trim(pg_result ($resI,$i,ipi));
				$peso                        = trim(pg_result ($resI,$i,peso));
				$peso_estimado               = $peso_estimado + $peso;
				$preco_unitario_item         = trim(pg_result ($resI,$i,preco));
				$preco_unitario_item_sem_ipi = $preco_unitario_item - ($preco_unitario_item * $desconto / 100);
				$preco_unitario_item         = $preco_unitario_item + ($preco_unitario_item * $ipi / 100);
				$preco_total_item            = $preco_unitario_item * pg_result ($resI,$i,qtde);
				fputs($fp,number_format ($i+1,0)."\t");
				fputs($fp,pg_result ($resI,$i,referencia)."\t");
				fputs($fp,pg_result ($resI,$i,qtde)."\t");
				fputs($fp,"\t");
				fputs($fp,substr ($descricao,0,35)."\t");
				fputs($fp,"$ipi\t");
				fputs($fp,"$origem\t");
				fputs($fp,str_replace(".",",",$preco_unitario_item_sem_ipi)."\t");
				fputs($fp,number_format ($preco_total_item,2,",",".")."\t");
				fputs($fp,"\t");
				fputs($fp,"\r\n");
			}
		fputs($fp,"\t");
		fputs($fp,"\t");
		fputs($fp,"\t");
		fputs($fp,"\t");
		fputs($fp,"\t");
		fputs($fp,"\t");
		fputs($fp,"\t");
		fputs($fp,"Valor total do saldo com IPI, em R$, com desconto de".$desconto."%\t");
			fputs($fp,$total = $total - ($total * $desconto / 100));
		fputs($fp,"\r\n");
		fputs($fp,"\t");
		fputs($fp,"\t");
		fputs($fp,"\t");
		fputs($fp,"\t");
		fputs($fp,"\t");
		fputs($fp,"\t");
		fputs($fp,"\t");
		fputs($fp,"Peso total estimado do saldo: \t");
			fputs($fp,"$peso_estimado");
		fputs($fp,"\r\n");
		$sql2 = "SELECT
					tbl_os.sua_os         ,
					tbl_produto.referencia,
					tbl_produto.descricao
				FROM
					tbl_pedido
					JOIN tbl_pedido_item  USING(pedido)
					JOIN tbl_peca         USING(peca)
					JOIN tbl_os_item      USING(pedido)
					JOIN tbl_os_produto   USING(os_produto)
					JOIN tbl_os           USING(os)
					JOIN tbl_produto      ON tbl_produto.produto = tbl_os_produto.produto
				WHERE tbl_pedido_item.pedido = $pedido
				GROUP BY 
					tbl_os.sua_os,
					tbl_produto.referencia,
					tbl_produto.descricao
				ORDER BY tbl_os.sua_os;";
		$res2= pg_exec ($con,$sql2);
		if (pg_numrows($res2) > 0) {
			fputs($fp,"SUA OS \t EQUIPAMENTO \r\n");
			for ($i = 0 ; $i < pg_numrows ($res2) ; $i++) {
				$sua_os = trim(pg_result($res2,$i,sua_os));
				$equip  = trim(pg_result($res2,$i,referencia)) ."-".trim(pg_result($res2,$i,descricao));
				fputs($fp,"$sua_os\t");
				fputs($fp,"$equip\t");
				fputs($fp,"\r\n");
			}
		}
}
		fclose ($fp);
		echo `mv $arquivo_completo_tmp $arquivo_completo `;
		echo '<script>window.location.href="xls/'.$arquivo_nome.'"</script>'; 
		// FIM DO PROCESSO DE GERAÇÃO DE ARQUIVO

//include "rodape.php";

?>