<template>
  <q-page class="q-pa-md">
    <div class="q-pa-md" style="max-width: 800px; margin: 0 auto">
      <div class="row items-center justify-between q-mb-lg">
        <div class="text-h4 text-weight-bold text-grey-9">
          {{ isEdit ? 'Edit Supplier' : 'Add New Supplier' }}
        </div>
      </div>

      <q-card flat bordered class="q-pa-md">
        <q-form @submit.prevent="submit">
          
          <div class="row q-col-gutter-md">
            <div class="col-12">
              <q-input 
                v-model.trim="form.name" 
                label="Supplier Name *" 
                outlined 
                dense
                :rules="[val => !!val || 'Name is required']"
              />
            </div>

            <div class="col-12">
              <q-input 
                v-model.trim="form.description" 
                label="Description" 
                outlined 
                dense 
                type="textarea"
                rows="4"
              />
            </div>
          </div>

          <div class="row justify-end q-mt-lg q-gutter-sm">
            <q-btn 
              label="Cancel" 
              outline 
              color="grey-7" 
              to="/suppliers"
            />
            <q-btn 
              :label="isEdit ? 'Update Supplier' : 'Create Supplier'" 
              type="submit"
              color="primary"
              unelevated
              :loading="loading"
            />
          </div>

        </q-form>
      </q-card>
    </div>
  </q-page>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useQuasar } from 'quasar'
import axios from 'axios'

const router = useRouter()
const route = useRoute()
const $q = useQuasar()

const loading = ref(false)
const isEdit = computed(() => !!route.params.id)

const form = ref({
  name: '',
  description: ''
})

const fetchSupplier = async () => {
  if (!isEdit.value) return

  loading.value = true
  try {
    const response = await axios.get(`/admin/suppliers/${route.params.id}`)
    const data = response.data.supplier
    form.value = {
      name: data.name,
      description: data.description
    }
  } catch (error) {
    console.error('Error fetching supplier:', error)
    $q.notify({
      type: 'negative',
      message: 'Failed to load supplier details'
    })
    router.push('/suppliers')
  } finally {
    loading.value = false
  }
}

const submit = async () => {
  loading.value = true
  try {
    if (isEdit.value) {
      await axios.put(`/admin/suppliers/${route.params.id}`, form.value)
      $q.notify({
        type: 'positive',
        message: 'Supplier updated successfully'
      })
    } else {
      await axios.post('/admin/suppliers', form.value)
      $q.notify({
        type: 'positive',
        message: 'Supplier created successfully'
      })
    }

    router.push('/suppliers')

  } catch (error) {
    console.error('Error saving supplier:', error)
    $q.notify({
      type: 'negative',
      message: error.response?.data?.message || 'Something went wrong',
      caption: error.response?.data?.errors ? Object.values(error.response.data.errors).flat().join(', ') : ''
    })
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchSupplier()
})
</script>
