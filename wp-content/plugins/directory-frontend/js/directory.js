var directory = angular.module('directory', []);

var DirectoryController = function($http, $element) {
    var vm = this;

    vm.searchDirectory = searchDirectory;

    function searchDirectory() {
        /*
        {
            "What is your postcode?":"a",
            "How far are you willing to travel?":"5km",
            "What issues are you seeking help for?":"Accommodation, Alcohol and other drugs, Anger management",
            "Do you have a preference for the gender of your counsellor?":"Male"
        }
        */
        formValues = getFormValues();
        var issues = formValues['What issues are you seeking help for?'];
        var gender = formValues['Do you have a preference for the gender of your counsellor?'];
        gender = gender == 'No preference' ? '' : gender;
        jQuery.each(issues.split(','), function(i, e){
            jQuery("#buddypress #field_4 option[value='" + e + "']").prop('selected', true);
        });
        if (gender) {
            var $radios = jQuery('#buddypress input:radio[name=field_22]');
            $radios.filter('[value=' + gender + ']').prop('checked', true);
        }
        jQuery('#bps_shortcode258').submit();
        // $http.post('http://mychristiancounsellor.org.au/wp-admin/admin-ajax.php', jQuery.param({action: 'search_directory', data: formValues}),
        //     {headers: {'Content-Type': 'application/x-www-form-urlencoded'}
        // }).then(successCallback, errorCallback);

        // function successCallback(response) {
        //     console.log(response);
        // }

        // function errorCallback(response) {
        //     console.log(response.status);
        // }
    }

    // Open sourced at https://github.com/mlooft/multi-step-form
    function textSummary(summaryObj, $block, title) {
        var value = $block.find('.fw-text-input').val();
        summaryObj[title] = value;
    }

    // function textareaSummary(summaryObj, $block, title) {
    //     var header = $block.find('h3').text();
    //     var value = $block.find('.fw-textarea').val();
    // }

    function radioSummary(summaryObj, $block, title) {
        var header = $block.find('h3').text();
        var value = '';
        $block.find('.fw-choice').each(function(idx, element) {
            if (jQuery(element).find('input').is(':checked')) {
                if (value != '') {
                    value += ',';
                }
                value += jQuery(element).find('label').text();
            }
        });
        summaryObj[title] = value;
    }

    function selectSummary(summaryObj, $block, title) {
        var header = $block.find('h3').text();
        var value = $block.find('select').select2('data')[0].text;
        summaryObj[title] = value;
    }

    // function checkboxSummary(summaryObj, $block, title) {
    //     var header = $block.find('label').text();
    //     var value;
    //     if ($block.find('.fw-checkbox').is(':checked')) {
    //         value = 'yes';
    //     }
    //     if ($block.hasClass('fw-block-invalid')) {
    //         console.log('INVALID' + $block);
    //     }
    // }

    function getStepCount($wizard) {
        return $wizard.attr('data-stepCount');
    }

    function getFormValues() {
        var $wizard = jQuery('.fw-wizard');
        var i;
        var stepCount = getStepCount($wizard);
        var fieldsObj = {};
        for (i = 0; i < stepCount; i++) {
            getStepValues($wizard, i, fieldsObj);
        }
        return fieldsObj;
    }

    function getStepValues($wizard, stepNum, fieldsObj) {
        var fields = '';
        var $step = $wizard.find('.fw-wizard-step[data-stepId="' + stepNum + '"]');
        $step.find('.fw-step-part').each(function(idx, element) {
            var title = jQuery(element).find('.fw-step-part-title').text().trim();
            jQuery(element).find('.fw-step-block').each(function(idx, element) {
                switch (jQuery(element).attr('data-type')) {
                    case 'fw-email':
                    case 'fw-date':
                    case 'fw-text':
                        fields = textSummary(fieldsObj, jQuery(element), title);
                        break;
                    // case 'fw-textarea':
                    //     textareaSummary(fieldsObj, jQuery(element), title);
                    //     break;
                    case 'fw-radio':
                        radioSummary(fieldsObj, jQuery(element), title);
                        break;
                    case 'fw-select':
                        selectSummary(fieldsObj, jQuery(element), title);
                        break;
                    // case 'fw-checkbox':
                    //     checkboxSummary(fieldsObj, jQuery(element), title);
                    //     break;
                    default:
                        break;
                }
            });
        });
    }
}

DirectoryController.$inject = ['$http', '$element'];

directory.controller('DirectoryController', DirectoryController);
