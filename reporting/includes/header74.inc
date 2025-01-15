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

    $Addr1,    $Addr2 - address info
    $Payment_Terms - payment terms line
    $Footer - footer texts
    $this->company - company info
    $this->title - report title
    $this->formData - some other info
***/

//include 'phpqrcode/qrlib.php';

$this->row = $this->pageHeight - $this->topMargin;

$upper = $this->row - 1 * $this->lineHeight;
$lower = $this->bottomMargin + 2.3 * $this->lineHeight;
$iline1 = $upper + 2.0 * $this->lineHeight;
$iline2 = $iline1 - 10.5 * $this->lineHeight;

$iline3 = $iline2 - 0.5 * $this->lineHeight; 
$iline4 = $iline3 - 1 * $this->lineHeight;
$iline5 = $iline4 - 0.5 * $this->lineHeight;
$iline6 = $iline5 - 2.0 * $this->lineHeight;
$iline7 = $lower;

$iline8 = $upper - 5.5 * $this->lineHeight;
$iline9 = $upper - 10.3 * $this->lineHeight;

$iline10 = $upper - 18.5 * $this->lineHeight;

$iline11 = $upper - 6.5 * $this->lineHeight;
$iline12 = $lower + 5.5 * $this->lineHeight;

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
$this->Line($iline11, 1);
$this->SetDrawColor(128, 128, 128);
$this->Line($iline11);

$this->rectangle($this->leftMargin, $iline5, $right - $this->leftMargin, $iline5 - 
$iline6, "F", null, array(255,255,255));   
$this->Line($iline5);
$this->Line($iline6);
$this->Line($iline7);


	
//$this->LineTo($this->cols[$cols - 2] + 4, $iline5 ,$this->cols[$cols - 2] + 4, $iline12);
	


		// Company Logo
		
		$this->NewLine();
		$this->fontSize += 4;
		$this->Font('bold');
		$this->Text($ccol, $this->company['coy_name'], $icol);
		$this->Font();
		$this->fontSize -= 4;
		
		
		
		//Document title
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
		$this->TextWrapLines($ccol, $icol, $this->company['postal_address']);
		$this->Font('italic');
		if (@$this->company['phone'])
		{
			$this->Text($ccol, _("Phone"), $c2col);
			$this->Text($c2col, $this->company['phone'], $mcol);
			$this->NewLine();
		}
		if (@$this->company['email'])
		{
			$this->Text($ccol, _("Email"), $c2col);

			$url = "mailto:" . $this->company['email'];
			$this->SetTextColor(0, 0, 255);
			$this->Text($c2col, $this->company['email'], $mcol);
			$this->SetTextColor(0, 0, 0);
			$this->addLink($url, $c2col, $this->row, $mcol, $this->row + $this->lineHeight);

			$this->NewLine();
		}
		if (@$this->company['gst_no'])
		{
			$this->Text($ccol, _("Our VAT No."), $c2col);
			$this->Text($c2col, $this->company['gst_no'], $mcol);
			$this->NewLine();
		}
		$this->Font();
		$this->row = $adrline;
		
		$logo = company_path() . "/images/" . $this->company['coy_logo'];
		if ($this->company['coy_logo'] != '' && file_exists($logo))
		{
			$this->NewLine();
			$this->AddImage($logo, $mcol+150, $this->row+10, 0, 40);
			$this->NewLine();
		}

        $this->NewLine(1);
        $this->fontSize += 7;
		$this->SetFont('helvetica', 'B', 9);
		$this->Text($mcol + 110,$this->title );
		$this->SetFont('', '', 0);
		$this->fontSize -= 7;
		
		
		$bank_tran = get_bank_trans(ST_BANKPAYMENT,$_POST['PARAM_0']);
		$bank_trans = db_fetch($bank_tran);
		

// Company data
		
		$this->NewLine(3);

		

		$this->NewLine();
		$this->SetFont('helvetica', 'B', 9);
		$this->Text($ccol, $this->formData['document_name'], $c2col+30);
		$this->SetFont('', '', 0);
		$this->Text($c2col+30, $bank_trans['ref'] , $icol);
		$this->NewLine();
		$this->SetFont('helvetica', 'B', 9);
		$this->Text($ccol, _("From Bank Account:"), $c2col+30);
		$this->SetFont('', '', 0);
		$this->Text($c2col+30, get_bank_payment_bank_act_name_rep($bank_trans['bank_act']) , $icol);
		
		
	   
		$this->Font();
		$this->row = $adrline;
		

        
		
	
		$this->NewLine(4);
		
		$this->NewLine(3);
		$this->SetFont('helvetica', 'B', 9);
		$this->Text($mcol + 100, _("Doc Date"));
		$this->Text($mcol + 160, _(":"));
		//$this->Text($mcol + 100, _("Page :"));
		$this->SetFont('', '', 0);
		$this->Text($mcol + 170 , sql2date($bank_trans['trans_date']));
		 $this->NewLine();
		$this->SetFont('helvetica', 'B', 9);
		$this->Text($mcol + 100, _("Cheque No."));
		$this->Text($mcol + 160, _(":"));
		$this->SetFont('', '', 0);
		$this->Text($mcol + 170, $this->formData['cheque_no']);
		
		$this->NewLine();
		$this->SetFont('helvetica', 'B', 9);
		$this->Text($mcol + 100,  _("Cheque Date"), $c2col+40);
		$this->Text($mcol + 160, _(":"));
		$this->SetFont('', '', 0);
		$this->Text($mcol + 170,  sql2date($this->formData['date_of_issue']));
		//$this->Text($mcol + 150, $this->pageNumber);

	

/*
$this->fontSize += 4;
$this->SetFont('helvetica', 'B', 12);
$this->Text($mcol - 130, $this->company['coy_name']);
$this->SetFont('', '', 0);
$this->fontSize -= 4;
$this->NewLine();
$this->TextWrapLines($mcol - 80, $icol, $this->company['postal_address']);
$this->Text($mcol - 100, _("Tel : ").$this->company['phone']."    "._("Fax : ").$this->company['fax']);
$this->NewLine();

//$logo = company_path() . "/images/" . $this->company['coy_logo'];


// Document title
$this->SetTextColor(190, 190, 190);
$this->TextWrap($mcol, $this->row, $this->pageWidth - $this->rightMargin - $mcol - 20, '', 'right');
$this->SetTextColor(0, 0, 0);*/




		

/*
$this->NewLine();
$this->fontSize += 6;
$this->SetFont('helvetica', 'B', 12);
$this->Text($mcol - 80,  _("CASH PAYMENT"));
$this->SetFont('', '', 0);
$this->fontSize -= 6;
$this->NewLine();
*/


/*
$this->SetFont('helvetica', 'B', 6);
$this->Text($ccol, _("Doc No. : ").$bank_trans['ref'] , $icol);
$this->NewLine();
$this->Text($ccol, _("Cash A/C : ").$bank_trans['bank_account_name'] , $icol);
$this->SetFont('', '', 0);
*/


/*
$this->NewLine(1);
$this->SetFont('helvetica', 'B', 9);
$this->Text($mcol + 30 , _("Doc Date"));
$this->Text($mcol + 120, _(":"));
$this->SetFont('', '', 0);
$this->Text($mcol + 130 , sql2date($bank_trans['trans_date']));

$this->NewLine();

$this->SetFont('helvetica', 'B', 9);
$this->Text($mcol + 30, _("Page "));
$this->Text($mcol + 120, _(":"));
$this->SetFont('', '', 0);
$this->Text($mcol + 130, $this->pageNumber);
*/

$this->row = $adrline;
		$this->NewLine();
		$temp = $this->row = $this->row - $this->lineHeight - 110;
		
		
        $this->row = $temp;
		
		//if ($this->pageNumber > 1)
			//$this->Text($mcol + 230, _("Page") . ' ' . $this->pageNumber);
		$this->row = $iline1 - $this->lineHeight;	
		

// Auxiliary document information
$col = $this->leftMargin;
foreach($aux_info as $info_header => $info_content)
{
    $this->row = $iline2 - $this->lineHeight - 0.5;
    $this->TextWrap($col, $this->row, $width, $info_header, 'C');
    $this->row = $iline3 - $this->lineHeight - 1;
    $this->TextWrap($col, $this->row, $width, $info_content, 'C');
    $col += $width;
}

// Line headers
$this->row = $iline5 - $this->lineHeight - 1;
$this->SetFont('helvetica', 'B', 9);
$count = count($this->headers);
$this->cols[$count] = $right - 3;
for ($i = 0; $i < $count; $i++)
    $this->TextCol($i, $i + 1, $this->headers[$i], -2);
$this->NewLine(1);

/*$count = count($this->headers2);
$this->cols[$count] = $right - 3;
for ($i = 0; $i < $count; $i++)
    $this->TextCol($i, $i + 1, $this->headers2[$i], -2);
$this->SetFont('', '', 0);*/

// Footer
$this->Font('italic');
$this->row = $iline7 - $this->lineHeight - 6;

foreach ($Footer as $line => $txt)
{
    if (!is_numeric($line))    // title => link
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
