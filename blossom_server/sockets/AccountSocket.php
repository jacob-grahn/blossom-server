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



class AccountSocket extends CommandSocket {

	protected $pass_salt = '45sda#sSS';
	private $last_save_time;
	private $save_freq = 3600; //auto save once every hour
	
	public $name;
	public $user_id;
	public $moderator = false;
	public $email;
	
	public $var_manager;
	public $secure_var_manager;
	
	private $silenced_until = -1;
	
	
	//--- init ---
	public function __construct() {
		$this->last_save_time = time();
		$this->var_manager = new VarManager();
		$this->secure_var_manager = new VarManager();
		parent::__construct();
	}
	
	
	//--- login ---------------------------------------------------------------------------------------------------------------------------------------
	protected function r_login($obj) {
		$name = $obj->name;
		$pass = $obj->pass;
		
		if($name == '' || $pass == '' || !isset($name) || !isset($pass)) {
			$this->send_login_error('Values name and pass must be set.');
		}
		
		else if(strlen($name) > 50) {
			$this->send_login_error('Your name can not be longer than 50 characters.');
		}
		
		else{
			$safe_name = addslashes($name);
			$safe_pass = addslashes($pass . $this->pass_salt);
			
			$query_str = "select * from users
							where name = '$safe_name'
							and pass = sha1('$safe_pass')
							limit 0, 1";
			
			$this->do_query($query_str, 'login_2');
		}
	}
	
	
	//--- if the login was ok, check if the account is banned
	public function login_2($query) {
		if($query->error != NULL) {
			$this->send_login_error($query->error);
		}
		
		else if(count($query->rows) == 0) {
			$this->send_login_error('No account was found with that name and password.');
		}

		else {
			//login
			$row = $query->rows[0];
			
			$this->name = $row->name;
			$this->user_id = (int)$row->user_id;
			$this->email = $row->email;
			$this->set_vars($row);
			
			if($row->moderator == 0){
				$this->moderator = false;
			}
			else if($row->moderator == 1){
				$this->moderator = true;
			}
			
			//check if this account is banned
			$safe_user_id = addslashes($this->user_id);
			$safe_time = addslashes(time());
			
			$query_str = "select * from bans
							where banned_user_id = '$safe_user_id'
							and expire_time > '$safe_time'
							limit 0,1";
	
			$ban_query = $this->do_query($query_str, 'login_3');
		}
	}
	
	//--- if the account is not banned, make the login official ---
	public function login_3($query) {
		//error
		if($query->error != NULL) {
			$this->send_login_error($query->error);
		}
		
		//banned
		else if(count($query->rows) > 0) {
			$row = $query->rows[0];
			if($row->ban_type == 'ban') {
				$this->send_login_error('Your account has been banned. Reason: '. $row->reason);
			}
			else if($row->ban_type == 'silence') {
				$this->silence($row->expire_time - $row->ban_time);
				$this->finalize_login();
			}
		}
		
		//not banned, procede with the login
		else{
			$this->finalize_login();
		}
	}
	
	protected function set_vars($row) {
		$this->var_manager->set_vars(json_decode($row->vars));
		$this->secure_var_manager->set_vars(json_decode($row->secure_vars));
	}
	
	protected function finalize_login() {
		//update status
		$safe_ip = addslashes($this->ip);
		$safe_user_id = addslashes($this->user_id);
		
		$query_str = "update users
						set status = 'online',
							last_login_ip = '$safe_ip'
						where user_id = '$safe_user_id'";
						
		$this->do_query($query_str, '');

		//send successfull login
		$obj = $this->get_intro_obj('*');
		$obj->type = 'loginSuccess';
		$this->send($obj);
	}
	
	
	
	
	
	//--- register new account ------------------------------------------------------------------------------------------------------------------
	protected function r_register($obj) {
		$name = $obj->name;
		$pass = $obj->pass;
		$email = $obj->email;
		
		if($name == '' || $pass == '' || !isset($name) || !isset($pass)) {
			$this->send_register_error('Values name and pass must be set.');
		}
		
		else if(strlen($name) > 50) {
			$this->send_register_error('Your name can not be longer than 50 characters.');
		}
		
		else if(strlen($email) > 200){
			$this->send_register_error('Your email can not be longer than 200 characters.');
		}
		
		else{
			$safe_name = addslashes($name);
			
			$query_str = "select users.name
							from users
							where name = '$safe_name'
							limit 0, 1";
							
			$query = $this->do_query($query_str, 'account_available_result');
			
			$query->vars = $obj;
		}
	}
	
	//--- if the account is available, register it ---
	public function account_available_result($query) {
		if($query->error != NULL) {
			$this->send_register_error($query->error);
		}
		
		else if(count($query->rows) > 0){
			$this->send_register_error('That name is not available. :(');
		}
		
		else {
			$obj = $query->vars;
			$name = $obj->name;
			$pass = $obj->pass;
			$email = $obj->email;
		
			$safe_name = addslashes($name);
			$safe_pass = addslashes($pass . $this->pass_salt);
			$safe_email = addslashes($email);
			$safe_time = addslashes(time());
			$safe_ip = addslashes($this->ip);
			
			$query_str = "insert into users
							set name = '$safe_name',
								pass = sha1('$safe_pass'),
								email = '$safe_email',
								register_time = '$safe_time',
								register_ip = '$safe_ip'";
			
			$query = $this->do_query($query_str, 'register_result');
		}
	}
	
	//--- check if the registration was succesfull ---
	public function register_result($query) {
		if($query->error != NULL) {
			$this->send_register_error($query->error);
		}
		else {
			$obj = new stdClass();
			$obj->type = 'registerSuccess';
			$this->send($obj);
		}
	}
	
	
	
	
	
	//--- save this account's vars to a database so they can be retrieved later ------------------------------------------------------------------
	public function save() {
		if(isset($this->user_id)) {
			
			$str_vars = json_encode($this->var_manager->get_vars());
			$safe_vars = addslashes($str_vars);
			$safe_user_id = addslashes($this->user_id);
			
			$query_str = "update users
							set vars = '$safe_vars',
								status = 'offline'
							where user_id = '$safe_user_id'";
			
			$this->do_query($query_str, 'save_result');
		}
	}
	
	public function save_result($query) {
		if($query->error != NULL) {
			output($query->error);
			//throwing an exception here will cause the query socket to close :(
		}
	}
	
	
	
	
	
	
	
	
	
	
	//--- create a Query object and send it to the query manager ---
	protected function do_query($query_str, $return_func=NULL) {
		$query = new Query($query_str, $this, $return_func);
		QueryManager::do_query($query);
		return($query);
	}
	
	
	
	
	//--- error messages ---
	protected function send_login_error($error) {
		$this->send_error($error, 'loginError');
	}
	
	protected function send_register_error($error) {
		$this->send_error($error, 'registerError');
	}
	
	protected function send_error($error, $type='error') {
		$obj = new stdClass();
		$obj->type = $type;
		$obj->message = $error;
		$this->send($obj);
	}
	
	
	//--- alerts ----
	public function send_alert($message) {
		$obj = new stdClass();
		$obj->type = 'alert';
		$obj->message = $message;
		$this->send($obj);
	}
	
	
	public function send_logout_trigger($message) {
		$obj = new stdClass();
		$obj->type = 'logoutTrigger';
		$obj->message = $message;
		$this->send($obj);
	}
	
	
	
	//--- create vars to be sent to other users as an introduction ---
	public function get_intro_obj($intro_var_list='*') {
		$obj = new stdClass();
		$obj->socketID = $this->socket_id;
		$obj->vars = $this->var_manager->get_vars($intro_var_list);
		
		if(isset($this->user_id)) {
			$obj->userID = $this->user_id;
		}
		if(isset($this->name)) {	
			$obj->userName = $this->name;
		}
		if(isset($this->moderator) && $this->moderator == true) {
			$obj->moderator = $this->moderator;
		}
		
		return $obj;
	}
	
	
	
	//--- save vars to db every hour ---
	public function check_health() {
		$elapsed = time() - $this->last_save_time;
		if($elapsed > $this->save_freq) {
			$this->save();
		}
		parent::check_health();
	}
	
	
	
	//--- prevent this account from sending chats or PMs ---
	public function silence($seconds) {
		$this->silenced_until = time() + $seconds;
	}
	
	
	
	//--- check if this account has been silenced ---
	public function is_silenced() {
		if($this->silenced_until > time()) {
			return true;
		}
		else {
			return false;
		}
	}

	
	
	//--- remove ---
	public function remove() {
		if(isset($this->user_id)) {
			$this->save();
		}
		
		$this->var_manager->remove();
		$this->secure_var_manager->remove();
		
		unset($this->var_manager);
		unset($this->secure_var_manager);
		unset($this->room_array);
		unset($this->pass_salt);
		
		parent::remove();
	}
}

?>