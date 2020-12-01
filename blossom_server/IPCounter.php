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


class IPCounter {
	
	private static $limit = 10;
	private static $ip_array = array();
	
	
	public static function increment_ip($ip) {
		$ip_count = self::count_connections($ip);
		$ip_count++;
		self::$ip_array[$ip] = $ip_count;
	}
	
	
	public static function decrement_ip($ip) {
		$ip_count = @self::$ip_array[$ip];
		if(isset($ip_count)) {
			$ip_count--;
			if($ip_count <= 0) {
				unset(self::$ip_array[$ip]);
			}
			else {
				self::$ip_array[$ip] = $ip_count;
			}
		}
	}
	
	
	public static function count_connections($ip) {
		$ip_count =  @self::$ip_array[$ip];
		if(!isset($ip_count)) {
			$ip_count = 0;
		}
		return $ip_count;
	}
	
	
	public static function is_over_limit($ip) {
		$ip_count = self::count_connections($ip);
		if($ip_count >= self::$limit) {
			return true;
		}
		else {
			return false;
		}
	}
}

?>