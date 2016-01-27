# How to configure phpivr #

Suppose that we need to do interactive voice responses, which briefly say in which company received a call and then redirect the call to the Sales department (1001) if you press 1, Corporate clients dep. (1002) by pressing 2. Well, as usual - to connect with the Secretary (1000) if you press 0 or wait while the message ends. Also we provide the case when from the terminal will introduce dtmf not covered by the menu – play message “to hear the menu again” then run it again.

To achieve this simple task, we need:
  1. sound file with greeting - welcome.gsm
  1. a sound file with describing the available menu (it can be combined with a greeting) - main-menu.gsm
  1. a sound file with a request to hear the menu again and make your choice - please-make-your-choice.gsm

Put the recorded files into the folder /var/lib/asterisk/sounds/ivr and set them corresponding rights

in phpivr.conf write
```
{
       "common" : { 
               "name" : "Main menu of my company IVR" 
               ,"options" : "say=ivr/welcome|say=ivr/main-menu,prompt,loop=1|transfer=1000" 
               ,"inputs" : { 
                       "0" : "transfer=1000" 
                       ,"1" : "transfer=1001" 
                       ,"2" : "transfer=1002" 
               } 
               ,"invalidinput_act" : "say=ivr/please-make-your-choice|menu=common" 
       } 
}
```

From here it should be noted that while playing a welcome message, pressing any key will cause it to completion, and immediately will playing main-menu. Also you can omit the option loop=1 because the count of playback by default is 1, but It written by me for a better understanding of the written material.

That's all the settings. Enjoy.

## See Also ##
  * [How to authorize user using phpivr](HowToAuthorizeUserUsingPhpivr.md)