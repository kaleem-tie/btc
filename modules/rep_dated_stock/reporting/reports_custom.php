<?php

global $reports, $dim;
/*			
$reports->addReport(RC_INVENTORY,"_dated_stock",_('Dated Stock Sheet'),
	array(	_('Date') => 'DATE',
			_('Inventory Category') => 'CATEGORIES',
			_('Location') => 'LOCATIONS',
			_('Comments') => 'TEXTBOX',
			_('Destination') => 'DESTINATION'));	
*/


$reports->addReport(RC_INVENTORY,"_dated_stock",_('Dated Stock Sheet'),
	array(	_('Date') => 'DATE',
			_('Supplier') => 'SUPPLIERS_NO_FILTER',
			_('Inventory Category') => 'CATEGORIES',
			_('Sub Category') => 'ONCHANGESTOCKSUBCATEGORY3',
			_('Location') => 'LOCATIONS',
			_('Comments') => 'TEXTBOX',
			_('Destination') => 'DESTINATION'),'','SA_INVENTORY_VALUATION_REPORT');				
