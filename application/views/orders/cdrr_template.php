<div class="full-content" style="width:98%;">
	<div>
		<ul class="breadcrumb">
			<li>
				<a href="<?php echo site_url().'order' ?>">CDRR</a><span class="divider">/</span>
			</li>
			<li class="active" id="actual_page">
				<?php echo $page_title;?>
			</li>
		</ul>
	</div>
	<?php 
	if($options=="update" || $options=="view"){
	?>
	<form id="fmPostCdrr" name="fmPostCdrr" method="post" action="<?php echo site_url('order/save/cdrr/prepared/'.$cdrr_id)?>">	
	<?php	
	}else{
	?>
	<form id="fmPostCdrr" name="fmPostCdrr" method="post" action="<?php echo site_url('order/save/cdrr/prepared')?>">
	<?php
	}
	?>
	<?php 
	if($this->session->flashdata('order_delete')){
		echo '<p class="message error">'.$this->session->flashdata('order_delete').'</p>';
	}
	else if ($this->session->flashdata('order_message')){
		echo '<p class="message info">'.$this->session->flashdata('order_message').'</p>';
	}	
	?>
		<?php 
		 if($options=="view"){
		?>
		<ul class="nav nav-tabs">
         <?php echo $option_links; ?>
        </ul>
		<label><h2><b><?php echo $cdrr_array[0]['cdrr_label']." ".$cdrr_array[0]['status_name'];?></b></h2></label><br/>
		<a href='<?php echo site_url("order/download_order/cdrr/".$cdrr_id);?>'><?php echo $cdrr_array[0]['cdrr_label']." ".$cdrr_array[0]['facility_name']." ".$cdrr_array[0]['period_begin']." to ".$cdrr_array[0]['period_end'].".xls";?></a>
		<p></p>
		<input type="hidden"  id="status" name="status" value="<?php echo strtolower($cdrr_array[0]['status_name']);?>"/>
		<input type="hidden"  id="created" name="created" value="<?php echo $cdrr_array[0]['created'];?>"/>
		<?php 
		   $access_level = $this -> session -> userdata("user_indicator");
		      if($access_level=="facility_administrator"){
		      	if($status_name=="prepared"){
		?>
	            <input type="hidden" name="status_change" value="approved"/>
				<input type='submit' name='save' class='btn btn-info state_change' value='Approve'/>
		<?php
		      } else if($status_name == "approved" && $cdrr_array[0]['status'] == "F-CDRR_units" && $is_central_site == TRUE){
		 ?>
		        <input type="hidden" name="status_change" value="archived"/> 
		 		<input type='submit' name='save' class='btn btn-info state_change' value='Archive'/>
		 <?php
		      }
		  ?> 	  	  
		<?php
			  }
		?>	     
		<?php	
		 }else if($options=="update"){
		 ?>
		<label><h2><b>Update <?php echo $cdrr_array[0]['cdrr_label']." ".$cdrr_array[0]['status_name'];?></b></h2></label>
					<input type="hidden"  id="status" name="status" value="<?php echo strtolower($cdrr_array[0]['status_name']);?>"/>
					<input type="hidden"  id="created" name="created" value="<?php echo $cdrr_array[0]['created'];?>"/>
		 <?php	
		 }
		?>
		<div class="facility_info">
			<input type="hidden" name="report_type" value="<?php echo $report_type;?>"/>
			<table cellpadding="5" border="1" width="100%" style="border:1px solid #DDD;">
				<thead>
					<tr>
						<td colspan='2' align="center"><b>
						<?php 
						if($hide_generate==2){
						?>
						CENTRAL SITE / DISTRICT STORE CONSUMPTION DATA REPORT AND REQUEST (D-CDRR) FOR ANTIRETROVIRAL AND OPPORTUNISTIC INFECTION MEDICINES
						<?php 
						}else{
						?>
						FACILITY CONSUMPTION DATA REPORT AND REQUEST (F-CDRR) FOR ANTIRETROVIRAL AND OPPORTUNISTIC INFECTION MEDICINES
						<?php 
						}
						?>
						</b></td>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td style="width:50%;"><b>Facility Name: &nbsp;</b><?php echo $facility_object -> name;?></td>
						<td><b>Facility code: &nbsp;</b><?php echo $facility_object -> facilitycode;?>
							<input type="hidden" name="facility_code" id="facility_code" value="<?php echo $facility_object ->facilitycode; ?>"/>
							<input type="hidden" name="facility_id" id="facility_id" value="<?php echo $facility_id; ?>"/>
							<input type="hidden" name="sponsor" value="<?php echo $facility_object -> support -> Name; ?>"/>
							<input type="hidden" name="non_arv" id="non_arv" value="0"/>
							<?php
								$type_of_service = array();
								if ($facility_object -> service_art == "1") {
									$type_of_service[] = "ART";
								}
								if ($facility_object -> service_pmtct == "1") {
									$type_of_service[] = "PMTCT";
								}
								if ($facility_object -> service_pep == "1") {
									$type_of_service[] = "PEP";
								}
							?>
							<input type="hidden" name="type_of_service" value="<?php echo implode(",", $type_of_service); ?>"/>
						</td>
					</tr>
					<tr>
						<td><b>County: &nbsp;</b><?php echo ucwords($facility_object -> County -> county);?></td>
						<td><b>Sub-County: &nbsp;</b><?php echo $facility_object -> Parent_District -> Name;?></td>
					</tr>
					<tr>
						<?php
						if (empty($cdrr_array)) {
						?>
						<td colspan='2'><b>Period of Reporting: &nbsp;</b>
						<select readonly="readonly" name="period_start" id="period_start">
							<option selected="selected" value="<?php echo date('Y-m-01', strtotime(date('Y-m-d') . "-1 month")); ?>"><?php echo date('F', strtotime(date('Y-m-d') . "-1 month"));?></option>
						</select>
						<select readonly="readonly" name="period_end" id="period_end">
							<option selected="selected" value="<?php echo date('Y-m-t', strtotime(date('Y-m-d') . "-1 month")); ?>"><?php echo date('Y', strtotime(date('Y-m-d') . "-1 month"));?></option>
						</select>
						<?php }else{?>
						<td colspan='2'><b>Period of Reporting: &nbsp;</b>
						<select readonly="readonly" name="period_start" id="period_start">
							<option selected="selected" value="<?php echo date('Y-m-01', strtotime($cdrr_array[0]['period_begin'])); ?>"><?php echo date('F', strtotime($cdrr_array[0]['period_end']));?></option>
						</select>
						<select readonly="readonly" name="period_end" id="period_end">
							<option selected="selected" value="<?php echo date('Y-m-t', strtotime($cdrr_array[0]['period_end'])); ?>"><?php echo date('Y', strtotime($cdrr_array[0]['period_end']));?></option>
						</select>			
						<?php
						  } 
						  if($hide_generate !=1 && $hide_btn==0){
						?>

                       	<select class="multiselect" id="stores" name="stores" multiple="multiple">
				        <?php 
				          foreach($stores as $category=>$groups){
				        ?>
                           <optgroup label="<?php echo $category; ?>">
				        <?php
				          	 foreach($groups as $group){
				        ?>
                           <option value="<?php echo $group['id'];?>"><?php echo $group['name']; ?></option>
                        <?php
				            }
				         ?>
                            </optgroup>
				        <?php
				         }
				        ?>
						</select>
						<?php
						  if($hide_generate==0 && $hide_btn==0){
						?>
						<input type="button" style="width:auto" name="generate" id="generate" class="btn" disabled value="Generate Report" >
						<?php 
						  }else if($hide_generate==2 && $hide_btn==0){
						?>
					    <input type="button" style="width:auto" name="generate" id="generate" class="btn" disabled value="Update Aggregated Data" >
						<?php
						  }
						}
						?>
						</td>
					</tr>
				</tbody>
			</table>
		</div>
		<div>
			<table class="table table-bordered" style="font-size:15px;background:#FFF;" id="generate_order">
	<?php
	if($hide_generate==2){
		$header_text = '<thead style="text-align:left;background:#c3d9ff;">
					<tr>
						<th colspan="2" style="text-align:center;"></th>
						<th colspan="7" style="text-align:center;">Central site store / Sub-county store data</th>
						<th colspan="2" style="text-align:center;">Data from the Satellite sites plus Central site dispensing point(s)</th>
						<th colspan="4" style="text-align:center;">Central site store / Sub-county store data</th>
					</tr>
					<tr>
						<th class="col_drug" rowspan="3">Commodity Name</th>
						<th class="number" rowspan="3">Unit of Issue /<br/>Pack Size</th>
						<th class="number">Beginning Balance</th>
						<th class="number">Total Quantity <br/>Received this month</th>
						<th class="col_dispensed_units">Total Quantity Issued this month</th>
						<th class="col_losses_units">Losses &amp Wastages</th>
						<th class="col_adjustments">Positive</br>Adjustments</th>
						<th class="col_adjustments">Negative</br>Adjustments</th>				
						<th class="number">End of Month Physical Count</th>
						<th class="number">AGGREGATED Quantity Dispensed this Month</th>
						<th class="number">AGGREGATED End of Month Physical Stock Count this Month</th>
						<th class="number" colspan="2">Commmodities expiring in <u>less than</u> 6 months</th>
						<th class="number">Days out of stock <u>this Month</u></th>
						<th class="number">Quantity requested for RE-SUPPLY</th>
					</tr>
					<tr>
						<th>A</th>
						<th>B</th>
						<th>C</th>
						<th>D</th>
						<th>E</th>
						<th>F</th>
						<th>G</th>
						<th>H</th>
						<th>I</th>
						<th>Quantity</th>
						<th>Earliest expiry<br/> date mm/yyyy</th>
						<th>J</th>
						<th>K</th>
					</tr>
			</thead>';

	// this is the start of a standalone form. 
	}else if($stand_alone==1){
		$header_text = '<thead style="text-align:left;background:#c3d9ff;">
					<tr>
						<th class="col_drug" rowspan="3">Commodity Name</th>
						<th class="number" rowspan="3">Unit of Issue /<br/>Pack Size</th>
						<th class="number">Beginning Balance</th>
						<th class="number">Total Quantity <br/>Received this month</th>
						<th class="col_dispensed_units">Total Quantity Dispensed <br/>this month</th>
						<th class="col_losses_units">Losses &amp Wastages</th>
						<th class="col_adjustments">Positive</br>Adjustments</th>
						<th class="col_adjustments">Negative</br>Adjustments</th>
						<th class="number">End of Month</br> Physical Count</th>
						<th class="number" colspan="2">Commodities expiring in <u>less than</u> 6 months to expiry</th>
						<th class="number">Days out of stock <u>this</u> Month</th>
						<th class="number">Quantity required for RE-SUPPLY</th>
					</tr>
					<tr>
						<th>A</th>
						<th>B</th>
						<th>C</th>
						<th>D</th>
						<th>E</th>
						<th>F</th>
						<th>G</th>
						<th>Quantity</th>
						<th>Earliest Expiry<br/> date mm/yyyy</th>
						<th>H</th>
						<th>I</th>
					</tr>
			</thead>';
		
	}else if($stand_alone==0 && $hide_generate !=2){
		$header_text = '<thead style="text-align:left;background:#c3d9ff;">
					<tr>
						<th class="col_drug" rowspan="2">Drug Name</th>
						<th class="number" rowspan="2">Unit Pack Size</th>
						<th class="number">Beginning Balance</th>
						<th class="number">Total Quantity <br/>Received this month</th>
						<th class="col_dispensed_units">Total Quantity Dispensed <br/>this month</th>
						<th class="col_losses_units">Losses &amp Wastages</font></th>
						<th class="col_adjustments">Positive</br>Adjustments</th>
						<th class="col_adjustments">Negative</br>Adjustments</th>
						<th class="number">End of Month</br> Physical Stock Count</br><font style="font-weight:lighter; color:blue;">(For CS </br>Dispensing </br>Point, please </br><strong>exclude</strong> the central site Store stocks)</font></th>
						<th class="number" colspan="2">Commodities expiring in <u>less than</u> 6 months to expiry</th>
						<th class="number">Days out of stock <u>this</u> Month</th>
						<th class="number">Quantity required for RE-SUPPLY</th>
					</tr>
					<tr>
						<th>A</th>
						<th>B</th>
						<th>C</th>
						<th>D</th>
						<th>E</th>
						<th>F</th>
						<th>G</th>
						<th>Quantity</th>
						<th>Earliest Expiry<br/> date mm/yyyy</th>
						<th>H</th>
						<th>I</th>
					</tr>
			</thead>';}echo $header_text;?>
				<tbody>
					<?php 
					$counter =-1;
					$count_one=0;
					$count_two=0;
					$count_three=0;
					$count_four=0;
					foreach($commodities as $commodity){
				         if($commodity -> Drug !=NULL){
				         	$counter++;
			                   if($counter ==10){
			                   echo $header_text;
			                   $counter = 0;
			                   }
						  if($hide_generate==2){
						  if($commodity->Category==1 && $count_one==0){
						  	  echo '<tr><td colspan="15" style="text-align:center;background:#998;">ARVs</td></tr>';
						  	  echo '<tr><td colspan="15" style="text-align:center;background:#999;">Adult Preparations</td></tr>';
							  $count_one++;
						  }	   
						  if($commodity->Category==2 && $count_two==0){
						  	  echo '<tr><td colspan="15" style="text-align:center;background:#999;">Pediatric Preparations</td></tr>';
							  $count_two++;
						  }
                          if($commodity->Category==3 && $count_three==0){
						  	  echo '<tr><td colspan="15" style="text-align:center;background:#999;">Medicines for OIs</td></tr>';
							  $count_three++;
						  }
						  if($commodity->Category==4 && $count_four==0){
						  	  echo '<tr><td colspan="15" style="text-align:center;background:#999;">TB/ HIV DRUGS</td></tr>';
							  $count_four++;
						  }
						  }else{
						  if($commodity->Category==1 && $count_one==0){
						  	  echo '<tr><td colspan="13" style="text-align:center;background:#998;">ARVs</td></tr>';
						  	  echo '<tr><td colspan="13" style="text-align:center;background:#999;">Adult Preparations</td></tr>';
							  $count_one++;
						  }	   
						  if($commodity->Category==2 && $count_two==0){
						  	  echo '<tr><td colspan="13" style="text-align:center;background:#999;">Pediatric Preparations</td></tr>';
							  $count_two++;
						  }
                          if($commodity->Category==3 && $count_three==0){
						  	  echo '<tr><td colspan="13" style="text-align:center;background:#999;">Medicines for OIs</td></tr>';
							  $count_three++;
						  }
						  if($commodity->Category==4 && $count_four==0){
						  	  echo '<tr><td colspan="13" style="text-align:center;background:#999;">TB/ HIV DRUGS</td></tr>';
							  $count_four++;
						  }
						  }
					?>
					<tr class="ordered_drugs" drug_id="<?php echo $commodity -> id;?>">
						<td class="col_drug"><?php echo $commodity -> Drug;?>
							<?php 
							if($options=="update" || $options=="view"){
							?>
							<input type="hidden" name="item_id[]" id="item_id_<?php echo $commodity -> id;?>" value=""/>
							<?php
							}
							?>
						</td>

						<?php
	                    if($hide_generate==2){
	                    ?>
<!-- showing the input of the column unit pack size -->
						<td class="number calc_count">
							<input type="text"  class="pack_size" name="pack_size[]" id="pack_size_<?php echo $commodity -> id;?>" value="<?php echo $commodity ->Pack_Size;?>" style="color: blue; font-weight: bold; text-align:center;"/>
						</td>
						<?php

						}else{ 
						?>
<!-- showing the input of the column unit pack size -->

<!-- Devide the displayed output by the number of packsize -->
						<td class="number calc_count"><input type="text"  class="pack_size" name="pack_size[]" id="pack_size_<?php echo $commodity -> id;?>" value="<?php echo $commodity ->Pack_Size;?>" style="color: blue; font-weight: bold; text-align:center;"/></td>
						<?php	
						}
						?>
						<td> <input name="opening_balance[]" id="opening_balance_<?php echo $commodity->id;?>" type="text" class="opening_balance" style="width:100%; text-align:center;"/></td>
						<td> <input name="quantity_received[]" id="received_in_period_<?php echo $commodity -> id;?>" type="text" class="quantity_received" style="width:100%; text-align:center;"/></td>
						<td> <input name="quantity_dispensed[]" id="dispensed_in_period_<?php echo $commodity -> id;?>" type="text" class="quantity_dispensed" style="width:100%; text-align:center;"/></td>
						<td> <input name="losses[]" id="losses_in_period_<?php echo $commodity->id;?>" type="text" class="losses" style="width:100%; text-align:center;"/></td>
						<!-- added column to the new cdrr templates ... Positive Adjustments -->
						<td> <input name="adjustments[]" id="positive_adjustment_<?php echo $commodity->id;?>" type="text" class="adjustments" style="width:100%; text-align:center;"/></td>
						<!-- end of added column to the new cdrr templates ... Negative Adjustments -->
						<td> <input name="adjustments_neg[]" id="adjustments_in_period_<?php echo $commodity->id;?>" type="text" class="adjustments_neg" style="width:100%; text-align:center;"/></td>
						<td> <input tabindex="-1" name="physical_count[]" id="physical_in_period_<?php echo $commodity->id;?>" type="text" class="physical_count" style="width:100%; text-align:center;"/></td>
						<?php
	                    if($hide_generate==2){
	                    ?>
						<td> <input tabindex="-1" name="aggregated_qty[]" id="aggregated_qty_<?php echo $commodity->id;?>" type="text" class="aggregated_qty" style="width:100%; text-align:center;"/></td>
						<td> <input tabindex="-1" name="aggregated_physical_qty[]" id="aggregated_physical_qty_<?php echo $commodity->id;?>" type="text" class="aggregated_physical_qty" style="width:100%; text-align:center;"/></td>
						<?php
						}
						?>
						<td> <input tabindex="-1" name="expire_qty[]" id="expire_qty_<?php echo $commodity -> id;?>" type="text" class="expire_qty" style="width:100%; text-align:center;"/></td>
						<td> <input tabindex="-1" name="expire_period[]" id="expire_period_<?php echo $commodity -> id;?>" type="text" class="expire_period" style="width:100%; text-align:center;"/></td>	
						<td> <input tabindex="-1" name="out_of_stock[]" id="out_of_stock_<?php echo $commodity -> id;?>" type="text" class="out_of_stock" style="width:100%; text-align:center;"/></td>
						<td> <input tabindex="-1" name="resupply[]" id="resupply_<?php echo $commodity -> id;?>" type="text" class="resupply" style="width:100%; text-align:center;"/></td>	
						<input type="hidden" name="commodity[]" value="<?php echo $commodity -> id;?>"/>					
					</tr>					
					<?php 
						  }
						 }
					?>
				</tbody>
			</table>
		</div>
		<div>
			<span style="vertical-align:bottom;font-size:1.2em;">Comments (Explain ALL Losses and Adjustments):</span>
			<textarea style="width:100%;font-size:18px;" rows="8" name="comments" id="comments"><?php 
				if($options=="update" || $options=="view") echo @$cdrr_array[0]['comments'];
			?></textarea>
			<?php
	           if($hide_generate==2){
	        ?>
			<table border="0" cellpadding="5" style="padding:10px;" class="table-bordered ">
				<tr>
					<td><b>Central site Reporting rate:-</b> </td>
					<td><b>Total No. of Facility Reports Expected:</b><br/>(Total number of Satellite sites plus the Central site Dispensing point)</td>
					<td><input type="text" name="central_rate"  id="central_rate"/></td>
					<td>Actual No. of Facility Reports Received:</td>
					<td><input type="text" name="actual_report" id="actual_report"/></td>
				</tr>
			</table>
						<?php 
				 }if($options=="view" || $options=="update"){
		    ?>
		    <table style="width:100%;" class="table table-bordered">
		    	<?php 
					error_reporting(0); 
		    	    foreach($logs as $log){?>
				<tr>
					<td><b>Report <?php echo $log->description;?> by:</b> 
					<input type="hidden" name="log_id[]" id="log_id_<?php echo $log -> id;?>" value="<?php echo $log -> id;?>"/>	
					</td>
					<td><?php echo $log->user->Name; ?></td>
					<td><b>Designation:</b></td>
					<td><?php echo $log->user->Access->Level_Name; ?></td>
				</tr>
				<tr>
					<td><b>Contact Telephone:</b></td>
					<td><?php echo $log->user->Phone_Number; ?></td>
					<td><b>Date:</b></td>
					<td><?php echo $log->created; ?></td>
				</tr>
				<?php }?>
			</table>
		    <?php		 	
				 }
			?>
			<?php
                if($hide_save==0){
				?>
			<input type="submit" class="btn btn-info actual" value="Save"/>
			<input type="hidden" value="Submit Order" name="save">
			<?php
				}
			?>
		</div>
	</form>
</div>
<script type="text/javascript">
	$(document).ready(function(){

		//Check if report is a duplicate
		var duplicate = "<?=$duplicate?>";
		if(duplicate == true)
		{ 
		   bootbox.alert("<h4>Duplicate</h4>\n\<hr/><center>This Report already exists!</center>");
		}

		$("#non_arv").click(function(){
			var selected_value=$(this).val();
			if(selected_value==0){
				$(this).val(1);
			}else{
				$(this).val(0);
			}
		});
		
		$("#generate").on('click',function() {
		    //display generating modal
		    $.blockUI({ message: '<h3><img width="30" height="30" src="<?php echo asset_url().'images/loading_spin.gif' ?>" /> Generating...</h3>' }); 
            //parameters
            var count = 0;
		    var total =$(".ordered_drugs").length;
            //get drug_id's
            var drugs=[];
            var stores=$('#stores').val();

            $(".ordered_drugs").each(function(i,v){ 
           	    drugs.push($(this).attr("drug_id")); 
	           	if(i==(total-1)){
	           	    var period_start = $("#period_start").attr("value");
		            var facility_id= $("#facility_id").attr("value");
		            var facility_code =$("#facility_code").attr("value");
		            //set the code
		            <?php
		            if($hide_generate==2){
		               $code="D-CDRR";
					}else if($stand_alone==1){
		               $code="F-CDRR_packs";
					}else{
						$code="F-CDRR_units";
					}
				   ?>
				   var code = "<?php echo $code; ?>";
		           //run function
	    	       getPeriodDrugBalance(count,period_start, facility_id,code,total,drugs,stores); 
	           	}
            });

            //If Report is D-CDRR, get Expected and actual reports
            var code = "<?php echo $code; ?>";
            var facility_code =$("#facility_code").attr("value");
            var period_start = $("#period_start").attr("value");

    	    if(code=="D-CDRR"){
    	       getExpectedActualReports(facility_code,period_start,"cdrr");
    	    }

		});
			
		$(".pack_size").live('change',function() {
			calculateResupply($(this));
		});
		$(".opening_balance").live('change',function() {
			calculateResupply($(this));
		});
		$(".quantity_received").live('change',function() {
			calculateResupply($(this));
		});
		$(".quantity_dispensed_packs").live('change',function() {
			calculateResupply($(this));
			var code = "<?php echo $code; ?>";
			if(code == "F-CDRR_packs")
			{
				calculateUnits($(this));
			}
		});
		$(".quantity_dispensed").live('change',function() {
			calculateResupply($(this));
			var code = "<?php echo $code; ?>";
			if(code == "F-CDRR_packs")
			{
				calculatePacks($(this));
			}
		});
        $(".adjustments").live('change',function() {
            calculateResupply($(this));
        });
		$(".losses").live('change',function() {
			calculateResupply($(this));
		});
		$(".adjustments_neg").live('change',function() {
			calculateResupply($(this));
		});
		$(".physical_count").live('change',function() {
			calculateResupply($(this));
		});

		$(".aggregated_qty").live('change',function() {
			calculateResupply($(this));
		});
		
		
		<?php
		if (!empty($cdrr_array)) {
			if($cdrr_array[0]['code']=='D-CDRR'){
		?>
		$("#central_rate").val("<?php echo $cdrr_array[0]['reports_expected']; ?>");
		$("#actual_report").val("<?php echo $cdrr_array[0]['reports_actual']; ?>");
		<?php	
		    }
		  foreach($cdrr_array as $cdrr){
		  	if($cdrr['non_arv']==1){
	    ?>
	    $("#non_arv").val("<?php echo $cdrr['non_arv']; ?>");
	    $("#non_arv").attr("checked",true);
	    <?php
			}
	    ?>
		  $("#period_start").val("<?php echo $cdrr['period_begin']; ?>");
		  $("#period_end").val("<?php echo $cdrr['period_end']; ?>");
		  $("#item_id_<?php echo $cdrr['drug_id']; ?>").val("<?php echo $cdrr['item_id']; ?>");
		  $("#opening_balance_<?php echo $cdrr['drug_id']; ?>").val("<?php echo $cdrr['balance']; ?>");
		  $("#received_in_period_<?php echo $cdrr['drug_id']; ?>").val("<?php echo $cdrr['received']; ?>");
		  $("#dispensed_in_period_<?php echo $cdrr['drug_id']; ?>").val("<?php echo $cdrr['dispensed_packs']; ?>"); 
		<?php	
		if($cdrr_array[0]['code']=="D-CDRR"){
		?>
 		  $("#dispensed_in_period_<?php echo $cdrr['drug_id']; ?>").val("<?php echo $cdrr['dispensed_packs']; ?>"); 
		<?php	
		}
		?>
		<?php  
		if($cdrr_array[0]['code']=="F-CDRR_packs"){
		?>
		  $("#dispensed_in_period_packs_<?php echo $cdrr['drug_id']; ?>").val("<?php echo $cdrr['dispensed_packs']; ?>"); 	
		<?php	
		}
		?>
          $("#positive_adjustment_<?php echo $cdrr['drug_id']; ?>").val("<?php echo $cdrr['adjustments']; ?>");
		  $("#losses_in_period_<?php echo $cdrr['drug_id']; ?>").val("<?php echo $cdrr['losses']; ?>");
		  $("#adjustments_in_period_<?php echo $cdrr['drug_id']; ?>").val("<?php echo $cdrr['adjustments_neg']; ?>");
		  $("#physical_in_period_<?php echo $cdrr['drug_id']; ?>").val("<?php echo $cdrr['count']; ?>");
		  <?php
		  if($cdrr_array[0]['code']=='D-CDRR'){
		  ?>
		  $("#aggregated_qty_<?php echo $cdrr['drug_id']; ?>").val("<?php echo $cdrr['aggr_consumed']; ?>");
		  $("#aggregated_physical_qty_<?php echo $cdrr['drug_id']; ?>").val("<?php echo $cdrr['aggr_on_hand']; ?>");
		  <?php
		  }
		  ?>
		  $("#expire_qty_<?php echo $cdrr['drug_id']; ?>").val("<?php echo $cdrr['expiry_quant']; ?>");
		  $("#expire_period_<?php echo $cdrr['drug_id']; ?>").val("<?php echo $cdrr['expiry_date']; ?>");
		  $("#out_of_stock_<?php echo $cdrr['drug_id']; ?>").val("<?php echo $cdrr['out_of_stock']; ?>");
		  $("#resupply_<?php echo $cdrr['drug_id']; ?>").val("<?php echo @$cdrr['resupply']; ?>");
		<?php	
		   }
		}
	   ?>
	  <?php 
	  if($options=="view"){
	  ?>
	   $("input,textarea").attr("readonly","readonly");
	   $(".state_change").attr("readonly",false);
	  <?php	
	  } 
	  ?>  

		//Initialize the multi select plugin:
		$('.multiselect').multiselect({
		    	includeSelectAllOption : true,
				maxHeight : 300,
				enableFiltering : true,
				filterBehavior : 'both',
				enableCaseInsensitiveFiltering : true,
				filterPlaceholder : 'Search'
		});	

		$("#stores").on('change', function() {
			var check = checkTableSelected();
			if(check != 0){
				$("#generate:disabled").removeAttr('disabled');
			}else{
				$("#generate").attr('disabled', 'disabled');
			}
		});
	  
	});

	function checkTableSelected(){//Function to check if database tables to be migrated were selected
		//Variable to check if all database tables are selected, true if all are selected, false if not
		  var allSelected = $("#stores option:not(:selected)").length == 0;
		  var check = 0;
		  var selectedTables = $('#stores').val();
		  if(allSelected){//If all database tables are selected
		  	check = selectedTables;
		  }else{
		  	 
		  	if(selectedTables==null){//If no table was selected
		  		check = 0;
		  	}else{//Is some tables were selected
		  		check = selectedTables;
		  	}
		  }
		  
		  return check;
	}


	function calculateResupply(element) {
		var row_element = element.closest("tr");
		var opening_balance = parseInt(row_element.find(".opening_balance").attr("value"));
		var quantity_received = parseInt(row_element.find(".quantity_received").attr("value"));
		<?php
		if($stand_alone==1){
		?>
		// var quantity_dispensed = parseInt(row_element.find(".quantity_dispensed_packs").attr("value"));
		<?php
		}else{
		?>
		var quantity_dispensed = parseInt(row_element.find(".quantity_dispensed").attr("value"));
		<?php
		}
		?>
		var losses = parseInt(row_element.find(".losses").attr("value"));
		var adjustments = parseInt(row_element.find(".adjustments").attr("value"));
		var adjustments_neg = parseInt(row_element.find(".adjustments_neg").attr("value"));
		var physical_count = parseInt(row_element.find(".physical_count").attr("value"));
		var resupply = 0;
		if(!(opening_balance + 0)) {
			opening_balance = 0;
		}
		if(!(quantity_received + 0)) {
			quantity_received = 0;
		}
		if(!(quantity_dispensed + 0)) {
			quantity_dispensed = 0;
		}
		if(!(losses + 0)) {
			losses = 0;
		}

		if(!(adjustments + 0)) {
			adjustments = 0;
		}

		if(!(adjustments_neg + 0)) {
			adjustments_neg = 0;
		}

		if(!(physical_count + 0)) {
			physical_count = 0;
		}
		calculated_physical = (opening_balance + quantity_received - quantity_dispensed - losses + adjustments - adjustments_neg);
		if(element.attr("class") == "physical_count") {
		 resupply = 0 - physical_count;
		 } else {
		 resupply = 0 - calculated_physical;
		 physical_count = calculated_physical;
		 } 
		//If D-CDRR use reported consumed
	    <?php  if($hide_generate==2){?>
        var quantity_dispensed = parseInt(row_element.find(".aggregated_qty").attr("value"));
        <?php  	} 	?>
		resupply = (quantity_dispensed * 3) - physical_count;
        if ('<?php echo $code; ?>' == 'F-CDRR_units') {
		resupply = (quantity_dispensed * 2) - physical_count;
        }
		resupply = parseInt(resupply);
		if(isNaN(resupply)){ 
			resupply = 0;
		}
		row_element.find('.label-warning').remove();
		row_element.find(".physical_count").attr("value", physical_count);
		row_element.find(".resupply").attr("value", resupply);
	}

	//Function to validate required fields
    function processData(form) {
      var form_selector = "#" + form;
      var validated = $(form_selector).validationEngine('validate');
        
        if(!validated) {
           return false;
        }else{
        	//$(".btn").attr("disabled","disabled");
        	return true;
        }
    }

   function calculateUnits(element)
   {
        var row_element = element.closest("tr");
		var pack_size = parseInt(row_element.find(".pack_size").attr("value"));
		var packs = parseInt(row_element.find(".quantity_dispensed_packs").attr("value"));

		var units = (packs * pack_size);
		row_element.find(".quantity_dispensed").attr("value",units.toFixed());
   }

   function calculatePacks(element)
   {
   	    var row_element = element.closest("tr");
		var pack_size = parseInt(row_element.find(".pack_size").attr("value"));
		var units = parseInt(row_element.find(".quantity_dispensed").attr("value"));

		var packs = (units / pack_size);
		row_element.find(".quantity_dispensed_packs").attr("value",packs.toFixed());

   }
</script>
<style>
	.facility_info {
	width:100%;
	background:#FFF;
	margin-bottom:1em;
	}
	#commodity-table{
		width:63%;
	}
	.regimen-table {
	   width: 35%;   
    }
    .breadcrumb{
    	margin: 0 0 10px;
    }
    .table td {
    	padding:4px;
    }
    .multiselect {
	  text-align: left;
	}
	.multiselect b.caret {
	  position: absolute;
	  top: 14px;
	  right: 8px;
	}
	.multiselect-group {
	  font-weight: bold;
	  text-decoration: underline;
	}
</style>
<!--scripts-->
<script src="<?php echo base_url();?>assets/scripts/bootstrap/bootstrap-multiselect.js"></script>