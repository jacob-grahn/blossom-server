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

class ListenerSocket extends Socket {

	private $socket_class;
	
	//--- create a listener socket and bind to the specified port ---
	public function init($address, $port, $server, $socket_class, $live_past_shutdown) {
		
		try {
			if(!is_subclass_of($socket_class, 'Socket')) {
				throw new Exception('Could not create listener socket. "'.$socket_class.'" must be a subclass of class Socket.');
			}
			
			$this->timeout = 0;
			$this->socket_class = $socket_class;
			$this->live_past_shutdown = $live_past_shutdown;
			
			output('Creating listener socket at '.$address .' '. $port);
			
			$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
			if ($socket == false) {
				throw new Exception("Could not create master socket. reason: " . socket_strerror($socket) . "\n");
			}
			
			socket_set_option($socket, SOL_SOCKET, SO_REUSEADDR, 1);
			socket_set_nonblock($socket);
			
			if (!socket_bind($socket, $address, $port)) {
			   throw new Exception('Could not bind master socket to port '.$port);
			}
			
			if (!socket_listen($socket, 20)) {
			   throw new Exception('Could not set master socket to listen.');
			}
			
			$this->set_socket($socket, $server);
		}
		catch (Exception $e) {
			$this->trigger_remove();
		}
	}
	
	//--- accept incoming connections ---
	public function read() {
		while($new_socket = @socket_accept($this->socket)) {
				
			//get the ip this socket has connected from
			$ip = '0';
			socket_getpeername($new_socket, $ip);
			
			//if too many connections have been made from this ip, don't accept the connection
			//(this helps the server survive DDOS attacks)
			if(IPCounter::is_over_limit($ip)) {
				//output('denying connection from '.$ip.' '.time());
				@socket_shutdown($new_socket, 2);
				@socket_close($new_socket);
			}
			
			//otherwise, initialize the socket
			else {			
				$new_user = new $this->socket_class();
				$new_user->set_socket($new_socket, $this->server);
			}
		}
	}
}

?>