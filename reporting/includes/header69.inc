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

/**
	Document blueprint use following parameters set in doctext.inc:

	$Addr1,	$Addr2 - address info
	$Payment_Terms - payment terms line
	$Footer - footer texts
	$this->company - company info
	$this->title - report title
	$this->formData - some other info
***/


//include 'phpqrcode/qrlib.php';

		$this->row = $this->pageHeight - $this->topMargin;

		$upper = $this->row - 1 * $this->lineHeight;
		$lower = $this->bottomMargin + 8.3 * $this->lineHeight;
		$iline1 = $upper + 3.0 * $this->lineHeight;
	    $iline2 = $iline1 - 10.5 * $this->lineHeight;
		
		$iline3 = $iline2 - 2 * $this->lineHeight; 
		$iline4 = $iline3 - 3.5 * $this->lineHeight;
		$iline5 = $iline4 - 2 * $this->lineHeight;
		$iline6 = $iline5 - 2.5 * $this->lineHeight;
		$iline7 = $lower;
		
		$iline8 = $upper - 5.5 * $this->lineHeight;
		$iline9 = $upper - 10.3 * $this->lineHeight;
		
		$iline10 = $upper - 18.5 * $this->lineHeight;
		
		$iline11 = $upper - 5.5 * $this->lineHeight;
		
		
		
		$right = $this->pageWidth - $this->rightMargin;
		$width = ($right - $this->leftMargin);
		$icol = $this->pageWidth / 2;
		$ccol = $this->cols[0] + 4;
		$c2col = $ccol + 60;
		$ccol2 = $icol / 2;
		$mcol = $icol + 8;
		$mcol2 = $this->pageWidth - $ccol2;
		$cols = count($this->cols);
		
		//$this->SetDrawColor(205, 205, 205);
		//$this->Line($iline8, 1);
		//$this->SetDrawColor(128, 128, 128);
		//$this->Line($iline8);
		
		
		
		$this->SetDrawColor(205, 205, 205);
		$this->Line($iline11, 1);
		$this->SetDrawColor(128, 128, 128);
		$this->Line($iline11);
		
		
	   
		$this->rectangle($this->leftMargin, $iline5, $right - $this->leftMargin, $iline5 - 
		$iline6, "F", null, array(255,255,255));   
		$this->Line($iline5);
		$this->Line($iline6);
		$this->Line($iline7);
		
		
		//$this->LineTo($this->leftMargin, $iline2 ,$this->leftMargin, $iline4);
		$col = $this->leftMargin;
		for ($i = 0; $i < 5; $i++)
		{
			//$this->LineTo($col += $width, $iline2,$col, $iline4);
		}
		//$this->LineTo($right, $iline2 ,$right, $iline4);
		//$this->LineTo($this->leftMargin, $iline5 ,$this->leftMargin, $iline7);
		if ($this->l['a_meta_dir'] == 'rtl') // avoid line overwrite in rtl language
			$this->LineTo($this->cols[$cols - 2], $iline5 ,$this->cols[$cols - 2], $iline7);
		else	
			//$this->LineTo($this->cols[$cols - 2] + 4, $iline5 ,$this->cols[$cols - 2] + 4, $iline7);
		//$this->LineTo($right, $iline5 ,$right, $iline7);
		$this->NewLine(2);
		
		
		//$this->MultiCell(540,30,'','LRBT','C',50,200,35,233);// Full border
		
		
		
		$logo = company_path() . "/images/" . $this->company['coy_logo'];
		// Document title
		$this->SetTextColor(190, 190, 190);
		$this->TextWrap($mcol, $this->row, $this->pageWidth - $this->rightMargin - $mcol - 20, '', 'right');
		$this->SetTextColor(0, 0, 0);
		
		
		
		$adrline = $this->row;
		$this->NewLine(3);
		$this->SetFont('helvetica', 'B', 9);
		$this->Text($ccol,  _("VATIN : ").$this->company['gst_no']);
		$this->Text($mcol+150,  _("Tax Card No.: ").$this->company['tax_id_no']);
		$this->SetFont('', '', 0);
		
		$this->NewLine();
		$this->fontSize += 6;
		$this->SetFont('helvetica', 'B', 12);
		$this->Text($mcol - 80,  _("TAX CREDIT NOTE"));
		$this->SetFont('', '', 0);
		$this->fontSize -= 6;
		$this->NewLine();
		
	   
		$this->row = $adrline;
		$this->NewLine();
		
		
		$temp = $this->row = $this->row - $this->lineHeight - 45;
		$this->SetFont('helvetica', 'B', 6);
		$this->Text($ccol, _("Customer : ").$this->formData['cust_code'] , $icol);
		
	

		$this->NewLine();
		$this->fontSize += 3;
		if(strlen($Addr1['name'])<=34){
		$this->Text($ccol, substr($Addr1['name'],0,34), $icol);
		}else{
		$this->Text($ccol, substr($Addr1['name'],0,34), $icol);
		$this->NewLine();
		$this->Text($ccol, substr($Addr1['name'],34,70), $icol);	
		}	
		$this->fontSize -= 3;
	    $this->SetFont('', '', 0);
		
		$this->NewLine();
		//$this->TextWrapLines($ccol, $icol - $ccol, $Addr1['address']);
		$this->TextWrapLines($ccol, $icol - $ccol, $this->formData['delivery_address']);
		//$this->NewLine();
		if($this->formData['phone']!='')
        $this->Text($ccol, _("Tel: ") . ' ' . $this->formData['phone']."  "._("Fax: ") . ' ' . $this->formData['fax']);
		
		
		
		$this->NewLine();
		$this->SetFont('helvetica', 'B', 9);
		$this->Text($ccol, _("Area :"));
		$this->SetFont('', '', 0);
		$this->Text($ccol+30, get_cust_branch_area_name($this->formData['area']));
		
		
		
		
		$this->NewLine();
		$this->SetFont('helvetica', 'B', 9);
		$this->Text($ccol, _("Cust VATIN : "));
		$this->SetFont('', '', 0);
		$this->Text($ccol+60, $this->formData['tax_id'], $icol);
		
	
		
		 
        $this->row = $temp + 11;
		
		$this->NewLine(1);
		$this->SetFont('helvetica', 'B', 9);
		$this->Text($mcol+30,  _("Invoice No."));
		$this->Text($mcol + 120, _(":"));
		$this->Text($mcol + 130, $this->formData['document_number']);
		$this->SetFont('', '', 0);
		
		$this->NewLine();
		
		$this->SetFont('helvetica', 'B', 9);
		$this->Text($mcol + 30 , _("Invoice Date"));
	    $this->Text($mcol + 120, _(":"));
		$this->SetFont('', '', 0);
	    $this->Text($mcol + 130 , sql2date($this->formData['document_date']));
		
		$this->NewLine();
		
		
		$this->SetFont('helvetica', 'B', 9);
		$this->Text($mcol + 30,  _("Salesman"));
		$this->Text($mcol + 120, _(":"));
		$this->SetFont('', '', 0);
		$this->Text($mcol+130, get_salesman_name($this->formData['sales_person_id']));
		
		$sales_order = get_sales_order_header($this->formData['order_'], ST_SALESORDER);
		if($sales_order['from_stk_loc']!="")
		{
		$this->SetFont('helvetica', 'B', 9);
		$this->Text($mcol+200 ,  _("Location"));
		$this->Text($mcol + 238, _(":"));
		$this->SetFont('', '', 0);
		$this->Text($mcol + 242, $sales_order['from_stk_loc']);
		}
		$this->NewLine();
		
		
		$this->SetFont('helvetica', 'B', 9);
		$this->Text($mcol+30 ,  _("Currency"));
		$this->Text($mcol + 120, _(":"));
		$this->SetFont('', '', 0);
		$this->Text($mcol + 130, $this->formData['curr_code']);
		
		
		$this->NewLine();
		
		$this->SetFont('helvetica', 'B', 9);
		$this->Text($mcol+30 ,  _("LPO No."));
		$this->Text($mcol + 120, _(":"));
		$this->SetFont('', '', 0);
		$this->Text($mcol + 130, $this->formData['lpo_no']);
		if($sales_order['from_stk_loc']!="")
		{
		$this->SetFont('helvetica', 'B', 9);
		$this->Text($mcol+200 ,  _("Dtd"));
		$this->Text($mcol + 215, _(":"));
		$this->SetFont('', '', 0);
		if($this->formData['lpo_date']!='0000-00-00')
		$this->Text($mcol + 220, sql2date($this->formData['lpo_date']));
		}
		$this->NewLine();
		
		
		
		$this->SetFont('helvetica', 'B', 9);
	    $this->Text($mcol + 30, _("Page "));
		$this->Text($mcol + 120, _(":"));
		$this->SetFont('', '', 0);
		$this->Text($mcol+130, $this->pageNumber);
		
		
		$this->row = $temp + 22;
		//$this->NewLine(2);
	
		$this->row = $iline1 - $this->lineHeight;
		

		
		// Auxiliary document information
		$col = $this->leftMargin;
		foreach($aux_info as $info_header => $info_content)
		{

			$this->row = $iline2 - $this->lineHeight - 1;
			$this->TextWrap($col, $this->row, $width, $info_header, 'C');
			$this->row = $iline3 - $this->lineHeight - 1;
			$this->TextWrap($col, $this->row, $width, $info_content, 'C');
			$col += $width;
		}
		
		 
		

		// Line headers
		$this->row = $iline5 - $this->lineHeight - 1;
		//$this->Font('bold');
		$this->SetFont('helvetica', 'B', 9);
		$count = count($this->headers);
		$this->cols[$count] = $right - 3;
		for ($i = 0; $i < $count; $i++)
			$this->TextCol($i, $i + 1, $this->headers[$i], -2);
			$this->NewLine(1);
			

		$count = count($this->headers2);
		$this->cols[$count] = $right - 3;
		for ($i = 0; $i < $count; $i++)
			$this->TextCol($i, $i + 1, $this->headers2[$i], -2);
      $this->SetFont('', '', 0);
	
		
     

		// Footer
		$this->Font('italic');
		$this->row = $iline7 - $this->lineHeight - 6;

		foreach ($Footer as $line => $txt)
		{
			if (!is_numeric($line))	// title => link
			{
				$this->fontSize -= 2;
				$this->TextWrap($ccol, $this->row, $right - $ccol, $line, 'C');
				$this->row -= $this->lineHeight;
				$this->SetTextColor(0, 0, 255);
				$this->TextWrap($ccol, $this->row, $right - $ccol, $txt, 'C');
				$this->SetTextColor(0, 0, 0);
				$this->addLink($txt, $ccol, $this->row, $this->pageWidth - $this->rightMargin, $this->row + $this->lineHeight);
				$this->fontSize += 2;
			}
			else
				$this->TextWrap($ccol, $this->row, $right - $ccol, $txt, 'C');
			$this->row -= $this->lineHeight;

		}
		
		$this->Font();
	    
		
		$this->Font();
		$temp = $iline6 - $this->lineHeight - 2;

