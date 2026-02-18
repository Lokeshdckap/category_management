<template>
  <q-page class="flex flex-center bg-grey-2">
    <q-card class="q-pa-lg" style="width: 400px; max-width: 90vw;">
      <div class="text-h6 text-center q-mb-md">Customer Login</div>

      <q-form @submit.prevent="login" class="q-gutter-md">
        <q-input
          v-model="form.email"
          label="Email"
          type="email"
          outlined
          dense
          lazy-rules
          :rules="[val => !!val || 'Email is required']"
        >
          <template v-slot:prepend>
            <q-icon name="email" />
          </template>
        </q-input>

        <q-input
          v-model="form.password"
          label="Password"
          :type="showPassword ? 'password' : 'text'"
          outlined
          dense
          lazy-rules
          :rules="[val => !!val || 'Password is required']"
        >
          <template v-slot:prepend>
            <q-icon name="lock" />
          </template>
          <template v-slot:append>
            <q-icon
              :name="showPassword ? 'visibility_off' : 'visibility'"
              class="cursor-pointer"
              @click="showPassword = !showPassword"
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

        <div class="text-center q-mt-md">
          Don't have an account? 
          <router-link to="/customer/register" class="text-primary text-weight-bold">Register</router-link>
        </div>
      </q-form>
    </q-card>
  </q-page>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useQuasar } from 'quasar'
import axios from 'axios'

const $q = useQuasar()
const router = useRouter()

const loading = ref(false)
const showPassword = ref(true)
const form = ref({
  email: '',
  password: ''
})

const login = async () => {
  loading.value = true
  try {
    const res = await axios.post('/api/customer/login', form.value)
    
    // Store token and redirect
    const { token, customer } = res.data.data
    localStorage.setItem('customer_token', token)
    localStorage.setItem('customer', JSON.stringify(customer))
    
    $q.notify({
      type: 'positive',
      message: 'Login successful!'
    })
    
    // Redirect to home or account page (adjust as needed)
    router.push('/')
  } catch (error) {
    console.error(error)
    if (error.response && error.response.status === 403) {
      $q.notify({
        type: 'warning',
        message: 'Account under verification once verified we will notify you',
        timeout: 5000,
        position: 'top'
      })
    } else {
      const message = error.response?.data?.message || 'Login failed'
      $q.notify({
        type: 'negative',
        message: message
      })
    }
  } finally {
    loading.value = false
  }
}
</script>
