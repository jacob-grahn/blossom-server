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

class LocalQuerySocket extends CommandSocket {

	private $query_array = array();
	
	public function __construct() {
		parent::__construct();
		$this->timeout = 3600;
	}
	
	//--- register this socket with the query manager ---
	public function set_socket($socket, $server) {
		parent::set_socket($socket, $server);
		QueryManager::add_socket($this);
		$this->live_past_shutdown = true;
		
		global $address;
		if($this->ip != $address) {
			$this->trigger_remove();
		}
	}
	
	
	//--- send a query to the remote query server ---
	public function add_query($query) {
		//save the query for later
		$this->query_array[$query->query_id] = $query;
		
		//format the query for sending
		$obj = new stdClass();
		$obj->type = 'query';
		$obj->query_str = $query->query_str;
		$obj->query_id = $query->query_id;
		
		//send the query
		$this->send($obj);
	}
	
	
	
	//--- accept a completed query from the remote query server ---
	protected function r_finish_query($obj) {
		//trigger whatever the query is supposed to do
		$query_id = $obj->query_id;
		$query = $this->query_array[$query_id];
		$query->rows = $obj->rows;
		$query->insert_id = $obj->insert_id;
		$query->query_duration = $obj->query_duration;
		$query->error = $obj->error;
		$query->finish();
		
		//remove the query
		$query->remove();
		unset($this->query_array[$query_id]);
	}
	
	
	
	//--- clean up ---
	public function remove() {
		//remove this socket from the query manager list
		QueryManager::remove_socket($this);
		
		//send any unfinished queries to be retired
		foreach($this->query_array as $query){
			QueryManager::do_query($query);
		}
		
		unset($this->query_array);
		
		parent::remove();
	}
}

?>