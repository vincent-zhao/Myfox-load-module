<?php
$arr	= explode(',', $argv[1]);
$prefix	= empty($argv[2]) ? 'server' : trim($argv[2]);
$i = 1;
foreach($arr as $server) {
	$server = trim($server);
	if(empty($server)) {
		continue;
	}
	echo "$prefix$i = \"$server\"\n";
	$i++;
}
