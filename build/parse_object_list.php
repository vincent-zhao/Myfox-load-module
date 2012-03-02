<?php
foreach (explode(',', $argv[1]) AS $item) {
	list($key, $val) = explode(':', $item, 2);
	if (is_numeric($val)) {
		echo "$key	= $val\n";
	} else {
		echo "$key	= \"$val\"\n";
	}
}
