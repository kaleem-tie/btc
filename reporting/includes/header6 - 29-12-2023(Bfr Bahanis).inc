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
		$lower = $this->bottomMargin + 2.5 * $this->lineHeight;
		$iline1 = $upper + 3.0 * $this->lineHeight;
	    $iline2 = $iline1 - 10.5 * $this->lineHeight;
		
		$iline3 = $iline2 - 6.5 * $this->lineHeight; 
		$iline4 = $iline3 - 3.5 * $this->lineHeight;
		$iline5 = $iline4 - 2.5 * $this->lineHeight;
		$iline6 = $iline5 - 3.0 * $this->lineHeight;
		$iline7 = $lower;
		
		$iline8 = $upper - 5.5 * $this->lineHeight;
		$iline9 = $upper - 10.3 * $this->lineHeight;
		
		$iline10 = $upper - 18.5 * $this->lineHeight;
		
		$iline11 = $upper - 9 * $this->lineHeight;
		
		
		
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
		
		
		
		
	   
		$this->rectangle($this->leftMargin, $iline5, $right - $this->leftMargin, $iline5 - $iline6, "F", null, array(222, 231, 236));   
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
		$this->LineTo($this->leftMargin, $iline5 ,$this->leftMargin, $iline7);
		if ($this->l['a_meta_dir'] == 'rtl') // avoid line overwrite in rtl language
			$this->LineTo($this->cols[$cols - 2], $iline5 ,$this->cols[$cols - 2], $iline7);
		else	
			//$this->LineTo($this->cols[$cols - 2] + 4, $iline5 ,$this->cols[$cols - 2] + 4, $iline7);
		$this->LineTo($right, $iline5 ,$right, $iline7);
		$this->NewLine(2);
		
		
		
		
		
		//$logo = company_path() . "/images/victoria.jpg";
		$logo = company_path() . "/images/" . $this->company['coy_logo'];
		// Document title
		$this->SetTextColor(190, 190, 190);
		//$this->fontSize += 10;
		//$this->Font('bold');
		$this->TextWrap($mcol, $this->row, $this->pageWidth - $this->rightMargin - $mcol - 20, '', 'right');
		//$this->Font();
		//$this->fontSize -= 10;
		$this->SetTextColor(0, 0, 0);
		$adrline = $this->row;
		
		// Company Logo
		$this->fontSize += 4;
		$this->Font('bold');
		$this->Text($ccol, $this->company['coy_name'], $icol);
		$this->Font();
		$this->fontSize -= 4;
		$this->NewLine();
		
		
		 $this->Text($ccol, _("CR No. : ").$this->company['cr_no']); 
		 $this->Text($mcol + 210, _("التجاري السجل ر  ")); 
		 $this->Text($mcol + 200  , _(": ")); 
		 $this->Text($mcol + 130, $this->company['cr_no']); 
		 $this->NewLine();
		 
		  $this->Text($ccol, _("Tel : ").'+ '.$this->company['phone']);
		 //$this->Text($mcol + 115, _("التليفون المحمول : ").substr($this->company['phone'],1).'+');
		 $this->Text($mcol + 210, _("التليفون المحمول "));
		 $this->Text($mcol + 200  , _(": ")); 
		 $this->Text($mcol + 115, '+ '.$this->company['phone']);
         $this->NewLine();	
         
         
		$this->Text($ccol  , _("Mob :"). '+ '.$this->company['coy_no']); 
		$this->Text($mcol + 240  , _("النقال ")); 
		$this->Text($mcol + 200  , _(": ")); 
		 $this->Text($mcol + 115, '+ '.$this->company['coy_no']);
		 
		
		
		 $this->NewLine();
		 
		  if (file_exists($logo))
		{
			
			$this->AddImage($logo, $mcol - 50, $this->row, 0, 40);
			//$this->NewLine();
		}
		 
		 
		 $this->TextWrapLines($ccol, $icol, $this->company['postal_address']);
		 //$this->TextWrapLines($mcol, $icol-$mcol, EnglishToArabic($this->company['postal_address']));
		 
		  if (@$this->company['email'])
		{
			$this->Text($ccol, _("Email : "), $ccol);
			
			$email = $this->company['email'];

			$url = "mailto:" . $email;
			$this->SetTextColor(0, 0, 255);
			$this->Text($ccol+ 35, $email, $mcol);
			$this->SetTextColor(0, 0, 0);
			$this->addLink($url, $ccol+ 35, $this->row, $mcol, $this->row + $this->lineHeight);
			$this->NewLine();
		}
		
		
		 if($this->company['gst_no']!='')
		 $this->Text($ccol, _("VAT No : ").$this->company['gst_no']); 
		 $temp = $this->row = $this->row - $this->lineHeight - 25;
		

		$this->fontSize += 6;
		$this->SetFont('helvetica', 'B', 12);
		$this->Text($mcol - 50,$this->title );
		$this->fontSize -= 6;
		$this->NewLine();
		
		$this->MultiCell(200,96,'','LRBT','C',50,200,30,190);// Full border
		$this->MultiCell(160,96,'','LRBT','C',50,200,250,190);// Full border
		$this->MultiCell(160,96,'','LRBT','C',50,200,420,190);// Full border
		
	   
		$this->row = $adrline;
		$this->NewLine();
		$temp = $this->row = $this->row - $this->lineHeight - 115.5;
		$this->Font('bold');
		$this->Text($ccol-10, _("Customer Name & Details:"), $icol);
		$this->Font();
	

		$this->NewLine();
		
		if(strlen($Addr1['name'])<=34){
		$this->Text($ccol-10, substr($Addr1['name'],0,34), $icol);
		}else{
		$this->Text($ccol-10, substr($Addr1['name'],0,34), $icol);
		$this->NewLine();
		$this->Text($ccol-10, substr($Addr1['name'],34,70), $icol);	
		}	
	
		
		$this->NewLine();
		//$this->TextWrapLines($ccol, $icol - $ccol, $Addr1['address']);
		$this->TextWrapLines($ccol-10, $icol - $ccol, $this->formData['delivery_address']);
		//$this->NewLine();
		if($this->formData['phone']!='')
        $this->Text($ccol-10, _("Mobile Number: ") . ' ' . $this->formData['phone']);
		
		
		if($this->formData['tax_id']){
		$this->NewLine();
		$this->Text($ccol-10, _("VAT No : "));
		$this->Text($ccol+40, $this->formData['tax_id'], $icol);
		}
		
		if($this->formData['sales_person_id']!=0){
		$this->NewLine();
		$this->Text($ccol -10, _("Sales Person :"));
		$this->Text($ccol+60, get_salesman_name($this->formData['sales_person_id']));
		$this->NewLine();
		}
		
	
		//$this->Text($ccol,_("Salesman Code : "). $this->formData['salesman'], $icol);
		
		//Arabic
		$this->row = $temp + 90.5;
		$this->Text($mcol + 210, _(" 112 : رمز البريد"));
		$this->Text($mcol + 140, _(" ,  54 : ص. ب "));
		$this->NewLine();
		
		
		//$this->NewLine(2);
		$this->row = $temp + 78.5;
		 if (@$this->company['email'])
		{
			$this->Text($mcol + 215, _("البريد الإلكتروني "));
			$this->Text($mcol + 200, _(": "));
			$email = $this->company['email'];

			$url = "mailto:" . $email;
			$this->SetTextColor(0, 0, 255);
			$this->Text($mcol + 90, $email);
			$this->SetTextColor(0, 0, 0);
			$this->addLink($url, $mcol - 35, $this->row, $icol-$mcol, $this->row + $this->lineHeight);
			$this->NewLine();
		}
			 
		$this->row = $temp + 65.5;
		//$this->Text($mcol + 150, _("رقم الضريبة : ").$this->company['gst_no']);
         if($this->company['gst_no']!=''){		
		$this->Text($mcol + 230, _("رقم الضريبة ")); 
		$this->Text($mcol + 200, _(": "));
		$this->Text($mcol + 125, $this->company['gst_no']);
        }		
		 
        $this->row = $temp + 11;
		$this->NewLine(1);
	    $this->Font('bold');
		$this->Text($mcol - 52, $this->formData['document_name']);
		$this->Text($mcol + 15, _(":"));
		$this->Text($mcol + 22, $this->formData['document_number']);
		$this->Font();
	    $this->Text($mcol + 118 , _("Invoice Date"));
	    $this->Text($mcol + 190, _(":"));
	    $this->Text($mcol + 200 , sql2date($this->formData['document_date']));
		$this->NewLine();
		
		$this->row = $temp - 13;
		$sales_order = get_sales_order_header($this->formData['order_'], ST_SALESORDER);
		
		$this->Text($mcol - 52, _("SO No."));
		$this->Text($mcol + 15, _(":"));
		$this->Text($mcol + 22, $sales_order['reference']);
		//$this->Text($mcol - 46, $sales_order['customer_ref']);
		$this->NewLine();
		
		
		$this->Text($mcol - 52, _("DO No."));
		$this->Text($mcol + 15, _(":"));
		
		$deliveries = get_sales_parent_numbers(ST_SALESINVOICE, $this->formData['trans_no']);
		foreach($deliveries as $m => $delivery) {
		    $deliveries[$r] = get_invoice_sales_delivery_reference(ST_CUSTDELIVERY, $delivery);
			$deliveries[$m] = get_invoice_sales_delivery_date(ST_CUSTDELIVERY, $delivery);
			
			
		    $this->Text($mcol + 22, $deliveries[$r]);
			$this->NewLine();
		}
		
		
		
		$this->row = $temp - 13;
		$this->Text($mcol + 118 , _("SO Date"));
	    $this->Text($mcol + 190, _(":"));
	    if($this->formData['lpo_date']!='0000-00-00')
	    $this->Text($mcol + 200, sql2date($sales_order['ord_date']));
	   	$this->NewLine();
	    
		
		
	    //$this->Text($mcol + 118 , _("Prepared By"));	
        //$this->NewLine();	
		//$this->Text($mcol + 118, $this->formData['prepared_by']);
		//$this->NewLine();
		
		$this->Text($mcol + 118 , _("DO Date"));
	        $this->Text($mcol + 190, _(":"));
		
		$deliveries1 = get_sales_parent_numbers(ST_SALESINVOICE, $this->formData['trans_no']);
		foreach($deliveries1 as $m => $delivery) {
		    $deliveries[$r] = get_invoice_sales_delivery_reference(ST_CUSTDELIVERY, $delivery);
			$deliveries[$m] = get_invoice_sales_delivery_date(ST_CUSTDELIVERY, $delivery);
			
			
		    $this->Text($mcol + 200, sql2date($deliveries[$m]));
			$this->NewLine();
		}
		
	    $this->NewLine(2);
	   
	 
		$this->row = $temp + 22;
		//$this->NewLine(2);
		if ($this->pageNumber > 1)
			$this->Text($mcol + 230, _("Page") . ' ' . $this->pageNumber);
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
			$this->NewLine(1.5);
			$this->Font();

		$count = count($this->headers2);
		$this->cols[$count] = $right - 3;
		for ($i = 0; $i < $count; $i++)
			$this->TextCol($i, $i + 1, $this->headers2[$i], -2);
	//	$this->Font();
		
     

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

