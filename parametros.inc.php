<?php
class Param{
	
	public	function title(){
		$titulo 	= "LV Network";
		return $titulo;
	} 	
	public function copyright(){
		setlocale(LC_MONETARY, 'pt_BR');		
		$nome = $this->title();
		$frase 	= "© Diretos reservados por ".$nome.": 2018 - ".date("Y");
		return $frase;
	} 	
	public function autor(){
		$parametro 	= "Adan Ribeiro, (31) 99297-1448";
		return $parametro;
	} 	
	public function descricao(){
		$seo_descricao 	= "A LV Network presta serviços de manutenção, monitoramento e gerenciamento de provedores e Call Center";
		return $seo_descricao;
	} 
	public function palavrachave(){
		$keyword 	= "Soluções em Telecomunicações, Suporte Técnico e Internet Banda Larga";
		return $keyword;
	} 
	public function emailConfig(){
		$nome = $this->title();
		$dominio = "lvdesk.com.br";
		$dominioHostinger = "hostinger.com.br"; #SMTP para hospedagem Hostinger. Desativar quando não for o caso.
		$array["dominio"] = $dominio;
		$array["serverhost"] = "smtp.".$dominioHostinger;
		$array["remetenteNome"]  = "$nome - Sistema de Mensagens"; 
		$array["username"] = "no-reply@".$dominio; // Usuário do servidor SMTP (endereço de email) $remetenteEmail 
		$array["varSMTPAuth"] = true; // Usar autenticação SMTP (obrigatório para smtp.seudomínio.com.br)
		$array["password"] = '76UaDdk2'; // Senha do servidor SMTP (senha do email usado)
		$array["isHTML"] = true; // Define que o e-mail será enviado como HTML
		$array["charSet"] = 'UTF-8'; // Charset da mensagem (opcional)
		$array["port"] = '465';	
		return $array;
	}
}
?>