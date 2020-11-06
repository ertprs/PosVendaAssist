<?

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';



// --====================================================================================--//
$os  = $_GET['os'];
$op  = $_GET['op'];
$cor = $_GET['cor'];
if (strlen ($os) > 0 AND $op=='ver') {
	include "ajax_cabecalho.php";

	$msg_erro = "";
	$res = pg_exec ($con,"BEGIN TRANSACTION");

	$sql = "SELECT  tbl_os.posto                                                      ,
					tbl_os.sua_os                                                     ,
					tbl_os.sua_os_offline                                             ,
					tbl_admin.login                              AS admin             ,
					troca_admin.login                            AS troca_admin       ,
					to_char(tbl_os.data_digitacao,'DD/MM/YYYY')  AS data_digitacao    ,
					to_char(tbl_os.data_abertura,'DD/MM/YYYY')   AS data_abertura     ,
					to_char(tbl_os.data_fechamento,'DD/MM/YYYY') AS data_fechamento   ,
					to_char(tbl_os.finalizada,'DD/MM/YYYY')      AS finalizada        ,
					tbl_os.tipo_atendimento                                           ,
					tbl_os.tecnico_nome                                               ,
					tbl_tipo_atendimento.descricao                 AS nome_atendimento,
					tbl_os.consumidor_nome                                            ,
					tbl_os.consumidor_fone                                            ,
					tbl_os.consumidor_endereco                                        ,
					tbl_os.consumidor_numero                                          ,
					tbl_os.consumidor_complemento                                     ,
					tbl_os.consumidor_bairro                                          ,
					tbl_os.consumidor_cep                                             ,
					tbl_os.consumidor_cidade                                          ,
					tbl_os.consumidor_estado                                          ,
					tbl_os.revenda_nome                                               ,
					tbl_os.revenda_cnpj                                               ,
					tbl_os.nota_fiscal                                                ,
					tbl_os.cliente                                                    ,
					tbl_os.revenda                                                    ,
					to_char(tbl_os.data_nf,'DD/MM/YYYY')         AS data_nf           ,
					tbl_defeito_reclamado.descricao              AS defeito_reclamado ,
					tbl_os.defeito_reclamado_descricao                                ,
					tbl_defeito_constatado.descricao             AS defeito_constatado,
					tbl_defeito_constatado.codigo                AS defeito_constatado_codigo,
					tbl_causa_defeito.codigo                     AS causa_defeito_codigo ,
					tbl_causa_defeito.descricao                  AS causa_defeito     ,
					tbl_causa_defeito.causa_defeito              AS cd                ,
					tbl_defeito_constatado.defeito_constatado    AS dc                ,
					tbl_defeito_reclamado.defeito_reclamado      AS dr                ,
					tbl_os.tipo_atendimento                      AS ta                ,
					tbl_os.aparencia_produto                                          ,
					tbl_os.acessorios                                                 ,
					tbl_os.consumidor_revenda                                         ,
					tbl_os.obs                                                        ,
					tbl_os.excluida                                                   ,
					tbl_produto.referencia                                            ,
					tbl_produto.descricao                                             ,
					tbl_produto.voltagem                                              ,
					tbl_os.qtde_produtos                                              ,
					tbl_os.serie                                                      ,
					tbl_os.posto                                                      ,
					tbl_os.codigo_fabricacao                                          ,
					tbl_os.troca_garantia                                             ,
					tbl_os.troca_via_distribuidor                                     ,
					tbl_os.troca_garantia_admin                                       ,
					to_char(tbl_os.troca_garantia_data,'DD/MM/YYYY') AS troca_garantia_data ,
					tbl_posto_fabrica.codigo_posto               AS posto_codigo      ,
					tbl_posto.nome                               AS posto_nome        ,
					tbl_posto.posto                               AS codigo_posto     ,
					tbl_os_extra.os_reincidente                                       ,
					tbl_os.ressarcimento                                              ,
					tbl_os.solucao_os
			FROM    tbl_os
			JOIN    tbl_posto                   ON tbl_posto.posto         = tbl_os.posto
			JOIN    tbl_posto_fabrica           ON  tbl_posto_fabrica.posto   = tbl_os.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
			LEFT JOIN    tbl_os_extra           ON tbl_os.os               = tbl_os_extra.os
			LEFT JOIN    tbl_admin              ON tbl_os.admin  = tbl_admin.admin
			LEFT JOIN    tbl_admin troca_admin  ON tbl_os.troca_garantia_admin = troca_admin.admin
			LEFT JOIN    tbl_defeito_reclamado  ON tbl_os.defeito_reclamado  = tbl_defeito_reclamado.defeito_reclamado
			LEFT JOIN    tbl_defeito_constatado ON tbl_os.defeito_constatado = tbl_defeito_constatado.defeito_constatado
			LEFT JOIN    tbl_causa_defeito      ON tbl_os.causa_defeito      = tbl_causa_defeito.causa_defeito
			LEFT JOIN    tbl_produto            ON tbl_os.produto            = tbl_produto.produto
			LEFT JOIN tbl_tipo_atendimento ON tbl_tipo_atendimento.tipo_atendimento = tbl_os.tipo_atendimento
			WHERE   tbl_os.os = $os
			AND     tbl_os.fabrica = $login_fabrica";
	$res = pg_exec ($con,$sql);
	$msg_erro = pg_errormessage($con) ;

	if (strlen ($msg_erro) == 0) {
		if (pg_numrows ($res) > 0) {
			$posto                       = pg_result ($res,0,posto);
			$sua_os                      = pg_result ($res,0,sua_os);
			$admin                       = pg_result ($res,0,admin);
			$data_digitacao              = pg_result ($res,0,data_digitacao);
			$data_abertura               = pg_result ($res,0,data_abertura);
			$data_fechamento             = pg_result ($res,0,data_fechamento);
			$data_finalizada             = pg_result ($res,0,finalizada);
			$consumidor_nome             = pg_result ($res,0,consumidor_nome);
			$consumidor_endereco         = pg_result ($res,0,consumidor_endereco);
			$consumidor_numero           = pg_result ($res,0,consumidor_numero);
			$consumidor_complemento      = pg_result ($res,0,consumidor_complemento);
			$consumidor_bairro           = pg_result ($res,0,consumidor_bairro);
			$consumidor_cidade           = pg_result ($res,0,consumidor_cidade);
			$consumidor_estado           = pg_result ($res,0,consumidor_estado);
			$consumidor_cep              = pg_result ($res,0,consumidor_cep);
			$consumidor_fone             = pg_result ($res,0,consumidor_fone);
			$revenda_cnpj                = pg_result ($res,0,revenda_cnpj);
			$revenda_nome                = pg_result ($res,0,revenda_nome);
			$nota_fiscal                 = pg_result ($res,0,nota_fiscal);
			$data_nf                     = pg_result ($res,0,data_nf);
			$cliente                     = pg_result ($res,0,cliente);
			$revenda                     = pg_result ($res,0,revenda);
			$defeito_reclamado           = pg_result ($res,0,defeito_reclamado);
			$aparencia_produto           = pg_result ($res,0,aparencia_produto);
			$acessorios                  = pg_result ($res,0,acessorios);
			$defeito_reclamado_descricao = pg_result ($res,0,defeito_reclamado_descricao);
			$produto_referencia          = pg_result ($res,0,referencia);
			$produto_descricao           = pg_result ($res,0,descricao);
			$produto_voltagem            = pg_result ($res,0,voltagem);
			$serie                       = pg_result ($res,0,serie);
			$codigo_fabricacao           = pg_result ($res,0,codigo_fabricacao);
			$consumidor_revenda          = pg_result ($res,0,consumidor_revenda);
			$defeito_constatado          = pg_result ($res,0,defeito_constatado);
			$defeito_constatado_codigo   = pg_result ($res,0,defeito_constatado_codigo);
			$causa_defeito               = pg_result ($res,0,causa_defeito);
			$causa_defeito_codigo        = pg_result ($res,0,causa_defeito_codigo);
			$posto_codigo                = pg_result ($res,0,posto_codigo);
			$posto_nome                  = pg_result ($res,0,posto_nome);
			$obs                         = pg_result ($res,0,obs);
			$qtde_produtos               = pg_result ($res,0,qtde_produtos);
			$excluida                    = pg_result ($res,0,excluida);
			$os_reincidente              = trim(pg_result($res,0,os_reincidente));
			$solucao_os                  = trim(pg_result($res,0,solucao_os));
			$troca_garantia              = trim(pg_result($res,0,troca_garantia));
			$troca_garantia_data         = trim(pg_result($res,0,troca_garantia_data));
			$troca_garantia_admin        = trim(pg_result($res,0,troca_garantia_admin));
			$tipo_atendimento            = trim(pg_result($res,0,tipo_atendimento));
			$tecnico_nome                = trim(pg_result($res,0,tecnico_nome));
			$nome_atendimento            = trim(pg_result($res,0,nome_atendimento));
			$sua_os_offline              = trim(pg_result($res,0,sua_os_offline));
			$ressarcimento               = trim(pg_result($res,0,ressarcimento));
			$troca_admin                 = trim(pg_result($res,0,troca_admin));
			$codigo_posto                = trim(pg_result($res,0,posto));
			$dc                          = trim(pg_result($res,0,dc));
			$cd                          = trim(pg_result($res,0,cd));
			$dr                          = trim(pg_result($res,0,dr));
			$ta                          = trim(pg_result($res,0,ta));

        if($aparencia_produto=='NEW'){
            if($sistema_lingua) $aparencia = "Buena aparencia";   else $aparencia = "Bom Estado";
            $aparencia_produto= $aparencia_produto.' - '.$aparencia;
        }
        if($aparencia_produto=='USL'){
            if($sistema_lingua) $aparencia = "Uso continuo";      else $aparencia = "Uso intenso";
            $aparencia_produto= $aparencia_produto.' - '.$aparencia;
        }
        if($aparencia_produto=='USN'){
            if($sistema_lingua) $aparencia = "Uso normal";        else $aparencia = "Uso Normal";
            $aparencia_produto= $aparencia_produto.' - '.$aparencia;
        }
        if($aparencia_produto=='USH'){
            if($sistema_lingua) $aparencia = "Uso Pesado";        else $aparencia = "Uso Pesado";
            $aparencia_produto= $aparencia_produto.' - '.$aparencia;
        }
        if($aparencia_produto=='ABU'){
            if($sistema_lingua) $aparencia = "Uso Abusivo";       else $aparencia = "Uso Abusivo";
            $aparencia_produto= $aparencia_produto.' - '.$aparencia;
        }
        if($aparencia_produto=='ORI'){
            if($sistema_lingua) $aparencia = "Original, sin uso"; else $aparencia = "Original, sem uso";
            $aparencia_produto= $aparencia_produto.' - '.$aparencia;
        }
        if($aparencia_produto=='PCK'){
            if($sistema_lingua) $aparencia = "Embalaje";          else $aparencia = "Embalagem";
            $aparencia_produto= $aparencia_produto.' - '.$aparencia;
        }
	//$resposta = "$dc - $dr -  $cd";
        //--=== Tradução para outras linguas ============================= Raphael HD:1212
        $sql_idioma = " SELECT tbl_produto_idioma.* FROM tbl_produto_idioma
			JOIN tbl_produto using(produto)
                        WHERE referencia     = '$produto_referencia'
                        AND upper(idioma) = '$sistema_lingua'";
        $res_idioma = @pg_exec($con,$sql_idioma);
        if (@pg_numrows($res_idioma) >0) {
            $produto_descricao  = trim(@pg_result($res_idioma,0,descricao));
        }
	if(strlen($dc)>0){
		$sql_idioma = "SELECT * FROM tbl_defeito_constatado_idioma
				WHERE defeito_constatado = '$dc'
				AND upper(idioma)        = '$sistema_lingua'";
		$res_idioma = @pg_exec($con,$sql_idioma);
		if (@pg_numrows($res_idioma) >0) {
		$defeito_constatado  = trim(@pg_result($res_idioma,0,descricao));
		}
	}
	if(strlen($dr)>0){
		$sql_idioma = "SELECT * FROM tbl_defeito_reclamado_idioma
				WHERE defeito_reclamado = '$dr'
				AND upper(idioma)        = '$sistema_lingua'";
		$res_idioma = @pg_exec($con,$sql_idioma);
		if (@pg_numrows($res_idioma) >0) {
		$defeito_reclamado_descricao  = trim(@pg_result($res_idioma,0,descricao));
		}
	}
	if(strlen($cd)>0){
		$sql_idioma = " SELECT * FROM tbl_causa_defeito_idioma
				WHERE causa_defeito = '$cd'
				AND upper(idioma)   = '$sistema_lingua'";
		$res_idioma = @pg_exec($con,$sql_idioma);
		if (@pg_numrows($res_idioma) >0) {
		$causa_defeito  = trim(@pg_result($res_idioma,0,descricao));
		}
        }
	if(strlen($ta)>0){
		$sql_idioma = " SELECT * FROM tbl_tipo_atendimento_idioma
				WHERE tipo_atendimento = '$ta'
				AND upper(idioma)   = '$sistema_lingua'";
		$res_idioma = @pg_exec($con,$sql_idioma);
		if (@pg_numrows($res_idioma) >0) {
		$nome_atendimento  = trim(@pg_result($res_idioma,0,descricao));
		}
        }

        //--=== Tradução para outras linguas ================================================


			if($cor=='#F1F4FA') $cor_titulo = '#32508D';
			else                $cor_titulo = '#B6A576';


			$resposta .= "<table width='700' border='0' cellspacing='1' cellpadding='0' class='Tabela' 	align='center'>";
			$resposta .="<tr ><td rowspan='4' class='conteudo' bgcolor='$cor' width='300' ><center>OS FABRICANTE<br>&nbsp;<b><FONT SIZE='5' COLOR='#C67700'>";

			if ($login_fabrica == 1)             $resposta .= "".$posto_codigo;
			if (strlen($consumidor_revenda) > 0) $resposta .= $sua_os ." - ". $consumidor_revenda;
			else                                 $resposta .= $sua_os;


			$resposta .= "</FONT></b><br>NF: ";
			$resposta .="$nota_fiscal";
			$resposta .="</center>";
			$resposta .= "</td>";
			$resposta .= "<td class='inicio' height='15' colspan='4' bgcolor='$cor'>&nbsp;";
if($sistema_lingua == 'ES') {
$resposta .= "Fecha del OS";
}else{
$resposta .= "DATAS DA OS";
}
$resposta .= "</td>";
			$resposta .= "</TR>";
			$resposta .= "<TR>";
			$resposta .= "<td class='titulo'width='100' height='15'>";
			$resposta .= "ABERTURA";
			$resposta .= "&nbsp;</td>";
			$resposta .= "<td class='conteudo' bgcolor='$cor' width='100' height='15'>&nbsp;$data_abertura</td>";
			$resposta .= "<td class='titulo' width='100' height='15'>";
if($sistema_lingua == 'ES') {
$resposta .= " DIGITACIÓN ";
}else{
$resposta .= " DIGITAÇÃO ";
}
$resposta .= " &nbsp;</td>";
			$resposta .= "<td class='conteudo' bgcolor='$cor' width='100' height='15'>&nbsp;$data_digitacao</td>";
			$resposta .= "</tr>";
			$resposta .= "<tr>";
			$resposta .= "<td class='titulo' width='100' height='15'>";
if($sistema_lingua == 'ES') {
$resposta .= " CERRAMIENTO ";
}else{
$resposta .= " FECHAMENTO ";
}
$resposta .= " &nbsp;</td>";
			$resposta .= "<td class='conteudo' bgcolor='$cor' width='100' height='15'>&nbsp;$data_fechamento</td>";
			$resposta .= "<td class='titulo' width='100' height='15'>";
if($sistema_lingua == 'ES') {
$resposta .= " FINALIZADA ";
}else{
$resposta .= " FINALIZADA ";
}
$resposta .= " &nbsp;</td>";
			$resposta .= "<td class='conteudo' bgcolor='$cor' width='100' height='15'>&nbsp;$data_finalizada</td>";
			$resposta .= "</tr>";
			$resposta .= "<tr>";
			$resposta .= "<TD class='titulo'  height='15'>";
if($sistema_lingua == 'ES') {
$resposta .= " FECHA COMPRA ";
}else{
$resposta .= " DATA DA NF ";
}
$resposta .= " &nbsp;</TD>";


			$resposta .= "<TD class='conteudo' bgcolor='$cor'  height='15'>&nbsp;$data_nf</TD>";
			$resposta .= "<td class='titulo' width='100' height='15'>";
if($sistema_lingua == 'ES') {
$resposta .= " FECHADO EN ";
}else{
$resposta .= " FECHADO EM ";
}
$resposta .= " &nbsp;</td>";
			$resposta .= "<td class='conteudo' bgcolor='$cor' width='100' height='15'>&nbsp;";
			if(strlen($data_fechamento)>0 AND strlen($data_abertura)>0){
				$sql_data = "SELECT SUM(data_fechamento - data_abertura)as final FROM tbl_os WHERE os=$os";
				$resD = pg_exec ($con,$sql_data);
				if (pg_numrows ($resD) > 0) {
					$total_de_dias_do_conserto = pg_result ($resD,0,'final');
				}

				if($total_de_dias_do_conserto==0) $resposta .=  'no mesmo dia' ;
				else                              $resposta .= $total_de_dias_do_conserto;
				if($total_de_dias_do_conserto==1) $resposta .=  ' dia' ;
				if($total_de_dias_do_conserto>1)  $resposta .=  ' dias' ;
			}
			$resposta .= "</td>";
			$resposta .= "</tr>";
			$resposta .= "</table>";

	// CAMPOS ADICIONAIS SOMENTE PARA LORENZETTI
		if($login_fabrica==19 OR $login_fabrica==20){

			$resposta .= "<TABLE width='700px' border='0' cellspacing='1' cellpadding='0' align='center' class='Tabela'>";
			$resposta .= "<TR>";
			$resposta .= "<TD class='titulo  height='15' width='90'>";
			if($sistema_lingua == 'ES') {
				$resposta .= " ATENDIMIENTO ";
			}else{
				$resposta .= " ATENDIMENTO ";
			}
			$resposta .= " &nbsp;</TD>";
			$resposta .= "<TD class='conteudo' bgcolor='$cor' height='15'>$nome_atendimento </TD>";
			$resposta .= "</TR>";
			$resposta .= "</TABLE>";
		}//FIM DA PARTE EXCLUSIVA DA LORENZETTI
		if(strlen($troca_garantia_admin)>0){
			$sql = "SELECT login,nome_completo
					FROM tbl_admin
					WHERE admin = $troca_garantia_admin";
			$res2 = pg_exec ($con,$sql);

			if (pg_numrows($res2) > 0) {
				$login                = pg_result ($res2,0,login);
				$nome_completo        = pg_result ($res2,0,nome_completo);

				$resposta .= "<TABLE width='700px' border='0' cellspacing='1' cellpadding='0' align='center' class='Tabela'>";
				$resposta .= "<TR>";
				$resposta .= "<TD class='titulo'  height='15' width='90'>Usuários&nbsp;</TD>";
				$resposta .= "<TD class='conteudo' bgcolor='$cor' height='15'>&nbsp;";
				if($nome_completo )$resposta .= $nome_completo; else $resposta .= $login;
				$resposta .= "</TD>";
				if(strlen($troca_garantia_data)>0){
				$resposta .= "<TD class='titulo' height='15'width='90'>Data</TD>";
				$resposta .= "<TD class='conteudo' bgcolor='$cor' height='15'>&nbsp;$troca_garantia_data </TD>";
				}
				$resposta .= "</TR>";
				$resposta .= "<TR>";
				$resposta .= "<TD class='conteudo' bgcolor='$cor'  height='15'colspan='4'>";
				if($troca_garantia=='t')
					$resposta .= "<b><center>Troca Direta</center></b>";
				else
					$resposta .= "<b><center>Troca Via Distribuidor</center></b>";
				$resposta .= "</TD>";
				$resposta .= "</TR>";
				$resposta .= "</TABLE>";
			}
		}

		$resposta .= "<table width='700' border='0' cellspacing='1' cellpadding='0' class='Tabela' align='center'>";
		$resposta .= "<tr>";
		$resposta .= "<td class='inicio' height='15' colspan='6' bgcolor='$cor'>&nbsp;";
if($sistema_lingua == 'ES') {
$resposta .= "  INFORMACIÓN DEL PRODUCTO  ";
}else{
$resposta .= " INFORMAÇÕES DO PRODUTO ";
}
$resposta .= " &nbsp;</td>";
		$resposta .= "</tr>";
		$resposta .= "<tr >";
		$resposta .= "<TD class='titulo' height='15' width='90'>";
if($sistema_lingua == 'ES') {
$resposta .= "  REFERENCIA  ";
}else{
$resposta .= " REFERÊNCIA ";
}
$resposta .= " &nbsp;</TD>";
		$resposta .= "<TD class='conteudo' bgcolor='$cor' height='15' >&nbsp;$produto_referencia </TD>";
		$resposta .= "<TD class='titulo' height='15' width='90'>";
if($sistema_lingua == 'ES') {
$resposta .= "  DESCRICIÓN  ";
}else{
$resposta .= " DESCRIÇÃO ";
}
$resposta .= "&nbsp;</TD>";
		$resposta .= "<TD class='conteudo' bgcolor='$cor' height='15' >&nbsp;$produto_descricao </TD>";
		$resposta .= "<TD class='titulo' height='15' width='90'>";
if($sistema_lingua == 'ES') {
$resposta .= "  N. DE SERIE  ";
}else{
$resposta .= " NÚMERO DE SÉRIE ";
}
$resposta .= "&nbsp;</TD>";
		$resposta .= "<TD class='conteudo' bgcolor='$cor' height='15'>&nbsp;$serie </TD>";
		$resposta .= "</tr>";
		if ($login_fabrica == 1) {
			$resposta .= "<tr>";
			$resposta .= "<TD class='titulo' height='15' width='90'>VOLTAGEM&nbsp;</TD>";
			$resposta .= "<TD class='conteudo' bgcolor='$cor' height='15'>&nbsp;$produto_voltagem </TD>";
			$resposta .= "<TD class='titulo' height='15' width='110'>CÓDIGO FABRICA?O&nbsp;</TD>";
			$resposta .= "<TD class='conteudo' bgcolor='$cor' height='15'>&nbsp;$codigo_fabricacao </TD>";
			$resposta .= "</tr>";
		}
		$resposta .= "</table>";
		if (strlen($aparencia_produto) > 0) {
			$resposta .= "<TABLE width='700px' border='0' cellspacing='1' cellpadding='0' align='center'  class='Tabela'>";
			$resposta .= "<TR>";
			$resposta .= "<td class='titulo' height='15' width='300'>";
if($sistema_lingua == 'ES') {
$resposta .= "  APARENCIA GERAL DEL APARELHO/PRODUCTO  ";
}else{
$resposta .= " APARENCIA GERAL DO APARELHO/PRODUTO ";
}
$resposta .= "</td>";
			$resposta .= "<td class='conteudo' bgcolor='$cor'>&nbsp;$aparencia_produto </td>";
			$resposta .= "</TR>";
			$resposta .= "</TABLE>";
		}
		if (strlen($acessorios) > 0) {
			$resposta .= "<TABLE width='700px' border='0' cellspacing='1' cellpadding='0' align='center'class='Tabela'>";
			$resposta .= "<TR>";
			$resposta .= "<TD class='titulo' height='15' width='300'>";
if($sistema_lingua == 'ES') {
$resposta .= "  ACESSORIOS DEIXADOS JUNTO COM O APARELHO  ";
}else{
$resposta .= " ACESSÓRIOS DEIXADOS JUNTO COM O APARELHO ";
}
$resposta .= "</TD>";
			$resposta .= "<TD class='conteudo' bgcolor='$cor'>&nbsp;$acessorios; </TD>";
			$resposta .= "</TR>";
			$resposta .= "</TABLE>";
		}
		if (strlen($defeito_reclamado) > 0) {
			$resposta .= "<TABLE width='700px' border='0' cellspacing='1' cellpadding='0' align='center'class='Tabela'>";
			$resposta .= "<TR>";
			$resposta .= "<TD class='titulo' height='15'width='300'>&nbsp;";
if($sistema_lingua == 'ES') {
$resposta .= "   FALLAS  ";
}else{
$resposta .= " INFORMAÇÕES SOBRE O DEFEITO ";
}
$resposta .= "</TD>";
			$resposta .= "<TD class='conteudo' bgcolor='$cor' >&nbsp;";
			if (strlen($defeito_reclamado) > 0) {
				$sql = "SELECT tbl_defeito_reclamado.descricao
						FROM   tbl_defeito_reclamado
						WHERE  tbl_defeito_reclamado.descricao = '$defeito_reclamado'";
						//WHERE  tbl_defeito_reclamado.defeito_reclamado = '$defeito_reclamado'";

				$res = pg_exec ($con,$sql);

				if (pg_numrows($res) > 0) {
					$descricao_defeito = trim(pg_result($res,0,descricao));
					$resposta .= "$defeito_reclamado_descricao";
				}
			}
			$resposta .= "</TD>";
			$resposta .= "</TR>";
			$resposta .= "</TABLE>";
		}
		$resposta .= "<TABLE width='700px' border='0' cellspacing='1' cellpadding='0' align='center' class='Tabela'>";
		$resposta .= "<TR>";
		$resposta .= "<TD  height='15' class='inicio' colspan='4' bgcolor='$cor'>&nbsp;";
if($sistema_lingua == 'ES') {
$resposta .= "   FALLAS  ";
}else{
$resposta .= " DEFEITOS ";
}
$resposta .= "</TD>";
		$resposta .= "</TR>";
		$resposta .= "<TR>";
		$resposta .= "<TD class='titulo' height='15' width='90'>";
if($sistema_lingua == 'ES') {
$resposta .= "   RECLAMADO  ";
}else{
$resposta .= " RECLAMADO ";
}
$resposta .= "</TD>";
		$resposta .= "<TD class='conteudo' bgcolor='$cor' height='15' width='150'> &nbsp;$descricao_defeito - $defeito_reclamado_descricao</TD>";
//defeito constatado
		$resposta .= "<TD class='titulo' height='15' width='90'>";
		if($login_fabrica==20)$resposta .= "REPARO";
		else                  $resposta .= "CONSTATADO";
		$resposta .= "</td>";
		$resposta .= "<td class='conteudo' bgcolor='$cor' height='15'>&nbsp;";
		if($login_fabrica==20) $resposta .= $defeito_constatado_codigo.' - ';
		$resposta .= $defeito_constatado;
		$resposta .="</TD>";
		$resposta .= "</TR>";
		$resposta .= "<TR>";

		$resposta .= "<TD class='titulo' height='15' width='90'>";
		if($login_fabrica==6)      $resposta .= "SOLUÇÃO";
		elseif($login_fabrica==20) {
			if($sistema_lingua == "ES") $resposta .= "DEFECTO";
			else                        $resposta .= "DEFEITO";
		}else                       $resposta .= "CAUSA"  ;

		$resposta .= "&nbsp;</td>";
		$resposta .= "<td class='conteudo' bgcolor='$cor' colspan='3' height='15'>";
		if($login_fabrica==6){
			if (strlen($solucao_os)>0){
				$xsql="SELECT descricao from tbl_servico_realizado where servico_realizado= $solucao_os limit 1";
				$xres = pg_exec($con, $xsql);
				$xsolucao = trim(pg_result($xres,0,descricao));
				$resposta .= "$xsolucao";
			}
		}else{
			if($login_fabrica==20)$resposta .= $causa_defeito_codigo.' - ' ;
			$resposta .= $causa_defeito;
		}
		$resposta .= "</TD>";
		$resposta .= "</TR>";

	if($login_fabrica==20){
		if($solucao_os){
			$xsql="SELECT descricao from tbl_servico_realizado where servico_realizado= $solucao_os limit 1";
			$xres = pg_exec($con, $xsql);
			$xsolucao = trim(pg_result($xres,0,descricao));

			if($sistema_lingua=="ES"){
				$xsql="SELECT descricao from tbl_servico_realizado_idioma where servico_realizado= $solucao_os limit 1";
				$xres = pg_exec($con, $xsql);
				$xsolucao = trim(pg_result($xres,0,descricao));
			}

			$resposta .= "<tr>";
			$resposta .= "<td class='titulo' height='15' width='90'>";
if($sistema_lingua == 'ES') {
$resposta .= "   IDENTIFICION  ";
}else{
$resposta .= " IDENTIFICAÇÃO ";
}
$resposta .= "&nbsp;</td>";
			$resposta .= "<td class='conteudo'bgcolor='$cor'colspan='3' height='15'>&nbsp;$xsolucao</TD>";
			$resposta .= "</tr>";
		}
	}

		$resposta .= "</TABLE>";

		$resposta .= "<TABLE width='700px' border='0' cellspacing='1' cellpadding='0' align='center'  class='Tabela'>";
		$resposta .= "<TR>";
		$resposta .= "<TD colspan='";
		if ($login_fabrica == 1) {$resposta .= "9"; }else{ $resposta .= "8"; }
		$resposta .= "' class='inicio' bgcolor='$cor'>&nbsp;";
if($sistema_lingua == 'ES') {
$resposta .= "   DIAGNÓSTICOS - COMPONENTES - MANTENIMIENTOS EXECUTADOS ";
}else{
$resposta .= " DIAGNÓSTICOS - COMPONENTES - MANUTENÇÕES EXECUTADAS ";
}
$resposta .= "</TD>";
		$resposta .= "</TR>";
		$resposta .= "<TR>";
		if($os_item_subconjunto == 't') {
			$resposta .= "<TD class=\"titulo2\">SUBCONJUNTO</TD>";
			$resposta .= "<TD class=\"titulo2\">POSIÇÃO</TD>";
		}

		$resposta .= "<TD class='titulo2'>COMPONENTE</TD>";
		$resposta .= "<TD class='titulo2'>";
if($sistema_lingua == 'ES') {
$resposta .= " CTD ";
}else{
$resposta .= " QTDE ";
}
$resposta .= "</TD>";
		if ($login_fabrica == 1) $resposta .= "<TD class='titulo'>PRE</TD>";
		$resposta .= "<TD class='titulo2'>DIGIT.</TD>";

		$resposta .= "</TR>";

		$sql = "SELECT  tbl_produto.referencia                                         ,
						tbl_produto.descricao                                          ,
						tbl_os_produto.serie                                           ,
						tbl_os_produto.versao                                          ,
						tbl_os_item.os_item                                            ,
						tbl_os_item.serigrafia                                         ,
						tbl_os_item.pedido              AS pedido_item                 ,
						tbl_os_item.peca                                               ,
						tbl_os_item.obs                                                ,
						tbl_os_item.custo_peca                                         ,
						tbl_os_item.posicao                                            ,
						TO_CHAR (tbl_os_item.digitacao_item,'DD/MM') AS digitacao_item ,
						tbl_pedido.pedido_blackedecker  AS pedido_blackedecker         ,
						tbl_pedido.distribuidor                                        ,
						tbl_defeito.descricao           AS defeito                     ,
						tbl_peca.referencia             AS referencia_peca             ,
						tbl_os_item_nf.nota_fiscal                                     ,
						TO_CHAR (tbl_os_item_nf.data_nf,'DD/MM/YYYY') AS data_nf      ,
						tbl_peca.descricao              AS descricao_peca              ,
						tbl_servico_realizado.descricao AS servico_realizado_descricao ,
						tbl_status_pedido.descricao     AS status_pedido               ,
						tbl_produto.referencia          AS subproduto_referencia       ,
						tbl_produto.descricao           AS subproduto_descricao        ,
						tbl_os_item.qtde
				FROM	tbl_os_produto
				JOIN	tbl_os_item USING (os_produto)
				JOIN	tbl_produto USING (produto)
				JOIN	tbl_peca    USING (peca)
				LEFT JOIN tbl_defeito USING (defeito)
				LEFT JOIN tbl_servico_realizado USING (servico_realizado)
				LEFT JOIN tbl_os_item_nf     ON tbl_os_item.os_item      = tbl_os_item_nf.os_item
				LEFT JOIN tbl_pedido         ON tbl_os_item.pedido       = tbl_pedido.pedido
				LEFT JOIN tbl_status_pedido  ON tbl_pedido.status_pedido = tbl_status_pedido.status_pedido
				WHERE   tbl_os_produto.os = $os
				ORDER BY tbl_peca.descricao";
		$res = pg_exec($con,$sql);
		$total = pg_numrows($res);

		for ($i = 0 ; $i < $total ; $i++) {
			$pedido        = trim(pg_result($res,$i,pedido_item));
			$pedido_blackedecker = trim(pg_result($res,$i,pedido_blackedecker));
			$obs           = trim(pg_result($res,$i,obs));
			$os_item       = trim(pg_result($res,$i,os_item));
			$peca          = trim(pg_result($res,$i,peca));
			$nota_fiscal   = trim(pg_result($res,$i,nota_fiscal));
			$status_pedido = trim(pg_result($res,$i,status_pedido));
			$peca_referencia = trim(pg_result($res,$i,referencia_peca));
			$peca_descricao  = pg_result($res,$i,descricao_peca);

			$distribuidor  = trim(pg_result($res,$i,distribuidor));
			$digitacao     = trim(pg_result($res,$i,digitacao_item));
			$data_nf       = trim(pg_result($res,$i,data_nf));

			$sql_idioma = " SELECT tbl_peca_idioma.* FROM tbl_peca_idioma
					JOIN tbl_peca using(peca)
					WHERE referencia     = '$peca_referencia'
					AND upper(idioma) = '$sistema_lingua'";
			$res_idioma = @pg_exec($con,$sql_idioma);

			if(pg_numrows($res)>0 ) $peca_descricao1 = trim(@pg_result($res_idioma,0,descricao));

			$resposta .= "<TR>";

			$resposta .= "<TD class='conteudo' bgcolor='$cor' style='text-align:left;'>". $peca_referencia. " - " . $peca_descricao."</TD>";
			$resposta .= "<TD class='conteudo' bgcolor='$cor' style='text-align:center;'>".pg_result($res,$i,qtde)."</TD>";

			if ($login_fabrica == 1) {
				$resposta .= "<TD class='conteudo' bgcolor='$cor' style='text-align:center;'>";
				$resposta .=  number_format (pg_result($res,$i,custo_peca),2,",",".");
				$resposta .= "</TD>";
			}

			$resposta .= "<TD class='conteudo' bgcolor='$cor' >".pg_result($res,$i,digitacao_item)."</TD>";
			$resposta .= "</tr>";
		}
		$resposta .= "</TABLE>";
		$resposta .= "<BR>";

		if (strlen($obs) > 0) {
			$resposta .= "<TABLE width='700px' border='0' cellspacing='1' cellpadding='0' align='center'>";
			$resposta .= "<TR>";
			$resposta .= "<TD class='conteudo' bgcolor='$cor'><b>OBS:</b>&nbsp;$obs</TD>";
			$resposta .= "</TR>";
			$resposta .= "</TABLE>";
		}
//fim
	}
}

	if (strlen ($msg_erro) == 0) {
		$res = pg_exec ($con,"COMMIT TRANSACTION");
		echo "ok|$resposta";
	}else{
		$res = @pg_exec ($con,"ROLLBACK TRANSACTION");
		echo "erro;$sql ==== $msg_erro ";
	}
	flush();
	exit;
}
//FIM DA EXIBI?O DO AJAX

// --====================================================================================--//
?>









