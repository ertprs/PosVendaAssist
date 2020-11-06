<?php
	include "dbconfig.php";
	include "includes/dbconnect-inc.php";
	include 'autentica_usuario.php';
  include "funcoes.php";
  
  $title = "Cadastro de T�cnico";

  function validDate($data = null){
    if($data == null)
      return false;
    
    $data   = array_reverse(explode("/", $data));     

    if(!checkdate($data[1], $data[2], $data[0]))
      return false;
    
    return implode("-",$data);
  }

  function validaEmail($email = null){
        if($email == null)
            return false;
      
        if (!preg_match("/^[a-z0-9_\.\-]+@[a-z0-9_\.\-]*[a-z0-9_\-]+\.[a-z]{2,4}$/", $email)) {
            return false;
        }

        return true;
  }

  $formacoes = array(
      "ENSINO FUNDAMENTAL" => array(
        'ENSINO FUNDAMENTAL',
        'AUX. T�CNICO EM ELETRICIDADE',
        'AUX. T�CNICO EM ELETROELETR�NICA',
        'AUX. T�CNICO EM ELETROMEC�NICA',
        'AUX. T�CNICO EM ELETR�NICA',
        'AUX. T�CNICO EM ELETROT�CNICA',
        'AUX. T�CNICO EM INFORM�TICA - MANUTEN��O DE COMPUTADORES',
        'AUX. T�CNICO EM INFORM�TICA - MANUTEN��O DE REDES',
        'AUX. T�CNICO EM INFORM�TICA - SISTEMAS DE INFORMA��O',
        'AUX. T�CNICO EM INSTALA��ES EL�TRICAS',
        'AUX. T�CNICO EM MEC�NICA',
        'AUX. T�CNICO EM SISTEMAS DE AUTOMA��O INDUSTRIAL',
        'AUX. T�CNICO EM TELECOMUNICA��ES',
        'AUX. T�CNICO EM OUTRAS �REAS'
      ),
      "ENSINO M�DIO" => array(
        'ENSINO M�DIO',
        'T�CNICO EM ACIONAMENTOS ELETR�NICOS',
        'T�CNICO EM AUTOMA��O INDUSTRIAL',
        'T�CNICO EM AUTOMOBILISTICA',
        'T�CNICO EM EL�TRICA',
        'T�CNICO EM ELETRICISTA DE MANUTEN��O',
        'T�CNICO EM ELETROELETR�NICA',
        'T�CNICO EM ELETROMEC�NICA',
        'T�CNICO EM ELETR�NICA',
        'T�CNICO EM ELETROT�CNICA',
        'T�CNICO EM INFORM�TICA - DESENVOLVIMENTO DE SISTEMA',
        'T�CNICO EM INFORM�TICA - HABILITA��O EM MANUTEN��O DE MICRO',
        'T�CNICO EM INFORM�TICA COM HABILITA��O EM REDES',
        'T�CNICO EM MANUTEN��O DE COMPUTADORES',
        'T�CNICO EM MANUTEN��O ELETROELETR�NICA',
        'T�CNICO EM MANUTEN��O INDUSTRIAL',
        'T�CNICO EM MANUTEN��O MEC�NICA',
        'T�CNICO EM MECANICA',
        'T�CNICO EM MECATR�NICA',
        'T�CNICO EM REFRIGERA��O',
        'T�CNICO EM TELECOMUNICA��ES',
        'T�CNICO EM DEMAIS �REAS'
      ),
      "ENSINO SUPERIOR" => array(
        'BACH. EM ANALISE DE SISTEMAS',
        'BACH. EM CI�NCIAS DA COMPUTA��O',
        'BACH. EM ENGENHARIA DE INFORM�TICA',
        'BACH. EM INFORM�TICA',
        'BACH. EM PROCESSAMENTO DE DADOS',
        'BACH. EM SISTEMAS DE INFORMA��O',
        'ENG. DE SISTEMAS EL�TRICOS INDUSTRIAIS',
        'ENGENHARIA DE AUTOMA��O',
        'ENGENHARIA DE COMPUTA��O',
        'ENGENHARIA DE CONTROLE E AUTOMA��O INDUSTRIAL - MECATR�NICA',
        'ENGENHARIA DE PRODU��O E SISTEMAS',
        'ENGENHARIA DE PRODU��O EL�TRICA',
        'ENGENHARIA DE PRODU��O MEC�NICA',
        'ENGENHARIA DE TELECOMUNICA��ES',
        'ENGENHARIA EL�TRICA',
        'ENGENHARIA EL�TRICA COM ENFASE EM ELETR�NICA',
        'ENGENHARIA ELETRICA-HABILITA��O EM TELECOMUNICACOES',
        'ENGENHARIA ELETR�NICA',
        'ENGENHARIA ELETROT�CNICA',
        'ENGENHARIA INDUSTRIAL EL�TRICA',
        'ENGENHARIA MEC�NICA COM �NFASE EM MECATR�NICA',
        'ENGENHARIA MEC�NICA COM HABILITA��O EM AUTOMA��O E CONTROLE',
        'ENGENHARIA MECATR�NICA',
        'TECNOLOGIA DA INFORMA��O',
        'TECNOLOGIA EM AN�LISE DE SISTEMAS INFORMATIZADOS',
        'TECNOLOGIA EM AUTOMA��O E CONTROLE INDUSTRIAL',
        'TECNOLOGIA EM AUTOMA��O E ROB�TICA',
        'TECNOLOGIA EM BANCO DE DADOS',
        'TECNOLOGIA EM EL�TRICA',
        'TECNOLOGIA EM ELETROELETR�NICA',
        'TECNOLOGIA EM ELETROMEC�NICA',
        'TECNOLOGIA EM ELETR�NICA',
        'TECNOLOGIA EM GEST�O DA TECNOLOGIA DA INFORMA��O',
        'TECNOLOGIA EM INFORM�TICA',
        'TECNOLOGIA EM MANUTEN��O INDUSTRIAL',
        'TECNOLOGIA EM MEC�NICA',
        'TECNOLOGIA EM MECATR�NICA',
        'TECNOLOGIA EM OPERA��O E MANUTEN��O MECATR�NICA INDUSTRIAL',
        'TECNOLOGIA EM PROCESSAMENTO DE DADOS',
        'TECNOLOGIA EM REDES DE COMPUTADORES',
        'TECNOLOGIA EM SISTEMAS DE INFORMA��O',
        'TECNOLOGIA EM SISTEMAS DIGITAIS',
        'SUPERIOR EM DEMAIS �REAS'
      ),
    );

  if(@$_POST['ajax'] == 'ajax'){
    $tecnico   = @$_POST['tecnico'];
    $categoria = utf8_decode(@$_POST['categoria']);
    $formacao  = utf8_decode(@$_POST['formacao']);

    if(!empty($tecnico)){
      //verifica se j� tem em alguma OS o t�cnico

      $sql = "SELECT os FROM tbl_os WHERE fabrica = {$login_fabrica} AND posto = {$login_posto} AND tecnico = {$tecnico} LIMIT 1";
      $res = pg_query($con, $sql);
      if(pg_num_rows($res) > 0){
        echo "1";
      }else{
        $sql = "DELETE FROM tbl_tecnico WHERE tecnico = {$tecnico}";
        if(pg_query($con, $sql))
          echo "0";
        else
          echo "2";
      }
    }

    if(!empty($categoria)){
      $formacoes = $formacoes[$categoria];

      echo "<option value=''>Selecione</option>";
      foreach ($formacoes as $formacao) {
        echo "<option value='{$formacao}' title='{$formacao}'>{$formacao}</option>";
      }
    }

    if(!empty($formacao)){
      foreach (array_keys($formacoes) AS $value) {
        foreach ($formacoes[$value] AS $key) {
          if($key == $formacao){
            echo $value."|".$formacao;
          }
        }
      }
    }

    exit;
  }

  include "cabecalho.php";

  if(!empty($_POST['gravar'])){
    $tecnico        = $_POST['tecnico'];
    $nome           = trim($_POST['nome']);
    $cpf            = preg_replace("/[-.]/", "", trim($_POST['cpf']));
    $telefone       = trim($_POST['telefone']);
    $ramal          = trim($_POST['ramal']);
    $email          = trim($_POST['email']);
    $data_admissao  = trim($_POST['data_admissao']);
    $formacao       = trim($_POST['formacao']);
    $data_conclusao = trim($_POST['data_conclusao']);
    $status         = trim($_POST['status']);
    $linhas         = (Array) $_POST['linhas'];
    $linha_atende   = "null";

    if(empty($nome)){
        $msg_erro = "Nome inv�lido!";
    }

    if(empty($cpf) AND empty($msg_erro))
        $msg_erro = "CPF inv�lido!";
    elseif(empty($msg_erro)){
		$valida_cpf_cnpj = verificaCpfCnpj(preg_replace("/\D/","",$cpf));

		if(empty($valida_cpf_cnpj)){
			$res = pg_query($con,"SELECT fn_valida_cnpj_cpf('$cpf')");
			if ($res === false) {
				$msg_erro = "CPF inv�lido!";
			}
		}else{
			$msg_erro = $valida_cpf_cnpj;
		}
    }

    if(empty($formacao) AND empty($msg_erro)){
        $msg_erro = "Forma��o inv�lida!";
    }

    if(empty($msg_erro) AND !validaEmail($email))
        $msg_erro = "Email inv�lido!";

    if(empty($data_admissao) AND empty($msg_erro))
        $msg_erro = "Data admiss�o inv�lida!";
    elseif(empty($msg_erro)){
        $x_data_admissao = validDate($data_admissao);
        if (!$x_data_admissao) {
            $msg_erro = "Data admiss�o inv�lida!";
        }
    }

    if(empty($telefone) AND empty($msg_erro)){
        $msg_erro = "Telefone inv�lido!";
    }

    if(empty($data_conclusao) AND empty($msg_erro))
        $msg_erro = "Data conclus�o inv�lida!";
    elseif(empty($msg_erro)){
        $x_data_conclusao = validDate($data_conclusao);
        if (!$x_data_conclusao) {
            $msg_erro = "Data conclus�o inv�lida!";
        }
    } 

    if(empty($msg_erro) AND count($linhas) == 0){
        $msg_erro = "Selecione as linhas que o t�cnico '$nome' atende!";
    }elseif(empty($msg_erro)){
        $linha_atende = Array();

        foreach ($linhas as $key => $value) {
            $sql = "SELECT 
                      tbl_linha.linha
                    FROM tbl_posto_linha 
                        JOIN tbl_linha ON tbl_linha.linha = tbl_posto_linha.linha AND tbl_linha.fabrica = {$login_fabrica} 
                    WHERE posto = {$login_posto} 
                      AND tbl_linha.ativo 
                      /*AND tbl_posto_linha.ativo */
                      AND tbl_linha.linha = {$value}
                    ORDER BY tbl_linha.nome;";
            $res = pg_query($con, $sql);

            if(pg_num_rows($res)){
                $linha_atende[] = pg_fetch_result($res, 0, 'linha'); 
            }
        }

        $linha_atende = "{".implode(",", $linha_atende)."}";
    }


    if(empty($msg_erro)){

        //verifica se o tecnico realmente existe
        $sql = "SELECT tecnico FROM tbl_tecnico WHERE fabrica = {$login_fabrica} AND posto = {$login_posto} AND tecnico = {$tecnico};";
        $res = pg_query($con, $sql);
        if(pg_num_rows($res) == 0)
          $tecnico = null;

        if(empty($tecnico)){
            $sql = "INSERT INTO tbl_tecnico
                        (
                            posto,
                            fabrica,
                            nome,
                            ativo,
                            cpf,
                            data_admissao,
                            formacao,
                            data_conclusao,
                            email,
                            telefone,
                            ramal,
                            linhas
                        )
                    VALUES
                        (
                            $login_posto,
                            $login_fabrica,
                            '$nome',
                            '$status',
                            $cpf,
                            '$x_data_admissao',
                            '$formacao',
                            '$x_data_conclusao',
                            '$email',
                            '$telefone',
                            '$ramal',
                            '$linha_atende'
                        );";
        }else{
            echo $sql = "UPDATE tbl_tecnico SET 
                        nome            = '{$nome}',
                        ativo           = '{$status}',
                        cpf             = '{$cpf}',
                        data_admissao   = '{$x_data_admissao}',
                        formacao        = '{$formacao}',
                        data_conclusao  = '{$x_data_conclusao}',
                        email           = '{$email}',
                        telefone        = '{$telefone}',
                        ramal           = '{$ramal}',
                        linhas          = '$linha_atende'
                    WHERE tecnico = {$tecnico}";
        }
        $res = pg_query($con, $sql);
        $msg_erro = pg_last_error();

        if(empty($msg_erro))
            header ("Location: {$_SERVER['PHP_SELF']}");
        else
            $msg_erro = "Erro ao gravar dados! <erro>".pg_last_error($con)."</erro>";
    }

}


?>
<style> 
    a {
        text-decoration: none;
        color: #000000;
    }

    a:hover {
        text-decoration: underline;
    }

    .titulo_coluna{
           background-color:#596d9b;
           font: bold 11px "Arial";
           color:#FFFFFF;
           text-align:center;
    }

	 .titulo_tabela{
           background-color:#596d9b;
           font: bold 14px "Arial";
           color:#FFFFFF;
           text-align:center;
    }

    table.tabela tr td{
           font-family: verdana;
           font-size: 11px;
           border-collapse: collapse;
           border:1px solid #596d9b;
    }
    .formulario{
           background-color:#D9E2EF;
           font:11px Arial;
           text-align:left;
    }

    .formulario td{
      font-weight: bold;
    }

    .msg_erro {
        background: #FF0000;
        color: #FFFFFF;
        font: bold 16px "Arial";
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

    .nao_disponivel {
       font: 14px Arial; color: rgb(200, 109, 89);
       background-color: #ffddff;
       border:1px solid #DD4466;
    }

    .espaco{
	     padding:0 0 0 150px;
    }

    input[type="text"] {
      font-weight: normal !important;
    }

    #linhas{
      width: 660px;
      margin: 0 auto;
    }

    #linhas ul, #linhas ul li{
      list-style: none;
      padding: 0;
      margin: 0;
    }

     #linhas ul li{
       float: left;
       width: 219px;
     }

     erro{
       display: none;
     }

</style>
<?php 


    if(!empty($_GET["tecnico"])){
        $tecnico = $_GET["tecnico"];

        $sql = "SELECT * FROM tbl_tecnico WHERE tecnico = {$tecnico} AND posto = {$login_posto} AND fabrica = {$login_fabrica}";
        $res = pg_query($con, $sql);

        if(pg_num_rows($res)){
            extract(pg_fetch_array($res));

            $linhas     = str_replace('{', '', $linhas);
            $linhas     = str_replace('}', '', $linhas);

            $linhas = explode(",", $linhas);

            $data_admissao   = implode("/",array_reverse(explode("-", $data_admissao)));
            $data_conclusao  = implode("/",array_reverse(explode("-", $data_conclusao)));

            $status = ($ativo == 't') ? "true" : "false";
        }
    }?>
    <link type="text/css" href="plugins/jqueryUI/css/redmond/jquery-ui-1.8.17.custom.css" rel="stylesheet" />  
    <script type="text/javascript" src="plugins/jqueryUI/js/jquery-1.7.1.min.js"></script>
    <script type="text/javascript" src="plugins/jqueryUI/js/jquery-ui-1.8.17.custom.min.js"></script>
    <script src="js/jquery.maskedinput-1.3.min.js" type="text/javascript"></script>
  	<script type="text/javascript">
          $(function() {
              $(".date").datepicker({ 
                  dateFormat: 'dd/mm/yy',
                  maxDate: '0d',
                  autoSize: false
              });

              $(".date").mask("99/99/9999");
              $(".cpf").mask("999.999.999-99");
              $(".fone").mask("(99) 9999-9999");
          });

          $(document).ready(function(){
            $('.apagarTecnico').click(function() {
              var id = $(this).attr('rel');
              $(this).attr("disabled", true);

              if(id.length > 0){
                var pergunta = confirm("Deseja realmente apagar este registro?")
                if (pergunta){
                  $.ajax({
                    type: "POST",
                    url: "<?php echo $_SERVER['PHP_SELF'];?>",
                    data: "ajax=ajax&tecnico="+id,
                    success: function(retorno){
                      if(retorno == 1){
                        alert("Erro ao apagar registro!\n\nO t�cnico possui cadastro em OS!");
                      }  

                      if(retorno == 2){
                        alert("Erro ao apagar registro!\n\nTente novamente mais tarde!");
                      }  

                      if(retorno == 0){
                        $("#"+id).fadeOut(1000);
                      }
                    }
                  }); 
                }
              }

              $(this).attr("disabled", false);
            });

          });
  	</script>

  <br />				
	<form name="frm_peca" method="post" action="<?=$_SERVER['PHP_SELF']?>" >
    <?php
      if(!empty($msg_erro))
        echo "<div class='msg_erro' style='width:700px'>{$msg_erro}</div>";
    ?>
    <input type='hidden' name='tecnico' value='<?php echo $tecnico?>' /> 
		<table cellpadding="3" cellspacing="1" width="700px" border="0" class="formulario" align="center">
				<tr class="titulo_tabela">
					<th colspan="8">Cadastro de T�cnico</th>
				</tr>
			<tr>
				<td width='*'>&nbsp;</td>
        <td width='100px'>&nbsp;</td>
				<td width='100px'>&nbsp;</td>
				<td width='100px'>&nbsp;</td>
				<td width='100px'>&nbsp;</td>
				<td width='100px'>&nbsp;</td>
				<td width='100px'>&nbsp;</td>
			</tr>
			<tr>
        <td>&nbsp;</td>
				<td colspan='2'>
					Nome Completo<br/>
					<input type="text" name="nome" value="<?php echo $nome?>" maxlength="100" style="width: 209px"/>
				</td>
        <td colspan='2'>
          Email<br/>
          <input type="text" name="email" maxlength="50" value="<?php echo $email?>" style="width: 200px"/>
        </td>
        <td colspan='2'>
          CPF<br/>
          <input type="text" name="cpf" class='cpf' value="<?php echo $cpf?>" maxlength="14" style="width: 180px" />
        </td>
        
      </tr>
      <tr>
        <td>&nbsp;</td>
        <td>
          Telefone<br/>
          <input type="text" name="telefone" class='fone' maxlength="20" value="<?php echo $telefone?>" style="width: 95px" />
        </td>
        <td>
          Ramal<br/>
          <input type="text" name="ramal" maxlength="10" value="<?php echo $ramal?>" style="width: 95px"  />
        </td>
        <td>
          Data Admiss�o<br/>
          <input type="text" name="data_admissao" maxlength="10" value="<?php echo $data_admissao?>" class='date' style="width: 95px" />
        </td>
        <td colspan='3'>
          Categoria Forma��o<br/>
          <select name='categoria_formacao' id='categoria_formacao' class='frm' style="width: 290px">
            <option value="" selected = 'selected'> - selecione -</option>
            <?php
                foreach (array_keys($formacoes) as $cat_formacao) {
                  echo "<option value='{$cat_formacao}'>{$cat_formacao}</option>";
                }
            ?>
          </select>
        </td>
      </tr>
      <tr>
        <td>&nbsp;</td>
        <td colspan='3'>
          Forma��o<br/>
          <input type='hidden' name='formacao_anterior' id='formacao_anterior' value='<?php echo $formacao;?>' />
          <select name='formacao' id='formacao' class='frm' style="width: 318px">
            <option value="" selected = 'selected'> - selecione uma categoria - </option>
          </select>
        </td>
        <td>
          Data Conclus�o<br/>
          <input type="text" name="data_conclusao" maxlength="10" value="<?php echo $data_conclusao?>" class='date'style="width: 95px"/>
        </td>
        <td colspan='2'>
          Status<br/>
          <select name='status' class='frm' style="width: 178px">
            <?php
                if(empty($status))
                    $status = "true";
            ?>
            <option value="true" <?php if($status == 'true') echo " selected = 'selected' "?>>Ativo</option>
            <option value="false" <?php if($status == 'false') echo " selected = 'selected' "?>>Inativo</option>
          </select>
        </td>
      </tr>
      <tr>
        <td>&nbsp;</td>
        <td colspan='6'>
          Linhas que o t�cnico atende:<br/>
          <?php 
            $sql = "SELECT 
                      tbl_linha.linha, 
                      tbl_linha.nome 
                    FROM tbl_posto_linha 
                        JOIN tbl_linha ON tbl_linha.linha = tbl_posto_linha.linha AND tbl_linha.fabrica = {$login_fabrica} 
                    WHERE posto = {$login_posto} 
                      AND tbl_linha.ativo 
                      /*AND tbl_posto_linha.ativo*/ 
                    ORDER BY tbl_linha.nome;";
            /*
            $sql = "
                    SELECT DISTINCT
                      tbl_linha.linha, 
                      tbl_linha.nome 
                    FROM tbl_linha 
                    WHERE tbl_linha.ativo 
                      AND fabrica = {$login_fabrica}
                    ORDER BY tbl_linha.nome;";
            */
            $res = pg_query($con, $sql);

            echo "<div id='linhas'>";
              if(pg_num_rows($res)){
                echo "<ul>";
                  for ($i=0; $i < pg_num_rows($res); $i++) { 
                    $linha = pg_fetch_result($res, $i, 'linha');
                    $nome = pg_fetch_result($res, $i, 'nome');
                    
                    $selected =  in_array($linha, $linhas) ? " checked = 'checked' " : null;

                    echo "<li>
                            <input type='checkbox' name='linhas[]' value='{$linha}' {$selected} id='{$linha}' /> <label for='{$linha}'>{$nome}</label>
                          </li>";
                  }

                echo "</ul>";
              }
              echo "<div style='clear: both;'>&nbsp;</div>";
            echo "</div>";
           ?>
        </td>
      </tr>
      <tr>
        <td colspan='10' style='padding: 15px; text-align: center'>
          <input type="submit" name="gravar" value=' Gravar '  />
          <?php if(!empty($tecnico)){?>
            <input type="button" name="novo" value=' Novo Registro ' onclick='javascript: window.location="<?php echo $_SERVER['PHP_SELF']; ?>"'  />
          <?php }?>
        </td>
      </tr>
	</table>
	</form>


    <?php 
        $sql = "SELECT tecnico, nome, email, telefone, ramal, ativo 
                FROM tbl_tecnico 
                WHERE posto = {$login_posto} 
                    AND fabrica = {$login_fabrica}
                ORDER BY tecnico DESC;";
        $res = pg_query($con, $sql);

        if(pg_num_rows($res)){
            echo "<br><table align='center' width='700px' border='0' cellpadding='0' cellspacing='1' class='tabela' >";

            echo "<tr class='titulo_coluna'>";
                echo "<td colspan='6'>T�cnico cadastrado</td>";
            echo "</tr>"; 

            echo "<tr class='titulo_coluna'>";
                echo "<td>Nome</td>";
                echo "<td>Email</td>";
                echo "<td>Telefone</td>";
                echo "<td>Ramal</td>";
                echo "<td>Status</td>";
                echo "<td>A��o</td>";
            echo "</tr>"; 

            for ($i=0; $i < pg_num_rows($res); $i++) { 
                extract(pg_fetch_array($res));
                $ativo = ($ativo == 't') ? "Ativo" : "Inativo";

                $cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";
                echo "<tr bgcolor='$cor' id='{$tecnico}'>";
                    echo "<td><a href='{$_SERVER['PHP_SELF']}?tecnico={$tecnico}'>&nbsp;{$nome}</a></td>";
                    echo "<td>&nbsp;{$email}</td>";
                    echo "<td align='center'>&nbsp;{$telefone}</td>";
                    echo "<td>&nbsp;{$ramal}</td>";
                    echo "<td>&nbsp;{$ativo}</td>";
                    echo "<td style='text-align: center'>&nbsp;<input type='button' value=' Apagar ' rel='{$tecnico}' class='apagarTecnico' />&nbsp;</td>";
                echo "</tr>";
            }

            echo "</table>";
        } 
?>
<script type="text/javascript">
  $(document).ready(function(){
    $("#categoria_formacao").change(function(){
      atualizaFormacao();
    });

    $("#formacao").change(function(){
      $("#formacao_anterior").val($(this).val());
    });

    function atualizaFormacao(){
      var categoria = $("#categoria_formacao").val();

      if(categoria.length){
        $.ajax({
          type: "POST",
          url: "<?php echo $_SERVER['PHP_SELF'];?>",
          data: "ajax=ajax&categoria="+categoria,
          success: function(retorno){
            $("#formacao").html(retorno);

            var formacao = $("#formacao_anterior").val();
            if(formacao.length > 0){
              $("#formacao").val(formacao);
            }
          }
        });  
      }
    }

    function buscaCategoriaFormacao(formacao){
      $.ajax({
        type: "POST",
        url: "<?php echo $_SERVER['PHP_SELF'];?>",
        data: "ajax=ajax&formacao="+formacao,
        success: function(retorno){
          retorno = retorno.split("|");
          $("#categoria_formacao").val(retorno[0]);
          atualizaFormacao();
        }
      });
    }

    <?php if(strlen($formacao) > 0){?>
      buscaCategoriaFormacao("<?php echo $formacao; ?>");
    <?php }?>
  });
</script>
<?php include "rodape.php";?>
