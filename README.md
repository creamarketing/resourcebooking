## Overview

Resource booking module for the [SilverStripe](http://silverstripe.org) project.  
Requires a [`silverstripe-installer`](http://github.com/silverstripe/silverstripe-installer) base project (branch 2.4) in which it should be installed as a normal module.

Requires the following modules from silverstripe:  
[`silverstripe-installer`](http://github.com/silverstripe/silverstripe-installer) (branch 2.4), use as base project  
[`sapphire`](http://github.com/silverstripe/sapphire) (branch 2.4)  
[`cms`](http://github.com/silverstripe/cms) (branch 2.4)  
[`pdfrendition`](http://github.com/nyeholt/silverstripe-pdfrendition)  

Requires the following modules from creamarketing:  
[`creamarketing`](http://github.com/creamarketing/creamarketing)  
[`dataobject_manager`](http://github.com/creamarketing/DataObjectManager)  
[`dialog_dataobject_manager`](http://github.com/creamarketing/DialogDataObjectManager)  

## Installation
First create a silverstripe project (for example via [`silverstripe-installer`](http://github.com/silverstripe/silverstripe-installer)).  
Configure a database as normal in `mysite/_config.php` and configure at least one allowed locale for `Translatable` (via `Translatable::set_allowed_locales()`).  
Then install the necessary modules (preferably by cloning the corresponding git repositories) and do a `/dev/build`.  
