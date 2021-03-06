/**
 * Created by rafag on 3/21/15.
 */

var App = App || {};

(function ($) {
    "use strict";

    App.newHire = App.newHire || {};

    $(document).ready(function () {

        $('#department').change(function () {
            if ($('#department').val() === '') {
                $('#department').toggleClass("inputRender", true);
                $('#departmentError').html('*');
            }
            else {
                $('#department').toggleClass('validateError', false);
                $('#department').toggleClass('inputRender', true);
                $('#departmentError').html('');
            }
        });
        $('#location').change(function () {

            if ($('#location').val() != '') {
                $('#location').toggleClass('validateError', false);
                $('#location').toggleClass('inputRender', true);
                $('#locationError').html('');
                switch ($('#location').val()) {
                    case "Canada":
                        if ($('#company').val() != 'illy Espresso Canada') {
                            $('#company').val('illy Espresso Canada').change();
                        }
                        break
                }

            }

            $('#location_Other').val("");
            $('#location_Other_Span').hide();

            switch ($('#location').val()) {
                case "":
                    $('#location').toggleClass('inputRender', true);
                    $('#locationError').html('*');
                    break;
                case "Rye Brook":
                    $('#illyRyeBrook').prop("checked", true);
                    break;
                case "New York City":
                    $('#illyNYCTeam').prop("checked", true);
                    break;
                case "Canada":
                    $('#illyCanadaTeam').prop("checked", true);
                    break;
                case "Remote Users":
                    $('#location_Other_Span').show();
                    $('#location_Other').focus();
                    break;
            }
        });

        $('#laptop').click(function () {  // add/remove li element to the list
            if ($('#laptop').is(":checked")) {
                $("#prepareLaptop").after('<li id="deliveryDateli" style="margin: 0px 0px 0px 30px">Delivery Date <input type="text" class="inputRender" name="deliveryDate" id="deliveryDate" style="width: 100px"></li>');
                $("#prepareLaptop").after('<li id="ship" style="margin: 0px 0px 0px 20px"><label><input type="checkbox" class="inputRender" name="iTDept[]" id="laptopShipping" value="Laptop needs to be shipped to an outside location, please contact HR Manager for address if delivery necessary">Laptop needs to be shipped to an outside location, please contact HR Manager for address if delivery necessary</label></li>');

                if ($('#startDate').val() != '') {
                    var d = new Date($('#startDate').val());
                    d.setDate(d.getDate() - 3);

                    //yourDate.getDay()
                    if (d.getDay() == 6) {
                        d.setDate(d.getDate() + 2);
                        //alert('Saturday detected')
                    }
                    if (d.getDay() == 0) {
                        d.setDate(d.getDate() + 1);
                    }

                    var deliveryDate = (d.getMonth() + 1).toString() + '/' + d.getDate().toString() + '/' + d.getFullYear().toString();
                    $('#deliveryDate').val(deliveryDate);
                }


                $("#deliveryDate").datepicker({
                    onSelect: function (dateText) {
                        $("#startDateError").html("");
                    }
                });
            }
            else {
                $("#ship").remove();
                $("#deliveryDateli").remove();
            }
        });


        $('#cancel').click(function () {  // cancel the new hire and move to the main page
            window.location.href = "/";
        });

        $('#company').change(function() {
            $('#companyError').html('');
            switch ($('#company').val()) {
                case "illy Espresso Canada":
                    if ($('#location').val() != 'Canada') {
                        $('#location').val('Canada').change();
                    }
                    break
            }

        });

        $('#department').change(function() {
            $('#departmentError').html('');
        });

        $('#hireStatus').change(function() {
            $('#hireStatusError').html('');
        });

        $('#salaryType').change(function() {
            $('#salaryTypeError').html('');
        });

        $('#location').change(function() {
            $('#locationError').html('');
        });

        $('#newHire').submit(function (event) {
            // VALIDATION

            var firstError = '';

            if ($('#startDate').val().length < 2) {
                $('#startDateError').html('<div class="errorSpan"> * You have to choose a start date before proceeding</div>');
                firstError = '#startDate';
            }
            else {
                $('#startDateError').html('<span class="errorSpan"> * </span>');
            }


            if ($('#company').val() == "") {
                $('#companyError').html('<div class="errorSpan"> * You have to choose a company before proceeding</div>');
                if (firstError == '') {
                    firstError = '#company';
                }
            }
            else {
                $('#companyError').html('<span class="errorSpan"> *</span>');
            }

            if ($('#department').val() == "") {
                $('#departmentError').html('<div class="errorSpan"> * You have to choose a department before proceeding</div>');
                if (firstError == '') {
                    firstError = '#department';
                }
            }
            else {
                $('#departmentError').html('<span class="errorSpan"> *</span>');
            }

            if ($('#location').val() == "") {
                $('#locationError').html('<div class="errorSpan"> * You must choose a location before proceeding</div>');
                if (firstError == '') {
                    firstError = '#location';
                }
            }
            else {
                $('#locationError').html('<span class="errorSpan"> * </span>');
            }


            if ($('#hireStatus').val() == "") {
                $('#hireStatusError').html('<div class="errorSpan"> * You have to choose a hire status before proceeding</div>');
                if (firstError == '') {
                    firstError = '#hireStatus';
                }
            }
            else {
                $('#hireStatusError').html('<span class="errorSpan"> * </span>');
            }

            if ($('#salaryType').val() == "") {
                $('#salaryTypeError').html('<div class="errorSpan"> * You must choose a salary category before proceeding</div>');
                if (firstError == '') {
                    firstError = '#salaryType';
                }
            }
            else {
                $('#salaryTypeError').html('<span class="errorSpan"> * </span>');
            }


            if (firstError == '') {

                $("#submit").attr('disabled', 'disabled');
                return true;
            }
            else {

                // roll the page to the first element
                var offset = $(firstError).offset();
                $("html, body").animate({scrollTop: offset.top}, "slow");
                return false;

            }

        });

        $("#startDate").datepicker({"dateFormat": "mm/dd/yy"});


        var d = new Date();
        var n = d.getFullYear() - 30;
        $("#birthDate").datepicker({
            defaultDate: new Date('23 February ' + n),
            changeYear: true,
            changeMonth: true,
            "dateFormat": "mm/dd/yy",
            onSelect: function (dateText) {
                $("#startDateError").html("");
            }
        });

        $("#benefitDate").datepicker({
            "dateFormat": "mm/dd/yy",
            onSelect: function (dateText) {
                $("#startDateError").html("");
            }
        });

        $("#payrollDate").datepicker({
            "dateFormat": "mm/dd/yy",
            onSelect: function (dateText) {
                $("#startDateError").html("");
            }
        });

        $("#HRB").datepicker({
            "dateFormat": "mm/dd/yy",
            onSelect: function (dateText) {
                $("#startDateError").html("");
            }
        });

        $("#manager").autocomplete({
            source: "/autocomplete",
            minLength: 2,
            search: function (event, ui) {
                $("#searchProgress").html("");
                $("#managerEmail").val("");
                $('<img src="images/wait.gif" align="middle">').load(function () {
                    $(this).width(23).height(23).appendTo('#searchProgress');
                });
            },
            response: function (event, ui) {
                $("#searchProgress").html("");
            },
            select: function (event, ui) {
                $("#manager").val(ui.item.label);
                $("#managerEmail").val(ui.item.value);
                return false;
            }
        });

    });
}(jQuery));