<head>

	<!--- Loading scripts --->
	<script src="js/jquery.min.js"></script>

</head>

<h1>D&D Gen</h1>
<p>Procedural generator for DND</p>

<br><br>

<?php

// Connecting to the database...
$pdo = new PDO('mysql:dbname=dndgen;host=localhost;charset=utf8', 'root', 'dermdermderm99');

$pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

?>

<h4> Furniture Generator </h4>

<label>Wealth: </label>
<select id = "wealth_select_furniture">
	<option value = -1>Random</option>
	
	<?php
		// Selecting all our variables to display...
		$stmt = $pdo->prepare('SELECT * FROM info_wealth');
		$stmt->execute();
		$results = $stmt->fetchAll();
		
		$index = 0;
		while ($index < sizeof($results)) {
			echo "<option value = ".$results[$index]['info_wealth_value'].">".$results[$index]['info_wealth_noun']."</option>";
			$index = $index + 1;
		}
		
	?>
</select>

<label>&nbsp;&nbsp;Material:</label>
<select id = "material_base_select_furniture">
	<option value = -1>Random</option>
	
	<?php
		// Selecting all our variables to display...
		$stmt = $pdo->prepare('SELECT * FROM mat_base');
		$stmt->execute();
		$results = $stmt->fetchAll();
		
		$index = 0;
		while ($index < sizeof($results)) {
			echo "<option value = '".$results[$index]['mat_base_value']."'>".$results[$index]['mat_base_value']."</option>";
			$index = $index + 1;
		}
		
	?>
</select>

<label>&nbsp;&nbsp;Furniture:</label>
<select id = "furniture_select_furniture">
	<option value = -1>Random</option>
	
	<?php
		// Selecting all our variables to display...
		$stmt = $pdo->prepare('SELECT * FROM module_furniture');
		$stmt->execute();
		$results = $stmt->fetchAll();
		
		$index = 0;
		while ($index < sizeof($results)) {
			echo "<option value = '".$results[$index]['value']."'>".$results[$index]['value']."</option>";
			$index = $index + 1;
		}
		
	?>
</select>

<label>&nbsp;&nbsp;Age:</label>
<select id = "age_select_furniture">
	<option value = -1>Random</option>
	
	<?php
		// Selecting all our variables to display...
		$stmt = $pdo->prepare('SELECT * FROM info_age');
		$stmt->execute();
		$results = $stmt->fetchAll();
		
		$index = 0;
		while ($index < sizeof($results)) {
			echo "<option value = '".$results[$index]['info_age_value']."'>".$results[$index]['info_age_descriptor']."</option>";
			$index = $index + 1;
		}
		
	?>
</select>

<br><br>

<button onclick = "generate('furniture')">Generate</button>

<br><br><br>

<h4>Room Generator</h4>

<label>Wealth: </label>
<select id = "wealth_select_room">
	<option value = -1>Random</option>
	
	<?php
		// Selecting all our variables to display...
		$stmt = $pdo->prepare('SELECT * FROM info_wealth');
		$stmt->execute();
		$results = $stmt->fetchAll();
		
		$index = 0;
		while ($index < sizeof($results)) {
			echo "<option value = ".$results[$index]['info_wealth_value'].">".$results[$index]['info_wealth_noun']."</option>";
			$index = $index + 1;
		}
		
	?>
</select>

<br><br>


<button onclick = "generate('room')">Generate</button>

<br><br><br>


<script>



function generate(type) {
	
	var wealth_select = document.getElementById("wealth_select_" + type);
	var material_base_select = document.getElementById("material_base_select_" + type);
	var furniture_select = document.getElementById("furniture_select_" + type);
	
	var data = {}
	
	if (wealth_select.value != -1) {
		data.wealth_base = wealth_select.value;
	}
	
	if (material_base_select.value != -1) {
		data.material_base = material_base_select.value;
	}
	
	data.furniture = furniture_select.value;
	
	// First we convert our data into string
	var data_PHP = JSON.stringify(data);
	
	$.ajax({

	url:"php/generate.php", //the page containing php script
	type: "POST", //request type
	data: {type: type, data: data_PHP},
	success:function(result){
			alert(result);
		}
	});
}

</script>