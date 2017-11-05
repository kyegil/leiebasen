<!DOCTYPE html>
<html lang="no">
<head>
	<?php $this->skrivHeader();?>
</head>
<body>
	<div id="vidujo">
		<div id="kaplinio">
			<span id="område">
				<a href="index.php">
					<img src="../bilder/drift.png" alt="DRIFT" height="25" />
				</a>
			</span>
			<span id="bruker">Innlogget som <?php echo $this->navn($this->bruker['id']);?>
				<a href="../index.php?oppdrag=avslutt">
					<img alt="Logg ut" title="Logg ut" src="../bilder/avslutt.png" width="40" />
				</a>
			</span>
		</div>
		<div id="navigado">
			<div id="menylinje">
			</div>
		</div>
		<div id="enhavo">
			<noscript>Siden krever at du slår på JavaScript i nettleseren din!</noscript>
			<?php $this->design();?>
		</div>
		<div id="piedlinio">
		</div>
	</div>
</body>
</html>
