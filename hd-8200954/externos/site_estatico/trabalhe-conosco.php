<?php $pagetitle = "Trabalhe Conosco" ?>

<?php include('header.php') ?>
<script>$('body').addClass('pg contato-page trabalhe-page')</script>

<section class="table h-img">
  <?php include('menu-pgi.php'); ?>
  <div class="cell">
    <div class="title"><h2>Trabalhe na Telecontrol</h2></div>
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
        tel1:{required: true, telefone:true },
        tel2:{required: true, telefone:true },
        arquivo:{required:true,arquivo:true}
      },
      messages:{
        nome:{required: "Por favor, digite o seu nome.", minLength: "O seu nome deve conter, no mínimo, 4 caracteres."},
        email:{required: "Por favor, digite o seu e-mail para contato.", email: "Por favor, digite um e-mail válido"},
        tel1:{required: "Por favor, digite um telefone para contato." },
        tel2:{required: "Por favor, digite um telefone para contato." },
        arquivo:{required:"Por favor, selecione um arquivo."}
        }
    });
  });
  </script>

    <div class="main">
    <div class="main-half">  
    <div class="desc">
      <p>
      As oportunidades de crescer profissionalmente são inúmeras. E elas surgem para quem está preparado para o crescimento profissional e pessoal.
      <br>Estamos sempre em busca de novos talentos para participar deste crescimento, formando um time forte e vencedor.
      <br>Seja um Vencedor, cadastre o seu currículo.
      <br>Ele ficará disponível para todas as áreas de negócios da empresa.
      <br>Faça parte de nossa equipe vencedora!
      </p>
    </div>

    <form class="m-top" action="<?php echo $url; ?>/trabalhe-validation.php" id="formularioContato" method="post" enctype="multipart/form-data" name="email">
    <ul>
         
          <li><input name="nome" class="required" placeholder="Nome Completo" id="nome" type="text"></li>
          <li class="i-big">
            <input name="endereco" class="required" placeholder="Endereço" id="endereco" type="text">
          </li>
          <li class="i-small f-right">
            <input name="numero" class="required" placeholder="Número" id="numero" type="text">
          </li>
          <li class="i-half">
            <input name="bairro" class="required" placeholder="Bairro" id="bairro" type="text">
          </li>
          <li class="i-half f-right">
            <input name="complemento" class="required" placeholder="Complemento" id="complemento" type="text">
          </li>

          <li class="i-big">
          <input name="cidade" class="required" placeholder="Cidade" id="cidade" type="text">
          </li>

          <li class="i-small f-right">

          <select name="estado" id="estado" class="required">
          <option value="" class="selected" selected="">Estado</option>
          <option value="AC">AC</option>
          <option value="AL">AL</option>
          <option value="AM">AM</option>
          <option value="AP">AP</option>
          <option value="BA">BA</option>
          <option value="CE">CE</option>
          <option value="DF">DF</option>
          <option value="ES">ES</option>
          <option value="GO">GO</option>
          <option value="MA">MA</option>
          <option value="MG">MG</option>
          <option value="MS">MS</option>
          <option value="MT">MT</option>
          <option value="PA">PA</option>
          <option value="PB">PB</option>
          <option value="PE">PE</option>
          <option value="PI">PI</option>
          <option value="PR">PR</option>
          <option value="RJ">RJ</option>
          <option value="RN">RN</option>
          <option value="RO">RO</option>
          <option value="RR">RR</option>
          <option value="RS">RS</option>
          <option value="SC">SC</option>
          <option value="SE">SE</option>
          <option value="SP">SP</option>
          <option value="TO">TO</option>
          </select>

          </li>

          <li class="i-half">
            <input name="tel1" class="required" placeholder="Telefone 1" id="tel1" type="text">
          </li>
          <li class="i-half f-right">
            <input name="tel2" class="required" placeholder="Telefone 2" id="tel2" type="text">
          </li>

          <li><input name="email_from" class="required" placeholder="Email" id="email" type="text"></li>
          
          <li class="i-half">
            <select name="cargo" id="cargo" class="required">
              <option value="" class="selected" selected="">Selecione o Cargo</option>
              <option value="Efetivo">Efetivo</option>
              <option value="Estagiário">Estagiário</option>
            </select>

          </li>

          <li class="i-half f-right">

            <select name="area" id="area" class="required">
              <option value="">Selecione a Área de Atuação</option>
              <option value="Analista">Analista de Sistemas</option>
              <option value="Programador">Programador</option>
              <option value="Suporte">Suporte</option>
              <option value="Logística">Logística</option>
              <option value="SAC">SAC</option>
              <option value="Auxiliar Escritório">Auxiliar Escritório</option>
            </select>

          </li>

          <li>
            <select name="salario" class="required" id="salario">
              <option value="" class="selected" selected="">Selecione a Pretensão Salarial</option>
              <option value="R$ 622,00 a R$ 1.000,00 ">R$ 622,00 a R$ 1.000,00 </option>
              <option value="R$ 1.000,00 a R$ 1.500,00 ">R$ 1.000,00 a R$ 1.500,00 </option>
              <option value="R$ 1.500,00 a R$ 2.000,00 ">R$ 1.500,00 a R$ 2.000,00 </option>
              <option value="R$ 2.000,00 a R$ 2.500,00 ">R$ 2.000,00 a R$ 2.500,00 </option>
              <option value="R$ 2.500,00 a R$ 3.000,00 ">R$ 2.500,00 a R$ 3.000,00 </option>
              <option value="R$ 3.000,00 a R$ 3.500,00 ">R$ 3.000,00 a R$ 3.500,00 </option>
              <option value="R$ 3.500,00 a R$ 4.000,00 ">R$ 3.500,00 a R$ 4.000,00 </option>
              <option value="R$ 4.000,00 a R$ 5.000,00 ">R$ 4.000,00 a R$ 5.000,00 </option>
              <option value="R$ 5.000,00 a R$ 6.000,00 ">R$ 5.000,00 a R$ 6.000,00 </option>
              <option value="R$ 6.000,00 a R$ 7.000,00 ">R$ 6.000,00 a R$ 7.000,00 </option>
              <option value="R$ 7.000,00 a R$ 8.000,00 ">R$ 7.000,00 a R$ 8.000,00 </option>
              <option value="R$ 8.000,00 a R$ 9.000,00 ">R$ 8.000,00 a R$ 9.000,00 </option>
              <option value="R$ 9.000,00 a R$ 10.000,00 ">R$ 9.000,00 a R$ 10.000,00 </option>
              <option value="Acima de R$ 10.000,00 ">Acima de R$ 10.000,00 </option>
            </select>
          </li>
  
          <li class="msg"><textarea placeholder="Observação" name="mensagem" id="mensagem" cols="40"></textarea></li>

          <li class="file">
            <span><i class="fa fa-upload"></i>Anexar Currículo</span>
            <input type="file" name="arquivo" id="arquivo" class="required i-big" accept="jpg|jpeg|pdf|doc|docx" value="">
          </li>

          <li class="enviar"><input type="submit" value="Enviar"></li>

    </ul>

    </form>

    </div>
    </div>

</section>

<?php include('footer.php') ?>
