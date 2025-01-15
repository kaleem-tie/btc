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



		$this->row = $this->pageHeight - $this->topMargin;

		$upper = $this->row -2 * $this->lineHeight;
		
		$lower = $this->bottomMargin + 4.5 * $this->lineHeight;
		
		$iline1 = $upper - 7 * $this->lineHeight;
		$iline2 = $iline1 - 6 * $this->lineHeight;
		$iline3 = $iline2 + 8.5 * $this->lineHeight;
		$iline4 = $iline3 + 1.5 * $this->lineHeight;
		$iline5 = $iline4 - 2 * $this->lineHeight;
		$iline6 = $iline5 - 1.5 * $this->lineHeight;
		$iline7 = $lower;
		
		$iline8 = $upper - 5.5 * $this->lineHeight;
		$iline9 = $upper - 5 * $this->lineHeight;
		

		
		
		$iline10 = $upper - 18.5 * $this->lineHeight;
		
		$right = $this->pageWidth - $this->rightMargin;
		$width = ($right - $this->leftMargin);
		$icol = $this->pageWidth / 2;
		$ccol = $this->cols[0] + 4;
		$c2col = $ccol + 60;
		$ccol2 = $icol / 2;
		$mcol = $icol + 8;
		$mcol2 = $this->pageWidth - $ccol2;
		$cols = count($this->cols);
		
	

		
		$this->SetDrawColor(205, 205, 205);
		$this->Line($iline9, 1);
		$this->SetDrawColor(128, 128, 128);
		$this->Line($iline9);
		
		
		$this->NewLine(2);		
		
		$this->fontSize += 6;
		$this->Font('bold');
		$this->Text($ccol, $this->company['coy_name'], $icol+100);
		$this->Font();
		$this->fontSize -= 6;
		
		$this->Text($mcol + 180, _("Page") . ' ' . $this->pageNumber);
				
		
		// Document title
		$this->SetTextColor(190, 190, 190);
		$this->fontSize += 10;
		$this->Font('bold');
		$this->TextWrap($mcol, $this->row, $this->pageWidth - $this->rightMargin - $mcol - 20, '', 'right');
		$this->Font();
		$this->fontSize -= 10;
		$this->NewLine();
		$this->SetTextColor(0, 0, 0);
		$adrline = $this->row;

    
		// Company data
		
		$this->TextWrapLines($ccol, $icol+100 , $this->company['postal_address']);
		
		$this->Text($ccol  , _("Tel :  ")  .$this->company['phone']. " ,   ". 
		_("Fax : ") .$this->company['fax']);
		$this->NewLine();
		$this->Text($ccol  , _("Email :  ")  .$this->company['email']);
	
	    $this->Text($mcol + 160, _("Date :") . ' ' . $_POST['PARAM_0']);
		
		
		
		
	   

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
