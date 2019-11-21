<?php
foreach($results as $result){
};

?>
<!DOCTYPE html>
<html lang="en">
<head>
	<style>
		@media screen{
			.button-bar{
				width: 10.7%;
			}
		}
		.btn_positioning{
			float:right; position:fixed; bottom:70px; right:40px;
			margin: 10px;
		}
		.button_size{
			height:38px; font-size:1em; font-weight: bold;
		}
		.ui-multiselect-menu{
			zoom:0.8;
			display:none; 
			padding:3px; 
			z-index:10000; 
		}
		.column input[type=checkbox] {
			width: 10%;
		}


	</style>
	<script type="text/javascript">
			function setCCC(service) {
				if (service=='ART'){
					$('#patient_number').val('<?=$facility_code. $cs;?>');
					$('#patient_number').attr('maxlength','11');
				}
				if (service=='PREP'){
					$('#patient_number').val('PREP<?=$cs.$facility_code. $cs;?>');
					$('#patient_number').attr('maxlength','16');
				}
				if (service=='PEP'){
					$('#patient_number').val('PEP<?=$cs.$facility_code. $cs;?>');
					$('#patient_number').attr('maxlength','15');
				}
				if (service=='HEP'){
					$('#patient_number').val('HEP<?=$cs.$facility_code. $cs;?>');
					$('#patient_number').attr('maxlength','15');
				}

				if (service=='HEI'){
					$('#patient_number').val('<?=$facility_code. $cs.date('Y').$cs;?>');
					$('#patient_number').attr('maxlength','16');
				}			
			}
		$(document).ready(function() {
			var facilityAdultAge = parseInt(<?php echo $facility_adult_age;?>)
			$("#drug_prophylaxis").trigger("multiselectclick");
	    //Function to Check Patient Numner exists
	    var base_url = "<?php echo base_url();?>";
	    $("#patient_number").change(function() {
	    		var patient_no=$("#patient_number").val();
				// -- do regex check on patient ccc number 
				var CCC_check = new RegExp('^[0-9]{5}<?=$cs;?>[0-9]{5}');
				var CCC_check_PREP = new RegExp('^PREP<?=$cs?>[0-9]{5}<?=$cs;?>[0-9]{5}');
				var CCC_check_PEP  = new RegExp('^PEP<?=$cs?>[0-9]{5}<?=$cs;?>[0-9]{5}');
				var CCC_check_HEP  = new RegExp('^HEP<?=$cs?>[0-9]{5}<?=$cs;?>[0-9]{5}');
				var CCC_check_HEI = new RegExp('^[0-9]{5}<?=$cs;?>[0-9]{4}<?=$cs;?>[0-9]{5}');

				if(!CCC_check.test(patient_no) && !CCC_check_PREP.test(patient_no) && !CCC_check_PEP.test(patient_no) && !CCC_check_HEI.test(patient_no) && !CCC_check_HEP.test(patient_no) ){
					bootbox.alert("<h4>Wrong CCC format</h4>\n\<hr/>Please Note recommended format \
					<br /><b> ART</b> : {mfl}<?=$cs;?>{ccc} e.g <b> <?=$facility_code;?><?=$cs?>00001 </b> \
					<br /><b> PREP</b>: PREP-{mfl}<?=$cs;?>{ccc} e.g <b> PREP<?=$cs?><?=$facility_code;?><?=$cs?>00001 </b> \
					<br /><b> PEP</b>:  PEP-{mfl}<?=$cs;?>{ccc} e.g <b> PEP<?=$cs?><?=$facility_code;?><?=$cs?>00001 </b> \
					<br /><b> HEP</b>:  HEP-{mfl}<?=$cs;?>{ccc} e.g <b> HEP<?=$cs?><?=$facility_code;?><?=$cs?>00001 </b> \
					<br /><b> HEI</b>:  {mfl}<?=$cs;?>{year}<?=$cs;?>{ccc} e.g <b> <?=$facility_code.$cs.date('Y');?><?=$cs?>00001 </b> \
						");
					$(".btn").attr("disabled","disabled");
				} else{
					$(".btn").attr("disabled",false);


	    	if (patient_no != '') {
	    		var original_patient_no = $("#original_patient_number").val();
	    		var link = base_url + "patient_management/checkpatient_no/" + patient_no;
	    		$.ajax({
	    			url: link,
	    			type: 'POST',
	    			success: function(data) {
	    				if (data == 1 && original_patient_no != patient_no) {
	    					bootbox.alert("<h4>Repeated Entry</h4>\n\<hr/><center>Patient Number Matches an existing record</center>");
	    					$(".btn").attr("disabled", "disabled");
	    				} else {
	    					$(".btn").attr("disabled", false);
	    				}
	    			}
	    		});
	    	}
	    }
	    });
	    $("#match_spouse").change(function() {
	    	var patient_no = $("#match_spouse").val();
	    	if (patient_no != '') {
	    		var link = base_url + "patient_management/checkpatient_no/" + patient_no;
	    		$.ajax({
	    			url: link,
	    			type: 'POST',
	    			success: function(data) {
	    				if (data == 1) {
	    					$(".btn").attr("disabled", false);
	    				} else {

	    					bootbox.alert("<h4>CCC Number Mismatch</h4>\n\<hr/><center>Patient Number does not exist</center>");
	    					$(".btn").attr("disabled", "disabled");
	    				}
	    			}
	    		});
	    	}
	    });
	    $("#match_parent").change(function() {
	    	var patient_no = $("#match_parent").val();
	    	if (patient_no != '') {
	    		var link = base_url + "patient_management/checkpatient_no/" + patient_no;
	    		$.ajax({
	    			url: link,
	    			type: 'POST',
	    			success: function(data) {
	    				if (data == 1) {
	    					$(".btn").attr("disabled", false);
	    				} else {

	    					bootbox.alert("<h4>CCC Number Mismatch</h4>\n\<hr/><center>Patient Number does not exist</center>");
	    					$(".btn").attr("disabled", "disabled");
	    				}
	    			}
	    		});
	    	}
	    });

	    //Attach date picker for date of birth
	    $("#dob").datepicker({
	    	yearRange: "-120:+0",
	    	maxDate: "0D",
	    	dateFormat: $.datepicker.ATOM,
	    	changeMonth: true,
	    	changeYear: true
	    });

	    //Function to calculate age in years and months
	    $("#dob").change(function() {
	    	var dob = $(this).val();
	    	dob = new Date(dob);
	    	var today = new Date();
	    	$('.plan_hidden').css("display", "none");
	    	$('.match_hidden').css("display", "none");
	    	var age_in_years = Math.floor((today - dob) / (365.25 * 24 * 60 * 60 * 1000));
	    	$("#age").attr("value", age_in_years);
	        //change start age
	        $('#start_age').val(getStartAge(dob, "<?php echo $result['date_enrolled'];?>"));
	        //if age in years is less than 15 years
	        if ($('#age').val() >= facilityAdultAge) {
	        	$('.plan_hidden').css("display", "block");
	        	$('.match_hidden').css("display", "none");
	        	$("#match_parent").val("<?php echo $result['parent']; ?>");
	        } else if ($('#age').val() < facilityAdultAge) {
	        	$('.match_hidden').css("display", "block");
	        	$("#match_parent").val("");
	        }
	    });

	    $("#medical_record_number").val("<?php echo $result['medical_record_number'];?>");
	    $("#patient_number").val("<?php echo $result['patient_number_ccc'];?>");
	    $("#original_patient_number").val("<?php echo $result['patient_number_ccc'];?>");
	    $("#last_name").val("<?php echo $result['last_name'];?>");
	    $("#first_name").val("<?php echo $result['first_name'];?>");
	    $("#other_name").val("<?php echo $result['other_name'];?>");
	    $("#dob").val("<?php echo $result['dob'];?>");
	    $("#pob").val("<?php echo $result['pob'];?>");
	    $("#gender").val("<?php echo $result['gender'];?>");
	    $("#who_stage").val("<?php echo $result['who_stage']; ?>");
	    $("#match_parent").val("<?php echo $result['parent']; ?>");
	    $("#match_spouse").val("<?php echo $result['secondary_spouse']; ?>");
	    $("#pregnant").val("<?php echo $result['pregnant'];?>");
	    $("#breastfeeding").val("<?php echo $result['breastfeeding']; ?>");

	    //Display Gender Tab
	    if ($("#gender").val() == 2) {
	    	$("#pregnant_view").show();
	    } else {
	    	$("#pregnant_view").hide();
	    }

	    $("#pregnant").change(function() {
	    	var selected_value = $(this).attr("value");
	    	if (selected_value == 1) {
	    		$("#service > option").each(function() {
	    			if (this.text === "PMTCT") {
	    				$(this).attr("selected", "selected");
	    			}
	    		});
	    	} else {
	    		$('#breastfeeding').val(0)
	    		$("#service").removeAttr("value");
	    	}
	    });

	    $('#start_age').val(getStartAge("<?php echo $result['dob'];?>", "<?php echo $result['date_enrolled'];?>"));
	    $('#age').val(getAge("<?php echo $result['dob'];?>"));

	    current_age = getAge("<?php echo $result['dob'];?>");
	    if (current_age < facilityAdultAge) {
	        //if patient is less than 15 years old hide all family planning data
	        $(".plan_hidden").css("display", "none");
	    } else {
	        //if patient is more than 15 years old hide all parent/dependant data
	        $('.match_hidden').css("display", "none");
	    }

	    $('#start_weight').val("<?php echo $result['start_weight'];?>");
	    $('#start_height').val("<?php echo $result['start_height'];?>");
	    $('#start_bsa').val("<?php echo $result['start_bsa'];?>");
	    $('#start_bmi').val("<?php echo $result['start_bmi'];?>");
	    $('#current_weight').val("<?php echo $result['weight'];?>");
	    $('#current_height').val("<?php echo $result['height'];?>");
	    $('#bmi').val("<?php echo $result['bmi'];?>");
	    $('#bsa').val("<?php echo $result['sa'];?>");
	    $('#phone').val("<?php echo $result['phone'];?>");

	    //To Check Sms Consent
	    var sms_consent = "<?php echo $result['sms_consent'];?>";
	    if (sms_consent == 1) {
	    	$("#sms_yes").attr("checked", "true");
	    } else if (sms_consent == 0) {
	    	$("#sms_no").attr("checked", "true");
	    }

	    $('#physical').val("<?php echo $result['physical'];?>");
	    $('#alternate').val("<?php echo trim(preg_replace('/\s\s+/', '\n ', $result['alternate']));?>");

	    $('#partner_status').val("<?php echo $result['partner_status'];?>");
	    $('#disclosure').val("<?php echo $result['disclosure'];?>");
	    //if partner status is not concordant do not show spouse field
	    partner_status = "<?php echo $result['partner_status'];?>";
	    if (partner_status != 1 && partner_status != 2) {
	    	$(".status_hidden").css("display", "none");
	    	$("#match_spouse").val("");
	    }
	    $('#partner_status').change(function() {
	    	var selected_value = $(this).val();
	    	if (selected_value == 1 ||  selected_value == 2) {
	    		$(".status_hidden").css("display", "block");
	    		$("#match_spouse").val("<?php echo $result['secondary_spouse']; ?>");
	    	} else {
	    		$(".status_hidden").css("display", "none");
	    		$("#match_spouse").val("");
	    	}
	    });

	    //Function to configure multiselect in family planning and other chronic illnesses
	    $("#family_planning").multiselect().multiselectfilter();
	    $("#other_illnesses").multiselect().multiselectfilter();
	    $("#drug_allergies").multiselect().multiselectfilter();
	    $("#drug_prophylaxis").multiselect().multiselectfilter();

	    //On Select Drug Prophylaxis
	    $("#isoniazid_view").css("display", "none");

	    $("#drug_prophylaxis").on("multiselectclick", function(event, ui) {
	    	$("#isoniazid_view").css("display", "none");
	    	$("#iso_start_date").val("");
	    	$("#iso_end_date").val("");
	    	var array_of_checked_values = $("select#drug_prophylaxis").multiselect("getChecked").map(function() {
	    		return this.value;
	    	}).get();

	    	$("select#drug_prophylaxis").multiselect("widget").find("input[value='1']").attr("disabled", false);
	    	$("select#drug_prophylaxis").multiselect("widget").find("input[value='2']").attr("disabled", false);
	    	$("select#drug_prophylaxis").multiselect("widget").find("input[value='3']").attr("disabled", false);
	        //loop through values
	        $.each(array_of_checked_values, function(i, v) {
	        	if (v == 1) {
	                //disable 2
	                $("select#drug_prophylaxis").multiselect("widget").find(":checkbox[value='1']").each(function() {
	                	$("select#drug_prophylaxis").multiselect("widget").find("input[value='2']").attr("disabled", true);
	                });
	            } else if (v == 2) {
	                //disable 1
	                $("select#drug_prophylaxis").multiselect("widget").find(":checkbox[value='1']").each(function() {
	                	$("select#drug_prophylaxis").multiselect("widget").find("input[value='1']").attr("disabled", true);
	                });
	            } else if (v == 3) {
	            	$("select#drug_prophylaxis").multiselect("widget").find(":checkbox[value='1']").each(function() {
	            		$("#isoniazid_view").css("display", "block");
	            		$("#iso_start_date").val("<?php echo $result['isoniazid_start_date'];?>");
	            		$("#iso_end_date").val("<?php echo $result['isoniazid_end_date'];?>");
	            	});
	            }
	        });
	    });

	    //Isoniazid start and end dates
	    $("#iso_start_date").datepicker({
	    	maxDate: "0D",
	    	dateFormat: $.datepicker.ATOM,
	    	changeMonth: true,
	    	changeYear: true
	    });
	    $("#iso_end_date").datepicker({
	    	minDate: "0D",
	    	dateFormat: $.datepicker.ATOM,
	    	changeMonth: true,
	    	changeYear: true
	    });
	    //Select Family Planning Methods Selected
	    var family_planning = "<?php echo $result['fplan'];?>";
	    if (family_planning != null || family_planning != " ") {
	    	var fplan = family_planning.split(',');
	    	for (var i = 0; i < fplan.length; i++) {
	    		$("select#family_planning").multiselect("widget").find(":checkbox[value='" + fplan[i] + "']").each(function() {
	    			$(this).click();
	    		});
	    	}
	    }
	    //select drug_prophylaxis
	    var drug_prophylaxis = "<?php echo $result['drug_prophylaxis'];?>";
	    if (drug_prophylaxis != null || drug_prophylaxis != " ") {
	    	var prophylaxis = drug_prophylaxis.split(',');
	    	for (var i = 0; i < prophylaxis.length; i++) {
	    		$("select#drug_prophylaxis").multiselect("widget").find(":checkbox[value='" + prophylaxis[i] + "']").each(function() {
	    			$(this).click();
	                //show isoniazid dates if its selected
	                if ($(this).attr("title").toLowerCase() == "isoniazid") {
	                	$("#isoniazid_view").show();
	                } else if ($(this).attr("title").toLowerCase() == "cotrimoxazole") {
	                	$("select#drug_prophylaxis").multiselect("widget").find("input[value='2']").attr("disabled", true);
	                } else if ($(this).attr("title").toLowerCase() == "dapsone") {
	                	$("select#drug_prophylaxis").multiselect("widget").find("input[value='1']").attr("disabled", true);
	                }

	            });
	    	}
	    }
	    //select isonazid dates
	    $("#iso_start_date").val("<?php echo $result['isoniazid_start_date'];?>");
	    $("#iso_end_date").val("<?php echo $result['isoniazid_end_date'];?>");


	    //To Disable Textareas
	    $("textarea[name='other_chronic']").not(this).attr("disabled", "true");
	    $("textarea[name='other_drugs']").not(this).attr("disabled", "true");
	    $("textarea[name='other_allergies_listing']").not(this).attr("disabled", "true");
	    $("textarea[name='support_group_listing']").not(this).attr("disabled", "true");

	    //Select Other Illnesses Methods Selected
	    other_illnesses = <?php echo $result['other_illnesses'];?>;
	    other_sickness_list = "";

	    $.each(other_illnesses, function(i, v) {
	    	ill_count = 0;
	        //Loop through list to find match for current selected illness
	        $("select#other_illnesses").multiselect("widget").find(":checkbox[value='" + v + "']").each(function() {
	        	$(this).click();
	        	ill_count = 1;
	        });
	        if (ill_count == 0) {
	        	other_sickness_list += "," + v;
	        }
	    });
	    $("#other_chronic").val(other_sickness_list.substring(1));

	    if ($("#other_chronic").val()) {
	    	$("input[name='other_other']").not(this).attr("checked", "true");
	    	$("textarea[name='other_chronic']").not(this).removeAttr("disabled");
	    }

	    $("#other_drugs").val("<?php echo  $other_drugs=str_replace(array("\
	    	n ","
	    	"," / "),array("\
	    	","
	    	"," - "),$result['other_drugs']);?>");

	    if ($("#other_drugs").val()) {
	    	$("input[name='other_drugs_box']").not(this).attr("checked", "true");
	    	$("textarea[name='other_drugs']").not(this).removeAttr("disabled");
	    }

	    $("#iso_start_date").change(function() {
	    	var endDate = new Date($("#iso_start_date").val());
	        var numberOfDaysToAdd = 168;
	        endDate.setDate(endDate.getDate() + numberOfDaysToAdd);
	        var end_date = (endDate.getFullYear() + '-' + ("0" + (endDate.getMonth() + 1)).slice(-2) + '-' + ("0" + (endDate.getDate())).slice(-2));
	        $("#iso_end_date").val(end_date);

	    });

	    //Select Other Drug Allergies
	    var other_drug_allergies = '<?php echo $adr=str_replace(array("\n"," ","/"),array(" \ ","","-"),$result['adr']);?>';

	    if (other_drug_allergies.indexOf(',') == -1) {
	    	other_drug_allergies = other_drug_allergies + ",";
	    } else {
	    	other_drug_allergies = other_drug_allergies;
	    }
	    var other_drug_allergy = "";
	    if (other_drug_allergies != null || other_drug_allergies != " ") {
	    	var other_all = other_drug_allergies.split(',');
	    	for (var i = 0; i < other_all.length; i++) {
	    		$("select#drug_allergies").multiselect("widget").find(":checkbox[value='" + other_all[i] + "']").each(function() {
	    			$(this).click();
	    		});
	    		if (other_all[i].charAt(0) != "-") {
	    			other_drug_allergy += "," + other_all[i];
	    		}
	    	}
	    	$("#other_allergies_listing").val(other_drug_allergy.substring(1));
	    }

	    if ($("#other_allergies_listing").val()) {
	    	$("input[name='other_allergies']").not(this).attr("checked", "true");
	    	$("textarea[name='other_allergies_listing']").not(this).removeAttr("disabled");
	    }

	    //To Check Disclosure
	    var disclosure = "<?php echo $result['disclosure'];?>";
	    if (disclosure == 1) {
	    	$("#disclosure_yes").attr("checked", "true");
	    } else if (disclosure == 0) {
	    	$("#disclosure_no").attr("checked", "true");
	    }

	    $("#support_group_listing").val("<?php echo $result['support_group']?>");

	    if ($("#support_group_listing").val()) {
	    	$("input[name='support_group']").not(this).attr("checked", "true");
	    	$("textarea[name='support_group_listing']").not(this).removeAttr("disabled");
	    }
	    $('#tested_tb').val("<?php echo $result['tb_test'];?>");
	    $('#pep_reason').val("<?php echo $result['pep_reason'];?>");

	    $('#prep_reason').val("<?php echo $result['prep_reason'];?>");
	    $('#prep_test_answer').val("<?php echo $result['prep_test_answer'];?>");
	    $('#prep_test_date').val("<?php echo $result['prep_test_date'];?>");
	    $('#prep_test_result').val("<?php echo $result['prep_test_result'];?>");


	    $('#smoke').val("<?php echo $result['smoke'];?>");
	    $('#alcohol').val("<?php echo $result['alcohol'];?>");

	    $("#tb").val("<?php echo $result['tb']; ?>");

	    if ($("#tb").val() == 1) {
	    	$("#tbphase_view").show();
	    	$("#tbcategory_view").show();
	    	$("#tbcategory").val("<?php echo $result['tb_category']; ?>");
	    	$("#tbphase").val("<?php echo $result['tbphase']; ?>");
	    	$("#fromphase").val("<?php echo $result['startphase']; ?>");
	    	$("#tophase").val("<?php echo $result['endphase']; ?>");

	    	if ($("#tbphase").val() == 3) {
	    		$("#fromphase_view").hide();
	    		$("#tophase_view").show();
	    	} else if ($("#tbphase").val() == 0) {
	    		$("#fromphase_view").hide();
	    		$("#tophase_view").hide();
	    	} else {
	    		$("#fromphase_view").show();
	    		$("#tophase_view").show();
	    		$("#transfer_source").attr("value", '');
	    	}
	    }

	    //Function to display tb phases
	    $(".tb").change(function() {
	    	var tb = $(this).val();
	    	if (tb == 1) {
	    		$("#tbcategory_view").show();
	    		$("#tbphase_view").show();
	    	} else {
	            //hide views
	            $("#tbcategory_view").hide();
	            $("#fromphase_view").hide();
	            $("#tophase_view").hide();
	            $("#tbphase_view").hide();
	            //reset values
	            $("#tbphase").attr("value", '0');
	            $("#tbcategory").attr("value", '0');
	            $("#fromphase").attr("value", '');
	            $("#tophase").attr("value", '');
	        }
	    });

	    $("#current_status").change(function() {
	    	$("#status_started").datepicker('setDate', new Date());
	    });

	    // function to display tb phase view
	    $("#tbcategory").change(function() {
	    	$("#tbphase_view").show();
	    	$("#fromphase").attr("value", '');
	    	$("#tophase").attr("value", '');

	    });

	    //Function to display tbphase dates
	    $(".tbphase").change(function() {
	    	var tbpase = $(this).val();
	    	$("#fromphase").attr("value", '');
	    	$("#tophase").attr("value", '');
	    	if (tbpase == 3) {
	    		$("#fromphase_view").hide();
	    		$("#tophase_view").show();
	    		$("#tb").val(0);
	    	} else if (tbpase == 0) {
	    		$("#fromphase_view").hide();
	    		$("#tophase_view").hide();
	    	} else {
	    		$("#fromphase_view").show();
	    		$("#tophase_view").show();
	    	}
	    });

	    //Function to display datepicker for tb fromphase
	    $("#fromphase").datepicker({
	    	maxDate: "0D",
	    	dateFormat: $.datepicker.ATOM,
	    	changeMonth: true,
	    	changeYear: true
	    });

	    //Function to display datepicker for tb tophase
	    $("#tophase").datepicker({
	    	dateFormat: $.datepicker.ATOM,
	    	changeMonth: true,
	    	changeYear: true
	    });


	    //Function to calculate date ranges for tb stages
	    $("#fromphase").change(function() {
	    	var from_date = $(this).val();
	    	var new_date = new Date(from_date);
	    	var to_date = new Date();
	    	var category = $("#tbcategory").val();
	    	var tbphase = $(".tbphase").val();
	    	if (category == 1) {
	    		if (tbphase == 1) {
	                //Intensive tb phase change the number of days from 90 to 56 for Category one
	                var numberOfDaysToAdd = 56;
	            } else if (tbphase == 2) {
	                //Continuation
	                var numberOfDaysToAdd = 112;
	            }
	        } else if (category == 2) {
	        	if (tbphase == 1) {
	                //Intensive tb phase change the number of days from 90 to 84 for Category two
	                var numberOfDaysToAdd = 84;
	            } else if (tbphase == 2) {
	                //Continuation changed from 150 to 140 days
	                var numberOfDaysToAdd = 140;
	            }
	        }
	        var start_date = new Date(new_date.getFullYear(), new_date.getMonth(), new_date.getDate());
	        var start_date_timestamp = start_date.getTime();
	        var end_date_timestamp = (1000 * 60 * 60 * 24 * numberOfDaysToAdd) + start_date_timestamp;
	        $("#tophase").datepicker('setDate', new Date(end_date_timestamp));
	    });

	    //Function to enable textareas for other chronic illnesses
	    $("#other_other").change(function() {
	    	var other = $(this).is(":checked");
	    	if (other) {
	    		$("textarea[name='other_chronic']").not(this).removeAttr("disabled");
	    	} else {
	    		$("textarea[name='other_chronic']").not(this).attr("disabled", "true");
	    	}
	    });

	    //Function to enable textareas for other allergies
	    $("#other_drugs_box").change(function() {
	    	var other = $(this).is(":checked");
	    	if (other) {
	    		$("textarea[name='other_drugs']").not(this).removeAttr("disabled");
	    	} else {
	    		$("textarea[name='other_drugs']").not(this).attr("disabled", "true");
	    	}
	    });

	    //Function to enable textareas for other allergies
	    $("#other_allergies").change(function() {
	    	var other = $(this).is(":checked");
	    	if (other) {
	    		$("textarea[name='other_allergies_listing']").not(this).removeAttr("disabled");
	    	} else {
	    		$("textarea[name='other_allergies_listing']").not(this).attr("disabled", "true");
	    	}
	    });

	    //Function to enable textareas for support group
	    $("#support_group").change(function() {
	    	var other = $(this).is(":checked");
	    	if (other) {
	    		$("textarea[name='support_group_listing']").not(this).removeAttr("disabled");
	    	} else {
	    		$("textarea[name='support_group_listing']").not(this).attr("disabled", "true");
	    	}
	    });

	    //Attach date picker for date of enrollment
	    $("#enrolled").datepicker({
	    	yearRange: "-30:+0",
	    	maxDate: "0D",
	    	dateFormat: $.datepicker.ATOM,
	    	changeMonth: true,
	    	changeYear: true
	    });
	    $("#enrolled").val("<?php echo $result['date_enrolled'] ?>");
	    $("#current_status").val("<?php echo $result['current_status'] ?>");
	    $("#status_started").val("<?php echo $result['status_change_date'] ?>");
	    $("#source").val("<?php echo $result['source'] ?>");
	    var service_name = "<?php echo $result['service_name'];?>";
	    if (service_name === "PEP") {
	    	$("#pep_reason_listing").show();
	    	$("#who_listing").hide();
	    	$("#drug_prophylax").hide();
	    } else if (service_name === "PREP") {
	    	$("#pep_reason_listing").hide();
	    	$("#who_listing").hide();
	    	$("#drug_prophylax").hide();
	    }

	    $("#service").val("<?php echo $result['service'] ?>");
	    prev_service = '';
	    // $("#service").val();

	    // console.log('service - ' +$("#service").text() );
			

	    if($("#service :selected").text() !== 'PREP' &&  $("#service :selected").text() !== 'PEP')
	    	{

	    	$("#prep_test_question").hide();
	    	$("#prep_test_date_view").hide();
	    	$("#prep_test_result_view").hide();
    	   	$("#prep_reason_listing").hide();
    	   	$("#pep_reason_listing").hide();


	    }
	    if ($('#prep_test_answer').val() == 1 && $("#service :selected").text() == 'PREP') {
	    	$('#prep_reason_listing').show();
	    	$('#prep_test_question').show();
	    	$('#prep_test_date_view').show();
	    	$('#prep_test_result_view').show();
	    }





	    $("#service_started").val("<?php echo $result['start_regimen_date'] ?>");

	    $("#regimen").val("<?php echo $result['start_regimen'] ?>");
	    $("#current_regimen").val("<?php echo $result['current_regimen'] ?>");


	    //Attach date picker for date of status change
	    $("#status_started").datepicker({
	    	yearRange: "-30:+0",
	    	dateFormat: $.datepicker.ATOM,
	    	changeMonth: true,
	    	maxDate: "0D",
	    	changeYear: true
	    });
	    // remove the validator class error
	    $("input,select").on('change', function(i, v) {
	    	var value = $(this).val();
	    	var id = this.id;
	    	if (value != '') {
	    		$('#' + id).validationEngine('hide');
	    	}
	    });
	    //Attach date picker for date of start regimen 
	    $("#service_started").datepicker({
	    	yearRange: "-30:+0",
	    	dateFormat: $.datepicker.ATOM,
	    	changeMonth: true,
	    	changeYear: true,
	    	maxDate: "0D"
	    });

	    //Function to display transfer from list if patient source is(transfer in)
	    $("#source").change(function() {
	    	var selected_value = $(this).val();

	    	if($('#source option:selected').html().toLowerCase().indexOf('transfer') >= 0) {
	    		$("#patient_source_listing").show();
	    		$("#facility-service").hide();
	    	} else {
	    		$("#patient_source_listing").hide();
	    		$("#facility-service").show();

	    		$("#transfer_source").attr("value", '');
	    	}
	    });

	    	$("#source").change(function() {
				var selected_value = $(this).val();
				if(selected_value == 3) {
					$("#patient_source_listing").show();
				} else {
					$("#patient_source_listing").hide();
					$("#transfer_source").attr("value",'');
				}
			});

			$("#transfer_source").change(function() {
				var selected_value = $(this).val();
				$('#patient_number').val(selected_value+'<?=$cs?>');
			});


	    //Function to display Regimens in this line

	    $("#service").change(function() {
	    	var service_line = $(this).val();
	    	var link = base_url + "regimen_management/getRegimenLine/" + service_line;
	    	var selected_text = $("#service option[value='" + service_line + "']").text();
	    	regimen_text = "#current_regimen";
	    	append_start_regimen = false;

	    	$("#drug_prophylax").show();
	    	$("#current_regimen option").remove();
	    	$("#servicestartedcontent").show();
	    	$("#prep_reason_listing").hide();
	    	$("#prep_reason").val(0);
	    	$("#prep_test_answer").val(0);
	    	$("#prep_test_result").val(0);
	    	$("#pep_reason_listing").hide();
	    	$("#pep_reason").val(0);
	    	$("#who_listing").show();
	    	$("#prep_test_question").hide();
	    	$("#prep_test_date_view").hide();
	    	$("#prep_test_result_view").hide();

	    	if (selected_text == "ART" || selected_text == "PMTCT") {
	    		if (selected_text == "PMTCT" && $("#age").val() < 2) {
	    			var link = base_url + "regimen_management/getRegimenLine/" + service_line + "/true";
	    		}

	    		if (prev_service != '') {
	    			if ((prev_service != "ART" && selected_text == "PMTCT") || (prev_service != "PMTCT" && selected_text == "ART")) {
	    				append_start_regimen = true;
	    				regimen_text = "#regimen,#current_regimen";
	    				$("#service_started").val("");
	    			}
	    		}
	    	} else if (selected_text === "PEP") {
	    		$("#pep_reason_listing").show();
	    		$('#pep_reason').addClass("validate[required]");
	    		$("#who_listing").hide();
	    		$("#who_stage").val(0);
	    		$("#drug_prophylax").hide();
	    		$("#drug_prophylax").val(0);
	    		$("#service_started").val("");
	    		$('#prep_reason').removeClass("validate[required]");
	    	} else if (selected_text == "PREP") {
	    		$("#prep_reason_listing").show();
	    		$('#prep_reason').addClass("validate[required]");
	    		$("#prep_test_question").show();
	    		$("#pep_reason_listing").hide();
	    		$('#pep_reason').removeClass("validate[required]");
	    		$("#who_listing").hide();
	    		$("#who_stage").val(0);
	    		$("#drug_prophylax").hide();
	    		$("#drug_prophylax").val(0);
	    		$("#service_started").val("");
	    	}
	    	$.ajax({
	    		url: link,
	    		type: 'POST',
	    		dataType: "json",
	    		success: function(data) {
	    			if (append_start_regimen == true) {
	    				$("#regimen option").remove();
	    			}
	    			$(regimen_text).append($("<option></option>").attr("value", '').text('--Select One--'));
	    			$.each(data, function(i, jsondata) {
	    				$(regimen_text).append($("<option></option>").attr("value", jsondata.id).text(jsondata.Regimen_Code + " | " + jsondata.Regimen_Desc));
	    			});
	    		}
	    	});
	    	prev_service = selected_text;
	    });

	    $("#prep_test_answer").on('change', function() {
	    	var prep_test_answer = $(this).val();

	    	$("#prep_test_date_view").hide();
	    	$("#prep_test_date").val('');
	    	$("#prep_test_result_view").hide();
	    	$("#prep_test_result").val(0);

	    	if (prep_test_answer == 1) {
	    		$("#prep_test_date_view").show();
	    	}
	    });

	    $("#prep_test_date").datepicker({
	    	maxDate: "0D",
	    	dateFormat: $.datepicker.ATOM,
	    	changeMonth: true,
	    	changeYear: true
	    });

	    $("#prep_test_date").change(function() {
	    	var prep_test_date = $(this).val();
	    	$("#prep_test_result").val(0);
	    	$("#prep_test_result_view").show();
	    });

	    $("#prep_test_result").on('change', function() {
	    	var prep_test_result = $(this).val();
	    	if (prep_test_result == 1) {
	    		bootbox.alert("<h4>Incorrect Regimen</h4>\n\<hr/><center>Patient Should Be started on ART Service</center>");
	    		$("#service option:contains(ART)").attr('selected', 'selected');
	    		$("#service").trigger('change')
	    	}
	    });

	    $("#next_appointment_date").datepicker({
	    	yearRange: "-30:+0",
	    	dateFormat: $.datepicker.ATOM,
	    	changeMonth: true,
	    	changeYear: true
	    });

	    $("#next_clinical_appointment_date").datepicker({
	    	yearRange: "-30:+0",
	    	dateFormat: $.datepicker.ATOM,
	    	changeMonth: true,
	    	changeYear: true
	    });
	    $("#next_appointment_date").val("<?php echo $result['nextappointment'];?>");
	    $("#prev_appointment_date").val("<?php echo $result['nextappointment'];?>");
	    $("#next_clinical_appointment_date").val("<?php echo $result['clinicalappointment'];?>");
	    $("#prev_clinical_appointment_date").val("<?php echo $result['clinicalappointment'];?>");

	    var appointment = $("#next_appointment_date").val();
	    var appointment_clinical = $("#next_clinical_appointment_date").val();
	    var days = getDays(appointment);
	    var days_toclinic = getDays(appointment_clinical);


	    //if(days>=0){
	    	$('#days_to_next').attr("value", days);
	    	$('#days_to_next_clinical').attr("value", days_toclinic);
	    // }

	    $("#next_appointment_date").change(function() {
	    	var appointment = $(this).val();
	    	var days = getDays(appointment);
	    	$('#days_to_next').attr("value", days);
	    });

	    $("#next_clinical_appointment_date").change(function() {
	    	var appointment_clinical = $(this).val();
	    	var days = getDays(appointment_clinical);
	    	$('#days_to_next_clinical').attr("value", days);
	    });


	    $("#days_to_next").change(function() {
	    	var days = $("#days_to_next").attr("value");
	    	var base_date = new Date();
	    	var appointment_date = $("#next_appointment_date");
	    	var today = new Date(base_date.getFullYear(), base_date.getMonth(), base_date.getDate());
	    	var today_timestamp = today.getTime();
	    	var appointment_timestamp = (1000 * 60 * 60 * 24 * days) + today_timestamp;
	    	appointment_date.datepicker("setDate", new Date(appointment_timestamp));
	    });


	    $("#days_to_next_clinical").change(function() {
	    	var days = $("#days_to_next_clinical").attr("value");
	    	var base_date = new Date();
	    	var appointment_date = $("#next_clinical_appointment_date");
	    	var today = new Date(base_date.getFullYear(), base_date.getMonth(), base_date.getDate());
	    	var today_timestamp = today.getTime();
	    	var appointment_timestamp = (1000 * 60 * 60 * 24 * days) + today_timestamp;
	    	appointment_date.datepicker("setDate", new Date(appointment_timestamp));
	    });


	    //Function to display tranfer From	
	    if ($("#source").val() == 3) {
	    	$("#patient_source_listing").show();
	    }
	    $("#transfer_source").val("<?php echo $result['transfer_from']; ?>");

	    //Function to check if female is pregnant
	    $("#gender").change(function() {
	    	var selected_value = $(this).attr("value");
	        //if female, display the prengancy selector
	        if (selected_value == 2) {
	            //If female show pregnant container
	            $('#pregnant_view').slideDown('slow', function() {});
	        } else {
	            //If male do not show pregnant container
	            $('#pregnant_view').slideUp('slow', function() {
	            	$('#pregnant').val(0)
	            	$('#breastfeeding').val(0)
	            });
	        }
	    });

	});		
function getMSQ() {
	var weight = $('#current_weight').attr('value');
	var height = $('#current_height').attr('value');
	var MSQ = Math.sqrt((parseInt(weight) * parseInt(height)) / 3600);
	$('#bsa').attr('value', MSQ);

	var BMI = (parseInt(weight) / (parseInt(height)/100 * parseInt(height)/100));
	$('#bmi').attr('value', BMI);

}

function getStartMSQ() {
	var weight = $('#start_weight').attr('value');
	var height = $('#start_height').attr('value');
	var MSQ = Math.sqrt((parseInt(weight) * parseInt(height)) / 3600);
	$('#start_bsa').attr('value', MSQ);

	var BMI = (parseInt(weight) / (parseInt(height)/100 * parseInt(height)/100));
	$('#start_bmi').attr('value', BMI);
}


function getDays(dateString) {
	var base_date = new Date();
	var today = new Date(base_date.getFullYear(), base_date.getMonth(), base_date.getDate());
	var today_timestamp = today.getTime();
	var one_day = 1000 * 60 * 60 * 24;
	var appointment_timestamp = new Date(Date.parse(dateString, "YYYY/MM/dd")).getTime();
	var difference = appointment_timestamp - today_timestamp;
	var days_difference = Math.ceil(difference / one_day);
	return (days_difference-1);
}



function getStartAge(dateString, baseDate) {
	var today = new Date(baseDate);
	var birthDate = new Date(dateString);
	var age = today.getFullYear() - birthDate.getFullYear();
	var m = today.getMonth() - birthDate.getMonth();
	if(m < 0 || (m === 0 && today.getDate() < birthDate.getDate())) {
		age--;
	}
	if(isNaN(age)) {
		return "N/A";
	}
	return age;
}

function getAge(dateString) {
	var today = new Date();
	var birthDate = new Date(dateString);
	var age = today.getFullYear() - birthDate.getFullYear();
	var m = today.getMonth() - birthDate.getMonth();
	if(m < 0 || (m === 0 && today.getDate() < birthDate.getDate())) {
		age--;
	}
	if(isNaN(age)) {
		return "N/A";
	}
	return age;
}

	        //Function to validate required fields
	        function processData(form) {
	        	var form_selector = "#" + form;
	        	var validated = $(form_selector).validationEngine('validate');
	        	var family_planning = $("select#family_planning").multiselect("getChecked").map(function() {
	        		return this.value;
	        	}).get();
	        	var other_illnesses = $("select#other_illnesses").multiselect("getChecked").map(function() {
	        		return this.value;
	        	}).get();
	        	var drug_allergies=$("select#drug_allergies").multiselect("getChecked").map(function() {
	        		return this.value;
	        	}).get();
	        	var drug_prophylaxis=$("select#drug_prophylaxis").multiselect("getChecked").map(function() {
	        		return this.value;
	        	}).get();
	        	$("#family_planning_holder").val(family_planning);
	        	$("#other_illnesses_holder").val(other_illnesses);
	        	$("#drug_allergies_holder").val(drug_allergies);
	        	$("#drug_prophylaxis_holder").val(drug_prophylaxis);

	        	if($("#original_patient_number").val()!=$("#patient_number").val()){
	        		var base_url="<?php echo base_url();?>";
	        		var link=base_url+"patient_management/update_visit";
	        		$.ajax({
	        			url: link,
	        			type: 'POST',
	        			dataType : 'json',
	        			data: {
	        				original_patient_number : $('#original_patient_number').val(),
	        				patient_number : $('#patient_number').val()
	        			},
	        			success: function() {
	        				bootbox.alert("<h4>Updates</h4>\n\<hr/><center>Patient Visits Updated</center>");					         
	        			}
	        		});	    	
	        	}

	        	if(!validated) {
	        		return false;
	        	}else{
	        		$(".btn").attr("disabled","disabled");
	        		return true;
	        	}  
	        }
	    </script>
	</head>
	<body>
		<div class="full-content" style="background:#FF9">
			<div id="sub_title" >
				<a href="<?php  echo base_url().'patient_management ' ?>">Patient Listing </a> <i class=" icon-chevron-right"></i><a href="<?php  echo base_url().'patient_management/load_view/details/'.$result['id'] ?>"><?php echo strtoupper($result['first_name'].' '.$result['other_name'].' '.$result['last_name']) ?></a> <i class=" icon-chevron-right"></i><strong>Edit details</strong>
				<hr size="1">
			</div>
			<h3>Edit Patient Details
				<div style="float:right;margin:5px 40px 0 0; width:350px;">
					(Fields Marked with <b><span class='astericks'>*</span></b> Asterisks are required)
				</div></h3>

				<form id="edit_patient_form" method="post"  action="<?php $record=$result['id']; echo base_url() . 'patient_management/update/'.$record; ?>" onsubmit="return processData('edit_patient_form')" >
					<div class="column" id="columnOne">
						<fieldset>
							<legend>
								Patient Information &amp; Demographics
							</legend>
							<div class="max-row">
									<label><span class='astericks'>*</span>Source of Patient</label>
									<select name="source" id="source" class="validate[required]">
										<option value="">--Select--</option>
										<?php
										foreach ($sources as $source) {
											echo "<option value='" . $source['id'] . "'>" . $source['Name'] . "</option>";
										}
										?>
									</select>
								</div>
								<div id="patient_source_listing" class="max-row" style="display:none;">
									<label> Transfer From</label>
									<select name="transfer_source" id="transfer_source" >
										<option value="">--Select--</option>
										<?php
										foreach ($facilities as $facility) {
											echo "<option value='" . $facility['facilitycode'] . "'>" . $facility['name'] . "</option>";
										}
										?>
									</select>
								</div>
								<div class="max-row" id="facility-service">
								<a href="javascript:;;" onclick="setCCC('ART')">ART</a> | <a href="javascript:;;" onclick="setCCC('PREP')">PREP</a> | <a href="javascript:;;" onclick="setCCC('HEI')">HEI</a> | <a href="javascript:;;" onclick="setCCC('PEP')">PEP</a> | <a href="javascript:;;" onclick="setCCC('HEP')">HEP</a>
							</div>
							<div class="max-row">
								<div class="mid-row">
									<label> Medical Record No.</label>
									<input type="text" name="medical_record_number" id="medical_record_number" value="">
								</div>
								<div class="mid-row">
									<label> <span class='astericks'>*</span>Patient Number CCC </label>
									<input type="text" name="patient_number" id="patient_number" class="validate[required]" maxlength="11">
									<input type="hidden" name="original_patient_number" id="original_patient_number">
								</div>
							</div>
							<div class="max-row">
								<label><span class='astericks'>*</span>Last Name</label>
								<input  type="text"name="last_name" id="last_name" class="validate[required]">
							</div>
							<div class="max-row">
								<div class="mid-row">
									<label><span class='astericks'>*</span>First Name</label>
									<input type="text"name="first_name" id="first_name" class="validate[required]">
								</div>

								<div class="mid-row">
									<label>Other Name</label>
									<input type="text"name="other_name" id="other_name">
								</div>
							</div>
							<div class="max-row">
								<div class="mid-row">
									<label><span class='astericks'>*</span>Date of Birth</label>
									<input type="text"name="dob" id="dob" class="validate[required]">
								</div>
								<div class="mid-row">
									<label> Place of Birth </label>
									<select name="pob" id="pob">
										<option value=" ">--Select--</option>
										<?php
										foreach ($districts as $district) {
											echo "<option value='" . $district['id'] . "'>" . $district['Name'] . "</option>";
										}
										?>
									</select>
								</div>
							</div>

							<div class="max-row match_hidden">
								<label>Match to parent/guardian in ccc?</label>
								<input type="text" name="match_parent" id="match_parent">
							</div>

							<div class="max-row">
								<label><span class='astericks'>*</span>Gender</label>
								<select name="gender" id="gender" class="validate[required]">
									<option value="">--Select--</option>
									<?php
									foreach ($genders as $gender) {
										echo "<option value='" . $gender['id'] . "'>" . $gender['name'] . "</option>";
									}
									?>
								</select>
							</div>
							<div class="max-row" id="pregnant_view" style="display:none;">
								<div class="mid-row">
									<label id="pregnant_container"> Pregnant?</label>
									<select name="pregnant" id="pregnant">
										<option value="0">No</option><option value="1">Yes</option>
									</select>
								</div>
								<div class="mid-row">
									<label id="pregnant_container"> Breast Feeding?</label>
									<select name="breastfeeding" id="breastfeeding">
										<option value="0">No</option><option value="1">Yes</option>
									</select>
								</div>
							</div>
							<div class="max-row">
								<div class="mid-row">
									<label >Start Age(Years)</label>
									<input type="text" id="start_age" disabled="disabled"/>
								</div>
								<div class="mid-row">
									<label >Current Age(Years)</label>
									<input type="text" id="age" disabled="disabled"/>
								</div>
							</div>
							<div class="max-row">
								<div class="mid-row">
									<label >Start Weight (KG)</label>
									<input type="text"name="start_weight" id="start_weight" onblur="getStartMSQ()">
								</div>
								<div class="mid-row">
									<label>Current Weight (KG) </label>
									<input type="text"name="current_weight" id="current_weight" onblur="getMSQ()">
								</div>
							</div>
							<div class="max-row">
								<div class="mid-row">
									<label > Start Height (CM)</label>
									<input type="text"name="start_height" id="start_height" onblur="getStartMSQ()">
								</div>
								<div class="mid-row">
									<label > Current Height (CM)</label>
									<input  type="text"name="current_height" id="current_height" onblur="getMSQ()">
								</div>
							</div>
							<div class="max-row">
								<div class="mid-row">
									<label > Start Body Surface Area  <br/> (MSQ)</label>
									<input type="text" name="start_bsa" id="start_bsa" value="" readonly/>
								</div>
								<div class="mid-row">
									<label > Current Body Surface Area (MSQ)</label>
									<input type="text" name="bsa" id="bsa" value="" readonly/>
								</div>
							</div>
							<div class="max-row">
								<div class="mid-row">
									<label > Start Body Mass Index  <br/> (BMI)</label>
									<input type="text" name="start_bmi" id="start_bmi" value=""  readonly/>
								</div>
								<div class="mid-row">
									<label > Current Body Mass Index (BMI)</label>
									<input type="text" name="bmi" id="bmi" value=""  readonly/>
								</div>
							</div>								
							<div class="max-row">
								<div class="mid-row">
									<label> Patient's Phone Contact(s)</label>
									<input  type="text"  name="phone" id="phone" value="" class="phone" placeholder="e.g 0722123456">
								</div>
								<div class="mid-row">
									<label > Receive SMS Reminders</label>
									<input  type="radio"  name="sms_consent" value="1" id="sms_yes">
									Yes
									<input  type="radio"  name="sms_consent" value="0" id="sms_no">
									No
								</div>
							</div>

							<div class="max-row">
								<label> Patient's Physical Contact(s)</label>
								<textarea name="physical" id="physical" value=""></textarea>
							</div>
							<div class="max-row">
								<label> Patient's Alternate Contact(s)</label>
								<textarea name="alternate" id="alternate" value=""></textarea>
							</div>
							<div class="max-row">
								<label>Does Patient belong to any support group?</label>
								<label>Yes
									<input type="checkbox" name="support_group" id="support_group" value="">
								</label>

								<div class="list">
									List Them
								</div>
								<textarea class="list_area" name="support_group_listing" id="support_group_listing"></textarea>
							</div>

						</div>

						<div class="column" id="colmnTwo">
							<fieldset>
								<legend>
									Patient History
								</legend>
								<div class="plan_hidden">
									<div class="max-row">
										<label  id="tstatus"><span class='astericks'>*</span> Partner Status</label>
										<select name="partner_status" id="partner_status" class="validate[required]" >
											<option value="">--Select--</option>
											<option value="0">No Partner</option>
											<option value="1" > Concordant</option>
											<option value="2" > Discordant</option>
											<option value="3" > Unknown</option>
										</select>

									</div>
									<div class="max-row">
										<div class="mid-row">
											<label id="dcs" >Disclosure</label>
											<input  type="radio"  name="disclosure" value="1" id="disclosure_yes">
											Yes
											<input  type="radio"  name="disclosure" value="0" id="disclosure_no">
											No
										</div>
									</div>
									<div class="max-row status_hidden">
										<label>Match to spouse in this ccc?</label>
										<input type="text" name="match_spouse" id="match_spouse">
									</div>

									<div class="max-row">
										<label>Family Planning Method</label>
										<input type="hidden" id="family_planning_holder" name="family_planning_holder" />
										<select name="family_planning" id="family_planning" multiple="multiple" style="width:400px;"  >
											<?php
											foreach ($family_planning as $fplan) {
												echo "<option value='" . $fplan['indicator'] . "'>" ." ".$fplan['name'] . "</option>";
											}
											?>
										</select>
									</div>
								</div>
								<div class="max-row">
									<label>Does Patient have other Chronic illnesses</label>
									<input type="hidden" id="other_illnesses_holder" name="other_illnesses_holder" />
									<select name="other_illnesses" id="other_illnesses"  multiple="multiple"  style="width:400px;" >
										<?php
										foreach ($other_illnesses as $other_illness) {
											echo "<option value='" . $other_illness['indicator'] . "'>" ." ".$other_illness['name'] . "</option>";
										}
										?>
									</select>
								</div>
								<div class="max-row">
									If <b>Other Illnesses</b>
									<br/>
									Click Here
									<input type="checkbox" name="other_other" id="other_other" value="">
									<br/>
									List Them Below (Use Commas to separate)
									<textarea  name="other_chronic" id="other_chronic"></textarea>
								</div>
								<div class="max-row">
									<label> List Other Drugs Patient is Taking </label>
									<label>Yes
										<input type="checkbox" name="other_drugs_box" id="other_drugs_box" value="">
									</label>

									<label>List Them</label>
									<textarea name="other_drugs" id="other_drugs"></textarea>
								</div>
								<div class="max-row">
									<label>Drugs Allergies/ADR</label>
									<input type="hidden" id="drug_allergies_holder" name="drug_allergies_holder" />
									<select name="drug_allergies" id="drug_allergies"  multiple="multiple" style="width:400px;">
										<?php
										foreach($drugs as $drug){
											echo "<option value='-".$drug['id']."-'>"." ".$drug['Drug']."</option>";
										}
										?>	
									</select>
								</div>
								<div class="max-row">
									<label>Does Patient have any other Drugs Allergies/ADR not listed?</label>

									<label>Yes
										<input type="checkbox" name="other_allergies" id="other_allergies" value="">
									</label>

									<label>List Them</label>
									<textarea class="list_area" name="other_allergies_listing" id="other_allergies_listing"></textarea>
								</div>

								<div class="max-row">
									<div class="mid-row">
										<label > Does Patient Smoke?</label>
										<select name="smoke" id="smoke">
											<option value="0" selected="selected">No</option>
											<option value="1">Yes</option>
										</select>
									</div>
									<div class="mid-row">
										<label> Does Patient Drink Alcohol?</label>
										<select name="alcohol" id="alcohol">
											<option value="0" selected="selected">No</option>
											<option value="1">Yes</option>
										</select>
									</div>
								</div>
								<div class="max-row">
									<div class="mid-row">
										<label> Has Patient been <br/>tested for TB?</label>
										<select name="tested_tb" id="tested_tb" class="tested_tb">
											<option value="0">No</option>
											<option value="1">Yes</option>
										</select>
									</div>
									<div class="mid-row">
										<label> Does Patient Have TB?</label>
										<select name="tb" id="tb" class="tb">
											<option value="0" selected="selected">No</option>
											<option value="1">Yes</option>
										</select>
									</div>
								</div>

								<div class="max-row">

									<div class="mid-row" id="tbcategory_view" style="display:none;">
										<label> Select TB category</label>
										<select name="tbcategory" id="tbcategory" class="tbcategory">
											<option value="0" selected="selected">--Select One--</option>
											<option value="1">Category 1</option>
											<option value="2">Category 2</option>
										</select>
									</div>

									<div class="mid-row" id="tbphase_view" style="display:none;">
										<label id="tbstats"> TB Phase</label>
										<select name="tbphase" id="tbphase" class="tbphase">
											<option value="0" selected="selected">--Select One--</option>
											<option value="1">Intensive</option>
											<option value="2">Continuation</option>
											<option value="3">Completed</option>
										</select>
									</div>

								</div>

								<div class="max-row">
									<div class="mid-row" id="fromphase_view" style="display:none;">
										<label id="ttphase">Start of Phase</label>
										<input type="text" name="fromphase" id="fromphase" value=""/>
									</div>
									<div class="mid-row" id="tophase_view" style="display:none;">
										<label id="endp">End of Phase</label>
										<input type="text" name="tophase" id="tophase" value=""/>
									</div>
								</div>


								<div class="max-row">
									<div class="mid-row">
										<label> Date of Next Appointment</label>
										<input type="text" name="next_appointment_date" id="next_appointment_date"  style="color:red"/>
										<input type="hidden" name="prev_appointment_date" id="prev_appointment_date" />
									</div>
									<div class="mid-row">
										<label> Days to Next Appointment</label>
										<input  type="text"name="days_to_next" id="days_to_next" style="color:red">
									</div>								
								</div>

								<div class="max-row">
									<div class="mid-row">
										<label> Date of Next Clinical Appointment</label>
										<input type="text" name="next_clinical_appointment_date" id="next_clinical_appointment_date"  style="color:red"/>
										<input type="hidden" name="prev_clinical_appointment_date" id="prev_clinical_appointment_date" />
									</div>
									<div class="mid-row">
										<label> Days to Next ClinicalAppointment</label>
										<input  type="text" name="days_to_next_clinical" id="days_to_next_clinical" style="color:red">
									</div>								
								</div>

							</fieldset>
						</div>
						<div class="column" id="columnThree">
							<fieldset>
								<legend>
									Patient Information
								</legend>
								<div class="max-row">
									<label><span class='astericks'>*</span>Date Patient Enrolled</label>
									<input type="text" name="enrolled" id="enrolled" value="" class="validate[required]">
								</div>
								<div class="max-row">
									<label><span class='astericks'>*</span>Current Status</label>
									<select name="current_status" id="current_status" class="validate[required] red">
										<option value="">--Select--</option>
										<?php
										foreach ($statuses as $status) {
											echo "<option value='" . $status['id'] . "'>" . $status['Name'] . "</option>";
										}
										?>
									</select>
								</div>
								<div class="max-row">
									<label class="status_started" >Date of Status Change</label>
									<input type="text" name="status_started" id="status_started" value="" >
								</div>
								<div class="max-row">
									<label><span class='astericks'>*</span>Type of Service</label>
									<select name="service" id="service" class="validate[required]">
										<option value="">--Select--</option>
										<?php
										foreach ($service_types as $service_type) {
											echo "<option value='" . $service_type['id'] . "'>" . $service_type['Name'] . "</option>";
										}
										?>
									</select> </label>
								</select>
							</div>
							<div class="max-row" id="pep_reason_listing" style="display:none;">
								<label><span class='astericks'>*</span>PEP Reason</label>
								<select name="pep_reason" id="pep_reason">
									<option value="">--Select--</option>
									<?php
									foreach($pep_reasons as $reason){
										echo "<option value='".$reason['id']."'>".$reason['name']."</option>";
									}
									?>	
								</select> </label>
							</select>
						</div>
						<div class="max-row" id="prep_reason_listing" style="display:none;">
							<label><span class='astericks'>*</span>PREP Reason</label>
							<select name="prep_reason" id="prep_reason">
								<option value="">--Select--</option>
								<?php
								foreach($prep_reasons as $reason){
									echo "<option value='".$reason['id']."'>".$reason['name']."</option>";
								}
								?>	
							</select> </label>
						</select>
					</div>
					<div class="max-row" id="prep_test_question" style="display:none;">
						<div class="max-row">
							<label>Have you been Tested?</label>
							<select name="prep_test_answer" id="prep_test_answer">
								<option value="0">No</option>
								<option value="1">Yes</option>
							</select>
						</div>
						<div class="mid-row" id="prep_test_date_view" style="display:none">
							<label>Test Date</label>
							<input type="text" name="prep_test_date" id="prep_test_date">
						</div>
						<div class="mid-row" id="prep_test_result_view" style="display:none">
							<label>Was the Test Positive?</label>
							<select name="prep_test_result" id="prep_test_result">
								<option value="0">No</option>
								<option value="1">Yes</option>
							</select>
						</div>
					</div>
					<div class="max-row">
						<label id="start_of_regimen"><span class='astericks'>*</span>Start Regimen </label>
						<select name="regimen" id="regimen" class="validate[required] start_regimen" >
							<option value="">--Select One--</option>
							<?php
							foreach ($regimens as $regimen) {
								echo "<option value='" . $regimen['id'] . "'>".$regimen['Regimen_Code'] ." | " . $regimen['Regimen_Desc'] . "</option>";
							}
							?>
						</select>
					</div>
					<div class="max-row" id="servicestartedcontent">
						<label id="date_service_started">Start Regimen Date</label>
						<input type="text" name="service_started" id="service_started" value="">
					</div>
					<div class="max-row">
						<label style="color:red;font-weight:bold;">Current Regimen</label>
						<select type="text"name="current_regimen" id="current_regimen" class="validate[required] red">
							<option value="">--Select--</option>
							<?php
							foreach ($regimens as $regimen) {
								echo "<option value='" . $regimen['id'] . "'>".$regimen['Regimen_Code'] ." | " . $regimen['Regimen_Desc'] . "</option>";
							}
							?>
						</select>
					</div>
					<div class="max-row" id="who_listing">
						<label>WHO Stage</label>
						<select name="who_stage" id="who_stage" class="who_stage" >
							<option value="">--Select One--</option>
							<?php
							foreach($who_stages as $stages){
								echo "<option value='".$stages['id']."'>".$stages['name']."</option>";
							}
							?>				
						</select>
					</div>
					<div class="max-row" id="drug_prophylax">
						<label>Drug Prophylaxis</label>
						<input type="hidden" id="drug_prophylaxis_holder" name="drug_prophylaxis_holder" />		
						<select name="drug_prophylaxis" id="drug_prophylaxis" multiple="multiple" style="width:300px;" >
							<?php
							foreach($drug_prophylaxis as $prohylaxis){
								echo "<option value='".$prohylaxis['id']."'>".$prohylaxis['name']."</option>";
							}
							?>	
						</select>

					</div>

					<div class="max-row" id="isoniazid_view">
						<div class="mid-row" id="isoniazid_start_date_view">
							<label class="">Isoniazid Start Date</label>
							<input type="text" name="iso_start_date" id="iso_start_date" class="validate[required]" style="color:red"/>
						</div>
						<div class="mid-row" id="isoniazid_end_date_view">
							<label > Isoniazid End Date</label>
							<input  type="text"name="iso_end_date" id="iso_end_date" style="color:red">
						</div>								
					</div>

				</fieldset>
			</div>
			<div class="button-bar btn_positioning " style="float: right;">
				<input form="edit_patient_form" type="submit" class="btn btn-block button_size " value="Update Patient Info" name="save"/>


			</div>

		</form>
	</div>
</body>
</html>