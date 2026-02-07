<template>
  <q-layout view="lHh Lpr lFf">

    <!-- TOP NAVBAR -->
    <q-header elevated class="bg-primary text-white">
      <q-toolbar>
        <q-btn
          flat
          dense
          round
          icon="menu"
          @click="leftDrawerOpen = !leftDrawerOpen"
        />

        <q-toolbar-title>
          Admin Panel
        </q-toolbar-title>

        <q-btn
          flat
          round
          icon="logout"
          @click="logout"
        />
      </q-toolbar>
    </q-header>

    <!-- SIDEBAR -->
    <q-drawer
      v-model="leftDrawerOpen"
      show-if-above
      bordered
      class="bg-grey-1"
    >
      <q-list padding>

        <!-- <q-item clickable v-ripple to="/admin">
          <q-item-section avatar>
            <q-icon name="dashboard" />
          </q-item-section>
          <q-item-section>Dashboard</q-item-section>
        </q-item> -->

        <q-item clickable v-ripple to="/categories">
          <q-item-section avatar>
            <q-icon name="category" />
          </q-item-section>
          <q-item-section>Categories</q-item-section>
        </q-item>

      </q-list>
    </q-drawer>

    <!-- PAGE CONTENT -->
    <q-page-container>
      <router-view />
    </q-page-container>

  </q-layout>
</template>

<script>
export default {
  name: 'AdminLayout',

  data () {
    return {
      leftDrawerOpen: true
    }
  },

  methods: {
    logout () {
      localStorage.removeItem('auth_token')
      localStorage.removeItem('user')

      this.$router.push('/login')
    }
  }
}
</script>
