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
		$lower = $this->bottomMargin + 2 * $this->lineHeight;
		$iline1 = $upper + 3.0 * $this->lineHeight;
	    $iline2 = $iline1 - 10.5 * $this->lineHeight;
		
		$iline3 = $iline2 - 6.5 * $this->lineHeight; 
		$iline4 = $iline3 - 3.5 * $this->lineHeight;
		$iline5 = $iline4 - 2 * $this->lineHeight;
		$iline6 = $iline5 - 2.5 * $this->lineHeight;
		$iline7 = $lower;
		
		$iline8 = $upper - 5.5 * $this->lineHeight;
		$iline9 = $upper - 10.3 * $this->lineHeight;
		
		$iline10 = $upper - 18.5 * $this->lineHeight;
		
		$iline11 = $upper - 6.5 * $this->lineHeight;
		
		
		
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
		
		
		
		//$this->MultiCell(540,30,'','LRBT','C',50,200,35,233);// Full border
		
		
		
		$logo = company_path() . "/images/" . $this->company['coy_logo'];
		// Document title
		$this->SetTextColor(190, 190, 190);
		$this->TextWrap($mcol, $this->row, $this->pageWidth - $this->rightMargin - $mcol - 20, '', 'right');
		$this->SetTextColor(0, 0, 0);
		
		
		$this->fontSize += 4;
		$this->Font('bold');
		$this->Text($mcol-100, $this->company['coy_name']);
		$this->Font();
		$this->fontSize -= 4;
		$this->NewLine();
		$this->TextWrapLines($mcol-50, $this->rightMargin - $mcol - 50, $this->company['postal_address'], 'left', 0, 0, NULL, 1);
		$this->Text($mcol-100, _("Tel :"). $this->company['coy_no']);
		$this->Text($mcol-30, _(",Store :"). $this->company['phone']);
	    $this->Text($mcol+55, _(",Fax :"). $this->company['fax']);
		$this->NewLine();
		
		$this->Text($mcol-80, _("Email :"));
		$url = "mailto:" . $this->company['email'];
		$this->SetTextColor(0, 0, 255);
		$this->Text($mcol- 45, $this->company['email']);
		$this->SetTextColor(0, 0, 0);
		$this->addLink($url, $mcol-100, $this->row, $mcol - 100, $this->row + $this->lineHeight);
		$this->NewLine();
		$this->SetFont('helvetica', 'B', 9);
		$this->Text($mcol-30, _("VATIN :").$this->company['gst_no']);
		$this->SetFont('', '', 0);
		
		
		
		$adrline = $this->row;
		$this->NewLine(1);
		
		
		$this->NewLine();
		$this->fontSize += 6;
		$this->SetFont('helvetica', 'B', 12);
		$this->Text($mcol - 40,  _("QUOTATION"));
		$this->SetFont('', '', 0);
		$this->fontSize -= 6;
		$this->NewLine();
		
	   
		$this->row = $adrline;
		$this->NewLine();
		
		
		$temp = $this->row = $this->row - $this->lineHeight - 25;
		$this->SetFont('helvetica', 'B', 9);
		$this->Text($ccol, _("Quote No. : "), $icol);
		$this->SetFont('', '', 0);
		$this->Text($ccol + 60, $this->formData['document_number'], $icol);
		$this->NewLine();
		$this->SetFont('helvetica', 'B', 9);
		$this->Text($ccol, _("Customer Code: ") , $icol);
		$this->SetFont('', '', 0);
		$this->Text($ccol + 80, $this->formData['cust_code'], $icol);
		$this->NewLine();
		
		$this->SetFont('helvetica', 'B', 9);
		//$this->TextWrapLines($ccol, $icol - $ccol, $Addr1['name']);
		
		if($this->formData['cash_cust_name']!=''){
		$this->Text($ccol, $this->formData['cash_cust_name'], $icol);
		$this->NewLine();
		$this->Text($ccol, $this->formData['cash_cust_mobile'], $icol);
		$this->NewLine();
		}
		else{
		$this->TextWrapLines($ccol, $icol - $ccol, $Addr1['name']);
		}
		
		$this->SetFont('', '', 0);
		$this->TextWrapLines($ccol, $icol - $ccol, $this->formData['delivery_address']);
		if($this->formData['phone']!='')
        $this->Text($ccol, _("Tel: ") . ' ' . $this->formData['phone']."  "._("Fax: ") . ' ' . $this->formData['fax']);
		
		
		$this->NewLine();
		$this->SetFont('helvetica', 'B', 9);
		$this->Text($ccol, _("Cust VATIN : "));
		$this->SetFont('', '', 0);
		$this->Text($ccol+60, $this->formData['tax_id'], $icol);
		
		$this->NewLine(1.5);
		$this->SetFont('helvetica', 'B', 9);
		$this->Text($ccol, _("Dear Sir, "));
		$this->SetFont('', '', 0);
		$this->NewLine();
		
		$this->Text($ccol + 50, _("Thank you for the opportunity to quote. Please find the details furnished below. "));
		 
        $this->row = $temp + 11;
		
		$this->NewLine(1);
		$this->SetFont('helvetica', 'B', 9);
		$this->Text($mcol+30,  _("Quote Date"));
		$this->Text($mcol + 100, _(":"));
		$this->Text($mcol + 110,  sql2date($this->formData['document_date']));
		$this->SetFont('', '', 0);
		$this->NewLine();
		
		$this->SetFont('helvetica', 'B', 9);
		$this->Text($mcol + 30,  _("Salesman"));
		$this->Text($mcol + 100, _(":"));
		$this->SetFont('', '', 0);
		$this->Text($mcol+110, get_salesman_name($this->formData['sales_person_id']));
		$this->NewLine();
		
		
		$this->SetFont('helvetica', 'B', 9);
		$this->Text($mcol+30 ,  _("Currency"));
		$this->Text($mcol + 100, _(":"));
		$this->SetFont('', '', 0);
		$this->Text($mcol + 110, $this->formData['curr_code']);
		$this->NewLine();
		
		
		
		$this->SetFont('helvetica', 'B', 9);
		$this->Text($mcol+30 ,  _("Quote Valid Till"));
		$this->Text($mcol + 100, _(":"));
		$this->SetFont('', '', 0);
		if($this->formData['delivery_date']!='0000-00-00')
		$this->Text($mcol + 110, sql2date($this->formData['delivery_date']));
		$this->NewLine();
		
		
		
		$this->SetFont('helvetica', 'B', 9);
	    $this->Text($mcol + 30, _("Page "));
		$this->Text($mcol + 100, _(":"));
		$this->SetFont('', '', 0);
		$this->Text($mcol+110, $this->pageNumber);
		
		
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
			$this->NewLine();
			

		
		
     

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

