<html>
<head>
	<meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css" integrity="sha384-MCw98/SFnGE8fJT3GXwEOngsV7Zt27NXFoaoApmYm81iuXoPkFOJwJ8ERdknLPMO" crossorigin="anonymous">
	<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
	<meta content="Mapeador Dashboard" name="description" />
	<meta content="Adan Ribeiro" name="author" />	
	<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css" integrity="sha384-MCw98/SFnGE8fJT3GXwEOngsV7Zt27NXFoaoApmYm81iuXoPkFOJwJ8ERdknLPMO" crossorigin="anonymous">
	<!-- Folhas de estilo para o Datatables usando BS4 'geral e botões respectivamente' -->
	<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.21/css/jquery.dataTables.css" />
	<link rel="stylesheet" type="text/css" href="assets/css/personal.css" />
	<title>Mapeador</title>
</head>
<body>
<section class="section-responsive">
	<div class="logo">&nbsp;</div>
	<h1>HSM - Mapeamento das ligações de cabos (Dados e Telefonia)</h1>
	<div class="table-responsive">
		<table id="tabela" class="display" style="width:100%">		
			<thead>
				<tr>
					<th>Pavimento </th>
					<th>Setor </th>
					<th>Tomada </th>
					<th>Porta do PP </th>
					<th>Status PP </th>
					<th>Porta do SW </th>
					<th>Status SW </th>
					<th>Tipo </th>
				</tr>
			</thead>
			<tbody>
			</tbody>
			<tfoot>
				<tr>
					<th>Pavimento </th>
					<th>Setor </th>
					<th>Tomada </th>
					<th>Porta do PP </th>
					<th>Status PP </th>
					<th>Porta do SW </th>
					<th>Status SW </th>
					<th>Tipo </th>
				</tr>
			</tfoot>
		</table>
	</div>
</section>
<script src="https://code.jquery.com/jquery-3.5.1.min.js" integrity="sha256-9/aliU8dGd2tb6OSsuzixeV4y/faTqgFtohetphbbj0=" crossorigin="anonymous"></script><script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.3/umd/popper.min.js" integrity="sha384-ZMP7rVo3mIykV+2+9J3UJ46jBk0WLaUAdn689aCwoqbBJiSnjAK/l8WvCWPIPm49" crossorigin="anonymous"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/js/bootstrap.min.js" integrity="sha384-ChfqqxuZUCnJSK3+MXmPNIyE6ZbWh2IMqE241rYiqJxyMiZ6OW/JmZQ5stwEULTy" crossorigin="anonymous"></script>
<!-- Arquivos de configuração do Datatables -->
<script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.10.21/js/jquery.dataTables.js"></script>
<script src="https://cdn.datatables.net/plug-ins/1.10.21/i18n/Portuguese-Brasil.json"></script>
<script>
$(document).ready(function() {
	$('#tabela').DataTable( {
		ajax: "set.php",		
		language: {
        url: "//cdn.datatables.net/plug-ins/1.10.21/i18n/Portuguese-Brasil.json"
		}
	} );
} );
</script>
</body>