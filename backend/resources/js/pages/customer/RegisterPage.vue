<template>
  <q-page class="flex flex-center bg-grey-2">
    <q-card class="q-pa-lg" style="width: 400px; max-width: 90vw;">
      <div class="text-h6 text-center q-mb-md">Customer Registration</div>

      <q-form @submit.prevent="register" class="q-gutter-md">
        <q-input
          v-model="form.name"
          label="Full Name"
          outlined
          dense
          lazy-rules
          :rules="[val => !!val || 'Name is required']"
        >
          <template v-slot:prepend>
            <q-icon name="person" />
          </template>
        </q-input>

        <q-input
          v-model="form.email"
          label="Email"
          type="email"
          outlined
          dense
          lazy-rules
          :rules="[
            val => !!val || 'Email is required',
            val => /.+@.+\..+/.test(val) || 'Please enter a valid email'
          ]"
        >
          <template v-slot:prepend>
            <q-icon name="email" />
          </template>
        </q-input>

        <q-input
          v-model="form.password"
          label="Password"
          :type="showPassword ? 'text' : 'password'"
          outlined
          dense
          lazy-rules
          :rules="[
            val => !!val || 'Password is required',
            val => val.length >= 8 || 'Minimum 8 characters'
          ]"
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
          label="Register"
          type="submit"
          color="primary"
          unelevated
          class="full-width"
          :loading="loading"
        />

        <div class="text-center q-mt-md">
          Already have an account? 
          <router-link to="/customer/login" class="text-primary text-weight-bold">Login</router-link>
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
const showPassword = ref(false)
const form = ref({
  name: '',
  email: '',
  password: ''
})

const register = async () => {
  loading.value = true
  try {
    const res = await axios.post('/api/customer/register', form.value)
    $q.notify({
      type: 'positive',
      message: res.data.message || 'Registration successful!',
      timeout: 5000
    })
    router.push('/customer/login')
  } catch (error) {
    console.error(error)
    const message = error.response?.data?.message || 'Registration failed'
    $q.notify({
      type: 'negative',
      message: message
    })
  } finally {
    loading.value = false
  }
}
</script>
