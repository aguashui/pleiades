<?php
echo "<h2> " . h($data['User']['username']) . "'s Levels</h2>";
?>
<table>

<?php
echo $this->Html->tableHeaders(array('Name', 'Game Type', 'Rating', 'Downloads'));
foreach ($data['Level'] as $level) {
	echo $this->Html->tableCells(array(
		$this->Html->link($level['name'], "/levels/view/{$level['id']}"),
		h($level['game_type']),
		intval($level['rating']),
		intval($level['downloads'])
		));
}
?>

</table>
