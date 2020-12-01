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

$import_dir = '/home/jiggmin/blossom/blossom_server/';

require_once($import_dir.'Interval.php');
require_once($import_dir.'Encryptor.php');
require_once($import_dir.'VarManager.php');
require_once($import_dir.'IPCounter.php');
require_once($import_dir.'rooms/Room.php');
require_once($import_dir.'rooms/Rooms.php');
require_once($import_dir.'sockets/Socket.php');
require_once($import_dir.'sockets/CommandSocket.php');
require_once($import_dir.'sockets/AdminSocket.php');
require_once($import_dir.'sockets/ListenerSocket.php');
require_once($import_dir.'sockets/AccountSocket.php');
require_once($import_dir.'sockets/UserSocket.php');
require_once($import_dir.'sockets/LocalQuerySocket.php');
require_once($import_dir.'sockets/RemoteQuerySocket.php');
require_once($import_dir.'sockets/PolicySocket.php');
require_once($import_dir.'database/Query.php');
require_once($import_dir.'database/QueryManager.php');




class BlossomServer {
	
	private $interval_array = array();
	private $socket_array = array();
	private $encryption_key = 'UypGaEFOMi9MN0JXZGpQKA==';
	private $status = 'initializing';
	private $start_shutdown_time;
	private $last_loop_duration = 0;
	
	private $last_tally_time = 0;
	private $reads_per_second = 0;
	private $writes_per_second = 0;
	
	public $var_manager;

	
	//--- set key used for encryption --- 
	public function set_key($key) {
		$this->encryption_key = $key;
	}
	
	
	//--- set remote query server ---
	public function set_query_server_path($path) {
		QueryManager::set_query_server_path($path);
	}
	
	
	//--- call a function every so often ---
	public function set_interval($target, $func, $freq) {
		$interval = new Interval($target, $func, $freq);
		$this->interval_array[] = $interval;
	}
	
	
	//--- get ---
	public function get_status() {
		return $this->status;
	}
	public function get_last_loop_duration() {
		return $this->last_loop_duration;
	}
	public function get_shutdown_duration() {
		return(time() - $this->start_shutdown_time);
	}
	public function count_sockets() {
		return(count($this->socket_array));
	}
	public function get_reads_per_second() {
		return $this->reads_per_second;
	}
	public function get_writes_per_second() {
		return $this->writes_per_second;
	}
	
	
	//--- listen for incomming connections on said port ---
	public function listen($address, $port, $socket_class='IntroSocket', $live_past_shutdown=false) {
		$listener = new ListenerSocket();
		$listener->init($address, $port, $this, $socket_class, $live_past_shutdown);
	}
	
	
	//--- connect to a remote socket ---
	public function connect($address, $port, $user_class) {
		$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
		$result = socket_connect($socket, $address, $port);
		if(!$result){
			throw new Exception('Could not connect to address: '. $address .' port: '. $port .' '. socket_strerror(socket_last_error()));
		}
		socket_set_nonblock($socket);
		$user = new $user_class();
		$user->set_socket($socket, $this);
	}
	

	//--- accept connections, read, write, and run an interval ---
	public function start() {
	
		set_time_limit(0);
		output('Starting Blossom Server');
		$this->status = 'running';
		
		Encryptor::init($this->encryption_key);
		
		$this->var_manager = new VarManager();
		
		$this->set_interval($this, 'check_health', 30);
		$this->set_interval($this, 'check_queries', 5);
		$this->set_interval($this, 'auto_shutdown', (60*60*24*30)); //once a month
		$this->set_interval($this, 'tally_reads_and_writes', 1);
		$this->set_interval($this, 'change_iv', 200);
		$this->set_interval($this, 'remove_old_rooms', 5);
		
		//start an infinite loop
		while (true) {
			
			$start_time = microtime(true);
			
			try {
			
				//will contain every socket
				$read_array = array();
				//will contain only sockets with data waiting to be written
				$write_array = array();
				//will contain every socket
				$exception_array = array();
				
				//fill the arrays with sockets
				foreach ($this->socket_array as $user) {
					$read_array[] = $user->socket;
					if($user->get_write_buffer_len() > 0) {
						$write_array[] = $user->socket;
					}
				}
				$exception_array = $read_array;
				
				
				//socket_select does not enjoy being passed an empty $read_arary, so just skip it if this is the case
				//this will likely only hapen as the server is shutting down
				//the sleep keeps the infinite loop from going out of control, while still allowing the check_shutdown interval to run
				if(count($read_array) == 0) {
					sleep(1);
				}
				
				else {
					//this function is what prevents the infinite loop from going out of control. 
					//It sits and waits for one of the sockets in it's arrays to do something.
					//the events that will allow the script to continue are:
					//a socket in $read_array recives data
					//a sockeet in $write_array is able to write data
					//a socket in $exception_array farts
					//more than 500000 milliseconds pass
					$result = socket_select($read_array, $write_array, $exception_array, 0, 500000);
					
					//if $result is false, things have likely gone very wrong. exit the program to prevent an infinite loop
					if($result === false) {
						output('Could not select active sockets. Reason: '.socket_strerror(socket_last_error()));
						exit();
					}
				
					//read from all sockets with data coming in
					foreach($read_array as $socket) {
						$user = $this->socket_array[(int)$socket];
						$user->read();
					}
						
					//write to all sockets with data going out
					foreach($write_array as $socket) {
						$user = $this->socket_array[(int)$socket];
						$user->send_data();
					}
						
					//remove sockets that have closed for whatever reason
					foreach($exception_array as $socket) {
						$user = $this->socket_array[(int)$socket];
						$user->trigger_remove();
					}
				}

				
				//remove sockets that want to be closed
				$time = time();
				foreach($this->socket_array as $user) {
					if($user->removing && $user->delay_remove_until < $time) {
						$this->remove_user($user);
					}
				}
				
				//run intervals
				foreach($this->interval_array as $interval) {
					$interval->run();
				}
			}
			
			catch (Exception $e) {
				output("SocketServer Error: $e");
			}
			
			$end_time = microtime(true);
			$this->last_loop_duration = $end_time - $start_time;
		}
	}
	
	
	//--- adds a user to the system ---
	public function on_user_add(&$user) {
		$this->socket_array[(int)$user->socket] = $user;
	}
	
	
	//--- removes a user from the server ---
	private function remove_user(&$user) {
		$index = (int)$user->socket;
		if($this->socket_array[$index] == $user){
			unset($this->socket_array[$index]);
		}
		
		$user->remove();
	}
	
	
	//--- checks on the query manager ---
	public function check_queries() {
		QueryManager::check_queries();
	}
	
	
	//--- keep things lively ---
	public function check_health() {
		foreach ($this->socket_array as $user) {
			$user->check_health();
		}
	}
	
	
	//--- calculate the reads and writes per second ---
	public function tally_reads_and_writes() {
		$cur_time = microtime(true);
		$elapsed = $cur_time - $this->last_tally_time;
		$this->last_tally_time = $cur_time;
		
		$reads = 0;
		$writes = 0;
		
		foreach ($this->socket_array as $socket) {
			$reads += $socket->get_reads();
			$writes += $socket->get_writes();
			$socket->reset_reads_and_writes();
		}
		
		$this->reads_per_second = round($reads / $elapsed);
		$this->writes_per_second = round($writes / $elapsed);
	}
	
	
	
	//--- change the iv this server's encryption is using ---
	public function change_iv() {
		$str_iv = Encryptor::generate_iv();
		
		foreach ($this->socket_array as $socket) {
			$socket->change_iv($str_iv);
		}
		
		Encryptor::set_iv($str_iv);
	}
	
	
	
	
	
	
	//--- shut down after a while ---
	public function auto_shutdown() {
		$this->shutdown();
	}
	
	
	//--- shut down all sockets and exit ---
	public function shutdown() {
		if($this->status == 'running') {
			output('Shutting Down Blossom Server');
			$this->status = 'shutdown';
			$this->start_shutdown_time = time();
		
			foreach ($this->socket_array as $socket) {
				if(!$socket->live_past_shutdown) {
					$socket->trigger_remove();
				}
			}
			
			$this->set_interval($this, 'check_shutdown', 1);
		}
		
		else {
			exit();
		}
	}
	
	
	//--- check the progress of the shutdown ---
	public function check_shutdown() {
		$pending_queries = QueryManager::$pending_queries;
		$shutdown_duration = $this->get_shutdown_duration();
		$live_sockets = 0;
		
		foreach ($this->socket_array as $socket) {
			if(!$socket->live_past_shutdown) {
				$live_sockets++;
			}
		}
			
		if($live_sockets == 0 || $shutdown_duration > 60) {
			exit();
		}
	}
	
	
	//--- remove old rooms ---
	public function remove_old_rooms() {
		Rooms::remove_old_rooms();
	}
}

//--- output in whatever format is good ---
function output($str) {
	echo "$str \n";
}


?>