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

class PolicySocket extends Socket {

	private $request;
	private $policy;
	
	public function __construct() {
		$EOL = chr(0x00);
		
		$this->request = '<policy-file-request/>'.$EOL;
		
		$this->policy = '
		<?xml version="1.0"?>
		<!DOCTYPE cross-domain-policy SYSTEM "/xml/dtds/cross-domain-policy.dtd">
		
		<cross-domain-policy> 
		   <site-control permitted-cross-domain-policies="master-only"/>
		   <allow-access-from domain="*" to-ports="*" />
		</cross-domain-policy>'.$EOL
		;
	}
	
	
	public function read() {
		parent::read();
		
		if($this->read_buffer == $this->request){
			$this->read_buffer = '';
			$this->write($this->policy);
		}
	}
}

?>