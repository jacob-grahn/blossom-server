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

class UserSocket extends AccountSocket {

	public static $user_array = array();
	
	public $room_array = array();
	protected $version = 1.1;

	
	
	//--- set up ---
	public function set_socket($new_socket, $server) {
		parent::set_socket($new_socket, $server);
		$this->write_mode = 'optimized';
		UserSocket::$user_array[$this->socket_id] = $this;
	}
	
	
	
	//--- send the socket id given to this socket ---
	public function send_socket_id() {
		$vars = new stdClass();
		$vars->type = 'receiveSocketID';
		$vars->socketID = $this->socket_id;
		$this->send($vars);
	}
	
	
	
	//--- send the version of this server ---
	public function send_version() {
		$vars = new stdClass();
		$vars->type = 'receiveVersion';
		$vars->version = $this->version;
		$this->send($vars);
	}
	
	
	
	
	//--- tries to find a user associated with a socket_id ---
	protected function id_to_user($socket_id) {
		$user =& UserSocket::$user_array[$socket_id];
		return $user;
	}
	
	

	//--- error messages ---
	public function send_error($error, $type='error') {
		$obj = new stdClass();
		$obj->type = $type;
		$obj->error = $error;
		$this->send($obj);
	}
	
	

	
	
	
	
	
	
	//--- remote functions --------------------------------------------------------------------------------------------------------------------------------
	
	
	
	
	
	
	
	//--- send a ping right back ---
	protected function r_ping($rec_obj) {
		$this->r_p($rec_obj);
	}
	
	protected function r_p($rec_obj) {
		$obj = new stdClass();
		$obj->type = 'ping';
		$obj->time = $rec_obj->time;
		$obj->server_time = time();
		$this->send($obj);
	}
	
	
	
	//--- send some basic info when the socket is first opened --------
	protected function r_confirm_connection($obj) {
		$this->set_encrypting(true);
		$this->send_version();
		$this->send_socket_id();
	}
	
	
	//--- get a snapshot of the server's stats ---
	protected function r_get_stats($rec_obj) {
		$obj = new stdClass();
		$obj->type = 'receiveStats';
		$obj->status = $this->server->get_status();
		$obj->loopDuration = $this->server->get_last_loop_duration();
		$obj->users = count(UserSocket::$user_array);
		$obj->sockets = $this->server->count_sockets();
		$obj->readsPerSecond = $this->server->get_reads_per_second();
		$obj->writesPerSecond = $this->server->get_writes_per_second();
		$obj->pendingQueries = QueryManager::$pending_queries;
		$this->send($obj);
	}


	//--- join a room of people that you can then interact with ---
	protected function r_join_room($obj) {
		$this->r_jr($obj);
	}
	
	protected function r_jr($obj) {
		$room_name = $obj->room_name;
		$room_type = $obj->room_type;
		$note = '';
		if(isset($obj->note)) {
			$note = $obj->note;
		}
		$room = Rooms::join_room($this, $room_name, $room_type, $note);
	}
	
	
	
	//--- leave a room ---
	protected function r_leave_room($obj) {
		$this->r_lr($obj);
	}
	
	protected function r_lr($obj) {
		$room_name = $obj->room_name;
		Rooms::leave_room($this, $room_name);
	}
	
	
	//--- send a string to everyone in a room ---
	protected function r_send_to_room($vars) {
		$this->r_sr($vars);
	}
	
	protected function r_sr($vars) {
		$room_name = $vars->room_name;
		$room = @$this->room_array[$room_name];
		
		if(isset($room)) {
			$send_vars = new stdClass();
			$send_vars->socketID = $this->socket_id;
			$send_vars->data = $vars->data;
			$send_vars->roomName = $room_name;
			
			$room->send_to_room($send_vars, $this->socket_id, $vars->send_to_self);
		}
		else {
			//$this->send_error('Can not send to room '.$room_name.'. Join this room before trying to send messages.');
		}
	}
	
	
	//--- send a message to a single user ---
	protected function r_send_to_user($vars) {
		$this->r_su($vars);
	}
	
	protected function r_su($vars) {
		$to_id = $vars->to_id;
		$user =& $this->id_to_user($to_id);
		
		if(isset($user)) {
			$send_vars = new stdClass();
			$send_vars->socketID = $this->socket_id;
			$send_vars->data = $vars->data;
			
			$user->send($send_vars, $this->socket_id);
		}
		else {
			$this->send_error('Can not send to user '.$to_id.' because they do not exist');
		}
	}
	
	
	//--- set vars ---
	protected function r_manage_vars($vars) {
		$this->r_mv($vars);
	}
	
	protected function r_mv($vars) {
		$location = $vars->location;
		$user_vars = $vars->user_vars;
		$action = $vars->action;
		$id = $vars->id;
		
		//get the target
		if($location == 'user') {
			$socket_id = $id;
			$var_manager = $this->var_manager;
		}
		if($location == 'room') {
			$room_name = $id;
			$room = @$this->room_array[$room_name];
			if(isset($room)) {
				$var_manager = $room->var_manager;
			}
		}
		if($location == 'global') {
			$var_manager = $this->server->var_manager;
		}
		
		if(!isset($var_manager)) {
			//$this->send_error("var manager does not exist. location: $location, user_vars: $user_vars, action: $action, id: $id");
		}
		
		else {
		
			//set
			if($action == 'set') {
				$var_manager->set_vars($user_vars);
			}
			
			//get
			if($action == 'get') {
			
				$ret_vars = new stdClass();
				
				if($location == 'user') {
					$type = 'receiveUserVars';
					$ret_vars->socketID = $id;
				}
				else if($location == 'room') {
					$type = 'receiveRoomVars';
					$ret_vars->roomName = $id;
				}
				else {
					$type = 'receiveGlobalVars';
				}
				
				
				$ret_vars->type = $type;
				$ret_vars->vars = $var_manager->get_vars($user_vars);
				$this->send($ret_vars);
			}
			
			//delete
			if($action == 'delete') {
				$var_manager->delete_vars($variables);
			}
			
			//lock
			if($action == 'lock') {
				$var_manager->lock();
			}
			
			//unlock
			if($action == 'unlock') {
				$var_manager->unlock();
			}
		}
	}
	
	
	
	//--- set room password ---
	protected function r_set_room_pass($vars) {
		$this->r_rp($vars);
	}
	
	protected function r_rp($vars) {
		$room_name = $vars->room_name;
		$pass = $vars->pass;
		
		$room = $this->room_array[$room_name];
		if(isset($room)) {
			$room->set_pass($pass);
		}
	}
	
	
	
	//--- get rooms ---
	protected function r_get_rooms($vars) {
		$this->r_gr($vars);
	}
	
	protected function r_gr($vars) {
		$array = array();
		$room_array = Rooms::get_rooms();
		
		foreach($room_array as $room_name => $room) {
			$obj = new stdClass();
			$obj->roomName = $room_name;
			$obj->members = $room->get_user_count();
			$obj->reqPass = $room->get_pass_required();
			$array[] = $obj;
		}
		
		$ret_vars = new stdClass();
		$ret_vars->type = 'receiveRooms';
		$ret_vars->roomList = $array;
		$this->send($ret_vars);
	}
	
	
	
	
	
	//--- get users ---
	protected function r_get_users($vars) {
		$this->r_gu($vars);
	}
	
	protected function r_gu($vars) {
		$room_name = $vars->room_name;
		$var_list = $vars->var_list;
		
		$room = $this->room_array[$room_name];
		$room->send_users($this, $var_list);
	}
	
	
	
	//--- count users ---
	protected function r_count_users($rec_obj) {
		$obj = new stdClass();
		$obj->users = count(UserSocket::$user_array);
		$this->send($obj);
	}
	
	
	
	//--- ban ---
	public function r_ban_user($obj) {
		if($this->moderator == true) {
			$user_id = $obj->user_id;
			$socket_id = $obj->socket_id;
			$ban_type = $obj->ban_type;
			$seconds = $obj->seconds;
			$reason = $obj->reason;
			$time = time();
			
			$ip = '';
			
			if(isset($socket_id) && $socket_id != 0) {
				if(isset(UserSocket::$user_array[$socket_id])) {
					$user = UserSocket::$user_array[$socket_id];
				}
			}
			
			if(isset($user)) {
				if(!$user->moderator) {
					$ip = $user->ip;
					if($ban_type == 'silence') {
						$user->silence($seconds);
						$user->send_alert('Your account has been silenced. You will not be able to chat or send messages. This ban will expire in '.$seconds.' seconds. Reason: '.$reason);
					}
					else {
						$user->trigger_remove();
					}
				}
			}
			
			if(!isset($user) || $user->moderator == false) {
				$safe_user_id = addslashes($user_id);
				$safe_ban_type = addslashes($ban_type);
				$safe_ban_time = addslashes($time);
				$safe_expire_time = addslashes($time + $seconds);
				$safe_ip = addslashes($ip);
				$safe_mod_id = addslashes($this->user_id);
				$safe_reason = addslashes($reason);
					
				$query_str = "insert into bans
								set banned_user_id = '$safe_user_id',
									mod_id = '$safe_mod_id',
									ban_time = '$safe_ban_time',
									expire_time = '$safe_expire_time',
									banned_ip = '$safe_ip',
									ban_type = '$safe_ban_type',
									reason = '$safe_reason'";
									
				$this->do_query($query_str, '');
			}
		}
	}
	
	
	
	
	//--- clean up ---
	public function remove() {
		foreach($this->room_array as $room_name => $room) {
			Rooms::leave_room($this, $room_name);
		}
		
		unset(UserSocket::$user_array[$this->socket_id]);
		unset($this->room_array);
		
		parent::remove();
	}
}

?>