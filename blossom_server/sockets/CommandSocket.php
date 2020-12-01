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

class CommandSocket extends Socket {
	
	protected $end_str;
	private $encrypt = false;
	private $write_num = 0;
	private $read_num = 0;
	private $confirmed_iv = true;
	protected $write_mode = 'normal';
	protected $trace_read = false;
	protected $trace_write = false;
	
	public function __construct() {
		$this->end_str = chr(0x04);
	}
	
	
	//--- converts an object into a string, encrypts it, adds some goodies, and then sends it ---
	public function send($vars) {
		$this->write_num++;
		
		if($this->write_mode == 'optimized') {
			$str_vars = json_encode($vars);
			
			if($this->trace_write) {
				output("write $str_vars");
			}
			
			if($this->encrypt) {
				$send_str = Encryptor::encrypt($str_vars);
			}
			else{
				$send_str = $str_vars;
			}
			$send_str = $this->write_num .' '. $send_str . $this->end_str;
		}
		
		else if($this->write_mode == 'normal'){
			$vars->write_num = $this->write_num;
			$str_vars = json_encode($vars);
			
			if($this->trace_write) {
				output("write $str_vars");
			}

			if($this->encrypt) {
				$send_str = Encryptor::encrypt($str_vars);
			}
			else{
				$send_str = $str_vars;
			}
			$send_str = $send_str . $this->end_str;
		}
		
		$this->write($send_str);
	}
	
	
	//--- sends an already encrypted string ---
	public function bypass_send($str) {
		if(!$this->removing) {
			$this->write_num++;
			$send_str = $this->write_num .' '. $str . $this->end_str;
			$this->write($send_str);
		}
	}
		
	
	
	//--- sets whether or not this socket will encrypt incoming and outgoing transmissions ---
	public function set_encrypting($bool) {
		if($bool) {
			$str_iv = Encryptor::get_iv();
			
			$obj = new stdClass();
			$obj->type = 'receiveIV';
			$obj->iv = $str_iv;
			$this->send($obj, true);
		}
		
		$this->encrypt = $bool;
	}
	

	//--- parses received info ---
	public function read() {
		parent::read();
		while (true) {
			$end_pos = strpos($this->read_buffer, $this->end_str);
			if ($end_pos === false) {
				break;
			}
			else {
				$message = substr($this->read_buffer, 0, $end_pos);
				$this->receive_message($message);
				$this->read_buffer = substr($this->read_buffer, $end_pos+1);
			}
			if($this->removing == true) {
				break;
			}
		}
	}

		
	
	//--- Call a function based on the received string. The format of the string should be "function: data" ---
	protected function receive_message($message) {
		try {
			$this->read_num++;
			
			if($this->encrypt) {
				$message = Encryptor::decrypt($message, !$this->confirmed_iv);
			}
			
			$ip = $this->ip;
			
			if($this->trace_read) {
				output("read $ip $message");
			}
			
			$vars = json_decode($message);
			
			//make sure things match up
			if(isset($vars->write_num)) {
				$remote_write_num = $vars->write_num;
			}
			else {
				$remote_write_num = $vars->n;
			}
			if($this->read_num != $remote_write_num) {
				throw new Exception('The read number does not match the write number. read_num: '. $this->read_num .' write_num: '. $remote_write_num);
			}
			
			//figure out which function to call
			if(isset($vars->type)) {
				$type = $vars->type;
			}
			else {
				$type = $vars->t;
			}
			$function = 'r_'. $type;
			if(!method_exists($this, $function)) {
				throw new Exception("$function is not a method");
			}
			else {
				//call the function
				$this->{$function}($vars);
			}
		}
		catch(Exception $e) {
			output('Error: '.$e->getMessage()."\n");
			$this->trigger_remove();
		}
	}
	
	
	public function change_iv($str_iv) {
		if($this->encrypt) {
			$obj = new stdClass();
			$obj->type = 'receiveIV';
			$obj->iv = $str_iv;
			$this->send($obj);
		
			$this->confirmed_iv = false;
		}
	}
	
	
	private function r_confirm_iv($obj) {
		$this->confirmed_iv = true;
	}
	
	
	public function remove() {
		unset($this->end_str);
		unset($this->encrypt);
		unset($this->write_num);
		unset($this->read_num);
		unset($this->write_mode);
		parent::remove();
	}
}

?>