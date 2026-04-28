import $ from 'jquery'
import Translator from 'bazinga-translator'

$(document).ready(() => {
  // Auto-show the new token modal when the page loads after a token was created
  const $modal = $('#js-new-token-modal')
  if ($modal.length) {
    $modal.modal('show')
  }

  // Copy token to clipboard
  $('#js-copy-token').on('click', function () {
    const token = document.getElementById('js-plain-token').value
    navigator.clipboard.writeText(token).then(() => {
      const $btn = $(this)
      $btn.html('<i class="fas fa-check mr-1"></i>' + Translator.trans('button.copied', {}, 'api_token'))
      setTimeout(() => {
        $btn.html('<i class="fas fa-copy mr-1"></i>' + Translator.trans('button.copy', {}, 'api_token'))
      }, 2000)
    })
  })

  // Confirm before deleting a token
  $('.js-token-delete-form').on('submit', function (e) {
    if (!window.confirm(Translator.trans('confirm.delete', {}, 'api_token'))) {
      e.preventDefault()
    }
  })
})
