<?php
include("../model.inc.php");
include("../actions.inc.php");
include("../logs.inc.php");
include("../conexao.inc.php");
if($_SESSION ['datalogin']['id']){
	$session_id_user = $_SESSION ['datalogin']['id'];
	$datalogin = $_SESSION['datalogin'];
}else{
	echo '<div class="col-lg-3 col-md-6 col-sm-6">
				<div class="card ">
					<div class="card-header align="center">
					<h4 class="card-title"> Aten&ccedil;&atilde;o sua Sess&atilde;o expirou!
					</h4>
					Ser&aacute; nescess&aacute;rio fazer login novamente.
					</div>
					<div class="card-body ">
					<a href="pages-login.php">
					<button class="btn btn-warning" id="btnLogar" >
							Fazer Login
					</button>
					</a>
					</div>
				</div>	
			</div>';
	die();
}
if(!empty($_POST)){
	switch($_POST['flag']){
		
		case "consulta-equal":
			$value   = $_POST['value'];
			$tabela  = $_POST['tabela'];
			$campo	 = $_POST['campo'];
			$a = new Model();
			$a->queryFree("SELECT * FROM $tabela WHERE lixo = 0 AND $campo = '$value'");
			echo $result->num_rows;			
		break;
		
		case "consulta-equal-ultimos-atendimentos":
			$a 	 = new Model();
			$cpf_cnpj = $_POST['value'];
			if($cpf_cnpj != ''){
				$cgr_query = "SELECT COUNT(id) AS id FROM pav_inscritos 
				WHERE cpf_cnpj_cliente = '$cpf_cnpj' AND finalizado != 1 AND lixo = 0";
				$teste = $a->queryFree($cgr_query);
				$retorno = $teste->fetch_assoc();
				$valor = $retorno['id'];
				if($valor > 0){					
					echo $valor;
				}else{
					echo "0";
				}
			}else{
				return false;
			}
		break;
		
		case "consulta-ultimosAtendimentos":
			$log = new Logs(); 
			$cpf_cnpj = $_POST['cpf'];
			return $log->ultimosAtendimentos($cpf_cnpj);
		break;
		
		case "consulta-assunto-macro":
			$a = new Model();
			$id   = $_POST['id'];
			$query_assunto = "SELECT * FROM assuntos WHERE lixo = 0 AND id = $id";	
			$teste = $a->queryFree($query_assunto);
			$retorno = $teste->fetch_assoc();
			echo htmlspecialchars_decode($retorno['macro']);
		break;
		
		case "existeUser":
			$dados = $_POST;
			$tabela = $dados["tbl"];
			$a = new Model();
			$a->queryFree("SELECT * FROM $tabela WHERE lixo = 0 AND usuario = '".$dados['usuario']."'");
			#print_r($result);
			if ($result->num_rows > 0) { 
				echo "<span style='color:red;'>E-mail já cadastrado!</span>"; 
			}
		break;
		case "add":		#Código de Inclusão
			$dados = $_POST;
			$tabela = $dados["tbl"];
			#print_r($dados); print_r($_FILES); die();
			$a 	 = new Model();
			$act = new Acoes();			
			# Cancelamento do lock na saída dos registros -> Adan 02/06/2019
			if(isset($dados['tbl'])){
				if($dados['tbl'] == 'pav_inscritos'){
					if(isset($dados['id'])){
						$query_disable_lock = "UPDATE pav_inscritos SET user_auth = NULL WHERE id = '".$dados['id']."'";
						$a->queryFree($query_disable_lock);
						#echo $query_disable_lock;
					}
				}
			}			
			
			if(isset($dados["senha"])){
				if($dados["senha"]!=""){
					$dados['senha'] = md5($dados['senha']);	
				}	
			}
			if(isset($_FILES)){
				$pic = $_FILES;
				$vetor = NULL;
				if(isset($_FILES['anexo_scripts'])){
					$anexo = $_FILES['anexo_scripts'];
					$media = $act->anexos($anexo);
					if($media == false){
						print_r($media);
						die();
					}else{
						$dados['anexo_scripts'] = $media['name'];
						unset($_FILES);
					}
				}else{
					if(!empty($pic["file"])){
						$nomeFile = $pic["file"]["name"];
						$vetor = "file";
					}else if(!empty($pic["foto"])){
						$nomeFile = $pic['foto']["name"];
						$vetor = "foto";
					}else if(!empty($pic['media'])){
						$nomeFile = $pic['media']["name"];
						$vetor = "media";
					}else if(!empty($pic['imagem'])){
						$nomeFile = $pic['imagem']["name"];
						$vetor = "imagem";
					}
					if(isset($_FILES[$vetor])){
						if($_FILES[$vetor]['error']!=0){
							unset($_FILES);
						}else{				
							if(isset($nomeFile)){
								$media = $a->addFoto($nomeFile, $vetor, $tabela);								
								if(isset($media)){
									$dados[$vetor] = $media['name'];
								}
							}
						}	
					}	
				}
			}
			
			if(isset($dados["valor_unit"])){
				$dados["valor_unit"] = str_replace(',','.',str_replace('.','',$dados["valor_unit"]));
			}
			
			if(isset($dados["historico"])){
				$subs_historico = addslashes($dados["historico"]);
				$dados['historico'] = $subs_historico;
			}			
			if(isset($dados["endereco_cliente_ins"])){
				$correct = addslashes($dados["endereco_cliente_ins"]);
				$dados['endereco_cliente_ins'] = $correct;
			}
			if(isset($dados["endereco_cliente_cad"])){
				$correct = addslashes($dados["endereco_cliente_cad"]);
				$dados['endereco_cliente_cad'] = $correct;
			}
			if(isset($dados["endereco_cliente_fin"])){
				$correct = addslashes($dados["endereco_cliente_fin"]);
				$dados['endereco_cliente_fin'] = $correct;
			}
			if(isset($dados["endereco_cliente_cob"])){
				$correct = addslashes($dados["endereco_cliente_cob"]);
				$dados['endereco_cliente_cob'] = $correct;
			}
			
			if(isset($dados["hora_add"])){
				unset($dados['hora_add']);
				if(isset($_SESSION['data_abertura_tma'])){
					$dados["data_abertura"] = $_SESSION['data_abertura_tma'];
					unset($_SESSION['data_abertura_tma']);
				}else{
					$dados["data_abertura"] = date("Y-m-d H:i:s");					
				}
				# ---------------------- Determinar data de expectativa ---------------------------- #
				if(isset($dados["assunto"])){				
					$query_assunto = "SELECT * FROM assuntos WHERE id = $dados[assunto]";
					$select_assunto = $a->queryFree($query_assunto);
					if($select_assunto->num_rows > 0){
						$tempo = $select_assunto->fetch_assoc();
						$data = $dados["data_abertura"]; // Data e hora que o atendimento começou
						$duracao = $tempo["solution_time"]; // Tempo esperado para finalizar o atendimento
						$v = explode(':', $duracao);
						$var_data = date('Y-m-d H:i:s', strtotime("{$data} + {$v[0]} hours {$v[1]} minutes {$v[2]} seconds"));
						$dados["data_expectativa_termino"] = $var_data;
					}else{
						echo $query_assunto;
					}					
				}
				# -------------------- Fim determinar data de expectativa -------------------------- #
				if(isset($dados["protocol"])){ # No caso de atendimentos com protocolo inicial serem informados
					$dados["protocol"] = $a->protocolo($dados["protocol"]);
					
				}else{ # Caso contrário o protocolo é gerado ao fim da movimentação
					$dados["protocol"] = $a->protocolo();
				}
				#$dados["autor"] = $dados['atendente_responsavel'];
			}
			
			if(isset($dados['auditoria'])){
				if(isset($dados["emails_user"])){ # caso o cliente não tenha cadastrado grupos de auditoria
					$a->envia($dados, NULL, NULL, $set = 1);
				}else{
					if(isset($dados['id_grupo'])){ # usuários encontrados
						global $mysqli;
						$usuarios_grupo = $dados['user_id'];
						if(is_array($usuarios_grupo) || is_object($usuarios_grupo)){
							foreach($usuarios_grupo as $value){
								
								$query_comunica = "INSERT INTO comunicacao_interna_movimentos (protocol, descricao, id_autor, id_destinatario, nome_provedor, data, id_contratos, atendente_responsavel) VALUES('".$dados['protocol']."', '".$dados['historico']."', '".$dados['atendente_responsavel']."', '".$value."', '".$dados['nome_provedor']."', '".date("Y-m-d H:i:s")."', '".$dados['id_contratos']."', '".$value."')";	
								#echo $query_comunica;die();
								$mysqli->query($query_comunica);
								$id_comunica = $mysqli->insert_id; # captura-se o id da movimentação
								$query_comunica_interna_contatos = "INSERT INTO comunicacao_interna_contatos (id_comunicacao_interna, id_usuarios_clientes) VALUES('".$id_comunica."', '".$dados['id_contratos']."')";
								$a->queryFree($query_comunica_interna_contatos);
							}
						}else{
							echo "<div class='alert alert-danger'><p>Falha no processamento da rotina. Erro: #0081.<br>Entre em contato com o suporte.</p></div>";die();
						}
					}
					unset($dados['emails_user'],$dados['auditoria'],$dados['user_id']);	
				}
			}
			
			if(isset($dados["despachar-cliente"])){				
				if(!empty($dados["emails_user"])){ # caso o usuário limpe o input o sistema não envia e-mail
					$a->envia($dados, NULL, NULL, $set = 1);
				}
				if(isset($dados['id_contratos'])){ # O número de contrato que identificam seus registros na tabela comunicacao_interna_contatos					
					global $mysqli;
					#print_r($dados);die();
					if(isset($dados['grupos'])){
						$grupos_cliente  = $dados['grupos'];
						foreach($grupos_cliente as $value){
							# ----------- Inserção dos dados de comunicação para inscritos nos grupos ----------- #
							$query_select_integrantes = "SELECT * FROM group_user WHERE group_id = $value";
							$select_integrantes = $a->queryFree($query_select_integrantes);
							while($integrantes = $select_integrantes->fetch_assoc()){
								$query_comunica = "INSERT INTO comunicacao_interna_movimentos (protocol, descricao, id_autor, id_destinatario, nome_provedor, data, id_contratos, atendente_responsavel) 
								VALUES('".$dados['protocol']."', '".$dados['historico']."', '".$dados['autor']."', '".$integrantes['user_id']."', '".$dados['nome_provedor']."', '".date("Y-m-d H:i:s")."', '".$dados['id_contratos']."', '".$dados['atendente_responsavel']."')";	
								$mysqli->query($query_comunica);		
								$id_comunica = $mysqli->insert_id; # captura-se o id da movimentação
								$query_comunica_interna_contatos = "INSERT INTO comunicacao_interna_contatos (id_comunicacao_interna, id_usuarios_clientes) VALUES('".$id_comunica."', '".$dados['id_contratos']."')";
								$a->queryFree($query_comunica_interna_contatos);	
							}
						}
					}
					if($dados['atendente_responsavel'] != 0){
						$query_comunica = "INSERT INTO comunicacao_interna_movimentos (protocol, descricao, id_autor, id_destinatario, nome_provedor, data, id_contratos, atendente_responsavel) 
						VALUES ('$dados[protocol]', '$dados[historico]', '$dados[autor]', '$dados[atendente_responsavel]', '$dados[nome_provedor]', '$dados[data_abertura]', '$dados[id_contratos]', '$dados[atendente_responsavel]')";	
						$mysqli->query($query_comunica);	
					}						
				}
				unset($dados['emails_user'],$dados['despachar-cliente'],$dados['destination'], $dados['grupos'], $dados['grupo_responsavel']);					
			}
			
      if(isset($dados['grupo_responsavel'])){
        $array_gr = $dados['grupo_responsavel'];
        unset($dados['grupo_responsavel']);
      }
     
			if(isset($dados['contatos'])){//campo de clientes que permite inserção dos contatos de e-mail
        if(isset($dados['flag_agenda_contatos'])){ # Rotina de inclusão de contato para provedores desconhecidos da captura de e-mail
          unset($dados['flag_agenda_contatos']);
        }else{
            if($dados['contatos'] != ''){
              $contatos = $dados['contatos'];					
              $array_contatos = explode(",", $contatos);
            }else{
              $contatos = $dados['usuario'];	
            }
            unset($dados['contatos']);
        }
			}
			
			if(isset($dados['id_contratos'])){
				if($dados['id_contratos']==0){//inclusão de comunicado interno sem atribuição de clientes
					//nenhum atendimento é computado: tratar posteriormente com retorno de erro
				}else{
					if(isset($dados["idd"])){
						if($dados["idd"] == "solucionado")	{
							$dados["validado"] 	= '1';
							$dados["status"]	= '2';
						}
						unset($dados['idd']);
						$a->gravaAtendimento($dados);
						#unset($dados['id_contratos']);
					}else{
						$a->gravaAtendimento($dados);
						# Verificar valor do contrato para fazer a 
					}
				}
				unset($dados['idd']);
			}
			
			if(isset($dados['id_contatos'])){
				if(is_array($dados['id_contatos'])){
					foreach($dados['id_contatos'] as $value_id_contato ){
						# Cada contato recebe uma cópia da mensagem (relação N - N)
						global $mysqli;
						$query_comunica = "INSERT INTO comunicacao_interna_movimentos (protocol, descricao, id_autor, id_destinatario, nome_provedor, data, id_contratos, atendente_responsavel) VALUES('".$dados['protocol']."', '".$dados['historico']."', '".$dados['autor']."', '".$value_id_contato."', '".$dados['nome_provedor']."', '".$dados['data_abertura']."', '".$dados['id_contratos']."', '".$dados['atendente_responsavel']."')";
						$mysqli->query($query_comunica);		
						$id_comunica = $mysqli->insert_id;
						$a->queryFree("INSERT INTO comunicacao_interna_contatos (id_comunicacao_interna, id_usuarios) VALUES('".$id_comunica."', '".$value_id_contato."')");							
					}
				}
				$protocolo_pav = $dados["protocol"]; # evita que o protocolo seja subescrito ao fim da rotina de comunicação
				unset($dados['id_contatos']);
			}
			if(isset($dados["origem"])){ # Setado em atendimento-entrada-nivel-3.php
				$protocolo_pav = $dados["protocol"]; 
			}
			if(isset($dados['subTabela'])){ 
				if($dados['subTabela']=="planos_movimentos"){ # cadastro auxiliar dos contratos na tabela planos_movimentos
					$newlog['tabela'] = $dados['subTabela'];
					# instancia variável para foreach tratar os planos selecionados
					$trata_planos = $dados['id_planos_mov'];
					$id_planos = NULL;
					if($dados['chave_cerquilha']){
						$cerq = $dados;
						unset($cerq['chave_cerquilha']);
						$id_planos = $a->processaCerquilhas($cerq);						
					}
					$dados['id_planos_mov'] = $id_planos['id_planos_mov']; 					
				}else if($dados['subTabela'] == "group_user"){
					$newlog['tabela'] 			= $dados['subTabela'];
					$newlog["group_users_id"] 	= $dados["group_users_id"];
					unset($dados['subTabela'], $dados["group_users_id"]);					
				}else if($dados['subTabela'] == "atendentes"){ # Cadastro automático de atendente principal da entidade para provedores
					$newlog["tabela"] 			= $dados["subTabela"];
					$newlog["tipo_atendente"] 	= "4";
					$newlog["id_privilegio"]	= "3";
					$newlog["nome"]				= $dados["nome"];
					$newlog["usuario"]			= $dados["usuario"];					
				}else{				
					if(isset($dados['id'])){ # caso seja uma inserção de logs para o CGR
						if(isset($protocolo_pav)){
							$newlog['protocol'] 		= $protocolo_pav;
						}else{
							$newlog['protocol'] 		= $dados['protocol'];
						}
						$newlog['descricao']		= $dados['historico'];
						# ------ Tratamento de upload de arquivos na timeline -------- #
            /* por Adan Ribeiro - 04/10/2019 */
            if(isset($pic['documentos'])){
              $documentos = $pic['documentos'];
              $newlog['files']	= $a->addAnexos($documentos);
            }else{
              $newlog['files']	= NULL;
            }
						$newlog['id_atendente']		= $dados['atendente_responsavel'];
						$newlog['id_autor']			= $session_id_user;#dados['autor'];
						$newlog['id_pav_inscritos']	= $dados['id'];
						$newlog['data']				= date('Y-m-d H:i:s');
						if(isset($data['solution'])){
							$newlog['solution'] 	= $dados['solution'];
						}
						$grab = $a->add($dados['subTabela'], $newlog);
						
					}else{ # caso seja a primeira inserção de log (nível 1) ou atribuição
						if(isset($dados["status"])){							
							if($dados["status"]	== '2'){
								$newlog['solution'] 		= 1;
								$dados['validado'] 			= 1;
							}else if($dados["status"]		== '3'){
								$newlog['solution'] 	    = 2;
								$dados['finalizado']	    = 1;
								$dados['finalizado_direto']	= 1;
							}
						}						
						if(isset($protocolo_pav)){
							$newlog['protocol'] 	= $protocolo_pav;
						}else{
							$newlog['protocol'] 	= $dados['protocol'];
						}
						$newlog['descricao']		= $dados['historico'];
						# ------ Tratamento de upload de arquivos na timeline -------- #
            /* por Adan Ribeiro - 04/10/2019 */
            if(isset($pic['documentos'])){
              $documentos = $pic['documentos'];
              $newlog['files']	= $a->addAnexos($documentos);
            }else{
              $newlog['files']	= NULL;
            }
						$newlog['id_atendente']		= $dados['atendente_responsavel']; 
						$newlog['id_autor']			= $session_id_user;#$dados['autor'];
						$newlog['data']				= date('Y-m-d H:i:s');
						$newlog['tabela']			= $dados['subTabela'];	
						if(isset($data['solution'])){
							$newlog['solution'] 	= $dados['solution'];
						}
					}
				}	
				unset($dados['id_atendente'], $dados['subTabela'], $dados['id'], $dados['solution']);
			}
			
			if($dados["retorno"]==".modal-body-add"){
				$select_retorno = $dados["retorno"];
			}else if($dados["retorno"]=="#modalMsgRetorno"){
				$retorno_default = '1';
			}
			
			unset(
			$dados["confirmasenha"], 
			$dados["flag"], 
			$dados["tbl"], 
			$dados["file"], 
			$dados["caminho"], 
			$dados["retorno"],
			$dados["id_grupo"],
			$dados["var"],
			$dados['flagRadius'],
			$dados['usernameAnterior']
			);
			
			if(in_array(true, array_map('is_array', $dados), true) == ''){
				unset($dados['chave_cerquilha']);
				$grab = $a->add($tabela, $dados);
				
				if($grab == true){
					if(isset($retorno_default)){
						$msg = $act->retornaMsg('2');
						if($msg != false){
							echo $msg;
						}else{
							echo "<div class='alert alert-danger'>
							<h4>Falha na operação.</h4>
							<hr><p><b>Código do erro retornado:</b> ";print_r($mysqli->error);echo "</p>
							<p>Por favor, informe este código a supervisão.</p></div>
							"; 
						}
						die();
					}
					
					if(isset($array_contatos)){//agenda de contatos válidos para clientes que usam e-mail
						$ult_id = $_SESSION['ult_id'];
						foreach($array_contatos as $value){
							$value = str_replace(array("\n", "\r", "&nbsp;", "/\r|\n/", "<br>", "<div>", "</div>", "<span>", "</span>"), "", $value);
							$newcontato['contatos'] 	= trim($value);
							$newcontato['id_cliente'] 	= $ult_id;
							$a->add("agenda_contatos", $newcontato);
						}
					}
					if(isset($newlog['tabela'])){# tabelas auxiliares de movimentação
						
						if($newlog['tabela']=="pav_movimentos"){
							$newlog['id_pav_inscritos'] = $_SESSION['ult_id'];
							$tabela = $newlog['tabela'];
							unset($newlog['tabela']);
							$a->add($tabela, $newlog);
							if(isset($dados["descricao_interna"])){
								unset($newlog["descricao"]);
								$newlog["descricao_interna"] = $dados["descricao_interna"];
								$a->add("pav_descriptions", $newlog);
							}
							# ----------------------------------------------------------------------
              # DONE - Reabilitar rotina gravando em pav_inscritos - Adan 17/10/2019
              # ----------------------------------------------------------------------
							if(isset($array_gr)){ # Adição de grupo padrão do CGR na abertura de chamados
								$id_gr_resp = $array_gr[0];								
								$id_pav = $newlog['id_pav_inscritos'];								
								$query_grupo_default = "SELECT * FROM group_user WHERE group_id = $id_gr_resp AND lixo = 0";
								$varresult = $a->queryFree($query_grupo_default);
								if($varresult->num_rows > 0){
									while($linhas = $varresult->fetch_assoc()){ 
										#$id_gr_user = $linhas['user_id'];										
										$a->queryFree("UPDATE pav_inscritos SET grupo_responsavel = $id_gr_resp WHERE id = $id_pav");               
									}
								}
							}
              # ----------------------------------------------------------------------
              # DONE - Reabilitar rotina gravando em pav_inscritos - Adan 17/10/2019
              # ----------------------------------------------------------------------
							if(isset($grupos_cliente)){
								$id_pav = $newlog['id_pav_inscritos'];
								foreach($grupos_cliente as $value){
									$query_grupo_default = "SELECT * FROM group_user WHERE group_id = $value AND lixo = 0";
									#echo $query_grupo_default;die();
									$varresult = $a->queryFree($query_grupo_default);
									if($varresult->num_rows > 0){
										while($linhas = $varresult->fetch_assoc()){ 
											#$id_gr_user = $linhas['user_id'];										
											$a->queryFree("UPDATE pav_inscritos SET grupo_responsavel = $value WHERE id = $id_pav");	                     
										}
									}
								}
							}
						}else if($newlog['tabela'] == "atendentes"){
							$tabela = $newlog['tabela'];
							unset($newlog['tabela']);
							if(isset($ult_id)){ # Setado em $array_contatos, caso os contatos não estejam vazios
								$newlog['id_usuarios'] = $ult_id;
							}else{
								$newlog['id_usuarios'] = $_SESSION['ult_id'];
							}
							$a->add($tabela, $newlog);
							# ------- Fim da inclusão de atendente de entidade para provedores ------- #
						}else if($newlog['tabela']=="planos_movimentos"){
							$newlog['id_contratos'] = $_SESSION['ult_id'];	
							#configura novo array para tabela auxiliar
							$newlog['id_cliente'] 	= $dados['id_cliente'];
							#$newlog['id_planos'] 	= $id_planos['id_planos_mov'];
							$newlog['data_limite'] 	= $dados['finaliza_em'];							
							$tabela = $newlog['tabela'];
							unset($newlog['tabela']);
							#tratar quantidade e limite dos planos
							foreach($trata_planos as $value){
								$query_contrato = "SELECT id, valor_unit, limite FROM planos WHERE id = '".$value."' AND lixo = 0";
								$foo = $a->queryFree($query_contrato);
								if($dados_contrato = $foo->fetch_assoc()){							
									$newlog['id_planos'] 			= $dados_contrato['id'];
									$newlog['qntd_atendimentos'] 	= $dados_contrato['limite'];
									$newlog['vlr_nominal'] 			= $dados_contrato['valor_unit'];							
									$a->add($tabela, $newlog);
								} 
							}
							# -------- Cadastro automático dos grupos --------- #
							$id_cliente = $newlog['id_cliente'];
							$a->queryFree("INSERT INTO `groups` (`id`, `name`, `id_lider`, `tipo`, `id_cliente`, `finalidade_especial`, `lixo`) VALUES (NULL, 'Grupo de Vendas', '0', 'Vendas', '$id_cliente', '1', '0');");
							$a->queryFree("INSERT INTO `groups` (`id`, `name`, `id_lider`, `tipo`, `id_cliente`, `finalidade_especial`, `lixo`) VALUES (NULL, 'Grupo de Suporte', '0', 'Suporte', '$id_cliente', '1', '0');");
							$a->queryFree("INSERT INTO `groups` (`id`, `name`, `id_lider`, `tipo`, `id_cliente`, `finalidade_especial`, `lixo`) VALUES (NULL, 'Grupo de Cancelamento', '0', 'Retenção', '$id_cliente', '1', '0');");
							$a->queryFree("INSERT INTO `groups` (`id`, `name`, `id_lider`, `tipo`, `id_cliente`, `finalidade_especial`, `lixo`) VALUES (NULL, 'Grupo Financeiro', '0', 'Finanças', '$id_cliente', '1', '0');");
							# ------- Fim cadastro automático dos grupos ------- #
						}else if($newlog['tabela']=="group_user"){ # Inserção dos usuários dos grupos de responsabilidades
							$group_id = $_SESSION['ult_id'];
							$tabela   = $newlog['tabela'];
							unset($newlog['tabela']);
							foreach($newlog['group_users_id'] as $value){
								$a->queryFree("INSERT INTO $tabela (group_id, user_id) VALUES ($group_id, $value)");
							}
						}	
					}
					if(isset($select_retorno)){
						$foo = $a->queryFree("SELECT id, nome FROM pav");
						$retorno = $foo->fetch_assoc();
						return $retorno;
						echo "
							<div class='alert alert-danger'>
							<h4>Falha na operação.</h4>
							<p>Código #0013 - Erro na inserção do cadastro do PAV. Nenhum protocolo foi gerado.</p>
							</div>
							";
					}else{
						$query_protocolo = "SELECT protocol FROM pav_movimentos WHERE id = '".$_SESSION['ult_id']."'";
						$foo = $a->queryFree($query_protocolo);
						$protocolo = $foo->fetch_assoc();
						echo '
						<div class="alert alert-success">
						<h4>Muito bom!</h4>
							<p>A operação foi realizada com sucesso. 
							<a href="." class="alert-link">Clique aqui</a> para atualizar os status do sistema. <br>
							';
						echo (isset($newlog["protocol"]) ? "Número de Protocolo: <b>".$newlog['protocol']."</b>" : "Número de Protocolo gerado: <b>".$protocolo['protocol']."</b>");
						echo'</p>
						</div>';
					}
				}else{
					echo "<div class='alert alert-danger'>
					<h4>Falha na operação.</h4>
					<hr><p><b>Código do erro retornado:</b> ";
					print_r($mysqli->error);
					echo "</p>
					</div>
					"; 
				}
			}else{
				//Para os checkboxes da rotina de módulos etc.
				if(isset($dados['chave_cerquilha'])){
					unset($dados['chave_cerquilha']);					
					$array = $a->processaCerquilhas($dados);
					$a->add($tabela, $array);
				}else{
					if($array_gr){ # Para atribuições diretas no CGR						
						global $mysqli;						
						if(isset($protocolo_pav)){ # Se existe possui tratativa, senão simples inclusão de grupo
							# Encontrar o número de protocolo inserido na rotida de comunicação acima
							$query_comunica = "SELECT id FROM comunicacao_interna_movimentos WHERE protocol = ".$protocolo_pav;
							$retorna_id_comunica = $a->queryFree($query_comunica);
							$id_comunica = $retorna_id_comunica->fetch_assoc();
							# Encontrado o n°, fazer inserção do pav & preparação para Comunicação Interna Atribuída
							$dados_grupo_responsavel = $array_gr;	
							#unset($dados['grupo_responsavel']);
							$dados['id_comunicacao_interna'] = $id_comunica['id'];
							if(!isset($dados['origem'])){
								$dados['origem'] = 'Comunicação';
							}
							$a->add("pav_inscritos", $dados);
							$ult_id_pav = $mysqli->insert_id;
							if(isset($newlog['tabela'])){ # Insert das tabelas auxiliares de movimentação 						
								if($newlog['tabela']=="pav_movimentos"){
									$newlog['id_pav_inscritos'] = $ult_id_pav;
									$newlog['id_atendente'] = $session_id_user;#$dados['autor'];
									$tabela = $newlog['tabela'];
									unset($newlog['tabela']);
									$a->add($tabela, $newlog);
									$ult_id_pav_mov = $mysqli->insert_id;
								}
							}//Desabilitando a inserção na tabela pav__group_user
							# Fazer a inserção dos responsáveis de acordo com o grupo dentro das tabelas de contatos e grupo
							/*foreach($dados_grupo_responsavel as $id_group){
								$boo = $a->queryFree("SELECT user_id FROM group_user WHERE group_id = $id_group AND lixo = 0");if($boo->num_rows > 0){
									while($id_user = $boo->fetch_assoc()){
										$id_usuario_responsavel = $id_user['user_id'];
										#$query_guser = "INSERT INTO group_user (pav_insc_id, user_id) VALUES('$ult_id_pav', '$id_usuario_responsavel')";
										$query_guser = "INSERT INTO pav__group_user (id_pav, id_group, id_group_user) VALUES('$ult_id_pav','$id_group', '$id_usuario_responsavel')";
										$a->queryFree($query_guser);
									}
								}
							}*/
							if($ult_id_pav_mov != false){							
								/* $query_protocolo = "SELECT protocol FROM pav_movimentos WHERE id = '".$ult_id_pav_mov."'";
								$foo = $a->queryFree($query_protocolo);
								$protocolo = $foo->fetch_assoc(); */
								echo '
								<div class="alert alert-success">
								<h4>Muito bom!</h4>
									<p>A operação foi realizada com sucesso. 
									<a href="." class="alert-link">Clique aqui</a> para atualizar os status do sistema. <br>
									';
								echo'</p>
								</div>';
							}else{
								echo "
								<div class='alert alert-danger'>
								<h4>Falha na operação.</h4><hr>
								<p>Código #0014 - Erro na inserção do movimento do PAV. Nenhum protocolo foi gerado.</p>
								</div>
								";
							}
						}else{ # end->isset(protocolo_pav)
							$dados_grupo_responsavel = $array_gr;	
							#unset($dados['grupo_responsavel']);
							$a->add("groups", $dados);
							$ult_id_groups = $mysqli->insert_id;
							foreach($dados_grupo_responsavel as $value){
								$group_user = array(
									"group_id"	=> $ult_id_groups,
									"user_id"	=> $value,
								);
								$a->add("group_user", $group_user);
							}
							if($_SESSION['ult_id'] != false){							
								echo '
								<div class="alert alert-success">
								<h4>Muito bom!</h4>
									<p>A operação foi realizada com sucesso. 
									<a href="." class="alert-link">Clique aqui</a> para atualizar os status do sistema. <br>
									</p>
								</div>';
							}
						}
					}else{
						echo "Código #18237 - Existe um array não tratado na sessão.";
					}
				}
			}
		break;
		
		case "addLog":		
			$array 	= $_POST; 
			$a = new Model();
			$act = new Acoes();
			print_r($array);echo "<br>";print_r($_FILES);die(); # $erro = new UploadException();
			
			# Cancelamento do lock na saída dos registros -> Adan 02/06/2019
			if(isset($array['id'])){
				$id = $array['id'];
				$query_disable_lock = "UPDATE pav_inscritos SET user_auth = NULL WHERE id = $id";
				$a->queryFree($query_disable_lock);
				#echo $query_disable_lock; die();
			}
			# ------ Tratamento de upload de arquivos na timeline -------- #
			/* por Adan Ribeiro - 18/09/2019 */
			if(isset($_FILES['documentos'])){
				$dados['files'] = $a->addAnexos($_FILES['documentos']);
			}			
			
			# ------ Tratamento da data de urgência -------- #			
			if(isset($array['data_expectativa_termino'])){
				if($array['data_expectativa_termino'] != ""){
					$data = $array['data_expectativa_termino'];
					$dt = new DateTime($data);
					$data_agendada = "<hr><i class='material-icons' style='vertical-align:middle;'>access_time</i>&nbsp;<span style='position: relative; top: 2px;'>Agendado para: ".$dt->format('d/m/Y H:i:s')."</span>";
					$array["historico"] .= $data_agendada; 
					$query_update_time = "UPDATE pav_inscritos SET data_expectativa_termino = '$data', status = '5' WHERE id = $id";
					$a->queryFree($query_update_time);					
				}
			}
		      
			if(isset($array["historico"])){
				$subs_historico = addslashes($array["historico"]);
				$array['historico'] = $subs_historico;
			}
			
			if($array['retorno'] == ".section_historico_descript"){
				$dados['descricao_interna']	= $array['descricao_interna'];
				$dados['id_autor']			= $array['id_atendente']; # esse item corresponde ao usuário atual do sistema
				$dados['id_pav_inscritos']	= $array['id'];
				$dados['data']				= date('Y-m-d H:i:s');				
				$captura = $a->add('pav_descriptions', $dados);
			}else{
				$dados['protocol'] 			= $array['protocol'];
				$dados['descricao']			= $array['historico'];
				if(isset($dados['files'])){(is_null($dados['files']) ? $dados['files']	= NULL : '');}else{$dados['files'] = NULL;}
				$dados['id_autor']			= $session_id_user;
				$dados['id_atendente']		= $array['id_atendente']; 
				$dados['id_pav_inscritos']	= $array['id'];
				$dados['data']				= date('Y-m-d H:i:s');
				isset($array['solution']) ? $dados['solution'] = $array['solution'] : $dados['solution'] = 0;
				isset($array['auditado']) ? $dados['auditado'] = $array['auditado'] : $dados['auditado'] = 0;
				if($_SESSION['datalogin']['id_contrato'] != 0){ 
					if(isset($datalogin['ambiente_privilegio'])){
						$contrato = $_SESSION['datalogin']['id_contrato']; 
						$resultado_cliente = $a->queryFree("SELECT id_cliente FROM contratos WHERE id = $contrato");
						$result = $resultado_cliente->fetch_assoc(); 
						$dados['origem'] = $result['id_cliente'];
						$dados['flag_cliente'] = 1;
						$array['func'] = NULL;
					}else{ # ---- diferenciar os funcionário na tabela usuários :: 26/09/2019 :: ---- #
						$dados['origem'] = $dados['id_autor']; 
						$array['func'] = 1;
					}					
				}else{
					$array['func'] = NULL;
				}

				// validando o atendimento antes de inserir uma finalização	
				// impedindo que ocorra a finalização do mesmo protocolo por mais de uma fez por um atendente do call center.		
					
					if($dados['solution'] == '2'){
						
						if($datalogin['id_privilegio']=='2'){
						$query_valida = "select  pav_movimentos.solution, usuarios.nome, pav_movimentos.data, pav_movimentos.id
						FROM pav_movimentos
						INNER JOIN usuarios ON pav_movimentos.id_autor = usuarios.id
						WHERE pav_movimentos.solution = 2 and usuarios.id_privilegio = 2 and pav_movimentos.id_pav_inscritos = ".$dados['id_pav_inscritos'];
						$valida = $a->queryFree($query_valida);
						$resultado_valida = $valida ->fetch_assoc();
							if(isset($resultado_valida['solution'])){
								echo "<div class='alert alert-danger'>
										<h4>Erro ao finalizar esse atendimento.</h4><hr>
											<p>Esse atendimento já foi finalizado pelo usuário: <strong> ".$resultado_valida['nome']." no dia " .$resultado_valida['data'] ." </strong>
											<br>Caso queira verificar no histórico basta buscar pelo seguinte protocolo: ". $dados['protocol']."
											</p>
										</div>";
								$captura = false;
								$mov_id  = $resultado_valida['id'];
							}else{
								$captura = $a->add('pav_movimentos', $dados);
								$mov_id  = $_SESSION['ult_id'];
							}

						}else{
							$captura = $a->add('pav_movimentos', $dados);
							$mov_id  = $_SESSION['ult_id'];
						}
						
					}else{
						$captura = $a->add('pav_movimentos', $dados);
						$mov_id  = $_SESSION['ult_id'];
					}					
				
				// fim da validação 

				
				//$mov_id  = $_SESSION['ult_id']; # -> ref 003
				# ---- Atualização do usuário responsável e entidade pela inscrição ----- #
				if(isset($array['nome_provedor'])){
					$query_pav_inscritos = "UPDATE pav_inscritos SET atendente_responsavel = '$dados[id_atendente]', id_contratos = '$array[id_contrato]', nome_provedor = '$array[nome_provedor]'  WHERE id = '$dados[id_pav_inscritos]'";
				}else{
					$query_pav_inscritos = "UPDATE pav_inscritos SET atendente_responsavel = $dados[id_atendente] WHERE id = '$dados[id_pav_inscritos]'";
				}
				$a->queryFree($query_pav_inscritos);
			}		
			
			if(isset($array['action-auditoria'])){
				$protocolo = $array['protocol'];
				$query = "UPDATE pav_inscritos SET auditado = 1, entidade_aberto = 1, validado = 0, finalizado = 0, status = 1 WHERE id = $id";
				$result = $a->queryFree($query);
				if($mysqli->affected_rows > 0){
					echo '
					<div class="alert alert-success">
					<h4>Processo de auditoria iniciado!</h4><hr>
						<p>Aguarde a movimentação do setor de backoffice para maiores informações. 
						<a href="." class="alert-link">Clique aqui</a> para atualizar os status do sistema. <br>
						</p>
					</div>';
				}else{
					echo "<div class='alert alert-danger'>
					<h4>Erro na atualização do protocolo.</h4><hr>
						<p>Não foi possível realizar a abertura de auditoria neste registro. 
						<br>Por favor entre em contato com o suporte informando o seguinte protocolo: $protocolo
						</p>
					</div>";
				}
			}else if(isset($array['action-pendencia'])){
				$protocolo = $array['protocol'];
				$query = "UPDATE pav_inscritos SET pendente = 1, status = 1 WHERE id = $id";
				$result = $a->queryFree($query);
				if($mysqli->affected_rows > 0){
					echo "
					<div class='alert alert-success'>
					<h4>Chamado marcado como pendente.</h4><hr>
						<p>Este registro foi movido para a fila de chamados pendentes com o protocolo $protocolo  
						<a href='.' class='alert-link'>Clique aqui</a> para atualizar os status do sistema. <br>
						</p>
					</div>";
				}else{
					echo "<div class='alert alert-danger'>
					<h4>Erro na atualização do protocolo.</h4><hr>
						<p>Não foi possível este registro como pendente. 
						<br>Por favor entre em contato com o suporte informando o seguinte protocolo: $protocolo
						</p>
					</div>";
				}
			}else if(isset($array['action-solution'])){
				$protocolo = $array['protocol']; # Needs to verify which value field 'auditado' has to keep it 
				# -------- Validação do campo auditado -------------- #
				$query_valida = "SELECT pav.auditado AS pav_aud, mov.auditado AS mov_aud
				FROM pav_inscritos AS pav 
				INNER JOIN pav_movimentos AS mov ON pav.id = mov.id_pav_inscritos
				WHERE pav.id = '$id' GROUP BY pav_aud";
				$foo_valida = $a->queryFree($query_valida);
				if($foo_valida->num_rows >0){
					$validacao = $foo_valida->fetch_assoc();
					$pav_aud = $validacao['pav_aud'];
					$mov_aud = $validacao['mov_aud'];
					# a variável $mov_id é definida após a inclusão do registro em pav_movimentos -> ref 003
					$query = "UPDATE pav_inscritos SET finalizado = 0, validado = 1, entidade_aberto = 1, status = 2, auditado = $pav_aud WHERE id = $id";
					$query_mov = "UPDATE pav_movimentos SET auditado = $mov_aud WHERE id = $mov_id";
				}else{
					$query = "UPDATE pav_inscritos SET finalizado = 0, validado = 1, entidade_aberto = 1, status = 2, auditado = 0 WHERE id = $id";
				}
				# -------- Execução da atualização do campo auditado em pav_movimentos -------------- #
				if(isset($mov_aud)){
					if($mov_aud > 0){						
						$resultado = $a->queryFree($query_mov);
					}
				}
				# -------- Execução da atualização do campo auditado em pav_inscritos -------------- #
				$result = $a->queryFree($query);
				if($mysqli->affected_rows > 0){	
					echo "
					<div class='alert alert-success'>
					<h4>Chamado solucionado!</h4><hr>
						<p>Número do protocolo: $protocolo<br>
						<a href='.' class='alert-link'>Clique aqui</a> para atualizar os status do sistema. <br>
						</p>
					</div>";
				}else{
					echo "<div class='alert alert-danger'>
					<h4>Erro na atualização do protocolo.</h4><hr>
						<p>Não foi possível realizar a operação. 
						<br>Por favor entre em contato com o suporte informando o seguinte protocolo: $protocolo
						</p>
					</div>";
				}
			}else if($array["retorno"] == ".section_historico_log"){
				if($captura == true){
					$log = new Logs;
					$query = "SELECT cpf_cnpj_cliente FROM pav_inscritos WHERE id = '".$array['id']."'";
					$ok = $a->queryFree($query);
					if($arr = $ok->fetch_assoc()){
						$log->ultimosAtendimentos($arr['cpf_cnpj_cliente']);	
					}
				}
			}else if($array['retorno'] == ".section_historico_descript"){
				if($captura == true){
					$log = new Logs;						
					$query_movimentos = "SELECT pav.id, pav.protocol, pav.data, pav.descricao_interna, pav.solution, atendentes.nome AS nome, atendentes.id_usuarios AS user_id, usuarios.foto AS foto  
					FROM pav_descriptions AS pav 
					INNER JOIN pav_inscritos ON pav_inscritos.id = pav.id_pav_inscritos 
					INNER JOIN atendentes ON atendentes.id_usuarios = pav.id_autor 
					INNER JOIN usuarios ON usuarios.id = pav.id_autor
					WHERE pav.id_pav_inscritos = '$array[id]' AND pav.lixo = 0 
					GROUP BY pav.id 
					ORDER BY pav.data DESC";
					$result = $a->queryFree($query_movimentos);
					if($result){
						while($linhas = $result->fetch_assoc()){
							#$log->description($linhas); erro da não carregar a  nova descrição no timeline esta aqui.
							$log->timelineGenerico($linhas['nome'],$linhas['protocol'],$linhas['descricao_interna'],$linhas['data'], $linhas['solution'], 'chat_bubble_outline', $linhas['foto']);
						}									
					}
				}
			}else{	
				if($captura == true){ # 'captura' denota se a inserção em pav_movimentos foi válida
					$log = new Logs;
					
					if(isset($array['finalizado'])){
						if($array['finalizado'] == 1){
							$protocolo = $array['protocol'];
							$id_pav_inscritos = $array['id'];
							if($dados['auditado'] == 0){ # Finalizado sem ser auditado
								$query_finaliza = "UPDATE pav_inscritos SET finalizado = 1, validado = 0, auditado = 0, despachado = 0, status = 3  WHERE id = $id_pav_inscritos";
							}else{ # Finalizado após processo de auditoria ter começado
								$query_finaliza = "UPDATE pav_inscritos SET finalizado = 1, validado = 0, auditado = 2, despachado = 0, status = 3, entidade_aberto = 0  WHERE id = $id_pav_inscritos";
								# a variável $mov_id é definida após a inclusão do registro em pav_movimentos -> ref 003
								$query_mov = "UPDATE pav_movimentos SET auditado = 3 WHERE id = $mov_id";
								$a->queryFree($query_mov);	
							}
							$a->queryFree($query_finaliza);	
							if($mysqli->affected_rows > 0){	
								echo "
								<div class='alert alert-success'>
								<h4>Chamado finalizado!</h4><hr>
									<p>Número do protocolo: $protocolo<br>
									<a href='.' class='alert-link'>Clique aqui</a> para atualizar os status do sistema. <br>
									</p>
								</div>";
							}else{
								echo "<div class='alert alert-danger'>
								<h4>Erro na atualização do protocolo.</h4><hr>
									<p>Não foi possível realizar a operação. 
									<br>Por favor entre em contato com o suporte informando o seguinte protocolo: $protocolo
									</p>
								</div>";
							}
							die(); # Esta rotina conclui aqui
						}
					# ----------- Retorno -------------#
					}else if(isset($array['action-retorno'])){
						$protocolo = $array['protocol'];
						$id_pav_inscritos = $array['id'];
						if($dados['auditado'] == 0){ # Retorno sem ser auditado
							$query_retorno = "UPDATE pav_inscritos SET validado = 0, finalizado = 0, status = 1 WHERE id = $id_pav_inscritos";
							# a variável $mov_id é definida após a inclusão do registro em pav_movimentos -> ref 003
							$query_ret = "UPDATE pav_movimentos SET solution = 3 WHERE id = $mov_id";
							$a->queryFree($query_ret);	
						}else{# Retorno após processo de auditoria ter começado
							$query_retorno = "UPDATE pav_inscritos SET finalizado = 0, validado = 0, status = 1, entidade_aberto = 1  WHERE id = $id_pav_inscritos";
							# a variável $mov_id é definida após a inclusão do registro em pav_movimentos -> ref 003
							$query_ret = "UPDATE pav_movimentos SET auditado = 1 WHERE id = $mov_id";
							$a->queryFree($query_ret);	
						}
						$a->queryFree($query_retorno);
						if($mysqli->affected_rows > 0){
							echo "
								<div class='alert alert-success'>
								<h4>Chamado retornado!</h4><hr>
									<p>Número do protocolo: $protocolo<br>
									<a href='.' class='alert-link'>Clique aqui</a> para atualizar os status do sistema. <br>
									</p>
								</div>";
						}else{
							echo "<div class='alert alert-danger'>
							<h4>Erro na atualização do protocolo.</h4><hr>
								<p>Não foi possível realizar a operação. 
								<br>Por favor entre em contato com o suporte informando o seguinte protocolo: $protocolo
								</p>
							</div>";
						}
						die();
					}
					# ----------- Retorno -------------#
					$act->buildTimeline($array['id'], NULL, NULL, $array['func']);
				}
			} 
		break;
		
		case "status_input":
			$a = new Model();
			$dados = $_POST;
			$foo = "UPDATE pav_inscritos SET status = '".$dados['value']."' WHERE id = '".$dados['id']."'";
			$result = $a->queryFree($foo);
			if($mysqli->affected_rows > 0){
				echo "<p class=text-success>Salvo</p>";
			}else{
				echo "<p class=text-danger>Falha no salvamento</p>";
			}
		break;
		
		case "load-select":
			$a = new Model();
			$dados = $_POST;
			$query	= "SELECT * FROM categorias WHERE lixo = 0 AND id_clientes = $dados[id]";				
			$result = $a->queryFree($query);
			if($result->num_rows >0){
				$select = '<select class="form-control required" name="id_categorias" id="select-categorias" data-live-search="true">';
				while($linhas = $result->fetch_assoc()){
					$select .= "<option value='$linhas[id]' data-tipo_categoria='$linhas[tipo_categoria]' data-tokens='$linhas[nome_categoria]'>$linhas[nome_categoria]</option>";
				}
				$select .= '</select>';
				echo $select;
			}else{				
				echo $result->num_rows;
			}		
		break;
		
		case "addUser":
			require_once("../parametros.inc.php");
			$parametros_server = new Param();
			$email_settings = $parametros_server->emailConfig();
			$dados = $_POST;
			$tabela = $dados["tbl"];
			
			$dados['uid'] = uniqid( rand(), true );
			$dados['data_ts'] = time();
			
			$a = new Model();
			if(isset($dados["senha"])){
				if($dados["senha"]!=""){
					$dados['senha'] = md5($dados['senha']);	
				}	
			}					
			unset($dados["flag"], $dados["tbl"]);					
			$result = $a->addUser($tabela, $dados);
			if($result->num_rows == '1'){
				$resultado = $result->fetch_assoc();
				
				$url = sprintf( 'id=%s&email=%s&uid=%s&key=%s', $resultado['id'], md5($resultado['usuario']), md5($resultado['uid']), md5($resultado['data_ts']));
				$mensagem = 'Para confirmar seu cadastro acesse o link:<br>'."\n";
				$mensagem .= sprintf('http://www.'.$email_settings['dominio'].'/validador.php?%s',$url);
		
				// enviar o email
				$a->envia($resultado, 'Registro de cadastro - '.$parametros_server->title(), $mensagem);
				echo '
				<div class="alert alert-success fade in">
				<h4>Operação executada com sucesso.</h4>
				<p>Verifique o seu e-mail.<br>Clique no botão abaixo para fechar esta mensagem.</p>
				<p class="m-t-10">
				  <button type="button" class="btn btn-default waves-effect" data-dismiss="alert" >Fechar</button>
				</p>
				</div>
				';
			}else{
				echo '
				<div class="alert alert-danger fade in">
				<h4>Falha no processo.</h4>
				<p>Houve um erro de causa desconhecida. Contacte o suporte.<br>Clique no botão abaixo para fechar esta mensagem.</p>
				<p class="m-t-10">
				  <button type="button" class="btn btn-default waves-effect" data-dismiss="alert" >Fechar</button>
				</p>
				</div>
				';
			}
		break;
		case "addGaleria":
			$a = new Model();
			$dadosPost = $_POST;
			$tabela = $dadosPost["tbl"];
			unset($dadosPost['flag'], $dadosPost['tbl']);			
			$arr = $_FILES['file'];			
			if($dadosPost['tipo']=='galeria'){
				$path = "../media/imagens/galeria/albuns/fotos_galeria/";
			}else if($dadosPost['tipo']=='album'){				
				$path = "../media/imagens/galeria/albuns/".$dadosPost['id_contato']."/";
				if (!file_exists($path)) {
					mkdir("../media/imagens/galeria/albuns/".$dadosPost['id_contato']."/", 0700);
				}
			}
			$goose = array();
			foreach($arr as $key => $dados){
				for($z=0; $z<count($dados); $z++){
					$goose[$z][$key] = $dados[$z];						
				}
			}	
			
			foreach($goose as $indice){
				$extfile  = strtolower(substr($indice['name'], strripos($indice['name'], '.', -1)));
				$indice['name'] = time().mt_rand(1000, 9000).$extfile;
				move_uploaded_file($indice['tmp_name'],$path.$indice['name']);					
				$dadosPost['midia'] = $indice['name'];
				$a->addGallery($tabela, $dadosPost);
			}
			
			if ($mysqli->affected_rows > 0) { 
				echo "<div msg_dialog class='confirm' title='Clique para fechar.'>Operação executada com sucesso.</div>"; 
			}else{
				echo "<div msg_dialog class='alerta' title='Clique para fechar.'>Falha na operação.</div>";
			}
		break;
		case "addVideo":
			$data = $_POST;		
			$arr = $_FILES['media'];
			$tabela = $data['tbl'];
			$path = "../media/video/videoteca/";
			$a = new Model();
			unset($data["flag"], $data["tbl"]);	
			$retorno = $a->ajeitaFoto($arr, $tbl);
			
			if ($retorno != -1) { 
				echo "<div msg_dialog class='confirm' title='Clique para fechar.'>Operação executada com sucesso.</div>"; 
			}else{
				echo "<div msg_dialog class='alerta' title='Clique para fechar.'>Falha na operação.</div>";
			}
		break;
		case "exc":		#Código de Exclusão
			$dados = $_POST;
			$tabela = $dados["tbl"];
			if(isset($dados['arquivo'])){
				unlink("../".$dados['arquivo']);	
			}
			unset($dados["flag"], $dados["tbl"], $dados['arquivo']);
			$a = new Model();
			$a->exc($tabela, $dados["id"]);
			
		break;
		case "verifica_exc_plano":		#Verifica se o plano está vinculado a um contrato antes de ser excluído
			$dados = $_POST;
			$query_teste = "SELECT id_contratos FROM planos_movimentos WHERE lixo = 0 AND id_planos = '".$dados['id']."'";
			$a = new Model;
			$retorno = $a->queryFree($query_teste);
			if(isset($retorno)){
				$foo = $retorno->fetch_assoc();
				if($foo['id_contratos'] != ''){				
					echo ("
						<script type='text/javascript'>
						$(document).ready(function () {
							$('#alerta').modal('toggle');	
						});
						</script>
						");
				}else{
					$a->exc('planos', $dados["id"]);					
				}
			}
		break;
		case "mensagens":
			$dados = $_POST;
			include("../../views/comunicacao-crud.php");
		break;
		
		case "update-config":		#Código de Atualização da tabela de configurações			
			$a = new Model();
			$act = new Acoes();
			foreach($_POST as $key=>$values){
				$dados[$key] = $values;
			}
			$tabela = $dados["tbl"];
			$id     = $dados['id'];
			unset($dados['caminho'], $dados['flag'], $dados['retorno'], $dados['tbl'], $dados['id']);
			$retorno = $a->upd($tabela, $dados, $id);
			if($retorno->affected_rows > 0){
				echo $act->retornaMsg(2).("
				<script type='text/javascript'>
				$(document).ready(function () {
					$('#modalMsgRetorno').modal('toggle');	
				});
				</script>
				");
			}else{
				echo $act->retornaMsg(9, "RETORNO: $retorno->error").("
				<script type='text/javascript'>
				$(document).ready(function () {
					$('#modalMsgRetorno').modal('toggle');	
				});
				</script>
				");;
			}
		break;
		
		case "update":		#Código de Atualização da Edição			
			$dados = $_POST;
			#print_r($dados);die();
			$tabela = $dados["tbl"];
			$a = $b = new Model();
			
			# Cancelamento do lock na saída dos registros -> Adan 02/06/2019
			if(isset($dados['tbl'])){
				if($dados['tbl'] == 'pav_inscritos'){
					if(isset($dados['id'])){
						$query_disable_lock = "UPDATE pav_inscritos SET user_auth = NULL WHERE id = '".$dados['id']."'";
						$a->queryFree($query_disable_lock);
					}
				}
			}
			
			if(isset($dados["senha"])){
				if($dados["senha"]!=""){
					$query_teste = "SELECT senha FROM $tabela WHERE id = '".$dados['id']."'";
					$foo = $a->queryFree($query_teste);
					$testa_senha = $foo->fetch_assoc();
					if($testa_senha['senha'] == $dados['senha']){
						unset($dados["senha"]);
					}else{
						$dados['senha'] = md5($dados['senha']);	
					}
				}else{
					unset($dados["senha"]);
				}
			}
			if(isset($_FILES)){			
				$pic = $_FILES;
				$vetor = NULL;
				if(!empty($pic["file"])){
					$nomeFile = $pic["file"]["name"];
					$vetor = "file";
				}
				else if(!empty($pic["foto"])){
					$nomeFile = $pic['foto']["name"];
					$vetor = "foto";
				}
				else if(!empty($pic['media'])){
					$nomeFile = $pic['media']["name"];
					$vetor = "media";
				}
				else if(!empty($pic['imagem'])){
					$nomeFile = $pic['imagem']["name"];
					$vetor = "imagem";
				}
				if(isset($_FILES[$vetor])){
					if($_FILES[$vetor]['error']!=0){
						unset($_FILES);
					}else{				
						if(isset($nomeFile)){
							$media = $a->addFoto($nomeFile, $vetor, $tabela);
							if(isset($media)){
								$dados[$vetor] = $media['name'];
							}
						}
					}	
				}					
			}
			# Tratamento para campos tipo DATE no perfil do usuário
			if(isset($dados["data_nascimento"])){
				if(empty($dados['data_nascimento'])){
					$dados["data_nascimento"] = date('Y-m-d');
				}
			}
			
			if(isset($dados["valor_unit"])){
				$dados["valor_unit"] = str_replace(',','.',str_replace('.','',$dados["valor_unit"]));
			}
			
			if(isset($dados["hora_add"])){
				unset($dados['hora_add']);
				/* $dados["data_abertura"] = date("Y-m-d H:i:s");
				$dados["protocol"] = $a->protocolo(); */
			}			
			
			if(isset($dados["idd"])){
				if($dados["idd"] == "solucionado")	{			
					$dados["id_pav"] 	= '0';
					$dados["validado"] 	= '1';
					$dados["status"]	= '2';					
				}
				unset($dados['idd']);
			}
			
			if(isset($dados['contatos'])){//campo de clientes que permite inserção dos contatos de e-mail
				$contatos = $dados['contatos'];
				unset($dados['contatos']);				
				$array_contatos = explode(",", $contatos);
			}
			
			unset($dados["confirmasenha"], $dados["flag"], $dados["tbl"], $dados["caminho"], $dados["retorno"], $dados['subTabela'], $dados['flagRadius'], $dados['usernameAnterior'] );
			
			if(isset($dados['id'])){
				if(in_array(true, array_map('is_array', $dados), true) == ''){
					
					unset($dados['chave_cerquilha']);
					$a->upd($tabela, $dados, $dados['id']);
					
					if(isset($array_contatos)){
						$query_delete = "DELETE FROM agenda_contatos WHERE id_cliente = ".$dados['id'];
						$a->queryFree($query_delete);
						$atualiza_contatos['id_cliente'] = $dados['id'];
						foreach($array_contatos as $value){
							$value = str_replace(array("\n", "\r", "&nbsp;", "/\r|\n/", "<br>", "<div>", "</div>", "<span>", "</span>"), "", $value);
							$atualiza_contatos['contatos'] = trim($value);
							$a->add("agenda_contatos", $atualiza_contatos);
						}
					}
					
				}else{
					/* $i 	= 1; 				
					$valor = NULL; */
					$array = NULL;
					if(isset($dados['chave_cerquilha'])){
						unset($dados['chave_cerquilha']);
						
						foreach($dados as $key=>$value){
							if(is_array($value)){
								$valor = NULL;
								$i = 1;
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
						$a->upd($tabela, $array, $dados['id']);
					}else{
          # ---------------------------------------------------  
          # TODO - Verificar se a rotina de atualização precisa do array em $dados[grupo_responsavel], caso contrário trocar por variável - Adan 17/10/2019  
          # ---------------------------------------------------  
						if(isset($dados['grupo_responsavel'])){
							$grupo_responsavel = $dados['grupo_responsavel'];
							unset($dados['grupo_responsavel']);
							$a->upd($tabela, $dados, $dados['id']);
							$a->queryFree("DELETE FROM group_user WHERE group_id = '".$dados['id']."'");
							foreach($grupo_responsavel as $value){
								$group_user = array(
									"group_id"	=> $dados['id'],
									"user_id"	=> $value,
								);
								$a->add("group_user", $group_user);
							}
						}
					}
				}
			}else{
				$a->upd($tabela, $dados);
			}
			
			if($mysqli->affected_rows != '-1'){
				echo '
					<div class="alert alert-success">
                    <h4>Muito bom!</h4>
					A operação foi realizada com sucesso. <a href="." class="alert-link">Clique aqui</a> para atualizar os status do sistema.
					</div>	
				';
			}else{
				echo '
				<div class="alert alert-danger fade in">
				<h4>Falha no processo.</h4>
				<p>Houve um erro de causa desconhecida. Contacte o suporte.<br>Será necessário reiniar a rotina.</p>
				<a href="." class="alert-link">Clique aqui</a> para atualizar o navegador.
				</div>
				';				
			}
		break;
		
		case "entrada": // Entrada de dados selecionados para atendimento 1º nível
			global $array;
			global $id_provedor;
			$dados = $_POST; 
			$a = new Model();
			if(isset($_SESSION['resultado_pesquisa']['id'])){
				$id_provedor = $_SESSION['resultado_pesquisa']['id'];
				$_SESSION['id_provedor'] = $id_provedor;
				unset($_SESSION['resultado_pesquisa']['id']);
			}else{
				echo "ATENÇÃO: ID do resultado da pesquisa retornou vazio!<br> Consulte pav.sys.php -> Código #55";
			}
			$indice = $dados["idd"]; 
			$_SESSION['resultado_pesquisa']['indice'] = $indice;
			#print_r($_SESSION['resultado_pesquisa']['clientes'][$indice]);die();						
			foreach($_SESSION['resultado_pesquisa']['clientes'][$indice] as $key=>$value)
				$array[$key] = $value;
				
			include("../../views/atendimento.php");	
		break;
		
		case "call-center-auditoria2Nivel": // Entrada de dados selecionados para atendimento 2º nível			
			global $id;
			global $user;
			$dados = $_POST;	
			$user  = $_SESSION['datalogin']['id'];			
			$id = $dados["idd"];
			include("../../views/call-center-auditoria-nivel-2.php");	 
		break;
		
		case "cgr-auditoria2Nivel": // Entrada de dados selecionados para atendimento 2º nível			
			global $id;
			global $user;
			$dados = $_POST;	
			$user  = $_SESSION['datalogin']['id'];			
			$id = $dados["idd"];
			include("../../views/cgr-auditoria-nivel-2.php");	 
		break;
		
		case "entrada2Nivel": // Entrada de dados selecionados para atendimento 2º nível			
			$a = new Model;
			$act = new Acoes;
			global $id;
			global $user;
			$dados = $_POST;	
			$user  = $_SESSION['datalogin']['id'];			
			$id = $dados["idd"];
			$query = "SELECT user_auth FROM pav_inscritos WHERE id = $id";
			$result = $a->queryFree($query);
			$auth = $result->fetch_assoc();
			if(is_null($auth['user_auth'])){
				include("../../views/atendimento-entrada-nivel-2.php");
			}else{
				if($auth['user_auth'] == $user){
					include("../../views/atendimento-entrada-nivel-2.php");
				}else{
					echo $act->retornaMsg(8);
				}
			}	 
		break;
		
		case "cgr_entrada2Nivel": // Entrada de dados selecionados para atendimento 2º nível que vieram do CGR			
			$a = new Model;
			$act = new Acoes;
			global $id;			
			$dados = $_POST;
			$user  = $_SESSION['datalogin']['id'];	# -> Define a variável que seta a concorrência pessimista
			$id = $dados["idd"];
			$query = "SELECT user_auth FROM pav_inscritos WHERE id = $id";
			$result = $a->queryFree($query);
			$auth = $result->fetch_assoc();
			if(is_null($auth['user_auth'])){
				include("../../views/cgr-chamados-abertos-nivel-2.php");
			}else{
				if($auth['user_auth'] == $user){
					include("../../views/cgr-chamados-abertos-nivel-2.php");
				}else{
					echo $act->retornaMsg(8);
				}
			}
		break;
		# ------------------- Listagem Clientes Chamados Pendentes --------------------- #
		case "clientes-chamados-pendentes":
		$datalogin   = $_SESSION['datalogin'];
		$dados = $_POST;
		$id = $dados['id'];
		$a = new Model;
		$e = new Acoes;
		$query_privilegio = "SELECT * FROM usuarios WHERE id = $id";
		$foo = $a->queryFree($query_privilegio);
		$priv = $foo->fetch_assoc();
		if($datalogin['id_contrato'] != 0){
			$id_contrato = $datalogin['id_contrato'];
			if($datalogin['id_privilegio'] == 3){				
				$query = "SELECT * FROM pav_inscritos 
				WHERE id_contratos = $id_contrato AND lixo = 0 AND despachado = 1 AND pendente = 1
				ORDER BY data_abertura ASC";
			}else{
				$query	= "SELECT pav_inscritos.* FROM pav_inscritos 
				INNER JOIN group_user ON group_user.group_id = pav_inscritos.grupo_responsavel
				WHERE pav_inscritos.lixo = 0 AND id_contratos = $id_contrato AND despachado = 1 AND pendente = 1 AND group_user.user_id = $datalogin[id] 
				ORDER BY data_abertura ASC";
			}		
		}
		
		$return	= $a->queryFree($query);
		if($return->num_rows > 0){
			while($linhas = $return->fetch_assoc()){
				$data_ready[] = $e->listagemCallCenter($linhas, $dados['caminho'], "pendente2Nivel");
			}
			$data_json = json_encode($data_ready);
			echo $data_json;
		}else{
			return false;			
		} 				
		break;
		
		case "pendente2Nivel": // Entrada de pendências para o cliente			
			$a = new Model;
			$act = new Acoes;
			global $id;
			$dados = $_POST;
			$user  = $_SESSION['datalogin']['id'];			
			$id = $dados["idd"];
			$query = "SELECT user_auth FROM pav_inscritos WHERE id = $id";
			$result = $a->queryFree($query);
			$auth = $result->fetch_assoc();
			if(is_null($auth['user_auth'])){
				include("../../views/clientes/clientes-chamados-pendentes-nivel-2.php");
			}else{
				if($auth['user_auth'] == $user){
					include("../../views/clientes/clientes-chamados-pendentes-nivel-2.php");
				}else{
					echo $act->retornaMsg(8);
				}
			}
		break;
		
		case "cgr_retorno2Nivel": // Entrada de retornos 2º nível que vieram do CGR			
			$a = new Model;
			$act = new Acoes;
			global $id;
			$dados = $_POST;
			$user  = $_SESSION['datalogin']['id'];			
			$id = $dados["idd"];
			$query = "SELECT user_auth FROM pav_inscritos WHERE id = $id";
			$result = $a->queryFree($query);
			$auth = $result->fetch_assoc();
			if(is_null($auth['user_auth'])){
				include("../../views/cgr-chamados-aguardando-finalizar-nivel-2.php");
			}else{
				if($auth['user_auth'] == $user){
					include("../../views/cgr-chamados-aguardando-finalizar-nivel-2.php");
				}else{
					echo $act->retornaMsg(8);
				}
			}
		break;
		
		case "clientes_retorno2Nivel": // Entrada de retornos 2º nível para clientes			
			$a = new Model;
			$act = new Acoes;
			global $id;
			$dados = $_POST;
			$user  = $_SESSION['datalogin']['id'];						
			$id = $dados["idd"];
			$query = "SELECT user_auth FROM pav_inscritos WHERE id = $id";
			$result = $a->queryFree($query);
			$auth = $result->fetch_assoc();
			if(is_null($auth['user_auth'])){
				include("../../views/clientes/clientes-chamados-aguardando-finalizar-nivel-2.php");	
			}else{
				if($auth['user_auth'] == $user){
					include("../../views/clientes/clientes-chamados-aguardando-finalizar-nivel-2.php");
				}else{
					echo $act->retornaMsg(8);
				}
			}			 
		break;
		# ------------------- Listagem Clientes Aguardando Finalizar --------------------- #
		case "clientes_aguardandofinalizar":
			$dados = $_POST;
			$id = $dados['id'];
			$a = new Model;
			$e = new Acoes;
			$query_privilegio = "SELECT * FROM clientes WHERE id = $id";
			$foo = $a->queryFree($query_privilegio);
			$priv = $foo->fetch_assoc();
			if($datalogin['id_contrato'] != 0){
				$id_contrato = $datalogin['id_contrato'];                                   
				$query	= "SELECT * FROM pav_inscritos WHERE id_contratos = $id_contrato AND pendente = 0 AND lixo = 0 AND auditado = 1 AND entidade_aberto = 1 AND validado = 1 AND atribuido_de = 1 ORDER BY data_abertura ASC";
			}
			$return	= $a->queryFree($query);
			if($return->num_rows > 0){
				while($linhas = $return->fetch_assoc()){
					$data_ready[] = $e->conteudoTabelaJSON($linhas, $dados['caminho'], "clientes_retorno2Nivel", NULL, NULL, "autor");
				}
				$data_json = json_encode($data_ready);
				echo $data_json;
			}else{
				return false;
			} 	
		break;
		# ------------------- Listagem NOC Aguardando Finalizar --------------------- #
		case "cgr_aguardandofinalizar": // Entrada de dados aguardando finalização que vieram do CGR			
			$dados = $_POST;
			$id = $dados['id'];
			$a = new Model;
			$e = new Acoes;
			$query_privilegio = "SELECT * FROM usuarios WHERE id = $id";
			$foo = $a->queryFree($query_privilegio);
			$priv = $foo->fetch_assoc();
			if($datalogin['id_contrato'] != 0){
				$id_contrato = $datalogin['id_contrato'];
				$query	= "SELECT * FROM pav_inscritos WHERE id_contratos = $id_contrato AND pendente = 0 AND lixo = 0 AND auditado = 0 AND validado = 1 AND finalizado != 1 AND atribuido_de = 2 ORDER BY data_abertura ASC";
			}else{
				if($priv['id_privilegio'] != 6){
					$query	= "SELECT * FROM pav_inscritos 
					WHERE lixo = 0 AND auditado = 0 AND validado = 1 AND finalizado != 1 AND atribuido_de = 2 
					ORDER BY data_abertura ASC";
				}else{
					$query = "SELECT pav_inscritos.* FROM pav_inscritos 
					LEFT JOIN group_user ON group_user.group_id = pav_inscritos.grupo_responsavel 
					WHERE (group_user.user_id = $id AND auditado = 0 AND validado = 1 AND finalizado != 1 AND atribuido_de = 2 AND pav_inscritos.autor = $id )
					OR (pav_inscritos.atendente_responsavel = $id AND auditado = 0 AND validado = 1 AND finalizado != 1 AND atribuido_de = 2) 
					OR (pav_inscritos.atendente_responsavel = 0 AND auditado = 0 AND validado = 1 AND finalizado != 1 AND atribuido_de = 2 )
					OR (pav_inscritos.grupo_responsavel = 0 AND pav_inscritos.lixo = 0 AND auditado = 0 AND validado = 1 AND finalizado != 1 AND atribuido_de = 2) 
					GROUP BY protocol 
					ORDER BY `pav_inscritos`.`id` ASC";
				}
			}
			$return	= $a->queryFree($query);
			if($return->num_rows > 0){
				while($linhas = $return->fetch_assoc()){
					$data_ready[] = $e->conteudoTabelaJSON($linhas, $dados['caminho'], "cgr_retorno2Nivel", NULL, NULL, "atendente_responsavel");
				}
				$data_json = json_encode($data_ready);
				echo $data_json;
			}else{
				return false;
			} 	
		break;
		
		case "ultimosAtendimentos":
		$act = new Acoes;
		$dados = $_POST;
		$act->buildTimeline($dados['id'], NULL, $dados['protocol']);		
		break;

		# ------------------- Listagem Chamados abertos pelas entidades para o NOC --------------------- #
		case "cgr_emabertos_entidades": #validado = '0', status = '1'
		$dados = $_POST;
		$id = $dados['id'];
		$a = new Model;
		$e = new Acoes;	
		$query_privilegio = "SELECT * FROM usuarios WHERE id = $id";
		$foo = $a->queryFree($query_privilegio);
		$priv = $foo->fetch_assoc();
		
			if($priv['id_privilegio'] != 6){
				$query	= "SELECT pav_inscritos.*
				FROM pav_inscritos INNER JOIN usuarios ON usuarios.id = pav_inscritos.autor 
				WHERE pav_inscritos.lixo = 0 AND pav_inscritos.auditado = 0 AND pav_inscritos.validado = 0 AND pav_inscritos.finalizado != 1 
				AND pav_inscritos.atribuido_de = 2 AND usuarios.id_contrato > 0	";
			}else{
				$query	= "SELECT pav_inscritos.*
				FROM pav_inscritos        
				LEFT JOIN group_user ON group_user.group_id = pav_inscritos.grupo_responsavel 
				INNER JOIN usuarios ON usuarios.id = pav_inscritos.autor 
				WHERE (group_user.user_id = $id AND pav_inscritos.validado = 0 AND auditado = 0 AND pav_inscritos.finalizado = 0 AND pav_inscritos.atribuido_de = 2 AND usuarios.id_contrato > 0) 
				OR (pav_inscritos.atendente_responsavel = $id AND pav_inscritos.validado = 0 AND auditado = 0 AND pav_inscritos.finalizado = 0 AND pav_inscritos.atribuido_de = 2 AND usuarios.id_contrato > 0) 
				OR (pav_inscritos.autor = $id AND pav_inscritos.validado = 0 AND auditado = 0 AND pav_inscritos.finalizado = 0 AND pav_inscritos.atribuido_de = 2 AND usuarios.id_contrato > 0) 
				OR (pav_inscritos.atendente_responsavel = 0 AND pav_inscritos.validado = 0 AND auditado = 0 AND pav_inscritos.finalizado = 0 AND pav_inscritos.atribuido_de = 2 AND usuarios.id_contrato > 0) 
				GROUP BY protocol";
			}
		
		$return	= $a->queryFree($query);
		if($return->num_rows > 0){
			while($linhas = $return->fetch_assoc()){
				$data_ready[] = $e->conteudoTabelaJSON($linhas, $dados['caminho'], "cgr_entrada2Nivel", NULL, NULL, "atendente_responsavel");				
			}
			$data_json = json_encode($data_ready);
			echo $data_json;
		}else{
			return false;
		}		
		break;

		# ------------------- Listagem Chamados abertos pelas entidades para o NOC - Agendados --------------------- #
		case "cgr_emabertos_entidades_agendados": #validado = '0', status = '1'
		$dados = $_POST;
		$id = $dados['id'];
		$a = new Model;
		$e = new Acoes;	
		$query_privilegio = "SELECT * FROM usuarios WHERE id = $id";
		$foo = $a->queryFree($query_privilegio);
		$priv = $foo->fetch_assoc();
		
			if($priv['id_privilegio'] != 6){
				$query	= "SELECT pav_inscritos.*
				 FROM pav_inscritos  INNER JOIN usuarios ON usuarios.id = pav_inscritos.autor 
          WHERE pav_inscritos.lixo = 0 AND pav_inscritos.auditado = 0 AND pav_inscritos.validado = 0 AND pav_inscritos.finalizado != 1 
          AND pav_inscritos.atribuido_de = 2 AND pav_inscritos.data_expectativa_termino > now() AND status = 5 AND usuarios.id_contrato > 0	";
			}else{
				$query	= "SELECT pav_inscritos.*
				 FROM pav_inscritos 
					LEFT JOIN group_user ON group_user.group_id = pav_inscritos.grupo_responsavel 
					INNER JOIN usuarios ON usuarios.id = pav_inscritos.autor 
					WHERE (group_user.user_id = $id AND pav_inscritos.validado = 0 AND auditado = 0 AND pav_inscritos.finalizado = 0 AND pav_inscritos.atribuido_de = 2 AND data_expectativa_termino > now() AND status = 5 AND usuarios.id_contrato > 0) 
					OR (pav_inscritos.atendente_responsavel = $id AND pav_inscritos.validado = 0 AND auditado = 0 AND pav_inscritos.finalizado = 0 AND pav_inscritos.atribuido_de = 2 AND data_expectativa_termino > now() AND status = 5 AND usuarios.id_contrato > 0) 
					OR (pav_inscritos.autor = $id AND pav_inscritos.validado = 0 AND auditado = 0 AND pav_inscritos.finalizado = 0 AND pav_inscritos.atribuido_de = 2 AND data_expectativa_termino > now() AND status = 5 AND usuarios.id_contrato > 0) 
					OR (pav_inscritos.atendente_responsavel = 0 AND pav_inscritos.validado = 0 AND auditado = 0 AND pav_inscritos.finalizado = 0 AND pav_inscritos.atribuido_de = 2 AND data_expectativa_termino > now() AND status = 5 AND usuarios.id_contrato > 0)
					GROUP BY protocol";
			}
		
		$return	= $a->queryFree($query);
		if($return->num_rows > 0){
			while($linhas = $return->fetch_assoc()){
				$data_ready[] = $e->conteudoTabelaJSON($linhas, $dados['caminho'], "cgr_entrada2Nivel", NULL, NULL, "atendente_responsavel");				
			}
			$data_json = json_encode($data_ready);
			echo $data_json;
		}else{
			return false;
		}		
		break;

		# ------------------- Listagem Chamados abertos pelas entidades para o NOC - Atrasados --------------------- #
		case "cgr_emabertos_entidades_atrasados": #validado = '0', status = '1'
		$dados = $_POST;
		$id = $dados['id'];
		$a = new Model;
		$e = new Acoes;	
		$query_privilegio = "SELECT * FROM usuarios WHERE id = $id";
		$foo = $a->queryFree($query_privilegio);
		$priv = $foo->fetch_assoc();
		
			if($priv['id_privilegio'] != 6){
				$query	= "SELECT pav_inscritos.*
				 FROM pav_inscritos  INNER JOIN usuarios ON usuarios.id = pav_inscritos.autor 
          WHERE pav_inscritos.lixo = 0 AND pav_inscritos.auditado = 0 AND pav_inscritos.validado = 0 AND pav_inscritos.finalizado != 1 
          AND pav_inscritos.atribuido_de = 2 AND pav_inscritos.data_expectativa_termino < now() AND usuarios.id_contrato > 0	";
			}else{
				$query	= "SELECT pav_inscritos.*
				FROM pav_inscritos 
				LEFT JOIN group_user ON group_user.group_id = pav_inscritos.grupo_responsavel
				INNER JOIN usuarios ON usuarios.id = pav_inscritos.autor  
				WHERE (group_user.user_id = $id AND pav_inscritos.validado = 0 AND auditado = 0 AND pav_inscritos.finalizado = 0 AND pav_inscritos.atribuido_de = 2 AND data_expectativa_termino < now() AND usuarios.id_contrato > 0) 
				OR (pav_inscritos.atendente_responsavel = $id AND pav_inscritos.validado = 0 AND auditado = 0 AND pav_inscritos.finalizado = 0 AND pav_inscritos.atribuido_de = 2 AND data_expectativa_termino < now() AND usuarios.id_contrato > 0) 
				OR (pav_inscritos.autor = $id AND pav_inscritos.validado = 0 AND auditado = 0 AND pav_inscritos.finalizado = 0 AND pav_inscritos.atribuido_de = 2 AND data_expectativa_termino < now() AND usuarios.id_contrato > 0) 
				OR (pav_inscritos.atendente_responsavel = 0 AND pav_inscritos.validado = 0 AND auditado = 0 AND pav_inscritos.finalizado = 0 AND pav_inscritos.atribuido_de = 2 AND data_expectativa_termino < now() AND usuarios.id_contrato > 0)
				GROUP BY protocol";
			}
		
		$return	= $a->queryFree($query);
		if($return->num_rows > 0){
			while($linhas = $return->fetch_assoc()){
				$data_ready[] = $e->conteudoTabelaJSON($linhas, $dados['caminho'], "cgr_entrada2Nivel", NULL, NULL, "atendente_responsavel");				
			}
			$data_json = json_encode($data_ready);
			echo $data_json;
		}else{
			return false;
		}		
		break;
		
		# ------------------- Listagem NOC Em abertos --------------------- #
		case "cgr_emabertos": #validado = '0', status = '1'
		$dados = $_POST;
		$id = $dados['id'];
		$a = new Model;
		$e = new Acoes;	
		$query_privilegio = "SELECT * FROM usuarios WHERE id = $id";
		$foo = $a->queryFree($query_privilegio);
		$priv = $foo->fetch_assoc();
		if(isset($_SESSION['datalogin']['tipo_ambiente']) || $_SESSION['datalogin']['id_ambiente'] != 0){
			$id_contrato = $_SESSION['datalogin']['id_contrato'];
			$query	= "SELECT * FROM pav_inscritos 
			WHERE id_contratos = $id_contrato AND lixo = 0 AND pendente = 0 AND auditado = 0 AND validado = 0 AND finalizado != 1 AND atribuido_de = 2 
			OR (despachado = 1 AND atribuido_de = 2 AND pendente = 0 AND id_contratos = $id_contrato AND lixo = 0)
			ORDER BY data_abertura ASC";
		}else{
			if($priv['id_privilegio'] != 6){
				$query	= "SELECT * FROM pav_inscritos 
				WHERE lixo = 0 AND auditado = 0 AND validado = 0 AND finalizado != 1 AND atribuido_de = 2 
				ORDER BY data_abertura ASC";
			}else{
				$query	= "SELECT pav_inscritos.* FROM pav_inscritos 
				LEFT JOIN group_user ON pav_inscritos.grupo_responsavel = group_user.group_id
				WHERE (group_user.user_id = $id AND pav_inscritos.validado = 0 AND auditado = 0 AND pav_inscritos.finalizado = 0 AND pav_inscritos.atribuido_de = 2) 
				OR (pav_inscritos.atendente_responsavel = $id AND pav_inscritos.validado = 0 AND auditado = 0 AND pav_inscritos.finalizado = 0 AND pav_inscritos.atribuido_de = 2) 
				OR (pav_inscritos.autor = $id AND pav_inscritos.validado = 0 AND auditado = 0 AND pav_inscritos.finalizado = 0 AND pav_inscritos.atribuido_de = 2) 
				OR (pav_inscritos.atendente_responsavel = 0 AND pav_inscritos.validado = 0 AND auditado = 0 AND pav_inscritos.finalizado = 0 AND pav_inscritos.atribuido_de = 2) 
				GROUP BY protocol ORDER BY data_abertura ASC";
			}
		}
		$return	= $a->queryFree($query);
		if($return->num_rows > 0){
			while($linhas = $return->fetch_assoc()){
				$data_ready[] = $e->conteudoTabelaJSON($linhas, $dados['caminho'], "cgr_entrada2Nivel", NULL, NULL, "atendente_responsavel");				
			}
			$data_json = json_encode($data_ready);
			echo $data_json;
		}else{
			return false;
		}		
		break;
		# ------------------- Listagem Call Center Em abertos --------------------- #
		case "emabertos":	
		$datalogin   = $_SESSION['datalogin'];
		$dados = $_POST;
		$id = $dados['id'];
		$a = new Model;
		$e = new Acoes;
		$query_privilegio = "SELECT * FROM usuarios WHERE id = $id";
		$foo = $a->queryFree($query_privilegio);
		$priv = $foo->fetch_assoc();
		if($datalogin['id_contrato'] != 0){
			# A partir de 23/09/2019 chamados validados (solucionados) entram na fila de chamados em aberto dos clientes
			$id_contrato = $datalogin['id_contrato'];
			if($datalogin['id_privilegio'] == 3){				
				$query = "SELECT * FROM pav_inscritos 
				WHERE (id_contratos = $id_contrato AND pav_inscritos.lixo = 0 AND atribuido_de = 1  AND despachado = 1 AND pendente = 0 AND auditado = 0)
				OR (id_contratos = $id_contrato AND pav_inscritos.lixo = 0 AND atribuido_de = 1  AND despachado = 1 AND pendente = 0 AND auditado = 1)
				ORDER BY data_abertura ASC";
			}else{				
				$query	= "SELECT pav_inscritos.* FROM pav_inscritos 
				INNER JOIN group_user ON group_user.group_id = pav_inscritos.grupo_responsavel
				WHERE (pav_inscritos.lixo = 0 AND atribuido_de = 1  AND despachado = 1 AND pendente = 0 AND auditado = 0 AND group_user.user_id =  $datalogin[id])
				OR (pav_inscritos.lixo = 0 AND atribuido_de = 1 AND despachado = 1 AND pendente = 0 AND auditado = 0 AND group_user.user_id =  $datalogin[id])
				ORDER BY data_abertura ASC";
			}	
		# ------------------------ by Adan Ribeiro ------------------------ #
		}else{
      /* -----------------------------------------------------------------
      * A partir de 18/10/2019 a fila de chamados em aberto do Call Center filtrará os 'despachos ao cliente' (finalizado_direto = 1) porque durante o atendimento
      * este tipo de chamado passará a constar na listagem de últimos chamados como ABERTO até que seja finalizado PELO CLIENTE. Sendo assim
      * ele não pode aparecer na listagem de abertos, apesar de seu comportamento ser semelhante a de um chamado em aberto.
      * ------------------------ By Adan ------------------------------ */
			if($priv['id_privilegio'] != 6){
				$query	= "SELECT * FROM pav_inscritos 
				WHERE lixo = 0 AND auditado = 0 AND validado = 0 AND finalizado != 1 AND finalizado_direto = 0 AND atribuido_de != 2 
				ORDER BY data_abertura ASC";
			}else{
				$query = "SELECT pav_inscritos.* FROM pav_inscritos 
				INNER JOIN group_user ON group_user.group_id = pav_inscritos.grupo_responsavel  
				WHERE (group_user.user_id = $id AND pav_inscritos.validado = 0 AND auditado = 0 AND pav_inscritos.finalizado = 0 AND finalizado_direto = 0 AND pav_inscritos.atribuido_de != 2) 
				OR (pav_inscritos.atendente_responsavel = $id AND pav_inscritos.validado = 0 AND auditado = 0 AND pav_inscritos.finalizado = 0 AND finalizado_direto = 0 AND pav_inscritos.atribuido_de != 2) 
				OR (pav_inscritos.autor = $id AND pav_inscritos.validado = 0 AND auditado = 0 AND pav_inscritos.finalizado = 0 AND finalizado_direto = 0 AND pav_inscritos.atribuido_de != 2) 
				OR (pav_inscritos.atendente_responsavel = 0 AND pav_inscritos.validado = 0 AND auditado = 0 AND pav_inscritos.finalizado = 0 AND finalizado_direto = 0 AND pav_inscritos.atribuido_de != 2) 
				GROUP BY protocol 
				ORDER BY data_abertura ASC";
			}
		}
		
		$return	= $a->queryFree($query);
		if($return->num_rows > 0){
			while($linhas = $return->fetch_assoc()){
				$data_ready[] = $e->listagemCallCenter($linhas, $dados['caminho'], "entrada2Nivel");
			}
			$data_json = json_encode($data_ready);
			echo $data_json;
		}else{
			return false;
		} 		
		break;
		# ------------------- Listagem Call Center Auditoria --------------------- #	
		case "call_center_auditoria": 
		$dados = $_POST;
		$id = $dados['id'];
		$a = new Model;
		$e = new Acoes;
		$query_privilegio = "SELECT * FROM usuarios WHERE id = $id";
		$foo = $a->queryFree($query_privilegio);
		$priv = $foo->fetch_assoc();
		# 25/09/2017 - by Adan: a partir de agora a listagem de auditoria não filtra mais chamados solucionados. 
		if($datalogin['id_contrato'] != 0){
			$id_contrato = $datalogin['id_contrato'];
			
			if($datalogin['id_privilegio'] == 3){
				$query	= "SELECT pav_inscritos.data_abertura, 
				MAX(PM.data) as ultimoMovimento, 
				pav_inscritos.protocol, 
				pav_inscritos.nome_provedor, 
				pav_inscritos.nome_cliente, 
				assuntos.description AS descricao_assunto,
				statuses.name AS status_nome,
				usuarios.nome as autor_nome, 						
				ADDTIME(pav_inscritos.data_abertura, SEC_TO_TIME(TIME_TO_SEC(assuntos.solution_time) / 2)) AS expected_time,
				pav_inscritos.data_expectativa_termino,
				pav_inscritos.status,
				pav_inscritos.id,
				pav_inscritos.user_auth,
				pav_inscritos.historico,
				assuntos.description AS descricao_assunto,
				pav_inscritos.cpf_cnpj_cliente
	
				FROM pav_inscritos 
				INNER JOIN pav_movimentos as PM on pav_inscritos.id = PM.id_pav_inscritos
				INNER JOIN statuses ON pav_inscritos.status = statuses.id 
				INNER JOIN assuntos ON assuntos.id = pav_inscritos.assunto 
				INNER JOIN usuarios ON usuarios.id = pav_inscritos.autor
        WHERE pav_inscritos.id_contratos = $id_contrato AND pav_inscritos.lixo = 0 AND pav_inscritos.auditado = 1 AND pav_inscritos.finalizado = 0 AND pav_inscritos.atribuido_de != 2
				GROUP BY pav_inscritos.protocol
				ORDER BY data_abertura ASC";

			}else{

				$query	= "SELECT pav_inscritos.data_abertura, 
				MAX(PM.data) as ultimoMovimento, 
				pav_inscritos.protocol, 
				pav_inscritos.nome_provedor, 
				pav_inscritos.nome_cliente, 
				assuntos.description AS descricao_assunto,
				statuses.name AS status_nome,
				usuarios.nome as autor_nome, 						
				ADDTIME(pav_inscritos.data_abertura, SEC_TO_TIME(TIME_TO_SEC(assuntos.solution_time) / 2)) AS expected_time,
				pav_inscritos.data_expectativa_termino,
				pav_inscritos.status,
				pav_inscritos.id,
				pav_inscritos.user_auth,
				pav_inscritos.historico,
				assuntos.description AS descricao_assunto,
				pav_inscritos.cpf_cnpj_cliente

				FROM pav_inscritos 
				INNER JOIN pav_movimentos as PM on pav_inscritos.id = PM.id_pav_inscritos
				INNER JOIN statuses ON pav_inscritos.status = statuses.id 
				INNER JOIN assuntos ON assuntos.id = pav_inscritos.assunto 
				INNER JOIN usuarios ON usuarios.id = pav_inscritos.autor
				INNER JOIN group_user ON group_user.group_id = pav_inscritos.grupo_responsavel

				WHERE (group_user.user_id = $id AND pav_inscritos.auditado = 1 AND pav_inscritos.finalizado = 0 AND pav_inscritos.atribuido_de != 2 AND id_contratos = $id_contrato) 
				OR (pav_inscritos.atendente_responsavel = $id AND pav_inscritos.auditado = 1 AND pav_inscritos.finalizado = 0 AND pav_inscritos.atribuido_de != 2 AND id_contratos = $id_contrato) 
				OR (pav_inscritos.autor = $id AND pav_inscritos.auditado = 1 AND pav_inscritos.finalizado = 0 AND pav_inscritos.atribuido_de != 2 AND id_contratos = $id_contrato) 
				OR (pav_inscritos.atendente_responsavel = 0 AND pav_inscritos.auditado = 1 AND pav_inscritos.finalizado = 0 AND pav_inscritos.atribuido_de != 2 AND id_contratos = $id_contrato)

				GROUP BY pav_inscritos.protocol
				ORDER BY data_abertura ASC";				
			}
		}else{
			
			$query	= "SELECT pav_inscritos.data_abertura, 
			MAX(PM.data) as ultimoMovimento, 
			pav_inscritos.protocol, 
			pav_inscritos.nome_provedor, 
			pav_inscritos.nome_cliente, 
			assuntos.description AS descricao_assunto,
			statuses.name AS status_nome,
			usuarios.nome as autor_nome, 						
			ADDTIME(pav_inscritos.data_abertura, SEC_TO_TIME(TIME_TO_SEC(assuntos.solution_time) / 2)) AS expected_time,
			pav_inscritos.data_expectativa_termino,
            pav_inscritos.status,
            pav_inscritos.id,
            pav_inscritos.user_auth,
            pav_inscritos.historico,
            assuntos.description AS descricao_assunto,
            pav_inscritos.cpf_cnpj_cliente

			FROM pav_inscritos 
			INNER JOIN pav_movimentos as PM on pav_inscritos.id = PM.id_pav_inscritos
			INNER JOIN statuses ON pav_inscritos.status = statuses.id 
			INNER JOIN assuntos ON assuntos.id = pav_inscritos.assunto 
			INNER JOIN usuarios ON usuarios.id = pav_inscritos.autor
			WHERE pav_inscritos.lixo = 0 AND pav_inscritos.auditado = 1 AND pav_inscritos.finalizado = 0 AND pav_inscritos.atribuido_de != 2
			GROUP BY pav_inscritos.protocol
			ORDER BY data_abertura ASC";
			
		}

		$return	= $a->queryFree($query);
		if($return->num_rows > 0){
			while($linhas = $return->fetch_assoc()){
				$data_ready[] = $e->conteudoTabelaJSON_Auditoria($linhas, $dados['caminho'], "call-center-auditoria2Nivel");
			}
			$data_json = json_encode($data_ready);
			echo $data_json;
		}else{
			return false;
		} 		
		break; 
		
		# ------------------- Listagem CGR/NOC Auditoria --------------------- #	
		case "cgr_auditoria": 
		$dados = $_POST;
		$id = $dados['id'];
		$a = new Model;
		$e = new Acoes;
		$query_privilegio = "SELECT * FROM usuarios WHERE id = $id";
		$foo = $a->queryFree($query_privilegio);
		$priv = $foo->fetch_assoc();
		# 25/09/2017 - by Adan: a partir de agora a listagem de auditoria não filtra mais chamados solucionados. 
		if($datalogin['id_contrato'] != 0){
			$id_contrato = $datalogin['id_contrato'];
			$query	= "SELECT * FROM pav_inscritos 
      INNER JOIN group_user ON group_user.group_id = pav_inscritos.grupo_responsavel
      WHERE id_contratos = $id_contrato AND pendente = 0 AND pav_inscritos.lixo = 0 AND auditado = 1 AND finalizado = 0 AND atribuido_de = 2 
      ORDER BY data_abertura ASC";
		}else{
			if($priv['id_privilegio'] != 6){
				$query	= "SELECT * FROM pav_inscritos WHERE lixo = 0 AND auditado = 1 AND finalizado = 0 AND atribuido_de = 2 ORDER BY data_abertura ASC";
			}else{
				$query = "SELECT pav_inscritos.* FROM pav_inscritos
				LEFT JOIN group_user ON group_user.group_id = pav_inscritos.grupo_responsavel 
				WHERE (group_user.user_id = $id AND auditado = 1 AND pav_inscritos.finalizado = 0 AND pav_inscritos.atribuido_de = 2) 
				OR (pav_inscritos.atendente_responsavel = $id AND auditado = 1 AND pav_inscritos.finalizado = 0 AND pav_inscritos.atribuido_de = 2) 
				OR (pav_inscritos.autor = $id AND auditado = 1 AND pav_inscritos.finalizado = 0 AND pav_inscritos.atribuido_de = 2) 
				OR (pav_inscritos.atendente_responsavel = 0 AND auditado = 1 AND pav_inscritos.finalizado = 0 AND pav_inscritos.atribuido_de = 2) 
				GROUP BY protocol ORDER BY data_abertura ASC";
			}
		}
		$return	= $a->queryFree($query);
		if($return->num_rows > 0){
			while($linhas = $return->fetch_assoc()){
				$data_ready[] = $e->conteudoTabelaJSON($linhas, $dados['caminho'], "cgr-auditoria2Nivel", NULL, NULL, "atendente_responsavel");
			}
			$data_json = json_encode($data_ready);
			echo $data_json;
		}else{
			return false;
		} 		
		break; 
		
		case "pesquisaCGR":
		$dados = $_POST;
		$a = new Model;
		$e = new Acoes;
		$query = $a->selecionaQueryMySQL($dados['nome_cliente'], 'nome_cliente', $dados['cpf'], 'cpf_cnpj', $dados['nome_provedor'], 'nome_provedor', 'pav_inscritos');
		$return = $a->queryFree($query);
		if($return === false){
			echo '<tr><td>Nenhum registro encontrado.</td></tr>';
		}else{
			while($linhas = $return->fetch_assoc()){
				$e->conteudoTabelaCGR($linhas, $dados['caminho'], $dados['flag']);
			}
		}
		break;
		# ------------------- Listagem NOC Históricos --------------------- #
		case "cgr-pesquisaHistoricos":
			$dados = $_POST;
			$a = new Model;
			$e = new Acoes;
			$complemento = "";

			if (strtotime($dados['dataInicial']) < strtotime($dados['dataFinal'])) {
				$complemento .= " and pav_inscritos.data_abertura BETWEEN '" . $dados['dataInicial'] . " 00:00:00' AND '" . $dados['dataFinal'] . " 23:59:59' ";

				
				if(!empty($dados['protocolo_lv'])){
					$complemento .= " and pav_inscritos.protocol =  '" . $dados['protocolo_lv'] . "' ";
				}
				if(!empty($dados['protocolo_telefonia'])){
					$complemento .= " and pav_inscritos.protocolo_telefonia =  '" . $dados['protocolo_telefonia'] . "' ";
				}
				if(!empty($dados['protocolo_entidade'])){
					$complemento .= " and pav_inscritos.protocolo_entidade =  '" . $dados['protocolo_entidade'] . "' ";
				}

				if ($dados['atendente'] != 0) {
					$complemento .= " and pav_inscritos.autor = " . $dados['atendente'] . " ";
				}
				if ($dados['entidade'] != 0) {
					$complemento .= " and clientes.id = " . $dados['entidade'] . " ";
				}
				if ($dados['assunto'] != 0) {
					$complemento .= " and pav_inscritos.assunto = " . $dados['assunto'] . " ";
				}
				if ($dados['origem'] != 'Todos') {
					$complemento .= " and pav_inscritos.origem = '" . $dados['origem'] . "' ";
				}

				if (isset($_SESSION['datalogin']['id_ambiente'])) {
					$id_ambiente = $_SESSION['datalogin']['id_ambiente'];
				} else {
					$id_ambiente = $_SESSION['datalogin']['tipo_ambiente'];
				}
				# 31/10/2018 - Estou liberando a triagem do autor devido a maior liberdade ao pessoal do CGR, segundo tratativa que o João orientou, as pesquisas não podem ter filtro por autor devido a dinamicidade que o atendimento necessita no dia-a-dia.
				if ($id_ambiente == 0) { # histórico no ambiente LV
					$query		= "SELECT pav_inscritos.* FROM pav_inscritos INNER JOIN contratos ON id_contratos = contratos.id, clientes WHERE clientes.id = contratos.id_cliente and finalizado = 1 AND auditado = 0 AND atribuido_de = 2  $complemento ORDER BY data_abertura DESC";				
				} else { # histórico no ambiente dos clientes
					$id_contrato = $_SESSION['datalogin']['id_contrato'];
					$query		= "SELECT * FROM pav_inscritos WHERE finalizado = 1 AND auditado = 0 AND despachado != 1 AND id_contratos = '$id_contrato' AND atribuido_de = 2 $complemento ORDER BY data_abertura DESC";
				}
				
				$foo = $a->queryFree($query);
				if ($foo->num_rows > 0) {
					while ($linhas = $foo->fetch_assoc()) {
						$data_ready[] = $e->conteudoTabelaJSON($linhas, $dados['caminho'], $dados['flag'], 'On', NULL, "atendente_responsavel");
					}
					$data_json = json_encode($data_ready);
					echo $data_json;
				} else {
					return false;
				}
			}
			break;
		# ------------------- Listagem Call Center Históricos --------------------- #
		case "pesquisaHistoricos":
			$dados = $_POST;
			$a = new Model;
			$e = new Acoes;
			$complemento = "";

			if (strtotime($dados['dataInicial']) < strtotime($dados['dataFinal'])) {
				$complemento .= " and pav_inscritos.data_abertura BETWEEN '" . $dados['dataInicial'] . " 00:00:00' AND '" . $dados['dataFinal'] . " 23:59:59' ";
				
				if(!empty($dados['nome'])){
					$complemento .= " and pav_inscritos.nome_cliente LIKE '%" . $dados['nome'] . "%' ";
				}
				if(!empty($dados['protocolo_lv'])){
					$complemento .= " and pav_inscritos.protocol =  '" . $dados['protocolo_lv'] . "' ";
				}
				if(!empty($dados['protocolo_telefonia'])){
					$complemento .= " and pav_inscritos.protocolo_telefonia =  '" . $dados['protocolo_telefonia'] . "' ";
				}
				if(!empty($dados['protocolo_entidade'])){
					$complemento .= " and pav_inscritos.protocolo_entidade =  '" . $dados['protocolo_entidade'] . "' ";
				}

				if(!empty($dados['cpf'])){
					$complemento .= " and pav_inscritos.cpf_cnpj_cliente LIKE '%" . $dados['cpf'] . "%' ";
				}
				if ($dados['atendente'] != 0) {
					$complemento .= " and pav_inscritos.autor = " . $dados['atendente'] . " ";
				}
				if ($dados['entidade'] != 0) {
					$complemento .= " and clientes.id = " . $dados['entidade'] . " ";
				}
				if ($dados['assunto'] != 0) {
					$complemento .= " and pav_inscritos.assunto = " . $dados['assunto'] . " ";
				}
				if ($dados['origem'] != 'Todos') {
					$complemento .= " and pav_inscritos.origem = '" . $dados['origem'] . "' ";
				}

				if (isset($_SESSION['datalogin']['id_ambiente'])) {
					$id_ambiente = $_SESSION['datalogin']['id_ambiente'];
				} else {
					$id_ambiente = $_SESSION['datalogin']['tipo_ambiente'];
				}
				# 31/10/2018 by Adan - Estou liberando a triagem do autor devido a maior liberdade ao pessoal do CGR, segundo tratativa que o João orientou, as pesquisas não podem ter filtro por autor devido a dinamicidade que o atendimento necessita no dia-a-dia.
				# 23/09/2019 by Adan - Chamados que são 'finalizado_direto' e 'auditado' podem aparecer aqui uma vez que ao despachar diretamente ao cliente esses registros são finalizados, porém o cliente pode volta-los para a fila se abrir uma auditoria.
				if ($id_ambiente == 0) { # histórico no ambiente LV
					$query		= "SELECT pav_inscritos.*  FROM pav_inscritos INNER JOIN contratos ON id_contratos = contratos.id, clientes 
					WHERE (clientes.id = contratos.id_cliente AND finalizado = 1 AND atribuido_de != 2 AND auditado = 0  $complemento)
					OR	(clientes.id = contratos.id_cliente AND finalizado_direto = 1 AND atribuido_de != 2 $complemento)
					ORDER BY data_abertura DESC";
				
				} else { # histórico no ambiente dos clientes
					$id_contrato = $_SESSION['datalogin']['id_contrato'];
					$query		= "SELECT pav_inscritos.*  FROM pav_inscritos WHERE finalizado = 1 AND atribuido_de != 2 AND auditado = 0 AND despachado != 1 AND id_contratos = '$id_contrato' $complemento ORDER BY data_abertura DESC";
				}
				
				$foo = $a->queryFree($query);
				if ($foo->num_rows > 0) {
					while ($linhas = $foo->fetch_assoc()) {
						$data_ready[] = $e->listagemCallCenter($linhas, $dados['caminho'], $dados['flag'], 'On');
					}
					$data_json = json_encode($data_ready);
					echo $data_json;
				} else {
					return false;
				}
			}

			break;

		
		case "pesquisaRetorno":
		$dados = $_POST;
		$a = new Model;
		$e = new Acoes;
		if(isset($_SESSION['datalogin']['id_ambiente'])){
			$id_ambiente = $_SESSION['datalogin']['id_ambiente'];
		}else{
			$id_ambiente = $_SESSION['datalogin']['tipo_ambiente'];
		}
		# 31/10/2018 - Estou liberando a triagem do autor devido a maior liberdade ao pessoal do CGR, segundo tratativa que o João orientou, as pesquisas não podem ter filtro por autor devido a dinamicidade que o atendimento necessita no dia-a-dia.
		if($id_ambiente == 0){ # histórico no ambiente LV
			$query = "SELECT * FROM pav_inscritos WHERE validado = 1 AND auditado = 0 AND finalizado = 0 AND atribuido_de != 2 ORDER BY data_abertura DESC";
		}else{ # histórico no ambiente dos clientes
			$id_contrato = $_SESSION['datalogin']['id_contrato'];
			$query = "SELECT * FROM pav_inscritos WHERE validado = 1 AND auditado = 0 AND finalizado = 0 AND atribuido_de != 2 AND id_contratos = '$id_contrato' ORDER BY data_abertura DESC";
		} 		
		$foo = $a->queryFree($query);
		if($foo->num_rows > 0){
			while($linhas = $foo->fetch_assoc()){
				$data_ready[] = $e->listagemCallCenter($linhas, $dados['caminho'], "entradaRetorno2nivel");				
			}
			$data_json = json_encode($data_ready);
			echo $data_json;
		}else{
			return false;
		}	
		break;
		
		case "pesquisaListagem":
		$dados = $_POST;
		$a = new Model;
		$e = new Acoes;
		
		$query		= $a->selecionaQueryMySQL($dados['nome_cliente'], 'nome_cliente', $dados['cpf'], 'cpf_cnpj_cliente', $dados['nome_provedor'], 'nome_provedor', 'pav_inscritos');
		if($query == 'SELECT * FROM pav_inscritos '){
			$query 	   .= "WHERE finalizado = 1 ORDER BY data_abertura ASC";
		}else{
			$query 	   .= " AND validado = 1 AND auditado = 0 AND finalizado = 0 ORDER BY data_abertura ASC";
		}
		$return = $a->queryFree($query);
		if($return === false){
			echo '<tr><td>Nenhum registro encontrado.</td></tr>';
		}else{
			while($linhas = $return->fetch_assoc()){
				$e->conteudoTabelaCGR($linhas, $dados['caminho'], $dados['flag']);
			}
		}		
		break;
		
		case "visualizar":
			global $id;			
			$id = $_POST["idd"];
			include("../../views/historicos-visualizar.php");
		break;
		
		case "load-page":
			$a 		= new Model;
			$foo 	= new Logs;
			$act		= new Acoes; 		
			global $dados;
			$dados = $_POST;
			include("$dados[destino]");
		break;
		
		case "entradaRetorno2nivel":
			$a = new Model;
			$act = new Acoes;
			global $id;
			$dados = $_POST;
			$user  = $_SESSION['datalogin']['id'];						
			$id = $dados["idd"];
			$query = "SELECT user_auth FROM pav_inscritos WHERE id = $id";
			$result = $a->queryFree($query);
			$auth = $result->fetch_assoc();
			if(is_null($auth['user_auth'])){
				include("../../views/call-center-retorno-visualizar.php");
			}else{
				if($auth['user_auth'] == $user){
					include("../../views/call-center-retorno-visualizar.php");
				}else{
					echo $act->retornaMsg(8);
				}
			}				
		break;
		
		case "visualizar-historico-cgr":
			global $id;			
			$id = $_POST["idd"];
			include("../../views/cgr-historicos-visualizar.php");
		break;
		
		case "planosMovimentos":
			$dados = $_POST;
			$valor = NULL;
			$i = 0;
			//salvar em planos_movimentos	
			foreach($dados as $key=>$value){
				if(is_array($value)){
					foreach($value as $vlr){
					  $array['id_planos'] = $vlr;
					  echo '<br>'; print_r ($array);
					 /*  if(is_null($dados['id'])){
						$a->add('planos_movimentos', $array);  
					  }else{
						echo "editar";
					  } */
					}
				}else{							
					if(strpos($key, "id_") === 0){
						if(isset($array[$key])){
							$array[$key] .= $value;
						} else{
							$array[$key] = $value;
						}
					}					
				}
			} 
		break;
		
		case "selecionaGrupoAtribuicao":
			$act = new Acoes;
			$dados = $_POST;
			#print_r($dados);die();
			$retorno = $act->atribuiGrupo($dados['id_grupo'], $dados);	
			#return $retorno;	// Desabilitei para testar o bug do retorno das opções da comunicacao_interna_contatos
		break;
		
		case "addComunicacao":
			$a = new Model;
			$array = $_POST;
			$tabela = $array['tbl'];
			unset($array['_wysihtml5_mode'], $array['flag'], $array['tbl'], $array['caminho'], $array['retorno'], $array['chave_cerquilha']);
			if(isset($array['id_contatos'])){
				foreach($array['id_contatos'] as $value_id_contato ){
					$count 	= 1;
					$coluna = NULL;
					$valor 	= NULL;
					foreach($array as $key=>$value){
						if($key == "id_contatos"){
							$coluna .= $key;
							$valor  .= "'".$value_id_contato."'";
						}else{
							$coluna .= $key;
							$valor  .= "'".$value."'";
							if($count < sizeof($array)){
								$coluna .= ", ";
								$valor  .= ", ";
							}
						}
						$count++;
					}
					$a->queryFree("INSERT INTO $tabela ($coluna) VALUES($valor)");
				}
			}
			echo '
				<div class="alert alert-success">
				<h4>Serviço comunicado!</h4>
				A operação foi realizada com sucesso. <a href="." class="alert-link">Clique aqui</a> para atualizar os status do sistema.
				</div>';
		break;

		case "pesquisaDestinatario":			
			$a = new Model;
			$array = $_POST;
			$query = "SELECT id, nome FROM usuarios WHERE nome LIKE '".$array['nome']."%'";
			$select = $a->queryFree($query);
			$ret = $select->fetch_assoc();
			if(!is_null($ret['id'])){
				echo "<span id='span_nome_".$ret['id']."' class='badge badge-danger'><input type='hidden' id='input_responsavel_".$ret['id']."' value='".$ret['id']."' >".$ret['nome']."</span>
				";
			}else{
				echo '';
			}	
		break;
		
		case "pesquisaGrupoResponsavel":			
			$a = new Model;
			$array = $_POST;
      if($array['contrato'] != 0){
        $query_cliente = "SELECT clientes.* FROM clientes INNER JOIN contratos ON clientes.id = contratos.id_cliente WHERE contratos.id = $array[contrato]";
        $foo = $a->queryFree($query_cliente);
        $clientes = $foo->fetch_assoc();
        $query = "SELECT id, name AS nome, id_lider FROM groups WHERE lixo = '0' AND id_cliente = '$clientes[id]' AND groups.name LIKE '%".$array['nome']."%'";
      }else{
        $query = "SELECT id, name AS nome, id_lider FROM groups WHERE lixo = '0' AND id_cliente = '0' AND groups.name LIKE '%".$array['nome']."%'";
      }			
			$select = $a->queryFree($query);
			$ret = $select->fetch_assoc();
			if(!is_null($ret['id'])){
				echo "<span id='span_nome_grupo_".$ret['id']."' class='badge badge-danger'><input type='hidden' data-lider='".$ret['id_lider']."' id='input_gruporesponsavel_".$ret['id']."' value='".$ret['id']."' >".$ret['nome']."</span>
				";
			}else{
				echo '';
			}	
		break;
		
		case "lerEmail":
			global $dados;			
			$dados = $_POST;
			include("../../views/mail-viewer-NOC.php");			
		break;
		
		case "processaProvedores":
			$a = new Model;
			$dados = $_POST;
			$tabela = $dados["tbl"];
			unset($dados["retorno"], $dados["flag"], $dados["tbl"], $dados["caminho"] );
			global $newArray; 
			global $dados_provedores; 
			
			foreach($dados as $key=>$value){
				if(is_array($value)){					
					foreach($value as $foo=>$valor){
						$arr = json_decode($valor);
						foreach($arr as $indice=>$item){
							$dados_provedores[$indice] = $item;
						} 	
						$a->add($tabela, $dados_provedores);	
						if(isset($_SESSION["ult_id"])){
							$a->upd($tabela, $newArray, $_SESSION["ult_id"]);
						}else{
							echo "SESSION não iniciada";
						}
					}
				}else{
					$newArray[$key] = $value;
				}	
			}		
			
		break;
		case "selecionar":
			global $id_provedor;
			global $dados;
			$dados = $_POST; 
			#print_r($_SESSION['resultado_pesquisa']['clientes']);echo "<hr>";
			$a = new Model();
			if(isset($_SESSION['id_provedor'])){
				$id_provedor = $_SESSION['id_provedor'];
			}else{
				echo "ATENÇÃO: ID do resultado da pesquisa retornou vazio!<br> Consulte pav.sys.php -> Código #55<br><hr>";
				print_r($_SESSION['resultado_pesquisa']);
			}
			$indice_servico = $dados["indice"];
			$indice = $dados["idd"];
			
			/* 
			foreach($_SESSION['resultado_pesquisa']['clientes'] as $key=>$value)
				$array[$key] = $value;
				
			foreach	($_SESSION['resultado_pesquisa']['clientes'][0]['servicos'][$indice] as $key=>$value)
				$array_servicos[$key] = $value;  */
				
			foreach($_SESSION['resultado_pesquisa']['clientes'][$indice] as $key=>$value)
				$array[$key] = $value;
				
			foreach	($_SESSION['resultado_pesquisa']['clientes'][$indice]['servicos'][$indice_servico] as $key=>$value)
				$array_servicos[$key] = $value; 
				
			include("../../views/atendimento.php");
		break;
		
		case "addpavaux":
			$array = $dados = $_POST;
			unset($array["_wysihtml5_mode"], $array['flag'], $array['tbl'], $array['caminho'], $array['retorno']);			
			$a = new Model();			
			$retorno = $a->add_retorno($dados['tbl'], $array);
			#$retorno["retorno"] = $dados["retorno"];
			echo $retorno;
		break;
		
		case "finalizar":
			$dados = $_POST;
			$a = new Model();
			$foo = $a->queryFree("SELECT * FROM pav_movimentos WHERE id_pav_inscritos = '".$dados['id']."' ORDER BY protocol desc limit 1");
			$woo = $foo->fetch_assoc();
			$a->queryFree("UPDATE pav_movimentos SET solution = '2' WHERE protocol = '".$woo['protocol']."'");
			$retorno = $a->queryFree("UPDATE pav_inscritos SET finalizado = '1', validado = '0', status = '3' WHERE protocol = '".$dados['protocol']."'");
			if($mysqli->affected_rows > 0){
				if(isset($dados['id'])){
					$query_disable_lock = "UPDATE pav_inscritos SET user_auth = NULL WHERE id = '".$dados['id']."'";
					$a->queryFree($query_disable_lock);
					#echo $query_disable_lock;
				}
				echo '
				<div class="alert alert-success">
				<h4>Tratativa finalizada.</h4>
				A operação foi realizada com sucesso. <a href="." class="alert-link">Clique aqui</a> para atualizar os status do sistema.
				</div>';
			}else{
				if(isset($dados['id'])){
					$query_disable_lock = "UPDATE pav_inscritos SET user_auth = NULL WHERE id = '".$dados['id']."'";
					$a->queryFree($query_disable_lock);
					#echo $query_disable_lock;
				}
				echo '
				<div class="alert alert-danger">
				<h4>Falha no processo.</h4>
				Um erro ao tentar atualizar o banco de dados ocorreu. Entre em contato com o suporte do sistema.<hr>';
				var_dump($retorno);echo " # protocolo: ".(!empty($dados['protocol']) ? $dados['protocol'] : "não disponível");
				echo '<br></div>';
			}
		break;
		
		case "retorno":
			$dados = $_POST;
			$a = new Model();	
			$foo = $a->queryFree("SELECT * FROM pav_movimentos WHERE id_pav_inscritos = '".$dados['id']."' ORDER BY protocol desc limit 1");
			$woo = $foo->fetch_assoc();
			$a->queryFree("UPDATE pav_movimentos SET solution = '0' WHERE protocol = '".$woo['protocol']."'");			
			$retorno = $a->queryFree("UPDATE pav_inscritos SET validado = '0', status = '1', finalizado = '0' WHERE protocol = '".$dados['protocol']."'");
			if($mysqli->affected_rows > 0){
				if(isset($dados['id'])){
					$query_disable_lock = "UPDATE pav_inscritos SET user_auth = NULL WHERE id = '".$dados['id']."'";
					$a->queryFree($query_disable_lock);
					#echo $query_disable_lock;
				}
				echo '
				<div class="alert alert-success">
				<h4>A operação foi realizada com sucesso. </h4>
				O registro de protocolo nº '.$dados['protocol'].' retornou para chamados em abertos. <a href="." class="alert-link">Clique aqui</a> para atualizar os status do sistema.
				</div>';
			}else{
				if(isset($dados['id'])){
					$query_disable_lock = "UPDATE pav_inscritos SET user_auth = NULL WHERE id = '".$dados['id']."'";
					$a->queryFree($query_disable_lock);
					#echo $query_disable_lock;
				}
				echo '
				<div class="alert alert-danger">
				<h4>Falha no processo.</h4>
				Um erro ao tentar atualizar o banco de dados ocorreu. Entre em contato com o suporte do sistema.<hr>';
				var_dump($retorno);echo " # protocolo: ".(!empty($dados['protocol']) ? $dados['protocol'] : "não disponível");
				echo '<br></div>';
			}
		break;
		
		case "addReg":
			$dados 		= $_POST;
			$act 		= new Acoes;
			$a			= new Model;				
			$cbkdel 	= $dados['cbkdel'];
			$link		= "controllers/sys/crud.sys.php";
			if($dados['layout'] == "contratos"){				
				echo ("
					<tr data-idd='" . $dados['id'] . "'>
					<td><input type='hidden' name='id_planos_mov[]' id='check".$dados['id']."' value='".$dados['id']."' />".$dados['nome']."</td>
					<td>R$ ". number_format($dados['valor_unit'], 2, ',', '.') ."</td>
					<td>");
						$act->crudTableButtons($dados['id'], $cbkdel, NULL, $link, "planos_movimentos");
						echo ("</td>
					</tr>
				");
			}else{
				echo "
					<tr data-idd='".$dados['id']."'>
					<td>
						<input type='checkbox' class='checkbox checkbox-info' value='".$dados['id']."'>
						<input type='hidden' name='group_users_id[]' value='".$dados['id']."'>
					</td>
					<td>".$dados['nome']."</td>
					<td>";	$act->crudTableButtons($dados['id'], $cbkdel, NULL, $link); 
				echo"</td>
					</tr>";
			}
		break;
		
		case "exc-table":
			$id = $_POST['id'];
			$a	= new Model;
			if(isset($_POST['table'])){
				$table = $_POST['table'];
				$result = $a->queryFree("DELETE FROM $table WHERE id = $id");
			}else{
				$result = $a->queryFree("DELETE FROM group_user WHERE id = $id");
			}
			return true;			
		break;
		
		/*case "exc-resp": // Função desabilitada pois não será mais utilizada
			$id = $_POST['id_pav'];
			$a	= new Model;
			$result = $a->queryFree("DELETE FROM pav__group_user WHERE id = $id");
			return true;			
		break;*/
		
		case "upd-table":
			$dados	= $_POST;
			$a = new Model;
			
			# Dados relacionados a subtabela
			$subtabela 			= $dados['group_users_id'];
			
			# Dados relacionados a configuração
			$info['retorno']	= $dados['retorno'];
			$info['flag']		= $dados['flag'];
			$info['tbl']		= $dados['tbl'];
			$info['subTabela']	= $dados['subTabela'];
			$info['caminho']	= $dados['caminho'];
			unset($dados['retorno'], $dados['flag'], $dados['tbl'], $dados['subTabela'], $dados['caminho'], $dados['group_users_id']);			
			
			# Atualização dos dados da tabela principal
			foreach($dados as $key=>$value){
				if(isset($array[$key])){
					$array[$key] .= $value;
				}else{
					$array[$key] = $value;
				}
			}
			
			$a->upd($info['tbl'], $array, $dados['id']);
			
			# Atualização ou inserção dos dados na subtabela			
			$a->queryFree("DELETE FROM ".$info['subTabela']." WHERE group_id = '".$dados['id']."'");
			foreach($subtabela as $value){
				$array_user['user_id'] = $value;
				$array_user['group_id']= $dados['id'];
				$a->add($info['subTabela'], $array_user);
			}
			echo '
				<div class="alert alert-success">
				<h4>A operação foi realizada com sucesso. </h4>
				<a href="." class="alert-link">Clique aqui</a> para atualizar os status do sistema.
				</div>';
		break;
		
		case "update-lock":
			$id = $_POST['id'];
			$a	= new Model;
			$result = $a->queryFree("UPDATE pav_inscritos SET user_auth = NULL WHERE id = $id");
			return true;
		break;
		
		case "libera-todos-lock":
			$a	= new Model;
			$result = $a->queryFree("UPDATE pav_inscritos SET user_auth = NULL ");
			if($mysqli->affected_rows > 0){
				echo "
				<div class='alert alert-success'>
				<h4>A operação foi realizada com sucesso.</h4><hr><p>".($mysqli->affected_rows == 1 ? $mysqli->affected_rows." registro atualizado.</p>" : $mysqli->affected_rows." registros atualizados.</p>")."<a href='.' class='alert-link'>Clique aqui</a> para atualizar os status do sistema.
				</div>";
			}else{
				echo "
				<div class='alert alert-warning'>
				<h4>Nenhum lock foi encontrado.</h4><hr><a href='.' class='alert-link'>Clique aqui</a> para atualizar os status do sistema.
				</div>";
			}
			return true;
		break;
    
	case "transferencia-chamados":
	  $valida_erro = 0;
      $a	= new Model;
      $act = new Acoes;
      $dados = $_POST;
     
	  $protocolo = explode(" ", $dados['protocol']);
	  if(isset($dados['grupo_responsavel'][0])){

		$id_grupo  = $dados['grupo_responsavel'][0];
		if(is_array($protocolo)){		      
			foreach($protocolo as $value){
				$query = "SELECT * FROM pav_inscritos WHERE protocol = $value";
				$foo = $a->queryFree($query);
				$select = $foo->fetch_assoc();
				
				if(isset($select['id'])){
					$query_upd = "UPDATE pav_inscritos SET grupo_responsavel = '$id_grupo' WHERE id = $select[id]";
					//$query_upd_group = "UPDATE pav__group_user SET id_group = '$id_grupo' WHERE id_pav = $select[id]"; // Não será mais utilizado
					//$a->queryFree($query_upd_group);
					$a->queryFree($query_upd);				
				}else{
					$valida_erro++;				
				}			
			}
			if($valida_erro > 0){
				echo $act->retornaMsg(9,"Erros encontrados $valida_erro");//mensagem de erro
			}else{
				echo $act->retornaMsg(11);//mensagem de finalizado com sucesso.
			}
		}else{
			$query = "SELECT * FROM pav_inscritos WHERE protocol = $protocolo";
			$foo = $a->queryFree($query);
			$select = $foo->fetch_assoc();        
			$id_grupo  = $dados['id_grupo'];			
			if(isset($select['id'])){
				$query_upd = "UPDATE pav_inscritos SET grupo_responsavel = '$id_grupo' WHERE id = $select[id]";
				//$query_upd_group = "UPDATE pav__group_user SET id_group = '$id_grupo' WHERE id_pav = $select[id]"; // Não será mais utilizado
				//$a->queryFree($query_upd_group);
				$a->queryFree($query_upd);
				echo $act->retornaMsg(11);//mensagem de finalizado com sucesso.
			}else{
				echo $act->retornaMsg(9);//mensagem de erro
			}
		}
		
	}else{
		echo $act->retornaMsg(9);//mensagem de erro
	}      
    break;

    case "pesquisa-cliente-por-contrato":
    # ------ Tratamento de gravação do provedor para chamados abertos via e-mail sem vínculo com provedores ativos no sistema -------- #
		/* por Adan Ribeiro - 10/10/2019 */
      $a = new Model;
      $query = "SELECT clientes.* FROM clientes INNER JOIN contratos ON clientes.id = contratos.id_cliente WHERE contratos.id = $_POST[id]";
      $foo = $a->queryFree($query);
      if($foo->num_rows > '0'){
        $cliente = $foo->fetch_assoc();
        # ------------ Verificação da agenda do cliente para saber quais são os seus contatos atuais --------------- #
        $foo = $a->queryFree("SELECT * FROM agenda_contatos WHERE id_cliente = $cliente[id]");        
        echo "
          <section class='contatos_agendados'>            
            <h4>Deseja cadastrar o e-mail do solicitante para o provedor selecionado?</h4>
            <table>
              <th>Contatos cadastrados atualmente para $cliente[nome]</th>";
        if($foo->num_rows > 0){        
          while($clientes = $foo->fetch_assoc()){
            if($clientes['contatos'] != ''){
                echo "<tr><td>$clientes[contatos]</td></tr>";
            }
          }
        }
        echo "</table>
            <hr>
            
              <div class='row'>
                
                  <div class='form-check'>
                    <label class='form-check-label'>
                    <input class='form-check-input' id='id_insere_blacklist' type='checkbox' value='1'> Inserir na blacklist
                        <span class='form-check-sign'>
                          <span class='check'></span>
                        </span>
                    </label>
                  </div>
                
              </div>
              
              <p>Clique no botão INSERIR para carregar o contato para salvamento.<br><em>ATENÇÃO: O sistema só atualizará os chamados futuros, feitos a partir DESTA atualização.</em></p>
              <div class='row'>
                <div class='form-group col-sm-12 text-right'>
                  <button type='button' class='btn btn-danger reset-modal' data-dismiss='modal'>Fechar</button>
                  <button type='button' class='btn btn-success rtrn-conteudo reset-modal' data-objeto='form_agenda_contatos'>Inserir</button>                
                </div>
              </div>            
            <form id='form_agenda_contatos'>  
              <input name='block' type='hidden' id='id_block' value />
              <input name='id_cliente' type='hidden' value='$cliente[id]' />
              <input name='contatos' class='input_contatos_emails' type='hidden' value='$_POST[email]' />
              <input name='caminho' type='hidden' value='controllers/sys/crud.sys.php' />
              <input name='retorno' type='hidden' value='#modalMsgRetorno' />
              <input name='flag' type='hidden' value='add' />
              <input name='tbl' type='hidden' value='agenda_contatos' />
              <input name='flag_agenda_contatos' type='hidden' value='1' />
            </form>
          </section>";  
      }
    break;

    case "insere-email-agenda-provedor-desconhecido":  
      $a = new Model;
      $query = "SELECT clientes.* FROM clientes INNER JOIN contratos ON clientes.id = contratos.id_cliente WHERE contratos.id = $_POST[id]";
      $foo = $a->queryFree($query);
      if($foo->num_rows > '0'){
        $cliente = $foo->fetch_assoc();
        $retorno = "<section class='input_hidden_contatos'>
          <input name='id_cliente' type='hidden' value='$cliente[id]' />
          <input name='contatos' class='input_contatos_emails' type='hidden' value='' />
          </section>";
        echo $retorno;
      }
    break;
    
		case "pesquisa-relatorio-call-center":
		$dados = $_POST;
		$a = new Model;   
		
		if(strtotime($dados['data_inicial']) <= strtotime($dados['data_final'])){
			
			$query = "SELECT Protocolo_Pav_Inscritos as Protocolo, `Status`,Origem, Cliente_Final as Cliente, Assunto_Nome, Entidade,Cidade,Departamento,Usuario, Data_Aberto, Data_Fechado, sec_to_time( Tempo_Atendimento) as TMA 
			FROM view_relatorioatendimentos where ID_Pav_Inscritos > 0 ";
		
			if($dados['atendente']!="Todos."){
				$query .=" and ID_Usuario_Abriu = ".$dados['atendente'];
				
			}
			if($dados['responsavel']!="Todos."){
				$query .=" and ID_Usuario_Fechou = ".$dados['responsavel'];
				
			}
			if($dados['entidade']!="Todos."){
				$query .=" and ID_Entidade = ".$dados['entidade'];
			}
			if(!empty($dados['cliente'])){
				$query .=" and Cliente_Final like '".$dados['cliente']."%'";
			}
			if($dados['assunto']!="Todos."){
				$query .=" and ID_Assunto = ".$dados['assunto'];
			}
			if($dados['origem']!="Todos."){
				$query .=" and Origem = '".$dados['origem']."'";
			}
			if($dados['nivel_assunto']!="Todos."){
				$query .=" and Assuto_Nivel = '".$dados['nivel_assunto']."'";
			}
			if($dados['status']!="Todos."){
				$query .=" and status = '".$dados['status']."'";
			}
			if($dados['finalizacao']!="Todos."){
				$query .=" and finalizacao = '".$dados['finalizacao']."'";
			}

			if($dados['departamento']!="Todos."){
				$query .=" and Departamento = '".$dados['departamento']."'";
			}
			if($dados['tipoAtendente']== "6"){
				$query .=" and Departamento = 'Call Center'";
			}
			if($dados['tipoAtendente']== "7"){
				$query .=" and Departamento = 'NOC'";
			}
			
			$query.= " and Data_Aberto BETWEEN '".$dados['data_inicial']." 00:00:00' AND '".$dados['data_final']." 23:59:59' ";

			$query .=" ORDER BY Data_Aberto ASC";
			
			//print_r($query);die();
			$result 	= $a-> queryFree($query);
		

			if ($result->num_rows>0) { 
				$data_ready = array();
				$result_consulta = array();

				while($row = $result->fetch_array())
				{
					$result_consulta[] = $row;						
				}
											
				$data_json = json_encode($result_consulta);
				echo $data_json;	
				
			}else{				
				echo "<div msg_dialog class='alerta' title='Clique para fechar.'>
				Aguardando resultado da pesquisa para exibir relatórios...
				</div>";
			}		

			}else{
				echo "<div msg_dialog class='alerta' title='Clique para fechar.'>
				Data Inicial maior que Data Final.<br>Favor corrigir e repita a pesquisa.
				</div>";
			}	
			
		break;

		case "pesquisa-relatorio-call-center-agrupado":
			$dados = $_POST;
			$a = new Model;
			$query="";
			$result;
			//print_r("setor = "+$dados['setor']);
			if(strtotime($dados['data_inicial']) <= strtotime($dados['data_final'])){
				
				
				// quando setor igual a 6 significa que são usuario é um supervisor do Call Center e precisa listar apenas atendentes do seu setor
				if($dados['setor']=="6"){
					$query = "CALL procedure_pontuacao_call_center ('".$dados['data_inicial']." 00:00:00','".$dados['data_final']." 23:59:59');";// A.tipo_atendente = 1 significa que são atendentes do call center
				// quando setor igual a 7 significa que são usuario é um supervisor do CGR e precisa listar apenas atendentes do seu setor
				}else if($dados['setor']=="7"){
					$query = "CALL procedure_pontuacao_noc ('".$dados['data_inicial']." 00:00:00','".$dados['data_final']." 23:59:59');";//A.tipo_atendente = 2 or A.tipo_atendente = 3 significa que podemos ter
																					// atendentes do CGR(2) e BeckOffice(3) 
				} else if($dados['setor']>"3"){
				
					if($dados['departamento']=="Call Center"){
						$query = "CALL procedure_pontuacao_call_center ('".$dados['data_inicial']." 00:00:00','".$dados['data_final']." 23:59:59');";

					} else if($dados['departamento']=="NOC"){
						$query = "CALL procedure_pontuacao_noc ('".$dados['data_inicial']." 00:00:00','".$dados['data_final']." 23:59:59');";
					}
				}

				
				//print_r($query);
				$result 	= $a-> queryFree($query);

				if ($result->num_rows>0) { 
					$data_ready = array();
					$result_consulta = array();
		
					while($row = $result->fetch_array())
					{
						$result_consulta[] = $row;						
					}
												
					$data_json = json_encode($result_consulta);
					echo $data_json;	
					
				}else{
					echo "<div>Aguardando resultado da pesquisa para exibir relatórios...</div>";
				}
				
			}else{
				echo "<div msg_dialog class='alerta' title='Clique para fechar.'>
				Data Inicial maior que Data Final.<br>Favor corrigir e repita a pesquisa.
				</div>";
			}	
			   
		break;
    
		case "pesquisa-relatorio-atendimentos-entidade":
			$dados = $_POST;
			$a = new Model;
			$queryComplemento = "";
				
			if(strtotime($dados['data_inicial']) <= strtotime($dados['data_final'])){		
				
				if($dados['entidade']!="Todos."){
					
					$queryComplemento .= " AND id_contratos = ".$dados['entidade'];
					
				}
				if($dados['status_chamado']!="Todos."){
					
					$queryComplemento .= " AND finalizado = '".$dados['status_chamado']."' ";
					
				}
				if($dados['tipo_usuario']!="Todos."){
					
					if($dados['tipo_usuario'] == 0){
						$queryComplemento .= " AND usuarios.id_contrato = 0 ";
					}elseif($dados['tipo_usuario'] != 0){
						$queryComplemento .= " AND usuarios.id_contrato != 0 ";
					}
					
					
				}

				$queryComplemento .= " AND data_abertura BETWEEN '".$dados['data_inicial']." 00:00:00' AND '".$dados['data_final']." 23:59:59' ";
			}
			
			$query = "SELECT  Nome , SUM(Call_Center) AS CallCenter,SUM( NOC) AS Noc, SUM( Call_Center + NOC) as Total
			FROM(

				SELECT  clientes.nome as Nome, COUNT(pav_inscritos.id) as Call_Center, '0' as NOC 
				FROM pav_inscritos INNER JOIN contratos ON contratos.id = pav_inscritos.id_contratos 
				INNER JOIN clientes ON contratos.id_cliente = clientes.id
				INNER JOIN usuarios ON usuarios.id = pav_inscritos.autor			
				WHERE pav_inscritos.lixo = 0 and pav_inscritos.atribuido_de = 1 ".$queryComplemento."
				GROUP BY pav_inscritos.id_contratos
				
				UNION (
					
					SELECT  clientes.nome as Nome, '0' as Call_Center, COUNT(pav_inscritos.id) as NOC 
					FROM pav_inscritos INNER JOIN contratos ON contratos.id = pav_inscritos.id_contratos 
					INNER JOIN clientes ON contratos.id_cliente = clientes.id
					INNER JOIN usuarios ON usuarios.id = pav_inscritos.autor			
					WHERE pav_inscritos.lixo = 0 and pav_inscritos.atribuido_de = 2 ".$queryComplemento."
					GROUP BY pav_inscritos.id_contratos			
				)
			)as Provedores
			GROUP BY Nome
			ORDER BY Nome ASC";				
							
			$result 	= $a-> queryFree($query);

			if ($result->num_rows>0) { 
				$data_ready = array();
				$result_consulta = array();
	
				while($row = $result->fetch_array())
				{
					$result_consulta[] = $row;						
				}
											
				$data_json = json_encode($result_consulta);
				echo $data_json;	
				
			}else{
				echo "<div>Está pesquisa não retornou nenhum resultado.</div>";
			}
				
			
			   
		break;

		case "pesquisa-relatorio-atendimentos-entidade-chamados":
			$dados = $_POST;
			$a = new Model;
			$queryComplemento = "";
				
			if(strtotime($dados['data_inicial']) <= strtotime($dados['data_final'])){		
				
				if($dados['entidade']!="Todos."){
					
					$queryComplemento .= " AND P.id_contratos = ".$dados['entidade'];
					
				}
				if($dados['status_chamado']!="Todos."){
					
					$queryComplemento .= " AND P.finalizado = '".$dados['status_chamado']."' ";
					
				}
				if($dados['departamento']!="Todos."){
					
					$queryComplemento .= " AND P.atribuido_de = '".$dados['departamento']."' ";
					
				}
				if($dados['tipo_usuario']!="Todos."){
					
					if($dados['tipo_usuario'] == 0){
						$queryComplemento .= " AND U.id_contrato = 0 ";
					}elseif($dados['tipo_usuario'] != 0){
						$queryComplemento .= " AND U.id_contrato != 0 ";
					}
					
					
				}

				$queryComplemento .= " AND P.data_abertura BETWEEN '".$dados['data_inicial']." 00:00:00' AND '".$dados['data_final']." 23:59:59' ";
			}
			
			$query = "SELECT 
			P.protocol,

			case 	
			when P.finalizado in (0) then 'Em Aberto'
			when P.finalizado in (1) then 'Finalizado'
			else '' end as Status_chamado,
			
			P.origem, 
			P.nome_cliente, 
			A.description, 
			U.nome,
			
			case 	
			when P.atribuido_de in (1) then 'LVCall'
			when P.atribuido_de in (2) then 'LVNoc'
			else '' end as Departamento, 
			
			P.data_abertura
			
			FROM pav_inscritos AS P 
			
			INNER JOIN contratos AS CT ON CT.id = P.id_contratos 
			INNER JOIN clientes AS CL ON CT.id_cliente = CL.id	
			INNER JOIN assuntos AS A ON A.id = P.assunto
			INNER JOIN usuarios AS U ON U.id = P.autor
			
			WHERE P.lixo = 0  ".$queryComplemento."
			ORDER BY P.protocol ASC";			
				
			$result 	= $a-> queryFree($query);

			if ($result->num_rows>0) { 
				$data_ready = array();
				$result_consulta = array();
	
				while($row = $result->fetch_array())
				{
					$result_consulta[] = $row;						
				}
											
				$data_json = json_encode($result_consulta);
				echo $data_json;	
				
			}else{
				echo "<div>Está pesquisa não retornou nenhum resultado.</div>";
			}
				
			
			   
		break;

		case "cgr_emabertos_para_mim": #validado = '0', status = '1'
		$dados = $_POST;
		$id = $dados['id'];
		$a = new Model;
		$e = new Acoes;	
		
		$query	= "SELECT pav_inscritos.* FROM pav_inscritos 
					 
					WHERE                
					pav_inscritos.atendente_responsavel = $id AND pav_inscritos.validado = 0 AND auditado = 0 AND pav_inscritos.finalizado = 0 AND pav_inscritos.atribuido_de = 2 
					GROUP BY protocol ORDER BY data_abertura ASC";
			
		$return	= $a->queryFree($query);
		if($return->num_rows > 0){
			while($linhas = $return->fetch_assoc()){
				$data_ready[] = $e->conteudoTabelaJSON($linhas, $dados['caminho'], "cgr_entrada2Nivel", NULL, NULL, "atendente_responsavel");				
			}
			$data_json = json_encode($data_ready);
			echo $data_json;
		}else{
			return false;
		}		
		break;

		case "cgr_emabertos_feitos_por_mim": #validado = '0', status = '1'
		$dados = $_POST;
		$id = $dados['id'];
		$a = new Model;
		$e = new Acoes;		
				
		$query = "SELECT pav_inscritos.* FROM pav_inscritos 
		 
		WHERE                
		pav_inscritos.autor = $id AND pav_inscritos.atendente_responsavel != $id AND  pav_inscritos.validado = 0 AND auditado = 0 AND pav_inscritos.finalizado = 0 AND pav_inscritos.atribuido_de = 2 
		GROUP BY protocol ORDER BY data_abertura ASC";
			
		$return	= $a->queryFree($query);
		if($return->num_rows > 0){
			while($linhas = $return->fetch_assoc()){
				$data_ready[] = $e->conteudoTabelaJSON($linhas, $dados['caminho'], "cgr_entrada2Nivel", NULL, NULL, "atendente_responsavel");				
			}
			$data_json = json_encode($data_ready);
			echo $data_json;
		}else{
			return false;
		}		
		break;

		case "pesquisa-relatorio-atendentes-noc":
		$dados = $_POST;
		$a = new Model;
		
		
		if(strtotime($dados['data_inicial']) <= strtotime($dados['data_final'])){
			
			$complementoSQL = " ";
		
			
			if($dados['entidade']!="Todos."){
				$complementoSQL .=" and ID_Entidade = ".$dados['entidade'];
			}
			
			if($dados['origem']!="Todos."){
				$complementoSQL .=" and Origem = '".$dados['origem']."'";
			}
			
			if($dados['status']!="Todos."){
				$complementoSQL .=" and status = '".$dados['status']."'";
			}
			if($dados['finalizacao']!="Todos."){
				$complementoSQL .=" and finalizacao = '".$dados['finalizacao']."'";
			}

			if($dados['departamento']!="Todos."){
				$complementoSQL .=" and Departamento = '".$dados['departamento']."'";
			}
			if($dados['setor']!="Todos."){
				$complementoSQL .=" and Setor = '".$dados['setor']."'";
			}
		
						
			$complementoSQL.= " and Data_Aberto BETWEEN '".$dados['data_inicial']." 00:00:00' AND '".$dados['data_final']." 23:59:59' ";

			$query =" SELECT (SELECT usuarios.nome FROM usuarios WHERE usuarios.id = id_usuario) as Nome_usuario,
					sum(Nivel_1) as Nivel_1,
					sum(Nivel_2) as Nivel_2,
					sum(Nivel_3) as Nivel_3, 
					sum(Nivel_4) as Nivel_4, 
					sum(Nivel_5) as Nivel_5,	
				    (sum(Nivel_1)+sum(Nivel_2)+sum(Nivel_3)+sum(Nivel_4)+sum(Nivel_5)) as Total				
					FROM (			
							SELECT 
							ID_Usuario_fechou as id_usuario, COUNT(ID_Usuario_fechou) as Nivel_1, '0' as Nivel_2, '0' as Nivel_3,  '0' as Nivel_4,  '0' as Nivel_5
							FROM view_relatorioatendimentos
							where   Assuto_Nivel = 1 $complementoSQL							
							GROUP BY ID_Usuario_fechou							
							UNION        	
							(								
								SELECT 
								ID_Usuario_fechou as id_usuario, '0' as Nivel_1, COUNT(ID_Usuario_fechou) as Nivel_2, '0' as Nivel_3,  '0' as Nivel_4,  '0' as Nivel_5
								FROM view_relatorioatendimentos
								where   Assuto_Nivel = 2 $complementoSQL								
								GROUP BY ID_Usuario_fechou
							)
							UNION        	
							(								
								SELECT 
								ID_Usuario_fechou as id_usuario, '0' as Nivel_1, '0' as Nivel_2, COUNT(ID_Usuario_fechou) as Nivel_3,  '0' as Nivel_4,  '0' as Nivel_5
								FROM view_relatorioatendimentos
								where   Assuto_Nivel = 3 $complementoSQL								 
								GROUP BY ID_Usuario_fechou
							)
							UNION        	
							(								
								SELECT 
								ID_Usuario_fechou as id_usuario, '0' as Nivel_1, '0' as Nivel_2, '0' as Nivel_3, COUNT(ID_Usuario_fechou) as Nivel_4,  '0' as Nivel_5
								FROM view_relatorioatendimentos
								where   Assuto_Nivel = 4 $complementoSQL								 
								GROUP BY ID_Usuario_fechou
							)
						
						
						)as Atendimentos 
					GROUP BY id_usuario
					ORDER BY Nome_usuario ASC";
			
			//print_r($query); die();
			$result 	= $a-> queryFree($query);
		

			if ($result->num_rows>0) { 
				$data_ready = array();
				$result_consulta = array();

				while($row = $result->fetch_array())
				{
					$result_consulta[] = $row;						
				}
											
				$data_json = json_encode($result_consulta);
				echo $data_json;	
				
			}else{				
				echo "<div msg_dialog class='alerta' title='Clique para fechar.'>
				Aguardando resultado da pesquisa para exibir relatórios...
				</div>";
			}		

			}else{
				echo "<div msg_dialog class='alerta' title='Clique para fechar.'>
				Data Inicial maior que Data Final.<br>Favor corrigir e repita a pesquisa.
				</div>";
			}	
			
		break;

		# ------------------- Listagem de chamados aberto pelo e-mail para o NOC --------------------- #
		case "cgr_emabertos_email": #validado = '0', status = '1'
		$dados = $_POST;
		$id = $dados['id'];
		$a = new Model;
		$e = new Acoes;	
		$query_privilegio = "SELECT * FROM usuarios WHERE id = $id";
		$foo = $a->queryFree($query_privilegio);
		$priv = $foo->fetch_assoc();
		if(isset($_SESSION['datalogin']['tipo_ambiente']) || $_SESSION['datalogin']['id_ambiente'] != 0){
			$id_contrato = $_SESSION['datalogin']['id_contrato'];
			$query	= "SELECT * FROM pav_inscritos 
			WHERE id_contratos = $id_contrato AND lixo = 0 AND pendente = 0 AND auditado = 0 AND validado = 0 AND finalizado != 1 AND atribuido_de = 2  AND assunto = 52
			OR (despachado = 1 AND atribuido_de = 2 AND pendente = 0 AND id_contratos = $id_contrato AND lixo = 0)
			ORDER BY data_abertura ASC";
		}else{
			if($priv['id_privilegio'] != 6){
				$query	= "SELECT * FROM pav_inscritos 
				WHERE lixo = 0 AND auditado = 0 AND validado = 0 AND finalizado != 1 AND atribuido_de = 2 AND assunto = 52
				ORDER BY data_abertura ASC";
			}else{
				$query	= "SELECT pav_inscritos.* FROM pav_inscritos 
				LEFT JOIN group_user ON group_user.group_id = pav_inscritos.grupo_responsavel 
				WHERE (group_user.user_id = $id AND pav_inscritos.validado = 0 AND auditado = 0 AND pav_inscritos.finalizado = 0 AND pav_inscritos.atribuido_de = 2  AND assunto = 52) 
				OR (pav_inscritos.atendente_responsavel = $id AND pav_inscritos.validado = 0 AND auditado = 0 AND pav_inscritos.finalizado = 0 AND pav_inscritos.atribuido_de = 2  AND assunto = 52) 
				OR (pav_inscritos.autor = $id AND pav_inscritos.validado = 0 AND auditado = 0 AND pav_inscritos.finalizado = 0 AND pav_inscritos.atribuido_de = 2  AND assunto = 52) 
				OR (pav_inscritos.atendente_responsavel = 0 AND pav_inscritos.validado = 0 AND auditado = 0 AND pav_inscritos.finalizado = 0 AND pav_inscritos.atribuido_de = 2  AND assunto = 52) 
				GROUP BY protocol ORDER BY data_abertura ASC";
			}
		}
		$return	= $a->queryFree($query);
		if($return->num_rows > 0){
			while($linhas = $return->fetch_assoc()){
				$data_ready[] = $e->conteudoTabelaJSON($linhas, $dados['caminho'], "cgr_entrada2Nivel", NULL, NULL, "atendente_responsavel");				
			}
			$data_json = json_encode($data_ready);
			echo $data_json;
		}else{
			return false;
		}		
		break;
		// --- Listagem de chamados em abertos que possui agendamento
		// --- Carrega listagem no arquivo servico_agendados.php 
		case "emabertos_agendados":	
		$datalogin   = $_SESSION['datalogin'];
		$dados = $_POST;
		$id = $dados['id'];
		$a = new Model;
		$e = new Acoes;
		$query_privilegio = "SELECT * FROM usuarios WHERE id = $id";
		$foo = $a->queryFree($query_privilegio);
		$priv = $foo->fetch_assoc();
		if($datalogin['id_ambiente'] == 0){
			
			if($priv['id_privilegio'] != 6){
				$query	= "SELECT * FROM pav_inscritos 
				WHERE lixo = 0 AND auditado = 0 AND validado = 0 AND finalizado != 1 AND atribuido_de != 2 AND data_expectativa_termino > now() AND status = 5
				ORDER BY data_abertura ASC";
			}else{
				$query = "SELECT pav_inscritos.* FROM pav_inscritos 
				INNER JOIN group_user ON group_user.group_id = pav_inscritos.grupo_responsavel  
				WHERE (group_user.user_id = $id AND pav_inscritos.validado = 0 AND auditado = 0 AND pav_inscritos.finalizado = 0 AND pav_inscritos.atribuido_de != 2 AND data_expectativa_termino > now() AND status = 5) 
				OR (pav_inscritos.atendente_responsavel = $id AND pav_inscritos.validado = 0 AND auditado = 0 AND pav_inscritos.finalizado = 0 AND pav_inscritos.atribuido_de != 2 AND data_expectativa_termino > now() AND status = 5) 
				OR (pav_inscritos.autor = $id AND pav_inscritos.validado = 0 AND auditado = 0 AND pav_inscritos.finalizado = 0 AND pav_inscritos.atribuido_de != 2 AND data_expectativa_termino > now() AND status = 5) 
				OR (pav_inscritos.atendente_responsavel = 0 AND pav_inscritos.validado = 0 AND auditado = 0 AND pav_inscritos.finalizado = 0 AND pav_inscritos.atribuido_de != 2 AND data_expectativa_termino > now() AND status = 5) 
				GROUP BY protocol 
				ORDER BY data_abertura ASC";
			}
		}		
		$return	= $a->queryFree($query);
		if($return->num_rows > 0){
			while($linhas = $return->fetch_assoc()){
				$data_ready[] = $e->listagemCallCenter($linhas, $dados['caminho'], "entrada2Nivel");
			}
			$data_json = json_encode($data_ready);
			echo $data_json;
		}else{
			return false;
		} 		
		break;

		// --- Listagem de chamados em abertos que possui agendamento 
		// --- Carrega listagem no arquivo servico_atrasados.php
		case "emabertos_atrasados":	
		$datalogin   = $_SESSION['datalogin'];
		$dados = $_POST;
		$id = $dados['id'];
		$a = new Model;
		$e = new Acoes;
		$query_privilegio = "SELECT * FROM usuarios WHERE id = $id";
		$foo = $a->queryFree($query_privilegio);
		$priv = $foo->fetch_assoc();
		if($datalogin['id_ambiente'] == 0){
			
			if($priv['id_privilegio'] != 6){
				$query	= "SELECT * FROM pav_inscritos 
				WHERE lixo = 0 AND auditado = 0 AND validado = 0 AND finalizado != 1 AND atribuido_de != 2 AND data_expectativa_termino < now() 
				ORDER BY data_abertura ASC";
			}else{
				$query = "SELECT pav_inscritos.* FROM pav_inscritos 
				INNER JOIN group_user ON group_user.group_id = pav_inscritos.grupo_responsavel  
				WHERE (group_user.user_id = $id AND pav_inscritos.validado = 0 AND auditado = 0 AND pav_inscritos.finalizado = 0 AND pav_inscritos.atribuido_de != 2 AND data_expectativa_termino < now()) 
				OR (pav_inscritos.atendente_responsavel = $id AND pav_inscritos.validado = 0 AND auditado = 0 AND pav_inscritos.finalizado = 0 AND pav_inscritos.atribuido_de != 2 AND data_expectativa_termino < now()) 
				OR (pav_inscritos.autor = $id AND pav_inscritos.validado = 0 AND auditado = 0 AND pav_inscritos.finalizado = 0 AND pav_inscritos.atribuido_de != 2 AND data_expectativa_termino < now()) 
				OR (pav_inscritos.atendente_responsavel = 0 AND pav_inscritos.validado = 0 AND auditado = 0 AND pav_inscritos.finalizado = 0 AND pav_inscritos.atribuido_de != 2 AND data_expectativa_termino < now()) 
				GROUP BY protocol 
				ORDER BY data_abertura ASC";
			}
		}		
		$return	= $a->queryFree($query);
		if($return->num_rows > 0){
			while($linhas = $return->fetch_assoc()){
				$data_ready[] = $e->listagemCallCenter($linhas, $dados['caminho'], "entrada2Nivel");
			}
			$data_json = json_encode($data_ready);
			echo $data_json;
		}else{
			return false;
		} 		
		break;

		// ------------------- Chamados em aberto do Noc que ja tiveram agendamento --------------------- 
		// --- Carrega listagem no arquivo cgr-chamados-abertos-agendados.php
		case "cgr_emabertos_agendados": #validado = '0', status = '1'
		$dados = $_POST;
		$id = $dados['id'];
		$a = new Model;
		$e = new Acoes;	
		$query_privilegio = "SELECT * FROM usuarios WHERE id = $id";
		$foo = $a->queryFree($query_privilegio);
		$priv = $foo->fetch_assoc();
		
			if($priv['id_privilegio'] != 6){
				$query	= "SELECT * 
				FROM pav_inscritos WHERE lixo = 0 AND auditado = 0 AND validado = 0 
				AND finalizado != 1 AND atribuido_de = 2 AND data_expectativa_termino > now() AND status = 5        
				ORDER BY data_abertura ASC";
			}else{
				$query	= "SELECT pav_inscritos.* 
				 FROM pav_inscritos 
				 LEFT JOIN group_user ON pav_inscritos.grupo_responsavel = group_user.group_id
				WHERE (group_user.user_id = $id AND pav_inscritos.validado = 0 AND auditado = 0 AND pav_inscritos.finalizado = 0 AND pav_inscritos.atribuido_de = 2 AND data_expectativa_termino > now() AND status = 5) 
				OR (pav_inscritos.atendente_responsavel = $id AND pav_inscritos.validado = 0 AND auditado = 0 AND pav_inscritos.finalizado = 0 AND pav_inscritos.atribuido_de = 2 AND data_expectativa_termino > now() AND status = 5) 
				OR (pav_inscritos.autor = $id AND pav_inscritos.validado = 0 AND auditado = 0 AND pav_inscritos.finalizado = 0 AND pav_inscritos.atribuido_de = 2 AND data_expectativa_termino > now() AND status = 5) 
				OR (pav_inscritos.atendente_responsavel = 0 AND pav_inscritos.validado = 0 AND auditado = 0 AND pav_inscritos.finalizado = 0 AND pav_inscritos.atribuido_de = 2 AND data_expectativa_termino > now() AND status = 5)
				GROUP BY protocol
				ORDER BY data_abertura ASC";
			}
		
		$return	= $a->queryFree($query);
		if($return->num_rows > 0){
			while($linhas = $return->fetch_assoc()){
				$data_ready[] = $e->conteudoTabelaJSON($linhas, $dados['caminho'], "cgr_entrada2Nivel", NULL, NULL, "atendente_responsavel");				
			}
			$data_json = json_encode($data_ready);
			echo $data_json;
		}else{
			return false;
		}		
		break;

		// ------------------- Chamados em aberto do Noc que estão atrasados --------------------- 
		// --- Carrega listagem no arquivo cgr-chamados-abertos-atrasados.php
		case "cgr_emabertos_atrasados": #validado = '0', status = '1'
		$dados = $_POST;
		$id = $dados['id'];
		$a = new Model;
		$e = new Acoes;	
		$query_privilegio = "SELECT * FROM usuarios WHERE id = $id";
		$foo = $a->queryFree($query_privilegio);
		$priv = $foo->fetch_assoc();
		
			if($priv['id_privilegio'] != 6){
				$query	= "SELECT * 
				 FROM pav_inscritos WHERE lixo = 0 AND auditado = 0 AND validado = 0 AND finalizado != 1 
				 AND atribuido_de = 2 AND data_expectativa_termino < now()        
				ORDER BY data_abertura ASC";
			}else{
				$query	= "SELECT pav_inscritos.* 
				 FROM pav_inscritos 
				LEFT JOIN group_user ON pav_inscritos.grupo_responsavel = group_user.group_id
			  	WHERE (group_user.user_id = $id AND pav_inscritos.validado = 0 AND auditado = 0 AND pav_inscritos.finalizado = 0 AND pav_inscritos.atribuido_de = 2 AND data_expectativa_termino < now()) 
				OR (pav_inscritos.atendente_responsavel = $id AND pav_inscritos.validado = 0 AND auditado = 0 AND pav_inscritos.finalizado = 0 AND pav_inscritos.atribuido_de = 2 AND data_expectativa_termino < now()) 
				OR (pav_inscritos.autor = $id AND pav_inscritos.validado = 0 AND auditado = 0 AND pav_inscritos.finalizado = 0 AND pav_inscritos.atribuido_de = 2 AND data_expectativa_termino < now()) 
				OR (pav_inscritos.atendente_responsavel = 0 AND pav_inscritos.validado = 0 AND auditado = 0 AND pav_inscritos.finalizado = 0 AND pav_inscritos.atribuido_de = 2 AND data_expectativa_termino < now())
        		ORDER BY data_abertura ASC";
			}
		
		$return	= $a->queryFree($query);
		if($return->num_rows > 0){
			while($linhas = $return->fetch_assoc()){
				$data_ready[] = $e->conteudoTabelaJSON($linhas, $dados['caminho'], "cgr_entrada2Nivel", NULL, NULL, "atendente_responsavel");				
			}
			$data_json = json_encode($data_ready);
			echo $data_json;
		}else{
			return false;
		}		
		break;

		// ------------------- Chamados aguardando retorno agendados Call Center --------------------- 
		// --- Carrega listagem no arquivo retorno-agendados.php
		case "pesquisa_retorno_agendados":
		$dados = $_POST;
		$a = new Model;
		$e = new Acoes;
		if(isset($_SESSION['datalogin']['id_ambiente'])){
			$id_ambiente = $_SESSION['datalogin']['id_ambiente'];
		}else{
			$id_ambiente = $_SESSION['datalogin']['tipo_ambiente'];
		}
		
		if($id_ambiente == 0){ # ambiente LV
			$query = "SELECT * 
			FROM pav_inscritos WHERE validado = 1 AND auditado = 0 AND finalizado = 0 AND atribuido_de != 2 
			AND data_expectativa_termino > now() AND status = 5 
			ORDER BY data_abertura DESC";
		}	
		$foo = $a->queryFree($query);
		if($foo->num_rows > 0){
			while($linhas = $foo->fetch_assoc()){
				$data_ready[] = $e->listagemCallCenter($linhas, $dados['caminho'], "entradaRetorno2nivel");				
			}
			$data_json = json_encode($data_ready);
			echo $data_json;
		}else{
			return false;
		}	
		break;

		// ------------------- Chamados aguardando retorno atrasados Call Center --------------------- 
		// --- Carrega listagem no arquivo retorno-atrasados.php
		case "pesquisa_retorno_atrasados":
		$dados = $_POST;
		$a = new Model;
		$e = new Acoes;
		if(isset($_SESSION['datalogin']['id_ambiente'])){
			$id_ambiente = $_SESSION['datalogin']['id_ambiente'];
		}else{
			$id_ambiente = $_SESSION['datalogin']['tipo_ambiente'];
		}
		
		if($id_ambiente == 0){ # ambiente LV
			$query = "SELECT * 
			FROM pav_inscritos WHERE validado = 1 AND auditado = 0 AND finalizado = 0 AND atribuido_de != 2 
			AND data_expectativa_termino < now() 
			ORDER BY data_abertura DESC";
		}	
		$foo = $a->queryFree($query);
		if($foo->num_rows > 0){
			while($linhas = $foo->fetch_assoc()){
				$data_ready[] = $e->listagemCallCenter($linhas, $dados['caminho'], "entradaRetorno2nivel");				
			}
			$data_json = json_encode($data_ready);
			echo $data_json;
		}else{
			return false;
		}	
		break;

		// ------------------- Chamados aguardando finalizar agendados Noc --------------------- 
		// --- Carrega listagem no arquivo aguardando-finalizar-agendados.php
		case "cgr_aguardandofinalizar_agendados": // Entrada de dados aguardando finalização que vieram do CGR			
			$dados = $_POST;
			$id = $dados['id'];
			$a = new Model;
			$e = new Acoes;
			$query_privilegio = "SELECT * FROM usuarios WHERE id = $id";
			$foo = $a->queryFree($query_privilegio);
			$priv = $foo->fetch_assoc();
			
				if($priv['id_privilegio'] != 6){
					$query	= "SELECT * 
					FROM pav_inscritos  WHERE lixo = 0 AND auditado = 0 AND validado = 1 AND finalizado != 1
					AND atribuido_de = 2 AND data_expectativa_termino > now() AND status = 5        
					ORDER BY data_abertura ASC";
				}else{
					$query = "SELECT pav_inscritos.* 
					 FROM pav_inscritos 
					LEFT JOIN group_user ON group_user.group_id = pav_inscritos.grupo_responsavel
					WHERE (group_user.user_id = $id AND auditado = 0 AND validado = 1 AND finalizado != 1 AND atribuido_de = 2 AND pav_inscritos.autor = $id AND data_expectativa_termino > now() AND status = 5) 
					OR (pav_inscritos.atendente_responsavel = $id AND auditado = 0 AND validado = 1 AND finalizado != 1 AND atribuido_de = 2 AND data_expectativa_termino > now() AND status = 5) 
					OR (pav_inscritos.atendente_responsavel = 0 AND auditado = 0 AND validado = 1 AND finalizado != 1 AND atribuido_de = 2 AND data_expectativa_termino > now() AND status = 5)
					OR (pav_inscritos.grupo_responsavel = 0 AND pav_inscritos.lixo = 0 AND auditado = 0 AND validado = 1 AND finalizado != 1 AND atribuido_de = 2 AND data_expectativa_termino > now() AND status = 5)
					GROUP BY protocol 
					ORDER BY `pav_inscritos`.`id` ASC";
				}
			
			$return	= $a->queryFree($query);
			if($return->num_rows > 0){
				while($linhas = $return->fetch_assoc()){
					$data_ready[] = $e->conteudoTabelaJSON($linhas, $dados['caminho'], "cgr_retorno2Nivel", NULL, NULL, "atendente_responsavel");
				}
				$data_json = json_encode($data_ready);
				echo $data_json;
			}else{
				return false;
			} 	
		break;

		// ------------------- Chamados aguardando finalizar atrasados Noc --------------------- 
		// --- Carrega listagem no arquivo aguardando-finalizar-atrasados.php
		case "cgr_aguardandofinalizar_atrasados": // Entrada de dados aguardando finalização que vieram do CGR			
			$dados = $_POST;
			$id = $dados['id'];
			$a = new Model;
			$e = new Acoes;
			$query_privilegio = "SELECT * FROM usuarios WHERE id = $id";
			$foo = $a->queryFree($query_privilegio);
			$priv = $foo->fetch_assoc();
			
				if($priv['id_privilegio'] != 6){
					$query	= "SELECT * 
					FROM pav_inscritos  WHERE lixo = 0 AND auditado = 0 AND validado = 1 AND finalizado != 1
					AND atribuido_de = 2 AND data_expectativa_termino < now()        
					ORDER BY data_abertura ASC";
				}else{
					$query = "SELECT pav_inscritos.* 
					 FROM pav_inscritos 
					LEFT JOIN group_user ON group_user.group_id = pav_inscritos.grupo_responsavel 
					WHERE (group_user.user_id = $id AND auditado = 0 AND validado = 1 AND finalizado != 1 AND atribuido_de = 2 AND pav_inscritos.autor = $id AND data_expectativa_termino > now() AND status = 5) 
					OR (pav_inscritos.atendente_responsavel = $id AND auditado = 0 AND validado = 1 AND finalizado != 1 AND atribuido_de = 2 AND data_expectativa_termino > now() AND status = 5) 
					OR (pav_inscritos.atendente_responsavel = 0 AND auditado = 0 AND validado = 1 AND finalizado != 1 AND atribuido_de = 2 AND data_expectativa_termino > now() AND status = 5)
					OR (pav_inscritos.grupo_responsavel = 0 AND pav_inscritos.lixo = 0 AND auditado = 0 AND validado = 1 AND finalizado != 1 AND atribuido_de = 2 AND data_expectativa_termino > now() AND status = 5)
					GROUP BY protocol
					ORDER BY `pav_inscritos`.`id` ASC";
				}
			
			$return	= $a->queryFree($query);
			if($return->num_rows > 0){
				while($linhas = $return->fetch_assoc()){
					$data_ready[] = $e->conteudoTabelaJSON($linhas, $dados['caminho'], "cgr_retorno2Nivel", NULL, NULL, "atendente_responsavel");
				}
				$data_json = json_encode($data_ready);
				echo $data_json;
			}else{
				return false;
			} 	
		break;

		// ------------------- Chamados em aberto para mim agendados Noc --------------------- 
		// --- Carrega listagem no arquivo cgr-chamados-abertos-para-mim-agendados.php			
		case "cgr_emabertos_para_mim_agendados": #validado = '0', status = '1'
		$dados = $_POST;
		$id = $dados['id'];
		$a = new Model;
		$e = new Acoes;	
		
		$query	= "SELECT pav_inscritos.* 
		FROM pav_inscritos 
        
        WHERE pav_inscritos.atendente_responsavel = $id AND pav_inscritos.validado = 0 AND auditado = 0 
		AND pav_inscritos.finalizado = 0 AND pav_inscritos.atribuido_de = 2 AND data_expectativa_termino > now() AND status = 5
        GROUP BY protocol ORDER BY data_abertura ASC";
			
		$return	= $a->queryFree($query);
		if($return->num_rows > 0){
			while($linhas = $return->fetch_assoc()){
				$data_ready[] = $e->conteudoTabelaJSON($linhas, $dados['caminho'], "cgr_entrada2Nivel", NULL, NULL, "atendente_responsavel");				
			}
			$data_json = json_encode($data_ready);
			echo $data_json;
		}else{
			return false;
		}		
		break;

		// ------------------- Chamados em aberto para mim atrasados Noc --------------------- 
		// --- Carrega listagem no arquivo cgr-chamados-abertos-para-mim-atrasados.php			
		case "cgr_emabertos_para_mim_atrasados": #validado = '0', status = '1'
		$dados = $_POST;
		$id = $dados['id'];
		$a = new Model;
		$e = new Acoes;	
		
		$query	= "SELECT pav_inscritos.* 
		FROM pav_inscritos 
        
        WHERE pav_inscritos.atendente_responsavel = $id AND pav_inscritos.validado = 0 AND auditado = 0 
		AND pav_inscritos.finalizado = 0 AND pav_inscritos.atribuido_de = 2 AND data_expectativa_termino < now()
        GROUP BY protocol ORDER BY data_abertura ASC";
			
		$return	= $a->queryFree($query);
		if($return->num_rows > 0){
			while($linhas = $return->fetch_assoc()){
				$data_ready[] = $e->conteudoTabelaJSON($linhas, $dados['caminho'], "cgr_entrada2Nivel", NULL, NULL, "atendente_responsavel");				
			}
			$data_json = json_encode($data_ready);
			echo $data_json;
		}else{
			return false;
		}		
		break;

		// ------------------- Chamados em aberto por mim agendados Noc --------------------- 
		// --- Carrega listagem no arquivo cgr-chamados-abertos-por-mim-agendados.php	
		case "cgr_emabertos_feitos_por_mim_agendados": #validado = '0', status = '1'
		$dados = $_POST;
		$id = $dados['id'];
		$a = new Model;
		$e = new Acoes;		
				
		$query = "SELECT pav_inscritos.* 
		FROM pav_inscritos 
          
          WHERE                
          pav_inscritos.autor = $id AND pav_inscritos.atendente_responsavel != $id AND  pav_inscritos.validado = 0 
		  AND auditado = 0 AND pav_inscritos.finalizado = 0 AND pav_inscritos.atribuido_de = 2 AND data_expectativa_termino > now() AND status = 5
          GROUP BY protocol ORDER BY data_abertura ASC";
			
		$return	= $a->queryFree($query);
		if($return->num_rows > 0){
			while($linhas = $return->fetch_assoc()){
				$data_ready[] = $e->conteudoTabelaJSON($linhas, $dados['caminho'], "cgr_entrada2Nivel", NULL, NULL, "atendente_responsavel");				
			}
			$data_json = json_encode($data_ready);
			echo $data_json;
		}else{
			return false;
		}		
		break;

		// ------------------- Chamados em aberto por mim atrasados Noc --------------------- 
		// --- Carrega listagem no arquivo cgr-chamados-abertos-por-mim-atrasados.php	
		case "cgr_emabertos_feitos_por_mim_atrasados": #validado = '0', status = '1'
		$dados = $_POST;
		$id = $dados['id'];
		$a = new Model;
		$e = new Acoes;		
				
		$query = "SELECT pav_inscritos.* 
		FROM pav_inscritos 
          
          WHERE                
          pav_inscritos.autor = $id AND pav_inscritos.atendente_responsavel != $id AND  pav_inscritos.validado = 0 
		  AND auditado = 0 AND pav_inscritos.finalizado = 0 AND pav_inscritos.atribuido_de = 2 AND data_expectativa_termino < now()
          GROUP BY protocol ORDER BY data_abertura ASC";
			
		$return	= $a->queryFree($query);
		if($return->num_rows > 0){
			while($linhas = $return->fetch_assoc()){
				$data_ready[] = $e->conteudoTabelaJSON($linhas, $dados['caminho'], "cgr_entrada2Nivel", NULL, NULL, "atendente_responsavel");				
			}
			$data_json = json_encode($data_ready);
			echo $data_json;
		}else{
			return false;
		}		
		break;

		// ------------------- Chamados em aberto por email que estão Agendados --------------------- 
		// --- Carrega listagem no arquivo cgr-chamados-abertos-email-agendados.php	
		case "cgr_emabertos_email_agendados": #validado = '0', status = '1'
		$dados = $_POST;
		$id = $dados['id'];
		$a = new Model;
		$e = new Acoes;	
		$query_privilegio = "SELECT * FROM usuarios WHERE id = $id";
		$foo = $a->queryFree($query_privilegio);
		$priv = $foo->fetch_assoc();
		
			if($priv['id_privilegio'] != 6){
				$query	= "SELECT * FROM pav_inscritos 
				WHERE lixo = 0 AND auditado = 0 AND validado = 0 AND finalizado != 1 AND atribuido_de = 2 
				AND assunto = 52  AND data_expectativa_termino > now() AND status = 5
				ORDER BY data_abertura ASC";
			}else{
				$query	= "SELECT pav_inscritos.* 
				FROM pav_inscritos 
				LEFT JOIN group_user ON group_user.group_id = pav_inscritos.grupo_responsavel
				WHERE (group_user.user_id = $id AND pav_inscritos.validado = 0 AND auditado = 0 AND pav_inscritos.finalizado = 0 AND pav_inscritos.atribuido_de = 2 AND assunto = 52 AND data_expectativa_termino > now() AND status = 5) 
				OR (pav_inscritos.atendente_responsavel = $id AND pav_inscritos.validado = 0 AND auditado = 0 AND pav_inscritos.finalizado = 0 AND pav_inscritos.atribuido_de = 2 AND assunto = 52 AND data_expectativa_termino > now() AND status = 5) 
				OR (pav_inscritos.autor = $id AND pav_inscritos.validado = 0 AND auditado = 0 AND pav_inscritos.finalizado = 0 AND pav_inscritos.atribuido_de = 2 AND assunto = 52 AND data_expectativa_termino > now() AND status = 5) 
				OR (pav_inscritos.atendente_responsavel = 0 AND pav_inscritos.validado = 0 AND auditado = 0 AND pav_inscritos.finalizado = 0 AND pav_inscritos.atribuido_de = 2 AND assunto = 52 AND data_expectativa_termino > now() AND status = 5) 
				GROUP BY protocol ORDER BY data_abertura ASC";
			}
		
		$return	= $a->queryFree($query);
		if($return->num_rows > 0){
			while($linhas = $return->fetch_assoc()){
				$data_ready[] = $e->conteudoTabelaJSON($linhas, $dados['caminho'], "cgr_entrada2Nivel", NULL, NULL, "atendente_responsavel");				
			}
			$data_json = json_encode($data_ready);
			echo $data_json;
		}else{
			return false;
		}		
		break;

		// ------------------- Chamados em aberto por email que estão Atrasados --------------------- 
		// --- Carrega listagem no arquivo cgr-chamados-abertos-email-atrasados.php	
		case "cgr_emabertos_email_atrasados": #validado = '0', status = '1'
		$dados = $_POST;
		$id = $dados['id'];
		$a = new Model;
		$e = new Acoes;	
		$query_privilegio = "SELECT * FROM usuarios WHERE id = $id";
		$foo = $a->queryFree($query_privilegio);
		$priv = $foo->fetch_assoc();
		
			if($priv['id_privilegio'] != 6){
				$query	= "SELECT * FROM pav_inscritos 
				WHERE lixo = 0 AND auditado = 0 AND validado = 0 AND finalizado != 1 AND atribuido_de = 2 
				AND assunto = 52  AND data_expectativa_termino < now()
				ORDER BY data_abertura ASC";
			}else{
				$query	= "SELECT pav_inscritos.* 
				FROM pav_inscritos 
				LEFT JOIN group_user ON group_user.group_id = pav_inscritos.grupo_responsavel 
				WHERE (group_user.user_id = $id AND pav_inscritos.validado = 0 AND auditado = 0 AND pav_inscritos.finalizado = 0 AND pav_inscritos.atribuido_de = 2 AND assunto = 52 AND data_expectativa_termino < now()) 
				OR (pav_inscritos.atendente_responsavel = $id AND pav_inscritos.validado = 0 AND auditado = 0 AND pav_inscritos.finalizado = 0 AND pav_inscritos.atribuido_de = 2 AND assunto = 52 AND data_expectativa_termino < now()) 
				OR (pav_inscritos.autor = $id AND pav_inscritos.validado = 0 AND auditado = 0 AND pav_inscritos.finalizado = 0 AND pav_inscritos.atribuido_de = 2 AND assunto = 52 AND data_expectativa_termino < now()) 
				OR (pav_inscritos.atendente_responsavel = 0 AND pav_inscritos.validado = 0 AND auditado = 0 AND pav_inscritos.finalizado = 0 AND pav_inscritos.atribuido_de = 2 AND assunto = 52 AND data_expectativa_termino < now()) 
				GROUP BY protocol ORDER BY data_abertura ASC";
			}
		
		$return	= $a->queryFree($query);
		if($return->num_rows > 0){
			while($linhas = $return->fetch_assoc()){
				$data_ready[] = $e->conteudoTabelaJSON($linhas, $dados['caminho'], "cgr_entrada2Nivel", NULL, NULL, "atendente_responsavel");				
			}
			$data_json = json_encode($data_ready);
			echo $data_json;
		}else{
			return false;
		}		
		break;

		// ------------------- Buscando ultima movimentaçao --------------------- 
		// --- Carrega listagem no arquivo cgr-chamados-abertos-email-atrasados.php	
		case "buscar_ultima_movimentacao": #validado = '0', status = '1'
		$dados = $_POST;
		$id = $dados['id'];
		$a = new Model;
				
		$query_privilegio = "SELECT pav_movimentos.descricao 
		FROM `pav_movimentos` 
		WHERE pav_movimentos.id = (SELECT MAX(id) FROM pav_movimentos WHERE pav_movimentos.id_pav_inscritos = $id )";
		$foo = $a->queryFree($query_privilegio);
		$priv = $foo->fetch_assoc();
		
		echo $priv['descricao'];
		
		break;

		case "consulta_dados_entidade":
			$dados = $_POST;
			$id = $dados['id'];
			$a = new Model;
			
			if($id == ''){
				echo"";
			}else{		
				$query = "SELECT clientes.*
					FROM clientes 
					INNER JOIN contratos ON clientes.id = contratos.id_cliente			
					WHERE contratos.id =  $id ";
				
				$resultado = $a->queryFree($query);
				$dados_entidade = $resultado->fetch_assoc();

				if(is_null($dados_entidade['cod_cliente'])){
					$codCliente = "[0000]";
				}else{
					$codCliente = "[".$dados_entidade['cod_cliente']."]";
				}
				
				$texto = "
							<h4>Cliente: <strong>". $codCliente." - ". $dados_entidade['nome'] ."</strong> </h4>
							
								<strong>Razão social: </strong> ". $dados_entidade['razao_social'] ."  <br>
								<strong>Razão CNPJ: </strong> ".   $dados_entidade['cpf_cnpj'] ."  <br>
								<strong>Contato legal: </strong>". $dados_entidade['contato'] ."  <br>
								<strong>E-mail: </strong> ".       $dados_entidade['usuario'] ."  <br>
								<strong>Telefone: </strong> ". 	   $dados_entidade['telefones'] ."  <br>								
								<strong>Endereço: </strong>  ".    $dados_entidade['endereco'] .", 
								n°: ". 							   $dados_entidade['numero'] ."
								-  ". 							   $dados_entidade['complemento'] ." <br>
								<strong>Bairro: </strong> ". 	   $dados_entidade['bairro'] ." - 
								<strong>Cidade: </strong>   ". 	   $dados_entidade['cidade'] ." -  
								<strong>Cep: </strong>   ". 	   $dados_entidade['cep'] ." <br>

						";


				echo $texto;
			}
		break;
				
	}

	


	// ------------------------------ INICIO ---------------------------------
	//-------------- inserção de dados no banco de dados "RADIUS" ------------	
	if(isset($_POST['flagRadius'])){
		switch ($_POST['flagRadius']) {

			case "update":		#Código de Atualização da Edição			
				$dados = $_POST;
				#print_r($dados);die();

				$a  = new Model();
				$query = "";

				# Cancelamento do lock na saída dos registros -> Adan 02/06/2019

				
				if (isset($dados['usuario'])) {
					
					if ($dados['acessoRadius'] == 0) {
						$pieces2   = explode("@", $dados['usernameAnterior']);
						$usernameAnterior = $pieces2[0];

						$pieces   = explode("@", $dados['usuario']);
						$username =  $pieces[0];
						$senha =  $dados['senha'];
						$grupo =  $dados['grupoRadius'];

						$query = "SELECT id FROM radcheck WHERE username = '" . $usernameAnterior . "' ";
						
						$consulta = $a->queryFreeRadius($query);					
						
						if ($consulta->num_rows > 0) {

							$query = "UPDATE radcheck SET username = '" . $username . "', `value` = '" . $senha . "' WHERE username = '" . $usernameAnterior . "'";
							
							$a->queryFreeRadius($query);


							$query = "UPDATE radreply SET username = '" . $username . "', `value` = '" . $grupo . "' WHERE username = '" . $usernameAnterior . "'";
							$a->queryFreeRadius($query);
						} else {
							$query = "INSERT INTO radcheck (`username`,`value`) VALUES ('" . $username . "','" . $senha . "'); ";
							$a->queryFreeRadius($query);

							$query = "INSERT INTO radreply (`username`,`value`) VALUES ('" . $username . "','" . $grupo . "'); ";
							$a->queryFreeRadius($query);
						}
					}
				}

				break;

			case "add":		#Código de Atualização da Edição			
				$dados = $_POST;
				#print_r($dados);die();		

				$a  = new Model();
				$query = "";

				# Cancelamento do lock na saída dos registros -> Adan 02/06/2019


				if (isset($dados['usuario'])) {

					$pieces   = explode("@", $dados['usuario']);

					$username =  $pieces[0];
					$senha =  $dados['senha'];
					$grupo =  $dados['grupoRadius'];
					
					if ($dados['acessoRadius'] == 0) {
						$query = "INSERT INTO radcheck (`username`,`value`) VALUES ('" . $username . "','" . $senha . "'); ";
						$a->queryFreeRadius($query);				

						$query = "INSERT INTO radreply (`username`,`value`) VALUES ('" . $username . "','" . $grupo . "'); ";
						$a->queryFreeRadius($query);
					}
				}


				break; 

			case "exc":		#Código de Exclusão
				$dados = $_POST;
				$id  =  $dados["id"];
				$a = new Model();
				$usuario;
				$query = "";


				$query = "SELECT nome FROM usuarios WHERE id = " . $id;
				$consulta = $a->queryFree($query);
				$retorno = $consulta->fetch_assoc();

				if (!is_null($retorno['nome'])) {

					$pieces   = explode("@", $retorno['nome']);
					$usuario = $pieces[0];

					$query = "DELETE FROM `radreply` WHERE `radreply`.`username` = '" . $usuario . "' ";
					$a->queryFreeRadius($query);

					$query = "DELETE FROM `radcheck` WHERE `radcheck`.`username` = '" . $usuario . "' ";
					$a->queryFreeRadius($query);
				}




				break;
		}
	}
	//-------------- inserção de dados no banco de dados "RADIUS" ------------
	// ------------------------------ FIM ------------------------------------


}else{
	echo "<h1>A requisição está vazia. <br> Procure por erros de configuração no servidor.</h1>";
}
?>