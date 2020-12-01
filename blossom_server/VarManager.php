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

class VarManager {

	public $var_array = array();
	private $locked = false;
	
	public function set_vars($variables) {
		if($this->check($variables)){
			if(!$this->locked) {
				foreach($variables as $var => $val) {
					$this->var_array[$var] = $val;
				}
			}
		}
	}
	
	public function get_vars($variables='*') {
		if($this->check($variables)){
			$ret_variables = array();
			
			if($variables == '*') {
				$ret_variables = $this->var_array;
			}
			else{
				foreach($variables as $var) {
					$ret_variables[$var] = @$this->var_array[$var];
				}
			}
			
			return($ret_variables);
		}
	}
	
	public function get_obj_vars($variables='*') {
		$array = $this->get_vars($variables);
		
		$obj = new stdClass();
		foreach ($array as $variable=>$value) {
			$obj->$variable = $value;
		}
		
		return($obj);
	}
	
	public function delete_vars($variables) {
		if($this->check($variables)){
			if(!$this->locked) {
				if($variables == '*') {
					unset($this->var_array);
					$this->var_array = array();
				}
				else{
					foreach($variables as $var) {
						unset($this->var_array[$var]);
					}
				}
			}
		}
	}
	
	public function lock() {
		$this->locked = true;
	}
	
	public function unlock() {
		$this->locked = false;
	}
	
	private function check($variables) {
		if(isset($variables) && $variables != '') {
			return true;
		}
		else {
			return false;
		}
	}
	
	public function remove() {
		unset($var_array);
		unset($locked);
	}
}

?>