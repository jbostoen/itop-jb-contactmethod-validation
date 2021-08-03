# jb-contactmethod-validation
Copyright (c) 2019-2021 Jeffrey Bostoen

[![License](https://img.shields.io/github/license/jbostoen/iTop-custom-extensions)](https://github.com/jbostoen/iTop-custom-extensions/blob/master/license.md)
[![Donate](https://img.shields.io/badge/Donate-PayPal-green.svg)](https://www.paypal.me/jbostoen)
🍻 ☕

Need assistance with iTop or one of its extensions?  
Need custom development?  
Please get in touch to discuss the terms: **jbostoen.itop@outlook.com**


## What?
Adds validation for certain contact methods (in the ContactMethod objects).
Currently only based on the situation in Belgium (length of phone numbers).

For now, this is specifically for Belgian phone numbers; but I'm open to pull requests for a more generic form of validation which may better support international needs.


## Requirements

iTop extensions
* [jb-contactmethod](https://github.com/jbostoen/itop-jb-contactmethod)


## Cookbook

PHP:
* make sure ContactMethod follows certain rules. Warning if necessary, strip unnecessary parts where needed

