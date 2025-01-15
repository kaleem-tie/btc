<?php
/**********************************************************************
    Copyright (C) FrontAccounting, LLC.
	Released under the terms of the GNU General Public License, GPL,
	as published by the Free Software Foundation, either version 3
	of the License, or (at your option) any later version.
    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
    See the License here <http://www.gnu.org/licenses/gpl-3.0.html>.
***********************************************************************/
class inventory_app extends application
{
	function __construct()
	{
		parent::__construct("stock", _($this->help_context = "&Items and Inventory"));

		$this->add_module(_("Transactions"));
		
		$this->add_lapp_function(0, _("Inventory &Adjustments"),
			"inventory/adjustments.php?NewAdjustment=1", 'SA_INVENTORYADJUSTMENT', MENU_TRANSACTION);
		
		$this->add_lapp_function(0, _("Material &Indent Request"),
			"inventory/material_indent_request.php?NewIndent=1", 'SA_MATERIAL_INDENT', MENU_TRANSACTION);
		
		$this->add_lapp_function(0, _("Inventory Location &Transfers"),
			"inventory/transfers.php?NewTransfer=1", 'SA_LOCATIONTRANSFER', MENU_TRANSACTION);
		
        $this->add_lapp_function(0, _("Inventory Receive Against Indent"),
			"inventory/receive_indent.php?NewReceive=1", 'SA_RECEIVE_INDENT', MENU_TRANSACTION);		
			
		

		$this->add_module(_("Inquiries and Reports"));
		
		$this->add_lapp_function(1, _("Inventory Transactions &Inquiry"),
			"inventory/inquiry/transactions_inquiry.php?", 'SA_ITEMSTRANSINQ', MENU_INQUIRY);
			
		$this->add_lapp_function(1, _("Inventory Item &Movements"),
			"inventory/inquiry/stock_movements.php?", 'SA_ITEMSTRANSVIEW', MENU_INQUIRY);
		$this->add_lapp_function(1, _("Inventory Item &Status"),
			"inventory/inquiry/stock_status.php?", 'SA_ITEMSSTATVIEW', MENU_INQUIRY);
        
		$this->add_lapp_function(1, _("Inventory Adjustment Enquiry"),
			"inventory/inquiry/inv_adjustment_inquiry.php?", 'SA_INVENTORY_ADJUSTMENT_INQUIRY', MENU_INQUIRY);
			
       
        $this->add_lapp_function(1, _("Material Indent Request Inquiry"),
			"inventory/inquiry/material_indent_request_inquiry.php?", 'SA_MATERIAL_INDENT_INQUIRY', MENU_INQUIRY);			
			
		$this->add_lapp_function(1, _("Inventory Transfer Reports"),
			"inventory/inquiry/transfers_report.php?", 'SA_ITEMSTRANSVIEW', MENU_INQUIRY);	
	
			
		$this->add_rapp_function(1, _("Inventory &Reports"),
			"reporting/reports_main.php?Class=2", 'SA_ITEMSTRANSVIEW', MENU_REPORT);

		$this->add_module(_("Masters"));
		$this->add_lapp_function(2, _("&Items"),
			"inventory/manage/items.php?", 'SA_ITEM', MENU_ENTRY);
		$this->add_lapp_function(2, _("&Offers - Categories"),
			"inventory/manage/promotional_discounts.php?", 'SA_PROMO_DISC', MENU_MAINTENANCE);
		$this->add_lapp_function(2, _("&Offers - Suppliers"),
			"inventory/manage/promotional_discounts_brands.php?", 'SA_PROMO_DISC', MENU_MAINTENANCE);
		$this->add_lapp_function(2, _("&Offers - Items"),
			"inventory/manage/promotional_discounts_items.php?", 'SA_PROMO_DISC', MENU_MAINTENANCE);
		/* $this->add_lapp_function(2, _("Sales &Kits"),
			"inventory/manage/sales_kits.php?", 'SA_SALESKIT', MENU_MAINTENANCE); */
		$this->add_lapp_function(2, _("Item &Categories"),
			"inventory/manage/item_categories.php?", 'SA_ITEMCATEGORY', MENU_MAINTENANCE);
		$this->add_lapp_function(2, _("Item &Subcategories"),
			"inventory/manage/item_sub_categories.php?", 'SA_ITEM_SUB_CATEGORY', MENU_MAINTENANCE);
		$this->add_rapp_function(2, _("Inventory &Locations"),
			"inventory/manage/locations.php?", 'SA_INVENTORYLOCATION', MENU_MAINTENANCE);
		$this->add_rapp_function(2, _("&Units of Measure"),
			"inventory/manage/item_units.php?", 'SA_UOM', MENU_MAINTENANCE);
		$this->add_rapp_function(2, _("&Reorder Levels"),
			"inventory/reorder_level.php?", 'SA_REORDER', MENU_MAINTENANCE);

		$this->add_module(_("Pricing and Costs"));
		$this->add_lapp_function(3, _("Sales &Pricing"),
			"inventory/prices.php?", 'SA_SALESPRICE', MENU_MAINTENANCE);
		$this->add_lapp_function(3, _("Purchasing &Pricing"),
			"inventory/purchasing_data.php?", 'SA_PURCHASEPRICING', MENU_MAINTENANCE);
		$this->add_rapp_function(3, _("Standard &Costs"),
			"inventory/cost_update.php?", 'SA_STANDARDCOST', MENU_MAINTENANCE);

		$this->add_extensions();
	}
}


