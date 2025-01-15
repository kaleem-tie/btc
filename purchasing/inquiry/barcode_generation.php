<?php /* version 1.1
Code By PHP HURDLES
OUR WEBSITE phphurdles.com
*/

$path_to_root = "../..";
include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/inventory/includes/inventory_db.inc");
?>
<style>
div.b128{
    border-left: 1px black solid;
	height: 20px;
}	
</style>

<?php
global $char128asc,$char128charWidth;
$char128asc=' !"#$%&\'()*+,-./0123456789:;<=>?@ABCDEFGHIJKLMNOPQRSTUVWXYZ[\]^_`abcdefghijklmnopqrstuvwxyz{|}~';					
$char128wid = array(
	'212222','222122','222221','121223','121322','131222','122213','122312','132212','221213', // 0-9 
	'221312','231212','112232','122132','122231','113222','123122','123221','223211','221132', // 10-19 
	'221231','213212','223112','312131','311222','321122','321221','312212','322112','322211', // 20-29 			
	'212123','212321','232121','111323','131123','131321','112313','132113','132311','211313', // 30-39 
	'231113','231311','112133','112331','132131','113123','113321','133121','313121','211331', // 40-49 
	'231131','213113','213311','213131','311123','311321','331121','312113','312311','332111', // 50-59 
	'314111','221411','431111','111224','111422','121124','121421','141122','141221','112214', // 60-69 
	'112412','122114','122411','142112','142211','241211','221114','413111','241112','134111', // 70-79 
	'111242','121142','121241','114212','124112','124211','411212','421112','421211','212141', // 80-89 
	'214121','412121','111143','111341','131141','114113','114311','411113','411311','113141', // 90-99
	'114131','311141','411131','211412','211214','211232','23311120'   );					   // 100-106

////Define Function
function bar128($text) {

$text=trim($text);
$sql="SELECT sm.description,sm.stock_id,dim.name,sp.price FROM ".TB_PREF."stock_master sm, ".TB_PREF."dimensions dim,".TB_PREF."prices sp WHERE sm.stock_id=".db_escape($text)." and dim.id=sm.dimension_id and sm.stock_id=sp.stock_id and sp.sales_type_id=1";

   	$result = db_query($sql, "could not query stock usage");
	$row = db_fetch($result);
		
	$item_desc= $row['description'];
	$item_code= $row['stock_id'];
    $company_name = $row['name'];
	$sales_price = $row['price'];
	 
	$sales_price=number_format($sales_price,3);

//$company_name = $_SESSION['SysPrefs']->prefs['coy_name'];



  global $char128asc,$char128wid;				
  $w = $char128wid[$sum = 104];							// START symbol
  $onChar=1;
  for($x=0;$x<strlen($text);$x++)								// GO THRU TEXT GET LETTERS
    if (!( ($pos = strpos($char128asc,$text[$x])) === false )){	// SKIP NOT FOUND CHARS
	  $w.= $char128wid[$pos];
	  $sum += $onChar++ * $pos;
	}					
  $w.= $char128wid[ $sum % 103 ].$char128wid[106];  		//Check Code, then END
  

//ravi

$item_description = str_split($item_desc, 18);

  $html="<table style='font:1px;margin-top:-2px;'>";
   $html=$html."<tr><td style='text-align:center;'><font family=arial size=2% !important><strong>".$company_name."</strong></font><td></tr>";
   
   for($i=0;$i<count($item_description);$i++)
   $html=$html."<tr><td style='text-align:center;'><font family=arial size=1% !important><strong>".$item_description[$i]."</strong></font><td></tr>";
   $html=$html."</table>";
  $html.="<table cellpadding=0 cellspacing=0><tr>";				
  for($x=0;$x<strlen($w);$x+=2)   						// code 128 widths: black border, then white space
	$html .= "<td><div class=\"b128\" style=\"border-left-width:{$w[$x]};width:{$w[$x+1]}\"></div></td>";
	 $html=$html."</tr></table>";
	
	$html.="<table cellpadding=0 cellspacing=0>";	
	$html=$html."<tr><td style='text-align:center; padding-left:0px;'><font family=arial size=2% !important><strong>".$item_code."</strong></font></td><td style='text-align:center;padding-left:10px;'><font family=arial size=2% !important><strong>RO:".$sales_price."</strong></font><td></tr>";
 
   $html=$html."</table>";
	 
   return $html;

}

?>