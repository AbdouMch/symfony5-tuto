import $ from "jquery";
import {RealtimeChannel} from "../../common/realtime/realtime";
import {QuestionList} from "./questionList";


$(document).ready(() => {
    /**
     * Simple (ugly) code to handle the comment vote up/down
     */
    const $container = $('.js-vote-arrows');
    $container.find('a').on('click', function (e) {
        e.preventDefault();
        const $link = $(e.currentTarget);

        $.ajax({
            url: '/comments/10/vote/' + $link.data('direction'),
            method: 'POST'
        }).then(function (data) {
            $container.find('.js-vote-total').text(data.votes);
        });
    });

    const $questionList = $('#question-partial-list[class*="js-question-partial-list"]');
    if ($questionList) {
        const realtimeChannelUrl = $questionList.data('realtime_channel');
        const topic = $questionList.data('realtime_topic');
        if (realtimeChannelUrl) {
            const questionList = new QuestionList();
            const questionChannel = new RealtimeChannel(realtimeChannelUrl, topic, true);
            questionChannel
                .connect()
                .subscribe(questionList);
        }
    }
})
