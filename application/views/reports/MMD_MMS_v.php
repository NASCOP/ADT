<style type="text/css">
	.bold{
		font-weight:bold; 
	}
</style>
<div id="wrapperd">
	<div id="patient_enrolled_content" class="full-content">
		<?php $this->load->view("reports/reports_top_menus_v") ?>
		<h4 style="text-align: center" id='report_title'><?=$report_title;?> As at <?php echo date('M-Y',strtotime($to)); ?> </h4>
		<hr size="1" style="width:80%">	
		<div id="appointment_list">
			<table cellpadding="5" border="1" width="100%" style="border:1px solid #DDD;">
				<thead>
					<tr>
						<th colspan="4">	
							MINISTRY OF HEALTH								
						</th>
					</tr>
					<tr>
						<th  colspan="2">
							FACILITY MONTHLY ARV PATIENT SUMMARY (F-MAPS) Report (MoH 729B)							
						</th>
						<th  colspan="2">
							MoH 729Bb	
						</th>
					</tr>
				</thead>
				<tr>
					<td class="bold">Facility Name</td>
					<td class="bold"><?=$facility['facility_name']?> </td>


					<td class="bold">Facility code</td>
					<td class="bold"><?=$facility['facility_code']?> </td>
				</tr>
				<tr>
					<td class="bold">County</td>
					<td class="bold"><?=$facility['facility_county']?> </td>

					<td class="bold">Sub County</td>
					<td class="bold"><?=$facility['facility_subcounty']?> </td>
				</tr>
				<tr>
					<td class="bold">Period of reporting</td>
					<td class="bold">Begining</td>
					<td> </td>
					<td class="bold"> Ending</td>
				</tr>
				<tr>
					<td></td>
					<td class="bold"><?= $first_day;?></td>
					<td></td>
					<td class="bold"><?= $last_day;?></td>
				</tr>
			</table>
			<table cellpadding="5" border="1" width="100%" style="border:1px solid #DDD;">
				<thead>
					<tr>
						<th>
							Regimen Code
						</th>
						<th>
							ARV or OI Treatment Regimen
						</th>
						<th colspan="12">
							Number of Current Active Patients/Clients on this regimen at the end of this Reporting period						
						</th>
					</tr>	
				</thead>
				<tbody>
					<tr>
						<th colspan="12" bgcolor="grey">
							ADULT ART
						</th>						
					</tr>				
					<tr>
						<!-- Category 2 -->
						<td colspan="2" class="bold">Adult ART 1st Line regimens</td>
						<td class="bold">≤ 1month</td>
						<td class="bold">MMD-2Months</td>
						<td class="bold">MMD-3 Months</td>
						<td class="bold">MMD-4 Months</td>
						<td class="bold">MMD-5 Months</td>
						<td class="bold">MMD-6Months</td>
						<td class="bold">MMD OVER-6Months</td>
						<td class="bold">Total</td>
						<!--<td class="bold">MMS-3 Monthly</td>-->
					</tr>
						<?php foreach ($regimens[2] as $r) { ?>
					<tr>
						<td><?=$r['rs']['regimen_code']?></td>
						<td><?=$r['rs']['regimen_desc']?></td>
						<td><?=$r['regimens']['1MONTH']?></td>
						<td><?=$r['regimens']['2MONTH']?></td>
						<td><?=$r['regimens']['3MONTH']?></td>
						<td><?=$r['regimens']['4MONTH']?></td>
						<td><?=$r['regimens']['5MONTH']?></td>
						<td><?=$r['regimens']['6MONTH']?></td>		
						<td><?=$r['regimens']['OVER6MONTH']?></td>	
						<td><?=$total=array_sum($r['regimens'])-end($r['regimens'])?></td>				
						<!--<td><?=$r['regimens']['MMS3MONTH']?></td>-->											
					</tr><?php } ?>
					<tr>
						<!-- Category 3 -->
						<td colspan="2" class="bold">Adult ART 2nd Line regimens</td>
						<td class="bold">≤ 1month</td>
						<td class="bold">MMD-2Months</td>
						<td class="bold">MMD-3 Months</td>
						<td class="bold">MMD-4 Months</td>
						<td class="bold">MMD-5 Months</td>
						<td class="bold">MMD-6Months</td>
						<td class="bold">MMD OVER-6Months</td>
						<td class="bold">Total</td>
						<!--<td class="bold">MMS-3 Monthly</td>-->
					</tr>
					<?php foreach ($regimens[3] as $r) { ?>
					<tr>
						<td><?=$r['rs']['regimen_code']?></td>
						<td><?=$r['rs']['regimen_desc']?></td>
						<td><?=$r['regimens']['1MONTH']?></td>
						<td><?=$r['regimens']['2MONTH']?></td>
						<td><?=$r['regimens']['3MONTH']?></td>
						<td><?=$r['regimens']['4MONTH']?></td>
						<td><?=$r['regimens']['5MONTH']?></td>
						<td><?=$r['regimens']['6MONTH']?></td>	
						<td><?=$r['regimens']['OVER6MONTH']?></td>		
						<td><?=$total=array_sum($r['regimens'])-end($r['regimens'])?></td>				
						<!--<td><?=$r['regimens']['MMS3MONTH']?></td>-->											
					</tr><?php } ?>
					<tr>
						<!-- Category 12 -->
						<td colspan="2" class="bold">Adult ART 3rd Line regimens</td>
						<td class="bold">≤ 1month</td>
						<td class="bold">MMD-2Months</td>
						<td class="bold">MMD-3 Months</td>
						<td class="bold">MMD-4 Months</td>
						<td class="bold">MMD-5 Months</td>
						<td class="bold">MMD-6Months</td>
						<td class="bold">MMD OVER-6Months</td>
						<td class="bold">Total</td>
						<!--<td class="bold">MMS-3 Monthly</td>-->
					</tr>
					<?php foreach ($regimens[12] as $r) { ?>
					<tr>
						<td><?=$r['rs']['regimen_code']?></td>
						<td><?=$r['rs']['regimen_desc']?></td>
						<td><?=$r['regimens']['1MONTH']?></td>
						<td><?=$r['regimens']['2MONTH']?></td>
						<td><?=$r['regimens']['3MONTH']?></td>
						<td><?=$r['regimens']['4MONTH']?></td>
						<td><?=$r['regimens']['5MONTH']?></td>
						<td><?=$r['regimens']['6MONTH']?></td>	
						<td><?=$r['regimens']['OVER6MONTH']?></td>	
						<td><?=$total=array_sum($r['regimens'])-end($r['regimens'])?></td>					
						<!--<td><?=$r['regimens']['MMS3MONTH']?></td>-->											
					</tr><?php } ?>
					<tr>
						<th colspan="12" bgcolor="grey">
							PAEDIATRIC ART 								
						</th>						
					</tr>				
					<tr>
						<!-- Category 5 -->
						<td colspan="2" class="bold">Paediatric ART 1st Line regimens</td>
						<td class="bold">≤ 1month</td>
						<td class="bold">MMD-2Months</td>
						<td class="bold">MMD-3 Months</td>
						<td class="bold">MMD-4 Months</td>
						<td class="bold">MMD-5 Months</td>
						<td class="bold">MMD-6Months</td>
						<td class="bold">MMD OVER-6Months</td>
						<td class="bold">Total</td>
						<!--<td class="bold">MMS-3 Monthly</td>-->
					</tr>
					<?php foreach ($regimens[5] as $r) { ?>
					<tr>
						<td><?=$r['rs']['regimen_code']?></td>
						<td><?=$r['rs']['regimen_desc']?></td>
						<td><?=$r['regimens']['1MONTH']?></td>
						<td><?=$r['regimens']['2MONTH']?></td>
						<td><?=$r['regimens']['3MONTH']?></td>
						<td><?=$r['regimens']['4MONTH']?></td>
						<td><?=$r['regimens']['5MONTH']?></td>
						<td><?=$r['regimens']['6MONTH']?></td>	
						<td><?=$r['regimens']['OVER6MONTH']?></td>	
						<td><?=$total=array_sum($r['regimens'])-end($r['regimens'])?></td>				
						<!--<td><?=$r['regimens']['MMS3MONTH']?></td>-->									
					</tr><?php } ?>
					<tr>
						<!-- Category 6 -->
						<td colspan="2" class="bold">Paediatric ART 2nd Line regimens</td>
						<td class="bold">≤ 1month</td>
						<td class="bold">MMD-2Months</td>
						<td class="bold">MMD-3 Months</td>
						<td class="bold">MMD-4 Months</td>
						<td class="bold">MMD-5 Months</td>
						<td class="bold">MMD-6Months</td>
						<td class="bold">MMD OVER-6Months</td>
						<td class="bold">Total</td>
						<!--<td class="bold">MMS-3 Monthly</td>-->
					</tr>
					<?php foreach ($regimens[6] as $r) { ?>
					<tr>
						<td><?=$r['rs']['regimen_code']?></td>
						<td><?=$r['rs']['regimen_desc']?></td>
						<td><?=$r['regimens']['1MONTH']?></td>
						<td><?=$r['regimens']['2MONTH']?></td>
						<td><?=$r['regimens']['3MONTH']?></td>
						<td><?=$r['regimens']['4MONTH']?></td>
						<td><?=$r['regimens']['5MONTH']?></td>
						<td><?=$r['regimens']['6MONTH']?></td>		
						<td><?=$r['regimens']['OVER6MONTH']?></td>	
						<td><?=$total=array_sum($r['regimens'])-end($r['regimens'])?></td>			
						<!--<td><?=$r['regimens']['MMS3MONTH']?></td>-->											
					</tr><?php } ?>
						<tr>
						<!-- Category 13 -->
						<td colspan="2" class="bold">Paediatric ART 3rd Line regimens</td>
						<td class="bold">≤ 1month</td>
						<td class="bold">MMD-2Months</td>
						<td class="bold">MMD-3 Months</td>
						<td class="bold">MMD-4 Months</td>
						<td class="bold">MMD-5 Months</td>
						<td class="bold">MMD-6Months</td>
						<td class="bold">MMD OVER-6Months</td>
						<td class="bold">Total</td>
						<!--<td class="bold">MMS-3 Monthly</td>-->
					</tr>					
					<?php foreach ($regimens[13] as $r) { ?>
					<tr>
						<td><?=$r['rs']['regimen_code']?></td>
						<td><?=$r['rs']['regimen_desc']?></td>
						<td><?=$r['regimens']['1MONTH']?></td>
						<td><?=$r['regimens']['2MONTH']?></td>
						<td><?=$r['regimens']['3MONTH']?></td>
						<td><?=$r['regimens']['4MONTH']?></td>
						<td><?=$r['regimens']['5MONTH']?></td>
						<td><?=$r['regimens']['6MONTH']?></td>	
						<td><?=$r['regimens']['OVER6MONTH']?></td>	
						<td><?=$total=array_sum($r['regimens'])-end($r['regimens'])?></td>					
						<!--<td><?=$r['regimens']['MMS3MONTH']?></td>-->											
					</tr><?php } ?>					
					<tr>
						<th colspan="12" bgcolor="grey">PMTCT</th>						
					</tr>
					<tr>
						<!-- Category 1 -->
						<td colspan="2" class="bold">Pregnant women or HEI Mothers</td>
						<td class="bold">≤ 1month</td>
						<td class="bold">MMD-2Months</td>
						<td class="bold">MMD-3 Months</td>
						<td class="bold">MMD-4 Months</td>
						<td class="bold">MMD-5 Months</td>
						<td class="bold">MMD-6Months</td>
						<td class="bold">MMD OVER-6Months</td>
						<td class="bold">Total</td>
						<!--<td class="bold">MMS-3 Monthly</td>-->
					</tr>
					<?php foreach ($regimens[1] as $r) { ?>
					<tr>
						<td><?=$r['rs']['regimen_code']?></td>
						<td><?=$r['rs']['regimen_desc']?></td>
						<td><?=$r['regimens']['1MONTH']?></td>
						<td><?=$r['regimens']['2MONTH']?></td>
						<td><?=$r['regimens']['3MONTH']?></td>
						<td><?=$r['regimens']['4MONTH']?></td>
						<td><?=$r['regimens']['5MONTH']?></td>
						<td><?=$r['regimens']['6MONTH']?></td>		
						<td><?=$r['regimens']['OVER6MONTH']?></td>	
						<td><?=$total=array_sum($r['regimens'])-end($r['regimens'])?></td>				
						<!--<td><?=$r['regimens']['MMS3MONTH']?></td>-->											
					</tr><?php } ?>		
					<tr>
						<!-- Category 10 -->
						<td colspan="2" class="bold">Infants</td>
						<td class="bold">≤ 1month</td>
						<td class="bold">MMD-2Months</td>
						<td class="bold">MMD-3 Months</td>
						<td class="bold">MMD-4 Months</td>
						<td class="bold">MMD-5 Months</td>
						<td class="bold">MMD-6Months</td>
						<td class="bold">MMD OVER-6Months</td>
						<td class="bold">Total</td>
						<!--<td class="bold">MMS-3 Monthly</td>-->											
					</tr>
					<?php foreach ($regimens[10] as $r) { ?>
					<tr>
						<td><?=$r['rs']['regimen_code']?></td>
						<td><?=$r['rs']['regimen_desc']?></td>
						<td><?=$r['regimens']['1MONTH']?></td>
						<td><?=$r['regimens']['2MONTH']?></td>
						<td><?=$r['regimens']['3MONTH']?></td>
						<td><?=$r['regimens']['4MONTH']?></td>
						<td><?=$r['regimens']['5MONTH']?></td>
						<td><?=$r['regimens']['6MONTH']?></td>
						<td><?=$r['regimens']['OVER6MONTH']?></td>	
						<td><?=$total=array_sum($r['regimens'])-end($r['regimens'])?></td>						
						<!--<td><?=$r['regimens']['MMS3MONTH']?></td>-->											
					</tr><?php } ?>					

					<tr>
						<!-- Category 15 -->
						<th colspan="12" bgcolor="grey">PREP</th>						
					</tr>				
					<?php foreach ($regimens[15] as $r) { ?>
					<tr>
						<td><?=$r['rs']['regimen_code']?></td>
						<td><?=$r['rs']['regimen_desc']?></td>
						<td><?=$r['regimens']['1MONTH']?></td>
						<td><?=$r['regimens']['2MONTH']?></td>
						<td><?=$r['regimens']['3MONTH']?></td>
						<td><?=$r['regimens']['4MONTH']?></td>
						<td><?=$r['regimens']['5MONTH']?></td>
						<td><?=$r['regimens']['6MONTH']?></td>	
						<td><?=$r['regimens']['OVER6MONTH']?></td>		
						<td><?=$total=array_sum($r['regimens'])-end($r['regimens'])?></td>				
						<!--<td><?=$r['regimens']['MMS3MONTH']?></td>-->											
					</tr><?php } ?>	
					<tr>
						<th colspan="12" bgcolor="grey">PEP</th>						
					</tr>	
					<tr><!-- Category 8 -->
						<!--<td class="bold">1</td>-->
						<td colspan="12" class="bold">PEP for Adults</td>
					</tr>
					<?php foreach ($regimens[8] as $r) { ?>
					<tr>
						<td><?=$r['rs']['regimen_code']?></td>
						<td><?=$r['rs']['regimen_desc']?></td>
						<td><?=$r['regimens']['1MONTH']?></td>
						<td><?=$r['regimens']['2MONTH']?></td>
						<td><?=$r['regimens']['3MONTH']?></td>
						<td><?=$r['regimens']['4MONTH']?></td>
						<td><?=$r['regimens']['5MONTH']?></td>
						<td><?=$r['regimens']['6MONTH']?></td>		
						<td><?=$r['regimens']['OVER6MONTH']?></td>	
						<td><?=$total=array_sum($r['regimens'])-end($r['regimens'])?></td>				
						<!--<td><?=$r['regimens']['MMS3MONTH']?></td>-->											
					</tr><?php } ?>				
					<tr><!-- Category 9 -->
						<!--<td class="bold">1</td>-->
						<td colspan="12" class="bold">PEP for Children</td>
					</tr>
					<?php foreach ($regimens[9] as $r) { ?>
					<tr>
						<td><?=$r['rs']['regimen_code']?></td>
						<td><?=$r['rs']['regimen_desc']?></td>
						<td><?=$r['regimens']['1MONTH']?></td>
						<td><?=$r['regimens']['2MONTH']?></td>
						<td><?=$r['regimens']['3MONTH']?></td>
						<td><?=$r['regimens']['4MONTH']?></td>
						<td><?=$r['regimens']['5MONTH']?></td>
						<td><?=$r['regimens']['6MONTH']?></td>	
						<td><?=$r['regimens']['OVER6MONTH']?></td>	
						<td><?=$total=array_sum($r['regimens'])-end($r['regimens'])?></td>					
						<!--<td><?=$r['regimens']['MMS3MONTH']?></td>-->											
					</tr><?php } ?>				
						<!-- Category 16 -->
					<!--<tr>
						<th colspan="12" bgcolor="grey">Hepatitis B Patients who are HIV-ve</th>						
					</tr>
					<?php foreach ($regimens[16] as $r) { ?>
					<tr>
						<td><?=$r['rs']['regimen_code']?></td>
						<td><?=$r['rs']['regimen_desc']?></td>
						<td><?=$r['regimens']['1MONTH']?></td>
						<td><?=$r['regimens']['2MONTH']?></td>
						<td><?=$r['regimens']['3MONTH']?></td>
						<td><?=$r['regimens']['4MONTH']?></td>
						<td><?=$r['regimens']['5MONTH']?></td>
						<td><?=$r['regimens']['6MONTH']?></td>		
						<td><?=$r['regimens']['OVER6MONTH']?></td>	
						<td><?=$total=array_sum($r['regimens'])-end($r['regimens'])?></td>				
						td><?=$r['regimens']['MMS3MONTH']?></td>											
					</tr><?php } ?>	
						 Category 11 
					<tr>
						<th colspan="12" bgcolor="grey">Management of Opportunistic infections (OIs)</th>
					</tr>
					<?php foreach ($regimens[11] as $r) { ?>
					<tr>
						<td><?=$r['rs']['regimen_code']?></td>
						<td><?=$r['rs']['regimen_desc']?></td>
						<td><?=$r['regimens']['1MONTH']?></td>
						<td><?=$r['regimens']['2MONTH']?></td>
						<td><?=$r['regimens']['3MONTH']?></td>
						<td><?=$r['regimens']['4MONTH']?></td>
						<td><?=$r['regimens']['5MONTH']?></td>
						<td><?=$r['regimens']['6MONTH']?></td>	
						<td><?=$r['regimens']['OVER6MONTH']?></td>	
						<td><?=$total=array_sum($r['regimens'])-end($r['regimens'])?></td>					
						<td><?=$r['regimens']['MMS3MONTH']?></td>											
					</tr><?php } ?>	-->
						<!-- Category 11 drug.. -->
					<!-- <tr>
						<td>1</td>
						<td colspan="8">Universal prophylaxis</td>						
					</tr> -->								
						<!-- Category 11 drug..isoniazid -->
					<!-- <tr>
						<td>2</td>
						<td colspan="8">IPT</td>						
					</tr> -->								
						<!-- Category 11 drug..flucon -->
					<!-- <tr>
						<td>3</td>
						<td colspan="8">Fluconazole (treatment & prophylaxis)</td>						
					</tr> -->
				</tbody>
			</table>
		</div>
	</div>
</div>	

