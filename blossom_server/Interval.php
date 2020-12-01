<?php

/*

Blossom Server 1.3
Copyright (C) 2011 Jacob Grahn <contact@jiggmin.com>
See http://blossom-server.com/ for more information

Blossom Server is free software: you can redistribute it and/or modify
it under the terms of the GNU Lesser General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

Blossom Server is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU Lesser General Public License for more details.

You should have received a copy of the GNU Lesser General Public License
along with Blossom Server.  If not, see <http://www.gnu.org/licenses/>.

*/

class Interval {

	private $target;
	private $func;
	private $freq = 1;
	private $last_run;


	//--- init some variables ---
	public function __construct($target, $func, $freq) {
		$this->target = $target;
		$this->func = $func;
		$this->freq = $freq;
		$this->last_run = time();
	}
	
	
	//--- runs the interval ---
	public function run() {
		$time = time();
		$time_elapsed = $time - $this->last_run;
		if($time_elapsed >= $this->freq) {
			$this->last_run = $time;
			$this->target->{$this->func}();
		}
	}
}

?>