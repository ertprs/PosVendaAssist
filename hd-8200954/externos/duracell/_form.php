<!DOCTYPE html>
<html lang="en">
<head>
  <meta>
  <title>Duracell Carregadores</title>
  <meta name="description" content="Duracell Carregadores" />
  <meta name="keywords" content="duracell carregadores" />
  <meta name="robots" content="index,follow" />
  <meta name="Googlebot" content="index,follow" />
  <meta name="geo.region" content="BR-SP" />
  <meta name="geo.placename" content="São Paulo" />
  <link rel="stylesheet" href="images/font-awesome/css/font-awesome.min.css">
  <link rel="shortcut icon" type="image/x-icon" href="images/favicon.png">
  <link rel="apple-touch-icon" href="images/apple-touch-icon.png" />
  <link href='http://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,600' rel='stylesheet' type='text/css'>
  <link rel="stylesheet" href="style.css">
  <script src="js/jquery.js"></script>

</head>

<body>

<?php

$array_estado = array(
  'AC'=>'AC - Acre',      'AL'=>'AL - Alagoas', 'AM'=>'AM - Amazonas',      'AP'=>'AP - Amapá',
  'BA'=>'BA - Bahia',     'CE'=>'CE - Ceará',   'DF'=>'DF - Distrito Federal',  'ES'=>'ES - Espírito Santo',
  'GO'=>'GO - Goiás',     'MA'=>'MA - Maranhão',  'MG'=>'MG - Minas Gerais',    'MS'=>'MS - Mato Grosso do Sul',
  'MT'=>'MT - Mato Grosso', 'PA'=>'PA - Pará',    'PB'=>'PB - Paraíba',     'PE'=>'PE - Pernambuco',
  'PI'=>'PI - Piauí',     'PR'=>'PR - Paraná',  'RJ'=>'RJ - Rio de Janeiro',  'RN'=>'RN - Rio Grande do Norte',
  'RO'=>'RO - Rondônia',    'RR'=>'RR - Roraima', 'RS'=>'RS - Rio Grande do Sul', 'SC'=>'SC - Santa Catarina',
  'SE'=>'SE - Sergipe',   'SP'=>'SP - São Paulo', 'TO'=>'TO - Tocantins'
);



?>

<script language="JavaScript" src="js/jquery.validate.js" type="text/javascript"></script>
<!-- <script type="text/javascript" src="js/mascara.js"></script> -->
<script type="text/javascript" src="js/jquery.autocomplete.min.js"></script>
<script type="text/javascript" src="js/jquery.mask.js"></script>

<script type="text/javascript">jQuery.noConflict.js</script>
<script type="text/javascript">
  jQuery('#auto\\:form').focusout(function() {
    var input = jQuery('#auto\\:form').val();
    var input = input.replace(/\./g, "");
    var input = input.replace(/\-/g, "");
  });
</script>

<script language="javascript" type="text/javascript">

jQuery(document).ready( function() {
  jQuery("#formularioContato").validate({
    rules:{
      nome:{required: true, minlength: 4 },
      email:{required: true, email: true },
      telefone:{required: true, }
    },
    messages:{
      nome:{required: "Por favor, digite o seu nome.", minLength: "O seu nome deve conter, no mínimo, 4 caracteres."},
      email:{required: "Por favor, digite seu e-mail para contato.", email: "Por favor, digite um e-mail válido."},
      telefone:{required: "Por favor, digite um telefone para contato."},
      assunto:{required: "Por favor, selecione o assunto."}
      }
  });

  var phoneMask = function(){
    if($(this).val().match(/^\(0/)){
      $(this).val('(');
      return;
    }
    if($(this).val().match(/^\([1-9][0-9]\) *[0-8]/)){
      $(this).mask('(00) 0000-0000');
    }
    else{
      $(this).mask('(00) 00000-0000');
    }
    $(this).keyup(phoneMask);
  };
  $('.telefone').keyup(phoneMask);

  $("#estado").change(function () {
    if ($(this).val().length > 0) {
      buscaCidade($(this).val());
    } else {
      $("#cidade > option[rel!=default]").remove();
    }
  });

  buscaProduto($(this).val());

  $("#produto").click(function(){
    buscaDefeito($(this).val());
  });

  $('#cep').change(function(){
    if( $(this).val() == '' ) return true; // Não faz nada se o usuário não teclou nada.
    var end      = new Object;
    end.endereco = $('#endereco');
    end.bairro   = $('#bairro');
    end.cidade   = $('#cidade');
    end.estado   = $('#estado');
    end.numero   = $('#numero');

    var cep = $(this).val().replace(/\D/, '');

    if( cep.length == 8 ){
      $.get('../callcenter/ajax_cep.php',
        { 'ajax': 'cep', 'cep': cep },
        function (data)
        {

          if( data=='ko' )
          {
            end.endereco.focus();
            return true;
          }

          if( data.indexOf(';') >= 0 )
          {
            r = data.split(';');


            var text = r[3];

            text = text.replace(new RegExp('[ÁÀÂÃ]','gi'), 'A');
            text = text.replace(new RegExp('[ÉÈÊ]','gi'), 'E');
            text = text.replace(new RegExp('[ÍÌÎ]','gi'), 'I');
            text = text.replace(new RegExp('[ÓÒÔÕ]','gi'), 'O');
            text = text.replace(new RegExp('[ÚÙÛ]','gi'), 'U');
            text = text.replace(new RegExp('[Ç]','gi'), 'C');

            text = text.toUpperCase();

            r[3] = text;

            end.endereco.val(r[1]);
            end.bairro.val(r[2]);
            end.estado.val(r[4]);
            end.numero.val(r[5]).focus();
            buscaCidade(r[4], r[3]);

          }
        }
      );
    }else{
      //alert('CEP inválido');
    }
  });

  $('#cep').keypress(function(event) {
      var tecla = (window.event) ? event.keyCode : event.which;
      if ((tecla > 47 && tecla < 58)) return true;
      else {
          if (tecla != 8) return false;
          else return true;
      }
  });

  // $("input, select").each(function() {
  //   $(this).removeClass('required');
  // });

});

function buscaProduto (){
  $.ajax({
    async: false,
    // url: "http://ww2.telecontrol.com.br/externos/duracell/contact-validation.php",
    url: "contact-validation.php",
    type: "POST",
    data: { buscaProduto: true},
    cache: false,
    complete: function (data) {
      data = $.parseJSON(data.responseText);

      if(data.produtos){
        //$("#produto > option[rel!=default]").remove();
        var produtos = data.produtos;
        $.each(produtos, function (key, value) {
          var option = $("<option></option>");
          $(option).attr({ value: value.produto });
          $(option).text(value.descricao);

          if (produto != undefined && value.produto == produto) {
            $(option).attr({ selected: "selected" });
          }

          $("#produto").append(option);
        });
      } else {
        $("#produto > option[rel!=default]").remove();
      }
    }
  });
}

function buscaCidade (estado, cidade) {

  $.ajax({
    async: false,
    // url: "http://ww2.telecontrol.com.br/externos/duracell/contact-validation.php",
    url: "contact-validation.php",
    type: "POST",
    data: { buscaCidade: true, estado: estado },
    cache: false,
    complete: function (data) {
      data = $.parseJSON(data.responseText);
      if (data.cidades) {
        $("#cidade > option[rel!=default]").remove();

        var cidades = data.cidades;

        $.each(cidades, function (key, value) {
          var option = $("<option></option>");
          $(option).attr({ value: value.cidade_pesquisa });
          $(option).text(value.cidade);

          if (cidade != undefined && value.cidade.toUpperCase() == cidade.toUpperCase()) {
            $(option).attr({ selected: "selected" });
          }

          $("#cidade").append(option);
        });
      } else {
        $("#cidade > option[rel!=default]").remove();
      }
    }
  });
}

function buscaDefeito(produto, defeito_reclamado){
  produto = $("#produto option:selected" ).val();

  $.ajax({
    async: false,
    // url: "http://ww2.telecontrol.com.br/externos/duracell/contact-validation.php",
    url: "contact-validation.php",
    type: "POST",
    data: { buscaDefeito: true, produto: produto },
    cache: false,
    complete: function (data) {
      data = $.parseJSON(data.responseText);

      if (data.defeitos) {

        var defeitos =  data.defeitos;
        $("#defeito").html("");

        $.each(defeitos, function (key, value) {
          var option = $("<option></option>");
          $(option).attr({ value: value.defeito_reclamado });
          $(option).text(value.descricao_defeito);
          if (defeito_reclamado != undefined && value.defeito_reclamado == defeito_reclamado) {
            $(option).attr({ selected: "selected" });
          }
          $("#defeito").append(option);
        });
      } else {
        $("#defeito > option[rel!=default]").remove();
      }
    }
  });

}



</script>

<style type="text/css">
  input:-webkit-autofill, textarea:-webkit-autofill, select:-webkit-autofill {
    -webkit-box-shadow: 0 0 0px 1000px black inset;
    -webkit-text-fill-color: white !important;
  }

  #button_submit {
    text-align: center;
  }

  #button_submit ul {
    display: inline-block;
  }
</style>

    <form action="contact-validation.php" id="formularioContato" method="post">
    <ul>
      <li>
      <select name="assunto" id="assunto" class="required">
        <option value="">Assunto</option>
        <option value="duvida_produto">Informação</option>
        <option value='sugestao'>Sugestão</option>
        <option value='reclamacao_produto'>Reclamação</option>
      </select>
      </li>
      <li><input name="nome" class="required" placeholder="Nome Completo" id="nome" type="text"></li>
      <li><input name="email" class="required" placeholder="Email" id="email" type="text"></li>
      <li>
      <input type="text" class="telefone" id="auto:form telefone" name="telefone" title="Telefone" placeholder="Tel. Fixo" maxlength="14">
      </li>
      <li>
      <input type="text" class="telefone" id="auto:form celular" name="celular" title="Celular" placeholder="Celular" maxlength="15">
      </li>
      <li><input type="text" name="cep" id="cep" class="required" placeholder="Cep" maxlength="9"></li>
      <li><input type="text" class="required" id='endereco' name="endereco" placeholder="Endereço"></li>
      <li><input type="text" class="required" id='numero' name="num" placeholder="Número"></li>
      <li><input type="text" name="complemento" placeholder="Complemento"></li>
      <li><input type="text" class="required" id='bairro' name="bairro" placeholder="Bairro"></li>
      <li>
        <select id="estado" name="estado" class="required">
        <option value="" selected>Estado</option>
        <?php
          foreach ($array_estado as $k => $v) {
            echo '<option value="'.$k.'"'.($estado == $k ? ' selected="selected"' : '').'>'.$v."</option>\n";
          }
        ?>
        </select>
      </li>
      <li>
        <select name='cidade' id='cidade' class='required'>
          <option value="" selected >Cidade</option>
        </select>
        <!-- <input name="cidade" class="required" placeholder="Cidade" id="cidade" type="text"> -->
      </li>
      <li>
        <!-- <input type="text" class="required" name="produto" placeholder="Produto"> -->
        <select name='produto' id='produto' class='required'>
          <option value='' selected >Produto</option>
        </select>
        <!-- <input type='hidden' name='produto' id='produto' value="<?php echo $produto?>"> -->
      </li>
      <li>
        <!-- <input type="text" class="required" name="defeito" id="defeito" placeholder="Defeito Reclamado"> -->
        <select name='defeito' id='defeito' class='required'>
          <option value='' selected >Defeito</option>
        </select>
      </li>
      <li class="msg"><textarea placeholder="Mensagem" name="mensagem" id="mensagem" cols="40"></textarea></li>
    </ul>
    <div id='button_submit'>
      <ul>
        <li><button type="submit"><i class="fa fa-check"></i>Enviar</button></li>
      </ul>
    </div>

  </form>
</body>
</html>
