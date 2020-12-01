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

//interfaces with an external process to perform queries
//this is annoyingly complex, but it avoids blocking
//and reduces the number of database connections needed

class QueryManager {

	//--- static -----------------------------------
	public static $instance;
	public static $pending_queries = 0;	
	private static $query_server_path = '';
	private static $socket_array = array();
	private static $query_array = array();
	

	
	//--- sets the path to start a remote query server if needed ---
	public static function set_query_server_path($path) {
		self::$query_server_path = $path;
	}
	
	
	//--- send the query to a socket to be sent to a remote query server, or
	//hold on to the query if there are no remote query servers available ---
	public static function do_query($query) {
		if(count(self::$socket_array) == 0){
			self::$query_array[$query->query_id] = $query;
		}
		else{
			$socket = self::$socket_array[array_rand(self::$socket_array)];
			$socket->add_query($query); 
		}
	}
	
	
	//--- adds a socket that is connected to a remote query server ---
	public static function add_socket($socket) {
		self::$socket_array[$socket->socket_id] = $socket;
		foreach(self::$query_array as $query) {
			$socket->add_query($query);
		}
		self::$query_array = array();
	}
	
	
	//--- stop using a socket ---
	public static function remove_socket($socket) {
		unset(self::$socket_array[$socket->socket_id]);
	}
	
	
	//--- start a new query server if queries need to be done but there are no query servers available
	// this is done on an interval to make sure the remote query server has enough time to start ---
	public static function check_queries() {
		//
		if(count(self::$query_array) > 0 && count(self::$socket_array) == 0){
			
			if(self::$query_server_path == '') {
				throw new Exeption('You are trying to perform a query, but you have not set a path to a query server.');
			}
			
			$result = exec(self::$query_server_path);
		}
	}
}


?>