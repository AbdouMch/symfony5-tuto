import '../../css/library/autocomplete.css';

import {ajax} from "rxjs/ajax";
import $ from 'jquery';

import {debounceTime, distinctUntilChanged, fromEvent, map, switchMap} from "rxjs";

function autocomplete($input) {
    const apiUrl = $input.data('autocomplete-url');
    const searchField = $input.data('autocomplete-search-field');
    const operator = $input.data('autocomplete-operator');

    const keyStroke$ = new fromEvent($input, 'input');

    const inputValue = keyStroke$.pipe(
        map(event => event.target.value),
        debounceTime(300),
        distinctUntilChanged(),
        switchMap(value => ajax.get(
            `${apiUrl}?${searchField}[${operator}]=${value}`
        )),
        map(httpResponse => httpResponse.response),
    );
    const resultClass = `${searchField}-autocomplete`;

    $(`
            <div class="${resultClass} list-group autocomplete-list">
            </div>
        `).insertAfter($input);
    inputValue.subscribe(datas => {
        const dataList = datas.result
            .map((data) => `<a href="#" class="list-group-item list-group-item-action autocomplete-item">
                                ${data[searchField]}
                            </a>`
            )
            .join('');

        $(`.${resultClass}`).html(`${dataList}`)
    });
}

$(document).ready(() => {
    const $autocompleteInput = $('.autocomplete-input');
    if (!$autocompleteInput) {
        return;
    }
    $autocompleteInput.each(function () {
        autocomplete($(this))
    })

})