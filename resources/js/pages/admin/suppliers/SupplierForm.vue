<template>
 <q-page class="q-pa-md">
    <q-card flat bordered>
        <div class="q-mt-md q-gutter-sm">
            <q-form @submit.prevent="submit">
            <q-input 
                v-model="form.name" 
                label="name" 
                outlined 
                dense 
              />
              <q-input 
                v-model="form.description" 
                label="description" 
                outlined 
                dense 
                type="textarea"
                rows="2"
              />
              <q-btn 
                label="save" 
                type="submit"
               />
              </q-form>
            </div>
    </q-card>
  </q-page>
  </template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useQuasar } from 'quasar'
import axios from 'axios'


const route = useRoute()
const router = useRouter()
const $q = useQuasar()



const form = ref({
    name:'',
    description:''
})

const submit = async () =>{
    try {

        await axios.post('/admin/supplier',form.value)
        
    } catch (error) {

        console.log(error);
        
        
    }
}


</script>