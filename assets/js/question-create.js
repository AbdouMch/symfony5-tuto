import '../css/question_create.css';

import $ from 'jquery';
import 'bootstrap-datetime-picker';
import '../js/common/select2/autocomplete.js';

$(document).ready(() => {
    $('.js-datepicker').datetimepicker({
        todayBtn: "linked",
        clearBtn: true,
        autoclose: true,
        todayHighlight: true,
        format: 'yyyy-mm-dd hh:ii'
    });
});