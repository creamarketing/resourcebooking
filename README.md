## Overview

Resource booking module for the [SilverStripe](http://silverstripe.org) project.  
Requires a [`silverstripe-installer`](http://github.com/silverstripe/silverstripe-installer) base project (branch 2.4) in which it should be installed as a normal module.

## Requirements
Requires the following modules from silverstripe:  
[`silverstripe-installer`](http://github.com/silverstripe/silverstripe-installer) (branch 2.4), use as base project  
[`sapphire`](http://github.com/silverstripe/sapphire) (branch 2.4)  
[`cms`](http://github.com/silverstripe/silverstripe-cms) (branch 2.4)  
[`pdfrendition`](http://github.com/nyeholt/silverstripe-pdfrendition)  

Requires the following modules from creamarketing:  
[`creamarketing`](http://github.com/creamarketing/creamarketing)  
[`dataobject_manager`](http://github.com/creamarketing/DataObjectManager)  
[`dialog_dataobject_manager`](http://github.com/creamarketing/DialogDataObjectManager)  

## Installation
First create a silverstripe project (for example via [`silverstripe-installer`](http://github.com/silverstripe/silverstripe-installer)).  
Configure a database as normal in `mysite/_config.php` and configure at least one allowed locale for `Translatable` (via `Translatable::set_allowed_locales()`).  
Then install the necessary modules (preferably by cloning the corresponding git repositories) and do a `/dev/build`.  

## License ##
	Copyright (C) 2011  Oy Creamarketing Ab

	This program is free software: you can redistribute it and/or modify
	it under the terms of the GNU Affero General Public License as
	published by the Free Software Foundation, either version 3 of the
	License, or (at your option) any later version.
	
	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU Affero General Public License for more details.
	
	You should have received a copy of the GNU Affero General Public License
	along with this program.  If not, see <http://www.gnu.org/licenses/>.
