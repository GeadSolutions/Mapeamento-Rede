<?php
ini_set('session.gc_maxlifetime', 45600);
session_set_cookie_params(45600);
session_start();
include("conexao.inc.php");

class Model{
	
	public function conectaIMAP(){
		$username = NULL; $password = NULL; $incoming_server = NULL; $port = NULL;
		$resultado = $this->queryFree("SELECT * FROM config WHERE nome LIKE 'set_NOC_email_%'");
		while($set = $resultado->fetch_assoc()){
			if($set['nome'] == 'set_NOC_email_address'){
				$username = $set['valor'];
			}else if($set['nome'] == 'set_NOC_email_pass'){
				$password = $set['valor'];
			}else if($set['nome'] == 'set_NOC_email_server'){
				$incoming_server = $set['valor'];
			}else{
				$port = $set['valor'];
			}
		}		
		$mail_box = imap_open("{" . $incoming_server . ":" . $port . "/imap/ssl/novalidate-cert}INBOX", $username, $password); 
		if($mail_box){
			return $mail_box;
		}else{
			return false;
		}
	}
	
	public function delDir($dir){
		$dir_content = scandir($dir);
		if($dir_content !== FALSE){
			foreach ($dir_content as $entry) {
				if(!in_array($entry, array('.','..'))){
					$entry = $dir . '/' . $entry;
					if(!is_dir($entry)){
		 				unlink($entry);
					}
					else{
						delDir($entry);
					}
				}
			}
		}
		rmdir($dir);
	}

	function login($user, $senha){
		#by Adan, 24 de junho de 2015. Atualizado em 19 de julho de 2018 para acesso a ambiente de cliente.
		global $mysqli;
		global $result;
		$query = "SELECT u.*, p.nome AS 'nomep' FROM usuarios AS u 
		INNER JOIN privilegios AS p ON id_privilegio = p.id 
		WHERE usuario = '$user' AND senha = '$senha' AND u.lixo = 0";
		$mysql_result = $mysqli->query($query);
		$result = $mysql_result->fetch_assoc();
		if($result){
			if($result['id_contrato']==0){
				$_SESSION["datalogin"] = $result;
				return header("Location: ../../index.php");
			}else{ //caso haja usuários cadastrados pelo cliente
				$_SESSION["datalogin"] = $result;
				return header("Location: ../../index-cliente.php"); 
			}
		}else{
			$_SESSION['returnLogin'] = 'denied';
			return header('Location: ../../pages-login.php');
		} 
	}	
	
	function colorStatus($status){
		global $mysqli;
		global $st;
		$query_status = "SELECT * FROM status WHERE id = '$status'";
		$mysql_result = $mysqli->query($query_status);
		$tt = $mysql_result->fetch_assoc();
		if($tt){
			$st = "<span id='$tt[id_css]' title='$tt[descritivo]'>$tt[status]</span>";
			return $st;
		}else{
			return false;			
		}		
	}

	function loginCliente($user, $senha){
		#by Adan em 19 de julho de 2018 para acesso a ambiente de cliente.
		global $mysqli;
		global $result;
		$query = "SELECT c.*, p.nome AS 'nomep', contr.id AS id_contrato FROM clientes AS c 
		INNER JOIN privilegios AS p ON id_privilegio = p.id
		INNER JOIN contratos AS contr ON contr.id_cliente = c.id
		WHERE usuario = '$user' AND senha = '$senha' AND c.lixo = 0";
		$mysql_result = $mysqli->query($query);
		$result = $mysql_result->fetch_assoc();
		if(!empty($result['id'])){
			$result['ambiente_privilegio'] = 'on';
			$_SESSION["datalogin"] = $result;
			return header("Location: ../../index-cliente.php");			
		}else{
			$_SESSION['returnLogin'] = 'denied';
			return header('Location: ../../pages-login.php');
		} 
	}	

	public function queryFree($query){
		#by Adan, 04 de junho de 2015.
		global $mysqli;
		global $result;

		if(!isset($query)){
			echo("Segue abaixo o valor da query enviada:<br>".$query);
		}else{
			$result = $mysqli->query($query);
		}
		if(!is_null($result)){
			return $result;
		}
	}

	public function ajeitaFoto($img, $tbl){
		if($tbl == 'usuarios'){
			global $img;
		}
		if(!empty($img['name'])){#para o caso de nenhuma imagem seja carregada, mas outro item qualquer seja editado
			if($img['type']=="image/png" || $img['type']=="image/jpg" || $img['type']=="image/jpeg" ||  $img['type']=="image/gif"){
				if($img['size']<5242880){
					$extfile  = strtolower(substr($img['name'], strripos($img['name'], '.', -1)));
					if($extfile == ".jpg" || $extfile == ".png" || $extfile == ".gif" || $extfile == ".jpeg"){
						if($tbl == 'usuarios'){
							$path = "../../assetsb/images/users/";
						}
						$img['name'] = time().rand (5, 15).$extfile;
						$resultado = move_uploaded_file($img['tmp_name'], $path.$img['name']);
						if($resultado != false){
							$media = $img;	
							return $media;
						}else{
							echo $_FILES[$vetor]['error'];
						}
					}else{
						echo '
						<div class="alert alert-danger">
							<h4>Tivemos um problema...</h4>
							Extensão inválida para fotos! <a href="." class="alert-link">Clique aqui</a> para atualizar os status do sistema.
						</div>';
						die();
					}
				}else{
					echo '
					<div class="alert alert-danger">
					<h4>Tivemos um problema...</h4>
					Tamanho do arquivo não pode<br> ser maior que 5 Mb! <a href="." class="alert-link">Clique aqui</a> para atualizar os status do sistema.
					</div>';
					die();
				}
			}else{# upload de arquivos tipo 'media'
				if($img['size']<15242880){
					$extfile  = strtolower(substr($img['name'], strripos($img['name'], '.', -1)));
					if($img['type']=="audio/mp3" || $img['type']=="audio/wav"){
						$path = "../media/audio/lista/";
						$img['name'] = time().mt_rand(1000, 9000).$extfile;
						move_uploaded_file($img['tmp_name'], $path.$img['name']);
						$media = $img;
						return $media;
					}else if($img['type']=="video/mpeg" || $img['type']=="video/x-mpeg" || $img['type']=="video/mp4"){
						$path = "../media/video/videoteca/";
						$img['name'] = time().mt_rand(1000, 9000).$extfile;
						move_uploaded_file($img['tmp_name'], $path.$img['name']);
						$media = $this->queryFree("INSERT INTO ".$tbl."(midia, tipo) VALUES('".$img['name']."', 'video')");
						return $media;
					}else{
						echo '						
							<div class="alert alert-danger">
							<h4>Tivemos um problema...</h4>
							Extensão inválida para áudio/vídeo.  <a href="." class="alert-link">Clique aqui</a> para atualizar os status do sistema.
							</div>	
						';
						die();
					}
				}else{
					echo '
					<div class="alert alert-danger">
					<h4>Tivemos um problema...</h4>
					Tamanho do arquivo não pode<br> ser maior que 15 Mb! <br>Edição não permitida.
					</div>';
					die();
				}#if($img['size']<5242880)
			}#if($img['type']=="image/png" || etc...
		}#if(!empty($img['name'])
	}

	public function addFoto($nomeFile, $vetor, $tbl){
		#by Adan, 12 de julho de 2015.
		if (isset($_FILES)){
			global $img;
			if(is_array($_FILES)){
				# é um array simples
				$img   = $_FILES[$vetor];
				$media = $this->ajeitaFoto($img, $tbl);
				return $media;
			}else{
				# é uma matriz	: Script por Felipe Goose - 23/09/2015
				$arr = $_FILES[$nomeFile];
				$goose = $imgArray = array();
				foreach($arr as $key => $dados){
					for($z=0; $z<count($dados); $z++){
						$goose[$z][$key] = $dados[$z];
					} 
				} 

				foreach($goose as $indice){
					$media = $this->ajeitaFoto($indice, $tbl);
					return $media;
				}
			}#if(testArray($_FILES))
		}else{
			echo '
			<div class="alert alert-danger">
			<h4>Tivemos um problema...</h4>
			Ambiente de upload não configurado!<br>Contate o suporte.
			</div>';
			die();
		}#if (isset($_FILES))
	}
  function addAnexos($array){
    $path = "../../assetsb/media/docs/";
    $file = $array;
   if(is_array($file)){
      $dados['files'] = NULL;
      $files = array_filter($file['name']);
      $total = count($files);
      $y = 1;
      for( $i=0 ; $i < $total ; $i++ ) {
        if($files[$i] == $file['name'][$i]){
          $tmpFilePath = $file['tmp_name'][$i];
          if ($tmpFilePath != ""){
            $extfile  = strtolower(substr($file['name'][$i], strripos($file['name'][$i], '.', -1)));
            $file['name'][$i] = time().rand (5, 15).$extfile;
            $newFilePath = $path . $file['name'][$i];
            if(move_uploaded_file($tmpFilePath, $newFilePath)) {	
              if($y < $total){
                $dados['files'] .= $file['name'][$i]."#";
                $y++;
              }else{
                $dados['files'] .= $file['name'][$i];  
              }
            }
          }
        }
      }
    }else{
      if($file['error'] == 0 and $file['size']<5242880){	
        $extfile  = strtolower(substr($file['name'], strripos($file['name'], '.', -1)));
				$file['name'] = time().rand (5, 15).$extfile;
				$arquivo = $path.$file['name'];
        $sucesso = move_uploaded_file($file['tmp_name'], $arquivo);
        if($sucesso){
          $dados['files'] = $file['name'];
        }
      }
    }
    return $dados['files'];
  }
	function add($tabela, $array){
		#by Adan, 05 de junho de 2015.
		global $mysqli;
		$count 	= 1;
		$coluna = NULL;
		$valor 	= NULL;
		foreach($array as $key=>$value){
			$coluna .= $key;
			$valor  .= "'".$value."'";
			if($count < sizeof($array)){
				$coluna .= ", ";
				$valor  .= ", ";
			}
			$count++;
		}
		
		#echo "INSERT INTO $tabela ($coluna) VALUES($valor)<br>";
		$mysqli->query("INSERT INTO $tabela ($coluna) VALUES($valor)");
		if ($mysqli->affected_rows > 0) {
			$_SESSION['ult_id'] = $mysqli->insert_id;
			return true;
		}
		else{
			return false;
		} 
	}
	
	function add_retorno($tabela, $array){
		global $mysqli;
			$count 	= 1;
			$coluna = NULL;
			$valor 	= NULL;
			foreach($array as $key=>$value){
				$coluna .= $key;
				$valor  .= "'".$value."'";
				if($count < sizeof($array)){
					$coluna .= ", ";
					$valor  .= ", ";
				}
				$count++;
			}
			#echo "INSERT INTO $tabela ($coluna) VALUES($valor)<br>";
			$mysqli->query("INSERT INTO $tabela ($coluna) VALUES($valor)");
			if ($mysqli->affected_rows > 0) {
				$ult_id = $mysqli->insert_id;
				return $ult_id;
			}
			else{
				return false;
			} 
	}

	function addingEmail($tabela, $array, $body){
		$msg = $array['Msgno'];
		$query = "SELECT msgno FROM $tabela WHERE msgno = $msg";
		$foo = $this->queryFree($query);
		$res = $foo->fetch_assoc();
		if($res["msgno"] == ''){ # Evitando duplicidade, pois o e-mail do servidor ainda não existe no Banco de Dados. 		
			unset($array['Date'], $array['Subject']);
			$param_emails = NULL;
			if(isset($array['to'])){
				$to = $array['to'];	
				$to['tbl'] = "emails_toaddress";
				$param_emails[] = $to;
			}
			if(isset($array['from'])){ 	 
				$from = $array['from'];	
				$from['tbl'] = "emails_fromaddress";
				$param_emails[] = $from;
			}
			if(isset($array['reply'])){ 
				$reply	= $array['reply_to'];	
				$reply['tbl'] = "emails_reply_toaddress";
				$param_emails[] = $from;
			}
			if(isset($array['sender'])) {
				$sender = $array['sender'];	
				$sender['tbl'] = "emails_senderaddress";
				$param_emails[] = $sender;
			}		
			if(isset($array['ccaddress'])) {
				$ccaddress = $array['ccaddress'];	
				if(isset($ccaddress['tbl'])){
					$ccaddress['tbl'] = "emails_ccaddress"; 
					$param_emails[] = $ccaddress;
				}				
			} 
			if(isset($array['cc'])) {
				$cc = $array['cc'];		
				$cc['tbl'] = "emails_cc";
				$param_emails[] = $cc;
			}
			if(isset($array['udate'])) {
				$array['date'] = date("Y-m-d H:i:s", $array['udate']);			
			}
			
			unset(
				$array['to'], 
				$array['from'], 
				$array['reply_to'], 
				$array['sender'], 
				$array['ccaddress'], 
				$array['cc'], 
				$array['references']
			); 
			$array['body'] = $this->mime_encode($body);				
			$coluna = NULL;
			$new_array 	= NULL;
			foreach($array as $key=>$value){				
				$coluna = strtolower($key);
				$new_array[$coluna] = $value;				
			}  	
			$result = $this->add($tabela, $new_array);
			if ($result == true) {
				$ult_id = $_SESSION['ult_id'];
				$result_param = $this->array_email($param_emails, $ult_id);	
				return($ult_id); # ID da tabela "emails", ou seja, o registro-mãe desse trigger.
			}else{
				return false;
			}
		}else{
			return false;
		}
	}
	
	function mime_encode($data)
    {
        $resp = imap_utf8(trim($data));
        if(preg_match("/=\?/", $resp))
            $resp = iconv_mime_decode($data, ICONV_MIME_DECODE_CONTINUE_ON_ERROR, "ISO-8859-15");

        if(json_encode($resp) == 'null')
            $resp = utf8_encode($resp);

        return $resp;
    }
	
	function array_email($array, $id){		
		foreach($array as $key=>$value){
			if(is_array($value)){
				foreach($value as $key2=>$value2){
					if(is_array($value2)){
						$count 	= 1;
						$coluna = "id_emails, ";
						$valor 	= $id.", ";
                        $mail_completo = '';
						foreach($value2 as $key3=>$value3){
							if($key3 == 'mailbox'){
								$mail_completo = "'".$value3."@";
							}else if($key3 == 'host'){
								$mail_completo .= $value3."'";
							}							
							$coluna .= $key3;
							$valor  .= "'".$value3."'";
							if($count < sizeof($value2)){
								$coluna .= ", ";
								$valor  .= ", ";
							}
							$count++;							
							#echo $key." | ".$key3." => ".$value3." <br>";
						}
					}else{
						$result = $this->queryFree("INSERT INTO $value2 ($coluna, mailcompleto) VALUES($valor, $mail_completo)");
					}				
				}
			}			
		}		
	}
	
	function getBody($uid, $imap) {
		$body = $this->get_part($imap, $uid, "TEXT/HTML");
		
		if ($body == "") {
			$body = $this->get_part($imap, $uid, "TEXT/PLAIN");
		}
		return $body;
	}

	function get_part($imap, $uid, $mimetype, $structure = false, $partNumber = false) {
		if (!$structure) {
		   $structure = imap_fetchstructure($imap, $uid, FT_UID);
		}
		if ($structure) {
			if ($mimetype == $this->get_mime_type($structure)) {
				if (!$partNumber) {
					$partNumber = 1;
				}
				$text = imap_fetchbody($imap, $uid, $partNumber, FT_UID);
				switch ($structure->encoding) {
					case 3: return imap_base64($text);
					case 4: return imap_qprint($text);
					default: return addslashes($text);
				}
			}

			// multipart
			if ($structure->type == 1) {
				foreach ($structure->parts as $index => $subStruct) {
					$prefix = "";
					if ($partNumber) {
						$prefix = $partNumber . ".";
					}
					$data = $this->get_part($imap, $uid, $mimetype, $subStruct,	$prefix. ($index + 1));
					if ($data) {
						return $data;
					}
				}
			}
		}
		return false;
	}

	function get_mime_type($structure) {
		$primaryMimetype = array("TEXT", "MULTIPART", "MESSAGE", "APPLICATION",
			"AUDIO", "IMAGE", "VIDEO", "OTHER");

		if ($structure->subtype) {
		   return $primaryMimetype[(int)$structure->type] . "/" . $structure->subtype;
		}
		return "TEXT/PLAIN";
	}       
	
	function verificaNovosEmails(){
		global $retorno;
    $act = new Acoes();
		$mail_box = $this->conectaIMAP();
		if($mail_box){
			$count = imap_num_msg($mail_box);
      $numero_mens_novas = 0;
			for($msgno = 1; $msgno <= $count; $msgno++) {
				$headers = imap_headerinfo($mail_box, $msgno);        
				if($headers->Unseen == 'U' or $headers->Recent == 'R' or $headers->Recent == 'N') {					
					$numero_mens_novas++;				  
					$msguid = $headers->Msgno;
					$resultado = $this->queryFree("SELECT valor FROM config WHERE nome = 'set_contatos_agenda_email'");
					$set = $resultado->fetch_assoc();
					
					$msgno = imap_msgno($mail_box, $msguid);
					$header = imap_header($mail_box, $msgno);
					$body = $this->getBody($msguid, $mail_box);				
					$array_header = json_decode(json_encode($header), true);
					# Grava na tabela emails para controle
					$flag = $this->addingEmail('emails', $array_header, $body);
					# Grava em pav_inscritos para serviço e retorna um "flag" contendo o ID do último registro inserido.
					if($set['valor'] == 0){
						if($flag){
							$retorno = $this->manualFilterEmailToCGR($flag);							
						}else{
							$retorno = false;
						}
					}else{
						$this->filterEmailtoCGR($flag);
						# Trigger para não identar TODAS as mensagens
						$numero_mens_novas--;
						if($numero_mens_novas == 0){
							imap_close($mail_box);
							$retorno = false;
						}
					}
				}        
			}
      if($retorno){
        $msg_txt = "Foram encontradas ".($numero_mens_novas > 1 ? $numero_mens_novas.' mensagens novas.' : $numero_mens_novas.' mensagem nova.');
        $msg_retorno = $act->retornaMsg(10, "<p>MENSAGEM RETORNO DO SERVIDOR: $msg_txt</p>");
        return $msg_retorno;
      }
      /* else{
        $msg_txt = "Não há e-mails novos.";
        $msg_retorno = $act->retornaMsg(7, "<p>MENSAGEM RETORNO DO SERVIDOR: $msg_txt</p>");
        return $msg_retorno;
      } */
		}else{			
			$msg_txt = "Conexão não realizada. Servidor IMAP está fora do ar.";
			$msg_retorno = $act->retornaMsg(7, "<p>MENSAGEM RETORNO DO SERVIDOR: $msg_txt</p>");
			return $msg_retorno;	
		}
    imap_close($mail_box);
	}
	
	# ---- Quando o parametro CONFIG.set_contatos_agenda_email estiver configurado como ZERO ---- #
	function manualFilterEmailToCGR($id){
		$query = "SELECT * FROM emails WHERE id = $id";
		$resultado = $this->queryFree($query);
		$pav_inscritos_add = $resultado->fetch_assoc();
		$array['set'] = $pav_inscritos_add;
		$retorno = $this->movingEmailToCGR($array);
		#$retorno = $this->add("pav_inscritos", $pav_inscritos_add);
		return $retorno;
	}	
	
	function filterEmailtoCGR($flag){ # Se houver sucesso, $flag traz o último ID inserido na tabela 'emails'
		if($flag){ # Possível desde que essa é uma checagem passível de resultado falso (nenhum e-mail encontrado)
			$query_grupos_por_contato_agenda = "
			SELECT emails.*, clientes.id AS ClienteID, agenda_contatos.contatos AS destinatario, emails_fromaddress.mailcompleto
			FROM emails 
				INNER JOIN emails_fromaddress ON emails.id = emails_fromaddress.id_emails
				INNER JOIN agenda_contatos ON agenda_contatos.contatos = emails_fromaddress.mailcompleto
				INNER JOIN clientes ON clientes.id = agenda_contatos.id_cliente
			WHERE emails.id = $flag 
			GROUP BY emails.id";
			$foo = $this->queryFree($query_grupos_por_contato_agenda);
			$teste = $foo->fetch_assoc();
			if($teste['id'] != ''){ # Nenhuma relação encontrada. E-mail não possui contatos na agenda - atribuir a grupos de venda.
				if($teste['ClienteID'] != ''){
					$this->movingEmailToCGR($teste, $teste['ClienteID']);
				}else{
					$this->movingEmailToCGR($teste);
				}
			}else{
				echo "<br>Foram encontrados e-mails que não possuem contatos na agenda de clientes. <br>Estes foram atribuídos a grupos de venda.";
				$retorno = $this->manualFilterEmailToCGR($flag);
				if($retorno){
					print_r($retorno);
				}else{
					return false;
				}
			}
		}
	}
	
	function movingEmailToCGR($array, $cliente = NULL){
		global $array_pav; 
		$a = new Model;
    $array_pav["historico"] = NULL;
    
		foreach($array as $key=>$value){
			if(is_array($value)){
				foreach($value as $keys=>$values){
					$array_pav['nome_provedor'] = "Provedor não encontrado";
					$array_pav['id_pav'] = 0;
					$array_pav['id_contratos'] = 0;
					if($keys == 'fromaddress'){
						$array_pav['nome_cliente'] = $values;
						$array_pav['historico'] .= "<b>De:</b> ".$values."<br>";
					}
					if($keys == 'senderaddress')
						$array_pav['email'] = $values;
					if($keys == 'ccaddress')
						$array_pav['historico'] .= "<b>Cópia para:</b> ".$values."<br>";
					if($keys == 'subject')
						$array_pav['historico'] .= "<b>Assunto:</b> <em>".iconv_mime_decode($values)."</em>";
					if($keys == 'body')
						$array_pav['historico'] .= "<br><hr><br><br>".$values;
					if($keys == 'date')
						$array_pav['data_abertura'] = $values;
				}
				$array = $array['set'];
        $query_destinatario = "SELECT mailcompleto FROM emails_fromaddress WHERE id_emails = $array[id]";
        $foo = $a->queryFree($query_destinatario);
        if($foo->num_rows > 0){
          $destinatario = $foo->fetch_assoc();
          $array['destinatario'] = $destinatario['mailcompleto'];
        }else{
          $array['destinatario'] = $array_pav['email'];
        }
			}else{				
				if($key == 'fromaddress'){
					$array_pav['nome_cliente'] = $value;
					$array_pav['historico'] .= "<b>De:</b> ".$value."<br>";
				}
				if($key == 'senderaddress')
					$array_pav['email'] = $value;
				if($key == 'ccaddress')
					$array_pav['historico'] .= "<b>Cópia para:</b> ".$value."<br>";
				if($key == 'subject')
					$array_pav['historico'] .= "<b>Assunto:</b> <em>". iconv_mime_decode($value)."</em>";
				if($key == 'body')
					$array_pav['historico'] .= "<br><hr><br><br>".$value;
				if($key == 'date')
					$array_pav['data_abertura'] = $value;
				if(is_null($cliente)){
					unset($array['ClienteID']);
				}else{
					if($key == 'ClienteID'){ # Setar um cliente não significa que ele possui um contrato.
						$query = "SELECT nome, id_provedor, contratos.id AS id_contrato FROM clientes 
						INNER JOIN contratos ON contratos.id_cliente = clientes.id
						WHERE clientes.id = '$value'";
						#print_r($query);die();
						$foo = $this->queryFree($query);
						$nome_cliente = $foo->fetch_assoc();
						if($nome_cliente['nome'] != ''){ # Se verdadeiro, significa que o cliente possui um contrato de CGR		
							$array_pav['nome_provedor'] = $nome_cliente['nome'];
							$array_pav['id_pav'] = $nome_cliente['id_provedor'];
							if(isset($nome_cliente['id_contrato'])){
								$array_pav['id_contratos'] = $nome_cliente['id_contrato'];
							}
						}else{ # Neste caso o contrato não existe, o lead deve ser enviado para tratativa de Vendas
							$query = "SELECT id FROM groups WHERE finalidade_especial  = 1";
							$foo = $this->queryFree($query);
							$grupo_responsavel = $foo->fetch_assoc();
							if($grupo_responsavel['id'] != ''){
								$array_gr = NULL;
								$i = 1;
								foreach($grupo_responsavel as $value){
									if($i >= sizeof($grupo_responsavel)){
										$array_gr .= $value;
									}else{
										$array_gr .= $value."#";
									}
									$i++;
								}
								$array_pav['grupo_responsavel'] = $array_gr;								
							}else{
								echo "<h4>Atenção</h4>Nenhum grupo foi cadastro. Sem isso a importação de e-mails de clientes sem contratos ficará inconsistente.";
							}
						}
					}
				}					
			}
			$array_pav['origem'] = "E-mail";
			$array_pav['protocol'] = $this->protocolo();
			$array_pav['atribuido_de'] = '2';
			$array_pav['assunto'] = '52';
			# Array para pav_movimentos
			$newlog['protocol'] 		= $array_pav['protocol'];
			$newlog['descricao']		= $array_pav['historico'];
			$newlog['files']			= NULL;
			$newlog['id_atendente']		= '0';
			$newlog['data']				= date('Y-m-d H:i:s');
		}	
		# Gravação do serviço e das tratativas dentro do histórico
		$gravou = $this->add('pav_inscritos', $array_pav); 
		if($gravou){
			$newlog['id_pav_inscritos'] = $_SESSION['ult_id'];
			$tabela						= "pav_movimentos";			
			$this->add($tabela, $newlog);
			unset($newlog['id_atendente']);
			$newlog['nome_provedor']	= $array_pav['nome_provedor'];
			$newlog['id_contratos']		= $array_pav['id_contratos'];
			# Definido o grupo de responsáveis do NOC o sistema deverá enviar notificações para todos
			$this->add("comunicacao_interna_movimentos", $newlog);
			$query_block = "SELECT * FROM agenda_contatos WHERE contatos = $array[destinatario]";
      $result_block = $this->queryFree($query_block);
      $block_msg = $result_block->fetch_assoc();
      if($block_msg['block'] == 0){
        $this->envia($array, 'Notificação LV Desk - Protocolo: '.$array_pav['protocol'], NULL, 0);
      }
			return $gravou;
		}else{
			echo "Não houve retorno na gravação do serviço";
		}
	}
	
	function addUser($tabela, $array){
		# by Adan, 27 de novembro de 2015.
		global $mysqli;
		$count 	= 1;
		$coluna = NULL;
		$valor 	= NULL;
		foreach($array as $key=>$value){
			$coluna .= $key;
			$valor  .= "'".$value."'";
			if($count < sizeof($array)){
				$coluna .= ", ";
				$valor  .= ", ";
			}
			$count++;
		}
		#echo "INSERT INTO $tabela ($coluna) VALUES($valor)";
		$mysqli->query("INSERT INTO $tabela ($coluna) VALUES($valor)");
		$id = $mysqli->insert_id;
		if ($mysqli->affected_rows > 0) {
			$resultado = $this->queryFree("SELECT * FROM usuarios WHERE id = '".$id."'");
			return $resultado;
		}
	}
	public function processaCerquilhas($dados){
		#by Adan, 01 de agosto de 2018.
		$i 	= 1; 				
		$valor = NULL;
		$array = NULL;
		foreach($dados as $key=>$value){
			if(is_array($value)){
				foreach($value as $vlr){
				  $valor .= $vlr;
				  if($i < sizeof($value)){
					$valor .= "#";
					$i++;
				  }
				}
				if(isset($array[$key])){
					$array[$key] .= $valor;
				} else{
					$array[$key] = $valor;
				}
			}else{							
				if(isset($array[$key])){
					$array[$key] .= $value;
				} else{
					$array[$key] = $value;
				}
			}
		}
		return $array;
	}

	#Montagem do filtro composto para rotina de Atendimento do PostgreSQL - Adan 25/06/2018
	public function selecionaQueryPostgreSQL($nome = NULL, $campo_nome = NULL, $cpf = NULL, $campo_cpf = NULL, $endereco = NULL, $campo_endereco = NULL, $tabela = NULL){
		$query = "SELECT * FROM ".$tabela." ";
		if($nome){
			$query .= "WHERE ".$campo_nome." ILIKE '%".$nome."%'";
		}
		if($nome && $cpf){
			$query .= " AND ".$campo_cpf." = '".$cpf."'";
		}else if($cpf){
			$query .= " WHERE ".$campo_cpf." = '".$cpf."'";
		}
		if($nome && $cpf && $endereco || $nome && $endereco || $cpf && $endereco){
			$query .= " AND ".$campo_endereco." ILIKE '%".$endereco."%'";
		}else if($endereco){
			$query .= " WHERE ".$campo_endereco." ILIKE '%".$endereco."%'";
		}
		return $query;
	}

	#Montagem do filtro composto para rotina de Atendimento e CGR do MySQL - Adan 25/06/2018
	public function selecionaQueryMySQL($itemA = NULL, $itemA_campo = NULL, $itemB = NULL, $itemB_campo = NULL, $itemC = NULL, $itemC_campo = NULL, $tabela = NULL, $id_contrato = NULL){
		$query = "SELECT * FROM ".$tabela." ";
		if($itemA){
			$query .= "WHERE ".$itemA_campo." LIKE '%".$itemA."%'";
		}
		if($itemA && $itemB){
			$query .= " AND ".$itemB_campo." = '".$itemB."'";
		}else if($itemB){
			$query .= " WHERE ".$itemB_campo." = '".$itemB."'";
		}
		if($itemA && $itemB && $itemC || $itemA && $itemC || $itemB && $itemC){
			$query .= " AND ".$itemC_campo." LIKE '%".$itemC."%'";
		}else if($itemC){
			$query .= " WHERE ".$itemC_campo." LIKE '%".$itemC."%'";
		}
		return $query;
	}
	
	public function termosPesquisa($itemA = NULL, $itemA_campo = NULL, $itemB = NULL, $itemB_campo = NULL){
		$query = "busca=";
		if($itemA){
			$query .= $itemA_campo."&termo_busca=".$itemA;
		}
		if($itemA && $itemB){
			$query .= "&".$itemB_campo."&termo_busca=".$itemB;
		}else if($itemB){
			$query .= $itemB_campo."&termo_busca=".$itemB;
		}
		$query .= "&limit=100";
		return $query;		
	}

	function upd($tabela, $array, $id = NULL, $field = NULL){
		global $mysqli;
		$count 	= 1;
		$coluna  = NULL;
		$valor 	 = NULL;
		$setting = NULL;
		foreach($array as $key=>$value){
			$coluna = $key;
			$valor  = " = '".$value."'";
			if($count < sizeof($array)){
				$valor  .= ", ";
			}
			$setting .= $coluna . $valor;
			$count++;
		}
		if(is_null($id)){
			#echo "UPDATE $tabela SET $setting<br>";
			$mysqli->query("UPDATE $tabela SET $setting");
			return $mysqli;
		}else{
			if(isset($field)){
				#echo "UPDATE $tabela SET $setting WHERE $field = '$id'<br>";
				$mysqli->query("UPDATE $tabela SET $setting WHERE $field = '$id'");
				return $mysqli;
			}else{
				#echo "UPDATE $tabela SET $setting WHERE id = '$id' <br>";
				$mysqli->query("UPDATE $tabela SET $setting WHERE id = '$id'");
				return $mysqli;
			}
		}
	}

	public function exc($tabela, $id){
		global $mysqli;

		$query = "SELECT lixo FROM $tabela WHERE id = $id ";
		
		$buscar = $this->queryFree($query);
		$resultado = $buscar->fetch_assoc();
		$status = 0;
		$mensagemStatus ="";
		
		if($resultado['lixo'] == '0'){
			$status = 1;
			$mensagemStatus = "Desativado";
		}elseif($resultado['lixo'] == '1'){
			$status = 0;
			$mensagemStatus = "Ativado";
		}

		#echo "UPDATE $tabela SET lixo = '1' WHERE id = '$id'";
		$mysqli->query("UPDATE $tabela SET lixo = '$status' WHERE id = '$id'");
		if($mysqli->affected_rows > 0){
			echo "<div class='alert alert-success'>
			<h4>Operação executada com sucesso!</h4>
			<p>Registro $mensagemStatus.</p>
			</div>
			"; 
			return true;
		}else{
			echo "<div class='alert alert-danger'>
			<h4>Falha na operação.</h4>
			<hr><p><b>Código do erro retornado:</b> ";
			print_r($mysqli->error);
			echo "</p>
			</div>
			"; 
			return false;
		}
		
	}

	public function edt($tabela, $id){
		global $mysqli;
		$mysqli->query("UPDATE $tabela SET lixo = '1' WHERE id = '$id'");
		return true;
	}

	public function criaAlbum($id){
		#by Adan, 22 de setembro de 2015.
		global $mysqli;
		$mysqli->query("SELECT id FROM albuns WHERE id_contato = '$id' AND lixo = 0");
		if($mysqli->affected_rows == 0){
			$data = date('Y-m-d');
			$mysqli->query("INSERT INTO albuns (data, caminho, id_contato) VALUES($data, $id, $id)");
		}
	}

	# Funções para gerenciamento de privilégios e acessos no sistema - by Adan 07/06/2018
	public function libPriv($id){
		$query = "SELECT acessos, acessos_menus FROM privilegios WHERE lixo = 0 AND id = $id ";
		$foo = $this->queryFree($query);
		$val = $foo->fetch_assoc();
		$modulos = explode("#", $val['acessos']);
		$menus = explode("#", $val['acessos_menus']);
		$this->libMenuAdmin($modulos, $menus);
	}
		
	public function libMenuAdmin($arrayAcessos, $idSubMenu){
		
		$queryMenus ="SELECT modulos.id as id_modulos, modulos.nome as nome_modulos, modulos.descricao as desc_modulos, 
		modulos.media as media_modulos, modulos.`value` as value_modulos, modulos.lixo as lixo_modulos 
		FROM  modulos WHERE  modulos.lixo = 0 ORDER BY ordem ";	
		$dadosMenus = $this-> queryFree($queryMenus);
		$querySubMenus ="SELECT  menus.id as id_menus, menus.id_pai as idPai_menus, menus.valor as valor_menus, menus.lixo as lixo_menus, menus.nome as nome_menus FROM menus WHERE  menus.lixo = 0 ORDER BY ordem ";	
		$dadosSubMenus = $this-> queryFree($querySubMenus);
	
		foreach($dadosMenus as $row ){		
			foreach ($arrayAcessos as $acessoMenu) {				
				if ($row['id_modulos'] == $acessoMenu)  {				
					echo "
						<li class='nav-item'>
						
						<a title='".$row["desc_modulos"]."' href='#".$row["id_modulos"]."' class='nav-link' href_link data-link='views/".$row["value_modulos"]."' data-toggle='collapse'> 
						
						<i class='material-icons'>".$row["media_modulos"]."</i> <p>".$row["nome_modulos"]." <b class='caret'></b></p>
						<span class='pull-right'></i></span>
				  
						</a>
						<div class='collapse' data-parent='#accordion' id='".$row["id_modulos"]."'>
						<ul class='nav'>
					";						
					foreach ($dadosSubMenus as $dadoSubMenu) {										
						foreach ($idSubMenu as $acessoSubMenu) {
							$letra1 = substr ($dadoSubMenu['nome_menus'],  0 ,3 );							
							if ($row['id_modulos'] == $dadoSubMenu["idPai_menus"]  and $dadoSubMenu["id_menus"] == $acessoSubMenu) {	
								
								echo "<li class='nav-item' ><a class='nav-link regular-link' data-link='".$dadoSubMenu['valor_menus']."' lv> <span class='sidebar-mini visible-on-sidebar-mini'> ".$letra1." </span>";								
														
								if($dadoSubMenu['nome_menus'] == 'Serviços'){
									$this->notification();
								}else if($dadoSubMenu['nome_menus'] == 'Leitura de e-mails'){
									$this->notification('1');
								}									
								echo "<span class='sidebar-normal'>".$dadoSubMenu['nome_menus']."</span></a></li>";
							}	
						}	
					}			
					echo "
						</ul>
						</div>
						</li>
					";
				}
			}			
		}	echo "<div class='espaco'></div>";	
	}	
		
	/* public function libMenuAdmin($arrayAcessos, $idSubMenu){
		foreach($arrayAcessos as $value){
			$woo = $this->queryFree("SELECT * FROM modulos WHERE lixo = 0 AND id = $value");
			$row = $woo->fetch_assoc();
			if($row['id'] != 0){
				echo "
				  <li class='has_sub'>
				  <a title='".$row["descricao"]."' class='waves-effect' href_link link='views/".$row["value"]."'>
				  <i class='".$row["media"]."'></i><span>".$row["nome"]."</span>
				  <span class='pull-right'><i class='mdi mdi-plus'></i></span>
				  </a>
				  <ul class='list-unstyled'>
				";
					
				foreach($idSubMenu as $value){					
					$woo = $this->queryFree("SELECT * FROM menus WHERE lixo = 0 AND id = $value AND id_pai = '".$row['id']."'");
					$subMenu = $woo->fetch_assoc();	
					if($subMenu['id']){
						echo "<li><a class='regular-link' link='".$subMenu['valor']."' lv>";
						if($subMenu['nome'] == 'Serviços'){
							$this->notification();
						}else if($subMenu['nome'] == 'Leitura de e-mails'){
							$this->notification('1');
						}
						echo $subMenu['nome']."</a></li>";
					}
				}				
				echo "
				  </ul>
				  </li>
				";
			}
		}
		
	}  */

	public function habilitaModulos($query){
	  $foo = $this->queryFree($query);
	  $val = $foo->fetch_assoc();
	  $retorno = explode("#", $val['acessos']);

	  foreach($retorno as $value){
		$woo = $this->queryFree("SELECT * FROM modulos WHERE lixo = 0 AND id = $value");
		$row = $woo->fetch_assoc();
	    print_r(
		  '<div class="checkbox checkbox-primary">
			<input id="checkbox1" type="checkbox" data-parsley-multiple="group1">
			<label for="checkbox1">'.$row["nome"].'</label>
		  </div>
	    ');
	  }
	}

	public function notification($flag = NULL, $panel = NULL){
		if(is_null($flag)){
			$query = "SELECT COUNT(id) as qnt_id FROM pav_inscritos WHERE validado = 0 AND lixo = 0";
			$foo = $this->queryFree($query);
			$val = $foo->fetch_assoc();
			if($val['qnt_id'] > 0){
				if(is_null($panel)){
					echo '<span style="vertical-align: top;" id="notificador" class="badge badge-primary pull-right">'.$val['qnt_id'].'</span>';
					return true;
				}else{
					#echo $val['qnt_id'];
					return true;
				}
			}else{
				return false;
			}
		}else{
			if (isset($_SESSION['mail_box'])) {
				$total_de_mensagens = imap_num_msg($_SESSION['mail_box']);
				if($total_de_mensagens > 0){
					echo '<span style="vertical-align: top;" id="notificador" class="badge badge-primary pull-right">'.$total_de_mensagens.'</span>';
					return true;
				}else{
					return false;
				}
			}
		}
	}

	public function envia($array, $assunto = NULL, $mensagem = NULL, $set = NULL){ # $set = 0 desliga o retorno de sucesso para envio
		// Inclui o arquivo class.phpmailer.php localizado na pasta phpmailer
		
		require_once("parametros.inc.php");
		$parametros_server 	= new Param();
		$email_settings 	= $parametros_server->emailConfig();
		$remetenteNome  	= $email_settings['remetenteNome'];
		$remetenteEmail 	= $email_settings['username'];
		if(isset($set)){ # $set indica configurações mais genéricas para mensagens diversas
			switch($set){
				case "0":
					$enviaFormularioParaNome 	= $array['fromaddress'];
					$enviaFormularioParaEmail 	= $array['destinatario'];
					$assunto			 		= "RE: ".$array['subject'];
					$mensagemConcatenada 		= "Olá! Essa é uma mensagem automática. <br> Informamos que a mensagem abaixo já foi enviada para tratativa em nosso sistema e logo entraremos em contato para atualizações.<br><hr> ".$array['body'];
					$this->sendMailNow($email_settings, $remetenteEmail, $remetenteNome, $enviaFormularioParaEmail, $enviaFormularioParaNome, $mensagemConcatenada, $assunto, $set);
				break;
				case "1":
					$destino = $array['emails_user'];
					foreach($destino as $value){
						$enviaFormularioParaNome 	= $value;
						$enviaFormularioParaEmail 	= $value;
						$assunto			 		= "Atualização em sua solicitação";
						$mensagemConcatenada 		= "Olá! Essa é uma mensagem automática. <br> Informamos que a tratativa abaixo já foi enviada para verificação em nosso sistema e logo entraremos em contato para atualizações.<br><hr> ".$array['historico'];
						$this->sendMailNow($email_settings, $remetenteEmail, $remetenteNome, $enviaFormularioParaEmail, $enviaFormularioParaNome, $mensagemConcatenada, $assunto, $set);
					}
				break;
			}
		}else{ # configurações específicas para e-mails de recuperação de senha e outras funções de sistema
			$dados = $array;
			$enviaFormularioParaNome = $dados['nome'];
			$enviaFormularioParaEmail = $dados['usuario'];			
			if(!isset($assunto)){
				$assunto  = "Recuperador de Senhas";
			}
			if(!isset($mensagem)){
				$mensagem = 'mensagem';
			}
			$mensagemConcatenada = 'Formulário gerado via website'.'<br/>';
			$mensagemConcatenada .= '-------------------------------<br/><br/>';
			$mensagemConcatenada .= 'Nome: '.$remetenteNome.'<br/>';
			$mensagemConcatenada .= 'E-mail: '.$remetenteEmail.'<br/>';
			$mensagemConcatenada .= 'Assunto: '.$assunto.'<br/>';
			$mensagemConcatenada .= '-------------------------------<br/><br/>';
			$mensagemConcatenada .= $mensagem.'"<br/>';
			$this->sendMailNow($email_settings, $remetenteEmail, $remetenteNome, $enviaFormularioParaEmail, $enviaFormularioParaNome, $mensagemConcatenada, $assunto, $set);
		}
	}

	public function sendMailNow($email_settings, $remetenteEmail, $remetenteNome, $enviaFormularioParaEmail, $enviaFormularioParaNome, $mensagemConcatenada, $assunto, $set = NULL){
		require_once("../assetsb/plugins/PHPMailer-master/PHPMailerAutoload.php");
		$mail = new PHPMailer();

		// Define os dados do servidor e tipo de conexão
		$mail->SMTPDebug = 0; // Debug para erros de conexão com PHPMailler
		$mail->IsSMTP(); // Define que a mensagem será SMTP
		$mail->Host 	= $email_settings['serverhost'];
		$mail->SMTPAuth = $email_settings['varSMTPAuth']; // Usar autenticação SMTP (obrigatório para smtp.seudomínio.com.br)
		$mail->Username = $email_settings['username']; // Usuário do servidor SMTP (endereço de email)
		$mail->Password = $email_settings['password']; // Senha do servidor SMTP (senha do email usado)

		// Define o remetente
		// =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=
		$mail->From = $remetenteEmail; // Seu e-mail
		$mail->Sender = $remetenteEmail; // Seu e-mail
		$mail->FromName = $remetenteNome; // Seu nome

		// Define os destinatário(s)
		// =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=
		$mail->AddAddress($enviaFormularioParaEmail,utf8_decode($enviaFormularioParaNome));
		//$mail->AddCC('ciclano@site.net', 'Ciclano'); // Copia
		//$mail->AddBCC('fulano@dominio.com.br', 'Fulano da Silva'); // Cópia Oculta

		// Define os dados técnicos da Mensagem
		// =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=
		$mail->IsHTML(true); // Define que o e-mail será enviado como HTML
		$mail->CharSet = $email_settings['charSet']; // Charset da mensagem (opcional)
		$mail->Post = $email_settings['port'];
		$mail->SMTPAutoTLS = false;
		// Define a mensagem (Texto e Assunto)
		// =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=
		$mail->Subject  = $assunto; // Assunto da mensagem
		$mail->Body = $mensagemConcatenada;

		// Define os anexos (opcional)
		// =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=
		//$mail->AddAttachment("/home/login/documento.pdf", "novo_nome.pdf");  // Insere um anexo

		// Envia o e-mail
		$enviado = $mail->Send();

		// Limpa os destinatários e os anexos
		$mail->ClearAllRecipients();
		$mail->ClearAttachments();

		if($set == 0 || $set == 1){ # Define se o sistema retorna ou não uma mensagem de sucesso para o envio do e-mail
			return true;
		}else{
			if ($enviado) {
				echo '
					<div class="alert alert-success fade in">
					<h4>Operação executada com sucesso.</h4>
					<p>Verifique o seu e-mail.<br>Clique no botão abaixo para fechar esta mensagem.</p>
					<p class="m-t-10">
					  <a type="button" class="btn btn-default waves-effect" data-dismiss="alert" href="javascript:history.go(-2);">Fechar</a>
					</p>
					</div>
					';
				return true;
			} else {
				echo '
					<div class="alert alert-danger fade in">
					<h4>Falha no processo.</h4>
					<p>Não foi possível enviar o e-mail.<br>
					';echo (extension_loaded('openssl')?'SSL está funcionando.':'SSL não foi carregado...')."<br>";
					echo "Informações do erro: " . $mail->ErrorInfo;
					echo '<br><br>Clique no botão abaixo para fechar esta mensagem.</p>
					<p class="m-t-10">
					  <a type="button" class="btn btn-default waves-effect" data-dismiss="alert" href="javascript:history.go(-2);">Fechar</a>
					</p>
					</div>
					';
				return false;
			}
		}
	}

	public function requisitaPagSeguro($parametros, $email){

		define("PAGSEGURO_TOKEN_PRODUCTION", "D9CCCD5E422641EA91F786FC6A536646");
		define("PAGSEGURO_APP_ID_PRODUCTION", "");
		define("PAGSEGURO_APP_KEY_PRODUCTION", "");
        define("PAGSEGURO_TOKEN_SANDBOX", "1BBC0C0AB15340B5AFAAFDDDAE2114F7");
		define("PAGSEGURO_APP_ID_SANDBOX", "app3685102155");
		define("PAGSEGURO_APP_KEY_SANDBOX", "471B95F68B8BBC388460FFA16E6B9866");
		define("MOEDA", "BRL");

		$url = "https://ws.sandbox.pagseguro.uol.com.br/v2/checkout -d\\ email=".$email."&token=".PAGSEGURO_TOKEN_SANDBOX."&currency=".MOEDA;
		$url .= $parametros;

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POST, true);
		#curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1); //O PagSeguro só irá aceitar a versão 1.1 do HTTP
		#curl_setopt($ch, CURLOPT_ENCODING, 'UTF-8');
		$dados = curl_exec($ch);
		curl_close($ch);
		if($dados == 'Unauthorized'){
			header('Location: ../view/pagFinaliza.php?tipo=autenticacao');
			#echo htmlentities($url, null, "UTF-8")."<br><br>";
			exit;
		}else if(count($dados -> error) > 0){

			header('Location: ../view/pagFinaliza.php?tipo=invalido&erro='.count($dados->error));
			exit;
		}else{
			$dados = simplexml_load_string($dados);
			header('Location: https://pagseguro.uol.com.br/v2/checkout/payment.html?code=' . $dados->code);
		}
	}

	public function setSender($dadosPessoais){
		$arrayRequisito[] = NULL;
		$arrayRequisito['senderName'] = $dadosPessoais['nome'];
		$arrayRequisito['senderAreaCode'] = $dadosPessoais['codareatelefone'];
		$arrayRequisito['senderPhone'] = $dadosPessoais['telefones'];
		$arrayRequisito['senderEmail'] = $dadosPessoais['usuario'];
		$arrayRequisito['shippingType'] = '1';
		$arrayRequisito['shippingAddressStreet'] = $dadosPessoais['endereco'];
		$arrayRequisito['shippingAddressNumber'] =   $dadosPessoais['numero'];
		$arrayRequisito['shippingAddressComplement'] =   $dadosPessoais['complemento'];
		$arrayRequisito['shippingAddressDistrict'] =   $dadosPessoais['bairro'];
		$arrayRequisito['shippingAddressCity'] =   $dadosPessoais['cidade'];
		$arrayRequisito['shippingAddressState'] =   $dadosPessoais['uf'];
		$arrayRequisito['shippingAddressPostalCode'] = $dadosPessoais['cep'];
		$arrayRequisito['shippingAddressCountry']= 'BRA';
		$arrayRequisito['redirectURL']= 'http://www.paisespeciais.com.br/view/pagFinaliza.php';
		$sender = "&";
		$sender .= http_build_query($arrayRequisito);
		return $sender;
	}

	public function arrayToCsv($input_array, $output_file_name){
		$f = fopen('../media/export/'.$output_file_name, 'w');
		foreach ($input_array as $line) {
			fputcsv($f, $line, ",", '"', " ");
		}
		fseek($f, 0);
		header('Content-Type: application/csv');
		header('Content-Disposition: attachement; filename="' . $output_file_name . '";');
		fpassthru($f);
	}

	public function protocolo($numero = NULL){
		if(is_null($numero)){
			$dt = new DateTime();				
			$alfa = $dt->format( "YmdHiu" );			
		}else{
			$alfa = $numero;
		}
		return $alfa;
	}
	
	public function moneyFormatReal($valor){
		$numero = "R$ ";
		$numero .= number_format($valor, 2 , ',' , '.' );	
		return $numero;
	}
	
	public function gravaAtendimento($dados){
		
		$query_entrada = "SELECT qntd_atendimentos FROM planos_movimentos WHERE id_contratos = '".$dados['id_contratos']."' AND data_limite >= now() AND lixo = 0";
		$woo = $this->queryFree($query_entrada);
		$entrada = $woo->fetch_assoc();
		/*
		O sistema busca pela quantidade de atendimentos do contrato via Call Center, mas não possui uma regra de negócio para o CGR.
		Atualmente, quando o retorno é vazio, isso pode significar que não existe um contrato ou ele apenas não possui uma ligação com o Call Center.
		*/
		if(empty($entrada['qntd_atendimentos'])){
			if(isset($dados["id_contratos"])){
				$query_busca_cliente_cgr = "SELECT * FROM clientes INNER JOIN contratos ON clientes.id = contratos.id_cliente WHERE contratos.id = '".$dados["id_contratos"]."' AND clientes.lixo = 0";
				$woo = $this->queryFree($query_busca_cliente_cgr);
				$cliente_cgr = $woo->fetch_assoc();
				/* print_r($cliente_cgr);
				echo "<hr>";
				print_r($dados);
				die(); */
				$this->add("pav_inscritos", $dados);
			}
		}else{//aqui será necessário incrementar o contador de atendimentos do cliente
			$query_movimentos = "SELECT * FROM planos_movimentos WHERE id_contratos = '".$dados['id_contratos']."' AND data_limite >= now() AND lixo = 0 ORDER BY qntd_atendimentos LIMIT 1"; 
			$woo = $this->queryFree($query_movimentos);
			$atend = $woo->fetch_assoc();
			$atual 	= intval($atend['atendimentos_atuais']); 
			$limite = intval($atend['qntd_atendimentos']);
			$valor 	= floatval($atend['vlr_nominal']);
			if($atual < $limite){
				$soma['atendimentos_atuais'] = $atual + 1;
				$this->upd("planos_movimentos", $soma, $atend['id']);
			}else{
				$query_movimentos = "SELECT * FROM planos_movimentos WHERE qntd_atendimentos > '".$atend['qntd_atendimentos']."' AND id_contratos = '".$dados['id_contratos']."' AND data_limite >= now() AND lixo = 0 ORDER BY qntd_atendimentos LIMIT 1"; 
				$woo = $this->queryFree($query_movimentos);
				$exced = $woo->fetch_assoc();
				if(empty($exced['id'])){
					$soma['atendimentos_atuais'] = $atual + 1;
//					$a->upd("planos_movimentos", $soma, $atend['id']);
				}else{
					$atual 	= intval($exced['atendimentos_atuais']); 
					$limite = intval($exced['qntd_atendimentos']);
					$valor 	= floatval($exced['vlr_nominal']);
					if($atual < $limite){						
						$soma['atendimentos_atuais'] = $atual + 1;
						$this->upd("planos_movimentos", $soma, $exced['id']);
					}
				}					
			}
		}		
	}
























	##################
	#
    # AUX FUNCTION TO NEW INTEGRATIONS
    #
    ##################

    /**
     * This method convert whole array to utf-8
     * @param $array
     * @return mixed
     */
    public function utf8_converter($array)
    {
        array_walk_recursive($array, function (&$item, $key) {
            if (!mb_detect_encoding($item, 'utf-8', true)) {
                $item = utf8_encode($item);
            }
        });
        return $array;
    }

    /**
     *
     * @param $bytes
     * @return string
     */
    private function formatSizeUnits($bytes)
    {
        if ($bytes >= 1073741824) {
            $bytes = round(number_format($bytes / 1073741824, 2)) . ' GB';
        } elseif ($bytes >= 1024000)//1048576
        {
            $bytes = round(number_format($bytes / 1048576, 2)) . ' MB';
        } elseif ($bytes >= 1024) {
            $bytes = round(number_format($bytes / 1024, 2)) . ' KB';
        } elseif ($bytes > 1) {
            $bytes = $bytes . ' bytes';
        } elseif ($bytes == 1) {
            $bytes = $bytes . ' byte';
        } else {
            $bytes = '0 bytes';
        }

        return $bytes;
    }

    /**
     * @param $data
     * @return string
     */
    public function maskCPFCNPJ($data)
    {
        $return = '';
        $data = $this->onlyInt($data);
        $numCharacter = strlen($data);

        if ($numCharacter == 20) {
            if (substr($data, 6, 1) == 0 && substr($data, 7, 1) == 0 && substr($data, 8, 1) == 0)
                $return = $this->mask(substr($data, -11), '###.###.###-##');
            else
                $return = $this->mask(substr($data, -14), '##.###.###/####-##');

        } else {
            if ($numCharacter <= 11) $return = $this->mask($data, '###.###.###-##');
            elseif ($numCharacter > 11) $return = $this->mask($data, '##.###.###/####-##');
        }
        return $return;
    }

    private function isJSON($string)
    {
        return is_string($string) && is_array(json_decode($string, true)) && (json_last_error() == JSON_ERROR_NONE) ? true : false;
    }

    /**
     * @param $url
     * @param $json
     * @param $action
     * @param string $apiKey
     * @param string $apiUser
     * @param string $apiUrl
     * @return mixed
     */
    public function curlWrap($url, $json, $action, $apiKey = '', $apiUser = '', $apiUrl = '', $headerType = 'json', $type = '')
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
        curl_setopt($ch, CURLOPT_URL, $apiUrl . $url);
        if ($apiKey != '' && $apiUser != '') curl_setopt($ch, CURLOPT_USERPWD, $apiUser . "/token:" . $apiKey);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        switch ($action) {
            case "POST":
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
                curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
                break;
            case "GET":
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
                break;
            case "PUT":
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
                curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
                break;
            case "DELETE":
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
                break;
            default:
                break;
        }

        $headers = array('Content-type: application/' . $headerType);
        if ($apiKey != '' && $type == 'SMARTMAPS') {
            $headers[] = 'validator: ' . $apiKey;
        }

        if ($headerType != '') curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_USERAGENT, "MozillaXYZ/1.0");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 600);
        $output = curl_exec($ch);
        if($output === false)
        {
            $error = 'Curl error output: ' . curl_error($ch).'<br>';
            curl_close($ch);
            return $error;
        }
        else
        {
            curl_close($ch);
            if ($this->isJSON($output))
                $decoded = json_decode($output);
            else
                $decoded = $output;
            return $decoded;
        }


    }

    /**
     * This function clear the data and return int
     * @param $data
     * @return int
     */
    private function onlyInt($data)
    {

        if (!is_array($data) && $data <> '')
            $data = preg_replace("/[^0-9]/", "", $data);
        else
            $data = 0;


        return trim($data);
    }

    public function object_to_array($obj)
    {
        $arr = array();
        $arrObj = is_object($obj) ? get_object_vars($obj) : $obj;
        foreach ($arrObj as $key => $val) {
            $val = (is_array($val) || is_object($val)) ? $this->object_to_array($val) : $val;
            $arr[$key] = $val;
        }
        return $arr;
    }

    /**
     * This function format any data.
     * You need to inform the mask that you need use #
     * Example phone mask (##) ####-####
     * @param $value
     * @param $mask
     * @return string
     */
    private function mask($value, $mask)
    {
        $masked = '';
        $k = 0;
        for ($i = 0; $i <= strlen($mask) - 1; $i++) {
            if ($mask[$i] == '#') {
                if (isset($value[$k]))
                    $masked .= $value[$k++];
            } else {
                if (isset($mask[$i]))
                    $masked .= $mask[$i];
            }
        }
        return $masked;
    }

    /**
     * This function format money for Brazil
     * @param $data
     * @return string
     */
    private function formatMoneyPTBR($data)
    {
        if (!isset($data) || $data == '') return '';
        else
            return 'R$' . number_format($data, 2, ',', '.');

    }

    /**
     * This function verify if has 10 or 11 chars and format
     * @param $number
     * @return string
     */
    private function maskPhone($number)
    {
        $return = $number;
        if (strpos($number, '0800') !== false) {
            $return = $this->mask($number, '####-########');
        } else {
            $len = strlen($number);
            if ($len == 10) $return = $this->mask($number, '(##) ####-####');
            else if ($len == 11) $return = $this->mask($number, '(##) #####-####');
            else if ($len == 5) $return = $number;
        }
        return $return;
    }

    /**
     * Format date the way you want. Pass date in format datetime and mask according PHP
     * @param $datetime
     * @param string $format
     * @return bool|string
     */
    private function formatDateTime($datetime, $format = 'd/m/Y H:i:s')
    {
        if ($datetime == '') return '';
        else
            return date_format(date_create($datetime), $format);
    }

    /**
     * Process data to display on screen
     * @param $data
     * @param int $idLog
     * @return mixed
     */
    private function processData($data, $idLog = 0)
    {
        $cont = 0;

        foreach ($data as $values) {
            foreach ($values as $k => $v) {
                switch ($k) {
                    case 'subscribers_phone':
                    case 'subscribers_phone2':
                    case 'subscribers_cellphone':
                    case 'subscribers_cellphone2':
                    case 'subscribers_phone_smart':
                    case 'subscribers_phone_smart2':
                        $data[$cont][$k] = $this->maskPhone($v);
                        break;
                    case 'subscribers_birthday':
                        $data[$cont][$k] = $this->formatDateTime($v, 'd/m/Y');
                        break;

                    case 'subscribers_cpfcnpj':
                        $data[$cont]['subscribers_cpfcnpj_formatted'] = $this->maskCPFCNPJ($v);
                        $data[$cont]['subscribers_cpfcnpj'] = $this->maskCPFCNPJ($v);
                        break;

                    /*
                    case 'subscribers_person_type':
                        if($v == 'F')
                            $data[$cont]['subscribers_cpfcnpj_formatted'] = $this->aux->maskCPFCNPJ($data[$cont]['subscribers_cpfcnpj']);
                        else if($v == 'J')
                            $data[$cont]['subscribers_cpfcnpj_formatted'] = $this->aux->maskCPFCNPJ($data[$cont]['subscribers_cpfcnpj']);
                        else
                            $data[$cont]['subscribers_cpfcnpj_formatted'] = $data[$cont]['subscribers_cpfcnpj'];
                        break
                    */
                    case 'subscribers_updated':
                        $data[$cont]['subscribers_updated_formatted'] = $this->formatDateTime($data[$cont]['subscribers_updated']);
                        break;
                    case 'subscribers_add_zipcode':
                        $data[$cont][$k] = $this->mask($v, '#####-###');
                        break;
                    case 'subscribers_technology_type':
//                        $data[$cont][$k] = $this->setNameOfTechnologyType($v);
                        break;
                    case 'subscribers_franchise':
                        if ($v == 'S') $data[$cont][$k] = '<div class="label label-success"> Sim </div>';
                        else if ($v == 'N') $data[$cont][$k] = '<div class="label label-danger"> Não </div>';
                        break;
                    case 'subscribers_reduced_speed_allow':
                        if ($v == 'S') $data[$cont][$k] = '<div class="label label-success"> Sim </div>';
                        else if ($v == 'N') $data[$cont][$k] = '<div class="label label-danger"> Não </div>';
                        break;
                    case 'subscribers_plan_upload':
                    case 'subscribers_plan_download':
                        $data[$cont][$k] = $this->formatSizeUnits($v * 1024);
                        break;
                    case 'subscribers_franchise_value':
                        $data[$cont][$k] = $this->formatSizeUnits($v);
                        break;
                    case 'subscribers_authentication':
//                        if ($v != '')
//                            $data[$cont]['subscribers_authentication'] = $this->setNameOfAuthenticationMK($data[$cont]['subscribers_authentication']);
//                        else
//                            $data[$cont]['subscribers_authentication'] = '';
                        break;
                    case 'subscribers_connection_blocked':
                        if ($v == 'S') $data[$cont]['subscribers_connection_blocked_formatted'] = '<div class="label label-danger"> Inativo</div>';
                        else $data[$cont]['subscribers_connection_blocked_formatted'] = '<div class="label label-success"> Ativo </div>';
                        break;
                    case 'subscribers_blocking_reason':
                        if ($data[$cont]['subscribers_connection_blocked'] == 'S') {
                            if ($v == '') $data[$cont]['subscribers_blocking_reason_formatted'] = '<div class="label label-warning"> Inativo / Pendente</div>';
                            else  $data[$cont]['subscribers_blocking_reason_formatted'] = '<div class="label label-warning"> ' . ucwords(strtolower($data[$cont]['subscribers_blocking_reason'])) . '</div>';
                        } else {
                            if ($v == '') $data[$cont]['subscribers_blocking_reason_formatted'] = '<div class="label label-success"> Liberado</div>';
                            else  $data[$cont]['subscribers_blocking_reason_formatted'] = '<div class="label label-warning"> ' . ucwords(strtolower($data[$cont]['subscribers_blocking_reason'])) . '</div>';
                        }
                        break;

                    case 'subscribers_plan_price':
                        $data[$cont][$k] = $this->formatMoneyPTBR($v);
                        break;
                    case 'subscribers_service_type':
                        if ($v == 1) $data[$cont][$k] = 'Internet';
                        elseif ($v == 2) $data[$cont][$k] = 'Telefonia';
                        else  $data[$cont][$k] = 'TV';
                        break;
                }
            }
            if ($idLog != 0) $data[$cont]['idLog'] = $idLog;

            $cont++;
        }

        return $data;
    }

	###################

    ####################################
    # FUNCTIONS OF MK SOLUTIONS
    #####################################
    # DEFAULT COMMUNICATION
    # DB POSTGREE
    # PORT DEFAULT
    ######################

    #todo Adan para integração com MK solutions temos algumas particularidades no qual precisaremos de acrescentar alguns campos tanto para buscar os dados de conexão quanto variação de empresas
    #todo Adan a buscar por nome pode ser bem perigosa e honerosa para integração tem necessidade de fazermos isso mesmo? Em alguns teste demorou basnte a busca por este campo
    /**
     * @param $conn
     * @param $name
     * @param $identify
     * @param $cod
     * @param $option
     * @return string
     */
    public function MKSolutionDefaultSearch($conn, $name, $identify, $cod, $option)
    {
        $querySql = '';
        if ($option == 1 )
            $querySql = " p.classificacao = 1  AND ( con.cancelado = 'N' OR c.conexao_bloqueada = 'N' )";
        if ($option == 2)
        {
            $querySqlOP21 = '';
            $querySqlOP22 = '';
            if($identify != '') {
                $querySqlOP21 = " (p.cpf = '" . $this->maskCPFCNPJ($identify) . "' 
                OR p.cnpj = '" . $this->maskCPFCNPJ($identify) . "' 
                OR p.cpf = '" . $this->onlyInt($this->maskCPFCNPJ($identify)) . "' 
                OR p.cnpj = '" . $this->onlyInt($this->maskCPFCNPJ($identify)) . "') ";
            }

            if($name!='')
            {
                $uc = ucfirst($name);
                $lo = strtolower($name);
                $up = strtoupper($name);
                if($querySqlOP21 == '')$querySqlOP22 = " p.nome_razaosocial LIKE '%$uc%' OR p.nome_razaosocial LIKE '%$lo%' OR p.nome_razaosocial LIKE '%$up%'  "; else $querySqlOP22 = " OR p.nome_razaosocial LIKE '%$uc%' OR p.nome_razaosocial LIKE '%$lo%'  OR p.nome_razaosocial LIKE '%$up%'";
            }

            $querySql = $querySqlOP21.$querySqlOP22;
        }

        if ($option == 3)
            $querySql = ' p.codpessoa > ' . $this->onlyInt($cod);

        if (isset($conn['codigo_multi_empresas']) && $conn['codigo_multi_empresas'] != 0 && $conn['codigo_multi_empresas'] != '')
            $querySql .= " AND p.cd_empresa = " . $conn['codigo_multi_empresas'];

        $query = "SELECT 
                    DISTINCT p.codpessoa as subscribers_refer_id,
                    p.nome_razaosocial as subscribers_name,
                    COALESCE(p.cpf, p.cnpj, '') as subscribers_cpfcnpj,
                    COALESCE(p.ie, p.rg, '') as subscribers_rgie,
                    p.nascimento as subscribers_birthday,
                    p.cep as subscribers_add_zipcode,
                    l.logradouro as subscribers_add_street,
                    p.numero as subscribers_add_number,
                    p.complementoendereco as subscribers_add_desc,
                    b.bairro as subscribers_add_neibor,
                    ci.cidade as subscribers_add_city,
                    e.nomeestado as subscribers_add_state,
                    p.cepcobranca as subscribers_add_zipcodec,
                    ll.logradouro as subscribers_add_streetc,
                    p.numerocobranca as subscribers_add_numberc,
                    p.complementoenderecocobr as subscribers_add_descc,
                    bb.bairro as subscribers_add_neiborc,
                    cii.cidade as subscribers_add_cityc,
                    ee.nomeestado as subscribers_add_statec,
                    p.email as subscribers_email,
                    p.fone01 as subscribers_phone,
                    p.fone02 as subscribers_phone2,
                    pa.ssid as subscribers_access_point,
                    c.username as subscribers_username,
                    c.password as subscribers_password,
                    p.user_sac as subscribers_sac_username,
                    p.pass_sac as subscribers_sac_password,
                    c.nasipaddress as subscribers_nasipaddress,
                    c.codconexao as subscribers_plan_id,
                    c.tecnologia as subscribers_technology_type,
                    c.em_velocidade_temporaria as subscribers_reduced_speed_allow,
                    c.velocidade_temporaria as subscribers_reduced_speed,
                    c.franquia_ativa as subscribers_franchise,
                    s.descricao as subscribers_server,
                    pla.descricao as subscribers_plan,
                    pla.vel_down as subscribers_plan_download,
                    pla.vel_up as subscribers_plan_upload,
                    pla.vlr_mensalidade as subscribers_plan_price,
                    1 as subscribers_service_type,
                    c.autenticacao as subscribers_authentication,
                    c.conexao_bloqueada as subscribers_connection_blocked,
                    mb.descricao as subscribers_blocking_reason,
                    c.mac_address_considerado as subscribers_mac_address,
                    c.auto_desbloqueio as subscribers_auto_unlock 
                    FROM mk_pessoas p
                    LEFT JOIN mk_logradouros l ON p.codlogradouro = l.codlogradouro
                    LEFT JOIN mk_bairros b ON b.codbairro = p.codbairro
                    LEFT JOIN mk_cidades ci ON ci.codcidade = p.codcidade
                    LEFT JOIN mk_estados e ON e.codestado = p.codestado
                    LEFT JOIN mk_logradouros ll ON p.codlogradourocobranca = ll.codlogradouro
                    LEFT JOIN mk_bairros bb ON bb.codbairro = p.codbairrocobranca
                    LEFT JOIN mk_cidades cii ON cii.codcidade = p.codcidadecobranca
                    LEFT JOIN mk_estados ee ON ee.codestado = p.codestadocobranca
                    LEFT JOIN mk_conexoes c ON c.codcliente = p.codpessoa
                    LEFT JOIN mk_pontos_acesso pa ON c.codponto_acesso = pa.codpontoacesso
                    LEFT JOIN mk_contratos con ON con.cliente = p.codpessoa
                    LEFT JOIN mk_faturamentos_regras fr ON fr.codfaturamentoregra = con.cd_regra_faturamento
                    LEFT JOIN mk_servidores s ON s.codmovimento = c.codservidor
                    LEFT JOIN mk_planos_acesso pla ON pla.codplano = c.codplano_acesso
                    LEFT JOIN mk_motivos_bloqueio mb ON mb.codmotbloq = c.motivo_bloqueio
                    WHERE ".$querySql."
                    
                    ";
//,
//                    ".($conn['nome_bd'] == 'mkData' ? 'fr.periodo1_vencimento as subscribers_due_date' : 'fr.dia_vcto as subscribers_due_date').
        return $query;

    }

    /**
     * This method get data of connection of subscribers
     * @param $conn
     * @param $username
     * @return string
     */
    public function MKSolutionRadiusData($conn, $username)
    {

        if($conn['radius_host']!='')
        $query = /** @lang text */
            "SELECT
                    nasipaddress as nas_ip, 
                    calledstationid as nas_name, 
                    framedipaddress as user_ip,
                    nasportidname as port_name,
                    callingstationid as mac_calling, 
                    acctinputoctets as upload, 
                    acctoutputoctets  as download,
                    acctstarttime as begin, 
                    acctstoptime as end, 
                    acctuniqueid as session, 
                    acctterminatecause as terminate_cause 
                    WHERE username = '$username'
                    FROM radius.radacct";
        else
            $query = /** @lang text */ "SELECT
                    nasipaddress as nas_ip, 
                    calledstationid as nas_name, 
                    framedipaddress as user_ip,
                    nasportidname as port_name,
                    callingstationid as mac_calling, 
                    acctinputoctets as upload, 
                    acctoutputoctets  as download,
                    acctstarttime as begin, 
                    acctstoptime as end, 
                    acctuniqueid as session, 
                    acctterminatecause as terminate_cause 
                    WHERE username = '$username'
                    FROM radacct";

        return $query;
    }


    //TODO: Adan é necessario instalar o pacote de SoapClient no servidor para executar esta função
    /**
     * This method get all bills of MK customers
     * @param $ip
     * @param $userSAC
     * @param $passSAC
     * $dbName can be mkData or mkData3.0 for example
     * $referId = codClient MK
     * @return mixed
     */
    public function MKSolutionGetBills($ip, $userSAC, $passSAC, $portAPI, $dbName = '', $referId = 0, $tokenMkData = '')
    {
        $return = array();
        if ($portAPI == '') $port = '8080'; else $port = $portAPI;

        if ($referId != 0 && $tokenMkData != '' && $tokenMkData != '0') { // NOT MK 3.0

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, "http://$ip:$port/mk/WSSacCobrancasIntegrado.rule?sys=MK0&token=" . $tokenMkData . "&cd_cliente=" . $referId . "");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $output = curl_exec($ch);
            $output = utf8_encode($output);
            $output = json_decode($output, true);
            if (isset($output['cobrancas'])) {
                $numCobrancas = count($output['cobrancas']);
                $aux = 0;
                for ($count = 0; $count < $numCobrancas; $count++) {
                    $return[$aux][0] = $output['cobrancas'][$count]['fatura'];
                    $return[$aux][1] = $output['cobrancas'][$count]['nosso_numero'];
                    $return[$aux][2] = $output['cobrancas'][$count]['vencimento'];
                    $return[$aux][3] = $output['cobrancas'][$count]['descricao'];;
                    $return[$aux][4] = $this->formatMoneyPTBR($output['cobrancas'][$count]['valor']);
                    $return[$aux][5] = $output['cobrancas'][$count]['valor'];
                    $return[$aux][6] = $this->formatMoneyPTBR($output['cobrancas'][$count]['valor_acrescimo']);
                    $return[$aux][7] = $output['cobrancas'][$count]['valor_acrescimo'];
                    $return[$aux][8] = $output['cobrancas'][$count]['registrada'];
                    $aux++;
                }
            }
            curl_close($ch);

        } else { // MK 3.0

            $soap = new SoapClient("http://$ip:$port/mk/webservices/MK0Services.jws?wsdl");
            $data = $soap->SacWsContas($userSAC, $passSAC);
            if (isset($data[3])) {
                $explode = explode('*/', $data[3]);
                for ($cont = 0; $cont < count($explode); $cont++) {
                    if ($explode[$cont] != '')
                        $return[$cont] = explode(';', $explode[$cont]);
                }
            }

        }
        return $return;
    }






    ####################################
    # FUNCTIONS OF ISP INTEGRATOR
    #####################################
    # DEFAULT COMMUNICATION
    # DB MYSQL
    # PORT DEFAULT
    ######################

    public function ISPIntegratorDefaultSearch($conn, $name, $identify, $cod, $option)
    {
        $querySql = '';
        if ($option == 1 )
            $querySql = " WHERE status_plano_cliente != 'Cancelado' AND  status_plano_cliente != 'Inativo'";
        if ($option == 2)
            $querySql = "WHERE cpf_cnpj_cliente = '" . $this->maskCPFCNPJ($identify) . "' OR nome_cliente LIKE %$name%";
        if ($option == 3)
            $querySql = ' WHERE codigo_cliente  > ' . $this->onlyInt($cod). " AND status_plano_cliente != 'Cancelado'";

        $query = "SELECT 
                    DISTINCT codigo_cliente as subscribers_refer_id,
                    nome_cliente as subscribers_name,
                    codigo_plano_cliente as subscribers_plan_id,
                    subscribers_birthday,
                    cpf_cnpj_cliente as subscribers_cpfcnpj,
                    telefone_cliente as subscribers_phone,
                    celular_cliente as subscribers_cellphone,
                    emails_cliente as subscribers_email,
                    endereco_instalacao_plano_cliente as subscribers_add_street,
                    NULL as subscribers_add_number,
                    complemento_instalacao_plano_cliente as subscribers_add_desc,
                    bairro_instalacao_plano_cliente as subscribers_add_neibor,
                    cep_instalacao_plano_cliente as subscribers_add_zipcode,
                    cidade_instalacao_plano_cliente as subscribers_add_city,
                    estado_instalacao_plano_cliente as subscribers_add_state,
                    endereco_cobranca_plano_cliente as subscribers_add_streetc,
                    NULL as subscribers_add_numberc,
                    complemento_cobranca_plano_cliente as subscribers_add_descc,
                    bairro_cobranca_plano_cliente as subscribers_add_neiborc,
                    cep_cobranca_plano_cliente as subscribers_add_zipcodec,
                    cidade_cobranca_plano_cliente as subscribers_add_cityc,
                    estado_instalacao_plano_cliente as subscribers_add_statec,
                    plano_cliente as subscribers_plan,
                    valor_plano_cliente as subscribers_plan_price,
                    status_plano_cliente as subscribers_connection_blocked,
                    status_plano_cliente as subscribers_blocking_reason,
                    login_autenticao as subscribers_username,
                    senha_autenticao as subscribers_password,
                    mac_autenticacao as subscribers_mac_address,
                    velocidade_autenticacao as subscribers_plan_download,
                    ponto_acesso_plano_cliente as subscribers_server,
                    numero_assinante as subscribers_contract_numbers,
                    plano_internet as subscribers_service_type,
                    plano_telefonia as subscribers_service_type2,
                    3 as subscribers_authentication,
                    subscribers_franchise,
                    subscribers_nasipaddress,
                    subscribers_technology_type ".$querySql;

        return $query;

    }

    public function ISPIntegrator($conn, $username)
    {
        //NOT HAS THIS INFORMATION
    }

    public function ISPIntegratorGetBills($conn, $id)
    {

        $query = "
            SELECT 
            'DISTINCT nosso_numero   as subscribers_financial_number,
            id_cobranca             as subscribers_financial_cod,
            data_vencimento         as subscribers_financial_due_at,
            data_pagamento          as subscribers_financial_pay_at,
            valor_total             as subscribers_financial_price,
            pago                    as subscribers_financial_status
            WHERE id_cliente = $id  
            AAND data_vencimento BETWEEN ". date('Y') . "-01-01 AND ". date('Y') ."-12-31 ORDER BY id_cobranca DESC LIMIT 12'
            FROM radiusnet.cobranca";

        return $query;
    }

    /**
     * @param $val
     * @return string
     */
    public function typeDriverSystem($val)
    {
        switch ($val)
        {
            case "hubsoft":
                $return = 'Hub Soft';
                break;
            case "mk_solution":
                $return = 'MK Solution';
                break;
            case "isp_integrator":
                $return = 'ISP INTEGRATOR';
                break;
            case "ixc_software":
                $return = 'IXC SOFTWARE';
                break;
            case "sgp":
                $return = 'SGP';
                break;
            default:
                $return  = 'Não foi definido';
                break;
        }
        return $return;
    }
//------------------------inicio-----------------------------------------------------------------------------	
	// ---- Classes para conexão com banco de dados "radius" ----------------------------------------------

	public function queryFreeRadius($query){
		#by Adan, 04 de junho de 2015.
		global $mysqliRadius;
		global $resultRadius;

		if(!isset($query)){
			echo("Segue abaixo o valor da query enviada:<br>".$query);
		}else{
		global $mysqliRadius;
			$resultRadius = $mysqliRadius->query($query);
		}
		if(!is_null($resultRadius)){
			return $resultRadius;
		}
	}
	

	//Função para inserção de dados no banco de dados radius
	function add_BD_Radius($tabela, $array){
		#by Adan, 05 de junho de 2015.
		global $mysqliRadius;
		$count 	= 1;
		$coluna = NULL;
		$valor 	= NULL;
		foreach($array as $key=>$value){
			$coluna .= $key;
			$valor  .= "'".$value."'";
			if($count < sizeof($array)){
				$coluna .= ", ";
				$valor  .= ", ";
			}
			$count++;
		}
		#echo "INSERT INTO $tabela ($coluna) VALUES($valor)<br>";
		$mysqliRadius->query("INSERT INTO $tabela ($coluna) VALUES($valor)");
		if ($mysqliRadius->affected_rows > 0) {
			//$_SESSION['ult_id'] = $mysqliRadius->insert_id;
			return true;
		}
		else{
			return false;
		} 
	}
	//Função para alteração de dados no banco de dados radius
	public function updateRadius($tabela,$username, $value, $id){
		global $mysqliRadius;

		$mysqliRadius->query("UPDATE $tabela SET username = '".$username."', `value` = '".$value."' WHERE id = '$id'");

		return true;
	}
	//Função para inserção de dados no banco de dados radius
	public function addRadius($tabela,$username, $value){
		global $mysqliRadius;

		$mysqliRadius->query("INSERT INTO `".$tabela."` ( `username`, `value`) VALUES ('".$username."', '".$value."')");
		
		return true;
	}
	//Função para deletar dados no banco de dados radius
	public function deleteRadius($tabela,$id){
		global $mysqliRadius;

		$mysqliRadius->query("DELETE FROM `".$tabela."` WHERE `id` = ".$id);
		
		return true;
	}
	// ---- Classes para conexão com banco de dados "radius" ----------------------------------------------
	//------------------------fim-----------------------------------------------------------------------------	
}