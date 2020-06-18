<?php
include("model.inc.php");
$a 		 = new Model;
$query = "SELECT pavimento.nome AS 'Pavimento', setor.nome AS 'Setor', tomadas.numero AS 'Tomada', 
CONCAT(patchpanel.numero, '-', p1.numero) AS 'Porta do PP', s1.status AS 'Status PP', 
CONCAT(switches.numero, '-', p2.numero) AS 'Porta do SW', s2.status AS 'Status SW', tipo_tomadas.tipo AS 'Tipo' 
FROM bindings 
INNER JOIN tomadas ON bindings.id_tomada = tomadas.id 
INNER JOIN tipo_tomadas ON tomadas.id_tipo_tomadas = tipo_tomadas.id 
INNER JOIN setor ON bindings.id_setor = setor.id 
INNER JOIN pavimento ON setor.id_pavimento = pavimento.id 
INNER JOIN patchpanel ON patchpanel.id = bindings.id_patchpanel 
INNER JOIN switches ON switches.id = bindings.id_switch 
JOIN portas p1 ON p1.id = bindings.pp_porta JOIN portas p2 ON p2.id = bindings.sw_porta 
JOIN status s1 ON s1.id = p1.id_status 
JOIN status s2 ON s2.id = p2.id_status 
ORDER BY Setor ASC";

echo '
<div class="table-responsive">
<table id="tabela" style="width:100%">
	<thead class="thead-light">
		<tr>
			<th><strong>Pavimento</strong> </th>
			<th><strong>Setor</strong> </th>
			<th><strong>Tomada</strong> </th>
			<th><strong>Porta do PP</strong> </th>
			<th><strong>Status PP</strong> </th>
			<th><strong>Porta do SW</strong> </th>
			<th><strong>Status SW</strong> </th>
			<th><strong>Tipo</strong> </th>
		</tr>

	</thead>
	<tbody>';
$a->queryFree($query);
if ($result) {
	while ($linhas = $result->fetch_assoc()) {
		echo ("
			<tr>
			<td>" . $linhas['Pavimento'] . "</td>
			<td>" . $linhas['Setor'] . "</td>
			<td>" . $linhas['Tomada'] . "</td>
			<td>" . $linhas['Porta do PP'] . "</td>
			<td>" . $linhas['Status PP'] . "</td>
			<td>" . $linhas['Porta do SW'] . "</td>
			<td>" . $linhas['Status SW'] . "</td>
			<td>" . $linhas['Tipo'] . "</td>
			</tr>
			");
	}
} else {
	echo "<tr><td>Nenhum registro foi encontrado.</td></tr>";
}
echo "</tbody>
</table>
</div>";					