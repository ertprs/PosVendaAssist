<?

header("Content-Type: text/html; charset=ISO-8859-1",true);
header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header("Pragma: no-cache"); // HTTP/1.0
include '../dbconfig.php';
include '../includes/dbconnect-inc.php';
include 'autentica_usuario.php';

$fabrica = 10;
$q = strtolower(utf8_decode($_GET["q"]));
$tipo = $_GET['tipo'];
$os = $_GET['os'];
if (isset($_GET["q"])){


	if($tipo == 'posto') {
		$sql = "SELECT   tbl_posto.*, tbl_posto_fabrica.codigo_posto
			FROM     tbl_posto
			JOIN     tbl_posto_fabrica USING (posto)
			WHERE    (tbl_posto.nome ILIKE '%$q%' OR tbl_posto.nome_fantasia ILIKE '%$q%')
			AND      tbl_posto_fabrica.fabrica = $fabrica
			ORDER BY tbl_posto.nome";
		$res = pg_query($con,$sql);


		if(pg_num_rows($res)>0) {
			for ( $i = 0 ; $i < @pg_num_rows ($res) ; $i++ ) {
				$cnpj_revenda             = trim(pg_result($res,0,'cnpj'));

				$posto				= trim(pg_fetch_result($res,$i,'posto'));
				$nome				= trim(pg_fetch_result($res,$i,'nome'));
				$ie_consumidor      = pg_result($res,$i,'ie');
				$numero_consumidor  = pg_result($res,$i,'numero');
				$consumidor_cep     = pg_result($res,$i,'cep');
				$fone_consumidor    = pg_result($res,$i,'fone');

				/**
				 * Mudança Telecontrol -> Acácia
				 */
				if ($posto == 4311) {
					$nome = 'Acáciaeletro Paulista Ltda.';
				}

				echo "$nome|$cnpj_revenda|$ie_consumidor|$consumidor_cep|$numero_consumidor|$fone_consumidor|$posto|";
				echo "\n";
			}
		}else{
			echo "<h2>Se você não conseguir encontrar o destinatário, avise o Ger. Ronaldo para cadastrar na Fábrica Telecontrol o posto para que sirva de Fornecedor (não precisa credenciar como posto, apenas cadastrar).</h2>";
		}
	}



	if($tipo =='transportadora') {
		$sql = "SELECT  DISTINCT transp
				FROM     tbl_faturamento ";
		$sql .= " WHERE transp ilike '%$q%' ";
		$sql .=" AND fabrica in ($telecontrol_distrib)
					ORDER BY transp ";
		$res = @pg_query ($con,$sql);

		for ($i=0; $i < pg_num_rows($res); $i++) {
			$transp_nome	= strtoupper(trim(pg_fetch_result($res,$i,'transp')));
			echo "$transp_nome\n";
		}
	}


}

if($tipo == 'codigo') {
	$sql = "
			SELECT tbl_os.os,
					tbl_os.sua_os,
					tbl_os.consumidor_nome,
					tbl_os.consumidor_endereco,
					tbl_os.consumidor_bairro,
					tbl_os.consumidor_cidade,
					tbl_os.consumidor_estado,
					tbl_os.consumidor_cpf,
					tbl_os.consumidor_cep,
					tbl_os.consumidor_numero,
					tbl_os.consumidor_complemento,
					tbl_os.consumidor_fone,
                    tbl_os.consumidor_email,
					'0' AS indice
			FROM tbl_os
			JOIN tbl_os_produto using(os)
			JOIN tbl_os_item using(os_produto)
			JOIN tbl_pedido_item using(pedido_item)
			JOIN tbl_peca on tbl_peca.peca = tbl_os_item.peca
			LEFT JOIN tbl_icms on tbl_icms.estado_origem = 'SP'
			WHERE (tbl_os.sua_os = '$os' OR tbl_os.os = $os)
			and (tbl_icms.estado_destino=tbl_os.consumidor_estado or consumidor_estado isnull) and tbl_os.fabrica in ($telecontrol_distrib) LIMIT 1; ";


	$sql1 = "
		SELECT tbl_os_item.qtde,
		tbl_pedido_item.qtde_faturada_distribuidor,
		qtde_cancelada,
		(tbl_pedido_item.preco * (1 + (tbl_peca.ipi / 100))) as preco,
		tbl_peca.referencia,
		tbl_peca.peca,
		tbl_peca.descricao,
		tbl_pedido_item.pedido,
		tbl_pedido_item.peca_alternativa,
		tbl_pedido_item.pedido_item,
		tbl_os_item.os_item
		FROM tbl_os_item
		JOIN tbl_os_produto USING(os_produto)
		JOIN tbl_os ON tbl_os_produto.os = tbl_os.os
		JOIN tbl_pedido_item           ON tbl_pedido_item.pedido_item      = tbl_os_item.pedido_item
		JOIN tbl_peca                  ON tbl_peca.peca                    = tbl_pedido_item.peca
		LEFT JOIN tbl_faturamento_item ON tbl_os_produto.os                = tbl_faturamento_item.os and tbl_faturamento_item.peca = tbl_os_item.peca and tbl_faturamento_item.pedido = tbl_os_item.pedido
		LEFT JOIN tbl_faturamento      ON tbl_faturamento_item.faturamento = tbl_faturamento.faturamento and tbl_faturamento.fabrica = 10
		WHERE
		tbl_os_produto.os = $os
		and tbl_faturamento.nota_fiscal IS NULL
		and tbl_os.excluida is not true
		and tbl_os.data_digitacao > current_timestamp - interval '5 years'
		AND tbl_pedido_item.qtde_faturada_distribuidor - tbl_pedido_item.qtde = 0
		AND tbl_pedido_item.qtde_faturada_distribuidor > tbl_pedido_item.qtde_cancelada
	";

	$res1 = pg_query($con,$sql1);

	$itemDb = NULL;
	$rows1 = pg_num_rows($res1);

	if($rows1 == 0){
		$sql = "
			SELECT null as os,
					null as sua_os,
					tbl_hd_chamado_extra.nome as consumidor_nome,
					tbl_hd_chamado_extra.endereco as consumidor_endereco,
					tbl_hd_chamado_extra.bairro as consumidor_bairro,
					tbl_hd_chamado_extra.rg,
					tbl_cidade.nome as consumidor_cidade,
					tbl_cidade.estado as consumidor_estado,
					tbl_hd_chamado_extra.cpf as consumidor_cpf,
					tbl_hd_chamado_extra.cep as consumidor_cep,
					tbl_hd_chamado_extra.numero as consumidor_numero,
					tbl_hd_chamado_extra.complemento as consumidor_complemento,
					tbl_hd_chamado_extra.fone as consumidor_fone,
                    tbl_hd_chamado_extra.email as consumidor_email,
					'0' AS indice
			FROM tbl_hd_chamado_extra
			JOIN tbl_cidade USING(cidade)
			JOIN tbl_pedido USING(pedido)
			JOIN tbl_pedido_item ON tbl_pedido.pedido = tbl_pedido_item.pedido
			JOIN tbl_peca on tbl_peca.peca = tbl_pedido_item.peca
			LEFT JOIN tbl_icms on tbl_icms.estado_origem = 'SP'
			WHERE (tbl_hd_chamado_extra.hd_chamado = $os )
			and tbl_icms.estado_destino=tbl_cidade.estado and tbl_pedido.fabrica in ($telecontrol_distrib) LIMIT 1;
	";

		$sql1 = "
			with pendente as (
			SELECT tbl_pedido_item.qtde ,
			tbl_pedido_item.qtde_faturada_distribuidor,
			qtde_cancelada,
			(tbl_pedido_item.preco * (1 + (tbl_peca.ipi / 100))) as preco,
			tbl_peca.referencia,
			tbl_peca.peca,
			tbl_peca.descricao,
			tbl_pedido_item.pedido,
			tbl_pedido_item.pedido_item,
			tbl_pedido_item.peca_alternativa,
				null	as os_item,
			sum(tbl_faturamento_item.qtde) as qtde_fat
			FROM tbl_hd_chamado_extra
			JOIN tbl_pedido USING(pedido)
			JOIN tbl_pedido_item           ON tbl_pedido_item.pedido      = tbl_pedido.pedido
			JOIN tbl_peca                  ON tbl_peca.peca                    = tbl_pedido_item.peca
			LEFT JOIN tbl_faturamento_item ON tbl_pedido.pedido               = tbl_faturamento_item.pedido and tbl_faturamento_item.peca = tbl_pedido_item.peca
			LEFT JOIN tbl_faturamento      ON tbl_faturamento_item.faturamento = tbl_faturamento.faturamento and tbl_faturamento.fabrica = 10
			WHERE
			tbl_hd_chamado_extra.hd_chamado = $os
			AND qtde_faturada_distribuidor > 0 
			group by 1,2,3,4,5,6,7,8,9)
			select * from pendente where (qtde_fat isnull or qtde_fat <  qtde_faturada_distribuidor)
			";
		$res1 = pg_query($con,$sql1);
		$itemDb = NULL;
		$rows1 = pg_num_rows($res1);

		if($rows1 == 0){
			$msgErro = "O atendimento ". $os ." não possui pedidos pendentes";
		}
	} else {
		$sql_qtdes = "select sum(tbl_pedido_item.qtde) as qtde, sum(tbl_pedido_item.qtde_faturada_distribuidor) as qtde_faturada_distribuidor
						FROM tbl_os_item
						JOIN tbl_os_produto USING(os_produto)
						JOIN tbl_pedido_item           ON tbl_pedido_item.pedido_item      = tbl_os_item.pedido_item
						JOIN tbl_peca                  ON tbl_peca.peca                    = tbl_pedido_item.peca
						LEFT JOIN tbl_faturamento_item ON tbl_os_produto.os                = tbl_faturamento_item.os
						LEFT JOIN tbl_faturamento      ON tbl_faturamento_item.faturamento = tbl_faturamento.faturamento
						WHERE tbl_os_produto.os = $os
						and tbl_faturamento.nota_fiscal IS NULL
						AND tbl_pedido_item.qtde - tbl_pedido_item.qtde_cancelada > 0";
		$qry_qtdes = pg_query($sql_qtdes);
		$qtde_comp = pg_fetch_result($qry_qtdes, 0, 'qtde');
		$qtde_faturada_distribuidor_comp = pg_fetch_result($qry_qtdes, 0, 'qtde_faturada_distribuidor');

		if ($qtde_comp <> $qtde_faturada_distribuidor_comp) {
			$msgErro = "A ordem de serviço $os está embarcada parcialmente";
		}

	}

	if (empty($msgErro)) {
		for ( $i = 0 ; $i < $rows1 ; $i++ ) {
			$referencia = trim(pg_fetch_result($res1,$i,'referencia'));
			$descricao = trim(pg_fetch_result($res1,$i,'descricao'));
			$qtde = pg_fetch_result($res1,$i,'qtde');
			$qtde_faturada_distribuidor = pg_fetch_result($res1,$i,'qtde_faturada_distribuidor');
			$qtde_cancelada = pg_fetch_result($res1,$i,'qtde_cancelada');
			$preco = pg_fetch_result($res1,$i,'preco');
			$preco = number_format ($preco,2,".","");
			$os_item = pg_fetch_result($res1, $i, 'os_item');
			$pedido_item = pg_fetch_result($res1, $i, 'pedido_item');
			$peca_alternativa = pg_fetch_result($res1, $i, 'peca_alternativa');
			$pedido = pg_fetch_result($res1, $i, 'pedido');
			$qtde_fat = pg_fetch_result($res1, $i, 'qtde_fat');


			if(!empty($peca_alternativa)) {
				$sqla = "select referencia, descricao from tbl_peca where peca = $peca_alternativa";
				$resa = pg_query($con,$sqla);

				if(pg_num_rows($resa) > 0) {
					$referencia = trim(pg_fetch_result($resa,0,'referencia'));
					$descricao = trim(pg_fetch_result($resa,0,'descricao'));
				}
			}

			if($qtde_cancelada > 0) {
				$qtde = $qtde - $qtde_cancelada;
				$qtde = ($qtde > $qtde_faturada_distribuidor) ? $qtde - $qtde_faturada_distribuidor : $qtde;
				$qtde = ($qtde_fat > 0 and $qtde_faturada_distribuidor > $qtde_fat) ? $qtde - $qtde_fat : $qtde;
			}else{
				$qtde = ($qtde > $qtde_faturada_distribuidor) ? $qtde_faturada_distribuidor : $qtde;
				$qtde = ($qtde_fat > 0 and $qtde_faturada_distribuidor > $qtde_fat) ? $qtde - $qtde_fat : $qtde;
			}


			$itemDb .= "¬".$referencia."¬".$descricao."¬".$qtde."¬".$preco."¬".$os_item."¬".$pedido_item."¬".$pedido;
;
		}
	}

	$res = pg_query($con,$sql);


	if(pg_num_rows($res)>0) {
		for ( $i = 0 ; $i < @pg_num_rows ($res) ; $i++ ) {
			$os             = trim(pg_result($res,0,os));
			$sua_os				    = trim(pg_fetch_result($res,$i,'sua_os'));
			$consumidor_nome	    = trim(pg_fetch_result($res,$i,'consumidor_nome'));
			$consumidor_cpf         = preg_replace( "/[^0-9]/",'', trim( pg_result($res,$i,'consumidor_cpf') ) );
			$consumidor_ie         = trim ( pg_result($res,$i,'rg') );
			$consumidor_cep         = trim ( pg_result($res,$i,'consumidor_cep') );
			$consumidor_numero      = trim ( pg_result($res,$i,'consumidor_numero') );
			$consumidor_complemento = trim ( pg_result($res,$i,'consumidor_complemento') );
			$consumidor_fone = trim ( pg_result($res,$i,'consumidor_fone') );
			$consumidor_endereco = trim ( pg_result($res,$i,'consumidor_endereco') );
			$consumidor_bairro   = trim ( pg_result($res,$i,'consumidor_bairro') );
			$consumidor_cidade   = trim ( pg_result($res,$i,'consumidor_cidade') );
			$consumidor_estado   = trim ( pg_result($res,$i,'consumidor_estado') );
			$consumidor_email = pg_fetch_result($res, 0, 'consumidor_email');
			//$referencia          = trim ( pg_result($res,$i,'referencia') );
			//$descricao           = trim ( pg_result($res,$i,'descricao') );
			$indice           = trim ( pg_result($res,$i,'indice') );

			// $consumidor_cpf = str_replace('.',"",$consumidor_cpf);
			// $consumidor_cpf = str_replace('-',"",$consumidor_cpf);
			if(empty($msgErro)){
				echo "$sua_os|$consumidor_nome|$consumidor_cpf|$consumidor_cep|$consumidor_numero|$consumidor_complemento|$consumidor_fone|$os|$consumidor_endereco|$consumidor_bairro|$consumidor_cidade|$consumidor_estado|$consumidor_email|$itemDb|$indice||$consumidor_ie";
			}else{
				echo "$sua_os|$consumidor_nome|$consumidor_cpf|$consumidor_cep|$consumidor_numero|$consumidor_complemento|$consumidor_fone|$os|$consumidor_endereco|$consumidor_bairro|$consumidor_cidade|$consumidor_estado|$consumidor_email|$itemDb|$indice|$msgErro";
			}
			echo "\n";
		}
	}else{
		//echo "<h2>Não foi encontrado nenhum resultado para sua pesquisa.</h2>";
		echo "F";
	}
}
?>
