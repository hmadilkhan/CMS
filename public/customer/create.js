var moduleCost = 0;
var systemSize = 0;
var baseCost = 0;

$(document).ready(function() {
    $(".loandiv").css("display", "none");
});

function getFinanceOptionById(id) {
    $.ajax({
        method: "POST",
        url: window.location.origin + "/get-finance-option-by-id",
        data: {
            _token: $('meta[name="csrf_token"]').attr('content'),
            id: id,
        },
        dataType: 'json',
        success: function(response) {
            if (response.status == 200) {
                let finance = response.finance_options;
                if (finance.loan_id == 1) {
                    $("#loadIdDiv").css("display", "block");
                } else {
                    $("#loadIdDiv").css("display", "none");
                }
                if (finance.production_requirements == 1) {
                    $("#soldProductionValueDiv").css("display", "block");
                } else {
                    $("#soldProductionValueDiv").css("display", "none");
                }
                if (finance.dealer_fee == 1) {
                    $(".loandiv").css("display", "block");
                    getLoanTerms(id);
                } else {
                    $(".loandiv").css("display", "none");
                    $("#dealer_fee").val(0);
                    $("#dealer_fee_amount").val(0);
                    calculateCommission()
                }
            } else {
                console.log(response.message);
            }
        },
        error: function(error) {
            console.log(error.responseJSON.message);
        }
    })
}

$("#finance_option_id").change(function() {
    getFinanceOptionById($(this).val())
});

function getLoanTerms(id) {
    $.ajax({
        method: "POST",
        url: window.location.origin + "/get-loan-terms",
        data: {
            _token: $('meta[name="csrf_token"]').attr('content'),
            id: id,
        },
        dataType: 'json',
        success: function(response) {
            $('#loan_term_id').empty();
            $('#loan_term_id').append($('<option value="">Select Loan Term</option>'));
            $.each(response.terms, function(i, term) {
                $('#loan_term_id').append($('<option value="' + term.id + '">' + term.year + '</option>'));
            });
        },
        error: function(error) {
            console.log(error.responseJSON.message);
        }
    })
}

$("#loan_term_id").change(function() {
    if ($(this).val() != "") {
        $.ajax({
            method: "POST",
            url: window.location.origin + "/get-loan-aprs",
            data: {
                _token: $('meta[name="csrf_token"]').attr('content'),
                id: $(this).val(),
                finance_option_id: $("#finance_option_id").val(),
            },
            dataType: 'json',
            success: function(response) {
                $('#loan_apr_id').empty();
                $('#loan_apr_id').append($('<option value="">Select Loan Apr</option>'));
                $.each(response.aprs, function(i, apr) {
                    $('#loan_apr_id').append($('<option value="' + apr.id + '">' + (apr.apr * 100).toFixed(2) + '%</option>'));
                });
            },
            error: function(error) {
                console.log(error.responseJSON.message);
            }
        })
    }
});

$("#loan_apr_id").change(function() {
    if ($(this).val() != "") {
        getDealerFee($(this).val())
    }
});

function getDealerFee(value) {
    $.ajax({
        method: "POST",
        url: window.location.origin + "/get-dealer-fee",
        data: {
            _token: $('meta[name="csrf_token"]').attr('content'),
            id: value,
        },
        dataType: 'json',
        success: function(response) {
            dealerFee(response.dealerfee);
            calculateCommission()
        },
        error: function(error) {
            console.log(error.responseJSON.message);
        }
    })
}

function getRedlineCost() {
    let panelQty = $("#panel_qty").val();
    let inverterType = $("#inverter_type_id").val();
    let overwriteBaseCost = $("#overwrite_base_price").val();
    overwriteBaseCost = parseFloat(overwriteBaseCost);
    let overwritePanelCost = $("#overwrite_panel_price").val();
    overwritePanelCost = parseFloat(overwritePanelCost);

    $.ajax({
        method: "POST",
        url: window.location.origin + "/get-redline-cost",
        data: {
            _token: $('meta[name="csrf_token"]').attr('content'),
            qty: panelQty,
            inverterType: inverterType,
        },
        dataType: 'json',
        success: function(response) {
            $('#redline_costs').val('');
            if (response.modules.length > 0) {
                $("#module_type_id").empty();
                $('#module_type_id').append($('<option value="">Select Module Type</option>'));
                $.each(response.modules, function(i, user) {
                    $('#module_type_id').append($('<option value="' + user.id + '">' + user.name + '</option>'));
                });
            }
            baseCost = response.redlinecost;
            let redlinecost = response.redlinecost + overwriteBaseCost;
            $('#redline_costs').val(redlinecost);
        },
        error: function(error) {
            console.log(error.responseJSON.message);
        }
    })
    setTimeout(() => {
        if (panelQty != "" && inverterType != "") {
            let moduleQty = $("#module_qty").val();
            $("#module_qty").val(panelQty * moduleQty);
            let totalOverwritePanelCost = overwritePanelCost * panelQty;
            let redlinecost = baseCost + (panelQty * moduleCost) + overwriteBaseCost + totalOverwritePanelCost;
            $("#redline_costs").val(redlinecost);
        }
    }, 2000);
    calculateCommission()
}

function modulesType(id) {
    if (id != "") {
        $("#inverter_type_id").prop("disabled", false)
        $.ajax({
            method: "POST",
            url: window.location.origin + "/get-module-types",
            data: {
                _token: $('meta[name="csrf_token"]').attr('content'),
                id: id,
                inverterTypeId: $("#inverter_type_id").val()
            },
            dataType: 'json',
            success: function(response) {
                moduleCost = response.types.amount;
                systemSize = response.types.value;
                $("#module_qty").val(response.types.value);
            },
            error: function(error) {
                console.log(error.responseJSON.message);
            }
        })
    }
}

function calculateSystemSize() {
    let moduleQty = $("#module_qty").val();
    modulesType($("#module_type_id").val());
    let panelQty = $("#panel_qty").val();
    let inverterType = $("#inverter_type_id").val();
    let overwritePanelCost = $("#overwrite_panel_price").val();
    let overwriteBaseCost = $("#overwrite_base_price").val();
    let totalOverwritePanelCost = overwritePanelCost * panelQty;
    overwritePanelCost = parseFloat(overwritePanelCost);
    overwriteBaseCost = parseFloat(overwriteBaseCost);

    $("#module_qty").val(panelQty * systemSize);
    let redlinecost = baseCost + (panelQty * moduleCost) + overwriteBaseCost + totalOverwritePanelCost;
    $("#redline_costs").val(redlinecost);
}

function calculateSystemSizeAmount() {
    let panelQty = $("#panel_qty").val();
    let moduleQty = $("#module_qty").val();
    let overwritePanelCost = $("#overwrite_panel_price").val();
    let overwriteBaseCost = $("#overwrite_base_price").val();
    overwritePanelCost = parseFloat(overwritePanelCost);
    overwriteBaseCost = parseFloat(overwriteBaseCost);
    let totalOverwritePanelCost = overwritePanelCost * panelQty;
    $("#module_qty").val(panelQty * systemSize);
    let redlinecost = baseCost + (panelQty * moduleCost) + totalOverwritePanelCost + overwriteBaseCost;
    $("#redline_costs").val(redlinecost);
}

function dealerFee(value) {
    let dealerFee = (value != undefined ? value : parseFloat($("#dealer_fee").val()));
    let dealerPercentage = (dealerFee * 100).toFixed(2);
    let contractAmount = parseFloat($('#contract_amount').val());
    if (value != undefined) {
        $('#dealer_fee').val('');
        $('#dealer_fee').val(dealerPercentage);
    }
    if (contractAmount != "" && value != undefined) {
        $('#dealer_fee_amount').val(value * contractAmount);
    } else {
        $('#dealer_fee_amount').val((dealerFee / 100) * contractAmount);
    }
    calculateCommission()
}

function calculateCommission() {
    let contractAmount = parseFloat($("#contract_amount").val());
    let dealerFeeAmount = parseFloat($("#dealer_fee_amount").val());
    let redlineFee = parseFloat($("#redline_costs").val());
    let adders = parseFloat($("#adders_amount").val());
    let commission = contractAmount - dealerFeeAmount - redlineFee - adders;
    $("#commission").val(commission.toFixed(2));
}

$("#adders").change(function() {
    if ($(this).val() != "") {
        $.ajax({
            method: "POST",
            url: window.location.origin + "/get-adders",
            data: {
                _token: $('meta[name="csrf_token"]').attr('content'),
                adder: $(this).val(),
            },
            dataType: 'json',
            success: function(response) {
                $("#uom").val(response.adders.adder_unit_id).change();
                $("#amount").val(response.adders.price);
            },
            error: function(error) {
                console.log(error.responseJSON.message);
            }
        })
    }
})

$("#btnAdder").click(function() {
    let rowLength = $('#adderTable tbody').find('tr').length;
    let adders_id = $("#adders").val();
    let unit_id = $("#uom").val();
    let adders_name = $.trim($("#adders option:selected").text());
    let unit_name = $.trim($("#uom option:selected").text());
    let amount = $("#amount").val();
    if (unit_id == 3) {
        let moduleQty = $('#module_qty').val();
        let panelQty = $('#panel_qty').val();
        amount = amount * moduleQty;
    }
    if (unit_id == 5) {
        let moduleQty = $('#module_qty').val();
        let panelQty = $('#panel_qty').val();
        amount = amount * panelQty;
    }
    let result = checkExistence(adders_id, unit_id);
    if (result == false) {
        let newRow = "<tr id='row" + (rowLength + 1) + "'>" +
            '<input type="hidden" value="' + adders_id + '" name="adders[]" />' +
            '<input type="hidden" value="' + unit_id + '" name="uom[]" />' +
            '<input type="hidden" value="' + amount + '" name="amount[]" />' +
            "<td>" + (rowLength + 1) + "</td>" +
            "<td>" + adders_name + "</td>" +
            "<td>" + unit_name + "</td>" +
            "<td>" + amount + "</td>" +
            "<td colspan='4'>&nbsp;&nbsp;<i style='cursor: pointer;' class='icofont-trash text-danger' onClick=deleteItem(" +
            (rowLength + 1) + ")>Delete</i></td>" +
            "</tr>";

        $("#adderTable > tbody").append(newRow);
        calculateAddersAmount();
        emptyControls();
    } else {
        alert("already added")
    }
});

function deleteItem(id) {
    $("#row" + id).remove();
    calculateAddersAmount();
    calculateCommission();
}

function checkExistence(firstval, thirdval) {
    let result = false;
    $("#adderTable tbody tr").each(function(index) {
        let first = $(this).children().eq(0).val();
        let third = $(this).children().eq(2).val();
        if (firstval == first && thirdval == third) {
            result = true;
        } else {
            result = false;
        }
    });
    return result;
}

function calculateAddersAmount() {
    let adders_amount = 0;
    $("#adderTable tbody tr").each(function(index) {
        adders_amount += $(this).children().eq(6).text() * 1;
    });
    $("#adders_amount").val(adders_amount);
}

function emptyControls() {
    $("#adders").val('').change();
    $("#uom").val('').change();
    $("#amount").val('');
}

$("#sub_contractor_id").change(function() {
    $('#sub_contractor_user_id').empty();
    $.ajax({
        method: "POST",
        url: window.location.origin + "/get-subcontractors-users",
        data: {
            _token: $('meta[name="csrf_token"]').attr('content'),
            id: $(this).val(),
        },
        dataType: 'json',
        success: function(response) {
            $('#sub_contractor_user_id').append("<option value=''>Select Sub Contractor User</option> ");
            $.each(response.users, function(i, user) {
                $('#sub_contractor_user_id').append($('<option value="' + user.id + '">' + user.name + '</option>'));
            });
        },
        error: function(error) {
            console.log(error.responseJSON.message);
        }
    })
})

$("#sales_partner_id").change(function() {
    $('#sales_partner_user_id').empty();
    $.ajax({
        url: window.location.origin + "/get-sales-partner-users",
        method: "POST",
        data: {
            _token: $('meta[name="csrf_token"]').attr('content'),
            id: $(this).val(),
        },
        dataType: 'json',
        success: function(response) {
            $('#sales_partner_user_id').append("<option value=''>Select Sales Person User</option> ");
            $.each(response.users, function(i, user) {
                $('#sales_partner_user_id').append($('<option value="' + user.id + '">' + user.name + '</option>'));
            });
        },
        error: function(error) {
            console.log(error.responseJSON.message);
        }
    })
})

$("#sales_partner_user_id").change(function() {
    $.ajax({
        method: "POST",
        url: window.location.origin + "/sales-partner-overwrite-prices",
        data: {
            _token: $('meta[name="csrf_token"]').attr('content'),
            id: $(this).val(),
        },
        dataType: 'json',
        success: function(response) {
            $("#overwrite_base_price").val(response.overwrites.overwrite_base_price)
            $("#overwrite_panel_price").val(response.overwrites.overwrite_panel_price)
            getRedlineCost();
            calculateSystemSize();
            calculateSystemSizeAmount();
        },
        error: function(error) {
            console.log(error.responseJSON.message);
        }
    })
});

$("#module_type_id").change(function() {
    modulesType($(this).val());
});

// Schedule Survey Checkbox Toggle
$("#schedule_survey").change(function() {
    console.log("cHECKBOX CHECKED");
    
    if ($(this).is(':checked')) {
        $("#departmentReviewSection").slideDown(300);
        // Make fields required
        $("#utility_company, #ntp_approval_date, #hoa").attr('required', true);
        $("#contract_pdf, #cpuc_pdf, #disclosure_document, #electronic_signature").attr('required', true);
    } else {
        $("#departmentReviewSection").slideUp(300);
        // Remove required attribute
        $("#utility_company, #ntp_approval_date, #hoa, #hoa_phone_number").attr('required', false);
        $("#contract_pdf, #cpuc_pdf, #disclosure_document, #electronic_signature").attr('required', false);
        // Clear values
        $("#utility_company, #ntp_approval_date, #hoa, #hoa_phone_number").val('');
        $("#contract_pdf, #cpuc_pdf, #disclosure_document, #electronic_signature").val('');
        $("#hoa_select").hide();
    }
});

// HOA Dropdown Toggle
$("#hoa").change(function() {
    if ($(this).val() == "yes") {
        $("#hoa_select").slideDown(300);
        $("#hoa_phone_number").attr('required', true);
    } else {
        $("#hoa_select").slideUp(300);
        $("#hoa_phone_number").attr('required', false);
        $("#hoa_phone_number").val('');
    }
});

// Form Validation Before Submit
$('form').on('submit', function(e) {
    if ($("#schedule_survey").is(':checked')) {
        let isValid = true;
        let errorMessages = [];
        
        // Clear previous error messages
        $('.text-danger.message').html('');
        
        // Validate department fields
        if ($("#utility_company").val() == '') {
            $("#utility_company_message").html('Utility Company is required');
            isValid = false;
        }
        if ($("#ntp_approval_date").val() == '') {
            $("#ntp_approval_date_message").html('NTP Approval Date is required');
            isValid = false;
        }
        if ($("#hoa").val() == '') {
            isValid = false;
            errorMessages.push('HOA selection is required');
        }
        if ($("#hoa").val() == 'yes' && $("#hoa_phone_number").val() == '') {
            $("#hoa_phone_number_message").html('HOA Phone Number is required');
            isValid = false;
        }
        
        // Validate file uploads
        if (!$("#contract_pdf")[0].files.length) {
            $("#contract_pdf_message").html('Contract PDF is required');
            isValid = false;
        }
        if (!$("#cpuc_pdf")[0].files.length) {
            $("#cpuc_pdf_message").html('CPUC PDF is required');
            isValid = false;
        }
        if (!$("#disclosure_document")[0].files.length) {
            $("#disclosure_document_message").html('Disclosure Document is required');
            isValid = false;
        }
        if (!$("#electronic_signature")[0].files.length) {
            $("#electronic_signature_message").html('Electronic Signature Certificate is required');
            isValid = false;
        }
        
        if (!isValid) {
            e.preventDefault();
            Swal.fire({
                icon: 'error',
                title: 'Validation Error',
                text: 'Please fill all required department fields and upload all required documents.',
            });
            return false;
        }
    }
});
