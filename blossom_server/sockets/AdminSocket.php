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

class AdminSocket extends CommandSocket {
	
	public function set_socket($socket, $server) {
		parent::set_socket($socket, $server);
		global $address;
		if($this->ip != $address) {
			$this->trigger_remove();
		}
	}

	protected function r_get_stats($rec_obj) {
		$obj = new stdClass();
		$obj->type = 'stats';
		$obj->status = $this->server->get_status();
		$obj->loop_duration = $this->server->get_last_loop_duration();
		$obj->shutdown_duration = $this->server->get_shutdown_duration();
		$obj->users = count(UserSocket::$user_array);
		$obj->sockets = $this->server->count_sockets();
		$obj->reads_per_second = $this->server->get_reads_per_second();
		$obj->writes_per_second = $this->server->get_writes_per_second();
		$obj->pending_queries = QueryManager::$pending_queries;
		$this->send($obj);
	}
	
	protected function r_get_status($rec_obj) {
		$obj = new stdClass();
		$obj->type = 'status';
		$obj->status = $this->server->get_status();
		$this->send($obj);
	}
	
	protected function r_shutdown($rec_obj) {
		global $address;
		if($this->ip == $address) {
			$this->server->shutdown();
		}
	}
	
	protected function kill($vars) {
		global $address;
		if($this->ip == $address) {
			exit;
		}
	}
}

?>