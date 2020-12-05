# BlossomRoom

Package: `com.jiggmin.blossomSocket`<br />
Class: `public class BlossomRoom`<br />
Inheritance: `BlossomRoom`<br />

### Public Properties
All public properties are read-only.

`roomName` : `String`\
The name of this room.

`userArray` : `Array` \
An array of users that are currently members of this room.

`vars` : `Object`\
The most recently retrieved variables from the room.

### Public Methods

`BlossomRoom(socket:BlossomSocket, roomName:String, pass:String="") :`\
Join a room, and automatically keep track of who else is in it.

```as3
var socket:BlossomSocket = new BlossomSocket("208.78.96.138", 1192, "NmojVTk3NFVWNStRRDRrNA==");
var room:BlossomRoom = new BlossomRoom(socket, "Uber Room");
```

`sendToRoom(data:*, sendToSelf:Boolean=true, type:String=null) : void`\
Send data to the members of this room. If `sendToSelf` is true, the data will be sent back to you as well. If `type` is not null, an event of that type will be dispatched from this room when the message is received.

```as3
var socket:BlossomSocket = new BlossomSocket("208.78.96.138", 1192, "NmojVTk3NFVWNStRRDRrNA==");
var room:BlossomRoom = new BlossomRoom(socket, "Uber Room");
var customEvent:String = "throwPandas"
var data:* = "3 Pandas have been thrown.";

room.sendToRoom(data, true, customEvent);

room.addEventListener(customEvent, customEventListener, false, 0, true);

private function customEventListener(be:BlossomEvent):void {
	var data:* = be.data;
	trace("Received data: " + data);
}
```

`setPass(pass:String) : void`\
Set a password for this room. Other users will not be able to join this room without the correct password. The password can not be changed once it has been set.

```as3
var socket:BlossomSocket = new BlossomSocket("208.78.96.138", 1192, "NmojVTk3NFVWNStRRDRrNA==");
var room:BlossomRoom = new BlossomRoom(socket, "Secure Fortress");

room.setPass("1234");
```

`getVars(varList:*) : void`\
Retrieve this room's variables that are stored on the remote server.

```as3
var socket:BlossomSocket = new BlossomSocket("208.78.96.138", 1192, "NmojVTk3NFVWNStRRDRrNA==");
var room:BlossomRoom = new BlossomRoom(socket, "School");
var varList:* = new Array("classes", "lunch");

room.getVars(varList);

room.addEventListener(BlossomEvent.RECEIVE_ROOM_VARS, receiveRoomVarsHandler, false, 0, true);

private function receiveRoomVars(be:BlossomEvent):void {
	var vars:Object = be.vars;
	trace(vars.classes, vars.lunch);
}
```

`setVars(vars:Object) : void`\
Store variables on the remote server.

```as3
var socket:BlossomSocket = new BlossomSocket("208.78.96.138", 1192, "NmojVTk3NFVWNStRRDRrNA==");
var room:BlossomRoom = new BlossomRoom(socket, "School");

var vars:Object = new Object();
vars.classes = new Array("Math", "Science", "History");
vars.lunch = "Meaty Suprise!";

room.setVars(vars);
```

`deleteVars(varList:*) : void`\
Delete variables from the remote server.

```as3
var socket:BlossomSocket = new BlossomSocket("208.78.96.138", 1192, "NmojVTk3NFVWNStRRDRrNA==");
var room:BlossomRoom = new BlossomRoom(socket, "School");
var varList:* = "*";

room.deleteVars(varList);
```

`lockVars() : void`\
Lock this room's variables. Users will be able to read them, but not edit them.

```
var socket:BlossomSocket = new BlossomSocket("208.78.96.138", 1192, "NmojVTk3NFVWNStRRDRrNA==");
var room:BlossomRoom = new BlossomRoom(socket, "School");

room.lockVars();
```

`unlockVars() : void`\
Unlock this room's variables.

```as3
var socket:BlossomSocket = new BlossomSocket("208.78.96.138", 1192, "NmojVTk3NFVWNStRRDRrNA==");
var room:BlossomRoom = new BlossomRoom(socket, "School");

room.unlockVars();
```

`leaveRoom() : void`\
Leave the room, and destroy this BlossomRoom object.

```as3
var socket:BlossomSocket = new BlossomSocket("208.78.96.138", 1192, "NmojVTk3NFVWNStRRDRrNA==");
var room:BlossomRoom = new BlossomRoom(socket, "Doomed");

room.leaveRoom();
room = null;
```

`remove() : void`\
Same as leaveRoom()

```as3
var socket:BlossomSocket = new BlossomSocket("208.78.96.138", 1192, "NmojVTk3NFVWNStRRDRrNA==");
var room:BlossomRoom = new BlossomRoom(socket, "Doomed");

room.remove();
room = null;
```
