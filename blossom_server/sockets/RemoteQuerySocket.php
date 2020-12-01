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

class RemoteQuerySocket extends CommandSocket {

	private $connection;
	
	
	public function __construct() {
		parent::__construct();
		$this->timeout = 3600;
	}
	
	
	//--- perform a query on a database ---
	protected function r_query($obj) {
		$query_str = $obj->query_str;
		
		//connect
		if(!isset($this->connection)){
			$this->connection = $this->db_connect();
		}
		
		//do the query
		$start_time = microtime(true);
		$rows = array();
		
		$result = $this->connection->query($query_str);
		if(!$result){
			$error = 'Could not perform query. Error: ' . $this->connection->error . ' Query: ' . $query_str;
		}
		else{
			if($result !== true) {
				while($row = $result->fetch_object()){
					$rows[] = $row;
				}
				$result->free_result();
			}
			else {
				$insert_id = $this->connection->insert_id;
			}
		}
		
		//make sure the connection is cleared out
		while($this->connection->more_results()) {
			$this->connection->next_result();
			$extra_result = $this->connection->store_result();
			if($extra_result !== false) {
				$extra_result->free_result();
			}
		}
		
		//keep record
		$end_time = microtime(true);
		$query_duration = $end_time - $start_time;
		
		//return the result
		$ret_obj = new stdClass();
		$ret_obj->type = 'finish_query';
		$ret_obj->query_duration = $query_duration;
		$ret_obj->query_id = $obj->query_id;
		$ret_obj->rows = $rows;
		@$ret_obj->insert_id = $insert_id;
		@$ret_obj->error = $error;
		
		$this->send($ret_obj);
	}
	
	//--- connect to a database. Privileges: SELECT, INSERT, UPDATE, DELETE, EXECUTE ---
	private function db_connect() {
		global $db_server, $db_user, $db_password, $db_name;
		
		$result = new mysqli($db_server, $db_user, $db_password, $db_name);
		if (!$result) {
			throw new Exception('Could not connect to the database. (select connect)');
		}
		else {
			return $result;
		}
	}
	
	//--- close the database connection if there are no pending queries ---
	public function check_health() {
		if($this->read_buffer == ''){
			if(isset($this->connection)){
				$this->connection->close();
				unset($this->connection);
			}
		}
		
		parent::check_health();
	}
	
	//--- clean up ---
	public function remove() {
		parent::remove();
		exit;
	}
}

?>