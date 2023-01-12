import {RealtimeSubscriberInterface} from "../../common/realtime/realtime";
import $ from "jquery";
import {UrlGenerator} from "../../common/url-generator/url_generator";

class QuestionList extends RealtimeSubscriberInterface {
    constructor() {
        super();
        this._url = UrlGenerator.generate('app_questions_partial_list');
        // this._url2 = UrlGenerator.generate('app_questions_list');
    }
    onmessage() {
        $.ajax({
            url: this._url,
            method: 'GET'
        }).then(function (data) {
            const $questionList = $('#question-partial-list[class*="js-question-partial-list"]');
            if ($questionList) {
                $questionList.html(data);
            }
        });
    }
}

export {QuestionList};