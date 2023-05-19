import $ from 'jquery'
import { UrlGenerator } from '../../common/url-generator/url_generator'
import { SessionStorage } from '../../common/storage/storage'

function syncTimezone () {
  const storage = new SessionStorage()
  const syncKey = 'synced_timezone'
  if (storage.get(syncKey) === '1') {
    return
  }

  const url = UrlGenerator.generate('app_sync_timezone')
  const timezone = Intl.DateTimeFormat().resolvedOptions().timeZone
  $.post(url,
    {
      timezone
    },
    function (data) {
      if (data) {
        storage.set(syncKey, '1')
      }
    }
  )
}

export {
  syncTimezone
}
