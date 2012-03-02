<?php
namespace Com\Aleafs;
class AutoLoadTestClass
{
	public static $requireTime = 0;

	public function path()
	{
		return __FILE__;
	}
}

AutoLoadTestClass::$requireTime++;

