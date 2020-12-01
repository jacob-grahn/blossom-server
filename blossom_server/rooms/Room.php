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

class Room {

	protected $user_array = array();
	protected $name = '';
	protected $pass = '';
	protected $survive_while_empty = 0;
	protected $intro_var_list = '*';
	protected $introductions = true;
	private $empty_since;
	public $var_manager;
	
	
	//--- init ---
	public function __construct($name, $survive_while_empty=0, $pass='') {
		$this->name = $name;
		$this->pass = $pass;
		$this->survive_while_empty = $survive_while_empty;
		$this->var_manager = new VarManager();
		$this->empty_since = time();
	}
	
	
	//--- add a user to this room. their entry is broadcast to all of the other members of this room ---
	public function add_user($user, $pass='') {
		//wrong password
		if(($this->pass != '') && ($pass != $this->pass)) {
			$user->send_error('The password for this room '. $this->name .' is incorrect.');
			return false;
		}
		
		//user is already a member of this room
		else if(isset($this->user_array[$user->socket_id])) {
			//$user->send_error('You are already a member of room '. $this->name);
			return false;
		}
		
		//successfully join the room
		else {
		
			//introduce the new user to the members of this room
			if($this->introductions) {
				$vars = $user->get_intro_obj($this->intro_var_list);
				$vars->t = 'userJoinRoom';
				$vars->roomName = $this->name;
				$this->send_to_room($vars);
			}
			
			//add the new user
			$this->user_array[$user->socket_id] = $user;
			$user->room_array[$this->name] = $this;
			
			//intrudocue the members of this room to the new user
			if($this->introductions) {
				$this->send_users($user, $this->intro_var_list);
			}
			
			return true;
		}
	}
	
	
	//--- remove a user from this room. their removal is broadcast to all of the other members of this room ---
	public function remove_user($user) {
		unset($this->user_array[$user->socket_id]);
		unset($user->room_array[$this->name]);
		
		if($this->introductions) {
			$vars = new stdClass();
			$vars->t = 'userLeaveRoom';
			$vars->roomName = $this->name;
			$vars->socketID = $user->socket_id;
			$this->send_to_room($vars, 0);
		}
		
		if(count($this->user_array) == 0) {
			$this->empty_since = time();
		}
	}
	
	
	//--- send a vars object to everyone in this room. if send_to_self is true, the vars will be sent back to you as well ---
	public function send_to_room($obj, $from_socket_id=0, $send_to_self=false) {
		$str = json_encode($obj);
		$encrypted = Encryptor::encrypt($str);
		
		foreach($this->user_array as $user) {
			if($send_to_self || ($from_socket_id != $user->socket_id)) {
				$user->bypass_send($encrypted);
			}
		}
	}
	
	
	//--- set this room's password. new users will not be able to join without the correct password ---
	public function set_pass($string) {
		if($this->pass == '') {
			$this->pass = $string;
		}
		else {
			throw new Exeption('Can not set pass for room '. $this->name .'. Pass has already been set.');
		}
	}
	
	
	//--- return the number of users in this room
	public function get_user_count() {
		return count($this->user_array);
	}
	
	
	//--- return a string ---
	public function get_users($var_list) {
		$array = array();
		foreach($this->user_array as $member) {
			$user_obj = $member->get_intro_obj($var_list);
			$array[] = $user_obj;
		}
				
		return $array;
	}
	
	
	//--- send a list of users in this room to a user ---
	public function send_users($user, $var_list='*') {
		$user_array = $this->get_users($var_list);
		
		$obj = new stdClass();
		$obj->t = 'receiveUsers';
		$obj->roomName = $this->name;
		$obj->userList = $user_array;
		$user->send($obj);
	}
	
	
	//--- return if a password is required to enter this room ---
	public function get_pass_required() {
		if($this->pass != '') {
			return true;
		}
		else {
			return false;
		}
	}
	
	
	//--- rutrn this room's name ---
	public function get_name() {
		return $this->name;
	}
	
	
	//--- returns a boolean stating wheather or not this room is ready to be removed ---
	public function should_be_removed() {
		$empty_seconds = time() - $this->empty_since;
		if(($this->get_user_count() <= 0) && ($empty_seconds >= $this->survive_while_empty)) {
			return true;
		}
		else {
			return false;
		}
	}

	
	//--- clean up ---
	public function remove() {
		$this->var_manager->remove();
		$user_array = NULL;
		$name = NULL;
		$pass = NULL;
		$survive_while_empty = NULL;
		$intro_var_list = NULL;
		$introductions = NULL;
		$empty_seconds = NULL;
		$var_manager = NULL;
	}
}

?>