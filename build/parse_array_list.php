<?php
foreach (explode(',', $argv[1]) AS $item) {
	echo "\"$item\" \\\n";
}
