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
        />

        <q-input
          v-model="password"
          label="Password"
          type="password"
          outlined
          dense
          lazy-rules
          :rules="[val => !!val || 'Password is required']"
          class="q-mb-md"
        />

        <q-btn
          label="Login"
          type="submit"
          color="primary"
          unelevated
          class="full-width"
          :loading="loading"
        />

      </q-form>
    </q-card>
  </q-page>
</template>

<script>
export default {
  name: 'LoginPage',

  data () {
    return {
      email: '',
      password: '',
      loading: false
    }
  },

  methods: {
    async login () {
      this.loading = true

      try {
        const response = await this.$axios.post('/login', {
          email: this.email,
          password: this.password
        })



        console.log(response, 'login response');
        

        localStorage.setItem('token', response?.data?.data?.user?.token)

        this.$router.push('/admin')
      } catch (error) {
        this.$q.notify({
          type: 'negative',
          message: error.response?.data?.message || 'Login failed'
        })
      } finally {
        this.loading = false
      }
    }
  }
}
</script>
