<?php

// Connecting to the database...
$pdo = new PDO('mysql:dbname=dndgen;host=localhost;charset=utf8', 'root', 'dermdermderm99');

$pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		
// Converting the data into JSON data
$data = $_POST['data'];
$data = json_decode($data,true);

// Loading type variable
$type = $_POST['type'];

// Taking all of the data we've imported and grabbing its helpers

// Pulling data regarding wealth from the database
if (array_key_exists('wealth_base', $data)) {
	$stmt = $pdo->prepare('SELECT * FROM info_wealth WHERE info_wealth_value = :wealth_base');
	$stmt->bindParam(':wealth_base',$data['wealth_base'],PDO::PARAM_INT);
	
	$stmt->execute();
	$results = $stmt->fetchAll();
	$data['wealth_data'] = $results[0];
}



//////////////////////
// Helper Functions //
//////////////////////

function get_random($stmt,$value) {

	$stmt->execute();
	$results = $stmt->fetchAll();
	$size = sizeof($results);
	
	if ($value == -1) {
		return $results[rand(0,$size-1)];
	}
	
	return $results[rand(0,$size-1)][$value];
}











//////////////////////////
// Generation functions //
//////////////////////////

// Generation for wealth
function gen_wealth($useWealthBase, $genWealthBase) {
	// Checking to see if we are allowed to generate the base wealth and if it is not already generated
	// If we are not allowed to generate the base wealth AND its already generated, return the current
	if ($useWealthBase && array_key_exists('wealth_base', $GLOBALS['data'])) {
		return $GLOBALS['data']['wealth_base'];
	}
	
	else {
		$stmt = $GLOBALS['pdo']->prepare('SELECT * FROM info_wealth');
		
		$wealth_data = get_random($stmt,-1);
		$wealth_val = $wealth_data['info_wealth_value'];
		
		// If we are allowed to generate the wealth base, we assign it here. Otherwise we do not bother
		if ($genWealthBase) {
			$GLOBALS['data']['wealth_base'] = $wealth_val;
			$GLOBALS['data']['wealth_data'] = $wealth_data;
		}

		return $wealth_val;
	}
}

// Generating for base materials
function gen_material_base($useBaseMaterial, $useBaseWealth, $genMaterialBase, $genBaseWealth) {
	
	// If our material base already exists then we will return it
	if ($useBaseMaterial && array_key_exists('material_base', $GLOBALS['data'])) {
		return $GLOBALS['data']['material_base'];
	}
	
	// If our wealth does not currently exist and we are allowed to generate it, we generate it
	if ($useBaseWealth && !array_key_exists('wealth_base', $GLOBALS['data'])) {
		$wealth = gen_wealth($useBaseWealth, $genBaseWealth);
	}
	else {
		$wealth = gen_wealth($useBaseWealth, $genBaseWealth);
	}
	
	$stmt = $GLOBALS['pdo']->prepare('SELECT * FROM mat_base WHERE mat_base_wealth <= :wealth');
	$stmt->bindParam(':wealth',$wealth,PDO::PARAM_INT);
	$material_base = get_random($stmt,'mat_base_value');
	
	if ($genMaterialBase) {
		$GLOBALS['data']['material_base'] = $material_base;
	}

	return $material_base;
	
}

// Generating for single material
function gen_material($useBaseWealth,$useBaseMaterial) {
	$mat_base = "";
	$wealth = "";
	
	if ($useBaseWealth) {
		$wealth = gen_wealth(true,true);
	}
	else {
		$wealth = gen_wealth(false,false);
	}
	
	if ($useBaseMaterial) {
		$mat_base = gen_material_base($useBaseMaterial,$useBaseWealth,true,false);
	}
	else {
		$mat_base = gen_material_base($useBaseMaterial,$useBaseWealth,false,false);
	}
	
	

	if ($mat_base == 'stone' || $mat_base == 'brick') {

		$stmt = $GLOBALS['pdo']->prepare('SELECT * FROM mat_stone WHERE wealth = :wealth');
		$stmt->bindParam(':wealth',$wealth,PDO::PARAM_INT);
	}
	else if ($mat_base == 'wood') {

		$stmt = $GLOBALS['pdo']->prepare('SELECT * FROM mat_wood WHERE wealth = :wealth');
		$stmt->bindParam(':wealth',$wealth,PDO::PARAM_INT);		
	}
	else {
		return "INVALID";
	}
	
	return get_random($stmt,'value');
}

// Generating for furniture
function gen_furniture($type) {
	$wealth = gen_wealth(true,true);
	$material = gen_material(true,true);
	
	if ($type != -1) {
	
		$stmt = $GLOBALS['pdo']->prepare('SELECT * FROM module_furniture WHERE value = :type');
		$stmt->bindParam(':type',$type,PDO::PARAM_STR);
	
	}
	
	else {
		$stmt = $GLOBALS['pdo']->prepare('SELECT * FROM module_furniture');
	}
	
	$furniture_data = get_random($stmt,-1);
	$furniture = $furniture_data['value'];
	
	$slotdata = [];
	
	// Determining how many slots to generate...
	$slotmin = $furniture_data['minslot'];
	$slotmax = $furniture_data['maxslot'];
	
	$slots = rand($slotmin,$slotmax);
	$slotdata = explode(",",$furniture_data['slotlist']);
	
	$slotjson = [];
	
	$index = 0;
	while ($index < $slots) {
		$newslot = gen_slot($slotdata[$index]);
		$slotjson[$index] = json_decode($newslot,true);
		
		$index++;
	}
	
	$json_furniture = ('{"furniture": "'.$furniture.'", "wealth": "'.$wealth.'", "wealth_n": "'.$GLOBALS["data"]["wealth_data"]["info_wealth_noun"].'", "material": "'.$material.'", "material_base": "'.$GLOBALS["data"]["material_base"].'"}');
	$json_furniture = json_decode($json_furniture,true);
	$json_furniture['slots'] = $slotjson;
	
	return $json_furniture;
}

function gen_slot($type) {
	
	// MySQL search for all possible types, if it doesn't exist we return as empty
	
	$stmt = $GLOBALS['pdo']->prepare('SELECT value FROM slots WHERE id = :type');
	$stmt->bindParam(':type',$type,PDO::PARAM_INT);
	$stmt->execute();
	
	$results = $stmt->fetchAll()[0];
	
	if (sizeof($results) > 0) {
		// Valid search
		$value = $results['value'];
		
		return '{"slot": "'.$value.'"}';
	}
	else {
		return '{"slot": "empty"}';
	}
}




/////////////////////////
// Printing Functions //
////////////////////////

// Printing furniture...
function print_furniture($furniture) {
	
	$type = $furniture['furniture'];
	$wealth = $furniture['wealth_n'];
	$material = $furniture['material'];
	$material_base = $furniture['material_base'];
	$slots = $furniture['slots'];
	
	$return_string = "You see a ".$type." (".$wealth."), made out of ".$material." ".$material_base;
	$return_string = $return_string . "\nSlots:\n";
	
	$index = 0;
	while ($index < sizeof($slots)) {
		$slot_string = print_slot($slots[$index],$furniture);
		$return_string = $return_string . $slot_string . "\n";
		$index = $index + 1;
	}
	
	return $return_string;
}

// Printing function for slots
function print_slot($slot,$furniture) {
	return "This slot contains " . $slot['slot'];
}







if ($type == 'furniture') {
	$furniture = gen_furniture($data['furniture']);
	echo print_furniture($furniture) . "\n\n";	
	die();
}
else if ($type == 'room') {
	echo "ROOM OUTPUT";
	die();
}


?>