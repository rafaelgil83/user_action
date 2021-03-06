// Made by Rafael Gil 2014

var App = App || {};

(function ($) {
    "use strict";

    App.org_change = App.org_change || {};
    $(document).ready(function () {
        $('#user').focus();

        $('#manual-add').on('click', function () {
            window.location.href = '/newHire';
        });

        $("#user").autocomplete({
            source: "/autocomplete",
            minLength: 2,
            search: function (event, ui) {
                $("#searchProgress").html("");
                $('<img src="images/wait.gif" align="middle">').load(function () {
                    $(this).width(23).height(23).appendTo('#searchProgress');
                });
            },
            response: function (event, ui) {
                $("#searchProgress").html("");
            },
            select: function (event, ui) {
                $("#user").val(ui.item.label);
                $("#errorDiv").html('');
                $.ajax({
                    type: "POST",
                    url: "org_change_lookup",
                    data: {email: ui.item.value},
                    beforeSend: function () {
                        $('<img src="images/wait.gif" align="middle">').load(function () {
                            $(this).width(52).height(52).appendTo('#report');
                        });
                        $('#report').html('Processing your request ...');
                    }
                })
                    .done(function (msg) {
                        $('#homeMenu').html('');

                        $('#report').html(App.templates.change_org({data: msg}));

                        $("#cancel").click(function () {
                            document.location = '/';
                        });

                        $("#effectiveDate").datepicker({
                            onSelect: function (dateText) {
                                $("#startDateError").html("");
                            }
                        });

                        changeName();
                        //set defaults
                        lookupDepartment(msg['fromAD']['department']);
                        lookupCompany(msg['fromAD']['company'])
                        findGroupMatch(msg['fromAD']["groups"]);

                        var verifiedManager = false;
                        var notVerifiedIcon = '<span title="User verified">&#10004;</span>';

                        $("#manager").keyup(function () {
                            verifiedManager = false;
                        });
                        $("#manager").blur(function () {
                            if (!verifiedManager) {
                                $("#searchProgressManager").html("<span title='This user has been not found in our database.'>&#10007;</span>");
                            }
                        });
                        $("#manager").autocomplete({
                            source: "/autocomplete",
                            minLength: 2,
                            search: function (event, ui) {
                                if (verifiedManager) {
                                    $("#searchProgressManager").html("");
                                }
                                else {
                                    $("#searchProgressManager").html(notVerifiedIcon);
                                }

                                $('<img src="images/wait.gif" align="middle">').load(function () {
                                    $(this).width(23).height(23).appendTo('#searchProgressManager');
                                });
                            },
                            response: function (event, ui) {
                                if (!verifiedManager) {
                                    $("#searchProgressManager").html(notVerifiedIcon);
                                }
                            },
                            select: function (event, ui) {
                                $("#manager").val(ui.item.label);
                                $("#managerEmail").val(ui.item.value);
                                $('#searchProgressManager').html(notVerifiedIcon);
                                verifiedManager = true;
                                return false;
                            }
                        });

                    });
                return false;
            }
        });

        function changeName() {
            $("#name, #lastName").keyup(function () {
                $('#newEmail').val($('#name').val().toLowerCase() + '.' + $('#lastName').val().toLowerCase() + '@illy.com');
            });
        }

        function lookupDepartment(department) {
            if (department != undefined) {
                $("#department option").each(function (i) {
                    if ($(this).text() === department) {
                        $(this).prop('selected', true);
                    }
                });
            }
        }

        function lookupCompany(company) {
            if (company != undefined) {
                $("#company option").each(function (i) {
                    if ($(this).text() === company) {
                        $(this).prop('selected', true);
                    }
                });
            }

        }

        $("#org_change_save").submit(function () {
            $("#submit").attr('disabled', 'disabled');
        });

        $("#org_change").submit(function () {


            var canSubmit = true;

            if (typeof $('#name').val() === 'undefined') {

                return false;
            }


            $('#departmentError').html('*');
            if ($('#department').val() === "") {
                $('#departmentError').html('<div> * You have to choose a department before proceeding</div>');
                canSubmit = false;
            }

            $('#companyError').html('*');
            if ($('#company').val() === "") {
                $('#companyError').html('<div> * You have to choose a company before proceeding</div>');
                canSubmit = false;
            }


            if (canSubmit) {
                $("#submit").attr('disabled', 'disabled');
            }


            return canSubmit;
        });


    });
}(jQuery));

