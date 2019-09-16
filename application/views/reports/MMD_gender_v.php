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
						<th colspan="28">
							Number of Current Active Patients/Clients on this regimen at the end of this Reporting period						
						</th>
					</tr>	
				</thead>
				<tbody>
					<tr>
						<th colspan="28" bgcolor="grey">
							ADULT ART
						</th>						
					</tr>				
					<tr>
						<!-- Category 2 -->
						<td colspan="2" class="bold">Adult ART 1st Line regimens</td>
						<td colspan="2" class="bold"><1</td>
						<td colspan="2" class="bold">1-4</td>
						<td colspan="2" class="bold">5-9</td>
						<td colspan="2" class="bold">10-14</td>
						<td colspan="2" class="bold">15-19</td>
						<td colspan="2" class="bold">20-24</td>
						<td colspan="2" class="bold">25-29</td>
						<td colspan="2" class="bold">30-34</td>
						<td colspan="2" class="bold">35-39</td>
						<td colspan="2" class="bold">40-44</td>
						<td colspan="2" class="bold">45-49</td>
						<td colspan="2" class="bold">50+</td>
						<td colspan="2" class="bold"></td>
					</tr>
					<tr>
						<td colspan="2" class="bold"></td>
						<td class="bold">F</td>
						<td class="bold">M</td> 
						<td class="bold">F</td>
						<td class="bold">M</td>
						<td class="bold">F</td>
						<td class="bold">M</td>
						<td class="bold">F</td>
						<td class="bold">M</td>
						<td class="bold">F</td>
						<td class="bold">M</td>
						<td class="bold">F</td>
						<td class="bold">M</td>
						<td class="bold">F</td>
						<td class="bold">M</td>
						<td class="bold">F</td>
						<td class="bold">M</td>
						<td class="bold">F</td>
						<td class="bold">M</td>
						<td class="bold">F</td>
						<td class="bold">M</td>
						<td class="bold">F</td>
						<td class="bold">M</td>
						<td class="bold">F</td>
						<td class="bold">M</td>
						<td class="bold">Total</td>
					</tr>
						<?php foreach ($regimens[2] as $r) { ?>
					<tr>
						<td><?=$r['rs']['regimen_code']?></td>
						<td><?=$r['rs']['regimen_desc']?></td>
						<td><?=$r['regimens']['1_female']?></td>
						<td><?=$r['regimens']['1_male']?></td>
						<td><?=$r['regimens']['1_4_female']?></td>
					    <td><?=$r['regimens']['1_4_male']?></td>
						<td><?=$r['regimens']['5_9_female']?></td>
						<td><?=$r['regimens']['5_9_male']?></td>
						<td><?=$r['regimens']['10_14_female']?></td>
					    <td><?=$r['regimens']['10_14_male']?></td>
					    <td><?=$r['regimens']['15_19_female']?></td>
						<td><?=$r['regimens']['15_19_male']?></td>
						<td><?=$r['regimens']['20_24_female']?></td>
					    <td><?=$r['regimens']['20_24_male']?></td>
						<td><?=$r['regimens']['25_29_female']?></td>	
						<td><?=$r['regimens']['25_29_male']?></td>
						<td><?=$r['regimens']['30_34_female']?></td>
					    <td><?=$r['regimens']['30_34_male']?></td>
					    <td><?=$r['regimens']['35_39_female']?></td>
						<td><?=$r['regimens']['35_39_male']?></td>
						<td><?=$r['regimens']['40_44_female']?></td>
					    <td><?=$r['regimens']['40_44_male']?></td>
						<td><?=$r['regimens']['45_49_female']?></td>		
						<td><?=$r['regimens']['45_49_male']?></td>
						<td><?=$r['regimens']['50_female']?></td>		
						<td><?=$r['regimens']['50_male']?></td>		
						<td><?=$total=array_sum($r['regimens'])?></td>					
					</tr><?php } ?>
					<tr>
						<!-- Category 3 -->
						<td colspan="2" class="bold">Adult ART 2nd Line regimens</td>
						<td colspan="2" class="bold"><1</td>
						<td colspan="2" class="bold">1-4</td>
						<td colspan="2" class="bold">5-9</td>
						<td colspan="2" class="bold">10-14</td>
						<td colspan="2" class="bold">15-19</td>
						<td colspan="2" class="bold">20-24</td>
						<td colspan="2" class="bold">25-29</td>
						<td colspan="2" class="bold">30-34</td>
						<td colspan="2" class="bold">35-39</td>
						<td colspan="2" class="bold">40-44</td>
						<td colspan="2" class="bold">45-49</td>
						<td colspan="2" class="bold">50+</td>
						<td colspan="2" class="bold"></td>
					</tr>
					<tr>
						<td colspan="2" class="bold"></td>
						<td class="bold">F</td>
						<td class="bold">M</td>
						<td class="bold">F</td>
						<td class="bold">M</td>
						<td class="bold">F</td>
						<td class="bold">M</td>
						<td class="bold">F</td>
						<td class="bold">M</td>
						<td class="bold">F</td>
						<td class="bold">M</td>
						<td class="bold">F</td>
						<td class="bold">M</td>
						<td class="bold">F</td>
						<td class="bold">M</td>
						<td class="bold">F</td>
						<td class="bold">M</td>
						<td class="bold">F</td>
						<td class="bold">M</td>
						<td class="bold">F</td>
						<td class="bold">M</td>
						<td class="bold">F</td>
						<td class="bold">M</td>
						<td class="bold">F</td>
						<td class="bold">M</td>
						<td class="bold">Total</td>
					</tr>
					<?php foreach ($regimens[3] as $r) { ?>
					<tr>
						<td><?=$r['rs']['regimen_code']?></td>
						<td><?=$r['rs']['regimen_desc']?></td>
						<td><?=$r['regimens']['1_female']?></td>
						<td><?=$r['regimens']['1_male']?></td>
						<td><?=$r['regimens']['1_4_female']?></td>
					    <td><?=$r['regimens']['1_4_male']?></td>
						<td><?=$r['regimens']['5_9_female']?></td>
						<td><?=$r['regimens']['5_9_male']?></td>
						<td><?=$r['regimens']['10_14_female']?></td>
					    <td><?=$r['regimens']['10_14_male']?></td>
					    <td><?=$r['regimens']['15_19_female']?></td>
						<td><?=$r['regimens']['15_19_male']?></td>
						<td><?=$r['regimens']['20_24_female']?></td>
					    <td><?=$r['regimens']['20_24_male']?></td>
						<td><?=$r['regimens']['25_29_female']?></td>	
						<td><?=$r['regimens']['25_29_male']?></td>
						<td><?=$r['regimens']['30_34_female']?></td>
					    <td><?=$r['regimens']['30_34_male']?></td>
					    <td><?=$r['regimens']['35_39_female']?></td>
						<td><?=$r['regimens']['35_39_male']?></td>
						<td><?=$r['regimens']['40_44_female']?></td>
					    <td><?=$r['regimens']['40_44_male']?></td>
						<td><?=$r['regimens']['45_49_female']?></td>		
						<td><?=$r['regimens']['45_49_male']?></td>
						<td><?=$r['regimens']['50_female']?></td>		
						<td><?=$r['regimens']['50_male']?></td>		
						<td><?=$total=array_sum($r['regimens'])?></td>										
					</tr><?php } ?>
					<tr>
						<!-- Category 12 -->
						<td colspan="2" class="bold">Adult ART 3rd Line regimens</td>
						<td colspan="2" class="bold"><1</td>
						<td colspan="2" class="bold">1-4</td>
						<td colspan="2" class="bold">5-9</td>
						<td colspan="2" class="bold">10-14</td>
						<td colspan="2" class="bold">15-19</td>
						<td colspan="2" class="bold">20-24</td>
						<td colspan="2" class="bold">25-29</td>
						<td colspan="2" class="bold">30-34</td>
						<td colspan="2" class="bold">35-39</td>
						<td colspan="2" class="bold">40-44</td>
						<td colspan="2" class="bold">45-49</td>
						<td colspan="2" class="bold">50+</td>
						<td colspan="2" class="bold"></td>
					</tr>
						<tr>
						<td colspan="2" class="bold"></td>
						<td class="bold">F</td>
						<td class="bold">M</td>
						<td class="bold">F</td>
						<td class="bold">M</td>
						<td class="bold">F</td>
						<td class="bold">M</td>
						<td class="bold">F</td>
						<td class="bold">M</td>
						<td class="bold">F</td>
						<td class="bold">M</td>
						<td class="bold">F</td>
						<td class="bold">M</td>
						<td class="bold">F</td>
						<td class="bold">M</td>
						<td class="bold">F</td>
						<td class="bold">M</td>
						<td class="bold">F</td>
						<td class="bold">M</td>
						<td class="bold">F</td>
						<td class="bold">M</td>
						<td class="bold">F</td>
						<td class="bold">M</td>
						<td class="bold">F</td>
						<td class="bold">M</td>
						<td class="bold">Total</td>
					</tr>
					<?php foreach ($regimens[12] as $r) { ?>
					<tr>
						<td><?=$r['rs']['regimen_code']?></td>
						<td><?=$r['rs']['regimen_desc']?></td>
						<td><?=$r['regimens']['1_female']?></td>
						<td><?=$r['regimens']['1_male']?></td>
						<td><?=$r['regimens']['1_4_female']?></td>
					    <td><?=$r['regimens']['1_4_male']?></td>
						<td><?=$r['regimens']['5_9_female']?></td>
						<td><?=$r['regimens']['5_9_male']?></td>
						<td><?=$r['regimens']['10_14_female']?></td>
					    <td><?=$r['regimens']['10_14_male']?></td>
					    <td><?=$r['regimens']['15_19_female']?></td>
						<td><?=$r['regimens']['15_19_male']?></td>
						<td><?=$r['regimens']['20_24_female']?></td>
					    <td><?=$r['regimens']['20_24_male']?></td>
						<td><?=$r['regimens']['25_29_female']?></td>	
						<td><?=$r['regimens']['25_29_male']?></td>
						<td><?=$r['regimens']['30_34_female']?></td>
					    <td><?=$r['regimens']['30_34_male']?></td>
					    <td><?=$r['regimens']['35_39_female']?></td>
						<td><?=$r['regimens']['35_39_male']?></td>
						<td><?=$r['regimens']['40_44_female']?></td>
					    <td><?=$r['regimens']['40_44_male']?></td>
						<td><?=$r['regimens']['45_49_female']?></td>		
						<td><?=$r['regimens']['45_49_male']?></td>
						<td><?=$r['regimens']['50_female']?></td>		
						<td><?=$r['regimens']['50_male']?></td>	
						<td><?=$total=array_sum($r['regimens'])?></td>											
					</tr><?php } ?>
					<tr>
						<th colspan="28" bgcolor="grey">
							PAEDIATRIC ART 								
						</th>						
					</tr>				
					<tr>
						<!-- Category 5 -->
						<td colspan="2" class="bold">Paediatric ART 1st Line regimens</td>
						<td colspan="2" class="bold"><1</td>
						<td colspan="2" class="bold">1-4</td>
						<td colspan="2" class="bold">5-9</td>
						<td colspan="2" class="bold">10-14</td>
						<td colspan="2" class="bold">15-19</td>
						<td colspan="2" class="bold">20-24</td>
						<td colspan="2" class="bold">25-29</td>
						<td colspan="2" class="bold">30-34</td>
						<td colspan="2" class="bold">35-39</td>
						<td colspan="2" class="bold">40-44</td>
						<td colspan="2" class="bold">45-49</td>
						<td colspan="2" class="bold">50+</td>
						<td colspan="2" class="bold"></td>
					</tr>
						<tr>
						<td colspan="2" class="bold"></td>
						<td class="bold">F</td>
						<td class="bold">M</td>
						<td class="bold">F</td>
						<td class="bold">M</td>
						<td class="bold">F</td>
						<td class="bold">M</td>
						<td class="bold">F</td>
						<td class="bold">M</td>
						<td class="bold">F</td>
						<td class="bold">M</td>
						<td class="bold">F</td>
						<td class="bold">M</td>
						<td class="bold">F</td>
						<td class="bold">M</td>
						<td class="bold">F</td>
						<td class="bold">M</td>
						<td class="bold">F</td>
						<td class="bold">M</td>
						<td class="bold">F</td>
						<td class="bold">M</td>
						<td class="bold">F</td>
						<td class="bold">M</td>
						<td class="bold">F</td>
						<td class="bold">M</td>
						<td class="bold">Total</td>
					</tr>
					<?php foreach ($regimens[5] as $r) { ?>
					<tr>
						<td><?=$r['rs']['regimen_code']?></td>
						<td><?=$r['rs']['regimen_desc']?></td>
						<td><?=$r['regimens']['1_female']?></td>
						<td><?=$r['regimens']['1_male']?></td>
						<td><?=$r['regimens']['1_4_female']?></td>
					    <td><?=$r['regimens']['1_4_male']?></td>
						<td><?=$r['regimens']['5_9_female']?></td>
						<td><?=$r['regimens']['5_9_male']?></td>
						<td><?=$r['regimens']['10_14_female']?></td>
					    <td><?=$r['regimens']['10_14_male']?></td>
					    <td><?=$r['regimens']['15_19_female']?></td>
						<td><?=$r['regimens']['15_19_male']?></td>
						<td><?=$r['regimens']['20_24_female']?></td>
					    <td><?=$r['regimens']['20_24_male']?></td>
						<td><?=$r['regimens']['25_29_female']?></td>	
						<td><?=$r['regimens']['25_29_male']?></td>
						<td><?=$r['regimens']['30_34_female']?></td>
					    <td><?=$r['regimens']['30_34_male']?></td>
					    <td><?=$r['regimens']['35_39_female']?></td>
						<td><?=$r['regimens']['35_39_male']?></td>
						<td><?=$r['regimens']['40_44_female']?></td>
					    <td><?=$r['regimens']['40_44_male']?></td>
						<td><?=$r['regimens']['45_49_female']?></td>		
						<td><?=$r['regimens']['45_49_male']?></td>
						<td><?=$r['regimens']['50_female']?></td>		
						<td><?=$r['regimens']['50_male']?></td>	
						<td><?=$total=array_sum($r['regimens'])?></td>	

					</tr><?php } ?>
					<tr>
						<!-- Category 6 -->
						<td colspan="2" class="bold">Paediatric ART 2nd Line regimens</td>
						<td colspan="2" class="bold"><1</td>
						<td colspan="2" class="bold">1-4</td>
						<td colspan="2" class="bold">5-9</td>
						<td colspan="2" class="bold">10-14</td>
						<td colspan="2" class="bold">15-19</td>
						<td colspan="2" class="bold">20-24</td>
						<td colspan="2" class="bold">25-29</td>
						<td colspan="2" class="bold">30-34</td>
						<td colspan="2" class="bold">35-39</td>
						<td colspan="2" class="bold">40-44</td>
						<td colspan="2" class="bold">45-49</td>
						<td colspan="2" class="bold">50+</td>
						<td colspan="2" class="bold"></td>
					</tr>
						<tr>
						<td colspan="2" class="bold"></td>
						<td class="bold">F</td>
						<td class="bold">M</td>
						<td class="bold">F</td>
						<td class="bold">M</td>
						<td class="bold">F</td>
						<td class="bold">M</td>
						<td class="bold">F</td>
						<td class="bold">M</td>
						<td class="bold">F</td>
						<td class="bold">M</td>
						<td class="bold">F</td>
						<td class="bold">M</td>
						<td class="bold">F</td>
						<td class="bold">M</td>
						<td class="bold">F</td>
						<td class="bold">M</td>
						<td class="bold">F</td>
						<td class="bold">M</td>
						<td class="bold">F</td>
						<td class="bold">M</td>
						<td class="bold">F</td>
						<td class="bold">M</td>
						<td class="bold">F</td>
						<td class="bold">M</td>
						<td class="bold">Total</td>
					</tr>
					<?php foreach ($regimens[6] as $r) { ?>
					<tr>
						<td><?=$r['rs']['regimen_code']?></td>
						<td><?=$r['rs']['regimen_desc']?></td>
						<td><?=$r['regimens']['1_female']?></td>
						<td><?=$r['regimens']['1_male']?></td>
						<td><?=$r['regimens']['1_4_female']?></td>
					    <td><?=$r['regimens']['1_4_male']?></td>
						<td><?=$r['regimens']['5_9_female']?></td>
						<td><?=$r['regimens']['5_9_male']?></td>
						<td><?=$r['regimens']['10_14_female']?></td>
					    <td><?=$r['regimens']['10_14_male']?></td>
					    <td><?=$r['regimens']['15_19_female']?></td>
						<td><?=$r['regimens']['15_19_male']?></td>
						<td><?=$r['regimens']['20_24_female']?></td>
					    <td><?=$r['regimens']['20_24_male']?></td>
						<td><?=$r['regimens']['25_29_female']?></td>	
						<td><?=$r['regimens']['25_29_male']?></td>
						<td><?=$r['regimens']['30_34_female']?></td>
					    <td><?=$r['regimens']['30_34_male']?></td>
					    <td><?=$r['regimens']['35_39_female']?></td>
						<td><?=$r['regimens']['35_39_male']?></td>
						<td><?=$r['regimens']['40_44_female']?></td>
					    <td><?=$r['regimens']['40_44_male']?></td>
						<td><?=$r['regimens']['45_49_female']?></td>		
						<td><?=$r['regimens']['45_49_male']?></td>
						<td><?=$r['regimens']['50_female']?></td>		
						<td><?=$r['regimens']['50_male']?></td>	
						<td><?=$total=array_sum($r['regimens'])?></td>											
					</tr><?php } ?>
						<tr>
						<!-- Category 13 -->
						<td colspan="2" class="bold">Paediatric ART 3rd Line regimens</td>
						<td colspan="2" class="bold"><1</td>
						<td colspan="2" class="bold">1-4</td>
						<td colspan="2" class="bold">5-9</td>
						<td colspan="2" class="bold">10-14</td>
						<td colspan="2" class="bold">15-19</td>
						<td colspan="2" class="bold">20-24</td>
						<td colspan="2" class="bold">25-29</td>
						<td colspan="2" class="bold">30-34</td>
						<td colspan="2" class="bold">35-39</td>
						<td colspan="2" class="bold">40-44</td>
						<td colspan="2" class="bold">45-49</td>
						<td colspan="2" class="bold">50+</td>
						<td colspan="2" class="bold"></td>
					</tr>					
					<?php foreach ($regimens[13] as $r) { ?>
					<tr>
						<td><?=$r['rs']['regimen_code']?></td>
						<td><?=$r['rs']['regimen_desc']?></td>
						<td><?=$r['regimens']['1_female']?></td>
						<td><?=$r['regimens']['1_male']?></td>
						<td><?=$r['regimens']['1_4_female']?></td>
					    <td><?=$r['regimens']['1_4_male']?></td>
						<td><?=$r['regimens']['5_9_female']?></td>
						<td><?=$r['regimens']['5_9_male']?></td>
						<td><?=$r['regimens']['10_14_female']?></td>
					    <td><?=$r['regimens']['10_14_male']?></td>
					    <td><?=$r['regimens']['15_19_female']?></td>
						<td><?=$r['regimens']['15_19_male']?></td>
						<td><?=$r['regimens']['20_24_female']?></td>
					    <td><?=$r['regimens']['20_24_male']?></td>
						<td><?=$r['regimens']['25_29_female']?></td>	
						<td><?=$r['regimens']['25_29_male']?></td>
						<td><?=$r['regimens']['30_34_female']?></td>
					    <td><?=$r['regimens']['30_34_male']?></td>
					    <td><?=$r['regimens']['35_39_female']?></td>
						<td><?=$r['regimens']['35_39_male']?></td>
						<td><?=$r['regimens']['40_44_female']?></td>
					    <td><?=$r['regimens']['40_44_male']?></td>
						<td><?=$r['regimens']['45_49_female']?></td>		
						<td><?=$r['regimens']['45_49_male']?></td>
						<td><?=$r['regimens']['50_female']?></td>		
						<td><?=$r['regimens']['50_male']?></td>		
						<td><?=$total=array_sum($r['regimens'])?></td>										
					</tr><?php } ?>					
					<tr>
						<th colspan="28" bgcolor="grey">PMTCT</th>						
					</tr>
					<tr>
						<!-- Category 1 -->
						<td colspan="2" class="bold">Pregnant women or HEI Mothers</td>
						<td colspan="2" class="bold"><1</td>
						<td colspan="2" class="bold">1-4</td>
						<td colspan="2" class="bold">5-9</td>
						<td colspan="2" class="bold">10-14</td>
						<td colspan="2" class="bold">15-19</td>
						<td colspan="2" class="bold">20-24</td>
						<td colspan="2" class="bold">25-29</td>
						<td colspan="2" class="bold">30-34</td>
						<td colspan="2" class="bold">35-39</td>
						<td colspan="2" class="bold">40-44</td>
						<td colspan="2" class="bold">45-49</td>
						<td colspan="2" class="bold">50+</td>
						<td colspan="2" class="bold"></td>
					</tr>
						<tr>
						<td colspan="2" class="bold"></td>
						<td class="bold">F</td>
						<td class="bold">M</td>
						<td class="bold">F</td>
						<td class="bold">M</td>
						<td class="bold">F</td>
						<td class="bold">M</td>
						<td class="bold">F</td>
						<td class="bold">M</td>
						<td class="bold">F</td>
						<td class="bold">M</td>
						<td class="bold">F</td>
						<td class="bold">M</td>
						<td class="bold">F</td>
						<td class="bold">M</td>
						<td class="bold">F</td>
						<td class="bold">M</td>
						<td class="bold">F</td>
						<td class="bold">M</td>
						<td class="bold">F</td>
						<td class="bold">M</td>
						<td class="bold">F</td>
						<td class="bold">M</td>
						<td class="bold">F</td>
						<td class="bold">M</td>
						<td class="bold">Total</td>
					</tr>
					<?php foreach ($regimens[1] as $r) { ?>
					<tr>
						<td><?=$r['rs']['regimen_code']?></td>
						<td><?=$r['rs']['regimen_desc']?></td>
						<td><?=$r['regimens']['1_female']?></td>
						<td><?=$r['regimens']['1_male']?></td>
						<td><?=$r['regimens']['1_4_female']?></td>
					    <td><?=$r['regimens']['1_4_male']?></td>
						<td><?=$r['regimens']['5_9_female']?></td>
						<td><?=$r['regimens']['5_9_male']?></td>
						<td><?=$r['regimens']['10_14_female']?></td>
					    <td><?=$r['regimens']['10_14_male']?></td>
					    <td><?=$r['regimens']['15_19_female']?></td>
						<td><?=$r['regimens']['15_19_male']?></td>
						<td><?=$r['regimens']['20_24_female']?></td>
					    <td><?=$r['regimens']['20_24_male']?></td>
						<td><?=$r['regimens']['25_29_female']?></td>	
						<td><?=$r['regimens']['25_29_male']?></td>
						<td><?=$r['regimens']['30_34_female']?></td>
					    <td><?=$r['regimens']['30_34_male']?></td>
					    <td><?=$r['regimens']['35_39_female']?></td>
						<td><?=$r['regimens']['35_39_male']?></td>
						<td><?=$r['regimens']['40_44_female']?></td>
					    <td><?=$r['regimens']['40_44_male']?></td>
						<td><?=$r['regimens']['45_49_female']?></td>		
						<td><?=$r['regimens']['45_49_male']?></td>
						<td><?=$r['regimens']['50_female']?></td>		
						<td><?=$r['regimens']['50_male']?></td>		
						<td><?=$total=array_sum($r['regimens'])?></td>										
					</tr><?php } ?>		
					<tr>
						<!-- Category 10 -->
						<td colspan="2" class="bold">Infants</td>
						<td colspan="2" class="bold"><1</td>
						<td colspan="2" class="bold">1-4</td>
						<td colspan="2" class="bold">5-9</td>
						<td colspan="2" class="bold">10-14</td>
						<td colspan="2" class="bold">15-19</td>
						<td colspan="2" class="bold">20-24</td>
						<td colspan="2" class="bold">25-29</td>
						<td colspan="2" class="bold">30-34</td>
						<td colspan="2" class="bold">35-39</td>
						<td colspan="2" class="bold">40-44</td>
						<td colspan="2" class="bold">45-49</td>
						<td colspan="2" class="bold">50+</td>		
						<td colspan="2" class="bold"></td>									
					</tr>
						<tr>
						<td colspan="2" class="bold"></td>
						<td class="bold">F</td>
						<td class="bold">M</td>
						<td class="bold">F</td>
						<td class="bold">M</td>
						<td class="bold">F</td>
						<td class="bold">M</td>
						<td class="bold">F</td>
						<td class="bold">M</td>
						<td class="bold">F</td>
						<td class="bold">M</td>
						<td class="bold">F</td>
						<td class="bold">M</td>
						<td class="bold">F</td>
						<td class="bold">M</td>
						<td class="bold">F</td>
						<td class="bold">M</td>
						<td class="bold">F</td>
						<td class="bold">M</td>
						<td class="bold">F</td>
						<td class="bold">M</td>
						<td class="bold">F</td>
						<td class="bold">M</td>
						<td class="bold">F</td>
						<td class="bold">M</td>
						<td class="bold">Total</td>
					</tr>
					<?php foreach ($regimens[10] as $r) { ?>
					<tr>
						<td><?=$r['rs']['regimen_code']?></td>
						<td><?=$r['rs']['regimen_desc']?></td>
						<td><?=$r['regimens']['1_female']?></td>
						<td><?=$r['regimens']['1_male']?></td>
						<td><?=$r['regimens']['1_4_female']?></td>
					    <td><?=$r['regimens']['1_4_male']?></td>
						<td><?=$r['regimens']['5_9_female']?></td>
						<td><?=$r['regimens']['5_9_male']?></td>
						<td><?=$r['regimens']['10_14_female']?></td>
					    <td><?=$r['regimens']['10_14_male']?></td>
					    <td><?=$r['regimens']['15_19_female']?></td>
						<td><?=$r['regimens']['15_19_male']?></td>
						<td><?=$r['regimens']['20_24_female']?></td>
					    <td><?=$r['regimens']['20_24_male']?></td>
						<td><?=$r['regimens']['25_29_female']?></td>	
						<td><?=$r['regimens']['25_29_male']?></td>
						<td><?=$r['regimens']['30_34_female']?></td>
					    <td><?=$r['regimens']['30_34_male']?></td>
					    <td><?=$r['regimens']['35_39_female']?></td>
						<td><?=$r['regimens']['35_39_male']?></td>
						<td><?=$r['regimens']['40_44_female']?></td>
					    <td><?=$r['regimens']['40_44_male']?></td>
						<td><?=$r['regimens']['45_49_female']?></td>		
						<td><?=$r['regimens']['45_49_male']?></td>
						<td><?=$r['regimens']['50_female']?></td>		
						<td><?=$r['regimens']['50_male']?></td>	
						<td><?=$total=array_sum($r['regimens'])?></td>												
					</tr><?php } ?>					

					<tr>
						<!-- Category 15 -->
						<th colspan="28" bgcolor="grey">PREP</th>						
					</tr>				
					<?php foreach ($regimens[15] as $r) { ?>
					<tr>
						<td><?=$r['rs']['regimen_code']?></td>
						<td><?=$r['rs']['regimen_desc']?></td>
						<td><?=$r['regimens']['1_female']?></td>
						<td><?=$r['regimens']['1_male']?></td>
						<td><?=$r['regimens']['1_4_female']?></td>
					    <td><?=$r['regimens']['1_4_male']?></td>
						<td><?=$r['regimens']['5_9_female']?></td>
						<td><?=$r['regimens']['5_9_male']?></td>
						<td><?=$r['regimens']['10_14_female']?></td>
					    <td><?=$r['regimens']['10_14_male']?></td>
					    <td><?=$r['regimens']['15_19_female']?></td>
						<td><?=$r['regimens']['15_19_male']?></td>
						<td><?=$r['regimens']['20_24_female']?></td>
					    <td><?=$r['regimens']['20_24_male']?></td>
						<td><?=$r['regimens']['25_29_female']?></td>	
						<td><?=$r['regimens']['25_29_male']?></td>
						<td><?=$r['regimens']['30_34_female']?></td>
					    <td><?=$r['regimens']['30_34_male']?></td>
					    <td><?=$r['regimens']['35_39_female']?></td>
						<td><?=$r['regimens']['35_39_male']?></td>
						<td><?=$r['regimens']['40_44_female']?></td>
					    <td><?=$r['regimens']['40_44_male']?></td>
						<td><?=$r['regimens']['45_49_female']?></td>		
						<td><?=$r['regimens']['45_49_male']?></td>
						<td><?=$r['regimens']['50_female']?></td>		
						<td><?=$r['regimens']['50_male']?></td>	
						<td><?=$total=array_sum($r['regimens'])?></td>											
					</tr><?php } ?>	
					<tr>
						<th colspan="28" bgcolor="grey">PEP</th>						
					</tr>	
					<tr><!-- Category 8 -->
						<!--<td class="bold">1</td>-->
						<td colspan="28" class="bold">PEP for Adults</td>
					</tr>
					<?php foreach ($regimens[8] as $r) { ?>
					<tr>
						<td><?=$r['rs']['regimen_code']?></td>
						<td><?=$r['rs']['regimen_desc']?></td>
						<td><?=$r['regimens']['1_female']?></td>
						<td><?=$r['regimens']['1_male']?></td>
						<td><?=$r['regimens']['1_4_female']?></td>
					    <td><?=$r['regimens']['1_4_male']?></td>
						<td><?=$r['regimens']['5_9_female']?></td>
						<td><?=$r['regimens']['5_9_male']?></td>
						<td><?=$r['regimens']['10_14_female']?></td>
					    <td><?=$r['regimens']['10_14_male']?></td>
					    <td><?=$r['regimens']['15_19_female']?></td>
						<td><?=$r['regimens']['15_19_male']?></td>
						<td><?=$r['regimens']['20_24_female']?></td>
					    <td><?=$r['regimens']['20_24_male']?></td>
						<td><?=$r['regimens']['25_29_female']?></td>	
						<td><?=$r['regimens']['25_29_male']?></td>
						<td><?=$r['regimens']['30_34_female']?></td>
					    <td><?=$r['regimens']['30_34_male']?></td>
					    <td><?=$r['regimens']['35_39_female']?></td>
						<td><?=$r['regimens']['35_39_male']?></td>
						<td><?=$r['regimens']['40_44_female']?></td>
					    <td><?=$r['regimens']['40_44_male']?></td>
						<td><?=$r['regimens']['45_49_female']?></td>		
						<td><?=$r['regimens']['45_49_male']?></td>
						<td><?=$r['regimens']['50_female']?></td>		
						<td><?=$r['regimens']['50_male']?></td>		
						<td><?=$total=array_sum($r['regimens'])?></td>											
					</tr><?php } ?>				
					<tr><!-- Category 9 -->
						<!--<td class="bold">1</td>-->
						<td colspan="28" class="bold">PEP for Children</td>
					</tr>
					<?php foreach ($regimens[9] as $r) { ?>
						<td><?=$r['rs']['regimen_code']?></td>
						<td><?=$r['rs']['regimen_desc']?></td>
						<td><?=$r['regimens']['1_female']?></td>
						<td><?=$r['regimens']['1_male']?></td>
						<td><?=$r['regimens']['1_4_female']?></td>
					    <td><?=$r['regimens']['1_4_male']?></td>
						<td><?=$r['regimens']['5_9_female']?></td>
						<td><?=$r['regimens']['5_9_male']?></td>
						<td><?=$r['regimens']['10_14_female']?></td>
					    <td><?=$r['regimens']['10_14_male']?></td>
					    <td><?=$r['regimens']['15_19_female']?></td>
						<td><?=$r['regimens']['15_19_male']?></td>
						<td><?=$r['regimens']['20_24_female']?></td>
					    <td><?=$r['regimens']['20_24_male']?></td>
						<td><?=$r['regimens']['25_29_female']?></td>	
						<td><?=$r['regimens']['25_29_male']?></td>
						<td><?=$r['regimens']['30_34_female']?></td>
					    <td><?=$r['regimens']['30_34_male']?></td>
					    <td><?=$r['regimens']['35_39_female']?></td>
						<td><?=$r['regimens']['35_39_male']?></td>
						<td><?=$r['regimens']['40_44_female']?></td>
					    <td><?=$r['regimens']['40_44_male']?></td>
						<td><?=$r['regimens']['45_49_female']?></td>		
						<td><?=$r['regimens']['45_49_male']?></td>
						<td><?=$r['regimens']['50_female']?></td>		
						<td><?=$r['regimens']['50_male']?></td>
						<td><?=$total=array_sum($r['regimens'])?></td>	
					</tr><?php } ?>				
						<!-- Category 16 -->
					<tr>
						<th colspan="28" bgcolor="grey">Hepatitis B Patients who are HIV-ve</th>						
					</tr>
					<?php foreach ($regimens[16] as $r) { ?>
					<tr>
						<td><?=$r['rs']['regimen_code']?></td>
						<td><?=$r['rs']['regimen_desc']?></td>
						<td><?=$r['regimens']['1_female']?></td>
						<td><?=$r['regimens']['1_male']?></td>
						<td><?=$r['regimens']['1_4_female']?></td>
					    <td><?=$r['regimens']['1_4_male']?></td>
						<td><?=$r['regimens']['5_9_female']?></td>
						<td><?=$r['regimens']['5_9_male']?></td>
						<td><?=$r['regimens']['10_14_female']?></td>
					    <td><?=$r['regimens']['10_14_male']?></td>
					    <td><?=$r['regimens']['15_19_female']?></td>
						<td><?=$r['regimens']['15_19_male']?></td>
						<td><?=$r['regimens']['20_24_female']?></td>
					    <td><?=$r['regimens']['20_24_male']?></td>
						<td><?=$r['regimens']['25_29_female']?></td>	
						<td><?=$r['regimens']['25_29_male']?></td>
						<td><?=$r['regimens']['30_34_female']?></td>
					    <td><?=$r['regimens']['30_34_male']?></td>
					    <td><?=$r['regimens']['35_39_female']?></td>
						<td><?=$r['regimens']['35_39_male']?></td>
						<td><?=$r['regimens']['40_44_female']?></td>
					    <td><?=$r['regimens']['40_44_male']?></td>
						<td><?=$r['regimens']['45_49_female']?></td>		
						<td><?=$r['regimens']['45_49_male']?></td>
						<td><?=$r['regimens']['50_female']?></td>		
						<td><?=$r['regimens']['50_male']?></td>			
						<td><?=$total=array_sum($r['regimens'])?></td>										
					</tr><?php } ?>	
						<!-- Category 11 -->
					<!--<tr>
						<th colspan="28" bgcolor="grey">Management of Opportunistic infections (OIs)</th>
					</tr>
					<?php foreach ($regimens[11] as $r) { ?>
					<tr>
						<td><?=$r['rs']['regimen_code']?></td>
						<td><?=$r['rs']['regimen_desc']?></td>
						<td><?=$r['regimens']['1_female']?></td>
						<td><?=$r['regimens']['1_male']?></td>
						<td><?=$r['regimens']['1_4_female']?></td>
					    <td><?=$r['regimens']['1_4_male']?></td>
						<td><?=$r['regimens']['5_9_female']?></td>
						<td><?=$r['regimens']['5_9_male']?></td>
						<td><?=$r['regimens']['10_14_female']?></td>
					    <td><?=$r['regimens']['10_14_male']?></td>
					    <td><?=$r['regimens']['15_19_female']?></td>
						<td><?=$r['regimens']['15_19_male']?></td>
						<td><?=$r['regimens']['20_24_female']?></td>
					    <td><?=$r['regimens']['20_24_male']?></td>
						<td><?=$r['regimens']['25_29_female']?></td>	
						<td><?=$r['regimens']['25_29_male']?></td>
						<td><?=$r['regimens']['30_34_female']?></td>
					    <td><?=$r['regimens']['30_34_male']?></td>
					    <td><?=$r['regimens']['35_39_female']?></td>
						<td><?=$r['regimens']['35_39_male']?></td>
						<td><?=$r['regimens']['40_44_female']?></td>
					    <td><?=$r['regimens']['40_44_male']?></td>
						<td><?=$r['regimens']['45_49_female']?></td>		
						<td><?=$r['regimens']['45_49_male']?></td>
						<td><?=$r['regimens']['50_female']?></td>		
						<td><?=$r['regimens']['50_male']?></td>	
						<td><?=$total=array_sum($r['regimens'])?></td>											
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

