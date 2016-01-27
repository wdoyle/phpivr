# How to authorize user using phpivr #

Here is example of “how to authorize user" to connect directly to certain internal phone numbers in your company.

By default user can connect directly only to 1000. If user will input dtmf 0 - ivr will prompt user input  6-digits pin number.
After that ivr will redirect call into "extended menu" or hangup.
If dtmf sequence has not inputed in “auth” menu then return into "common" menu.

```
 {
     "common" : {
             "name" : "Main menu of my IVR"
             ,"options" : "say=ivr/main-menu,prompt,loop=2|say=ivr/goodby|exit"
             ,"inputs" : {
                     "0" : "menu=auth"
                     ,"1" : "transfer=1000"
             }
             ,"invalidinput_act" : "say=ivr/please-make-your-choice|menu=common"
     }
     "auth" : {
             "name" : "Authorize user"
             ,"options" : "say=ivr/input-your-pin-number,prompt=6|menu=common"
             ,"inputs" : {
                     "506812" : "menu=extended menu"
             }
             ,"invalidinput_act" : "exit"
     }
     "extended menu" : {
             "name" : "Extended menu"
             ,"options" : "say=ivr/make-your-choise,prompt,loop=2|exit"
             ,"inputs" : {
                     "0" : "menu=auth"
                     ,"1" : "transfer=1001"
                     ,"2" : "transfer=1002"
                     ,"3" : "transfer=1003"
                     ,"4" : "transfer=1004"
             }
             ,"invalidinput_act" : "exit"
     }
}
```

## See also ##
  * [How to configure phpivr](HowToConfigurePhpivr.md)