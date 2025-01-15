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
$page_security = 'SA_CUSTBULKREP';
// ----------------------------------------------------------------
// $ Revision:	2.0 $
// Creator:	Joe Hunt
// date_:	2005-05-19
// Title:	Customer Details Listing
// ----------------------------------------------------------------
$path_to_root="..";

include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/data_checks.inc");
include_once($path_to_root . "/gl/includes/gl_db.inc");

//----------------------------------------------------------------------------------------------------

print_customer_details_listing();

function get_customer_details_for_report($area=0, $salesid=0)
{
	$sql = "SELECT debtor.debtor_no,
			debtor.name,
			debtor.phone,
			debtor.curr_code,
			area.description,
			salesman.salesman_name
		FROM ".TB_PREF."debtors_master debtor
		INNER JOIN ".TB_PREF."cust_branch branch ON debtor.debtor_no=branch.debtor_no
		INNER JOIN ".TB_PREF."areas area ON branch.area = area.area_code
		INNER JOIN ".TB_PREF."salesman salesman	ON branch.salesman=salesman.salesman_code
		WHERE debtor.inactive = 0";
	if ($area != 0)
	{
		if ($salesid != 0)
			$sql .= " AND salesman.salesman_code=".db_escape($salesid)."
				AND area.area_code=".db_escape($area);
		else
			$sql .= " AND area.area_code=".db_escape($area);
	}
	elseif ($salesid != 0)
		$sql .= " AND salesman.salesman_code=".db_escape($salesid);
	$sql .= " ORDER BY description,
			salesman.salesman_name,
			debtor.debtor_no,
			branch.branch_code";

    return db_query($sql,"No transactions were returned");
}

//----------------------------------------------------------------------------------------------------

function print_customer_details_listing()
{
    global $path_to_root;

    $from = $_POST['PARAM_0'];
    $area = $_POST['PARAM_1'];
    $folk = $_POST['PARAM_2'];
  	$orientation = $_POST['PARAM_3'];
	$destination = $_POST['PARAM_4'];
	if ($destination)
		include_once($path_to_root . "/reporting/includes/excel_report.inc");
	else
		include_once($path_to_root . "/reporting/includes/pdf_report.inc");

	$orientation = ($orientation ? 'L' : 'P');
    $dec = 0;

	if ($area == ALL_NUMERIC)
		$area = 0;
	if ($folk == ALL_NUMERIC)
		$folk = 0;

	if ($area == 0)
		$sarea = _('All Areas');
	else
		$sarea = get_area_name($area);
	if ($folk == 0)
		$salesfolk = _('All Sales Folk');
	else
		$salesfolk = get_salesman_name($folk);
	
	$cols = array(0, 150, 250,350, 425, 515);

	$headers = array(_('Customer Name'), _('Phone'),_('Currency'),
		_('Area'),_('salesman'));

	$aligns = array('left',	'center','left','left','left');

    $params =   array( 	0 => '',
    				    1 => array('text' => _('Activity Since'), 	'from' => $from, 		'to' => ''),
    				    2 => array('text' => _('Sales Areas'), 		'from' => $sarea, 		'to' => ''),
    				    3 => array('text' => _('Sales Folk'), 		'from' => $salesfolk, 	'to' => ''));

    $rep = new FrontReport(_('Customer Contact Listing'), "CustomerDetailsListing", user_pagesize(), 9, $orientation);
    if ($orientation == 'L')
    	recalculate_cols($cols);

    $rep->Font();
    $rep->Info($params, $cols, $headers, $aligns);
    $rep->NewPage();

	$result = get_customer_details_for_report($area, $folk);

	$carea = '';
	$sman = '';
	while ($myrow=db_fetch($result))
	{
			$rep->TextCol(0, 1,	$myrow['name']);
			$rep->TextCol(1, 2,	$myrow['phone']);
			$rep->TextCol(2, 3,	$myrow['curr_code']);
			$rep->TextCol(3, 4,	$myrow['description']);
			$rep->TextCol(4, 5,	$myrow['salesman_name']);
			$rep->NewLine();
	}
    $rep->End();
}

