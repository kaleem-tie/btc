<?php

class CustomerPDCPayment {

    var $items;

    function __construct($person_type = null, $trans_no = null, $date = null)
    {
        $this->person_type = $person_type;
        $this->type = ST_CUSTPDC;
        $this->trans_no = $trans_no;
        $this->items = array();
        $this->date = $date;
    }

    function resetItems() {
        unset($this->items);
        $this->items = array();
    }

    function addAllItems($items) {
        $this->resetItems();
        $this->items = $items;
    }

    function addItem($customer_id, $customer_name, $branch_id, $branch_name, $invoice_no, $outstanding_amount, $amount) {
        $item = array(
            'customer_id' => $customer_id,
            'customer_name' => $customer_name,
            'branch_id' => $branch_id,
            'branch_name' => $branch_name,
            'invoice_no' => $invoice_no,
            'outstanding_amount' => $outstanding_amount,
            'amount' => $amount
        );

        $this->items[] = $item;
    }

    function updateItem($line_no, $customer_id, $customer_name, $branch_id, $branch_name, $invoice_no, $outstanding_amount, $amount) {
        $this->items[$line_no] = array(
            'customer_id' => $customer_id,
            'customer_name' => $customer_name,
            'branch_id' => $branch_id,
            'branch_name' => $branch_name,
            'invoice_no' => $invoice_no,
            'outstanding_amount' => $outstanding_amount,
            'amount' => $amount
        );
    }
	
	
    function find_cart_customer_branch($customer_id,$branch_id)
	{
		foreach($this->items as $index => $pdc_item) {
			
			if ($pdc_item['customer_id'] == $customer_id && $pdc_item['branch_id'] == $branch_id)
				return $this->items[$index];
		}
		return null;
	}
	

    function removeItem($index)
    {
        unset($this->items[$index]);
    }

    function getTotalAmount()
    {
        $totalAmount = 0;
        foreach ($this->items as $itm) {
            $totalAmount += (double)$itm['amount'];
        }
        return $totalAmount;
    }

    function write()
	{
		
		global 	$no_exchange_variations;

        begin_transaction();
        foreach ($this->items as $item) 
        {
            
            if ($this->person_type == PT_SUPPLIER)
            {
                if($this->type == ST_SUPPPDC)
                { //ramesh for pdc allocations
                    clear_pdc_supp_alloctions($this->type, $this->trans_no, $item['person_id'], $this->date);
                }
                else
                {
                    clear_supp_alloctions($this->type, $this->trans_no, $item['person_id'], $this->date);
                }
            }
            else
            {
                if($this->type == ST_CUSTPDC)
                { 
				clear_pdc_cust_alloctions($this->type, $this->trans_no, $item['customer_id'], $this->date);
		        }
                else
                {
                  clear_cust_alloctions($this->type, $this->trans_no, $item['customer_id'], $this->date);
                }
            }

            // now add the new allocations
            $total_allocated = 0;
            $dec = user_price_dec();
           // display_error(http_build_query($item));
            if($item['invoice_no']!=0)
            {
                $invoice_info =get_invoice_information($item['customer_id'],$item['invoice_no']);
            }
            if ($item['amount'] > 0 && $item['invoice_no']!=0)
			{
				$amount = round($item['amount'], $dec);

				if ($this->person_type == PT_SUPPLIER)
                {
				    if($this->type == ST_SUPPPDC)
                    {  // With PDC Entry 
					    add_supp_pdc_allocation($amount, $this->type, $this->trans_no, 20, $invoice_info['trans_no'], $item['customer_id'], $this->date);
					    update_supp_trans_allocation_with_pdc(20, $invoice_info['trans_no'], $item['customer_id']);
					}
                    else
                    {
					    add_supp_allocation($amount, $this->type, $this->trans_no, 20, $invoice_info['trans_no'], $item['customer_id'], $this->date);
					    update_supp_trans_allocation(20, $invoice_info['trans_no'], $item['customer_id']);
					}
					
				}
                else
                {
					if($this->type == ST_CUSTPDC)
                    {  
                        add_cust_pdc_allocation($amount, $this->type, $this->trans_no, 10, $invoice_info['trans_no'], $item['customer_id'], $this->date);
                        update_debtor_trans_allocation_with_pdc(10 , $invoice_info['trans_no'], $item['customer_id']);
					}
                    else
                    {
	 				    add_cust_allocation($amount, $this->type, $this->trans_no, 10, $invoice_info['trans_no'], $item['customer_id'], $this->date);
	 				    update_debtor_trans_allocation(10, $invoice_info['trans_no'], $item['customer_id']);
					}
				}
				
                // Exchange Variations Joe Hunt 2008-09-20 ////////////////////
		    	// if ($alloc_item->type != ST_SALESORDER && !@$no_exchange_variations
				// 	&& $alloc_item->type != ST_PURCHORDER && $alloc_item->type != ST_JOURNAL && $this->type != ST_JOURNAL)
				if($this->type == ST_SUPPPDC)
                {
                    exchange_variation($this->type, $this->trans_no,
						20, $invoice_info['trans_no'], $this->date,
						$amount, $this->person_type,false, $item['customer_id']); //ravi
                } 
				
                else {
			          exchange_variation($this->type, $this->trans_no,
						10, $invoice_info['trans_no'], $this->date,
						$amount, $this->person_type,false, $item['customer_id']); //ravi
                } 
                

				//////////////////////////////////////////////////////////////
				$total_allocated += $alloc_item->current_allocated;
			}

            if ($this->person_type == PT_SUPPLIER)
            {
                if($this->type == ST_SUPPPDC)
                {   
                    update_supp_trans_allocation_with_pdc($this->type, $this->trans_no, $item['customer_id']);
                }
                else
                {
                    update_supp_trans_allocation($this->type, $this->trans_no, $item['customer_id']);
                }
                    
            }
            else
            {
                if($this->type == ST_CUSTPDC)// With PDC Entry ##Ramesh
                {
                    update_debtor_trans_allocation_with_pdc($this->type, $this->trans_no, $item['customer_id']);
                }
                else
                {
                    update_debtor_trans_allocation($this->type, $this->trans_no, $item['customer_id']);
                }
            }
		  
           
        }
        commit_transaction();
	}
}