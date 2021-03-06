<?php
include_once("model.inc.php");
$a 		 = new Model;
$query = "SELECT pavimento.nome AS 'Pavimento', setor.nome AS 'Setor', id_tomada AS 'Tomada', CONCAT(patchpanel.numero, '-', p1.numero) AS 'Porta do PP', s1.id AS 'Status PP', CONCAT(switches.numero, '-', p2.numero) AS 'Porta do SW', s2.id AS 'Status SW', tipo_tomadas.tipo AS 'Tipo' 
FROM bindings 
INNER JOIN tipo_tomadas ON bindings.id_tipo_tomada = tipo_tomadas.id 
INNER JOIN setor ON bindings.id_setor = setor.id 
INNER JOIN pavimento ON setor.id_pavimento = pavimento.id 
INNER JOIN patchpanel ON patchpanel.id = bindings.id_patchpanel 
INNER JOIN switches ON switches.id = bindings.id_switch 
JOIN portas p1 ON p1.id = bindings.pp_porta 
JOIN portas p2 ON p2.id = bindings.sw_porta 
JOIN status s1 ON s1.id = p1.id_status 
JOIN status s2 ON s2.id = p2.id_status  
ORDER BY Setor ASC";
$data_json = array();
$i = 0;
$a->queryFree($query);
if ($result) {
	while ($linhas = $result->fetch_assoc()) {
		$data = array( 
			$linhas['Pavimento'],
			$linhas['Setor'],
			$linhas['Tomada'],
			$linhas['Porta do PP'],
			$a->colorStatus($linhas['Status PP']),
			$linhas['Porta do SW'],
			$a->colorStatus($linhas['Status SW']),
			$linhas['Tipo']
		);
		$data_json["data"][$i] = $data;
		$i++;
	}	
	echo json_encode($data_json);	
} else {
	echo "<tr><td colspan='8'>Nenhum registro foi encontrado.</td></tr>";
}
?>