class LocalStorage {
  constructor () {
    if (LocalStorage.instance) {
      return LocalStorage.instance
    }

    LocalStorage.instance = this
    return this
  }

  /**
     * @param {string} key
     */
  get (key) {
    return localStorage.getItem(key)
  }

  /**
     * @param {string} key
     * @param {string} value
     */
  set (key, value) {
    sessionStorage.setItem(key, value)
  }

  /**
     * @param {string} key
     */
  remove (key) {
    return localStorage.removeItem(key)
  }

  clear () {
    localStorage.clear()
  }
}

class SessionStorage {
  constructor () {
    if (SessionStorage.instance) {
      return SessionStorage.instance
    }

    SessionStorage.instance = this
    return this
  }

  /**
     * @param {string} key
     */
  get (key) {
    return sessionStorage.getItem(key)
  }

  /**
     * @param {string} key
     * @param {string} value
     */
  set (key, value) {
    sessionStorage.setItem(key, value)
  }

  /**
     * @param {string} key
     */
  remove (key) {
    return sessionStorage.removeItem(key)
  }

  clear () {
    sessionStorage.clear()
  }
}

export { LocalStorage, SessionStorage }
