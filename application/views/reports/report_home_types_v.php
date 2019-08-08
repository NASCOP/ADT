
<?php
$ccc_stores = $this->session->userdata("ccc_store");
?>

<script type="text/javascript">
    $(document).ready(function () {
        // $('#drugselector').select2();
       $('.nav a').click(function(){
        $('.drugselector').css('display', 'none');
        });
       
        $('#visiting_patient_report_select').change(function () {
            var value = $(this).val();
            if (value === 'getPatientList') {
                $('#generate_date_range_report').addClass('generate_drug_select');
                $('.generate_drug_select').prop('id', '');
                dropdown = $('#drugselector');
                $('.drugselector').css('display', 'block');
                dropdown.css('display', 'block');
                dropdown.show();
                dropdown.empty();
                $.getJSON("<?= base_url(); ?>report_management/getDrugs", function (resp) {
                    $.each(resp, function (i, r) {
                        dropdown.append('<option value="' + r.id + '">' + r.drug + '</option>')
                    });
                }, 'json');

            } else {
                $('.generate_drug_select').prop('id', 'generate_date_range_report');
                $('#generate_date_range_report').removeClass('generate_drug_select');
                dropdown.css('display', 'none');
                dropdown.hide();
                $('.drugselector').css('display', 'none');
            }


        });

        $(document).on('click', '.generate_drug_select', function () {
            var val = $('#drugselector').val();

            if (val === '') {
                alert('Please select a drug to proceed');
            } else {
                window.location.href="<?php echo base_url().'report_management/getPatientList/';?>"+val+"/"+$('#date_range_from').val()+"/"+$('#date_range_to').val();
            }
        });

        $("#reporting_period").datepicker({
            yearRange: "-120:+0",
            maxDate: "0D",
            changeMonth: true,
            changeYear: true,
            showButtonPanel: true,
            dateFormat: 'MM-yy',
            onClose: function (dateText, inst) {
                var month = $("#ui-datepicker-div .ui-datepicker-month :selected").val();
                var year = $("#ui-datepicker-div .ui-datepicker-year :selected").val();
                month = parseInt(month);
                var last_day_month = LastDayOfMonth(year, month + 1);
                $(this).datepicker('setDate', new Date(year, month, 1));
                month = month + 1;
                month = ("0" + month).slice(-2);
                $("#period_start_date").val(year + "-" + month + "-01");
                $("#period_end_date").val(year + "-" + month + "-" + last_day_month);
            }
        });

        $("#month_period").datepicker({
            yearRange: "-120:+0",
            maxDate: "0D",
            changeMonth: true,
            changeYear: true,
            showButtonPanel: true,
            dateFormat: 'MM-yy',
            onClose: function (dateText, inst) {
                var month = $("#ui-datepicker-div .ui-datepicker-month :selected").val();
                var year = $("#ui-datepicker-div .ui-datepicker-year :selected").val();
                month = parseInt(month);
                var last_day_month = LastDayOfMonth(year, month + 1);
                $(this).datepicker('setDate', new Date(year, month, 1));
                month = month + 1;
                month = ("0" + month).slice(-2);
                $("#month_start_date").val(year + "-" + month + "-01");
                $("#month_end_date").val(year + "-" + month + "-" + last_day_month);
            }
        });
        $('#reporting_period').focusin(function () {
            $('.ui-datepicker-calendar').hide();
        });
        $('#month_period').focusin(function () {
            $('.ui-datepicker-calendar').hide();
        });
        $(".reports_tabs").click(function () {
            $('#standard_report_sub').show();
        });

        $(window).resize(function () {
            $(".hasDatepicker").datepicker("hide");
        });
    });
    function LastDayOfMonth(Year, Month) {
        return (new Date((new Date(Year, Month, 1)) - 1)).getDate();
    }
</script>
<style>
    select {
        width: 100%;
    }
    label {
        font-weight: bold;
    }
    .dataTables td, .dataTables tr, .dataTables th {
        white-space: nowrap;
        overflow: hidden;         /* <- this does seem to be required */
        text-overflow: ellipsis;
    }
    .dataTables_scroll
    {
        overflow:auto;
    }
</style>
<script type="text/javascript">
    $(document).ready(function () {
        $(".dataTables").wrap('<div class="dataTables_scroll" />');
    })
</script>
<div id="standard_report_sub" style="display:none;">
    <table >
        <!-- Standard reports -->
        <tr id="standard_report_row" class="reports_types">
            <td><label >Select Report </label></td>
            <td>
                <select id="standard_report_select" class="input-xlarge select_report">
                    <option  value="0" class="none">-- Select a Report  --</option>
                    <option class="donor_date_range_report" value="patient_enrolled">Number of Patients Enrolled in Period</option>
                    <option class="donor_date_range_report" value="getStartedonART">Number of Patients Started on ART in the Period</option>
                    <option class="annual_report" value="graph_patients_enrolled_in_year">Graph of Number of Patients Enrolled Per Month in a Given Year</option>
                    <option class="single_date_report" value="cumulative_patients">Cumulative Number of Patients to Date</option>
                    <option class="single_date_report" value="all_service_statistics">Number of Active Patients Receiving ART (by Regimen)</option>
                    <option class="date_range_report" value="clinical_bands">Filtered number of Active Patients receiving ART (by AGE - Clinical Age Bands) </option>
                    <option class="date_range_report" value="service_statistics">Filtered number of Active Patients receiving ART (by AGE - formulation age bands)</option>
                    <option class="single_date_report" value="getFamilyPlanning">Family Planning Summary</option>
                    <option class="date_range_report" value="getIndications">Patient Indications Summary</option>
                    <option class="date_range_report" value="getTBPatients">TB Stages Summary</option>
                    <option class="single_date_report" value="getChronic">Chronic Illnesses Summary</option>
                    <option class="single_date_report" value="getADR">Patient Allergy Summary</option>
                    <option class="date_range_report" value="patients_disclosure">Patients Status &amp; Disclosure</option>	
                    <option class="single_date_report" value="getBMI">Patient BMI Summary</option>
                    <option class="date_range_report" value="getisoniazidPatients">Patients on Drug Prophylaxis </option><!-- New section for patients Isoniazid -->
                    <option class="single_date_report" value="getnonisoniazidPatients">Patients not on Isoniazid </option><!-- New section for patients not on Isoniazid -->

                    <option class="month_period_report" value="get_prep_patients">Patient PREP Summary</option>
                    <option class="date_range_report" value="get_pep_reasons">PEP Reasons Summary</option>
                    <option class="date_range_report" value="get_prep_reasons">PREP Reasons Summary</option>
                </select></td>
        </tr>
        <!-- Visiting patients reports -->
        <tr id="visiting_patient_report_row" class="reports_types">
            <td><label >Select Report </label></td>
            <td>
                <select id="visiting_patient_report_select" class="input-xlarge select_report">
                    <option value="0" class="none">-- Select a Report  --</option>
                    <option class="date_range_report" value="getScheduledPatients">List of Patients Scheduled to Visit</option>
                    <option class="date_range_report" value="getPatientsStartedonDate">List of Patients Started (on a Particular Date)</option>
                    <option class="date_range_report" value="getPatientsforRefill">List of Patients Visited For Refill</option>
                    <option class="date_range_report" value="getPatientMissingAppointments">Patients Missing Appointments</option>
                    <option class="date_range_report" value="dispensingReport">Patients Visit Summary</option>
                    <option class="date_range_report" value="get_viral_load_results">List of Patient Viral Load Results</option>
                    <option class="date_range_report" value="getPatientList">List of Patients on a given Drug </option>
                    <option class="single_date_report" value="distribution_refill">Multi-Monthly ARVs Dispensing (MMD) </option>
                    <option class="month_period_report" value="getMMD">Multi-Monthly Dispensing (MMD) FMAP </option>
                
                FMAPS_MMD_MMS
                </select></td>
        </tr>

        <tr id="differentiated_care_report_row" class="reports_types">
            <td><label >Select Report </label></td>
            <td>
                <select id="differentiated_care_report_select" class="input-xlarge select_report">
                    <option value="0" class="none">-- Select a Report  --</option>
                    <option id="PatientsOnDiffCare" class="date_range_report" value="getPatientsOnDiffCare">List of Patients on Differentiated Care</option>
                    <option class="date_range_report" value="getScheduledPatientsDiffCare">List of Patients Scheduled to Visit</option>
                    <option class="date_range_report" value="getPatientsStartedonDateDiffCare">List of Patients Started (on a Particular Date)</option>
                    <option class="date_range_report" value="getPatientsforRefillDiffCare">List of Patients Visited For Refill</option>
                   <!-- <option class="single_date_report" value="multi_month_arv_dispensing">Multi Month ARVs Dispensing (MMD) </option> -->
                    <!-- <option class="date_range_report" value="differenciated_package_of_care">Patients on Differentiated Care(Viral Load)</option> -->
                    <!-- <option class="date_range_report" value="get_differentiated_care_appointments">Patients on Differentiated Care(Appointments)</option> -->
                </select></td>
        </tr>
        <!-- Early warning reports -->
        <tr id="early_warning_report_row" class="reports_types">
            <td><label>Select Report </label></td>
            <td>
                <select id="early_warning_report_select" class="input-xlarge select_report">
                    <option value="0" class="none">-- Select a Report  --</option>
                    <option class="date_range_report" value="patients_who_changed_regimen">Active Patients who Have Changed Regimens</option>
                    <option class="date_range_report" value="patients_switched_to_second_line_regimen">Active Patients who Have Changed to second line Regimens</option>
                    <option class="date_range_report" value="patients_starting">List of Patients Starting (By Regimen)</option>
                    <!-- <option class="month_period_report" value="early_warning_indicators">HIV Early Warning Indicators</option> -->
                    <!--<option class="date_range_report" value="patients_adherence">Patients Adherence Report</option>-->
                    <option class="date_range_report" value="graphical_adherence">Patients Adherence Report</option>
                    <option class="date_range_report" value="patients_nonadherence">Patients Non Adherence Report</option>
                    <option class="date_range_report" value="get_lost_followup">Lost to Followup Report</option>
                    <option class="single_date_report" value="get_viral_loadsummary">Viral Load Suppression Report</option>
                    <!--<option class="single_date_report" value="service_statistics">Service Statistics (By Regimen)</option>-->

                </select></td>
        </tr>
        <!-- Drug inventory reports -->
        <tr id="drug_inventory_report_row" class="reports_types">
            <td><label >Select Report </label></td>
            <td>
                <select id="drug_inventory_report__select" class="input-xlarge select_report">
                    <option value="0" class="none">-- Select a Report  --</option>
                    <option id="drug_consumption" class="annual_report" value="stock_report/drug_consumption">Drug Consumption Report</option>
                    <option id="patient_consumption" class="month_period_report" value="patient_consumption">Patient Drug Consumption Report</option>	
                    <option id="drug_stock_on_hand" class="month_range_report" value="stock_report/drug_stock_on_hand">Drug Stock on Hand Report</option>
                    <option id="commodity_summary" class="month_range_report" value="stock_report/commodity_summary">Facility Summary Commodity Report</option>
                    <option id="expiring_drugs" class="no_filter" value="expiring_drugs">Short Dated Stocks &lt;6 Months to Expiry</option>
                    <option id="expired_drugs" class="no_filter" value="expired_drugs">List of Expired Drugs</option>
                    <option id="getFacilityConsumption" class="date_range_report" value="getFacilityConsumption">Stock Consumption</option>
                    <option id="getDailyConsumption" class="date_range_report" value="getDailyConsumption">Daily Drug Consumption</option>
                    <option id="getDrugsIssued" class="date_range_report" value="getDrugsIssued">Drugs Issued at</option>
                    <option id="getDrugsReceived" class="date_range_report" value="getDrugsReceived">Drugs Received at</option>
                </select></td>
        </tr>
        <!--MOH Form-->
        <tr id="moh_forms_report_row" class="reports_types">
            <td><label >Select Report </label></td>
            <td>
                <select id="moh_forms_report__select" class="input-xlarge select_report">
                    <option value="0" class="none">-- Select a Report  --</option>
                    <option class="month_period_report" value="getMOHForm/711">GET MOH 711 </option>
                    <option class="month_period_report" value="getMOHForm/731">GET MOH 731 </option>
                </select></td>
        </tr>
        <!-- guidelines-->
        <tr id="guidelines_report_row" class="reports_types">
        </tr>
        <tr id="DRUGFILTER" class="select_drug">
            <td>
                <strong class="drugselector" style="display: none;">Select Drug</strong> 
                <select id="drugselector" class="select2 drugselector" style="display: none;">
                    <option value="">-Select Drug-</option>

                </select>
            </td>
        </tr>

        <tr>
            <!-- Select report range donors -->
        <table id="donor_date_range_report" class="select_types">
            <tr>
                <td><label >Select Donor : </label></td>
                <td>
                    <select id="donor" class="input-medium">
                        <option value="0">--All Donor--</option>
                        <option value="1">GOK</option>
                        <option value="2">PEPFAR</option>
                    </select>



                </td>
            </tr>
            <tr>
                <td><label>From : </label></td>
                <td>
                    <input type="text" name="donor_date_range_from" id="donor_date_range_from" class="input-medium donor_input_dates_from">
                </td>
                <td><label >To : </label></td>
                <td>
                    <input type="text" name="donor_date_range_to" id="donor_date_range_to" class="input-medium donor_input_dates_to">
                </td>
            </tr>
            <tr>
                <td>
                    <input type="button" id="donor_generate_date_range_report" class="btn btn-warning generate_btn" value="Generate Report">
                </td>
            </tr>
        </table>
        <!-- Report year -->
        <table id="year" class="select_types">
            <tr>
                <td><label>Select Year : </label></td>
                <td>
                    <input type="text" name="filter_year" id="single_year_filter" class="input-medium input_year" />
                </td>
                <td>
                    <select name="pack_unit" id="pack_unit">
                        <option value="unit">Units</option>
                        <option value="pack">Packs</option>	
                    </select>

                </td>
                <td>
                    <input type="button" id="generate_single_year_report" class="btn btn-warning generate_btn" value="Generate Report">
                </td>
            </tr>
        </table>
        <!-- Report single date -->
        <table id="single_date" class="select_types">
            <tr>
                <td><label>Select Date : </label></td>
                <td>
                    <input type="text" name="filter_date" id="single_date_filter" class="input-medium input_dates" />
                </td>
                <td>
                    <input type="button" id="generate_single_date_report" class="btn btn-warning generate_btn" value="Generate Report">
                </td>
            </tr>
        </table>
        <!-- Report date range -->
        <table id="date_range_report" class="select_types">
            <tr>
                <td class="show_report_type"><label>Select Report Type :</label></td>
                <td class="show_report_type">
                    <select name="commodity_summary_report_type" id="commodity_summary_report_type" class="report_type input-large">
                        <option value="0">-- Select Report Type --</option>
                        <?php
                        foreach ($ccc_stores as $key => $value) {
                            echo "<option value='" . $value['id'] . "'>" . $value['Name'] . "</option>";
                        }
                        ?>
                    </select></td>
                <td class="adherence_report_type_title"><label >Adherence Report By: </label></td>
                <td class="adherence_report_type_title"><select name="adherence_type_report" id="adherence_type_report">
                        <option value="appointment">Appointment</option>
                        <option value="pill_count">Pill Count</option>
                        <option value="missed_pill">Missed Pill</option>
                    </select></td>
                <td>
                <td><label >From: </label></td>
                <td><input type="text" name="date_range_from" id="date_range_from" class="input-medium input_dates_from"></td>
                </td>
                <td><label >To: </label></td>
                <td>
                    <input type="text" name="date_range_to" id="date_range_to" class="input-medium input_dates_to">
                </td>
                <td class="service_report_type_title" style="display: none;">
                    <select name="gender_type" id="report_gender">
                        <option value="male">Male</option>
                        <option value="female">Female</option>
                    </select>
                </td>
                
                <td class="service_report_type_title" style="display: none;">
                    <select name="age_type" id="report_age">
                        <option value="below4">Below 4 weeks</option>
                        <option value="4weeks">4 weeks to 4 years</option>
                        <option value="5years">5 years - 9 years</option>
                        <option value="10years">10 years - 14 years</option>
                        <option value="15years">15 years - 19 years</option>
                        <option value="20years">20 years - 24 years</option>
                        <option value="25years">25 years - 29 years</option>
                        <option value="30years">30 years - 34 years</option>
                        <option value="35years">35 years - 39 years</option>
                        <option value="40years">40 years - 44 years</option>
                        <option value="45years">45 years - 49 years</option>
                        <option value="above49">above 49 years</option>
                    </select>
                </td>

                 <td class="clinical_band_report_type_title" style="display: none;">
                    <select name="gender_type" id="report_gender">
                        <option value="male">Male</option>
                        <option value="female">Female</option>
                    </select>
                </td>
                
                <td class="clinical_band_report_type_title" style="display: none;">
                    <select name="age_type" id="clinical_report_age">
                        <option value="below1">Below 1 year</option>
                        <option value="1year">1 Year -  4 years</option>
                        <option value="5years">5 years - 9 years</option>
                        <option value="10years">10 years - 14 years</option>
                        <option value="15years">15 years - 19 years</option>
                        <option value="20years">20 years - 24 years</option>
                        <option value="25years">25 years - 29 years</option>
                        <option value="30years">30 years - 34 years</option>
                        <option value="35years">35 years - 39 years</option>
                        <option value="40years">40 years - 44 years</option>
                        <option value="45years">45 years - 49 years</option>
                        <option value="above49">above 49 years</option>
                    </select>
                </td>
                

                <td>
                    <input type="button" id="generate_date_range_report" class="btn btn-warning generate_btn" value="Generate Report">
                </td>
            </tr>
        </table>
        <!-- Report Month range -->
        <table id="month_range_report" class="select_types">
            <tr>
                <td class="show_report_type"><label>Select Report Type :</label></td>
                <td class="show_report_type">
                    <select name="commodity_summary_report_type" id="commodity_summary_report_type" class="report_type input-large">
                        <!--<option value="0">-- Select Report Type --</option>-->
                        <?php
                        foreach ($ccc_stores as $key => $value) {
                            echo "<option value='" . $value['id'] . "'>" . $value['Name'] . "</option>";
                        }
                        ?>
                    </select></td>
                <td>
                    <input class="_green" name="reporting_period" id="reporting_period" type="text" placeholder="Select Period">
                    <input name="date_range_from" id="period_start_date" type="hidden">
                    <input name="date_range_to" id="period_end_date" type="hidden">
                </td>
                <td>
                    <input type="button" id="generate_month_range_report" class="btn btn-warning generate_btn" value="Generate Report">
                </td>
            </tr>
        </table>
        <!--Month Period Picker-->
        <table id="month_period_report" class="select_types">
            <tr>
                <td class="show_report_type"><label>Select Period :</label></td>
                <td>
                    <input class="_green month_period" name="month_period" id="month_period" type="text" placeholder="Select Period">
                    <input name="date_range_from" id="month_start_date" type="hidden">
                    <input name="date_range_to" id="month_end_date" type="hidden">
                </td>
                <td>
                    <input type="button" id="generate_month_period_report" class="btn btn-warning generate_btn" value="Generate Report">
                </td>
            </tr>
        </table>
        <!-- Reports no filter -->
        <table id="no_filter" class="select_types">
            <tr  >
                <td class="show_report_type"><label>Select Report Type :</label></td>
                <td class="show_report_type">
                    <select name="commodity_summary_report_type_1" id="commodity_summary_report_type_1" class="report_type input-large">
                        <option value="0">-- Select Report Type --</option>
                        <?php
                        foreach ($ccc_stores as $key => $value) {
                            echo "<option value='" . $value['id'] . "'>" . $value['Name'] . "</option>";
                        }
                        ?>
                    </select></td>
                <td>
                    <input type="button" id="generate_no_filter_report" class="btn btn-warning generate_btn" value="Generate Report">
                </td>
            </tr>
        </table>
        </tr>
    </table>
</div>