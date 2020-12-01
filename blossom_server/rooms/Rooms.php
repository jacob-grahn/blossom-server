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

class Rooms {

	private static $room_array = array();
	
	public static function create_room($room_name, $room_type, $note='') {
		if (!isset(self::$room_array[$room_name])) {
			if($room_type == 'chat') {
				$new_room = new ChatRoom($room_name);
			}
			else {
				$new_room = new Room($room_name);
			}
			self::add_room($new_room);
			$new_room->var_manager->var_array['note'] = $note;
		}
		return($new_room);
	}
	
	
	public static function join_room($user, $room_name, $room_type, $note='') {
		if (!isset(self::$room_array[$room_name])) {
			self::create_room($room_name, $room_type, $note);
		}
		$room = self::$room_array[$room_name];
		$join_result = $room->add_user($user);
		
		if($join_result == true) {
			return $room;
		}
		else {
			return false;
		}
	}
	
	
	public static function leave_room($user, $room_name) {
		$room = @self::$room_array[$room_name];
		if(isset($room)) {
			$room->remove_user($user);
			if($room->should_be_removed()) {
				self::remove_room($room);
			}
		}
	}
	
	
	public static function get_user_count($room_name) {
		$room = self::$room_array[$room_name];
		if(!isset($room)) {
			$user_count = 0;
		}
		else{
			$user_count = $room->get_user_count();
		}
		return $user_count;
	}
	
	
	public static function get_room($room_name) {
		return self::$room_array[$room_name];
	}
	
	
	public static function get_rooms() {
		$rooms = self::$room_array;
		return($rooms);
	}
	
	
	public static function remove_old_rooms() {
		foreach(self::$room_array as $room_name=>$room) {
			if($room->should_be_removed()) {
				self::remove_room($room);
			}
		}
	}
	
	
	public static function add_room($room) {
		$room_name = $room->get_name();
		if(@self::$room_array[$room_name] == NULL) {
			self::$room_array[$room_name] = $room;
		}
	}
	
	
	private static function remove_room($room) {
		unset(self::$room_array[$room->get_name()]);
		$room->remove();
	}
}

?>