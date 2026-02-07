<template>
  <q-page class="flex flex-center bg-grey-2">
    <q-card class="q-pa-lg" style="width: 380px; max-width: 90vw;">
      
      <div class="text-h6 text-center q-mb-md">
        Admin Login
      </div>

      <q-form @submit.prevent="login">

        <q-input
          v-model="email"
          label="Email"
          type="email"
          outlined
          dense
          lazy-rules
          :rules="[val => !!val || 'Email is required']"
          class="q-mb-md"
        >
          <template v-slot:prepend>
            <q-icon name="email" />
          </template>
        </q-input>

        <q-input
          v-model="password"
          label="Password"
          :type="isPwd ? 'password' : 'text'"
          outlined
          dense
          lazy-rules
          :rules="[val => !!val || 'Password is required']"
          class="q-mb-md"
        >
          <template v-slot:prepend>
            <q-icon name="lock" />
          </template>
          <template v-slot:append>
            <q-icon
              :name="isPwd ? 'visibility_off' : 'visibility'"
              class="cursor-pointer"
              @click="isPwd = !isPwd"
            />
          </template>
        </q-input>

        <q-btn
          label="Login"
          type="submit"
          color="primary"
          unelevated
          class="full-width"
          :loading="loading"
        />

      </q-form>

      <!-- Error message display -->
      <q-banner v-if="errorMessage" class="bg-negative text-white q-mt-md" rounded>
        {{ errorMessage }}
      </q-banner>
    </q-card>
  </q-page>
</template>

<script>
import axios from 'axios'

export default {
  name: 'LoginPage',

  data () {
    return {
      email: '',
      password: '',
      loading: false,
      isPwd: true,
      errorMessage: ''
    }
  },

  methods: {
    async login () {
      this.loading = true
      this.errorMessage = ''

      try {
        const response = await axios.post('http://localhost:8000/api/login', {
          email: this.email,
          password: this.password
        })

        console.log(response, 'login response')

        // Store token and user data correctly
        const token = response?.data?.data.user.token
        const user = response?.data?.data.user

        localStorage.setItem('auth_token', token)
        localStorage.setItem('user', JSON.stringify(user))

        // Set authorization header for future requests
        axios.defaults.headers.common['Authorization'] = `Bearer ${token}`

        this.$q.notify({
          type: 'positive',
          message: 'Login successful!',
          icon: 'check'
        })

        // Redirect to dashboard
        this.$router.push('/dashboard')

      } catch (error) {
        console.error('Login error:', error)

        // Handle different error types
        if (error.response) {
          // Server responded with error
          if (error.response.status === 422) {
            // Validation errors
            this.errorMessage = error.response.data.errors?.email?.[0] || 'Invalid credentials'
          } else if (error.response.status === 401) {
            this.errorMessage = 'Invalid email or password'
          } else {
            this.errorMessage = error.response.data.message || 'Login failed'
          }
        } else if (error.request) {
          // Request made but no response
          this.errorMessage = 'Cannot connect to server. Please check your connection.'
        } else {
          // Something else happened
          this.errorMessage = 'An unexpected error occurred'
        }

        this.$q.notify({
          type: 'negative',
          message: this.errorMessage,
          icon: 'error'
        })
      } finally {
        this.loading = false
      }
    }
  },

  // Clear error when user starts typing
  watch: {
    email() {
      this.errorMessage = ''
    },
    password() {
      this.errorMessage = ''
    }
  }
}
</script>