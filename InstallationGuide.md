# Installation guide #

1. Copy folder ivr into astagidir (/var/lib/asterisk/agi-bin/)
```
$ cp -rv ./ivr /var/lib/asterisk/agi-bin/
```
2. Create symlink on IVR.php
```
$ ln -s /var/lib/asterisk/agi-bin/ivr/IVR.php /var/lib/asterisk/agi-bin/IVR
```
3. Copy configuration file phpivr.conf and phpivr\_extensions.conf into /etc/asterisk/
```
$ cp phpivr.conf phpivr_extensions.conf /etc/asterisk/
```
4. Edit configuration file. By default IVR will run menu named "common".
> Parameter `options' - required minimum to play "welcome" message.

5. For use Default settings copy folder ./sounds/ivr ( from demosounds.tar.bz2) into asterisk sounds dir
```
$ cp ./sounds/ivr /var/lib/asterisk/sounds
```
6. Add these lines into /etc/asterisk/extensions.conf
```
; IVR menu
#include "phpivr_extensions.conf"
```

then from asterisk CLI type following
```
asterisk*CLI> dialplan reload
```
7. Change folders owner to asterisk user
```
$ sudo chown astreisk.asterisk /var/lib/astreisk/agi-bin/ivr /var/lib/asterisk/sounds/ivr
```
8. Call to SIP/7777 number and test IVR. By default 0, 2 and 1 menus (dtmf inputs) are available.
  1. - jumps to another menu, 0 - exit from menu, 2 - playing additional info. Other dtmf - exit.


## See Also ##
  * [How to configure phpivr](HowToConfigurePhpivr.md)
  * [How to authorize user using phpivr](HowToAuthorizeUserUsingPhpivr.md)