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

class Query {
	
	private static $next_query_id = 1;
	
	public $query_id;
	public $query_str;
	public $result;
	public $creation_time;
	public $finish_time;
	public $vars;
	
	public $target;
	public $func;
	
	public $rows;
	public $error;
	public $insert_id;
	public $query_duration;
	
	public function __construct($query_str, $target=NULL, $func=NULL) {
		$this->query_str = $query_str;
		$this->target = $target;
		$this->func = $func;
		$this->vars = new stdClass();
		
		$this->creation_time = time();
		$this->query_id = Query::$next_query_id++;
		
		$this->query_str = str_replace("\t", '', $this->query_str);
		
		QueryManager::$pending_queries++;
	}
	
	public function finish() {
		$this->finish_time = time();
		if($this->target != NULL && $this->func != '') {
			$this->target->{$this->func}($this);
		}
	}
	
	public function __toString() {
		return('query_id: '.$this->query_id.' query_str: '.$this->query_str);
	}
	
	public function remove() {
		unset($this->query_id);
		unset($this->query_str);
		unset($this->result);
		unset($this->creation_time);
		unset($this->finish_time);
		unset($this->vars);
		
		unset($this->target);
		unset($this->func);
		
		unset($this->rows);
		unset($this->error);
		unset($this->insert_id);
		unset($this->query_duration);
		
		QueryManager::$pending_queries--;
	}
}



?>