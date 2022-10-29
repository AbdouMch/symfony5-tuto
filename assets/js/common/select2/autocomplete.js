import "../../../css/common/select2/autocomplete.css";

import $ from "jquery";
import 'select2/dist/js/select2';

$(document).ready(() => {
    const $autocompleteInputs = $('.autocomplete-js');
    if (!$autocompleteInputs) {
        return;
    }
    $autocompleteInputs.each(function () {
        const $autocompleteInput = $(this);
        const apiUrl = $autocompleteInput.data('autocomplete-url');
        const searchField = $autocompleteInput.data('autocomplete-search-field');
        const operator = $autocompleteInput.data('autocomplete-search-operator');
        const limit = $autocompleteInput.data('autocomplete-page-size') || 10;
        const minimumInputLength = $autocompleteInput.data('autocomplete-search-length') || 2;
        const searchParam = `${searchField}[${operator}]`;

        $autocompleteInput.select2({
            theme: 'bootstrap4',
            minimumInputLength: minimumInputLength,
            minimumResultsForSearch: limit,
            ajax: {
                url: apiUrl,
                delay: 500,
                data: function (params) {
                    const query = {
                        page: params.page || 1,
                        limit: limit,
                    };
                    query[searchParam] = params.term;

                    return query;
                },
                processResults: function (response, params) {
                    params.page = params.page || 1;

                    const datas = $.map(response.result, function (data) {
                        return {
                            id: data[searchField],
                            text: data[searchField]
                        }
                    })

                    return {
                        results: datas,
                        pagination: {
                            more: (params.page * response.limit) < response.filtered_count
                        }
                    };
                }
            }
        })
    })
});