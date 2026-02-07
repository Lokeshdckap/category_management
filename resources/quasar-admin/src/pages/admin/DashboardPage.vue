<template>
  <q-page class="q-pa-md">

    <div class="text-h5 q-mb-md">
      Dashboard
    </div>

    <div class="row q-col-gutter-md">

      <div class="col-12 col-md-4">
        <q-card>
          <q-card-section>
            <div class="text-subtitle2">Total Categories</div>
            <div class="text-h6">12</div>
          </q-card-section>
        </q-card>
      </div>

      <div class="col-12 col-md-4">
        <q-card>
          <q-card-section>
            <div class="text-subtitle2">Featured Categories</div>
            <div class="text-h6">5</div>
          </q-card-section>
        </q-card>
      </div>

      <div class="col-12 col-md-4">
        <q-card>
          <q-card-section>
            <div class="text-subtitle2">Active Categories</div>
            <div class="text-h6">10</div>
          </q-card-section>
        </q-card>
      </div>

    </div>

  </q-page>
</template>

<script>
export default {
  name: 'DashboardPage'
}
</script>
