<?php

namespace Myfox\Test\Factory;

class Test
{

	public $a;

	public $b;

	public function __construct($a, $b = 'default')
	{
		$this->a	= $a;
		$this->b	= $b;
	}

}
