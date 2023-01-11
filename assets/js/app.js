/*
 * Welcome to your app's main JavaScript file!
 *
 * We recommend including the built version of this JavaScript file
 * (and its CSS file) in your base layout (base.html.twig).
 */

// any CSS you import will output into a single css file (app.css in this case)
import "bootstrap";
import '../css/app.css';

// Need jQuery? Install it with "yarn add jquery", then uncomment to import it.
import $ from 'jquery';
import {RealtimeChannel} from './common/realtime/realtime';


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
        if (realtimeChannelUrl) {
            const questionChannel = new RealtimeChannel(realtimeChannelUrl);
            questionChannel
                .connect()
                .onMessage()
                .then((data) => {
                    console.log(data)
                });
        }
    }
})
