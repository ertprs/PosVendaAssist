<?php header('Content-Type: text/html; charset=utf-8');

$pavalida = array (
	"Primeiro_Acesso" => array (
		"pt-br" => "",
		"es"    => "Primer Acceso",
		"en"    => "First-Time Access",
		"de"    => "",
		"zh-cn" => "首次登录",
		"zh-tw" => "首次登入"
	),
	"Gravar" => array (
		"pt-br" => "",
		"es"    => "Grabar",
		"en"    => "Save",
		"de"    => "",
		"zh-cn" => "存取",
		"zh-tw" => "儲存"
	),
	"err_conf_pass" => array (
 		"pt-br" => "Confirme sua senha.",
 		"es"    => "Confirme su clave.",
 		"en"    => "Confirm your Password",
 		"de"    => "",
 		"zh-cn" => "确认密码",
 		"zh-tw" => "確認密碼"
 	),
	"err_senha_invalidos" => array (
		"pt-br" => "Senha inválida, a senha contém caracteres inválidos.",
		"es"    => "Clave inválida, la clave debe tener al menos 2 letras.",
		"en"    => "Password invalid, it must have at least 2 letters.",
		"de"    => "",
  		"zh-cn" => "密码设置错误",
  		"zh-tw" => "密碼設置錯誤"
	),
	"err_senha_2_letras" => array (
  		"pt-br" => "Senha inválida, a senha deve ter pelo menos 2 letras.",
  		"es"    => "Clave inválida, la clave debe tener al menos 2 letras.",
  		"en"    => "Password invalid, it must have at least 2 letters.",
  		"de"    => "",
  		"zh-cn" => "密码设置错误，最少要有两个英文字母",
  		"zh-tw" => "密碼設置錯誤，最少要有兩個英文字母"
  	),
	"err_senha_2_nums" => array (
		"pt-br" => "Senha inválida, a senha deve ter pelo menos 2 números.",
		"es"    => "Clave inválida, la clave debe tener al menos 2 números.",
		"en"    => "Password invalid, it must have at least 2 numbers.",
		"de"    => "",
  		"zh-cn" => "密码设置错误，最少要有两个数字",
  		"zh-tw" => "密碼設置錯誤，最少要有兩個數字"
	),
	"err_senha_6_min" => array (
  		"pt-br" => "A senha deve conter um mínimo de 6 caracteres.",
  		"es"    => "La clave debe ser como mínimo de 6 caracteres.",
  		"en"    => "Password invalid, it must be at least 6 characters long.",
  		"de"    => "",
  		"zh-cn" => "密码设置错误，最少要有六个字节",
  		"zh-tw" => "密碼設置錯誤，最少要有六個字節"
  	),
	"err_senha_vazia" => array (
		"pt-br" => "Digite uma senha",
		"es"    => "La clave es obligatoria",
		"en"    => "Password is required",
		"de"    => "",
		"zh-cn" => "输入一个密码",
		"zh-tw" => "輸入一個密碼"
	),
	"sel_fabrica" => array (
  		"pt-br" => "Selecione a fábrica.",
  		"es"    => "Seleccione el fabricante.",
  		"en"    => "Select a Manufacturer",
  		"de"    => "",
  		"zh-cn" => "选择公司",
  		"zh-tw" => "選擇公司"
  	),
	"err_senha_dup" => array (
		"pt-br" => "Senha inválida. Por favor, digite uma nova senha para esta fábrica.",
		"es"    => "Clave inválida. Por favor, teclee una nueva clave para este fabricante.",
		"en"    => "Password invalid. Please, type in a new password for this manufacturer.",
		"de"    => "",
		"zh-cn" => "密码错误，请再为这个公司重设一个密码",
		"zh-tw" => "密碼錯誤，請再為這個公司重設一個密碼"
	),
	"err_no_email" => array (
 		"pt-br" => "Não foi encontrado um email cadastrado para seu posto, entre em contato com o fabricante e solicite o cadastramento do seu email.",
 		"es"    => "No consta dirección de correo-e para su Servicio Autorizado, entre en contacto con el fabricante y solicite el alta de su dirección de correo-e.",
 		"en"    => "There is no e-mail in our database for this manufacture. Please, contact them and ask them to update your data adding your e-mail.",
 		"de"    => "",
 		"zh-cn" => "并未找到您的E-mail资料，请跟厂商联络并注册E-mail",
 		"zh-tw" => "並未找到您的E-mail資料，請跟廠商聯絡並註冊E-mail"
 	),
	"err_update_senha" => array (
		"pt-br" => "Não foi possível cadastrar sua nova senha.",
		"es"    => "No fue posible grabar su clave de acceso.",
		"en"    => "Error while saving your password.",
		"de"    => "",
		"zh-cn" => "密码注册错误",
		"zh-tw" => "密碼註冊錯誤"
	),
	"err_senha_nao_bate" => array (
		"pt-br" => "Os campos 'Digite uma Senha' e 'Confirme sua senha' estão diferentes.",
		"es"    => "¡Los campos 'Nueva Clave' y 'Confirme Clave' son diferentes!",
		"en"    => "'New Password' and 'Confirm Password' fields are different!",
		"de"    => "",
		"zh-cn" => "密码和上面的不一样",
		"zh-tw" => "密碼和上面的不一樣"
	),
	"email_subject" => array (
		"pt-br" => "Seus dados de acesso ao Sistema - Telecontrol",
		"es"    => "Sus datos de acceso al sistema - Telecontrol",
		"en"    => "",
		"de"    => "",
		"zh-cn" => "您的Telecontrol系统登入资料",
		"zh-tw" => "您的Telecontrol系統登入資料"
	),
	"err_email_ko" => array (
		"pt-br" => "Erro no envio de email de confirmação. Por favor, entre em contato com o suporte.",
		"es"    => "Error al enviar el mensaje de confirmación de e-mail. Por favor, contacte con nuestro Soporte Técnico.",
		"en"    => "",
		"de"    => "",
		"zh-cn" => "未能寄送确认E-mail，请跟我们联络",
		"zh-tw" => "未能寄送確認E-mail，請跟我們聯絡"
	),
	"Confirmar_Dados" => array (
		"pt-br" => "",
		"es"    => "Confirmar Datos",
		"en"    => "Confirm Your Info",
		"de"    => "",
		"zh-cn" => "确认填写资料",
		"zh-tw" => "確認填寫資料"
	),
	"CNPJ_KO" => array (
		"pt-br" => "O CNPJ informado não foi encontrado nos cadastros da TELECONTROL. \\nFavor entrar em contato com o fabricante para saber a previsão de quando o seu Posto Autorizado será CREDENCIADO no sistema TELECONTROL.",
		"es"    => "No hemos localizado la ID Fiscal indicada en nuestros registros.\\nPor favor, entre en contacto con el fabricante para obtener una previsión de cuándo su Servicio Técnico estará CREDENCIADO en el sistema TELECONTROL.",
		"en"    => "",
		"de"    => "",
		"zh-cn" => "在我们的系统中未找到您的公司行号，请跟厂商联络并确认何时能授权给您",
		"zh-tw" => "在我們的系統中未找到您的公司行號，請跟廠商聯絡並確認何時能授權給您"
	),
	"Aguarde" => array (
		"pt-br" => "Aguarde...",
		"es"    => "Espere...",
		"en"    => "Please, wait...",
		"de"    => "",
		"zh-cn" => "请稍等…",
		"zh-tw" => "請稍等…"
	),
	"err_no_email" => array (
		"pt-br" => "Nenhum email cadastrado, entre em contato com o fabricante.",
		"es"    => "No consta e-mail en el registro. Contacte con el fabricante.",
		"en"    => "",
		"de"    => "",
		"zh-cn" => "未注册E-mail，请联络此公司",
		"zh-tw" => "未注冊E-mail，請聯絡此公司"
	),
	"err_sem_acesso" => array (
		"pt-br" => "Não há permissão de acesso às fábricas.",
		"es"    => "No tiene acceso a los fabricantes.",
		"en"    => "You do not have access to any manufacturer.",
		"de"    => "",
		"zh-cn" => "没有权限进入此厂商",
		"zh-tw" => "沒有權限進入此廠商"
	),
	"err_sem_acesso_msg" => array (
		"pt-br" => "Entre em contato com seu fabricante e solicite seu cadastramento e liberação de acesso.",
		"es"    => "Entre en contacto con el fabricante y solicite el alta y datos de acceso.",
		"en"    => "",
		"de"    => "",
		"zh-cn" => "请跟厂商联络来注册和开放登入您的维修中心",
		"zh-tw" => "請跟廠商聯絡來註冊和開放登入您的維修中心"
	),
	"cadastra_senha" => array (
		"pt-br" => "Primeiro Acesso - Cadastro Senha",
		"es"    => "Primer Acceso - Crear Clave de Acceso",
		"en"    => "First-Time Access - Password",
		"de"    => "",
		"zh-cn" => "首次登入 - 注册密码",
		"zh-tw" => "首次登入 - 註冊密碼"
	),
	"instrucoes" => array (
		"pt-br" => "Instruções",
		"es"    => "Instrucciones",
		"en"    => "Instructions",
		"de"    => "",
		"zh-cn" => "教学",
		"zh-tw" => "教學"
	),
	"instrucoes_1" => array (
		"pt-br" => "Selecione a fábrica.",
		"es"    => "Seleccione el Fabricante.",
		"en"    => "Select the manufacturer.",
		"de"    => "",
  		"zh-cn" => "选择公司",
  		"zh-tw" => "選擇公司"
	),
	"instrucoes_2" => array (
		"pt-br" => "Informe o e-mail do seu posto que está cadastrado para a fábrica selecionada.",
		"es"    => "Comprobar que el correo-e esté correcto. Si no lo está, entre en contacto con el fabricante y solicite la corrección.",
		"en"    => "Make sure the e-mail address is correct. If it is not, please contact the manufacturer and ask for a correction.",
		"de"    => "",
		"zh-cn" => "确认您的E-mail是否正确，如果不对的话，请联络厂商并要求修改",
		"zh-tw" => "確認您的E-mail是否正確，如果不對的話，請聯絡廠商並要求修改"
	),
	"instrucoes_3" => array (
		"pt-br" => "Digite a senha desejada, com mínimo de seis caracteres e no máximo dez, sendo no minímo 2 letras (de A a Z) e 2 números (de 0 a 9).<BR>&nbsp;&nbsp;&nbsp; por exemplo: bra500, tele2007, ou assist0682.",
		"es"    => "Escriba la clave que desee, con un mínimo de seis caracteres y un máximo de diez, siendo que debe contener un mínimo de 2 letras (A - z) y 2 dígitos (0 -9).<br />&nbsp;&nbsp;&nbsp; por ejemplo: bra500, tele2007, o assist0682.",
		"en"    => "Type in a password, with a length between six and ten. Is has to have at least two letters (A - z) <b>and</b> two digits (0 - 9).<br />Example: bra500, tele2007, o assist0682.",
		"de"    => "",
		"zh-cn" => "请输入最少六个，最多十个字节的密码。密码最少要有两个英文字母和两个数字。例：bra500，tele2007，或是assist0682。",
		"zh-tw" => "請輸入最少六個，最多十個字節的密碼。密碼最少要有兩個英文字母和兩個數字。例：bra500，tele2007，或是assist0682。"
	),
	"instrucoes_4" => array (
		"pt-br" => "Digitar a senha novamente para confirmar.",
		"es"    => "Escriba de nuevo la clave, para confirmar.",
		"en"    => "Type the password again to confirm.",
		"de"    => "",
		"zh-cn" => "重新确认密码",
		"zh-tw" => "重新確認密碼"
	),
	"instrucoes_5" => array (
		"pt-br" => "Selecionar capital ou interior.",
		"es"    => "Seleccione 'Capital' o 'Provincia'",
		"en"    => "Select 'Capital' or 'Country'",
		"de"    => "",
		"zh-cn" => "请选择是首都或内地",
		"zh-tw" => "請選擇是首都或內地"
	),
	"instrucoes_6" => array (
		"pt-br" => "Após clicar no botão gravar, você receberá um e-mail para cadastrar sua nova senha de acesso.",
		"es"    => "Después de pulsar el botón 'Grabar', recibirá en su bandeja de entrada un mensaje de confirmación, un enlace de confirmación que debe abrir para liberar su acceso al sistema.",
		"en"    => "After clicking in 'Save', you'll receive a confirmation message in your Inbox, with your login, password and a confirmation link which has to be clicked, in order to free your access to our system.",
		"de"    => "",
		"zh-cn" => "注册后，您会收到一封有帐号，密码及一个认证链结的E-mail",
		"zh-tw" => "註冊後，您會收到一封有帳號，密碼及一個認證鏈結的E-mail"
	),
	"cadastro_de_usuarios" => array (
		"pt-br" => "Cadastro de Usuários",
		"es"    => "Alta de Usuarios",
		"en"    => "User Sign-Up",
		"de"    => "",
		"zh-cn" => "用户注册",
		"zh-tw" => "用戶注冊"
	),
	"Email" => array (
		"pt-br" => "",
		"es"    => "Correo-e",
		"en"    => "",
		"de"    => "",
		"zh-cn" => "",
		"zh-tw" => ""
	),
	"info_email_1" => array (
		"pt-br" => "Caso o e-mail não esteja correto, entre em contato com o fabricante e solicite a correção. Por exemplo, meunome@exemplo.com. Com isso você pode acessar a sua conta.",
		"es"    => "Si su dirección de correo-e no es correcta, contacte con el fabricante y solicite su alteración. Por ejemplo: mi_nombre@ejemplo.com. Esta dirección es necesaria para acceder a su cuenta.",
		"en"    => "If you e-mail address is not correct, pleast contact the manufacturere and ask for its update. i.e.: myname.example.com. This e-mail is necessary for you gain access to your accaout.",
		"de"    => "",
		"zh-cn" => "如果E-mail不正确的话，请跟厂商联络并要求修改。例: myname@example.com，如此您才能进入系统",
		"zh-tw" => "如果E-mail不正確的話，請跟廠商聯絡並要求修改。例: myname@example.com，如此您才能進入系統"
	),
	"info_email_2" => array (
		"pt-br" => "PREENCHA ESTE CAMPO CASO SEJA SEU PRIMEIRO ACESSO, CASO CONTRÁRIO NÃO SERÁ VÁLIDO",
		"es"    => "RELLENE ESTE CAMPO SÓLO SI ES SU PRIMER ACCESO. EN CASO CONTRARIO, NO SERÁ VÁLIDO",
		"en"    => "FILL IN THIS FIELD ONLY IF THIS IS YOUR FIRST-TIME ACCESS. IF NOT IT WILL NOT BE VALID",
		"de"    => "",
		"zh-cn" => "如果是首次登入的话，请填写此框框，不是的话则不用",
		"zh-tw" => "如果是首次登入的話，請填寫此框框，不是的話則不用"
	),
	"digite_senha" => array (
		"pt-br" => "Digite uma Senha",
		"es"    => "Nueva Clave",
		"en"    => "New Password",
		"de"    => "",
		"zh-cn" => "输入密码",
		"zh-tw" => "輸入密碼"
	),
	"confirme_senha" => array (
		"pt-br" => "Confirme sua senha",
		"es"    => "Confirme Clave",
		"en"    => "Confirm Password",
		"de"    => "",
		"zh-cn" => "确认密码",
		"zh-tw" => "確認密碼"
	),
	"cap_int" => array (
		"pt-br" => "Capital / Interior",
		"es"    => "Capital / Provincia",
		"en"    => "Capital / Country",
		"de"    => "",
		"zh-cn" => "首都/内地",
		"zh-tw" => "首都/內地"
	),
	"fabrica" => array (
		"pt-br" => "Fábrica",
		"es"    => "",
		"en"    => "Manufacturer",
		"de"    => "",
		"zh-cn" => "公司",
		"zh-tw" => "公司"
	),
	"sem_pa_subtitle" => array (
		"pt-br"	=> "Não tem nenhum primeiro acesso pendente",
		"es"	=> "No tiene ningún Primer Acesso pendiente",
		"en"	=> "There are no pending First Accesses",
		"zh-cn" => "",
		"zh-tw" => ""
	),
	"prezado_usuario" => array (
		"pt-br"	=> "Prezado usuário",
		"es"	=> "Apreciado usuario",
		"en"	=> "Dear user",
		"zh-cn" => "",
		"zh-tw" => ""
	),
	"nao_tem_pa" => array (
		"pt-br"	=> "Não tem pendência de Primeiro Acesso com nenhum fabricante",
		"es"	=> "No tiene ningún Primer Acceso pendiente para ningún fabricante",
		"en"	=> "There are no pending First Accesses for any manufacturer",
		"zh-cn" => "",
		"zh-tw" => ""
	),
	"caso_de_erro" => array (
		"pt-br"	=> "Se você não consegue acessar o sistema de algum dos fabricantes, contate com o Fabricante ou conosco (via <i>site</i> usando a tela de ",
		"es"	=> "Si no consigue acceder al sistema de algún fabricante, contacte con el Fabricante o con nosotros (vía <i>site</i> usando la página de ",
		"en"	=> "If you can't access the Telecontrol's manufacturer's system, please contact the manufacturer or contact with us (vía <i>site</i> using the ",
		"zh-cn" => "",
		"zh-tw" => ""
	),
	"ou_email" => array (
		"pt-br"	=> "ou pelo e-mail",
		"es"	=> "o a través del correo-e",
		"en"	=> " page, or to the e-mail",
		"zh-cn" => "",
		"zh-tw" => ""
	),
	"para_esclarecimentos" => array (
		"pt-br"	=> "para esclarecimentos sobre o que pode estar impedindo o acesso",
		"es"	=> "to clarify what might be preventing that access",
		"en"	=> "in order to ",
	),
	"Clique_aqui" => array (
		"pt-br"	=> "Clique aqui",
		"es"	=> "Pulse aquí",
		"en"	=> "Click here"
	),
	"para_acessar_o_sistema" => array (
		"pt-br"	=> "para voltar à página inicial do Sistema",
		"es"	=> "para volver a la página inicial del sistema",
		"en"	=> "to return to our site's homepage"
	),
);
?>
