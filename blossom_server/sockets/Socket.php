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

class Socket {
	
	protected static $next_socket_id = 1;
	
	public $socket;
	public $ip;
	public $socket_id;
	public $live_past_shutdown = false;
	public $removing = false;
	public $delay_remove_until = 0;
	public $max_read_buffer = 99999;
	
	protected $server;
	protected $read_buffer = "";
	protected $write_buffer = "";
	protected $timeout = 60;
	private $last_read_time = 0;
	private $reads = 0;
	private $writes = 0;


	public function set_socket($socket, $server) {
		$this->socket = $socket;
		$this->socket_id = Socket::$next_socket_id++;
		$this->server = $server;
		socket_set_nonblock($socket);
		@socket_getpeername($socket, $this->ip);
		
		IPCounter::increment_ip($this->ip);
		
		//output('accepting connection from: '.$this->ip.' ('.IPCounter::count_connections($this->ip).') '.time());
		
		$this->last_read_time = time();
		
		$server->on_user_add($this);
	}
	
	
	//--- get ---
	public function get_reads() {
		return $this->reads;
	}
	public function get_writes() {
		return $this->writes;
	}
	public function get_write_buffer() {
		return $this->write_buffer;
	}
	public function get_write_buffer_len() {
		return strlen($this->write_buffer);
	}
	
	//--- set ---
	public function reset_reads_and_writes() {
		$this->reads = 0;
		$this->writes = 0;
	}
	

	
	//--- send info to the client through their open socket ---
	protected function write($write_string) {
		$this->write_buffer .= $write_string;
	}
	
	
	//--- send as much of the write buffer through the socket as possible ---
	public function send_data () {
		try {
			//output('write: '.$this->write_buffer);
			$bytes_written = socket_write($this->socket, $this->write_buffer);
			if($bytes_written === false) {
				throw new Exception("Could not write to socket: ".$this->get_error());
			}
			
			$this->write_buffer = substr($this->write_buffer, $bytes_written);
			
			//count the writes
			$this->writes++;
		}
		
		catch (Exception $e) {
			$this->trigger_remove();
		}
	}
	
	
	//--- read info received from the socket, if available ---
	public function read() {
		try{
		
			//read as much info as is available
			$read_string = @socket_read($this->socket, $this->max_read_buffer+1);
			
			if($read_string === false || $read_string == '') {
				throw new Exception('Could not read from socket: '.$this->get_error());
			}
			
			//output('read: '.$read_string);
			
			//add the read info to the read buffer
			$this->read_buffer .= $read_string;
			
			//prevent a spam data attack
			if(strlen($this->read_buffer) > $this->max_read_buffer) {
				$this->read_buffer = '';
				throw new Exception('Possible data attack.');
			}
			
			//respond to a policy request
			if($this->read_buffer == '<policy-file-request/>'.chr(0x00)) {
				$this->read_buffer = '';
				$this->write('<cross-domain-policy><allow-access-from domain="*" to-ports="*" /></cross-domain-policy>'.chr(0x00));
			}
			
			//keep track of the last time this socket was active
			$this->last_read_time = time();
			
			//count the reads
			$this->reads++;
		}
		
		catch (Exception $e) {
			$this->trigger_remove();
		}
	}
	
	public function check_health() {
		//remove this socket if it has not received data in a while
		if($this->timeout != 0){
			if(time() - $this->last_read_time > $this->timeout) {
				$this->trigger_remove();
			}
		}
		
		//remove this socket if it's dead
		else if(!is_resource($this->socket)) {
			$this->trigger_remove();
		}
	}
	
	public function change_iv($str_iv) {
	}
	
	public function get_error() {
		$error = socket_strerror(socket_last_error($this->socket));
		socket_clear_error($this->socket);
		return $error;
	}

	public function trigger_remove($delay_seconds=0) {
		if($delay_seconds > 0) { 
			if(!$this->removing) {
				$this->delay_remove_until = time() + $delay_seconds;
			}
		}
		else {
			$this->delay_remove_until = 0;
		}
		$this->removing = true;
	}
	
	public function remove() {
		if(is_resource($this->socket)) {
			@socket_shutdown($this->socket, 2);
			@socket_close($this->socket);
		}
		
		IPCounter::decrement_ip($this->ip);
		
		unset($this->server);
		unset($this->ip);
		//unset($this->socket_id);
		unset($this->socket);
		unset($this->read_buffer);
		unset($this->write_buffer);
		unset($this->last_read_time);
	}
}

?>