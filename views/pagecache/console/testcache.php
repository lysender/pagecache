<h2>Test result</h2>
<hr />

<?php if ($created): ?>
<h3 class="positive">SUCCESS</h3>
<p>Pagecache is working in your current setup. </p>
<p>The test page was created on <code><?php echo $created ?></code> during the first
	request and two sub-sequent requests return the same content.</p>
<?php else: ?>
<h3 class="negative">FAILED</h3>
<p>Pagecache is not working correctly in your setup. Two sub-sequent requests
	return different contents.</p>
<?php endif ?>

<hr />

<p><a href="<?php echo URL::site('/pagecache/console') ?>">Go back to main</a></p>