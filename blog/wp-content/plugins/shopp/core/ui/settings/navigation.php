<ul class="subsubsub">
	<?php $i = 0; foreach ($this->Admin->settings as $key => $screen): ?>
		<li><a href="?page=<?php echo $screen[0] ?>"<?php if ($_GET['page'] == $screen[0]) echo ' class="current"'; ?>><?php echo $screen[1]; ?></a><?php if (count($this->Admin->settings)-1!=$i++): ?> | <?php endif; ?></li>
	<?php endforeach; ?>
</ul>
<br class="clear" />