<?php
/*
	@author Brayan L. Rastelli
	@description Pesquisa de satisfação - HD 408341
*/
	include 'dbconfig.php';
	include 'includes/dbconnect-inc.php';
    include 'funcoes.php';

	header("Cache-Control: no-cache, public, must-revalidate, post-check=0, pre-check=0");
	header("Pragma: no-cache, public");

	$layout_menu = "cadastro";
	$admin_privilegios="cadastros";
	include 'autentica_admin.php';

	$title= traduz("CADASTRO DE PESQUISA DE SATISFAÇÃO");
    $display_none = "display:none;";

	/* inicio exclusao de integridade */
	if (isset($_GET['excluir']) ) {

		$id = (int) $_GET['excluir'];

		if(!empty($id)) {

			$sql = 'DELETE FROM tbl_pesquisa WHERE pesquisa =' . $id . ' AND fabrica = ' . $login_fabrica;
			$res = @pg_query($con,$sql);
			$msg_erro = pg_last_error($con);
			$msg = !empty($msg_erro) ? 'msg_erro='.traduz("Erro ao excluir a pesquisa. Exclua as perguntas antes.").'' : 'msg='.traduz("Excluída com Sucesso").'';
			header('Location: cadastro_pesquisa.php?' . $msg);

		}

	}
	/* fim exclusao */
	include 'cabecalho.php';

	if ( isset($_GET['pesquisa']) ) {

		$pesquisa = (int) $_GET['pesquisa'];
		$sql = "SELECT  descricao   ,
                        categoria   ,
                        ativo       ,
                        resposta_obrigatoria ,
                        texto_ajuda
				FROM    tbl_pesquisa
				WHERE   pesquisa = $pesquisa
        ";
		$res = pg_query($con,$sql);
		if ( pg_num_rows($res) > 0 ) {

			$descricao      = pg_result($res,0,0);
			$categoria      = pg_result($res,0,1);
			$ativo          = pg_result($res,0,2);
			$obrigatorio    = pg_result($res,0,3);
			$texto_ajuda    = pg_result($res,0,4);

		}

	}

	// ----- Inicio do cadastro ----------
	if ( isset($_POST['gravar'] ) ) {

		$msg_erro = null;
		$pesquisa 	= (int) trim ($_POST['pesquisa']);

		if ( empty($_POST['pergunta']) && empty($pesquisa) ) {

			$msg_erro = traduz('Escolha pelo menos uma pergunta');

		}

		$descricao 	= trim ( $_POST['descricao'] );
		$categoria	= trim ( $_POST['categoria'] );
		$ativo 		= trim ( $_POST['ativo'] );
		$obrigatorio = trim ( $_POST['obrigatorio'] );
		$texto_ajuda 		= trim ( $_POST['texto_ajuda'] );

        if(in_array($login_fabrica, array(169,170))){
            $categoria = "fabrica";
        }

		if ($ativo == 't' && $login_fabrica != 52) { //hd_chamado=2414398

			$sql = "SELECT pesquisa
					   FROM tbl_pesquisa
					   WHERE fabrica = $login_fabrica
					       AND ativo
					       AND categoria = '$categoria'";

			$res = pg_query($con,$sql);
            if ( pg_num_rows($res) > 0 ) {
				$pesquisa_ativa = pg_result($res,0,0);

                if($login_fabrica == 94){
                    if($categoria <> 'callcenter'){
                        if ($pesquisa != $pesquisa_ativa){
                            $msg_erro = traduz('Já existe uma pesquisa ativa para o ') . ucwords($categoria);
                        }
                    }
                }else{
                    if(!in_array($login_fabrica, array(169,170))){
                        if($login_fabrica == 161){
                            switch ($categoria) {
                                case "callcenter":
                                    $categoria_desc = "Callcenter";
                                    break;
                                case "externo":
                                    $categoria_desc = "E-mail Call-Center";
                                    break;
                                case "posto":
                                    $categoria_desc = "Posto Autorizado (Login)";
                                    break;
                                case "posto_2":
                                    $categoria_desc = "posto_2";
                                    break;
                                case "ordem_de_servico":
                                    $categoria_desc = "Ordem de Serviço";
                                    break;
                                case "ordem_de_servico_email":
                                    $categoria_desc = "Ordem de Serviço - Email";
                                    break;
                            }

                            if ($pesquisa != $pesquisa_ativa){
                                $msg_erro = 'Já existe uma pesquisa ativa para o ' . ucwords($categoria_desc);
                            }
                        } else {
                            if ($pesquisa != $pesquisa_ativa){
                                $msg_erro = 'Já existe uma pesquisa ativa para o ' . ucwords($categoria);
                            }
                        }
                    }
                }
            }
		}

		if (empty($descricao)) {
			$msg_erro = traduz('Digite a Descrição da Pesquisa');
		} else if (empty($categoria) && $login_fabrica != 52) {
			$msg_erro = traduz('Escolha o local da pesquisa.');
		} else if (empty($ativo)) {
			$msg_erro = traduz('Escolha a opção ativo ou inativo.');
		} else if (empty($obrigatorio)) {
			$msg_erro = traduz('Escolha a opção se a resposta é obrigatória ou não.');
		}

   	    if (empty($msg_erro) && !empty($pesquisa)) {

			$sql = "SELECT pesquisa
					FROM tbl_pesquisa
					WHERE pesquisa = $pesquisa
					AND fabrica = $login_fabrica";

			$res = pg_query($con,$sql);

			if ( pg_num_rows($res) == 0 ) {

				$msg_erro = traduz('Pesquisa ') . $pesquisa . traduz(' não Encontrada.');

			}

		}
        if( empty($msg_erro) ) {

            $perguntas = array();

            if (is_array($_POST['item'])){
                foreach( $_POST['item'] as $pergunta ) {
                    $perguntas[] = ($pergunta);

                }
            }

            if (is_array($_POST['ordem'])){
                foreach( $_POST['ordem'] as $indice => $ordens ) {
                    if (!empty($ordens)) {
                        $ordem[$indice] = ($ordens);
                    } else {
                        $msg_erro .= traduz("Inserir o campo Ordem nas Perguntas!");
                    }
                }
            }
        }

		if( empty($msg_erro) ) {

            # HD-2227519
            if($login_fabrica == 129){
                $perguntas_at = array();

                if (is_array($_POST['item_at'])){
                    foreach( $_POST['item_at'] as $pergunta_at ) {
                        $perguntas_at[] = ($pergunta_at);
                    }
                }

                if (is_array($_POST['ordem_at'])){
                    foreach( $_POST['ordem_at'] as $indice => $ordens_at ) {
                        $ordem_at[$indice] = ($ordens_at);
                    }
                }
            }
            # FIM HD-2227519


    		pg_exec($con,"BEGIN TRANSACTION");


    		if ( !empty ($pesquisa) ) {
    			$sql = "UPDATE  tbl_pesquisa
    					SET     admin       = $login_admin  ,
                                descricao   = '$descricao'  ,
                                ativo       = '$ativo'      ,
                                resposta_obrigatoria = '$obrigatorio',
                                categoria   = '$categoria'  ,
                                texto_ajuda = '$texto_ajuda'
    					WHERE   pesquisa    = $pesquisa
    					AND     fabrica     = $login_fabrica";
                $res = @pg_query($con,$sql);
    			$msg_erro .= pg_last_error($con);
    		} else {
    			$sql = "INSERT INTO tbl_pesquisa(
                            fabrica     ,
                            admin       ,
                            ativo       ,
                            resposta_obrigatoria ,
                            descricao   ,
                            categoria   ,
                            texto_ajuda
                        ) VALUES (
                            $login_fabrica  ,
                            $login_admin    ,
                            '$ativo'        ,
                            '$obrigatorio'  ,
                            '$descricao'    ,
                            '$categoria'    ,
                            '$texto_ajuda'
                        ) RETURNING pesquisa
                ";
                $res = @pg_query($con,$sql);
    			$msg_erro .= pg_last_error($con);
    			$pesquisa = @pg_result($res,0,0);
    		}

            for ( $i = 0; $i < count($perguntas); $i++ ) {

    			$sql2 = "SELECT pesquisa
                        FROM tbl_pesquisa_pergunta
                        WHERE pesquisa = '$pesquisa'
                        AND pergunta = '$perguntas[$i]'";
                $res2 = pg_query($con, $sql2);
                $msg_erro .= pg_last_error($con);

    			if(!empty($msg_erro) || pg_num_rows($res2) > 0)
    				continue;

    			if(!empty($perguntas[$i]) and !empty($ordem[$i])){
                    $sql = 'INSERT INTO tbl_pesquisa_pergunta (pesquisa,pergunta,ordem)
        							VALUES('.$pesquisa.','.$perguntas[$i].','.$ordem[$i].')';
                    $query = pg_query($con,$sql);
                    $msg_erro .= pg_last_error($con);
    			} else {
                    $msg_erro .= traduz("Inserir o campo Ordem nas Perguntas!");
                    break;
                }

    		}


            # HD-2227519
            if($login_fabrica == 129){
                for ( $at = 0; $at < count($perguntas_at); $at++ ) {

                  $sql3 = "SELECT pesquisa
                              FROM tbl_pesquisa_pergunta
                              WHERE pesquisa = '$pesquisa'
                              AND pergunta = '$perguntas_at[$at]'";
                  $res3 = pg_query($con, $sql3);
                  $msg_erro .= pg_last_error($con);

                  if(!empty($msg_erro) || pg_num_rows($res3) > 0)
                    continue;

                  if(!empty($perguntas_at[$at]) and !empty($ordem_at[$at])){

                    $sqlUp = "UPDATE tbl_pesquisa_pergunta SET ordem = $ordem_at[$at]
                        WHERE pesquisa = $pesquisa
                        AND pergunta = $perguntas_at[$at]";
                    $resUp = pg_query($con, $sqlUp);
                    $msg_erro .= pg_last_error($con);
                  }
                }

                # HD-2374588

                $sql_pesquisa_ob = "SELECT resposta_obrigatoria
                                    FROM tbl_pesquisa
                                    WHERE pesquisa = $pesquisa
                                    AND tbl_pesquisa.resposta_obrigatoria IS TRUE";
                $res_pesquisa_obr = pg_query($con, $sql_pesquisa_ob);

                if(pg_num_rows($res_pesquisa_obr) > 0){
                    $sqlObg = "SELECT tbl_tipo_resposta.obrigatorio
                            FROM tbl_pesquisa_pergunta
                            JOIN tbl_pergunta on tbl_pergunta.pergunta = tbl_pesquisa_pergunta.pergunta
                            JOIN tbl_tipo_resposta on tbl_tipo_resposta.tipo_resposta = tbl_pergunta.tipo_resposta
                            AND tbl_pesquisa_pergunta.pesquisa = $pesquisa
                            AND tbl_tipo_resposta.fabrica = $login_fabrica
                                AND tbl_tipo_resposta.obrigatorio IS TRUE";
                    $resObg = pg_query($con, $sqlObg);

                    if(pg_num_rows($resObg) == 0){
                        $msg_erro = "pergunta_obrigatoria";
                    }
                    # FIM HD-2374588
                }


                # FIM HD-2227519
            }

            if(empty($msg_erro)) {
                pg_exec($con, "COMMIT TRANSACTION");
                unset($pesquisa,$descricao,$ativo,$categoria,$texto_ajuda);
                unset($_POST);
                //header('Location:cadastro_pesquisa.php?msg=Gravado com Sucesso');
                $msg= traduz("Gravado com Sucesso");
        	}else {
                if($login_fabrica == 129 AND $msg_erro == "pergunta_obrigatoria"){
                    $msg_erro = "A pesquisa deve ter 1 pergunta com resposta obrigatória.";
                    pg_exec($con, "ROLLBACK TRANSACTION");
                }else{
                    $msg_erro = "Erro ao gravar pesquisa.<!-- $msg_erro -->";
    			 pg_exec($con, "ROLLBACK TRANSACTION");
                }
            }

        }

        if (!empty($msg_erro)) {
            $display_none = "display:block;";
        }
    }

	// fim cadastro
?>

<style type="text/css">
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
.msg_erro{
	background-color:#FF0000;
	font: bold 14px "Arial";
	color:#FFFFFF;
	text-align:center;
}
.formulario{
	background-color:#D9E2EF;
	font:11px Arial;
	text-align:left;
}
table.tabela tr td{
	font-family: verdana;
	font-size: 11px;
	border-collapse: collapse;
	border:1px solid #596d9b;
}
#tabela{<?=$display_none?>}
.sucesso{
    background-color:#008000;
    font: bold 14px "Arial";
    color:#FFFFFF;
    text-align:center;
}
.texto_avulso{
    font: 14px Arial; color: rgb(89, 109, 155);
    background-color: #d9e2ef;
    text-align: center;
    width:700px;
    margin: 0 auto;
    border-collapse: collapse;
    border:1px solid #596d9b;
}
.subtitulo{
    background-color: #7092BE;
    font:bold 11px Arial;
    color: #FFFFFF;
}
#pesquisas_cad tbody tr td {cursor:pointer;}
</style>
<script type="text/javascript">
function deletaitem(id) {

    $("#"+id).remove();

}
function deletaintegridade(id){

        if ( confirm('<?=traduz("Deseja mesmo excluir essa pesquisa?")?>') )
            window.location='?excluir=' + id;
        else
            return false;

}
function deletaPergunta(pergunta,pesquisa) {

    if ( confirm ('<?=traduz("Deseja mesmo excluir essa pergunta da pesquisa?")?>' ) ) {
        $.get('cadastro_pesquisa_ajax.php?pergunta='+pergunta+'&pesquisa='+pesquisa, function(data){
            if (data === 't') {
                alert('<?=traduz("Pergunta excluída com sucesso da pesquisa")?>');
                $("tr#perg_"+pergunta+pesquisa).remove();
            }
            else {
                alert('<?=traduz("Erro ao excluir pergunta")?>');
            }
        });
    }

    return false;

}
function editaPesquisa(id) {
    window.location = '?pesquisa='+id;
}
function addPergunta() {

    var i = $('tr.count_linha').length;

    var pergunta = $('#pergunta').val();
    var txt_pergunta = $('#pergunta').find('option').filter(':selected').text();

    var cor = (i % 2) ? "#F7F5F0" : "#F1F4FA";

    var htm_input = '<tr class="count_linha" id="'+i+'" bgcolor="'+cor+'"><td align="center"><input type="hidden" value="' + pergunta + '" name="item['+i+']"  />' + txt_pergunta+'</td> <td align="center"> <input type="text" name="ordem['+i+']" class="frm" size="5"> </td> <td align="center"> <button onclick="deletaitem('+i+')"><?=traduz("Remover")?></button></td></tr>';

    if (pergunta  === '') {
        alert('Escolha uma Pergunta');
        return false;
    }

    else {
        //i++;
        $("#tabela").css("display","block");
        $(htm_input).appendTo("#integracao");
    }
}

$().ready(function(){

    //i = 0;
    $("#addPergunta").click(function(e){
        addPergunta();
        e.preventDefault();
    });

    $(".pesquisa_ajax").click(function(){

        var id = $(this).attr('id');

        if ( $("."+id).length > 0 ) {
            $("."+id).toggle();
            $("#"+id+"-2").toggle();
            return;
        }
        $.getJSON('cadastro_pesquisa_ajax.php?pesquisa=' + id + '&cache=<?=time()?>&fabrica=<?=$login_fabrica?>',function(data) {

            <?php if($login_fabrica == 30){
                ?>
                header = '<tr class="subtitulo '+id+'" id="header_'+id+'"><th><?=traduz("Ordem")?></th><th colspan="3"><?=traduz("Descrição")?></th><th><?=traduz("Ativo")?></th><th><?=traduz("Ações")?></th></tr>';
                <?php
            }else{
                ?>
                header = '<tr class="subtitulo '+id+'" id="header_'+id+'"><th><?=traduz("Ordem")?></th><th colspan="2"><?=traduz("Tipo Pergunta")?></th><th><?=traduz("Descrição")?></th><th><?=traduz("Ativo")?></th><th><?=traduz("Ações")?></th></tr>';
                <?php
            } ?>

            var i = 0;
            $.each(data, function(key, obj) {

                var cor = (i % 2 == 0 ) ? "#F7F5F0" : "#F1F4FA";
                console.log(obj);
                tr = document.createElement('tr');

                $(tr).attr('id','perg_'+obj.pergunta+id);

                $(tr).attr('class',id);
                $(tr).attr('bgcolor',cor);

                if ( obj.pergunta !== null ) {
                    col = '1';
                } else {
                    col = '2';
                }

                <?php if($login_fabrica == 30){
                ?>
                    var td = '<td>'+obj.ordem+'</td><td colspan="3" class="'+id+'" align="left">'+obj.descricao+'</td>';
                    <?php
                }else{
                    ?>
                    var td = '<td>'+obj.ordem+'</td><td align="left" colspan="2">'+obj.tipo_pergunta+'</td><td  colspan="'+col+'" class="'+id+'" align="left">'+obj.descricao+'</td>';
                    <?php
                } ?>

                ativo = document.createElement('td');
                $(ativo).append( obj.ativo );

                $(tr).append( td, ativo);

                if ( obj.pergunta !== null) {
                    excluir = document.createElement('td');
                    $(excluir).append('<button onclick="deletaPergunta('+obj.pergunta+','+id+')">Excluir</button>');
                    $(tr).append(excluir);
                }
                if ( i === 0 ){
                    $("#"+id+"-2").toggle();
                    $("#"+id+"-2").after(header,tr)
                }else{
                    $("#header_"+id).after(tr);
                }

                i++;

            });
        });
    });

<?
if(in_array($login_fabrica, array(1,94))) {
?>
    if($("#categoria :selected").val() == ''){
        $("#table_obrigatorio").css("visibility","hidden");
    }
    $("#categoria").change(function(){
        if($("#categoria :selected").val() == 'posto'){
            $("#table_obrigatorio").css("visibility","visible");
        }else{
            $("#table_obrigatorio").css("visibility","hidden");
            $("#obrigatorio option[value=f]").prop("selected",true);
        }
    });
<?
}else{
?>
    $("#table_obrigatorio").css("visibility","visible");
<?
}
?>
});
</script>
<form action="<?php echo $_SERVER['PHP_SELF'] ?>" method="POST">
<?php if(isset($msg_erro) && !empty($msg_erro)) { ?>
	<div class="msg_erro" style="width:700px;margin:auto; text-align:center;"><?=$msg_erro?> </div>
<?php } ?>
<?php if(isset($msg)) { ?>
	<div class="sucesso" style="width:700px;margin:auto; text-align:center;"><?=$msg?> </div>
<?php } ?>

<div class="formulario" style="width:700px; margin:auto;text-align:center;">

	<div class="titulo_tabela">Cadastro</div>

	<div style="padding:10px;">
			<fieldset style="text-align:left; border:none;">
				<table width="660px">
					<tr>
						<td colspan="3">
							<label for="descricao">Título</label><br />
							<input type="hidden" name="pesquisa" value="<?=$pesquisa?>" />
							<input type="text" name="descricao" value="<?=$descricao?>" id="descricao" class="frm" size="70" maxlength='80' />
						</td>
					</tr>
					<tr>
                        <?php if(!in_array($login_fabrica, array(169,170))){ ?>
                        <td width="150">
                            <label for="categoria"><?=traduz('Local da Pesquisa')?></label><br />
                            <select name="categoria" id="categoria" class="frm">
                                <option value=""></option>
                                <?php
                                if (!in_array($login_fabrica,array(30,151,152,180,181,182))) {
                                ?>
                                <option value="callcenter" <?=($categoria=='callcenter') ? 'selected' : ''?>>Callcenter</option>
<?php
                                }

                                if(in_array($login_fabrica, array(129))) {
?>
                                <option value="externo" <?=($categoria=='externo') ? 'selected' : ''?>>Callcenter - E-mail</option>
<?php
                                }

                                if (!in_array($login_fabrica, array(52,94))) {
?>
                                <option value="posto" <?=($categoria=='posto') ? 'selected' : ''?>><?=traduz('Posto Autorizado')?></option>
<?php
                                }

                                if(in_array($login_fabrica, array(1,85,94,145,161))) {
?>
                                <option value="externo" <?=($categoria=='externo') ? 'selected' : ''?>>E-mail consumidor</option>
<?php
                                }

                                if(in_array($login_fabrica, array(24,74))) {
?>
							    <option value="externo"<?=($categoria=='posto_2') ? 'selected' : ''?>>Posto</option>
<?php
                                }

                                if(in_array($login_fabrica, array(35,129,138,145,161))) {
?>
                                <option value="ordem_de_servico" <?=($categoria=='ordem_de_servico') ? 'selected' : ''?> >Ordem de Serviço</option>
<?php
                                }

                                if(in_array($login_fabrica, array(129,161))) {
?>
                                <option value="ordem_de_servico_email" <?=($categoria=='ordem_de_servico_email') ? 'selected' : ''?> >Ordem de Serviço - E-mail</option>
<?php
                                }
                                if (in_array($login_fabrica,array(161))) {
?>
                                <option value="externo_outros" <?=($categoria=='externo_outros') ? 'selected' : ''?> >E-mail Consumidor - Pós Venda</option>
<?php
                                }
?>
                            </select>
                        </td>
                        <?php } ?>
						<td width="80">
							<label for="ativo"><?=traduz('Ativo')?></label><br />
							<select name="ativo" id="ativo" class="frm">
								<option value=""></option>
								<option value="t" <?=($ativo=='t') ? 'selected' : ''?>><?=traduz('Ativo')?></option>
								<option value="f" <?=($ativo=='f') ? 'selected' : ''?>><?=traduz('Inativo')?></option>
							</select>
						</td>
                        <td id="table_obrigatorio">
							<label for="obrigatorio"><?=traduz('Resposta Obrigatória')?></label><br />
							<select name="obrigatorio" id="obrigatorio" class="frm">
								<option value=""></option>
								<option value="t" <?=($obrigatorio=='t') ? 'selected' : ''?>><?=traduz('Sim')?></option>
								<option value="f" <?=($obrigatorio=='f') ? 'selected' : ''?>><?=traduz('Não')?></option>
							</select>
						</td>
					</tr>
					<tr>
						<td colspan="3">
							<?=traduz('Texto da pesquisa:')?>
						</td>
					</tr>
					<tr>
						<td colspan="3">
							<textarea name="texto_ajuda" value="$texto_ajuda" class="frm" style="width:100%;height:100px"><?php echo $texto_ajuda ?></textarea>
						</td>
					</tr>

				</table>
			</fieldset>

	</div>

</div>
<div class="formulario" style="width:700px; margin:auto;text-align:center;">
	<table width="100%">
		<tr class="subtitulo">
		    <td>
		        <?=traduz('Adicionar perguntas na pesquisa')?>
		    </td>
		</tr>
		<tr><td>&nbsp;</td></tr>
		<tr>
			<td>
				<select name="pergunta" id="pergunta" class="frm" style="width:600px">
					<option value=""></option>
					<?php
						if ($login_fabrica == 52) {
							$join = 'JOIN tbl_tipo_pergunta USING(tipo_pergunta) JOIN tbl_tipo_relacao USING(tipo_relacao)';
							$campos_tipo_pergunta = ", tbl_tipo_pergunta.descricao as tipo_pergunta_desc ";
							$where_sigla = "AND sigla_relacao in ('A','D','C') ";
							$order_by = 'tbl_tipo_pergunta.descricao';
						}else{
							$order_by = 'descricao';
						}
						$sql ="SELECT tbl_pergunta.descricao,tbl_pergunta.pergunta $campos_tipo_pergunta from tbl_pergunta $join  where tbl_pergunta.fabrica=$login_fabrica and tbl_pergunta.ativo='t' $where_sigla order by $order_by";
                        $res = pg_exec ($con,$sql);
						for ($y = 0 ; $y < pg_numrows($res) ; $y++){

							$xpergunta = pg_result($res,$y,'pergunta');
                            $select_pergunta = "";
                            if ($xpergunta == $_POST['pergunta']) {
                                $select_pergunta = "selected";
                            }
							if($login_fabrica == 52){
                                echo '<option value="'.$xpergunta.'"'.$select_pergunta.'>' . pg_result($res,$y,'tipo_pergunta_desc'). ' - '. pg_result($res,$y,'descricao') . "</option>";
                            }else{
                                echo '<option value="'.$xpergunta.'"'.$select_pergunta.'>' . pg_result($res,$y,'descricao') . "</option>";
                            }
                        }
					?>
				</select>&nbsp;
				<button  id="addPergunta"><?=traduz('Adicionar')?></button>
			</td>
		</tr>
	</table>

	<div id="tabela" style="width:700px; margin:auto;" class="formulario">

		<table id="integracao" class="tabela" width="100%" cellspacing="1" align="center">
			<thead>
				<tr class="titulo_coluna">
					<th><?=traduz('Pergunta')?></th>
					<th><?=traduz('Ordem')?></th>
					<th><?=traduz('Ações')?></th>
				</tr>
			</thead>
            <?php if (!empty($_POST['item'])) { ?>
                <tbody>
                    <?php for ($i=0; $i < count($_POST['item'])  ; $i++) {

                        if ($login_fabrica == 52) {
                            $join = 'JOIN tbl_tipo_pergunta USING(tipo_pergunta) JOIN tbl_tipo_relacao USING(tipo_relacao)';
                            $campos_tipo_pergunta = ", tbl_tipo_pergunta.descricao as tipo_pergunta_desc ";
                            $where_sigla = "AND sigla_relacao in ('A','D','C') ";
                            $order_by = 'tbl_tipo_pergunta.descricao';
                        }else{
                            $order_by = 'descricao';
                        }
                        $sql ="SELECT   tbl_pergunta.descricao,
                                        tbl_pergunta.pergunta
                                        $campos_tipo_pergunta
                                    FROM tbl_pergunta
                                        $join
                                    WHERE tbl_pergunta.fabrica=$login_fabrica
                                        AND tbl_pergunta.ativo='t'
                                        AND tbl_pergunta.pergunta =".$_POST['item'][$i]."
                                        $where_sigla
                                ORDER BY $order_by";
                        $res = pg_exec ($con,$sql);

                        $txt_pergunta = pg_fetch_result($res, 0, descricao);

                        $cor_item = ($i % 2 )? "#F7F5F0" : "#F1F4FA";
                        ?>
                        <tr class="count_linha" id="<?=$i?>" bgcolor="<?=$cor_item?>">
                            <td align="center">
                                <input type="hidden" value="<?=$_POST['item'][$i]?>" name="item[<?=$i?>]"  /> <?=$txt_pergunta?>
                            </td>
                            <td align="center">
                                <input type="text" value="<?=$_POST['ordem'][$i]?>" name="ordem[<?=$i?>]" class="frm" size="5">
                            </td>
                            <td align="center">
                                <button onclick="deletaitem('<?=$i?>')"><?=traduz('Remover')?></button>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            <?php } ?>
		</table>
	</div><br />
	<table width="700">
		<tr class="subtitulo">
		    <td colspan="100%">
		        &nbsp;
		    </td>
		</tr>
	</table>

    <?php
    if($login_fabrica == 129){
        if ( isset($_GET['pesquisa']) ) {

            $sql = "SELECT  tbl_pergunta.pergunta,
                            tbl_pergunta.descricao,
                            CASE WHEN tbl_pergunta.ativo IS TRUE
                                THEN 'Ativo'
                                ELSE 'Inativo'
                            END AS ativo,
                            tbl_pesquisa_pergunta.ordem,
                            tbl_pesquisa.texto_ajuda,
                            tbl_tipo_pergunta.descricao as tipo_pergunta
                        FROM tbl_pesquisa_pergunta
                            JOIN tbl_pergunta USING(pergunta)
                            JOIN tbl_pesquisa USING(pesquisa)
                            JOIN tbl_tipo_pergunta USING(tipo_pergunta)
                        WHERE pesquisa = $pesquisa
                    ORDER BY    tbl_tipo_pergunta.descricao DESC,
                                tbl_pesquisa_pergunta.ordem DESC";
            $res = pg_query($con,$sql);

            if(pg_num_rows($res) > 0){
                $counT = pg_num_rows($res); ?>
                <div id="" style="width:700px; margin:auto;" class="formulario">
                    <table id="" class="tabela" width="100%" cellspacing="1" align="center">
                        <thead>
                            <tr class="titulo_coluna">
                                <th><?=traduz('Pergunta')?></th>
                                <th><?=traduz('Ordem')?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            for($i = 0; $i < $counT; $i++ ) {
                                $pergunta         = pg_result($res,$i,'pergunta');
                                $descricao        = pg_result($res,$i,'descricao');
                                $ordem            = pg_result($res,$i,'ordem'); ?>

                                <?php
                                echo "<tr>";
                                    echo "<td><input type='hidden' value='".$pergunta."' name='item_at[".$i."]'>".$descricao."</td>";
                                    echo "<td align='center'><input type='text' style='width:25px; text-align:center;' name='ordem_at[".$i."]' value='".$ordem."'></td>";
                                echo "</tr>";
                            } ?>
                        </tbody>
                    </table>
                </div>
                <br />
            <?php
            }
        }
    }
?>


</div>

<p align="center" class="formulario" style="width:700px;margin:auto; text-align:center; padding:5px 0 5px;">
	<input type="submit" value='<?=traduz("Salvar")?>' name="gravar" />
</p>
</form>

<?php

	if (!empty($pesquisa)) {
		$cond = 'AND pesquisa = ' . $pesquisa;
	}

	$sql = "SELECT  pesquisa                                    ,
                    descricao                                   ,
                    nome_completo AS admin                      ,
                    CASE WHEN tbl_pesquisa.ativo IS TRUE
                         THEN 'Ativo'
                         ELSE 'Inativo'
                    END                         AS ativo        ,
                    CASE WHEN tbl_pesquisa.resposta_obrigatoria IS TRUE
                         THEN 'Sim'
                         ELSE 'Não'
                    END                         AS obrigatorio  ,
                    categoria                                   ,
                    texto_ajuda
			FROM    tbl_pesquisa
			JOIN    tbl_admin USING(admin)
			WHERE   tbl_pesquisa.fabrica = $login_fabrica
			$cond
      ORDER BY ativo";

	$res = pg_query($con,$sql);

	if ( pg_numrows($res) > 0 ) {

?>
		<div id="cadastrados">
			<br />
			<table class="tabela" style="min-width:700px;margin:auto;" cellspacing="1" id="pesquisas_cad">
				<thead>
					<tr class="titulo_tabela">
						<th colspan="6"><?=traduz('Pesquisas Cadastradas')?></th>
					</tr>
					<tr class="titulo_coluna">
						<th><?=traduz('Pesquisa')?></th>
						<th><?=traduz('Responsável')?></th>
						<th><?=traduz('Ativo')?></th>
						<th>Obrigatório</th>
						<?php if(!in_array($login_fabrica, array(169,170))){ ?>
                        <th><?=traduz('Local da Pesquisa')?></th>
                        <?php } ?>
						<th><?=traduz('Ações')?></th>
					</tr>
				</thead>
				<tbody>
					<?php
						for ($i=0; $i<pg_numrows($res); $i++) {
                            $x_ativo        = pg_result($res,$i,'ativo');
                            $x_obrigatorio  = pg_result($res,$i,'obrigatorio');
                            $img_src        = ($x_ativo == 'Ativo') ? "imagens/icone_ok.gif" : "imagens/icone_deletar.png";
                            $img_src_obg    = ($x_obrigatorio == 'Sim') ? "imagens/icone_ok.gif" : "imagens/icone_deletar.png";

							$id = pg_result($res,$i,'pesquisa');
							$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";
							$texto_ajuda = pg_result($res,$i,'texto_ajuda');

							$categoria = pg_fetch_result($res, $i, "categoria");

                            if ($login_fabrica == 129) {
                                switch ($categoria) {
                                case "callcenter":
                                    $categoria_desc = "Callcenter";
                                    break;

                                case "externo":
                                    $categoria_desc = "Callcenter - E-mail";
                                    break;

                                case "posto":
                                    $categoria_desc = "Posto Autorizado (Login)";
                                    break;

                                case "ordem_de_servico_email":
                                    $categoria_desc = "Ordem de Serviço - E-mail";
                                    break;

                                case "ordem_de_servico":
                                    $categoria_desc = "Ordem de Serviço";
                                    break;
                                }
                            } else {
                                switch ($categoria) {
                                    case "callcenter":
                                        $categoria_desc = "Callcenter";
                                        break;

                                    case "externo":
                                        $categoria_desc = "E-mail Call-Center";
                                        break;

                                    case "posto":
                                        $categoria_desc = "Posto Autorizado (Login)";
                                        break;

                                    case "posto_2":
                                        $categoria_desc = "posto_2";
                                        break;

                                    case "ordem_de_servico":
                                        $categoria_desc = "Ordem de Serviço";
                                        break;
                                    case "ordem_de_servico_email":
                                        $categoria_desc = "Ordem de Serviço - Email";
                                        break;
                                    case "externo_outros":
                                        $categoria_desc = "Email Consumidor - Pós Venda";
                                        break;

                                }
                            }
							echo '<tr bgcolor="'.$cor.'"  class="pesquisa_ajax" id="'.$id.'">
								 	<td align="left" nowrap><img src="imagens/mais.gif" />&nbsp;'.pg_result($res,$i,'descricao').'</td>
								 	<td align="left">&nbsp;'.pg_result($res,$i,'admin').'</td>
								 	<td><img src='.$img_src.' alt=""></td>
								 	<td><img src='.$img_src_obg.' alt=""></td>';
                                    if(!in_array($login_fabrica, array(169,170))){
                                        echo '<td>'.$categoria_desc.'</td>';
                                    }
								 	echo '<td>
										<button onclick="editaPesquisa('.$id.')">'.traduz("Editar").'</button>&nbsp; ';

							$sql = "SELECT * from tbl_resposta WHERE pesquisa = ".$id;
							$resPesquisaResposta  = pg_query($con,$sql);
							if (pg_num_rows($resPesquisaResposta)==0) {

								echo '<button onclick="deletaintegridade('.$id.')">'.traduz("Remover").'</button>';
							}
							echo '
									</td>
								 </tr>';

                                 $colspan = ($login_fabrica == 30) ? 6 : 5;

								echo '
								 <tr   id="'.$id.'-2" style="display:none">
								 	<td colspan="'.$colspan.'" nowrap>
								 		<p style="text-align:center">
								 			'.nl2br($texto_ajuda).'
								 		</p>
								 	</td>
								 </tr>
								 ';
						}
					?>
				</tbody>
			</table>

		</div>
<?php } ?>

<script type="text/javascript">



	$().ready(function(){

		$("form").submit(function(e){
			erro 		= false
			descricao 	= $("input[name=descricao]").val();
			ativo		= $("#ativo").val();
			categoria	= $("#categoria").val();

			if ( jQuery.trim(descricao).length === 0 ) {
				alert('<?=traduz("Digite a Descrição da Pesquisa")?>');
				erro = true;
			}
			else if ( jQuery.trim(ativo).length === 0 ) {
				alert('<?=traduz("Escolha ativo ou inativo")?>');
				erro = true;
			}
			<?php if (!in_array($login_fabrica, array(52,169,170))){ ?>
				else if ( jQuery.trim(categoria).length === 0){

					alert('<?=traduz("Escolha um local para a pesquisa")?>');
					erro = true;

				}
			<?php } ?>

			if ( erro === true ) {
				e.preventDefault();
				return false;
			}

			return true;

		});



	});

</script>

<?php include 'rodape.php'; ?>
