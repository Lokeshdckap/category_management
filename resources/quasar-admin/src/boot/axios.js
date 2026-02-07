import axios from 'axios'

export default ({ app }) => {
  axios.defaults.baseURL = process.env.VITE_API_URL

  axios.interceptors.request.use(config => {
    const token = localStorage.getItem('token')
    if (token) {
      config.headers.Authorization = `Bearer ${token}`
    }
    return config
  })

  app.config.globalProperties.$axios = axios
}
