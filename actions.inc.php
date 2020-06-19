<?php
/*Classes de uso para frontend. Criação de controles para rotinas comuns ao UX.
* Autor: Adan Ribeiro
* Template: LV Desk
* Data: 06/06/2018
*
*/
class Acoes{
/*
* SUMÁRIO
* $id 				= Código do registro.
* $tabela 			= Nome da tabela.
* $callbackdelete 	= Página que vai retornar após a exclusão do registro.
* $callbackedit 	= Página que vai retornar após a edição do registro. Esse link coincide com outras ações de POST.
* $link 			= Destino do POST.
*/
  public function crudButtons($id, $callbackdelete, $callbackedit, $link, $lixo){
	if(!is_null($callbackedit)){
		echo "	
		<a class='btn btn-warning rtrn-conteudo-listagem' item=".$id." flag='edt' data-objeto='form_action' caminho=".$callbackedit." title='Editar registro'>Editar</a>";
	}
	if($lixo == 1){
		if(!is_null($callbackdelete)){
			echo "	
			<a class='btn btn-success rtrn-conteudo-listagem botao' item=".$id." flag='exc' data-objeto='form_action' caminho=".$link." title='Excluir registro' data-toggle='modal' data-target='#confirma'>&nbsp; &nbsp; Ativar &nbsp; &nbsp;</a>";
		
		}
	}else{
		if(!is_null($callbackdelete)){
			echo "	
			<a class='btn btn-danger rtrn-conteudo-listagem botao' item=".$id." flag='exc' data-objeto='form_action' caminho=".$link." title='Excluir registro' data-toggle='modal' data-target='#confirma'>Desativar</a>";
		
		}
	}
  }

 public function crudTableButtons($id, $callbackdelete, $callbackedit, $link, $table = NULL){
	if(!is_null($callbackedit)){
		echo "	
		<a class='btn btn-warning' data-item=".$id." flag='edt-table' data-objeto='form_action' data-caminho=".$callbackedit." title='Editar registro'>Editar</a>";
	}
	if(!is_null($callbackdelete)){
		if(is_null($table)){
			echo "<a class='btn btn-danger exc-table-reg botao' data-item='$id' data-flag='exc-table' data-caminho='$link' title='Excluir registro' >Excluir</a>";
		}else{
			echo "<a class='btn btn-danger exc-table-reg botao' data-item='$id' data-flag='exc-table' data-caminho='$link' title='Excluir registro' data-table='$table' >Excluir</a>";
		}
	}
  }
  public function retornaMsg($id, $param1 = NULL){
		$a = new Model();
		$query = "SELECT * FROM sysmsg WHERE id = '$id'";
		$resultado = $a->queryFree($query);
		if($resultado->num_rows > 0){
			$foo = $resultado->fetch_assoc();
			$mensagem = "<div class='alert alert-$foo[tipo]'>
				<div class='row'>
				  <div class='col-sm-10'><h4>$foo[title]</h4></div>
				  <div class='col-sm-2 text-right'><i style='color:white; font-size: 2rem;' class='material-icons'>$foo[icone]</i></div>
				</div>
				<hr>
				<div class='row'>
				  <div class='col-sm-10'>
					<p>Código #00$foo[codigo] - $foo[texto]</p>
					<p>$foo[obs]</p><p>".(!is_null($param1) ? $param1 : '')."</p>					
				  </div>
				</div>
			</div>
			";
			return $mensagem;
		}else{
			return false;
		}
  }
  
  public function extraiEmail($istring){
    $string = strstr(addslashes($istring), " &lt;");
    $removethis = array("<", ">", " ", "&quot;", "&lt;", "&gt;");  
    $new_string = str_replace($removethis, "",  addslashes($string));
    return $new_string;
  }
  
  public function darEntrada($id, $cpf, $link, $flag, $lock = NULL, $lock_nome = NULL){
	echo '
	<input data-objeto="form_action" class="btn btn-success btn_driver rtrn-conteudo-listagem" value="Dar entrada" type="button" flag="'.$flag.'" caminho="'.$link.'" item="'.$cpf.'" idd="'.$id.'" data-lock="'.$lock.'" data-lock-nome="'.$lock_nome.'">';
  }
  public function darEntradaJSON($id, $cpf, $link, $flag, $lock = NULL, $lock_nome = NULL){
	return "<input data-objeto='form_action' class='btn btn-success btn_driver rtrn-conteudo-listagem' value='Dar entrada' type='button' flag='$flag' caminho='$link' item='$cpf' idd='$id' data-lock='$lock' data-lock-nome='$lock_nome'>";
  }
	
 public function selecionar($id, $uid, $link, $flag){
	echo "
	<input  class='btn btn-success btn_driver rtrn-conteudo-grade' value='Selecionar' type='button' data-item='$id'>
	<input type='hidden' name='flag' value='$flag' >
	<input type='hidden' name='caminho' value='$link' >
	<input type='hidden' name='item' value='$uid'>";
 }
 
  public function visualizar($id){
	echo '
	<input data-objeto="form_view" class="btn btn-success btn_driver rtrn-conteudo-listagem" value="Visualizar" type="button" idd="'.$id.'">	
	';
  }
  
  public function visualizarJSON($id){
	return "<input data-objeto='form_view' class='btn btn-success btn_driver rtrn-conteudo-listagem' value='Visualizar' type='button' idd='$id'>";
  }

  public function chbxModulo($modulos = NULL, $acessos = NULL){
	$retorno = NULL;
	if($modulos){
		if(isset($acessos)){
		  $i = 0;			 
		  while($row = $modulos->fetch_assoc()){
			$retorno .= ("
			  <div class='checkbox checkbox-primary'>
				<input id='checkbox".$row['id']."' type='checkbox' data-parsley-multiple='group1' name='acessos[]' value='".$row['id']."' ".($row['id'] == $acessos[$i] ? 'checked' : '').">
				<label for='checkbox".$row['id']."'>".$row['nome']."</label>
			  </div>
			");
			$i++;
		  }
		}else{ 		
		  while($row = $modulos->fetch_assoc()){
			$retorno .= ("
			  <div class='checkbox checkbox-primary'>
				<input id='checkbox".$row['id']."' type='checkbox' data-parsley-multiple='group1' name='acessos[]' value='".$row['id']."' >
				<label for='checkbox".$row['id']."'>".$row['nome']."</label>
			  </div>
			");
		  }
		}
	}else{
		$retorno .= "<p>Os Módulos não estão habilitados.</p>";
	}	
	return $retorno;	
  } 
  
  public function conteudoTabelaCGR($array, $link, $flag, $pave = NULL, $lock = NULL){
	$a = new Model;
	$query = ("SELECT * FROM statuses WHERE id = '".$array["status"]."'");
	$foo = $a->queryFree($query);
	$status = $foo->fetch_assoc();
	
	$query_assunto = ("SELECT * FROM assuntos WHERE id = '".$array["assunto"]."'");
	$woo = $a->queryFree($query_assunto);
	$assunto = $woo->fetch_assoc();
	
	$query_autor = ("SELECT nome FROM atendentes WHERE id_usuarios = '".$array["autor"]."'");
	$zoo = $a->queryFree($query_autor);
	$autor = $zoo->fetch_assoc();
	
	#$query_autor = ("SELECT user_auth FROM pav_inscritos WHERE id = '".$array["id"]."'");
	$query_autor = ("SELECT user_auth, atendentes.nome FROM pav_inscritos 
	INNER JOIN atendentes ON user_auth = atendentes.id_usuarios
	WHERE pav_inscritos.id = '".$array["id"]."'");
	$yoo = $a->queryFree($query_autor);
	$auth = $yoo->fetch_assoc();
	
	if(is_null($pave)){
		echo "
		<tr>
		<td>".(is_null($auth['user_auth']) ? '<i class="large material-icons">lock_open</i>' : '<i class="large material-icons">lock</i>')."</td>
		<td>".date('d/m/Y', strtotime($array['data_abertura']))."</td>
		<td data-search='$array[protocol]'>$array[protocol]</td>
		<td data-search='$array[nome_provedor]'>$array[nome_provedor]</td>
		<td data-search='$assunto[description]'>$assunto[description]</td>
		<td data-search='$status[name]'>$status[name]</td>
		<td data-search='$array[origem]'>$array[origem]</td>
		<td data-search='$autor[nome]'>$autor[nome]</td>
		<td>
		";  
		$this->darEntrada($array['id'], $array['cpf_cnpj_cliente'], $link, $flag, $auth['user_auth'], $auth['nome']);  
	}else{
		echo "
		<tr ".($array['finalizado'] == 0 ? 'class=' : '').">
		<td>".date('d/m/Y', strtotime($array['data_abertura']))."</td>
		<td>$array[protocol]</td>
		<td>$array[nome_provedor]</td>
		<td>$assunto[description]</td>
		<td>$status[name]</td>
		<td>$array[origem]</td>	
		<td>$autor[nome]</td>
		<td>
		";  
		$this->visualizar($array['id']); 
	}
	echo "</td></tr>";
	return true;
  } 
  
  public function atribuiGrupo($id_grupo, $array_data = NULL){
	$a = new Model;
	switch($id_grupo){
		case "1":
		  //comunicação interna
		  echo '
			<div class="row">						
				<div class="form-group col-md-12" >
					<label>Selecione os destinatários e tecle <span class="badge badge-default">enter</span> </label>
					<select class="form-control" data-live-search="true" id="buscaDestinatarioComunica">
						<option>...</option>';
						
						$query_provedor	= "SELECT nome, id FROM usuarios  WHERE lixo = 0 ORDER BY nome ASC";	
						$result = $a->queryFree($query_provedor);
						while($linhas = $result->fetch_assoc()){
							echo "<option value='".$linhas['id']."' data-nome='".$linhas['nome']."' data-tokens='".$linhas['nome']."'>".$linhas['nome']."</option>";				
						}
						
		  echo '	</select>
					<div class="list-container-comunica">
						<div class="list-group-comunica"></div>
						<div class="list-search-comunica"></div>
					</div>
				</div>
			</div>
			<script>$(function() {$("#buscaDestinatarioComunica").selectpicker();});</script>';
		break;
		case "2":
		  //auditoria
		  # testa se o cliente possui grupos cadastrados para auditoria
		  $query = "SELECT clientes.id, group_user.user_id FROM clientes INNER JOIN groups ON clientes.id = groups.id_cliente INNER JOIN group_user ON groups.id = group_user.group_id WHERE clientes.nome = '".$array_data['cliente']."' AND groups.lixo = 0 AND groups.tipo = 'Auditoria'";
		  $result = $a->queryFree($query);
		  if($result){
			if($result->num_rows > 0){				
				echo "
				<div class=''>
				<p>OK! Tudo certo.<br>
				Notificações serão enviadas para os grupos cadastrados pelo cliente.<br>Clique em enviar para continuar.</p>
				";
				while($foo = $result->fetch_assoc()){	
					echo "<input type='hidden' name='user_id[]' value='".$foo['user_id']."' />";
				}
				echo "<input type='hidden' value='1' name='auditoria' /></div>";				
			}else{
				$query_cli = "SELECT usuario FROM clientes WHERE nome = '".$array_data['cliente']."' AND lixo = 0";
				$result_cli = $a->queryFree($query_cli);
				if($result_cli->num_rows > 0){
					$woo = $result_cli->fetch_assoc();
					echo "<div class='alert alert-success'><p>Não há usuários cadastrados no grupo de auditoria.<br> Deseja enviar essa tratativa via e-mail para ".$woo['usuario']."?</p>
					<input type='hidden' value='".$woo['usuario']."' name='emails_user' /></div></div>";
				}else{
					echo "<div class='alert alert-danger'><p>Não é possível fazer auditoria sem um usuário válido.<br>O cadastro de cliente está incompleto.</p></div>";
				}
			}
		  }else{
			echo "<div class='alert alert-danger'><p>Este cliente não fez opção por auditoria ou não foi selecionado.</p></div>";
		  }
		break;
		case "3":
		  $array_despachar = $_POST;
		  //despachar a cliente
		  $query = "SELECT * FROM clientes 
		  INNER JOIN agenda_contatos ON clientes.id = id_cliente 
		  WHERE nome = '$array_data[cliente]' AND clientes.lixo = 0 
		  GROUP BY contatos";	
		  $woo = $a->queryFree($query);
		  while($resultado = $woo->fetch_assoc()){
			$contato[] = $resultado["contatos"];
		  }
		  # ------------- seleção de grupos existentes -------------- #
		  $query_grupos = "SELECT groups.* FROM clientes 
		  INNER JOIN groups ON groups.id_cliente = clientes.id 
		  INNER JOIN group_user ON group_user.group_id = groups.id 
		  WHERE nome = '$array_data[cliente]' GROUP BY groups.name";
		  $foo = $a->queryFree($query_grupos);		
			
		  if(isset($contato)){
			echo "<div class=''>";
        if(isset($array_despachar['cgr'])){	
          echo "<h3>Enviar e-mail?</h3><p>Uma notificação será entregue a <span class='badge badge-success'>".$array_data['cliente']."</span>.<br>
          <div class='alert alert-info'>Deseja despachar esse atendimento via e-mail para os e-mails abaixo?</div> <br>
          <div class='form-group'>";
          foreach($contato as $value){
            echo "
            <div class='radio radio-primary'>            
                <label class='form-radio-label'>
                <input type='radio' name='emails_user[]' value='$value' checked> $value
                <span class='form-radio-sign'>
                  <span class='radio'></span>
                </span>
                </label>
            </div>
            ";
          }
          echo "<p><strong>Caso não queira enviar e-mails basta limpar o campo acima.</strong></p>";
        }
        if($foo->num_rows > 0){
          echo "<h3>Grupos cadastrado pelo cliente</h3>
          <p>Selecione os setores para os quais deseja enviar esse atendimento.</p>
          ";
          while($result = $foo->fetch_assoc()){
            echo "
          <div class='radio radio-primary'>            
                <label class='form-radio-label'>
                <input type='radio' name='grupos[]' value='$result[id]' >$result[name]
                  <span class='form-radio-sign'>
                    <span class='radio'></span>
                  </span>
                </label>
          </div>		
            ";
          }
        }else{
          echo "<h3>Grupos não encontrados</h3>
          <p>Por favor, solicite o cadastro dos <strong>usuários</strong> nos grupos de contato ao cliente. Grupos vazios não recebem notificações!
          <br>Caso prossiga, apenas uma notificação padrão será enviada a área do cliente.</p>";
        }
        /* A partir de 18/10/2019, em ambos os IFs, o valor do input 'finalizado' passa a ser zero para possibilitar a simulação de atendimento em aberto
        * nos últimos atendimentos da abertura de chamada no Call Center. O cliente poderá modificar seu valor no ambiente de cliente.
        * by Adan */
        echo "</div>
          <input type='hidden' name='despachar-cliente' value='1' />
          <input type='hidden' name='finalizado' value='0' />
          <input type='hidden' name='finalizado_direto' value='1' />
          <input type='hidden' name='status' value='3' />
          <input type='hidden' name='solution'  value='2' />
          <input type='hidden' name='despachado' value='1' />
          </div>
        ";
		  }else{
        echo "
          <div class='form-group col-sm-12'>
            <p>Nenhum cliente foi encontrado para esse despacho.<br>
            Digite o endereço de e-mail para notificação do contato abaixo:</p> 
            <div class='form-group'>
              <input type='text' class='form-control' name='emails_user' title='Caso haja mais de um contato, separe os e-mails com uma vírgula'/>
            </div>
            
            <input type='hidden' name='despachar-cliente' value='1' />
            <input type='hidden' name='finalizado'  value='0' />
            <input type='hidden' name='finalizado_direto' value='1' />
            <input type='hidden' name='status' value='3' />
            <input type='hidden' name='solution'  value='2' />
            <input type='hidden' name='despachado' value='1' />
            <input type='hidden' name='destination' value='$resultado[usuario]' />
          </div>
        ";
		  }
		break;
	}
  }
  
  public function notifyComm($bind){	
	while($array = $bind->fetch_assoc()){
		echo "
		<form id='form_link_".$array['id']."'>
		<a class='list-group-item regular-link-msg' data-item=".$array['id']."  title='Ver mensagem' data-objeto='form_link_".$array['id']."'>	
			<div class='row'>				 
				<p class='sm-12'><small>$array[nome_user]</small></p>			  
			</div>
		</a>
		<input id='input_flag_".$array['id']."' type='hidden' name='retorno' value='.content-sized'>
		<input id='input_id_".$array['id']."' type='hidden' name='id' value='".$array['id']."'>
		<input id='input_link_".$array['id']."' type='hidden' name='caminho' value='views/atendimento-entrada-nivel-2.php' >
		<input type='hidden' name='var' value='pav_inscritos.id' />
		</form>
		";
	}
  }
  
  public function gradeEmail($array){
		
		#Montagem do layout			
		echo "
		<tr>			
			<td style='max-width:210px;'>
				<a class='regular-link-msg' data-objeto='action_email_$array[id]'  ".($array['unseen']==0 ? 'style=color:#009933;' : 'style=color:#999999;').">".iconv_mime_decode($array['fromaddress'])."</a>
			</td>
			<td style='max-width:400px;'>
				<a class='regular-link-msg' data-objeto='action_email_$array[id]' ".($array['unseen']==0 ? 'style=color:#009933;' : 'style=color:#999999;').">".iconv_mime_decode($array['subject'])."</a>
			</td>
			<td>
				<a class='regular-link-msg' data-objeto='action_email_$array[id]' ".($array['unseen']==0 ? 'style=color:#009933;' : 'style=color:#999999;').">".date('d/m/Y H:i:s', strtotime($array['date']))."</a>
				<form id='action_email_$array[id]'>
					<input type='hidden' name='caminho' value='controllers/sys/crud.sys.php' />
					<input type='hidden' name='flag' value='lerEmail' />
					<input type='hidden' name='id' value='$array[id]' />
					<input type='hidden' name='retorno' value='.content' />
				</form>
			</td>		
		</tr>
		";			
  }
  
  function detect_encoding($string){
	////w3.org/International/questions/qa-forms-utf-8.html
	if (preg_match('%^(?: [\x09\x0A\x0D\x20-\x7E] | [\xC2-\xDF][\x80-\xBF] | \xE0[\xA0-\xBF][\x80-\xBF] | [\xE1-\xEC\xEE\xEF][\x80-\xBF]{2} | \xED[\x80-\x9F][\x80-\xBF] | \xF0[\x90-\xBF][\x80-\xBF]{2} | [\xF1-\xF3][\x80-\xBF]{3} | \xF4[\x80-\x8F][\x80-\xBF]{2} )*$%xs', $string))
		return 'UTF-8';

	return mb_detect_encoding($string, array('UTF-8', 'ASCII', 'ISO-8859-1', 'JIS', 'EUC-JP', 'SJIS'));
  }

  function convert_encoding($string, $to_encoding, $from_encoding = '')	{
	if ($from_encoding == '')
		$from_encoding = $this->detect_encoding($string);

	if ($from_encoding == $to_encoding)
		return $string;

	return mb_convert_encoding($string, $to_encoding, $from_encoding);
  }

	public function grupo_responsavel($id, $flag = NULL){
		$a = new Model;
		$retorno = NULL;
		$query = "SELECT usuarios.nome AS nome_atend, usuarios.id AS id 
		FROM group_user
		INNER JOIN pav_inscritos ON pav_inscritos.grupo_responsavel = group_user.group_id
		INNER JOIN usuarios ON group_user.user_id = usuarios.id 
		WHERE pav_inscritos.id = $id";
		$foo = $a->queryFree($query);
		if($foo){
			$number = $foo->num_rows;			
			$i = 0;
			if(is_null($flag)){
				while($result = $foo->fetch_assoc()){
					if($i < $number){
						$retorno .= $result['nome_atend']."  ";
					}else{
						$retorno .= $result['nome_atend'];
					}
					$i++;
				}
				if($retorno != ""){
					return $retorno;
				}else{
					$retorno = "Nenhum grupo atribuído";
					return $retorno;
				}	  
			}else{
				while($result = $foo->fetch_assoc()){
					if($i < $number){
						$retorno .= "
						<span class='badge badge-success com-padding'>
							<input type='hidden' id='input_gruporesponsavel_".$result['id']."' name='grupo_responsavel[]'  value='".$result['id']."' />".$result['nome_atend']."
							
						</span>";
					}
					$i++;
				}
				if($retorno != ""){
					return $retorno;
				}else{
					#$retorno = "Nenhum grupo atribuído";
					return $retorno;
				}
			}
		}else{
			echo $this->retornaMsg(6, "<p>O registro todo ou alguma parte essencial da pesquisa parece estar faltando ou está inascessível ao sistema.
			Isso pode ser causado por um registro sem consistência e/ou que tenha sido modificado há algum tempo devida alguma manutenção do banco de dados.
			<br>Por favor, informe o alerta abaixo ao suporte.<p>
			<p>ERRO: Resultado da pesquisa retornou como <em>FALSE</em></p>
			<p><strong><a href='.'>Clique aqui para atualizar o status do sistema.</a></strong></p>");
			die();
		}
	}
	
	public function pagination($limite, $offset, $tabela){
		$a = new Model;
		$foo = $a->queryFree("SELECT COUNT('id') AS 'id' FROM $tabela WHERE lixo = 0");
		$total = $foo->fetch_assoc();
		return $total['id'];
	}
	
	public function setUrgency($atual, $media = NULL, $fim, $agenda, $id){
		$a = new Model;
		$tempo_agora = new DateTime($atual);
		$tempo_expec = new DateTime($fim);
		$agora 				= $tempo_agora->format('d/m/Y H:i:s');
		$expectativa 		= $tempo_expec->format('d/m/Y H:i:s');
		$tempo_metad = new DateTime($media);			
		$metade_expectativa = $tempo_metad->format('d/m/Y H:i:s');
							
		if($agenda == 5  and $tempo_agora <= $tempo_expec){
			return "<i class='large material-icons urgency-icon' style='color:green;' title='Dentro do prazo de resolução. Expectativa: $expectativa'>error_outline</i>";
		}else{					
			if($tempo_agora <= $tempo_metad){
				return "<i class='large material-icons urgency-icon' style='color:green;' title='Dentro do prazo de resolução. Expectativa: $expectativa'>error_outline</i>";
			}else if($tempo_agora > $tempo_metad and $tempo_agora <= $tempo_expec){
				return "<i class='large material-icons urgency-icon' style='color:orange;' title='O tempo de resolução execedeu 50% do limite para finalização. $agora > $metade_expectativa - Expect.: $expectativa '>error</i>";
			}else{
				return "<i class='large material-icons urgency-icon' style='color:red;' title='Tempo de resolução excedido. $agora > $expectativa'>error</i>";
			}
		}
		
	}
	
	public function conteudoTabelaJSON($array, $link, $flag, $pave = NULL, $lock = NULL, $atend_autor){
		$a = new Model;
		$query = "SELECT pav_inscritos.*, user_auth, statuses.name AS status_nome, assuntos.description AS descricao_assunto, atendentes.nome AS nome_user, MAX(PM.data) as ultimoMovimento, ADDTIME(pav_inscritos.data_abertura, SEC_TO_TIME(TIME_TO_SEC(assuntos.solution_time) / 2)) AS expected_time, (SELECT usuarios.nome FROM usuarios WHERE usuarios.id = pav_inscritos.autor) as autor_nome, (SELECT usuarios.nome FROM usuarios WHERE usuarios.id = pav_inscritos.atendente_responsavel) as responsavel     
		FROM pav_inscritos 
		INNER JOIN pav_movimentos as PM on pav_inscritos.id = PM.id_pav_inscritos
		INNER JOIN statuses ON pav_inscritos.status = statuses.id 
		INNER JOIN assuntos ON assuntos.id = pav_inscritos.assunto 
		INNER JOIN atendentes ON atendentes.id_usuarios = pav_inscritos.$atend_autor 
		WHERE pav_inscritos.id ='$array[id]' 
    ORDER BY pav_inscritos.data_abertura";		
		$yoo = $a->queryFree($query);
		if($yoo->num_rows > 0){
			$var_data = $yoo->fetch_assoc();
			if(is_null($var_data['id'])){
				$query = "SELECT pav_inscritos.*, user_auth, statuses.name AS status_nome, assuntos.description AS descricao_assunto, atendentes.nome AS nome_user, MAX(PM.data) as ultimoMovimento, ADDTIME(pav_inscritos.data_abertura, SEC_TO_TIME(TIME_TO_SEC(assuntos.solution_time) / 2)) AS expected_time, (SELECT usuarios.nome FROM usuarios WHERE usuarios.id = pav_inscritos.autor) as autor_nome, (SELECT usuarios.nome FROM usuarios WHERE usuarios.id = pav_inscritos.atendente_responsavel) as responsavel     
				FROM pav_inscritos 
				INNER JOIN pav_movimentos as PM on pav_inscritos.id = PM.id_pav_inscritos
				INNER JOIN statuses ON pav_inscritos.status = statuses.id 
				INNER JOIN assuntos ON assuntos.id = pav_inscritos.assunto 
				INNER JOIN atendentes ON atendentes.id = pav_inscritos.$atend_autor 
				WHERE pav_inscritos.id = '$array[id]'
        ORDER BY pav_inscritos.data_abertura DESC";
				$yoo = $a->queryFree($query);
				$var_data = $yoo->fetch_assoc();
			}
		}else{
			$query = "SELECT pav_inscritos.*, user_auth, statuses.name AS status_nome, assuntos.description AS descricao_assunto, atendentes.nome AS nome_user, MAX(PM.data) as ultimoMovimento, ADDTIME(pav_inscritos.data_abertura, SEC_TO_TIME(TIME_TO_SEC(assuntos.solution_time) / 2)) AS expected_time, (SELECT usuarios.nome FROM usuarios WHERE usuarios.id = pav_inscritos.autor) as autor_nome, (SELECT usuarios.nome FROM usuarios WHERE usuarios.id = pav_inscritos.atendente_responsavel) as responsavel     
		FROM pav_inscritos 
		INNER JOIN pav_movimentos as PM on pav_inscritos.id = PM.id_pav_inscritos
		INNER JOIN statuses ON pav_inscritos.status = statuses.id 
		INNER JOIN assuntos ON assuntos.id = pav_inscritos.assunto 
		INNER JOIN atendentes ON atendentes.id = pav_inscritos.$atend_autor 
		WHERE pav_inscritos.id = '$array[id]'
    	ORDER BY pav_inscritos.data_abertura DESC";
			$yoo = $a->queryFree($query);
			$var_data = $yoo->fetch_assoc();
		}		
		#print_r($query);die();
		if(is_null($pave)){	
			if(is_null($var_data['user_auth'])){
				$auth = NULL;
			}else{				
				$query_autor = ("SELECT user_auth, atendentes.nome FROM pav_inscritos 
				INNER JOIN atendentes ON user_auth = atendentes.id_usuarios
				WHERE pav_inscritos.id = '".$array["id"]."'");
				$foo = $a->queryFree($query_autor);
				$auth_data = $foo->fetch_assoc(); 
				$auth = $auth_data['nome'];
				$var_data['user_auth'] = $auth_data['user_auth'];
			}				
			$data = array(
				$this->setUrgency(date('y-m-d H:i:s'), $var_data['expected_time'], $var_data['data_expectativa_termino'], $var_data['status'], $var_data['id']),
				(is_null($var_data['user_auth']) ? "<i class='large material-icons urgency-icon'>lock_open</i>" : "<i class='large material-icons urgency-icon'>lock</i>"),
				date('d/m/Y H:i:s', strtotime($var_data['data_abertura'])),
				date('d/m/Y H:i:s', strtotime($var_data['ultimoMovimento'])),
				$var_data['protocol'],
				$var_data['nome_provedor'],
				'<abbr title=\''.strip_tags(addslashes($var_data['historico'])).'\' >'. $var_data['descricao_assunto'].' </abbr>',
				$var_data['status_nome'],
				$var_data['autor_nome'],
				$var_data['nome_user'],
				$this->darEntradaJSON($var_data['id'], $var_data['cpf_cnpj_cliente'], $link, $flag, $var_data['user_auth'], $auth),
			);				
			return $data;	
		}else{
			$data = array(
				date('d/m/Y H:i:s', strtotime($var_data['data_abertura'])),
				date('d/m/Y H:i:s', strtotime($var_data['ultimoMovimento'])),
				$var_data['protocol'],
				$var_data['nome_provedor'],
				'<abbr title=\''.strip_tags(addslashes($var_data['historico'])).'\' >'. $var_data['descricao_assunto'].' </abbr>',
				$var_data['status_nome'],
				$var_data['autor_nome'],
				$var_data['nome_user'],
				$this->visualizarJSON($array['id']),
			);
			return $data;
		}
    }

	# ----- Função para criação de listagens dinâmicas ------ #
	public function listagemCallCenter($array, $link, $flag, $pave = NULL, $lock = NULL){
		$a = new Model;
		$query = "SELECT pav_inscritos.*, user_auth, assuntos.description AS descricao_assunto, atendentes.nome AS nome_user, MAX(PM.data) as ultimoMovimento, ADDTIME(pav_inscritos.data_abertura, SEC_TO_TIME(TIME_TO_SEC(assuntos.solution_time) / 2)) AS expected_time    
		FROM pav_inscritos 
		INNER JOIN pav_movimentos as PM on pav_inscritos.id = PM.id_pav_inscritos 
		INNER JOIN assuntos ON assuntos.id = pav_inscritos.assunto 
		INNER JOIN atendentes ON atendentes.id_usuarios = pav_inscritos.autor 
		WHERE pav_inscritos.id ='$array[id]'
    ORDER BY pav_inscritos.data_abertura DESC";		
		$yoo = $a->queryFree($query);
		if($yoo->num_rows > 0){
			$var_data = $yoo->fetch_assoc();
		}else{
			$query = "SELECT pav_inscritos.*, user_auth, assuntos.description AS descricao_assunto, atendentes.nome AS nome_user, MAX(PM.data) as ultimoMovimento, ADDTIME(pav_inscritos.data_abertura, SEC_TO_TIME(TIME_TO_SEC(assuntos.solution_time) / 2)) AS expected_time    
			FROM pav_inscritos 
			INNER JOIN pav_movimentos as PM on pav_inscritos.id = PM.id_pav_inscritos
			INNER JOIN assuntos ON assuntos.id = pav_inscritos.assunto 
			INNER JOIN atendentes ON atendentes.id = pav_inscritos.autor 
      WHERE pav_inscritos.id = '$array[id]'
      ORDER BY pav_inscritos.data_abertura DESC";
			$yoo = $a->queryFree($query);
			$var_data = $yoo->fetch_assoc();
		}
		$nivelAtendimento = "";// Solicitação realizada pelo Bruno 25-09-19 para identificar se atendimento foi fechado em Nível 1 ou Nível 2 no Histórico
		if($var_data['finalizado_direto'] == '1'){
			$nivelAtendimento = "N1 - ";
		}else{
			$nivelAtendimento = "N2 - ";
		}		
		
		if(is_null($pave)){	
			if(is_null($var_data['user_auth'])){
				$auth = NULL;
			}else{				
				$query_autor = ("SELECT user_auth, atendentes.nome FROM pav_inscritos 
				INNER JOIN atendentes ON user_auth = atendentes.id_usuarios
				WHERE pav_inscritos.id = '".$array["id"]."'");
				$foo = $a->queryFree($query_autor);
				$auth_data = $foo->fetch_assoc(); 
				$auth = $auth_data['nome'];
				$var_data['user_auth'] = $auth_data['user_auth'];
			}				
			$data = array(
				$this->setUrgency(date('y-m-d H:i:s'), $var_data['expected_time'], $var_data['data_expectativa_termino'], $var_data['status'], $var_data['id']),
				(is_null($var_data['user_auth']) ? "<i class='large material-icons urgency-icon'>lock_open</i>" : "<i class='large material-icons urgency-icon'>lock</i>"),
				date('d/m/Y H:i:s', strtotime($var_data['data_abertura'])),
				date('d/m/Y H:i:s', strtotime($var_data['ultimoMovimento'])),
				$var_data['protocol'],
				$var_data['nome_provedor'],				
				'<abbr title=\''.strip_tags(addslashes($var_data['historico'])).'\' >'. $var_data['descricao_assunto'].' </abbr>',
				$var_data['nome_cliente'],				
				$var_data['nome_user'],
				$this->darEntradaJSON($var_data['id'], $var_data['cpf_cnpj_cliente'], $link, $flag, $var_data['user_auth'], $auth),
			);				
			return $data;	
		}else{
			$data = array(
				date('d/m/Y H:i:s', strtotime($var_data['data_abertura'])),
				date('d/m/Y H:i:s', strtotime($var_data['ultimoMovimento'])),
				$var_data['protocol'],
				$var_data['nome_provedor'],
				'<abbr title=\''.strip_tags(addslashes($var_data['historico'])).'\' >'. $nivelAtendimento. $var_data['descricao_assunto'].' </abbr>',
				$var_data['nome_cliente'],				
				$var_data['nome_user'],
				$this->visualizarJSON($array['id']),
			);
			return $data;
		}
    }

	public function tabMaker($id){	
		/* if (!include_once("model.inc.php")) {
			include_once("model.inc.php");
		} */
		$a = new Model();
		$query	= "SELECT * FROM categorias WHERE lixo = 0 AND id_clientes = '$id'";
		$result = $a->queryFree($query);
		global $linhas;
		if($result->num_rows >0){
			echo '<ul class="nav nav-pills nav-pills-warning nav-pills-icons justify-content-center" role="tablist">';			
			$i = 1;
			while ($linha = $result->fetch_assoc()) {
				echo "<li class='nav-item'>
					<a data-tipo='$linha[tipo_categoria]' class='nav-link".($i == 1 ? " active show'" : "'")." data-toggle='tab' href='#link$i' role='tablist'>
					  <i class='material-icons'>$linha[icone]</i> $linha[nome_categoria]
					</a>
				  </li>";	
				$i++;	
				$linhas[] = $linha['id'];
			}			
			echo "</ul><div class='tab-content tab-space tab-subcategories '>";
			$x = 1;
			foreach($linhas as $value){
				$query_script	= "SELECT * FROM scripts 
				INNER JOIN categorias ON categorias.id = scripts.id_categorias 
				WHERE scripts.lixo = 0 AND id_categorias = '$value'";
				$resultado = $a->queryFree($query_script);
				if($resultado->num_rows >0){
					
					while ($rows = $resultado->fetch_assoc()) {							
						echo "      
						  <!-- inicio Categoria Pane accordion -->
						  <div class='tab-pane".($x == 1 ? " active show'" : "'")." id='link$x'>
							<div class='card'>                 
							  <div class='card-body bg-light'> 
								<!-- inicio accordion -->
								<div id='accordion' role='tablist'>";
						if($value == $x){							
							echo "<div class='card-collapse'>
								  <div class='card-header bg-light' role='tab' id='heading$x'>
									<h5 class='mb-0'>
									  <a data-toggle='collapse' href='#sc1cat$x' aria-expanded='false' aria-controls='sc1cat$x' class='collapsed'>
										$rows[nome_categoria] - $rows[nome_scripts] 
										<i class='material-icons'>keyboard_arrow_down</i>
									  </a>
									</h5>
									  </div>
									  <!-- cada collapse deve ter um id diferente -->
									  <div id='sc1cat$x' class='collapse' role='tabpanel' aria-labelledby='heading$x' data-parent='#accordion' style=''>
										<div class='card-body'>";
							if($rows['tipo_categoria'] == 1){
								echo $rows['tutorial_scripts'];
							}else{
								echo "
								<object width='100%' height='600px' data='assetsb/media/docs/$rows[anexo_scripts]' type='application/pdf'>
									<p>Seu navegador não tem um plugin pra PDF</p>
								</object>";
							}
							echo "		</div>
									  </div>
									</div>									
								  </div>
								<!-- fim accordion -->
							";							
						}
						echo " </div>
							</div>
						  </div>";
						$x++;						
					}
				}
			}
			echo "</div>";
		}else{
			echo $this->retornaMsg(6);
		}
	}
	
	public function anexos($array){
		if($array['type']=="application/pdf" || $array['type']=="application/msword" || $array['type']=="application/vnd.openxmlformats-officedocument.wordprocessingml.document"){
			$limite_max = 5242880;
			if($array['size'] < $limite_max){
				$extfile  = strtolower(substr($array['name'], strripos($array['name'], '.', -1)));
				if($extfile == ".pdf" || $extfile == ".docx" || $extfile == ".doc"){
					$path = "../../assetsb/media/docs/";					
					$array['name'] = time().rand (5, 15).$extfile;
					move_uploaded_file($array['tmp_name'], $path.$array['name']);
					return $array;
				}
			}else{
				echo $this->retornaMsg(4, "Tamanho máximo: ".$limite_max."KB");
				return false;
			}
		}else{
			echo $this->retornaMsg(3);
			return false;
		}
	}
	
	public function buildTimeline($id, $tipo = NULL, $ultimo_atendimento = NULL, $func_client = NULL){
		
		$a		= new Model;
		$log 	= new Logs;
		if(is_null($tipo)){ # Tratativas para o cliente
			$query_movimentos = "SELECT pav.flag_cliente, pav.id, pav.files, pav.protocol, pav.data, pav.descricao, pav.solution, pav.origem, atendentes.nome AS nome, atendentes.id_usuarios AS user_id, usuarios.foto AS foto, pav.auditado 
			FROM pav_movimentos AS pav 
			INNER JOIN atendentes ON atendentes.id_usuarios = pav.id_autor
			INNER JOIN usuarios ON usuarios.id = pav.id_autor
			WHERE pav.id_pav_inscritos = $id AND pav.lixo = 0 
			GROUP BY pav.data ASC";
			
			$resultado = $a->queryFree($query_movimentos);
			if ($resultado->num_rows > 0) {
				if(is_null($ultimo_atendimento)){
					echo "<br>";
				}else{
					echo "<p>Protocolo: $ultimo_atendimento</p><hr>";
				}
				while($linhas = $resultado->fetch_assoc()){
					# Testa se existem arquivos anexados a timeline #
					if($linhas['files'] != ''){
						$file = $linhas['files'];
					}else{
						$file = NULL;
					}
					
					if($linhas['origem'] != 0){	# 0 = LV; Demais valores de acordo com o cliente
						$id_cliente = $linhas['origem'];
						if($linhas['flag_cliente'] == 0){
							$nome_cliente_query = "SELECT * FROM usuarios WHERE id = '$id_cliente'";
						}else{
							$nome_cliente_query = "SELECT * FROM clientes WHERE id = '$id_cliente'";
						}
						$mysqli=$a->queryFree($nome_cliente_query);
						if($mysqli->num_rows > 0){
							$dados_cliente = $mysqli->fetch_assoc();
							$nome_cliente = $dados_cliente['nome'];
							$foto_cliente = $dados_cliente['foto'];
							$log->timelineGenerico($nome_cliente, $linhas['protocol'],$linhas['descricao'],$linhas['data'], $linhas['solution'], 'chat_bubble_outline', $foto_cliente, $id_cliente, $file, $linhas['auditado']);
						}
					}else{
						$log->timelineGenerico($linhas['nome'], $linhas['protocol'],$linhas['descricao'],$linhas['data'], $linhas['solution'], 'chat_bubble_outline', $linhas['foto'], NULL, $file, $linhas['auditado']);
					}
				}
			} else {
				$query_movimentos = "SELECT pav.flag_cliente, pav.id, pav.files, pav.protocol, pav.data, pav.descricao, pav.solution, pav.origem, atendentes.nome AS nome, atendentes.id_usuarios AS user_id, usuarios.foto AS foto, pav.auditado  
				FROM pav_movimentos AS pav 
				INNER JOIN atendentes ON atendentes.id = pav.id_autor 
				INNER JOIN usuarios ON usuarios.id = pav.id_autor
				WHERE pav.id_pav_inscritos = $id AND pav.lixo = 0 
				GROUP BY pav.data ASC";
				$resultado = $a->queryFree($query_movimentos);
				if(is_null($ultimo_atendimento)){
					echo "<br>";
				}else{
					echo "<p>Protocolo: $ultimo_atendimento</p><hr>";
				}
				while($linhas = $resultado->fetch_assoc()){
					# Testa se existem arquivos anexados a timeline #
					if($linhas['files'] != ''){
						$file = $linhas['files'];
					}else{
						$file = NULL;
					}
					
					if($linhas['origem'] != 0){	# 0 = LV; Demais valores de acordo com o cliente
						$id_cliente = $linhas['origem'];
						if($linhas['flag_cliente'] == 0){
							$nome_cliente_query = "SELECT * FROM usuarios WHERE id = '$id_cliente'";
						}else{
							$nome_cliente_query = "SELECT * FROM clientes WHERE id = '$id_cliente'";
						}
						$mysqli=$a->queryFree($nome_cliente_query);
						if($mysqli->num_rows > 0){
							$dados_cliente = $mysqli->fetch_assoc();
							$nome_cliente = $dados_cliente['nome'];
							$foto_cliente = $dados_cliente['foto'];
							$log->timelineGenerico($nome_cliente,$linhas['protocol'],$linhas['descricao'],$linhas['data'], $linhas['solution'], 'chat_bubble_outline', $foto_cliente, $id_cliente, $file, $linhas['auditado']);
						}
					}else{
						$log->timelineGenerico($linhas['nome'],$linhas['protocol'],$linhas['descricao'],$linhas['data'], $linhas['solution'], 'chat_bubble_outline', $linhas['foto'], NULL, $file, $linhas['auditado']);
					}
				}
			}
		}else{ # Timeline descriptions
			$query_movimentos = "SELECT pav.id, pav.protocol, pav.data, pav.descricao_interna, pav.solution, atendentes.nome AS nome, atendentes.id_usuarios AS user_id, usuarios.foto AS foto  
			FROM pav_descriptions AS pav 
			INNER JOIN pav_inscritos ON pav_inscritos.id = pav.id_pav_inscritos 
			INNER JOIN atendentes ON atendentes.id_usuarios = pav.id_autor 
			INNER JOIN usuarios ON usuarios.id = pav.id_autor
			WHERE pav.id_pav_inscritos = $id AND pav.lixo = 0 
			GROUP BY pav.id 
			ORDER BY pav.data ASC";
			#echo $query_movimentos;die();
			$resultado = $a->queryFree($query_movimentos);
			if ($resultado->num_rows > 0) {
				while ($linhas = $resultado->fetch_assoc()) {
					$log->timelineGenerico($linhas['nome'],$linhas['protocol'],$linhas['descricao_interna'],$linhas['data'], $linhas['solution'], 'chat_bubble_outline', $linhas['foto']);
				}
			} else {
				echo "<div class='cd-timeline-content'><p>Não existe nenhuma mensagem ainda.</p></div>";
			}							
		}
	}
  
  public function formatCnpjCpf($value){
    $cnpj_cpf = preg_replace("/\D/", '', $value);
    
    if (strlen($cnpj_cpf) === 11) {
      return preg_replace("/(\d{3})(\d{3})(\d{3})(\d{2})/", "\$1.\$2.\$3-\$4", $cnpj_cpf);
    } 
    
    return preg_replace("/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/", "\$1.\$2.\$3/\$4-\$5", $cnpj_cpf);
  }

  public function conteudoTabelaJSON_Auditoria($array, $link, $flag){
		
		$data = array(
			$this->setUrgency(date('y-m-d H:i:s'), $array['expected_time'], $array['data_expectativa_termino'], $array['status'], $array['id']),
			(is_null($array['user_auth']) ? "<i class='large material-icons urgency-icon'>lock_open</i>" : "<i class='large material-icons urgency-icon'>lock</i>"),
			date('d/m/Y H:i:s', strtotime($array['data_abertura'])),
			date('d/m/Y H:i:s', strtotime($array['ultimoMovimento'])),
			$array['protocol'],
			$array['nome_provedor'],
			$array['nome_cliente'],
			'<abbr title=\''.strip_tags(addslashes($array['historico'])).'\' >'. $array['descricao_assunto'].' </abbr>',
			$array['status_nome'],
			$array['autor_nome'],			
			$this->darEntradaJSON($array['id'], $array['cpf_cnpj_cliente'], $link, $flag, $array['user_auth'], null),
		);				
		return $data;		
	
	}
}


class UploadException extends Exception
{
    public function __construct($code) {
        $message = $this->codeToMessage($code);
        parent::__construct($message, $code);
    }

    private function codeToMessage($code)
    {
        switch ($code) {
            case UPLOAD_ERR_INI_SIZE:
                $message = "O arquivo enviado excedeu o tamanho máximo da diretirva upload_max_filesize em php.ini";
                break;
            case UPLOAD_ERR_FORM_SIZE:
                $message = "O arquivo enviado excedeu o tamanho máximo da diretirva MAX_FILE_SIZE que foi especificado no form HTML";
                break;
            case UPLOAD_ERR_PARTIAL:
                $message = "O arquivo foi parcialmente enviado";
                break;
            case UPLOAD_ERR_NO_FILE:
                $message = "Nenhum arquivo foi enviado";
                break;
            case UPLOAD_ERR_NO_TMP_DIR:
                $message = "Pasta temporária não encontrada";
                break;
            case UPLOAD_ERR_CANT_WRITE:
                $message = "Falha ao gravar o arquivo no disco";
                break;
            case UPLOAD_ERR_EXTENSION:
                $message = "Extensão não permitida";
                break;

            default:
                $message = "Erro de envio desconhecido";
                break;
        }
        return $message;
    }
}
?>