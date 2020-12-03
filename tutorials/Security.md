# Securing Your Flash Game

### Hacking - The Never Ending Fun Times
Flash games (especially multiplayer flash games) are a pretty common and easy target for hackers. When someone does find a hole, it's important to be able to patch it. I recommend you plan on having a way to update your game without too much hassle, because people will never stop finding ways to cheat at or mess up your game. Use a service like [mochimedia](https://web.archive.org/web/20110918232044/http://www.mochimedia.com/) to keep a central version of your game, or create your own distribution method. The advantage of doing this is that you only need to update your game once, and the change is automatically distributed to every website the game is on.

### Packet Sniffers - Packet Encryption
Programs like [WPE Pro](https://web.archive.org/web/20090918212028/http://forum.cheatengine.org:80/viewtopic.php?t=108036) can intercept and change any data that your program sends or receives over the internet. Blossom Server encrypts all of the data that you send through it, but any http requests you make are still vulnerable. The solution is to either [encrypt](https://github.com/timkurvers/as3-crypto) or [make a hash](https://github.com/mikechambers/as3corelib/blob/master/src/com/adobe/crypto/MD5.as) of all of the data you send and receive.

### Decompilers - SWF Encryption
There are a bunch of decompilers available, and a lot of people have them. Anyone with a decompiler can open up your swf, and then do whatever they like with your code. This makes any packet encryption you use worthless, because people can extract the password that you're using to encrypt your data. The defense against this is to encrypt your game, and then make a loader that will download and decrypt the encrypted game so people can play it. You can do this yourself, or you can use [mochimedia](https://web.archive.org/web/20110918232044/http://www.mochimedia.com/)'s encryption service. It's still possible for people to manually decrypt your swf, but it's not the easiest thing to do.

### Persistent people who decompiled your SWF anyway - SWF Obfuscation
If someone manages to get past your swf encryption, then there is still the ever fun obfuscation technique. The idea is to mess up your swf as much as you can without actually breaking it. Trying to manually obfuscate is generally more trouble than it's worth, but luckily there are some programs around that are supposed to automagically obfuscate your swf for you. (I've never been able to get any of them to actually work, though.) Maybe you'll have better luck: [secureSWF](http://www.kindi.com/), [swfEncrypt](https://www.amayeta.com/software/swfencrypt/), [swfGuard](https://web.archive.org/web/20111205003031/http://simulat.com/swfguard/) *(defunct)*

[Here's another interesting trick.](https://web.archive.org/web/20110911174833/http://www.veryinteractivepeople.com/?p=67)

### Memory Editors - Variable Encryption
Even if you have the most impenetrable security setup ever, your game is still vulnerable to [Cheat Engine](https://cheatengine.org/). Cheat Engine can change variables in your game as it's running, along with some other rather unfortunate stuff. You can protect your variables by [encrypting them](https://web.archive.org/web/20111121215357/http://mikegrundvig.blogspot.com/2007/05/encrypting-variables-in-memory-to.html), but some of the other things Cheat Engine can do are tougher to beat.

[See Cheat Engine in Action](https://www.youtube.com/watch?v=Mj1bnmWAadc) *(vid unavailable as of Dec. 3, 2020 -- replaced)*

### Other Reading

[Tutorial: Protecting Your Flash Game](https://web.archive.org/web/20080801030214/http://mochiland.com/articles/tutorial-protecting-your-flash-game)\
[Following The White Rabbit](https://web.archive.org/web/20090531201641/http://www.communities.hp.com/securitysoftware/blogs/rafal/archive/2009/04/20/raising-the-bar-flash-encryption-obfuscation.aspx)

<hr />

*This content was originally uploaded to blossom-server.com and retrieved from [the web archive](https://web.archive.org/web/20110829040714/http://blossom-server.com/tutorials/security.php) on Dec. 3, 2020.*
