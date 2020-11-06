
<?
// $URL = "__FORMNAME=PgLogin&__FORMSTATE=BQYDAAgAAAAhNAAADAwA1CQAR3AAAYAAAIdDAAwEAIhQSF0UQJVRTNFIUJXYjtWaudG&__ACTIVECONTROLNAME=weLogin&__TARGETNAME=cxWebButton2&__ACTIONNAME=CLICK&__LEFTPOS=100&__TOPPOS=100&weLogin=ronaldo@telecontrol.com.br&wePassword=telecontrol&cxWebButton2=Entrar&PgLogin=/trk/trkisapi.dll/PgLogin";
// $teste =  `echo '$URL' | lynx -post_data "http://tracking.braspress.com.br/trk/trkisapi.dll/PgLogin"`;
// echo nl2br($teste);

/*
<input name="__FORMNAME" type="hidden" value="PgLogin" />
<input name="__FORMSTATE" type="hidden" value="BQYDAAgAAAAhNAAADAwA1CQAR3AAAYAAAIdDAAwEAIhQSF0UQJVRTNFIUJXYjtWaudG=" />
<input type="hidden" name="__ACTIVECONTROLNAME" id="__ACTIVECONTROLNAME" value="" />
<input type="hidden" name="__LEFTPOS" id="__LEFTPOS" value="" />
<input type="hidden" name="__TOPPOS" id="__TOPPOS" value="" />
<input type="hidden" name="__TARGETNAME" id="__TARGETNAME" value="" />
<input type="hidden" name="__ACTIONNAME" id="__ACTIONNAME" value="" />

action="/trk/trkisapi.dll/PgLogin"

name="PgLogin"
name="weLogin"
name="wePassword"
name="cxWebButton2"

theform.__TARGETNAME.value = "cxWebButton2";
theform.__ACTIONNAME.value = "CLICK";
theform.__LEFTPOS.value = ;
theform.__TOPPOS.value = dxModule.Pos.GetWindowScroll().y;
*/






// CORREIOS

// tx_codigo=5131669
// tx_senha=7246174


$URL = "tx_codigo=5131669&tx_senha=7246174";
$teste =  `echo '$URL' | lynx -dump -resubmit_posts -traversal -cookie_save_file=correios.cok -accept_all_cookies -post_data " http://www.correios.com.br/encomendas/servicosonline/encomendas/servicosonline/login.cfm"`;
echo nl2br($teste);

$teste =  `lynx -cmd_log=logcorreios.txt -accept_all_cookies  "http://www.correios.com.br/encomendas/servicosonline/logisticaReversa/default.cfm" `;
echo nl2br($teste);

$teste =  `lynx -cookie_file=correios.cok -accept_all_cookies  "http://www.correios.com.br/encomendas/servicosonline/default.cfm?s=true"`;
echo nl2br($teste);

 //lynx -cmd_log=logcorreios.txt -accept_all_cookies  http://www.correios.com.br/encomendas/servicosonline/default.cfm







?>