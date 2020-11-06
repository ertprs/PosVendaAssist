<?php $pagetitle = "Contato" ?>

<?php include('header.php') ?>
<script>$('body').addClass('pg contato-page')</script>

<section class="table h-img">
  <?php include('menu-pgi.php'); ?>
  <div class="cell">
    <div class="title"><h2>Entre em contato com a Telecontrol</h2></div>
  </div>
</section>

<section class="pad-1 contato theform">

  <script type="text/javascript" src="<?php echo $url; ?>/js/jquery.validate.js"></script>
  <script type="text/javascript" src="<?php echo $url; ?>/js/mascara.js"></script>
  <script type="text/javascript">
    jQuery('#auto\\:form').focusout(function() {
      var input = jQuery('#auto\\:form').val();
      var input = input.replace(/\./g, "");
      var input = input.replace(/\-/g, "");
    });
  </script>
  <script type="text/javascript">
  jQuery(document).ready( function() {
    jQuery("#formularioContato").validate({
      rules:{
        nome:{required: true, minlength: 4 },
        email:{required: true, email: true },
        telefone:{required: true, telefone:true },
        cnpj: { cnpj:true }
      },
      messages:{
        nome:{required: "Por favor, digite o seu nome.", minLength: "O seu nome deve conter, no mínimo, 4 caracteres."},
        email:{required: "Por favor, digite o seu e-mail para contato.", email: "Por favor, digite um e-mail válido"},
        telefone:{required: "Por favor, digite um telefone para contato.", } 
        }
    });
  });
  </script>

    <div class="main">
    <div class="main-half">  

    <form action="<?php echo $url; ?>/contact-validation.php" id="formularioContato" method="post">
    <ul>
         
          <li><input name="nome" class="required" placeholder="Razão Social / Nome" id="nome" type="text"></li>
          <li><input name="cnpj" class="cnpj" placeholder="CNPJ" id="cnpj" type="text" style="display:none;"></li>
          <li><input name="email" class="required" placeholder="Email" id="email" type="text"></li>
          <li><input name="telefone" class="required" placeholder="Telefone" id="telefone" type="text"></li>
          
          <li class="select">
            <select name="setor" id="setor" class="required">
              <option value="" class="selected" selected="">Setor</option>
              <option value="Comercial">Comercial</option>
              <option value="Financeiro">Financeiro</option>
              <option value="Fabricantes">Suporte Fabricantes</option>
              <option value="Postos">Suporte Postos</option>
            </select>
          </li>

            <li class="select">
              <select name="assunto" id="assunto" class="required">
                <option value="" class="selected" selected="">Assunto</option>
              </select>
            </li>
          <li><input name="pedido" class="pedido" placeholder="Pedido" id="pedido" type="text" style="display:none;"></li>
          <li class="msg"><textarea placeholder="Mensagem" name="mensagem" id="mensagem" cols="40"></textarea></li>
          <li class="enviar"><input type="submit" value="Enviar"></li>

    </ul>

    <script>

        $('#setor').on('change', function() {
          var setorOption = $(this).val();
          if(setorOption == 'Comercial') {
              var newOptions = {
              '' : 'Selecione o Setor',
              'Apresentação do Sistema' : 'Apresentação do Sistema',
              'Consultoria Telecontrol' : 'Consultoria Telecontrol',
              'Contrato' : 'Contrato'
              };
          };

          if(setorOption == 'Financeiro') {
              var newOptions = {
              '' : 'Selecione o Setor',
              'Boleto(s)' : 'Boleto(s)',
              'Extratos(s)' : 'Extrato(s)'
              };
          };
          if(setorOption == 'Fabricantes') {
              var newOptions = {
              '' : 'Selecione o Setor',
              'Sugestões' : 'Sugestões',
              'Dúvidas do Sistema' : 'Dúvidas do Sistema',
              'Reclamação' : 'Reclamação'
              };
          };
          if(setorOption == 'Postos') {
              var newOptions = {
              '' : 'Selecione o Setor',
              'Sugestões' : 'Sugestões',
              'Dúvidas do Sistema' : 'Dúvidas do Sistema',
              'Pedidos' : 'Pedidos',
              'Reclamação' : 'Reclamação'
              };
          };

          if(setorOption == 'Financeiro' || setorOption == 'Fabricantes' || setorOption == 'Postos') {
            $('form .cnpj').show();
            $('form .cnpj').addClass('required');
          }
          else {
            $('form .cnpj').hide();
            $('form .cnpj').removeClass('required');
          }
          $('#assunto').on('change', function(){
            if($(this).val() == 'Pedidos') {
              $('form .pedido').show();
              $('form .pedido').addClass('required');
            }
            else {
              $('form .pedido').hide();
              $('form .pedido').removeClass('required');
            }
          });

          var select = $('#assunto');
          if(select.prop) {
          var options = select.prop('options');
          }
          else {
          var options = select.attr('options');
          }
          $('option', select).remove();

          $.each(newOptions, function(val, text) {
          options[options.length] = new Option(text, val);
          });

        });

    </script>

    </form>

    </div>
    </div>

</section>

<section class="tels">

    <div class="main">
    <div class="main-half">
      <div class="pad-1">
        <div class="title"><h2>Telefones de Contato</h2></div>
        <h3>O uso de telefonia VoIP nos permite ficar perto de nossos clientes.
        <br>Disque para o telefone mais próximo e economize.</h3>

        <ul>
          <li><h3>São Paulo</h3><span>(11) 4063-4230</span></li>
          <li><h3>Rio De Janeiro</h3><span>(21) 4063-4180</span></li>
          <li><h3>Campinas</h3><span>(19) 4062-9689</span></li>
          <li><h3>Curitiba</h3><span>(41) 4063-9872</span></li>
          <li><h3>Belo Horizonte</h3><span>(31) 4062-7401</span></li>
          <li><h3>Fortaleza</h3><span>(85) 4062-9872</span></li>
          <li><h3>Indaial (Blumenau)</h3><span>(47) 4052-9292</span></li>
          <li><h3>Salvador</h3><span>(71) 4062-8851</span></li>
          <li><h3>Caxias Do Sul</h3><span>(54) 4062-9112</span></li>
          <li><h3>Recife</h3><span>(81) 4062-8384</span></li>
          <li><h3>Florianópolis</h3><span>(48) 4052-8762</span></li>
          <li><h3>Porto Alegre</h3><span>(51) 4063-9872</span></li>
          <li><h3>Navegantes (Itajaí)</h3><span>(47) 4052-9292</span></li>
          <li><h3>Pato Branco</h3><span>(46) 4055-9292</span></li>
        </ul>

      </div>
    </div>
    </div>

</section>

<section class="address">
    <div class="main">
      <div class="pad-1">
        <div class="title">
          <h2>Endereço</h2>
        </div>
        <h3>Av. Carlos Artêncio, 420-A - Bairro Fragata - CEP: 17.519-255 | Marília, SP - Brasil</h3>
      </div>
    </div>
    <div id="map-canvas"></div>
</section>

<?php include('footer.php') ?>
