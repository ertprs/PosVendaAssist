<?
include '../dbconfig.php';
include '../includes/dbconnect-inc.php';
include 'autentica_usuario_empresa.php';
include 'ajax_cabecalho.php';




//--====== DETALHES DO TREINAMENTO =================================================================
if($_GET['ajax']=='sim' AND $_GET['acao']=='detalhes') {

	$hd_chamado  = $_GET["hd_chamado"];
	$cor         = $_GET["cor"];
	$cor = "#cccccc";
	$sql= " SELECT tbl_hd_chamado.hd_chamado                                              ,
			to_char (tbl_hd_chamado.data,'DD/MM HH24:MI') AS data                 ,
			tbl_hd_chamado.titulo                                                 ,
			tbl_hd_chamado.categoria                                              ,
			tbl_hd_chamado.status                                                 ,
			tbl_hd_chamado.empregado                                              ,
			tbl_hd_chamado.orcamento                                              ,
			tbl_orcamento.consumidor_nome                                         ,
			tbl_orcamento.consumidor_fone                                         ,
			tbl_orcamento.total_mao_de_obra                                       ,
			tbl_orcamento.total_pecas                                             ,
			tbl_orcamento.brinde                                                  ,
			tbl_orcamento.frete                                                   ,
			tbl_orcamento.desconto                                                ,
			tbl_orcamento.acrescimo                                               ,
			tbl_orcamento.total                                                   ,
			tbl_orcamento.aprovacao_responsavel                                   ,
			TO_CHAR(tbl_orcamento.data_previsao  ,'DD/MM/YYYY') AS data_previsao  ,
			TO_CHAR(tbl_orcamento.data_aprovacao ,'DD/MM/YYYY') AS data_aprovacao ,
			TO_CHAR(tbl_orcamento.data_reprovacao,'DD/MM/YYYY') AS data_reprovacao
		FROM tbl_hd_chamado
		JOIN tbl_orcamento USING(orcamento)
		WHERE hd_chamado = $hd_chamado";

	$res = pg_exec ($con,$sql);

	if (pg_numrows($res) > 0) {
		$orcamento            = pg_result($res,0,orcamento);
		$hd_chamado           = pg_result($res,0,hd_chamado);
		$empregado            = pg_result($res,0,empregado);
		$data                 = pg_result($res,0,data);
		$titulo               = pg_result($res,0,titulo);
		$categoria            = pg_result($res,0,categoria);
		$status               = pg_result($res,0,status);
		$consumidor_nome      = pg_result($res,0,consumidor_nome);
		$consumidor_fone      = pg_result($res,0,consumidor_fone);
		$data_previsao        = pg_result($res,0,data_previsao);
		$data_aprovacao       = pg_result($res,0,data_aprovacao);
		$data_reprovacao      = pg_result($res,0,data_reprovacao);

		$total_mao_de_obra    = number_format(pg_result($res,0,total_mao_de_obra),2,',','.');
		$total_pecas          = number_format(pg_result($res,0,total_pecas),2,',','.');
		$brinde               = number_format(pg_result($res,0,brinde),2,',','.');
		$frete                = number_format(pg_result($res,0,frete),2,',','.');
		$desconto             = number_format(pg_result($res,0,desconto),2,',','.');
		$acrescimo            = number_format(pg_result($res,0,acrescimo),2,',','.');
		$total                = number_format(pg_result($res,0,total),2,',','.');

		
		$sql2 = "SELECT nome     ,
				email

			FROM tbl_empregado 
			JOIN tbl_pessoa USING(pessoa)
			WHERE empregado = $empregado";
		//echo $sql2;
		$res2 = @pg_exec ($con,$sql2);
		$nome  = pg_result($res2,0,0);
		$nome_abreviado = explode (' ',$nome);
		$nome_abreviado = $nome_abreviado[0];

		$resposta .= "<table width='700'><tr><td valign='top'>";

			$resposta .= "<table border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#d2e4fc'width='500' align='center'>";
			$resposta .= "<tr class='HD'>";
			$resposta .= "<td bgcolor='$cor' align='center' colspan='8'><b>ORÇAMENTO <font size='4' color='#009900'>$orcamento</font></b></td>";
			$resposta .= "</tr>";
			$resposta .= "<tr class='HD'>";
			$resposta .= "<td bgcolor='$cor' align='left'><b>CRM N°</b></td>";
			$resposta .= "<td bgcolor='#FAFAFA' align='left'>$hd_chamado</td>";
			$resposta .= "<td bgcolor='$cor' align='left'><b>Data</b></td>";
			$resposta .= "<td bgcolor='#FAFAFA' align='left'>$data</td>";
			$resposta .= "<td bgcolor='$cor' align='left'><b>Status</b></td>";
			$resposta .= "<td bgcolor='#FAFAFA' align='left'>$status</td>";
			$resposta .= "<td bgcolor='$cor' align='left'><b>Vendedor</b></td>";
			$resposta .= "<td bgcolor='#FAFAFA' align='left' width='100' title='$nome'>$nome_abreviado</td>";
			$resposta .= "</tr>";
			$resposta .= "<tr class='HD'>";
			$resposta .= "<td bgcolor='$cor' align='left'><b>Cliente</b></td>";
			$resposta .= "<td bgcolor='#FAFAFA' align='left' colspan='5'>$consumidor_nome</td>";
			$resposta .= "<td bgcolor='$cor' align='left'><b>Fone</b></td>";
			$resposta .= "<td bgcolor='#FAFAFA' align='left'>$consumidor_fone</td>";
			$resposta .= "</tr>";

			$resposta .= "</table>";

		$sql2 = "SELECT descricao,preco,qtde FROM tbl_orcamento_item WHERE orcamento = $orcamento";
		$res2 = pg_exec ($con,$sql2);
		if (pg_numrows($res2) > 0) {
			$resposta  .=  "<br><font size='2'><b>Produtos";
			$resposta  .=  "<table border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#d2e4fc'  align='center' width='500'>";
			$resposta  .=  "<TR class='HD' height='20' bgcolor='$cor' align='center'>";
			$resposta  .=  "<TD ><b>Produto</b></TD>";
			$resposta  .=  "<TD ><b>Preço</b></TD>";
			$resposta  .=  "<TD ><b>Qtde</b></TD>";
			$resposta  .=  "<TD ><b>Total</b></TD>";
			$resposta  .=  "</TR>";
	
			for ($i=0; $i<pg_numrows($res2); $i++){

				$descricao_peca = pg_result($res2,$i,descricao);
				$preco_peca     = pg_result($res2,$i,preco);
				$qtde_peca      = pg_result($res2,$i,qtde);
				$total_peca     = $preco_peca * $qtde_peca;

				$preco_peca = number_format($preco_peca,2,',','.');
				$total_peca = number_format($total_peca,2,',','.');
	
				if($cor1=="#fafafa")$cor1 = '#fdfdfd';
				else                $cor1 = '#fafafa';
	
				$resposta  .=  "<TR bgcolor='$cor1'class='Conteudo'>";
				$resposta  .=  "<TD align='left' nowrap>$descricao_peca</TD>";
				$resposta  .=  "<TD align='right'>$preco_peca</TD>";
				$resposta  .=  "<TD align='center'nowrap>$qtde_peca</TD>";
				$resposta  .=  "<TD align='right'nowrap>$total_peca</TD>";
				$resposta  .=  "</TR>";
			}
			$resposta .= "</table>";
		}
		$sql2 = "SELECT  tbl_servico.descricao          ,
				tbl_orcamento_mao_de_obra.valor,
				tbl_orcamento_mao_de_obra.qtde
			FROM tbl_orcamento_mao_de_obra
			JOIN tbl_servico USING (servico)
			WHERE orcamento = $orcamento ";
		$res2 = pg_exec ($con,$sql2);
		if (pg_numrows($res2) > 0) {
			$resposta  .=  "<br><font size='2'><b>Serviços";
			$resposta  .=  "<table border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#d2e4fc'  align='center' width='500'>";
			$resposta  .=  "<TR class='HD' height='20' bgcolor='$cor' align='center'>";
			$resposta  .=  "<TD ><b>Serviço</b></TD>";
			$resposta  .=  "<TD ><b>Preço</b></TD>";
			$resposta  .=  "<TD ><b>Qtde</b></TD>";
			$resposta  .=  "<TD ><b>Total</b></TD>";
			$resposta  .=  "</TR>";
	
			for ($i=0; $i<pg_numrows($res2); $i++){

				$descricao_servico = pg_result($res2,$i,descricao);
				$preco_servico     = pg_result($res2,$i,valor);
				$qtde_servico      = pg_result($res2,$i,qtde);
				$total_servico     = $preco_servico * $qtde_servico;

				$preco_servico = number_format($preco_servico,2,',','.');
				$total_servico = number_format($total_servico,2,',','.');

				if($cor1 == "#fafafa")$cor1 = '#fdfdfd';
				else                  $cor1 = '#fafafa';
	
				$resposta  .=  "<TR bgcolor='$cor1'class='Conteudo'>";
				$resposta  .=  "<TD align='left'nowrap>$descricao_servico</TD>";
				$resposta  .=  "<TD align='right'>$preco_servico</TD>";
				$resposta  .=  "<TD align='center'nowrap>$qtde_servico</TD>";
				$resposta  .=  "<TD align='right'nowrap>$total_servico</TD>";
				$resposta  .=  "</TR>";
			}
			$resposta .= "</table>";
		}

		$resposta .= "</td>";
		$resposta .= "<td valign='top'>";


			$resposta .= "<table border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#d2e4fc'width='198' align='center'>";
			$resposta .= "<tr class='HD'>";
			$resposta .= "<td bgcolor='$cor' align='left'><b>Mão de Obra</b></td>";
			$resposta .= "<td bgcolor='#FAFAFA' align='right'>$total_mao_de_obra</td>";
			$resposta .= "</tr>";
			$resposta .= "<tr class='HD'>";
			$resposta .= "<td bgcolor='$cor' align='left'><b>Peças</b></td>";
			$resposta .= "<td bgcolor='#FAFAFA' align='right'>$total_pecas</td>";
			$resposta .= "</tr>";
			$resposta .= "<tr class='HD'>";
			$resposta .= "<td bgcolor='$cor' align='left'><b>Brinde</b></td>";
			$resposta .= "<td bgcolor='#FAFAFA' align='right'>$brinde</td>";
			$resposta .= "</tr>";
			$resposta .= "<tr class='HD'>";
			$resposta .= "<td bgcolor='$cor' align='left'><b>Frete</b></td>";
			$resposta .= "<td bgcolor='#FAFAFA' align='right'>$frete</td>";
			$resposta .= "</tr>";
			$resposta .= "<tr class='HD'>";
			$resposta .= "<td bgcolor='$cor' align='left'><b>Desconto</b></td>";
			$resposta .= "<td bgcolor='#FAFAFA' align='right'>$desconto</td>";
			$resposta .= "</tr>";
			$resposta .= "<tr class='HD'>";
			$resposta .= "<td bgcolor='$cor' align='left'><b>Acréscimo</b></td>";
			$resposta .= "<td bgcolor='#FAFAFA' align='right'>$acrescimo</td>";
			$resposta .= "</tr>";
			$resposta .= "<tr class='HD'>";
			$resposta .= "<td bgcolor='eeFFee' align='left'><b>TOTAL</b></td>";
			$resposta .= "<td bgcolor='#FAFAFA' align='right'><b>$total</b></td>";
			$resposta .= "</tr>";
			$resposta .= "</table>";
	
			$resposta .= "</td></tr>";
			$resposta .= "</table>";

		$resposta .= "</td></tr></table>";


		$resposta  .= "<div id='interacao_$hd_chamado'></div>";
		$resposta  .= "<script language='javascript'>mostrar_interacao('$hd_chamado','interacao_$hd_chamado');</script>";

	}


	$resposta .=  "<form name='frm'>";
	$resposta .=  "Digite novas informações abaixo: ";
	$resposta .=  "<input type='hidden' name='hd_chamado' id='hd_chamado' value='$hd_chamado'>";
	$resposta .=  "<input type='checkbox' name='email' id='hd_chamado' value='t'> Enviar por email para o cliente";
	$resposta .=  "<br>";
	$resposta .=  "<textarea name='comentario' id='comentario' cols='70' rows='5' class='Caixa'wrap='VIRTUAL'></textarea><br>";
	$resposta .=  "<input type='button' name='btn_acao' id='btn_acao' value='Gravar' onClick=\"if (this.value=='Gravando...'){ alert('Aguarde');}else {this.value='Gravando...'; gravar_comentario(this.form);}\">";

	$resposta .=  "</form>";

	echo "ok|$hd_chamado|".$resposta."<p>";
	exit;
}


if($_GET['ajax']=='sim' AND $_GET['acao']=='interacao') {

	$hd_chamado = $_GET["hd_chamado"];
	$cor = "#cccccc";
	$sql= "SELECT   tbl_hd_chamado_item.hd_chamado_item,
			to_char (tbl_hd_chamado_item.data,'DD/MM HH24:MI') AS data   ,
			tbl_hd_chamado_item.comentario                               ,
			tbl_hd_chamado_item.empregado                                ,
			tbl_hd_chamado_item.pessoa
		FROM tbl_hd_chamado_item 
		WHERE hd_chamado = $hd_chamado
		AND   interno IS NOT TRUE
		ORDER BY hd_chamado_item";
	$res = @pg_exec ($con,$sql);

	if (@pg_numrows($res) > 0) {

		$resposta  .=  "<font size='2'><b>Acompanhamento<table border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#d2e4fc'  align='center' width='700'>";
		$resposta  .=  "<TR class='HD' height='20' bgcolor='$cor' align='center'>";
		$resposta  .=  "<TD ><b>#</b></TD>";
		$resposta  .=  "<TD width='25'><b>Data</b></TD>";
		$resposta  .=  "<TD><b>Descrição</b></TD>";
		$resposta  .=  "<TD ><b>Autor</b></TD>";
		$resposta  .=  "</TR>";

		for ($i=0; $i<pg_numrows($res); $i++){

			$x=$i+1;

			$hd_chamado_item = pg_result($res,$i,hd_chamado_item);
			$data_interacao  = pg_result($res,$i,data);
			$item_comentario = pg_result($res,$i,comentario);
			$empregado       = pg_result($res,$i,empregado);
			$pessoa          = pg_result($res,$i,pessoa);

			$sql2 = "SELECT nome FROM tbl_empregado JOIN tbl_pessoa using(pessoa) WHERE empregado = $empregado";
			$res2 = @pg_exec ($con,$sql2);
			$empregado_nome = @pg_result($res2,0,0);

			$sql2 = "SELECT nome FROM tbl_pessoa WHERE pessoa = $pessoa";
			$res2 = @pg_exec ($con,$sql2);
			$cliente_nome = @pg_result($res2,0,0);


			if($cor=="#fafafa")$cor1 = '#fdfdfd';
			else               $cor1 = '#fafafa';
			if(strlen($cliente_nome)>0) $cor = "#CCFFCC";

			$resposta  .=  "<TR bgcolor='$cor1'class='Conteudo'>";
			$resposta  .=  "<TD align='left'>$x</TD>";
			$resposta  .=  "<TD align='center'nowrap>$data_interacao</TD>";
			$resposta  .=  "<TD align='left'>".nl2br($item_comentario)."</TD>";
			$resposta  .=  "<TD align='center'nowrap>$empregado_nome $cliente_nome</TD>";
			$resposta  .=  "</TR>";

		}
		$resposta .= " </TABLE>";

	}else{
		$resposta .= "<b>Nenhuma interação feita até o momento</b>";
	}
	echo "ok|$resposta";
}

if($_GET['ajax']=='sim' AND $_GET['acao']=='cadastrar') {
	$hd_chamado = $_GET["hd_chamado"];
	$comentario = $_GET["comentario"];
	$email      = $_GET["email"];

	if($email == 't'){
		$sql=  "SELECT  tbl_pessoa.pessoa                            ,
				tbl_pessoa.nome                              ,
				tbl_pessoa.email                             ,
				count(tbl_orcamento_venda.orcamento) AS venda,
				count(tbl_orcamento_os.orcamento)    AS os   ,
				tbl_orcamento.orcamento                      ,
				tbl_orcamento.aprovado
			FROM tbl_pessoa
			JOIN tbl_orcamento            ON tbl_orcamento.cliente = tbl_pessoa.pessoa
			JOIN tbl_hd_chamado           USING(orcamento) 
			LEFT JOIN tbl_orcamento_venda USING(orcamento)
			LEFT JOIN tbl_orcamento_os    USING(orcamento)
			WHERE hd_chamado = $hd_chamado
			GROUP BY tbl_pessoa.pessoa,nome,email,orcamento,aprovado;";
	
		$res = pg_exec ($con,$sql);
	
		if (@pg_numrows($res) > 0){
			$pessoa    = pg_result($res,0,pessoa);
			$nome      = pg_result($res,0,nome);
			$email     = pg_result($res,0,email);
			$venda     = pg_result($res,0,venda);
			$os        = pg_result($res,0,os);
			$orcamento = pg_result($res,0,orcamento);
			$aprovado  = pg_result($res,0,aprovado);
	
			$chave1 = md5($pessoa);
			$chave2 = md5($orcamento);
			
			if($aprovado <>'t') $texto  = "Orçamento de ";
			if($venda     > 0 ) $texto .= "Compra nº $orcamento ";
			elseif($os    > 0 ) $texto .= "Serviço nº $orcamento ";
			//ENVIA EMAIL PARA POSTO PRA CONFIRMAÇÃO
		
			$email_origem  = "$login_empregado_email";
			$email_destino = "$email";
			$assunto       = "$login_loja_nome - $texto";
		
			$corpo.= "$nome, foi enviado a seguinte mensagem referente a $texto<br>";
			$corpo.= "<i>\"$comentario\"</i>\n";

			$corpo.="<br>Para responder essa mensagem clique no link abaixo.\n\n";
			$corpo.="<br><br><a href='http://www.telecontrol.com.br/assist/erp/crm_cliente.php?key1=$chave1&key2=$pessoa&key3=$chave2&key4=$orcamento&key5=$hd_chamado'><font color='#0000FF'>CLIQUE AQUI PARA RESPONDER</font></a> \n\n";
			$corpo.="<br>Caso o link acima esteja com problema copie e cole este link em seu navegador: http://www.telecontrol.com.br/assist/erp/crm_cliente.php?key1=$chave1&key2=$pessoa&key3=$chave2&key4=$orcamento&key5=$hd_chamado'\n\n";
			$corpo.="<br><br>Att,<br><br>$login_empregado_nome\n";
			$corpo.="<br>$login_empregado_email - $login_loja_nome\n";
			$corpo.="<br>_______________________________________________\n";
			$corpo.="<br>OBS: POR FAVOR NÃO RESPONDA ESTE EMAIL.";
		
		
			$body_top  = "MIME-Version: 1.0\r\n";
			$body_top .= "Content-type: text/html; charset=iso-8859-1\r\n";
			$body_top .= "From: $email_origem\r\n";
		
			if ( @mail($email_destino, stripslashes($assunto), $corpo, $body_top ) ){
				$msg = "$email";
			}else{
				$resposta .= "Não foi possível enviar o email. Por favor entre em contato com a TELECONTROL.";
			}
		}
	}

	$sql = "INSERT INTO tbl_hd_chamado_item (hd_chamado,empregado,posto,comentario) VALUES ($hd_chamado,$login_empregado,$login_loja,'$comentario')";
	$res = @pg_exec ($con,$sql);
	$resposta = @pg_errormessage($res);

	if(strlen($resposta==0)) echo "ok|$hd_chamado|Informações cadastradas com sucesso $msg!";
	else                     echo "0|$resposta";

}
?>
