import $ from 'jquery'
import flashService from 'toastr'

$(function () {
    const data = JSON.parse($('#js-flash-messages').html())
    data.forEach((flash) => {
        flashService[flash.type](flash.message, flash.title)
    })
})
