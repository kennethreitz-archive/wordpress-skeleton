<?php
function show_constant($constant) {
	if (!defined($constant)) {
?>			<tr>
				<th><?php echo $constant; ?></th>
				<td>Not defined</td>
			</tr>
<?php	
	} else {
?>			<tr>
				<th><?php echo $constant; ?></th>
				<td><?php echo constant($constant); ?></td>
			</tr>
<?php	
	}
}

function show_var($desc, $var, $onlytrue = false) {
	if ($onlytrue && !$var)
		return;
?>			<tr>
				<th><?php echo $desc; ?></th>
				<td><?php var_dump($var); ?></td>
			</tr>
<?php	
}

?>