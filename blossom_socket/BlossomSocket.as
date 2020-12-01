/*

Blossom Socket 1.2
Copyright (C) 2011 Jacob Grahn <contact@jiggmin.com>
See http://blossom-server.com/ for more information

Blossom Socket is free software: you can redistribute it and/or modify
it under the terms of the GNU Lesser General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

Blossom Socket is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU Lesser General Public License for more details.

You should have received a copy of the GNU Lesser General Public License
along with Blossom Socket.  If not, see <http://www.gnu.org/licenses/>.

*/


/*-------------------------------------------------------------------------------------------------------------------
-- provides functions for joining rooms, pinging, and such
-------------------------------------------------------------------------------------------------------------------*/



package com.jiggmin.blossomSocket {
	
	import flash.utils.setInterval;
	import flash.utils.clearInterval;
	import flash.events.Event;
	
	public class BlossomSocket extends CommandSocket {
		
		private var _socketID:int;
		private var _pingTime:int;
		private var pingInterval:uint;
		private var timeInterval:uint;
		private var lastTime:Number = 0;
		private var baseServerTime:Number = 0;
		private var baseLocalTime:Number = 0;
		protected var version:Number = 1.2;
		
		private var _me:BlossomUser;
		
		
		//--- init ---
		public function BlossomSocket(address:String, port:int, key:String):void {
			super();
			initListeners();
			setKey(key);
			connect(address, port);
			
			timeInterval = setInterval(checkTime, 100);
			_me = new BlossomUser(this, 0, 0, null, false, new Object());
		}
		
		
		
		//--- get ---
		public function get socketID():int {
			return(_socketID);
		}
		public function get pingTime():int {
			return(_pingTime);
		}
		public function get me():BlossomUser {
			return(_me);
		}
	
		
		
		//--- get the server's stats ---
		public function getStats():void {
			var obj:Object = new Object();
			obj.t = "get_stats";
			send(obj);
		}
		
		
		//--- register a new account ---
		public function register(userName:String, pass:String, email:String=""):void {
			var obj:Object = new Object();
			obj.t = "register";
			obj.name = userName;
			obj.pass = pass;
			obj.email = email;
			send(obj);
		}
		
		
		//--- log into an already existing account ---
		public function login(userName:String, pass:String):void {
			var obj:Object = new Object();
			obj.t = "login";
			obj.name = userName;
			obj.pass = pass;
			send(obj);
		}
	
		
		//--- send a ping to test our connection speed ---
		public function sendPing():void {
			var obj:Object = new Object();
			obj.t = "ping";
			obj.time = new Date().time;
			send(obj);
		}
		
		
		//--- get a list of rooms ---
		public function getRooms():void {
			var obj:Object = new Object();
			obj.t = "gr";
			send(obj);
		}
		
		
		//--- join a group of people so you can send info to all of them ---
		public function joinRoom(roomName:String, pass:String="", roomType:String="", note:String=""):void {
			var obj:Object = new Object();
			obj.t = "jr";
			obj.room_name = roomName;
			obj.room_type = roomType;
			obj.pass = pass;
			if(note != "" && note != null) {
				obj.note = note;
			}
			send(obj);
		}
		
		
		//--- leave a group of people ---
		public function leaveRoom(roomName:String):void {
			var obj:Object = new Object();
			obj.t = "lr";
			obj.room_name = roomName;
			send(obj);
		}
		
		
		//--- set the pass for a room ---
		public function setRoomPass(roomName:String, pass:String):void {
			var obj:Object = new Object();
			obj.t = "rp";
			obj.room_name = roomName;
			obj.pass = pass;
			send(obj);
		}
		
		
		//--- send a message to everyone in said room ---
		public function sendToRoom(roomName:String, data:*, sendToSelf:Boolean=false):void {	
			var obj:Object = new Object();
			obj.t = "sr";
			obj.room_name = roomName;
			obj.send_to_self = sendToSelf;
			obj.data = data;
			send(obj);
		}
		
		
		//--- send a message to a specific user ---
		public function sendToUser(socketID:int, data:*):void {
			var obj:Object = new Object();
			obj.t = "su";
			obj.to_id = socketID;
			obj.data = data;
			send(obj);
		}
		
		
		//--- set remote variables ---
		public function setUserVars(variables:Object):void {
			_me.addVars(variables);
			manageVars(variables, "user", "set", "");
		}
		
		public function setRoomVars(variables:Object, roomName:String):void {
			manageVars(variables, "room", "set", roomName);
		}
		
		public function setGlobalVars(variables:Object):void {
			manageVars(variables, "global", "set", "");
		}
		
		
		//--- get remote variables ---
		public function getUserVars(varList:*, socketID:int):void {
			manageVars(varList, "user", "get", socketID.toString());
		}
		
		public function getRoomVars(varList:*, roomName:String):void {
			manageVars(varList, "room", "get", roomName);
		}
		
		public function getGlobalVars(varList:*):void {
			manageVars(varList, "global", "get", "");
		}
		
		
		//--- delete remote variables ---
		public function deleteUserVars(varList:*):void {
			manageVars(varList, "user", "delete", "");			
		}
		
		public function deleteRoomVars(varList:*, roomName:String):void {
			manageVars(varList, "room", "delete", roomName);
		}
		
		public function deleteGlobalVars(varList:*):void {
			manageVars(varList, "global", "delete", "");
		}
		
		
		//--- lock remote variables ---
		public function lockUserVars():void {
			manageVars("", "user", "lock", "");
		}
		
		public function lockRoomVars(roomName:String):void {
			manageVars("", "room", "lock", roomName);
		}
		
		public function lockGlobalVars():void {
			manageVars("", "global", "lock", "");
		}
		
		
		//--- unlock remote variables ---
		public function unlockUserVars():void {
			manageVars("", "user", "unlock", "");
		}
		
		public function unlockRoomVars(roomName:String):void {
			manageVars("", "room", "unlock", roomName);
		}
		
		public function unlockGlobalVars():void {
			manageVars("", "global", "unlock", "");
		}
		
		
		
		
		
		
	
		
		
		
		//--- start a ping interval to prevent the server from thinking we're dead ---
		protected override function connectHandler(e:Event):void {
			clearInterval(pingInterval);
			pingInterval = setInterval(sendPing, 30000);
			super.connectHandler(e);
		}
		
		
		//--- remove the ping interval if we're no longer connected ---
		protected override function closeHandler(e:Event):void {
			clearInterval(pingInterval);
			super.closeHandler(e);
		}
		
		
		
		//--- manage remote variables ---
		private function manageVars(variables:*, location:String, action:String, id:String):void {
			var obj:Object = new Object();
			obj.t = "mv";
			obj.user_vars = variables; 
			obj.location = location;
			obj.action = action;
			obj.id = id;
			send(obj);
		}
		
		
		
		//--- listeners ---
		private function initListeners():void {
			addEventListener(BlossomEvent.RECEIVE_SOCKET_ID, receiveSocketIDHandler, false, 0, true);
			addEventListener(BlossomEvent.RECEIVE_MESSAGE, receiveMessageHandler, false, 0, true);
			addEventListener(BlossomEvent.RECEIVE_USER_VARS, receiveUserVarsHandler, false, 0, true);
			addEventListener(BlossomEvent.RECEIVE_ROOM_VARS, receiveRoomVarsHandler, false, 0, true);
			addEventListener(BlossomEvent.RECEIVE_VERSION, receiveVersionHandler, false, 0, true);
			addEventListener(BlossomEvent.RECEIVE_USERS, receiveUsersHandler, false, 0, true);
			addEventListener(BlossomEvent.USER_JOIN_ROOM, userJoinRoomHandler, false, 0, true);
			addEventListener(BlossomEvent.USER_LEAVE_ROOM, userLeaveRoomHandler, false, 0, true);
			addEventListener(BlossomEvent.LOGIN_SUCCESS, loginSuccessHandler, false, 0, true);
			
			addEventListener(BlossomEvent.PING, receivePing, false, 0, true);
		}
		
		protected function receiveSocketIDHandler(be:BlossomEvent):void {
			_me = new BlossomUser(this, be.socketID, 0, null, false, new Object());
			
			this._socketID = be.socketID;
			var blossomEvent:BlossomEvent = new BlossomEvent(BlossomEvent.READY, be.raw);
			dispatchEvent(blossomEvent);
		}
		
		private function receiveMessageHandler(be:BlossomEvent):void {
			if(be.roomName != null){
				dispatchEvent(new BlossomEvent(BlossomEvent.RECEIVE_MESSAGE + be.roomName, be.raw));
			}
			if(be.socketID != 0){
				dispatchEvent(new BlossomEvent(BlossomEvent.RECEIVE_MESSAGE + be.socketID, be.raw));
			}
		}
		
		private function receiveVersionHandler(be:BlossomEvent):void {
			if(be.version > version) {
				var obj:Object = new Object();
				obj.error = "Platform Racing 3 has been updated! Please refresh this page to try to load the newer version.";
				dispatchEvent(new BlossomEvent(BlossomEvent.ERROR, obj));
				//remove();
			}
		}
		
		private function receiveUsersHandler(be:BlossomEvent):void {
			var users:Array = be.userList;
			var i:int;
			var len:int = users.length;
			var userObj:Object;
			var userRaw:Object;

			for(i=0; i<len; i++) {
				userObj = users[i];
				
				userRaw = new Object();
				userRaw.type = BlossomEvent.USER_JOIN_ROOM;
				userRaw.socketID = userObj.socketID;
				userRaw.userID = userObj.userID;
				userRaw.moderator = userObj.moderator;
				userRaw.userName = userObj.userName;
				userRaw.vars = userObj.vars;
				userRaw.roomName = be.roomName;
				
				dispatchEvent(new BlossomEvent(BlossomEvent.USER_JOIN_ROOM, userRaw));
			}
		}
		
		private function receiveUserVarsHandler(be:BlossomEvent):void {
			dispatchEvent(new BlossomEvent(BlossomEvent.RECEIVE_USER_VARS + be.socketID, be.raw));
		}
		
		private function receiveRoomVarsHandler(be:BlossomEvent):void {
			dispatchEvent(new BlossomEvent(BlossomEvent.RECEIVE_ROOM_VARS + be.roomName, be.raw));
		}
		
		private function userJoinRoomHandler(be:BlossomEvent):void {
			dispatchEvent(new BlossomEvent(BlossomEvent.USER_JOIN_ROOM + be.roomName, be.raw));
		}
		
		private function userLeaveRoomHandler(be:BlossomEvent):void {
			dispatchEvent(new BlossomEvent(BlossomEvent.USER_LEAVE_ROOM + be.roomName, be.raw));
		}
		
		private function errorHandler(be:BlossomEvent):void {
			trace("Error: "+be.error);
			if(be.roomName != null){
				dispatchEvent(new BlossomEvent(BlossomEvent.ERROR + be.roomName, be.raw));
			}
		}
		
		private function loginSuccessHandler(be:BlossomEvent):void {
			_me = new BlossomUser(this, socketID, be.userID, be.userName, be.moderator, be.vars);
		}
		
		
		
		private function receivePing(be:BlossomEvent):void {
			_pingTime = (new Date().time) - be.time;
			if(baseServerTime == 0) {
				baseServerTime = be.raw.server_time;
				baseLocalTime = getSeconds();
			}
			else {
				var serverElapsed:Number = be.raw.server_time - baseServerTime;
				var localElapsed:Number = getSeconds() - baseLocalTime;
				var timeError:Number = serverElapsed - localElapsed;
				if(Math.abs(timeError) > 10) {
					var obj:Object = new Object();
					obj.error = "Remote Time Error. (Are you located near a black hole?)";
					dispatchEvent(new BlossomEvent(BlossomEvent.ERROR, obj));
				}
			}
		}
		
		
		private function checkTime():void {
			var curTime:Number = getMS();
			if(curTime < lastTime) {
				var obj:Object = new Object();
				obj.error = "Local Time Error. (Are you traveling faster than the speed of light?)";
				dispatchEvent(new BlossomEvent(BlossomEvent.ERROR, obj));
			}
			lastTime = curTime;
		}
		
		
		private function getMS():Number {
			var date:Date = new Date();
			var ms:Number = date.time;
			return(ms);
		}
		
		
		private function getSeconds():Number {
			var ms:Number = getMS();
			var seconds:Number = Math.round(ms / 1000);
			return(seconds);
		}
		

		//--- clean up ----
		public override function remove():void {
			removeEventListener(BlossomEvent.RECEIVE_SOCKET_ID, receiveSocketIDHandler);
			removeEventListener(BlossomEvent.RECEIVE_MESSAGE, receiveMessageHandler);
			removeEventListener(BlossomEvent.RECEIVE_USER_VARS, receiveUserVarsHandler);
			removeEventListener(BlossomEvent.RECEIVE_ROOM_VARS, receiveRoomVarsHandler);
			removeEventListener(BlossomEvent.RECEIVE_VERSION, receiveVersionHandler);
			removeEventListener(BlossomEvent.RECEIVE_USERS, receiveUsersHandler);
			removeEventListener(BlossomEvent.USER_JOIN_ROOM, userJoinRoomHandler);
			removeEventListener(BlossomEvent.USER_LEAVE_ROOM, userLeaveRoomHandler);
			removeEventListener(BlossomEvent.PING, receivePing);
			removeEventListener(BlossomEvent.LOGIN_SUCCESS, loginSuccessHandler);
			
			if(_me != null) {
				_me.remove();
				_me = null;
			}
			
			clearInterval(pingInterval);
			clearInterval(timeInterval);
			super.remove();
		}
	}
}